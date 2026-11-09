<?php
// /google-merchant-feed.xml: product feed for Google Merchant Center (free listings and Shopping ads).
// Built from the database on every fetch, with the same title, price, stock, condition, pictures and links as the
// product pages. A product with options (Core i5 / Core i7) gives one item per option, grouped by item_group_id.
// In Merchant Center: Products → Feeds → add a "Scheduled fetch" of this address (daily).
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$x = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$money = fn ($n) => number_format((float) $n, 2, '.', '') . ' KES';
$fee = (float) setting('delivery_fee', '0');
$rows = array_map('price_product', db()->query('SELECT * FROM products WHERE is_active = 1 AND noindex = 0 ORDER BY id')->fetchAll());

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">', "\n<channel>\n";
echo '  <title>', $x(seo_store()), "</title>\n";
echo '  <link>', $x(seo_site_url() . '/'), "</link>\n";
echo '  <description>', $x(seo_store() . ' products'), "</description>\n";

foreach ($rows as $p) {
    $images = array_values(array_unique(array_filter([$p['img_src'], ...product_images((int) $p['id'])])));
    $description = product_description_text($p) ?: seo_meta_auto($p);
    $category = category_find(isset($p['category_id']) ? (int) $p['category_id'] : null);
    $type = $category ? implode(' > ', array_unique(array_filter([category_main($category)['name'] ?? null, $category['name']]))) : (string) $p['category'];
    $condition = seo_condition($p);
    $brand = $p['brand'] ? brand_display((string) $p['brand']) : null;
    $gtin = trim((string) ($p['gtin'] ?? ''));
    $mpn = trim((string) ($p['mpn'] ?? ''));
    $facts = seo_offer_facts($p);
    $variants = array_values(array_filter($facts['variants'], fn ($v) => !empty($v['options'])));
    $url = seo_abs(product_url($p));
    $items = [];
    if (count($variants) >= 2) {
        foreach ($variants as $v) {
            $items[] = [
                'id'     => $v['sku'] ?: $p['sku'] . '-' . $v['id'],
                'title'  => mb_substr($p['title'] . ' – ' . implode(', ', array_map('strval', $v['options'])), 0, 150),
                'link'   => $url . '?v=' . $v['id'],
                'price'  => (float) $v['price'],
                'old'    => $v['oldPrice'] !== null ? (float) $v['oldPrice'] : null,
                'stock'  => !empty($p['is_preorder']) ? 'preorder' : ($v['stock'] !== null && $v['stock'] <= 0 ? 'out_of_stock' : 'in_stock'),
                'opts'   => $v['options'],
                'group'  => $p['sku'] ?: (string) $p['id'],
            ];
        }
    } else {
        $items[] = [
            'id'    => $p['sku'] ?: (string) $p['id'],
            'title' => mb_substr($p['title'], 0, 150),
            'link'  => $url,
            'price' => $facts['low'],
            'old'   => $p['old_price'] !== null ? (float) $p['old_price'] : null,
            'stock' => ['InStock' => 'in_stock', 'OutOfStock' => 'out_of_stock', 'PreOrder' => 'preorder'][$facts['availability']],
            'opts'  => [],
            'group' => null,
        ];
    }
    foreach ($items as $it) {
        echo "  <item>\n";
        echo '    <g:id>', $x($it['id']), "</g:id>\n";
        echo '    <title>', $x($it['title']), "</title>\n";
        echo '    <description>', $x(mb_substr($description, 0, 5000)), "</description>\n";
        echo '    <link>', $x($it['link']), "</link>\n";
        if ($images) {
            echo '    <g:image_link>', $x(seo_abs(asset($images[0]))), "</g:image_link>\n";
            foreach (array_slice($images, 1, 10) as $img) {
                echo '    <g:additional_image_link>', $x(seo_abs(asset($img))), "</g:additional_image_link>\n";
            }
        }
        echo '    <g:availability>', $x($it['stock']), "</g:availability>\n";
        if ($it['old'] !== null && $it['old'] > $it['price']) {
            echo '    <g:price>', $money($it['old']), "</g:price>\n";
            echo '    <g:sale_price>', $money($it['price']), "</g:sale_price>\n";
        } else {
            echo '    <g:price>', $money($it['price']), "</g:price>\n";
        }
        echo '    <g:condition>', $condition, "</g:condition>\n";
        if ($brand) {
            echo '    <g:brand>', $x($brand), "</g:brand>\n";
        }
        if ($gtin !== '') {
            echo '    <g:gtin>', $x($gtin), "</g:gtin>\n";
        }
        if ($mpn !== '') {
            echo '    <g:mpn>', $x($mpn), "</g:mpn>\n";
        }
        if ($gtin === '' && $mpn === '') {
            echo "    <g:identifier_exists>no</g:identifier_exists>\n";
        }
        if ($type !== '') {
            echo '    <g:product_type>', $x($type), "</g:product_type>\n";
        }
        if ($it['group']) {
            echo '    <g:item_group_id>', $x($it['group']), "</g:item_group_id>\n";
        }
        foreach ($it['opts'] as $name => $value) {
            $key = ['color' => 'color', 'colour' => 'color', 'size' => 'size', 'material' => 'material', 'pattern' => 'pattern'][mb_strtolower((string) $name)] ?? null;
            if ($key) {
                echo "    <g:$key>", $x($value), "</g:$key>\n";
            }
        }
        if ($fee > 0) {
            echo '    <g:shipping><g:country>KE</g:country><g:service>Countrywide delivery</g:service><g:price>', $money($fee), "</g:price></g:shipping>\n";
        }
        echo "  </item>\n";
    }
}
echo "</channel>\n</rss>\n";
