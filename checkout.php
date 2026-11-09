<?php
require __DIR__ . '/includes/bootstrap.php';

visitor_id();

$errors = [];
$form = ['customer_name' => '', 'email' => '', 'phone' => '', 'city' => '', 'country' => 'Kenya', 'notes' => '', 'delivery_method' => 'delivery'];
$problem = null;
$promoMessage = null; // [text, 'ok'|'error'] after applying or removing a promo code
$action = (string) ($_POST['promo_action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(csrf_token(), (string) ($_POST['_token'] ?? ''))) {
        $problem = 'Your session expired. Please check your details and confirm again.';
    } elseif (trim((string) ($_POST['co_ref_hp'] ?? '')) !== '' && time() - (int) ($_SESSION['checkout_shown_at'] ?? 0) < 4) {
        // Spam check: a hidden field that people never see was filled in AND the form came back within
        // seconds of opening. (Browser autofill can fill hidden fields too, so the field alone is not enough.)
        $problem = 'Your order could not be placed. Please try again, or order via WhatsApp.';
    } elseif ($action === 'apply' || $action === 'remove') {
        // Promo code box: keep what the shopper typed, just apply or drop the code.
        foreach ($form as $k => $_) {
            $form[$k] = mb_substr(trim((string) ($_POST[$k] ?? '')), 0, 1000);
        }
        $form['country'] = $form['country'] ?: 'Kenya';
        $form['delivery_method'] = $form['delivery_method'] === 'pickup' ? 'pickup' : 'delivery';
        if ($action === 'remove') {
            unset($_SESSION['promo_code']);
            $promoMessage = ['Promo code removed.', 'ok'];
        } else {
            [$promo, $saving, $why] = promo_code_check((string) ($_POST['promo_code'] ?? ''), cart_items());
            if ($promo) {
                $_SESSION['promo_code'] = $promo['code'];
                $promoMessage = ['Code ' . $promo['code'] . ' applied: you save ' . money($saving) . '.', 'ok'];
            } else {
                $promoMessage = [$why, 'error'];
            }
        }
    } else {
        [$form, $errors] = validate_checkout($_POST);
        $channel = ($_POST['channel'] ?? '') === 'whatsapp' && whatsapp_number() ? 'whatsapp' : 'web';
        if (!$errors) {
            try {
                $order = place_order($form, $channel, $_SESSION['promo_code'] ?? null);
                unset($_SESSION['promo_code']);
                send_order_emails($order);
                header('Location: ' . order_url($order) . '&placed=1' . ($channel === 'whatsapp' ? '&wa=1' : ''));
                exit;
            } catch (RuntimeException $e) {
                $problem = $e->getMessage();
            }
        }
    }
}

track_page_view();
$_SESSION['checkout_shown_at'] = time(); // for the spam check above
$items = cart_items();
$subtotal = cart_subtotal($items);
$fee = delivery_fee_for($subtotal, $form['delivery_method']);
$deliveryFee = delivery_fee_for($subtotal, 'delivery'); // used when the shopper switches option
// A code applied earlier is checked again against the current cart.
$promo = null;
$discount = 0.0;
if (!empty($_SESSION['promo_code']) && $items) {
    [$promo, $discount, $why] = promo_code_check($_SESSION['promo_code'], $items);
    if (!$promo) {
        unset($_SESSION['promo_code']);
        $promoMessage ??= [$why, 'error'];
    }
}
$delivery = free_delivery_progress($subtotal);
$invalid = fn (string $f) => isset($errors[$f]) ? ' is-invalid' : '';
$error = fn (string $f) => isset($errors[$f]) ? '<p class="co-error" id="err-' . $f . '">' . e($errors[$f]) . '</p>' : '';
$described = fn (string $f) => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="err-' . $f . '"' : '';

partial('page-start', ['title' => 'Checkout · ' . setting('store_name', 'Shop ICT Gadgets')]);
?>
<section class="tf-sp-2 co-page">
  <div class="container">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">Checkout</span></nav>
    <h3 class="fw-semibold mb-4">Checkout</h3>

    <?php if (!$items): ?>
    <div class="co-card text-center py-5">
      <h5 class="fw-semibold mb-2">Your cart is empty</h5>
      <p class="body-text-3 text-main-2 mb-4">Add a few products, then come back here to place your order.</p>
      <a class="tf-btn d-inline-flex" href="./"><span class="text-white">Continue shopping</span></a>
    </div>
    <?php else: ?>

    <?php if ($problem): ?><div class="co-alert" role="alert"><?= e($problem) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="co-alert" role="alert">Please check the highlighted fields.</div><?php endif; ?>

    <form method="post" action="checkout.php" class="row g-4" novalidate>
      <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
      <div class="co-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="co_ref_hp" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore data-bwignore></label></div>

      <div class="col-lg-7">
        <div class="co-card">
          <h5 class="fw-semibold mb-1">Delivery details</h5>
          <p class="body-text-3 text-main-2 mb-4">We'll send your confirmation by email and call you to arrange pickup or delivery.</p>
          <div class="row g-3">
            <div class="col-12">
              <label class="co-label" for="customer_name">Full name *</label>
              <input class="co-input<?= $invalid('customer_name') ?>" id="customer_name" name="customer_name" value="<?= e($form['customer_name']) ?>" autocomplete="name" required<?= $described('customer_name') ?>>
              <?= $error('customer_name') ?>
            </div>
            <div class="col-md-6">
              <label class="co-label" for="email">Email *</label>
              <input class="co-input<?= $invalid('email') ?>" type="email" id="email" name="email" value="<?= e($form['email']) ?>" autocomplete="email" required<?= $described('email') ?>>
              <?= $error('email') ?>
            </div>
            <div class="col-md-6">
              <label class="co-label" for="phone">Phone *</label>
              <input class="co-input<?= $invalid('phone') ?>" type="tel" id="phone" name="phone" value="<?= e($form['phone']) ?>" autocomplete="tel" placeholder="e.g. 0712 345 678" required<?= $described('phone') ?>>
              <?= $error('phone') ?>
            </div>
            <div class="col-md-6">
              <label class="co-label" for="city">Town / city <span data-city-required<?= $form['delivery_method'] === 'pickup' ? ' hidden' : '' ?>>*</span></label>
              <input class="co-input<?= $invalid('city') ?>" id="city" name="city" value="<?= e($form['city']) ?>" autocomplete="address-level2" placeholder="e.g. Nairobi, Mombasa, Kisumu"<?= $form['delivery_method'] === 'pickup' ? '' : ' required' ?><?= $described('city') ?>>
              <?= $error('city') ?>
            </div>
            <div class="col-md-6">
              <label class="co-label" for="country">Country</label>
              <input class="co-input" id="country" name="country" value="<?= e($form['country']) ?>" autocomplete="country-name">
            </div>
            <div class="col-12">
              <label class="co-label" for="notes">Delivery notes</label>
              <textarea class="co-input" id="notes" name="notes" rows="3" placeholder="Gate code, best time to call, etc."><?= e($form['notes']) ?></textarea>
            </div>
          </div>
        </div>

        <div class="co-card mt-4">
          <h5 class="fw-semibold mb-3" id="deliveryOptionTitle">Delivery option</h5>
          <div class="co-choices" role="radiogroup" aria-labelledby="deliveryOptionTitle">
            <?php foreach (delivery_methods() as $key => [$title, $detail]): ?>
            <label class="co-choice">
              <input type="radio" name="delivery_method" value="<?= $key ?>" <?= $form['delivery_method'] === $key ? 'checked' : '' ?> data-delivery-method>
              <span><span class="fw-semibold d-block"><?= e($title) ?></span><?php if ($detail !== ''): ?><span class="body-text-3 text-main-2"><?= e($detail) ?></span><?php endif; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="co-card co-summary">
          <h5 class="fw-semibold mb-3">Your order</h5>
          <ul class="co-items">
            <?php foreach ($items as $it): ?>
            <li>
              <span class="co-thumb"><?= img($it['img_src'], 64, 64, '', $it['title']) ?><span class="co-qty"><?= (int) $it['quantity'] ?></span></span>
              <span class="co-item-text">
                <a class="body-md-2 fw-semibold d-block link" href="<?= e(product_url($it)) ?>" title="<?= e($it['title']) ?>"><?= e(short_title($it['title'])) ?></a>
                <?php if ($it['variant_label'] !== ''): ?><span class="caption text-main-2 d-block"><?= e($it['variant_label']) ?></span><?php endif; ?>
                <?php if (!empty($it['is_preorder'])): ?><span class="preorder-pill is-small">Pre-order</span><?php endif; ?>
                <?php if (!empty($it['promotion'])): ?><span class="caption text-primary d-block"><?= e($it['promotion']['badge'] ?: $it['promotion']['name']) ?></span><?php endif; ?>
              </span>
              <span class="body-md-2 fw-semibold text-nowrap"><?= money($it['price'] * $it['quantity']) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <dl class="co-totals">
            <div><dt>Subtotal</dt><dd><?= money($subtotal) ?></dd></div>
            <?php if ($promo): ?><div class="co-discount"><dt>Promo code <?= e($promo['code']) ?></dt><dd>−<?= money($discount) ?></dd></div><?php endif; ?>
            <div><dt>Delivery</dt><dd data-delivery-cost data-pickup="<?= e(delivery_cost_text('pickup', 0)) ?>" data-delivery="<?= e(delivery_cost_text('delivery', $deliveryFee)) ?>"><?= e(delivery_cost_text($form['delivery_method'], $fee)) ?></dd></div>
            <div class="co-grand"><dt>Total</dt><dd data-order-total data-pickup="<?= e(money(max(0, $subtotal - $discount))) ?>" data-delivery="<?= e(money(max(0, $subtotal - $discount) + $deliveryFee)) ?>"><?= money(max(0, $subtotal - $discount) + $fee) ?></dd></div>
          </dl>
          <div class="co-promo">
            <label class="co-label" for="promo_code">Promo code</label>
            <?php if ($promo): ?>
            <div class="co-promo-row">
              <span class="co-promo-applied"><?= e($promo['code']) ?> · <?= e(promo_code_label($promo)) ?></span>
              <button class="co-promo-btn is-light" type="submit" name="promo_action" value="remove" formnovalidate>Remove</button>
            </div>
            <?php else: ?>
            <div class="co-promo-row">
              <input class="co-input" id="promo_code" name="promo_code" value="<?= e((string) ($_POST['promo_code'] ?? '')) ?>" maxlength="30" autocomplete="off" autocapitalize="characters" placeholder="Enter code"<?= $promoMessage && $promoMessage[1] === 'error' ? ' aria-invalid="true" aria-describedby="promo-msg"' : '' ?>>
              <button class="co-promo-btn" type="submit" name="promo_action" value="apply" formnovalidate>Apply</button>
            </div>
            <?php endif; ?>
            <?php if ($promoMessage): ?><p class="co-promo-msg<?= $promoMessage[1] === 'error' ? ' is-error' : '' ?>" id="promo-msg" role="status"><?= e($promoMessage[0]) ?></p><?php endif; ?>
          </div>
          <div class="co-terms">
            <p class="mb-0">We use your details only to process your order and support you. See our <a class="link text-secondary" href="privacy-policy.php" target="_blank" rel="noopener">Privacy Policy</a> and <a class="link text-secondary" href="terms.php" target="_blank" rel="noopener">Terms &amp; Conditions</a>.</p>
          </div>
          <button class="tf-btn w-100" type="submit" name="channel" value="web"><span class="text-white">Confirm order</span></button>
          <?php if (whatsapp_number()): ?>
          <div class="co-or"><span>or</span></div>
          <button class="tf-btn btn-whatsapp w-100" type="submit" name="channel" value="whatsapp"><?= whatsapp_icon(20) ?><span class="text-white">Confirm order via WhatsApp</span></button>
          <p class="caption text-main-2 mt-2 mb-0">Your order is saved, then WhatsApp opens with all your order details typed in. Just tap send.</p>
          <?php endif; ?>
          <p class="caption text-main-2 mt-3 mb-0"><a class="link text-secondary" href="./">← Continue shopping</a></p>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</section>
<?php partial('page-end'); ?>
