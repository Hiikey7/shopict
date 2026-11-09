<?php
// Articles (/buying-guides): published articles, newest first. Written and approved in Admin → SEO → Articles.
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
track_page_view('/buying-guides');

$posts = posts_published(100);
$store = seo_store();
$description = 'Practical technology articles from ' . $store . ', Nairobi: how to choose laptops, monitors, phones and accessories, and what the specifications mean.';
partial('page-start', [
    'title'       => 'Articles – Laptops, Monitors & Phones | ' . $store,
    'description' => $description,
    'canonical'   => 'buying-guides',
    'robots'      => $posts ? null : 'noindex, follow',
    'jsonLd'      => [
        [
            '@context'   => 'https://schema.org',
            '@type'      => 'CollectionPage',
            'name'       => 'Articles',
            'url'        => seo_abs('buying-guides'),
            'description' => $description,
            'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => array_map(fn ($post, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => seo_abs(post_url($post)), 'name' => $post['title']], $posts, array_keys($posts))],
        ],
        seo_breadcrumb_ld([['Home', seo_site_url() . '/'], ['Articles', seo_abs('buying-guides')]]),
    ],
]);
?>
<section class="tf-sp-2 co-page">
  <div class="container policy-page">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">Articles</span></nav>
    <div class="co-card policy-card">
      <p class="policy-eyebrow">Advice</p>
      <h1 class="policy-title">Articles</h1>
      <p class="policy-lead">Practical advice on choosing laptops, monitors, phones and accessories, from the team at <?= e($store) ?>.</p>
      <?php if (!$posts): ?>
      <p class="body-text-3 text-main-2 mb-0">New articles are on the way. Meanwhile, <a class="link" href="shop.php">browse our products</a> or ask our team on WhatsApp.</p>
      <?php else: ?>
      <ul class="guide-list">
        <?php foreach ($posts as $post): ?>
        <li class="guide-item">
          <?php if ($post['image']): ?><a class="guide-thumb" href="<?= e(post_url($post)) ?>" tabindex="-1" aria-hidden="true"><?= img($post['image'], 240, 160, '', '') ?></a><?php endif; ?>
          <div>
            <h2 class="guide-item-title"><a class="link" href="<?= e(post_url($post)) ?>"><?= e($post['title']) ?></a></h2>
            <?php if ($post['excerpt']): ?><p class="guide-item-text"><?= e($post['excerpt']) ?></p><?php endif; ?>
            <p class="guide-item-date"><?= e(date('j F Y', strtotime((string) ($post['published_at'] ?: $post['created_at'])))) ?></p>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php partial('page-end'); ?>
