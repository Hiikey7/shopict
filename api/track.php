<?php
// Analytics API: POST {type: "product_view", product_id, path} when Quick View opens.
require __DIR__ . '/../includes/bootstrap.php';

enforce_api_rate_limit('analytics-track', 60, 60);

$input = api_input();
$productId = (int) ($input['product_id'] ?? 0);

if (($input['type'] ?? '') !== 'product_view' || !shop_product($productId)) {
    json_response(['ok' => false, 'error' => 'Unknown event'], 400);
}

$pathInput = $input['path'] ?? '/';
$path = is_string($pathInput) ? $pathInput : '/';
track_event('product_view', $productId, null, preg_replace('#/index\.php$#', '/', $path) ?: '/');
json_response(['ok' => true]);
