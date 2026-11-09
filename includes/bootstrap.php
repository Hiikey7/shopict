<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$normalizedRequestPath = str_replace('\\', '/', $requestPath);
$isAdminRequest = preg_match('#(?:^|/)admin(?:/|$)#i', $normalizedRequestPath) === 1;
$isPrivateRequest = $isAdminRequest
    || preg_match('#(?:^|/)api(?:/|$)|(?:^|/)(?:cart|checkout|wishlist|order)\.php$#i', $normalizedRequestPath) === 1;
$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure'   => $isHttps,
]);
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if ($isPrivateRequest) {
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
    }
}

function config(string $key)
{
    global $config;
    return $config[$key] ?? null;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Anonymous visitor token that ties a browser to its cart and wishlist rows. */
function visitor_id(): string
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $name = config('visitor_cookie');
    $cookie = $_COOKIE[$name] ?? '';
    if (preg_match('/^[a-f0-9]{32}$/', $cookie)) {
        return $id = $cookie;
    }
    $id = bin2hex(random_bytes(16));
    setcookie($name, $id, [
        'expires'  => time() + 60 * 60 * 24 * 365,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $id;
    return $id;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function setting(string $key, ?string $default = null): ?string
{
    static $settings = null;
    if ($settings === null) {
        $settings = db()->query('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $settings[$key] ?? $default;
}

require __DIR__ . '/functions.php';
require __DIR__ . '/analytics.php';
require __DIR__ . '/categories.php';
require __DIR__ . '/finder.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/store.php';
require __DIR__ . '/mailer.php';
require __DIR__ . '/orders.php';
require __DIR__ . '/components.php';

if (!$isAdminRequest
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && !preg_match('#(?:^|/)checkout\.php$#i', $normalizedRequestPath)
) {
    csrf_token();
    session_write_close();
}

if (preg_match('#(?:^|/)api(?:/|$)#i', $normalizedRequestPath)) {
    $scope = 'api-' . basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'api'));
    if (request_rate_limited($scope, 240, 60)) {
        header('Retry-After: 60');
        json_response(['ok' => false, 'error' => 'Too many requests. Please wait a minute and try again.'], 429);
    }
}
if (!$isAdminRequest && setting('maintenance_mode') === '1') {
    http_response_code(503);
    header('Retry-After: 3600');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    partial('maintenance');
    exit;
}
