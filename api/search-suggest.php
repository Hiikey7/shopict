<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

enforce_api_rate_limit('search-suggest', 120, 60);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$rawQuery = $_GET['q'] ?? '';
$query = mb_substr(trim(is_string($rawQuery) ? $rawQuery : ''), 0, 100);
if (mb_strlen($query) < 2) {
    json_response(['ok' => true, 'products' => []]);
}

[$where, $params] = shop_filters(null, $query);
[$rankSql, $rankParams] = shop_search_rank($query);
$sql = 'SELECT id, title, category, img_src, price FROM products WHERE ' . implode(' AND ', $where)
    . " ORDER BY ($rankSql) DESC, COALESCE(sold, 0) DESC, id DESC LIMIT 6";
$stmt = db()->prepare($sql);
$stmt->execute([...$params, ...$rankParams]);
$products = [];
foreach ($stmt->fetchAll() as $product) {
    $products[] = [
        'title' => (string) $product['title'],
        'category' => (string) ($product['category'] ?? ''),
        'price' => money($product['price']),
        'image' => image_url((string) $product['img_src'], 48),
        'url' => product_url($product),
    ];
}

json_response(['ok' => true, 'products' => $products]);
