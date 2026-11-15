<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$expected = (string) config('admin_access_token');
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePath = parse_url((string) (config('site_url') ?: ''), PHP_URL_PATH);
$basePath = is_string($basePath) && $basePath !== '/' ? rtrim($basePath, '/') : '';
if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
    $path = substr($path, strlen($basePath));
}
if (!preg_match('#^/manage/([a-f0-9]{64})/?$#i', $path, $match)
    || !preg_match('/^[a-f0-9]{64}$/i', $expected)
    || !hash_equals($expected, $match[1])
) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    exit('Not Found');
}

session_regenerate_id(true);
$_SESSION['admin_gate'] = hash('sha256', $expected);
$_SESSION['admin_gate_last_activity'] = time();
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('Location: ' . app_url('manage/' . rawurlencode($expected) . '/admin/login.php'), true, 302);
exit;
