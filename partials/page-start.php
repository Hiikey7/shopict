<?php
/**
 * Opening of every storefront page: <head>, the header and the wrapper.
 *
 * @var string $title page title
 */
$title = $title ?? setting('store_name', 'Shop ICT Gadgets');
$siteName = setting('store_name', 'Shop ICT Gadgets');
$siteUrl = rtrim((string) (config('site_url') ?: 'https://www.shopict.co.ke'), '/');
$pagePath = ltrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$pageDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($pageDir !== '' && str_starts_with($pagePath, $pageDir . '/')) {
    $pagePath = substr($pagePath, strlen($pageDir) + 1); // when the shop runs in a sub-folder locally
}
// The one address search engines should use for this page (pages may pass their own $canonical).
$canonical = isset($canonical) ? seo_abs($canonical) : $siteUrl . '/' . ($pagePath === 'index.php' ? '' : $pagePath);
// Carts, checkout, orders and the wishlist are private: kept out of search results (pages may pass $robots too).
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$robots = $robots ?? (in_array($script, ['cart.php', 'checkout.php', 'wishlist.php', 'order.php'], true) || http_response_code() >= 400 ? 'noindex, follow' : null);
if ($robots !== null && !headers_sent()) {
    header('X-Robots-Tag: ' . $robots);
}
$googleVerify = trim((string) setting('seo_google_verification', ''));
$bingVerify = trim((string) setting('seo_bing_verification', ''));
$ga4 = preg_match('/^G-[A-Z0-9]{4,20}$/', trim((string) setting('seo_ga4_id', ''))) ? trim((string) setting('seo_ga4_id')) : null;
$description = $description ?? $siteName . ' – laptops, monitors, phones, audio, printers and accessories in Nairobi, Kenya. Free pickup at Laxmi Plaza and countrywide delivery.';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>" />
    <?php if ($robots !== null): ?><meta name="robots" content="<?= e($robots) ?>" />
    <?php else: ?><meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />
    <?php endif; ?><link rel="canonical" href="<?= e($canonical) ?>" />
    <?php if ($googleVerify !== ''): ?><meta name="google-site-verification" content="<?= e($googleVerify) ?>" />
    <?php endif; if ($bingVerify !== ''): ?><meta name="msvalidate.01" content="<?= e($bingVerify) ?>" />
    <?php endif; ?><meta property="og:locale" content="en_KE" />
    <meta property="og:site_name" content="<?= e($siteName) ?>" />
    <meta property="og:title" content="<?= e($title) ?>" />
    <meta property="og:description" content="<?= e($description) ?>" />
    <meta property="og:url" content="<?= e($canonical) ?>" />
    <meta property="og:type" content="<?= isset($ogType) ? e($ogType) : 'website' ?>" />
    <meta property="og:image" content="<?= e(isset($ogImage) ? (preg_match('#^https?://#', asset($ogImage)) ? asset($ogImage) : $siteUrl . '/' . asset($ogImage)) : $siteUrl . '/' . asset(store_logo())) ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <?php foreach ($jsonLd ?? [] as $ld): ?><script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <?php endforeach; ?>
    <meta name="theme-color" content="#e30613" />
    <link rel="manifest" href="manifest.json" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-title" content="ICT Gadgets" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <link rel="icon" href="favicon.ico?v=<?= @filemtime(__DIR__ . '/../favicon.ico') ?: 1 ?>" sizes="any" />
    <link rel="icon" href="<?= asset_v('images/logo/favicon-64.png') ?>" type="image/png" sizes="64x64" />
    <link rel="apple-touch-icon" href="<?= asset_v('images/logo/apple-touch-icon.png') ?>" />
    <link rel="stylesheet" href="<?= asset('css/bootstrap-select.css') ?>" />
    <link rel="stylesheet" href="<?= asset_v('css/main.css') ?>" />
    <link rel="stylesheet" href="<?= asset('css/photoswipe.css') ?>" />
    <link rel="stylesheet" href="<?= asset_v('css/custom.css') ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&amp;family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
      rel="stylesheet"
    />
    <link href="https://fonts.cdnfonts.com/css/helvetica-neue-55" rel="stylesheet" />
    <?php if ($ga4): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4) ?>"></script>
    <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', <?= json_encode($ga4) ?>>);</script>
    <?php endif; ?>
  </head>
  <body>
    <div id="wrapper">
      <div class="site-head" data-site-head>
        <?php partial('topbar'); ?>
        <?php partial('header'); ?>
      </div>
