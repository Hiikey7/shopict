<?php
// /sitemap.xml: an index pointing to /sitemap-pages.xml (home, categories, brands, policies),
// /sitemap-products.xml (every visible product with its pictures) and /sitemap-guides.xml (published guides).
// Only pages that can appear in Google are listed: no hidden products, empty categories, filters or private pages.
// Built from the database on every visit, so new, changed and deleted products are reflected straight away.
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=1800');
$site = seo_site_url();
$x = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
$date = fn ($ts) => $ts ? date('c', is_numeric($ts) ? (int) $ts : strtotime((string) $ts)) : null;
$part = (string) ($_GET['part'] ?? '');

/** The newest change among the products matching a condition (for a listing page's lastmod). */
$lastChange = function (array $where, array $params): ?string {
    $stmt = db()->prepare('SELECT MAX(updated_at) FROM products WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: null;
};
$count = function (array $where, array $params): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
};

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";

if ($part === '') {
    $productsChanged = db()->query('SELECT MAX(updated_at) FROM products WHERE is_active = 1 AND noindex = 0')->fetchColumn();
    $guidesChanged = db()->query("SELECT MAX(updated_at) FROM posts WHERE status = 'published'")->fetchColumn();
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
    foreach (['pages' => $productsChanged, 'products' => $productsChanged, 'guides' => $guidesChanged] as $name => $changed) {
        if ($name === 'guides' && !$guidesChanged) {
            continue;
        }
        echo '  <sitemap><loc>', $x("$site/sitemap-$name.xml"), '</loc>', $changed ? '<lastmod>' . $date($changed) . '</lastmod>' : '', "</sitemap>\n";
    }
    echo "</sitemapindex>\n";
    exit;
}

if ($part === 'products') {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">', "\n";
    $rows = db()->query('SELECT * FROM products WHERE is_active = 1 AND noindex = 0 ORDER BY id')->fetchAll();
    $gallery = [];
    foreach (db()->query('SELECT product_id, image FROM product_images ORDER BY position, id')->fetchAll() as $g) {
        $gallery[(int) $g['product_id']][] = $g['image'];
    }
    foreach ($rows as $p) {
        $images = array_values(array_unique(array_filter([$p['img_src'], ...($gallery[(int) $p['id']] ?? [])])));
        echo '  <url><loc>', $x(seo_abs(product_url($p))), '</loc><lastmod>', $date($p['updated_at']), '</lastmod>';
        foreach (array_slice($images, 0, 10) as $img) {
            echo '<image:image><image:loc>', $x(seo_abs(asset($img))), '</image:loc></image:image>';
        }
        echo "</url>\n";
    }
    echo "</urlset>\n";
    exit;
}

if ($part === 'guides') {
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
    $posts = posts_published(5000);
    if ($posts) {
        echo '  <url><loc>', $x("$site/buying-guides"), '</loc><lastmod>', $date($posts[0]['updated_at']), "</lastmod></url>\n";
    }
    foreach ($posts as $post) {
        echo '  <url><loc>', $x(seo_abs(post_url($post))), '</loc><lastmod>', $date($post['updated_at']), "</lastmod></url>\n";
    }
    echo "</urlset>\n";
    exit;
}

// pages: home, shop, categories and brands that have products, and the policy pages
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
$any = $lastChange(['is_active = 1'], []);
$urls = [[$site . '/', $any], [seo_abs('shop.php'), $any]];
foreach (category_tree() as $main) {
    foreach ([$main, ...$main['children']] as $c) {
        [$sql, $params] = products_in_categories_sql(category_ids_within($c));
        if ($count(['is_active = 1', $sql], $params) > 0) {
            $urls[] = [seo_abs(category_url($c)), max((string) $lastChange(['is_active = 1', $sql], $params), (string) ($c['updated_at'] ?? ''))];
        }
    }
}
foreach (brands_public() as $b) {
    [$sql, $params] = brand_products_sql($b);
    if ($count(['is_active = 1', $sql], $params) > 0) {
        $urls[] = [seo_abs(brand_url($b['name'])), $lastChange(['is_active = 1', $sql], $params)];
    }
}
$urls[] = [seo_abs('about'), max((string) $any, date('Y-m-d H:i:s', (int) @filemtime(__DIR__ . '/about.php')))];
foreach (['terms.php', 'privacy-policy.php'] as $page) {
    $urls[] = [seo_abs($page), @filemtime(__DIR__ . '/' . $page) ?: null];
}
foreach (array_values(array_column($urls, null, 0)) as [$loc, $changed]) {
    echo '  <url><loc>', $x($loc), '</loc>', $changed ? '<lastmod>' . $date($changed) . '</lastmod>' : '', "</url>\n";
}
echo "</urlset>\n";
