<?php
declare(strict_types=1);

// Shared code for the admin dashboard (admin/*.php).

require_once __DIR__ . '/bootstrap.php';

const UPLOAD_DIR = 'images/uploads';
const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;

/** Text colour classes the theme provides, offered for banner text. */
const COLOR_CLASSES = [
    ''               => 'Default',
    'text-black'     => 'Black',
    'text-white'     => 'White',
    'text-secondary' => 'Secondary (blue)',
    'text-primary'   => 'Primary (red)',
    'text-primary-2' => 'Primary 2',
    'text-third-2'   => 'Yellow',
];

/** WOW.js entrance animations offered for products in a section. */
const ANIMATIONS = ['', 'fadeInUp', 'fadeInLeft', 'fadeInRight', 'fadeInDown', 'fadeIn', 'zoomIn'];

/**
 * Banner placements: where each kind of banner appears, how many it can have,
 * which fields it uses and the recommended image sizes.
 */
function banner_placements(): array
{
    return [
        'hero_main' => [
            'label'  => 'Hero – main banner slides',
            'where'  => 'Three-slide carousel at the top of the home page: one slide at a time, changing every 6 seconds. The order sets the slide order.',
            'max'    => 3,
            'fields' => ['title', 'title_highlight', 'subtitle', 'price_label', 'price', 'features', 'button_text', 'link_url', 'image', 'bg_image'],
            'sizes'  => ['image' => '794 × 794 px (transparent PNG)', 'bg_image' => 'wide background'],
        ],
        'hero_side_1' => [
            'label'  => 'Hero – side banners',
            'where'  => 'Up to two equal-size banners stacked to the right of the main hero banner (in position order).',
            'max'    => 2,
            'fields' => ['eyebrow', 'title', 'price', 'product', 'button_text', 'link_url', 'image', 'bg_image', 'custom_colours'],
            'labels' => [
                'eyebrow' => ['Company name', 'e.g. Sony. Shown on the first line.'],
                'title'   => ['Product name', 'Second line. Leave empty to use the linked product\'s name.'],
                'price'   => ['Price', 'Enter the amount; KES and comma separators are added automatically. Leave empty to show the linked product\'s current price.'],
            ],
            'sizes'  => ['image' => '688 × 688 px (transparent PNG)'],
        ],
        'hero_side_2' => [
            'label'  => 'Hero – bottom side banner (price style)',
            'where'  => 'Shown under the first side banner only when there are fewer than two side banners.',
            'max'    => 1,
            'fields' => ['eyebrow', 'title', 'subtitle', 'price_label', 'price', 'button_text', 'link_url', 'image', 'bg_image'],
            'sizes'  => ['image' => '808 × 808 px (transparent PNG)'],
        ],
        'featured_card' => [
            'label'  => 'Featured cards',
            'where'  => 'Row of promo cards between Trending Products and Best Sellers.',
            'max'    => null,
            'fields' => ['eyebrow', 'title', 'subtitle', 'price', 'link_url', 'image', 'tag_color', 'text_color', 'price_color'],
            'sizes'  => ['image' => '972 × 424 px'],
        ],
        'smart_home' => [
            'label'  => 'Featured Products banner',
            'where'  => 'Tall banner beside the Featured Products grid (large screens only).',
            'max'    => 1,
            'fields' => ['eyebrow', 'title', 'price_label', 'price', 'link_url', 'image'],
            'sizes'  => ['image' => '400 × 650 px'],
        ],
        'promo_slider' => [
            'label'  => 'Promo slider',
            'where'  => 'Two-up banner slider above the Top 20 lists. Slides alternate between two layouts.',
            'max'    => null,
            'fields' => ['title', 'title_highlight', 'link_url', 'image', 'bg_image', 'custom_colours'],
            'sizes'  => ['image' => 'about 240 × 230 px (transparent PNG)', 'bg_image' => 'about 700 × 280 px'],
        ],
    ];
}

const BANNER_FIELD_LABELS = [
    'eyebrow'         => ['Small text above the title', 'e.g. "New arrival" or a tag'],
    'title'           => ['Title', 'Use a new line to break the title where the design allows it.'],
    'title_highlight' => ['Title highlight', 'Second part of the title, shown emphasised.'],
    'subtitle'        => ['Subtitle', ''],
    'price_label'     => ['Price label', 'e.g. "From" or "Starting"'],
    'price'           => ['Price', 'Enter an amount; KES and comma separators are added automatically.'],
    'button_text'     => ['Button text', 'Leave empty to hide the button.'],
    'link_url'        => ['Link', 'Where the banner goes when clicked.'],
    'image'           => ['Image', ''],
    'bg_image'        => ['Background image', ''],
    'tag_color'       => ['Small text colour', ''],
    'text_color'      => ['Title colour', ''],
    'price_color'     => ['Price colour', ''],
    'features'        => ['Feature list', 'Label and value pairs shown under the title (e.g. 100Hz / Refresh Rate). The icon is picked from the text automatically, or choose one.'],
];

/** Whether a section row has a product list (max_products NULL = unlimited, 0 = none). */
function section_takes_products(array $section): bool
{
    return $section['max_products'] === null || (int) $section['max_products'] > 0;
}

function section_limit(array $section): ?int
{
    return $section['max_products'] === null ? null : (int) $section['max_products'];
}

/** Icon dropdown for a hero feature row: "Automatic" picks one from the feature's text. */
function feature_icon_select(array $feature): string
{
    $chosen = (string) ($feature['icon'] ?? '');
    $auto = $feature ? FEATURE_ICONS[feature_icon_key(['label' => $feature['label'] ?? '', 'value' => $feature['value'] ?? ''])] : null;
    $html = '<select class="form-select" name="feature_icon[]" aria-label="Icon">';
    $html .= '<option value="">Automatic' . ($auto ? ' (' . e($auto) . ')' : '') . '</option>';
    foreach (FEATURE_ICONS as $key => $label) {
        $html .= '<option value="' . e($key) . '"' . ($key === $chosen ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    return $html . '</select>';
}

// ---------------------------------------------------------------- promotions & analytics

/** e.g. "20% off · Laptops" */
function promotion_summary(array $promo): string
{
    $value = (float) $promo['discount_value'];
    $off = $promo['discount_type'] === 'percent'
        ? rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '% off'
        : money($value) . ' off';
    $count = isset($promo['product_count']) ? (int) $promo['product_count'] : count($promo['product_ids'] ?? []);
    $target = match ($promo['applies_to']) {
        'all'      => 'all products',
        'category' => 'category "' . $promo['category'] . '"',
        'products' => $count . ' product' . ($count === 1 ? '' : 's'),
        default    => '',
    };
    $usage = isset($promo['id']) && ($promo['max_uses'] ?? null) !== null
        ? ' · ' . (promotion_uses()[(int) $promo['id']] ?? 0) . '/' . (int) $promo['max_uses'] . ' used'
        : '';
    return $off . ' · ' . $target . $usage
        . (!empty($promo['code']) ? ' · code ' . $promo['code'] . (!empty($promo['min_spend']) ? ' (min. ' . money($promo['min_spend']) . ')' : '') : '');
}

/** [label, badge class] for a promotion's current state. */
function promotion_status(array $promo): array
{
    $now = date('Y-m-d H:i:s');
    if (!$promo['is_active']) {
        return ['Off', 'badge-off'];
    }
    if ($promo['ends_at'] !== null && $promo['ends_at'] <= $now) {
        return ['Ended', 'badge-off'];
    }
    if ($promo['starts_at'] !== null && $promo['starts_at'] > $now) {
        return ['Scheduled', 'badge-soft'];
    }
    if (promotion_left($promo) === 0) {
        return ['Used up', 'badge-off'];
    }
    return ['Running', 'badge-on'];
}

/** Event counts for one product over the last $days days. */
function product_stats(int $productId, int $days): array
{
    $stmt = db()->prepare(
        "SELECT type, COUNT(*) FROM analytics_events
          WHERE product_id = ? AND created_at >= ? AND type IN ('product_view', 'add_to_cart')
          GROUP BY type"
    );
    $stmt->execute([$productId, date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'))]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) + ['product_view' => 0, 'add_to_cart' => 0];
}

/** "Y-m-d H:i:s" from a datetime-local input, null when empty, false when invalid. */
function post_datetime(string $key): string|false|null
{
    $v = post_str($key);
    if ($v === null) {
        return null;
    }
    $ts = strtotime($v);
    return $ts === false ? false : date('Y-m-d H:i:s', $ts);
}

// ---------------------------------------------------------------- auth

function current_admin(): ?array
{
    static $admin = false;
    if ($admin === false) {
        $admin = null;
        if (!empty($_SESSION['admin_id'])) {
            $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ? AND is_admin = 1');
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch() ?: null;
        }
    }
    return $admin;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect('login.php');
    }
    $now = time();
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
    if ($lastActivity > 0 && $now - $lastActivity > 1800) {
        unset($_SESSION['admin_id'], $_SESSION['admin_last_activity']);
        session_regenerate_id(true);
        flash('Your admin session expired due to inactivity. Please sign in again.', 'error');
        redirect('login.php');
    }
    $_SESSION['admin_last_activity'] = $now;
    return $admin;
}

// ---------------------------------------------------------------- requests

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/** Stops the request unless the POSTed form carries this session's CSRF token. */
function verify_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['_token'] ?? ''))) {
        http_response_code(419);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/** Trimmed POST value, or null when empty. */
function post_str(string $key): ?string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    return $v === '' ? null : $v;
}

/** Numeric POST value, null when empty, NAN when it is not a number. */
function post_num(string $key): ?float
{
    $v = post_str($key);
    if ($v === null) {
        return null;
    }
    $v = str_ireplace([',', '$', ' ', 'ksh', 'kes', " "], '', $v);
    return is_numeric($v) ? (float) $v : NAN;
}

/** True for a usable non-negative amount. */
function valid_amount(?float $n): bool
{
    return $n !== null && !is_nan($n) && $n >= 0;
}

function post_int(string $key): ?int
{
    $v = post_str($key);
    return $v === null ? null : (int) $v;
}

/** URL of an asset as seen from the admin folder. */
function admin_asset(?string $path): string
{
    if (!$path) {
        return '';
    }
    return preg_match('#^https?://#i', $path) ? image_url($path, 300) : '../' . asset($path);
}

/** Build an admin link from the current admin directory, independent of the current page URL. */
function admin_url(string $path = 'index.php'): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'));
    $directory = rtrim(dirname($script), '/');
    if ($directory === '' || $directory === '.') {
        $directory = '/admin';
    }
    return $directory . '/' . ltrim($path, '/');
}

// ---------------------------------------------------------------- uploads

/**
 * Saves an uploaded image from $_FILES[$field] (or a $_FILES[$field][$index] entry of a multi-upload)
 * and returns ['path' => 'images/uploads/…', 'width' => …, 'height' => …], or null if no file was sent.
 *
 * @throws RuntimeException when the upload is not a valid image
 */
function upload_image(string $field, ?int $index = null): ?array
{
    $f = $_FILES[$field] ?? null;
    if (!$f) {
        return null;
    }
    $pick = fn ($key) => $index === null ? $f[$key] : ($f[$key][$index] ?? null);
    $error = $pick('error');
    if ($error === UPLOAD_ERR_NO_FILE || $error === null) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image could not be uploaded (error ' . $error . ').');
    }
    $tmp = $pick('tmp_name');
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid upload.');
    }
    if ($pick('size') > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Images must be 5 MB or smaller.');
    }
    $info = @getimagesize($tmp);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('Please upload a JPG, PNG, GIF or WebP image.');
    }
    // Lighter file: at most 1600 px, saved as WebP (transparency kept). GIFs are kept as they are.
    $file = optimize_image($tmp);
    $made = $file !== $tmp;
    try {
        if (cloudinary_enabled()) {
            return cloudinary_upload($file);
        }
        $dir = __DIR__ . '/../assets/' . UPLOAD_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $out = @getimagesize($file) ?: $info;
        $ext = $made ? 'webp' : $types[$info[2]];
        $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!($made ? rename($file, $dir . '/' . $name) : move_uploaded_file($tmp, $dir . '/' . $name))) {
            throw new RuntimeException('The image could not be saved.');
        }
        return ['path' => UPLOAD_DIR . '/' . $name, 'width' => $out[0], 'height' => $out[1]];
    } finally {
        if ($made && is_file($file)) {
            @unlink($file);
        }
    }
}

// ---------------------------------------------------------------- layout

function admin_header(string $title, string $active = ''): void
{
    partial('admin/header', ['title' => $title, 'active' => $active, 'admin' => current_admin()]);
}

function admin_footer(): void
{
    partial('admin/footer');
}

/** Renders a thumbnail for an asset path (or a placeholder). */
function thumb(?string $path, int $size = 48): string
{
    if (!$path) {
        return '<span class="thumb thumb-empty" style="width:' . $size . 'px;height:' . $size . 'px"></span>';
    }
    return '<img class="thumb" src="' . e(admin_asset($path)) . '" alt="" width="' . $size . '" height="' . $size . '" loading="lazy">';
}
