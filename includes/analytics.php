<?php
declare(strict_types=1);

// Visitor analytics: page views, Quick View opens (product views) and add-to-carts,
// stored in analytics_events and reported in admin/analytics.php.

const ANALYTICS_EVENT_TYPES = ['page_view', 'product_view', 'add_to_cart', 'order_placed'];

function is_bot(): bool
{
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    return $ua === '' || strlen($ua) > 512 || (bool) preg_match(
        '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse|pagespeed|uptimerobot|pingdom|ahrefs|semrush|mj12|dotbot|petalbot|bytespider/i',
        $ua
    );
}

function device_type(): string
{
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/iPad|Tablet/i', $ua)) {
        return 'tablet';
    }
    return preg_match('/Mobi|Android|iPhone/i', $ua) ? 'mobile' : 'desktop';
}

/** Host of the referring site, or null when the visitor came from this site or typed the address. */
function referrer_host(?string $referrer = null): ?string
{
    $host = parse_url((string) ($referrer ?? $_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
    if (!$host) {
        return null;
    }
    $own = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    $host = strtolower(preg_replace('/^www\./i', '', $host));
    return $host === strtolower(preg_replace('/^www\./i', '', (string) $own)) ? null : substr($host, 0, 255);
}

/** Normalise an internal path and discard query strings that may contain search terms or personal data. */
function analytics_path(?string $path = null): string
{
    $candidate = $path ?? (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if (parse_url($candidate, PHP_URL_SCHEME) !== null || parse_url($candidate, PHP_URL_HOST) !== null) {
        return '/';
    }
    $pathOnly = parse_url($candidate, PHP_URL_PATH);
    $pathOnly = is_string($pathOnly) ? $pathOnly : '/';
    $pathOnly = preg_replace('/[\x00-\x20\x7f]/', '', $pathOnly) ?: '/';
    if (!str_starts_with($pathOnly, '/') || str_starts_with($pathOnly, '//')) {
        return '/';
    }
    $pathOnly = preg_replace('#/index\.php$#', '/', $pathOnly) ?: '/';
    return substr($pathOnly, 0, 255);
}

/** Normalised path of the current page, e.g. "/" or "/index.php" → "/". */
function current_path(): string
{
    return analytics_path();
}

/** Records an analytics event. Never breaks the page: failures are only logged. */
function track_event(string $type, ?int $productId = null, ?int $variantId = null, ?string $path = null, ?string $referrer = null): void
{
    $safePath = analytics_path($path ?? current_path());
    if (!in_array($type, ANALYTICS_EVENT_TYPES, true)
        || is_bot()
        || preg_match('#^/(?:admin|manage|api)(?:/|$)#i', $safePath)
    ) {
        return;
    }
    try {
        db()->prepare(
            'INSERT INTO analytics_events (type, path, product_id, variant_id, visitor_id, referrer, device, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $type,
            $safePath,
            $productId,
            $variantId,
            visitor_id(),
            $type === 'page_view' ? referrer_host($referrer) : null,
            device_type(),
            date('Y-m-d H:i:s'),
        ]);
    } catch (PDOException $e) {
        error_log('analytics: ' . $e->getMessage());
    }
}

/** Call at the top of every storefront page (product pages pass their own path). Skips HEAD requests and browser prefetches. */
function track_page_view(?string $path = null, ?int $productId = null): void
{
    $purpose = strtolower((string) ($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? ''));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || str_contains($purpose, 'prefetch')) {
        return;
    }
    track_event('page_view', $productId, null, $path);
}
