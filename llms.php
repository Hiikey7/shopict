<?php
// /llms.txt: a short, plain guide to the shop for AI assistants (llmstxt.org format): who we are, how buying
// works, and links to the main categories, brands, guides and policies. An extra help for discovery only; search
// engines still rely on the normal pages, the sitemap and the structured data.
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$site = seo_site_url();
$store = seo_store();
$count = function (array $where, array $params): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
};
$out = [];
$out[] = '# ' . $store;
$out[] = '';
$out[] = '> ' . $store . ' is an electronics shop in Nairobi, Kenya (' . trim((string) setting('store_address', '')) . ') selling laptops, monitors, '
    . 'phones, audio, printers and computer accessories. Prices are in Kenyan shillings (KES). Customers order online or on WhatsApp '
    . 'and choose free pickup at the shop or countrywide delivery across Kenya.';
$out[] = '';
$out[] = '- Website: ' . $site . '/';
if ($phone = trim((string) setting('store_phone', ''))) {
    $out[] = '- Phone / WhatsApp: ' . $phone;
}
if ($email = trim((string) setting('store_email', ''))) {
    $out[] = '- Email: ' . $email;
}
if ($hours = trim((string) setting('store_hours', ''))) {
    $out[] = '- Opening hours: ' . $hours;
}
$out[] = '- Payment: cash on delivery or when picking up at the shop. No instalment plans.';
if ($days = seo_return_days()) {
    $out[] = "- Returns: within $days days of purchase, in original condition with all accessories and packaging.";
}
$out[] = '- Warranty: depends on the product and is given by the manufacturer; confirm with the shop before buying.';
$out[] = '- Each product page lists the current price, stock, specifications and options in its HTML and in schema.org Product data.';
$out[] = '';
$out[] = '## Product categories';
$out[] = '';
foreach (category_tree() as $main) {
    [$sql, $params] = products_in_categories_sql(category_ids_within($main));
    $n = $count(['is_active = 1', $sql], $params);
    $subs = [];
    foreach ($main['children'] as $sub) {
        [$s2, $p2] = products_in_categories_sql([$sub['id']]);
        if ($count(['is_active = 1', $s2], $p2) > 0) {
            $subs[] = $sub['name'];
        }
    }
    $out[] = '- [' . $main['name'] . '](' . seo_abs(category_url($main)) . "): $n products" . ($subs ? ' (' . implode(', ', $subs) . ')' : '');
}
$brands = [];
foreach (brands_public() as $b) {
    [$sql, $params] = brand_products_sql($b);
    if ($count(['is_active = 1', $sql], $params) > 0) {
        $brands[] = '- [' . $b['name'] . '](' . seo_abs(brand_url($b['name'])) . ')';
    }
}
if ($brands) {
    $out[] = '';
    $out[] = '## Brands';
    $out[] = '';
    array_push($out, ...$brands);
}
if ($posts = posts_published(30)) {
    $out[] = '';
    $out[] = '## Buying guides';
    $out[] = '';
    foreach ($posts as $post) {
        $out[] = '- [' . $post['title'] . '](' . seo_abs(post_url($post)) . ')' . ($post['excerpt'] ? ': ' . seo_plain($post['excerpt']) : '');
    }
}
$out[] = '';
$out[] = '## Policies and more';
$out[] = '';
$out[] = '- [About us and buying advice by category](' . seo_abs('about') . ')';
$out[] = '- [Terms, returns and warranty](' . seo_abs('terms.php') . ')';
$out[] = '- [Privacy policy](' . seo_abs('privacy-policy.php') . ')';
$out[] = '- [All products](' . seo_abs('shop.php') . ')';
$out[] = '- [Sitemap](' . $site . '/sitemap.xml)';
echo implode("\n", $out), "\n";
