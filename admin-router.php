<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePath = parse_url((string) (config('site_url') ?: ''), PHP_URL_PATH);
$basePath = is_string($basePath) && $basePath !== '/' ? rtrim($basePath, '/') : '';
if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
    $path = substr($path, strlen($basePath));
}
if (!preg_match('#^/manage/([a-f0-9]{64})/admin(?:/([A-Za-z0-9_-]+\.php))?/?$#i', $path, $match)) {
    http_response_code(404);
    exit('Not Found');
}

$token = $match[1];
$expected = (string) config('admin_access_token');
$file = $match[2] ?? 'index.php';
$gateActivity = (int) ($_SESSION['admin_gate_last_activity'] ?? 0);
if (!preg_match('/^[a-f0-9]{64}$/i', $expected)
    || !hash_equals($expected, $token)
    || $gateActivity < 1
    || time() - $gateActivity > 43200
    || !hash_equals(hash('sha256', $expected), (string) ($_SESSION['admin_gate'] ?? ''))
    || !is_file(__DIR__ . '/admin/' . $file)
) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    exit('Not Found');
}

$_SESSION['admin_gate_last_activity'] = time();
$_SERVER['SCRIPT_NAME'] = app_url('manage/' . $token . '/admin/' . $file);
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/admin/' . $file;
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
require $_SERVER['SCRIPT_FILENAME'];
