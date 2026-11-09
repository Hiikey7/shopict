<?php
require __DIR__ . '/../includes/admin.php';
$admin = require_admin();

$saveSetting = function (string $key, ?string $value): void {
    db()->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')->execute([$key, $value]);
};
$parseSettingDate = static function (?string $value, string $label): ?string {
    if ($value === null) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $date->format('Y-m-d\TH:i') !== $value) {
        throw new RuntimeException("The {$label} date and time is not valid.");
    }
    return $date->format('Y-m-d H:i:s');
};

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'maintenance') {
            $saveSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
            flash(isset($_POST['maintenance_mode']) ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.');
        } elseif ($action === 'store') {
            $ends = post_str('deal_ends_at');
            if ($ends !== null) {
                $ts = strtotime($ends);
                if ($ts === false) {
                    throw new RuntimeException('The deal end date is not valid.');
                }
                $ends = date('Y-m-d H:i:s', $ts);
            }
            $saveSetting('deal_ends_at', $ends);
            $saveSetting('quickview_default_product', (string) (post_int('quickview_default_product') ?? ''));
            if ($logo = upload_image('logo')) {
                $saveSetting('logo', $logo['path']);
                rebuild_app_icons($logo['path']); // app icon, home-screen icon and favicon follow the logo
            } elseif (!empty($_POST['logo_reset'])) {
                $saveSetting('logo', null);
                rebuild_app_icons('images/logo/logo.png');
            }
            if ($img = upload_image('hero_bg_image')) {
                $saveSetting('hero_bg_image', $img['path']);
            }
            flash('Settings saved.');
        } elseif ($action === 'promotion') {
            $enabled = isset($_POST['promo_popup_enabled']) ? '1' : '0';
            $title = trim((string) post_str('promo_popup_title'));
            $description = trim((string) post_str('promo_popup_description'));
            $actionType = (string) post_str('promo_popup_action_type');
            $actionLabel = trim((string) post_str('promo_popup_action_label'));
            $productId = post_int('promo_popup_product_id');
            $promoCode = trim((string) post_str('promo_popup_code'));
            $startsAt = $parseSettingDate(post_str('promo_popup_starts_at'), 'Popup start');
            $endsAt = $parseSettingDate(post_str('promo_popup_ends_at'), 'Popup end');

            if (!in_array($actionType, ['product', 'promo_code'], true)) {
                throw new RuntimeException('Choose a valid popup action.');
            }
            if ($startsAt !== null && $endsAt !== null && $startsAt > $endsAt) {
                throw new RuntimeException('The popup end must be after its start.');
            }
            if (mb_strlen($title) > 120 || mb_strlen($description) > 600 || mb_strlen($actionLabel) > 40 || mb_strlen($promoCode) > 100) {
                throw new RuntimeException('Popup text is too long. Shorten the title, description, button label or promo code.');
            }
            if ($enabled === '1' && ($title === '' || $description === '' || $actionLabel === '')) {
                throw new RuntimeException('An enabled popup needs a title, description and button label.');
            }
            if ($enabled === '1' && $productId) {
                $stmt = db()->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
                $stmt->execute([$productId]);
                if (!$stmt->fetchColumn()) {
                    throw new RuntimeException('Choose an active product for the popup link.');
                }
            }
            if ($enabled === '1' && $actionType === 'product' && !$productId) {
                throw new RuntimeException('Choose an active product for the popup.');
            }
            if ($enabled === '1' && $actionType === 'promo_code' && $promoCode === '') {
                throw new RuntimeException('Enter the promo code visitors should copy.');
            }

            $image = upload_image('promo_popup_image');
            $saveSetting('promo_popup_enabled', $enabled);
            $saveSetting('promo_popup_title', $title);
            $saveSetting('promo_popup_description', $description);
            $saveSetting('promo_popup_action_type', $actionType);
            $saveSetting('promo_popup_action_label', $actionLabel);
            $saveSetting('promo_popup_product_id', $productId ? (string) $productId : null);
            $saveSetting('promo_popup_code', $promoCode);
            $saveSetting('promo_popup_starts_at', $startsAt);
            $saveSetting('promo_popup_ends_at', $endsAt);
            if ($image) {
                $saveSetting('promo_popup_image', $image['path']);
            } elseif (!empty($_POST['promo_popup_image_reset'])) {
                $saveSetting('promo_popup_image', null);
            }
            flash('Promotion popup saved.');
        } elseif ($action === 'seasonal') {
            $effect = (string) post_str('seasonal_effect');
            $startsAt = $parseSettingDate(post_str('seasonal_starts_at'), 'Seasonal effect start');
            $endsAt = $parseSettingDate(post_str('seasonal_ends_at'), 'Seasonal effect end');
            if (!in_array($effect, ['off', 'black_november', 'christmas'], true)) {
                throw new RuntimeException('Choose a valid seasonal effect.');
            }
            if ($startsAt !== null && $endsAt !== null && $startsAt > $endsAt) {
                throw new RuntimeException('The seasonal effect end must be after its start.');
            }
            $saveSetting('seasonal_effect', $effect);
            $saveSetting('seasonal_starts_at', $startsAt);
            $saveSetting('seasonal_ends_at', $endsAt);
            flash('Seasonal effect settings saved.');
        } elseif ($action === 'order_email_banner') {
            $bannerUrl = trim((string) post_str('order_email_banner_url'));
            if ($bannerUrl !== '') {
                $parts = parse_url($bannerUrl);
                if (!filter_var($bannerUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
                    throw new RuntimeException('Enter a valid http:// or https:// destination for the email banner.');
                }
            }
            if (mb_strlen($bannerUrl) > 2048) {
                throw new RuntimeException('The email banner link is too long.');
            }
            $image = upload_image('order_email_banner');
            $saveSetting('order_email_banner_url', $bannerUrl);
            if ($image) {
                $saveSetting('order_email_banner', $image['path']);
            } elseif (!empty($_POST['order_email_banner_reset'])) {
                $saveSetting('order_email_banner', null);
            }
            flash('Order email banner saved.');
        } elseif ($action === 'orders') {
            $wa = preg_replace('/\D+/', '', (string) post_str('whatsapp_number'));
            if ($wa !== '' && (strlen($wa) < 8 || strlen($wa) > 15 || $wa[0] === '0')) {
                throw new RuntimeException('Enter the WhatsApp number in international format without + or a leading 0, e.g. 254712345678.');
            }
            $fee = post_num('delivery_fee');
            $free = post_num('free_delivery_over');
            if (($fee !== null && !valid_amount($fee)) || ($free !== null && !valid_amount($free))) {
                throw new RuntimeException('Delivery amounts must be numbers (0 or more).');
            }
            $notify = post_str('order_notify_email');
            if ($notify !== null && !filter_var($notify, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('The order notification email is not valid.');
            }
            $saveSetting('store_name', post_str('store_name') ?? 'Shop ICT Gadgets');
            $saveSetting('store_phone', post_str('store_phone'));
            $saveSetting('store_address', post_str('store_address'));
            $saveSetting('store_hours', post_str('store_hours'));
            $saveSetting('map_query', post_str('map_query'));
            $email = post_str('store_email');
            if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('The store email address is not valid.');
            }
            $saveSetting('store_email', $email);
            $saveSetting('store_city', post_str('store_city'));
            $saveSetting('footer_about', post_str('footer_about'));
            $saveSetting('whatsapp_number', $wa === '' ? null : $wa);
            $saveSetting('delivery_fee', $fee === null ? '0' : plain_number($fee));
            $saveSetting('order_notify_email', $notify);
            flash('Order settings saved.');
        } elseif ($action === 'password') {
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$admin['id']]);
            if (!password_verify((string) ($_POST['current_password'] ?? ''), (string) $stmt->fetchColumn())) {
                throw new RuntimeException('Your current password is not correct.');
            }
            $new = (string) ($_POST['new_password'] ?? '');
            if (strlen($new) < 12) {
                throw new RuntimeException('The new password must be at least 12 characters.');
            }
            if ($new !== (string) ($_POST['confirm_password'] ?? '')) {
                throw new RuntimeException('The new passwords do not match.');
            }
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            flash('Password changed.');
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('settings.php');
}

$dealEnds = setting('deal_ends_at');
$dealTs = $dealEnds ? strtotime($dealEnds) : false;
$products = db()->query('SELECT id, title FROM products WHERE is_active = 1 ORDER BY title')->fetchAll();
$popupActionType = setting('promo_popup_action_type', 'product');
$popupActionLabel = setting('promo_popup_action_label');
if ($popupActionLabel === null || $popupActionLabel === '') {
    $popupActionLabel = $popupActionType === 'promo_code' ? 'Copy code' : 'Shop now';
}
$popupStarts = setting('promo_popup_starts_at');
$popupEnds = setting('promo_popup_ends_at');
$popupStartTs = $popupStarts ? strtotime($popupStarts) : false;
$popupEndTs = $popupEnds ? strtotime($popupEnds) : false;
$seasonalStarts = setting('seasonal_starts_at');
$seasonalEnds = setting('seasonal_ends_at');
$seasonalStartTs = $seasonalStarts ? strtotime($seasonalStarts) : false;
$seasonalEndTs = $seasonalEnds ? strtotime($seasonalEnds) : false;

admin_header('Settings', 'settings');
?>
<div class="alert alert-success mb-4" role="status">
  <strong>Everything is working perfectly and streamlined.</strong>
  Your store settings and controls are organized here for easy management.
</div>
<div class="row mb-4">
  <div class="col-12">
    <form method="post" class="card">
      <?= csrf_field() ?><input type="hidden" name="action" value="maintenance">
      <div class="card-header">Website availability</div>
      <div class="card-body">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" value="1" <?= setting('maintenance_mode') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="maintenance_mode">Show the under-maintenance page to visitors</label>
        </div>
        <div class="form-text mt-2">Visitors will see the maintenance page and contact buttons. The admin panel stays available so you can switch the website back on.</div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save availability</button></div>
    </form>
  </div>
</div>
<div class="row g-4">
  <div class="col-xl-7">
    <form method="post" enctype="multipart/form-data" class="card">
      <?= csrf_field() ?><input type="hidden" name="action" value="store">
      <div class="card-header">Store</div>
      <div class="card-body">
        <div class="mb-4">
          <label class="form-label" for="logo">Logo</label>
          <div class="logo-preview mb-2"><img src="<?= e(admin_asset(store_logo())) ?>" alt="Current logo" data-preview="logo"></div>
          <input class="form-control" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" data-preview-input="logo">
          <div class="form-text">Shown in the header, footer, phone menu and admin. A PNG with a transparent background works best, about 370 × 80 px (wide and short).</div>
          <?php if (setting('logo')): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="logo_reset" name="logo_reset" value="1">
            <label class="form-check-label" for="logo_reset">Go back to the original logo</label>
          </div>
          <?php endif; ?>
        </div>
        <div class="mb-4">
          <label class="form-label" for="deal_ends_at">Deal Of The Day ends</label>
          <input class="form-control" type="datetime-local" id="deal_ends_at" name="deal_ends_at" value="<?= $dealTs ? date('Y-m-d\TH:i', $dealTs) : '' ?>" style="max-width:280px">
          <div class="form-text">
            The countdown on the deal cards runs until this moment, then shows "Time's up!".
            <?php if ($dealTs && $dealTs <= time()): ?><span class="text-danger">This date has passed, so the deals currently show "Time's up!".</span><?php endif; ?>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label" for="quickview_default_product">Quick View default product</label>
          <select class="form-select" id="quickview_default_product" name="quickview_default_product">
            <option value="">— None —</option>
            <?php foreach ($products as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === setting('quickview_default_product') ? 'selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach; ?>
          </select>
          <div class="form-text">Pre-loaded in the Quick View window before a visitor clicks a product.</div>
        </div>
        <div>
          <label class="form-label">Hero background</label>
          <img class="img-preview <?= setting('hero_bg_image') ? '' : 'd-none' ?>" src="<?= e(admin_asset(setting('hero_bg_image'))) ?>" alt="" data-preview="hero">
          <input class="form-control" type="file" name="hero_bg_image" accept="image/jpeg,image/png,image/gif,image/webp" data-preview-input="hero">
          <div class="form-text">Wide image behind the hero banners at the top of the home page.</div>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save settings</button></div>
    </form>
  </div>
  <div class="col-xl-5">
    <form method="post" enctype="multipart/form-data" class="card mb-4">
      <?= csrf_field() ?><input type="hidden" name="action" value="promotion">
      <div class="card-header">Promotional popup</div>
      <div class="card-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" role="switch" id="promo_popup_enabled" name="promo_popup_enabled" value="1" <?= setting('promo_popup_enabled') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="promo_popup_enabled">Enable popup</label>
        </div>
        <div class="mb-3">
          <label class="form-label" for="promo_popup_title">Title</label>
          <input class="form-control" id="promo_popup_title" name="promo_popup_title" maxlength="120" value="<?= e(setting('promo_popup_title', '')) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="promo_popup_description">Description</label>
          <textarea class="form-control" id="promo_popup_description" name="promo_popup_description" rows="3" maxlength="600"><?= e(setting('promo_popup_description', '')) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="promo_popup_image">Banner image</label>
          <?php if (setting('promo_popup_image')): ?>
          <img class="img-preview" src="<?= e(admin_asset(setting('promo_popup_image'))) ?>" alt="Current popup banner" data-preview="promo-popup">
          <?php else: ?>
          <img class="img-preview d-none" src="" alt="" data-preview="promo-popup">
          <?php endif; ?>
          <input class="form-control" type="file" id="promo_popup_image" name="promo_popup_image" accept="image/png,image/jpeg,image/webp" data-preview-input="promo-popup">
          <div class="form-text">Optional image (up to 5 MB). Recommended: 1200 × 675 px (16:9). On phones, the full image is shown without cropping; it may appear smaller to fit the screen.</div>
          <?php if (setting('promo_popup_image')): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="promo_popup_image_reset" name="promo_popup_image_reset" value="1">
            <label class="form-check-label" for="promo_popup_image_reset">Remove the current banner</label>
          </div>
          <?php endif; ?>
        </div>
        <div class="mb-3">
          <label class="form-label" for="promo_popup_action_type">Button action</label>
          <select class="form-select" id="promo_popup_action_type" name="promo_popup_action_type" data-popup-action-type>
            <option value="product" <?= $popupActionType === 'product' ? 'selected' : '' ?>>Open a product</option>
            <option value="promo_code" <?= $popupActionType === 'promo_code' ? 'selected' : '' ?>>Copy a promo code</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="promo_popup_product_id">Product page</label>
          <select class="form-select" id="promo_popup_product_id" name="promo_popup_product_id">
            <option value="">— Choose an active product —</option>
            <?php foreach ($products as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (string) $p['id'] === setting('promo_popup_product_id') ? 'selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach; ?>
          </select>
          <div class="form-text">Required for “Open a product”. With “Copy a promo code”, this adds an optional arrow button linking to the selected product.</div>
        </div>
        <div class="mb-3" data-popup-action-target="promo_code">
          <label class="form-label" for="promo_popup_code">Promo code</label>
          <input class="form-control" id="promo_popup_code" name="promo_popup_code" maxlength="100" value="<?= e(setting('promo_popup_code', '')) ?>">
          <div class="form-text">Used when the button action is “Copy a promo code”.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="promo_popup_action_label">Button label</label>
          <input class="form-control" id="promo_popup_action_label" name="promo_popup_action_label" maxlength="40" value="<?= e($popupActionLabel) ?>" data-popup-action-label>
          <div class="form-text">The code action copies the code; for example, use “Copy code” as its button label.</div>
        </div>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="promo_popup_starts_at">Show from</label>
            <input class="form-control" type="datetime-local" id="promo_popup_starts_at" name="promo_popup_starts_at" value="<?= $popupStartTs ? date('Y-m-d\TH:i', $popupStartTs) : '' ?>">
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="promo_popup_ends_at">Stop showing</label>
            <input class="form-control" type="datetime-local" id="promo_popup_ends_at" name="promo_popup_ends_at" value="<?= $popupEndTs ? date('Y-m-d\TH:i', $popupEndTs) : '' ?>">
          </div>
        </div>
        <div class="form-text mt-2">Leave dates empty to show immediately while enabled. The popup appears on every page load during its schedule.</div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save popup</button></div>
    </form>
    <form method="post" class="card mb-4">
      <?= csrf_field() ?><input type="hidden" name="action" value="seasonal">
      <div class="card-header">Seasonal decorations</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label" for="seasonal_effect">Effect</label>
          <select class="form-select" id="seasonal_effect" name="seasonal_effect">
            <option value="off" <?= setting('seasonal_effect', 'off') === 'off' ? 'selected' : '' ?>>Off</option>
            <option value="black_november" <?= setting('seasonal_effect') === 'black_november' ? 'selected' : '' ?>>Black November — red and black balloons rise</option>
            <option value="christmas" <?= setting('seasonal_effect') === 'christmas' ? 'selected' : '' ?>>Christmas — decorations fall from above</option>
          </select>
        </div>
        <div class="row g-3">
          <div class="col-sm-6">
            <label class="form-label" for="seasonal_starts_at">Show from</label>
            <input class="form-control" type="datetime-local" id="seasonal_starts_at" name="seasonal_starts_at" value="<?= $seasonalStartTs ? date('Y-m-d\TH:i', $seasonalStartTs) : '' ?>">
          </div>
          <div class="col-sm-6">
            <label class="form-label" for="seasonal_ends_at">Stop showing</label>
            <input class="form-control" type="datetime-local" id="seasonal_ends_at" name="seasonal_ends_at" value="<?= $seasonalEndTs ? date('Y-m-d\TH:i', $seasonalEndTs) : '' ?>">
          </div>
        </div>
        <div class="form-text mt-2">Dates are optional. The animation plays whenever the homepage loads. Visitors who prefer reduced motion will not see it.</div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save decorations</button></div>
    </form>
    <form method="post" class="card mb-4">
      <?= csrf_field() ?><input type="hidden" name="action" value="order_email_banner">
      <div class="card-header">Order confirmation email banner</div>
      <div class="card-body">
        <p class="small text-muted">The optional promotional banner appears below the order details and can link to your chosen page.</p>
        <?php if (setting('order_email_banner')): ?>
        <img class="img-preview" src="<?= e(admin_asset(setting('order_email_banner'))) ?>" alt="Current order email banner" data-preview="order-email-banner">
        <?php else: ?>
        <img class="img-preview d-none" src="" alt="" data-preview="order-email-banner">
        <?php endif; ?>
        <div class="mb-3">
          <label class="form-label" for="order_email_banner">Banner image</label>
          <input class="form-control" type="file" id="order_email_banner" name="order_email_banner" accept="image/png,image/jpeg,image/webp" data-preview-input="order-email-banner">
          <div class="form-text">Upload a JPG, PNG or WebP up to 5 MB. Use a wide promotional banner for best results.</div>
          <?php if (setting('order_email_banner')): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="order_email_banner_reset" name="order_email_banner_reset" value="1">
            <label class="form-check-label" for="order_email_banner_reset">Remove the current banner</label>
          </div>
          <?php endif; ?>
        </div>
        <div>
          <label class="form-label" for="order_email_banner_url">Banner destination URL</label>
          <input class="form-control" type="url" id="order_email_banner_url" name="order_email_banner_url" maxlength="2048" value="<?= e(setting('order_email_banner_url', '')) ?>" placeholder="https://www.shopict.co.ke/shop">
          <div class="form-text">Optional. When set, clicking the banner opens this URL.</div>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save email banner</button></div>
    </form>
    <form method="post" class="card mb-4">
      <?= csrf_field() ?><input type="hidden" name="action" value="orders">
      <div class="card-header">Store contact, orders &amp; WhatsApp</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label" for="store_name">Store name</label>
          <input class="form-control" id="store_name" name="store_name" value="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" maxlength="60">
          <div class="form-text">Used in page titles and order emails.</div>
        </div>
        <p class="section-note mb-2">Shown in the dark bar above the header (it scrolls slowly on phones). Leave all three empty to hide the bar.</p>
        <div class="mb-3">
          <label class="form-label" for="store_phone">Phone</label>
          <input class="form-control" id="store_phone" name="store_phone" value="<?= e(setting('store_phone', '')) ?>" maxlength="30" placeholder="+254713869018" inputmode="tel">
        </div>
        <div class="mb-3">
          <label class="form-label" for="store_address">Address</label>
          <input class="form-control" id="store_address" name="store_address" value="<?= e(setting('store_address', '')) ?>" maxlength="120">
        </div>
        <div class="mb-3">
          <label class="form-label" for="store_hours">Opening hours</label>
          <input class="form-control" id="store_hours" name="store_hours" value="<?= e(setting('store_hours', '')) ?>" maxlength="80">
        </div>
        <div class="mb-3">
          <label class="form-label" for="store_email">Email</label>
          <input class="form-control" type="email" id="store_email" name="store_email" value="<?= e(setting('store_email', '')) ?>" maxlength="120">
          <div class="form-text">Shown under "Reach Us" in the footer.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="store_city">Location (footer)</label>
          <input class="form-control" id="store_city" name="store_city" value="<?= e(setting('store_city', '')) ?>" maxlength="80" placeholder="Nairobi, Kenya">
        </div>
        <div class="mb-3">
          <label class="form-label" for="footer_about">About text (footer)</label>
          <textarea class="form-control" id="footer_about" name="footer_about" rows="3" maxlength="255"><?= e(setting('footer_about', '')) ?></textarea>
          <div class="form-text">Shown under the logo in the footer.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="map_query">Map location</label>
          <input class="form-control" id="map_query" name="map_query" value="<?= e(setting('map_query', 'Laxmi Plaza, Nairobi')) ?>" maxlength="120" placeholder="Laxmi Plaza, Nairobi">
          <div class="form-text">What Google Maps shows in the "Visit Our Shop" map on the home page. Building and town work best.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="whatsapp_number">WhatsApp number</label>
          <input class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?= e(setting('whatsapp_number', '')) ?>" placeholder="254712345678" inputmode="numeric">
          <div class="form-text">Country code + number, no + or leading 0. Turns on the "Order via WhatsApp" buttons. Leave empty to hide them.</div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-12">
            <label class="form-label" for="delivery_fee">Delivery fee (KSh)</label>
            <input class="form-control" id="delivery_fee" name="delivery_fee" value="<?= e(setting('delivery_fee', '0')) ?>" inputmode="decimal" style="max-width:220px">
            <div class="form-text">Added to orders with countrywide delivery. Leave at 0 to agree the cost with the customer by phone. Pickup at the shop is always free.</div>
          </div>
        </div>
        <div>
          <label class="form-label" for="order_notify_email">Email me new orders</label>
          <input class="form-control" type="email" id="order_notify_email" name="order_notify_email" value="<?= e(setting('order_notify_email', '')) ?>" placeholder="you@example.com">
          <div class="form-text">Optional. Gets a copy of every order confirmation. Orders always appear under <a href="orders.php">Orders</a>.</div>
        </div>
        <?php $mail = (array) config('mail'); ?>
        <div class="alert <?= ($mail['driver'] ?? 'log') === 'smtp' ? 'alert-success' : 'alert-warning' ?> small mt-3 mb-0">
          <?php if (($mail['driver'] ?? 'log') === 'smtp'): ?>Emails are sent through <?= e($mail['host']) ?> as <?= e($mail['from_email']) ?>.
          <?php else: ?>Emails are in <strong>test mode</strong>: they are saved in <code>storage/mail/</code>, not sent. For Gmail, enable 2-Step Verification and create an App Password. In <code>config.php</code>, set the mail driver to <code>smtp</code>, host to <code>smtp.gmail.com</code>, port to <code>587</code>, encryption to <code>tls</code>, username and from address to your Gmail address, and password to the App Password (not your normal Gmail password).<?php endif; ?>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save order settings</button></div>
    </form>
    <form method="post" class="card">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="card-header">Change your password</div>
      <div class="card-body">
        <p class="small text-muted">Signed in as <?= e($admin['email']) ?></p>
        <div class="mb-3"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
        <div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" autocomplete="new-password" minlength="12" required><div class="form-text">Use at least 12 characters.</div></div>
        <div><label class="form-label">Repeat new password</label><input class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="12" required></div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-outline-secondary">Change password</button></div>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
