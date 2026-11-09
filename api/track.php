<?php
// Analytics API: POST {type: "product_view", product_id, path} when Quick View opens.
require __DIR__ . '/../includes/bootstrap.php';

$input = api_input();
$productId = (int) ($input['product_id'] ?? 0);

if (($input['type'] ?? '') !== 'product_view' || !shop_product($productId)) {
    json_response(['ok' => false, 'error' => 'Unknown event'], 400);
}

$path = (string) parse_url((string) ($input['path'] ?? '/'), PHP_URL_PATH);
track_event('product_view', $productId, null, preg_replace('#/index\.php$#', '/', $path) ?: '/');
json_response(['ok' => true]);
