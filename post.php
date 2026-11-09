<?php
// One article (/how-to-choose-a-laptop-in-kenya-g3). Only published articles are shown; a logged-in admin can
// preview drafts (not indexed).
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin.php';

visitor_id();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch() ?: null;
$published = $post && $post['status'] === 'published' && (!$post['published_at'] || strtotime((string) $post['published_at']) <= time());
$preview = $post && !$published && current_admin();

if (!$post || (!$published && !$preview)) {
    http_response_code(404);
    partial('page-start', ['title' => 'Article not found · ' . seo_store(), 'robots' => 'noindex, follow']);
    echo '<section class="tf-sp-2 co-page"><div class="container"><div class="co-card text-center py-5"><h1 class="h5 fw-semibold mb-2">This article isn’t available</h1><p class="body-text-3 text-main-2 mb-4">It may have moved. See all our articles.</p><a class="tf-btn d-inline-flex" href="buying-guides"><span class="text-white">Articles</span></a></div></div></section>';
    partial('page-end');
    exit;
}
// One address per article: an old name in the link moves to the current one.
$asked = basename(rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
if ($asked !== post_url($post) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: ' . post_url($post), true, 301);
    exit;
}
track_page_view('/' . post_url($post));

// Products the article recommends (chosen in the admin), else popular products from its category.
$ids = array_values(array_filter(array_map('intval', explode(',', (string) $post['product_ids']))));
$products = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $q = db()->prepare("SELECT * FROM products WHERE is_active = 1 AND id IN ($in)");
    $q->execute($ids);
    $products = array_map('price_product', $q->fetchAll());
} elseif ($post['category_id'] && ($c = category_find((int) $post['category_id']))) {
    $products = category_feature_products($c, 4);
}
foreach ($products as $p) {
    register_product($p);
}
$category = category_find($post['category_id'] ? (int) $post['category_id'] : null);
$url = seo_abs(post_url($post));
$description = $post['meta_description'] ?: ($post['excerpt'] ?: seo_cut(seo_plain(seo_markdown((string) $post['body'])), 158));

partial('page-start', [
    'title'       => ($post['seo_title'] ?: $post['title'] . ' | ' . seo_store()),
    'description' => $description,
    'canonical'   => post_url($post),
    'robots'      => $preview ? 'noindex, nofollow' : null,
    'ogType'      => 'article',
    'ogImage'     => $post['image'] ?: null,
    'jsonLd'      => [
        seo_article_ld($post, $url),
        seo_breadcrumb_ld([['Home', seo_site_url() . '/'], ['Articles', seo_abs('buying-guides')], [$post['title'], $url]]),
    ],
]);
?>
<section class="tf-sp-2 co-page">
  <div class="container policy-page">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><a class="link" href="buying-guides">Articles</a><span>/</span><span class="text-truncate" aria-current="page"><?= e($post['title']) ?></span></nav>
    <?php if ($preview): ?><div class="alert alert-warning small">Preview: this article is <strong><?= e($post['status']) ?></strong> and only you can see it. Publish it in Admin → SEO → Articles.</div><?php endif; ?>
    <article class="co-card policy-card guide-article">
      <p class="policy-eyebrow"><?= e($category['name'] ?? 'Article') ?></p>
      <h1 class="policy-title"><?= e($post['title']) ?></h1>
      <p class="policy-updated"><?= $post['author'] ? 'By ' . e($post['author']) . ' · ' : '' ?><?= e(date('j F Y', strtotime((string) ($post['published_at'] ?: $post['created_at'])))) ?><?php if (strtotime((string) $post['updated_at']) - strtotime((string) ($post['published_at'] ?: $post['created_at'])) > 86400): ?> · Updated <?= e(date('j F Y', strtotime((string) $post['updated_at']))) ?><?php endif; ?></p>
      <?php if ($post['excerpt']): ?><p class="policy-lead"><?= e($post['excerpt']) ?></p><?php endif; ?>
      <?php if ($post['image']): ?><figure class="guide-hero"><?= str_replace('loading="lazy"', 'loading="eager" fetchpriority="high"', img($post['image'], 860, 480, '', $post['title'])) ?></figure><?php endif; ?>
      <div class="guide-body listing-about-text"><?= seo_markdown((string) $post['body'], 2) ?></div>
    </article>
    <?php if ($products): ?>
    <section class="mt-4" aria-labelledby="guideProducts">
      <h2 class="listing-about-title" id="guideProducts">Products mentioned in this article</h2>
      <div class="shop-grid guide-products">
        <?php foreach ($products as $p): partial('cards/product', ['p' => $p, 'class' => 'style-img-border', 'delay' => false]); endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
    <?php if ($category): ?>
    <nav class="pv-explore" aria-label="More to explore"><span class="pv-explore-label">Shop now:</span><ul class="listing-links"><li><a href="<?= e(category_url($category)) ?>"><?= e($category['name']) ?></a></li><li><a href="buying-guides">All articles</a></li></ul></nav>
    <?php endif; ?>
  </div>
</section>
<?php partial('page-end'); ?>
