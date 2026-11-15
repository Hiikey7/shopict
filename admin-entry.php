<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$expected = (string) config('admin_access_token');
$provided = (string) ($_GET['key'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/i', $expected) || !hash_equals($expected, $provided)) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    exit('Not Found');
}

session_regenerate_id(true);
$_SESSION['admin_gate'] = hash('sha256', $expected);
$_SESSION['admin_gate_last_activity'] = time();
header('Referrer-Policy: no-referrer');
header('Location: /admin/login.php', true, 302);
exit;
