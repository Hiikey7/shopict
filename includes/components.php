<?php
declare(strict_types=1);

// Small UI pieces shared by the product cards. Markup matches the original React components;
// the data-* attributes are the hooks used by assets/js/main.js.

function btn_add_to_cart(int $id, string $tooltipClass = ''): string
{
    return '<a href="#shoppingCart" data-bs-toggle="offcanvas" data-cart-add="' . $id . '" class="box-icon add-to-cart btn-icon-action hover-tooltip ' . e($tooltipClass) . '">'
        . '<span class="icon icon-cart2"></span>'
        . '<span class="tooltip">' . (in_cart($id) ? 'Already Added' : 'Add to Cart') . '</span></a>';
}

function btn_wishlist(int $id, string $tooltipClass = ''): string
{
    $added = in_wishlist($id);
    return '<a href="#" data-wishlist="' . $id . '" class="box-icon btn-icon-action hover-tooltip ' . e($tooltipClass) . '">'
        . '<span class="icon ' . ($added ? 'icon-trash' : 'icon-heart2') . ' "></span>'
        . '<span class="tooltip"> ' . ($added ? 'Remove Wishlist' : 'Add to Wishlist') . '</span></a>';
}

function btn_quickview(int $id, string $tooltipClass = ''): string
{
    return '<a href="#quickView" data-bs-toggle="modal" data-quickview="' . $id . '" class="box-icon quickview btn-icon-action hover-tooltip ' . e($tooltipClass) . '">'
        . '<span class="icon icon-view"></span>'
        . '<span class="tooltip">Quick View</span></a>';
}

/** The two hover images inside a card's product-img link. */
function product_images_pair(array $p): string
{
    $w = $p['img_width'] ?? 500;
    $h = $p['img_height'] ?? 500;
    $alts = seo_image_alts($p, 2);
    return img($p['img_src'], $w, $h, 'img-product lazyload', $alts[0])
        . img($p['img_hover'] ?? $p['img_src'], $p['hover_width'] ?? $w, $p['hover_height'] ?? $h, 'img-hover lazyload', $p['img_hover'] ? $alts[1] : '');
}

/** WOW.js attributes for a card: class suffix and optional data-wow-delay. */
function wow_class(array $p): string
{
    return !empty($p['animation']) ? 'wow ' . $p['animation'] : '';
}

function wow_delay_attr(array $p): string
{
    return !empty($p['wow_delay']) ? ' data-wow-delay="' . e($p['wow_delay']) . '"' : '';
}

/**
 * Deal countdown. The timer itself is drawn by JavaScript; the server renders the
 * "time's up" state when the deal has already ended, as the original did.
 */
function countdown_placeholder(): string
{
    $ends = strtotime((string) setting('deal_ends_at', ''));
    return ($ends === false || $ends <= time()) ? '<div>Time&#x27;s up!</div>' : '';
}

// ---------------------------------------------------------------- hero banner feature icons

/** Icons offered for hero banner features (Admin → Banners). "chip" is the template's original icon. */
const FEATURE_ICONS = [
    'refresh' => 'Refresh rate',
    'screen'  => 'Screen / resolution',
    'ports'   => 'Ports / connectivity',
    'chip'    => 'Processor (chip)',
    'memory'  => 'Memory / RAM',
    'storage' => 'Storage',
    'battery' => 'Battery',
    'camera'  => 'Camera',
];

/** The icon key for a feature: the one chosen in the admin, or a guess from its text. */
function feature_icon_key(array $feature): string
{
    $chosen = (string) ($feature['icon'] ?? '');
    if (isset(FEATURE_ICONS[$chosen])) {
        return $chosen;
    }
    $text = strtolower(($feature['label'] ?? '') . ' ' . ($feature['value'] ?? ''));
    $rules = [
        'refresh' => '/\bhz\b|\d\s*hz|refresh|fps/',
        'ports'   => '/port|hdmi|usb|thunderbolt|connect|jack|bluetooth|wi-?fi/',
        'screen'  => '/resolution|\d{3,4}\s*[x×]\s*\d{3,4}|screen|display|inch|"|qhd|uhd|\b[248]k\b|panel/',
        'battery' => '/battery|mah|charg/',
        'memory'  => '/\bram\b|memory|ddr/',
        'storage' => '/storage|ssd|hdd|\btb\b|rom/',
        'camera'  => '/camera|\bmp\b|lens/',
        'chip'    => '/cpu|processor|chip|snapdragon|core|ryzen|gpu/',
    ];
    foreach ($rules as $key => $pattern) {
        if (preg_match($pattern, $text)) {
            return $key;
        }
    }
    return 'chip';
}

/** Inline white line icon (26 × 26) for a hero feature. */
function feature_icon(array $feature): string
{
    static $chip = null;
    $key = feature_icon_key($feature);
    if ($key === 'chip') {
        return $chip ??= (string) file_get_contents(__DIR__ . '/../partials/hero-feature-icon.svg');
    }
    $paths = [
        // Circular arrows
        'refresh' => '<path d="M21.5 9.5A9 9 0 0 0 5.2 7.2"/><path d="M4.5 3.5v4h4"/><path d="M4.5 16.5a9 9 0 0 0 16.3 2.3"/><path d="M21.5 22.5v-4h-4"/><path d="M10 13h1.6l1.4-3 1.6 6 1.4-3H17"/>',
        // Monitor with resolution corners
        'screen'  => '<rect x="2.5" y="4" width="21" height="14" rx="1.5"/><path d="M9.5 22h7M13 18v4"/><path d="M6 10V7.5h2.5M20 10V7.5h-2.5M6 12v2.5h2.5M20 12v2.5h-2.5"/>',
        // Plug and cable
        'ports'   => '<path d="M8.5 3v4.5M17.5 3v4.5"/><path d="M5.5 7.5h15v4a7.5 7.5 0 0 1-15 0z"/><path d="M13 19v4"/><path d="M10.5 12.5h5"/>',
        'memory'  => '<rect x="2.5" y="7" width="21" height="10" rx="1"/><path d="M6 10.5h3v3H6zM11.5 10.5h3v3h-3zM17 10.5h3v3h-3z"/><path d="M5 17v2.5M9 17v2.5M13 17v2.5M17 17v2.5M21 17v2.5"/>',
        'storage' => '<rect x="3" y="4" width="20" height="18" rx="2"/><circle cx="13" cy="12" r="4.5"/><circle cx="13" cy="12" r="1"/><path d="M19 19h.01"/>',
        'battery' => '<rect x="2.5" y="7.5" width="18" height="11" rx="2"/><path d="M23.5 11v4"/><path d="M12 9.5l-3 4h4l-3 4"/>',
        'camera'  => '<path d="M3 8.5a2 2 0 0 1 2-2h3l2-2.5h6l2 2.5h3a2 2 0 0 1 2 2V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="13" cy="13.5" r="4"/>',
    ];
    return '<svg width="26" height="26" viewBox="0 0 26 26" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$key] . '</svg>';
}
