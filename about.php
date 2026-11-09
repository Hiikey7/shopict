<?php
// About page (/about, linked in the footer): who we are, then one tab per main category with what we sell,
// buying advice and links to its subcategories, brands and guides (Admin → SEO → Categories edits the text).
require __DIR__ . '/includes/bootstrap.php';

if (str_ends_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/')) { // "/about/" → "/about"
    header('Location: ../about', true, 301);
    exit;
}
visitor_id();
track_page_view('/about');

$store = seo_store();
$address = trim((string) setting('store_address', ''));
$hours = trim((string) setting('store_hours', ''));
$about = trim((string) setting('footer_about', ''));
$days = seo_return_days();
$tree = category_tree();

// Each main category: product count, price range and brands, for its tab.
$tabs = [];
foreach ($tree as $main) {
    $ids = category_ids_within($main);
    [$where, $params] = shop_filters(null, '', $ids);
    $count = db()->prepare('SELECT COUNT(*) FROM products WHERE ' . implode(' AND ', $where));
    $count->execute($params);
    [$min, $max] = shop_price_bounds(null, '', $ids);
    $tabs[] = ['cat' => $main, 'total' => (int) $count->fetchColumn(), 'min' => $min, 'max' => $max, 'brands' => seo_listing_brands($where, $params)];
}

$description = 'About ' . $store . ', Laxmi Plaza, Nairobi: laptops, monitors, phones, audio, printers and accessories, with buying advice for each. Free pickup or countrywide delivery.';
partial('page-start', [
    'title'       => 'About Us – ' . $store . ', Nairobi',
    'description' => seo_cut($description, 158),
    'canonical'   => 'about',
    'jsonLd'      => [
        [
            '@context'    => 'https://schema.org',
            '@type'       => 'AboutPage',
            'name'        => 'About ' . $store,
            'url'         => seo_abs('about'),
            'description' => $description,
            'about'       => seo_store_ld(),
        ],
        seo_breadcrumb_ld([['Home', seo_site_url() . '/'], ['About us', seo_abs('about')]]),
    ],
]);
?>
<section class="tf-sp-2 co-page">
  <div class="container">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">About us</span></nav>

    <div class="co-card about-intro">
      <p class="policy-eyebrow">About us</p>
      <h1 class="policy-title"><?= e($store) ?></h1>
      <?php if ($about !== ''): ?><p class="policy-lead mb-3"><?= e($about) ?></p><?php endif; ?>
      <ul class="about-facts">
        <?php if ($address !== ''): ?><li><strong>Shop:</strong> <?= e($address) ?><?= $hours !== '' ? ' · ' . e($hours) : '' ?></li><?php endif; ?>
        <li><strong>Getting your order:</strong> free pickup at our shop, or countrywide delivery across Kenya.</li>
        <li><strong>Ordering:</strong> online or on WhatsApp. Payment in cash on delivery or at pickup.</li>
        <?php if ($days): ?><li><strong>Returns:</strong> within <?= $days ?> days of purchase, in original condition (<a class="link" href="terms.php#returns">details</a>).</li><?php endif; ?>
      </ul>
    </div>

    <?php if ($tabs): $first = $tabs[0]['cat']['slug']; ?>
    <div class="co-card pv-tabs about-tabs mt-4">
      <ul class="nav pv-tab-list" role="tablist" aria-label="Product categories">
        <?php foreach ($tabs as $t): $slug = $t['cat']['slug']; ?>
        <li class="nav-item" role="presentation">
          <button class="pv-tab<?= $slug === $first ? ' active' : '' ?>" id="tab-<?= e($slug) ?>" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#pane-<?= e($slug) ?>" aria-controls="pane-<?= e($slug) ?>" aria-selected="<?= $slug === $first ? 'true' : 'false' ?>"><?= e($t['cat']['name']) ?></button>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="tab-content pv-tab-panes">
        <?php foreach ($tabs as $t): $slug = $t['cat']['slug']; ?>
        <div class="tab-pane fade<?= $slug === $first ? ' show active' : '' ?>" id="pane-<?= e($slug) ?>" role="tabpanel" aria-labelledby="tab-<?= e($slug) ?>" tabindex="0">
          <?php partial('listing-about', ['cat' => $t['cat'], 'brand' => null, 'brandCats' => [], 'tree' => $tree, 'total' => $t['total'], 'min' => $t['min'], 'max' => $t['max'], 'brands' => $t['brands'], 'embed' => true]); ?>
          <a class="tf-btn d-inline-flex mt-4" href="<?= e(category_url($t['cat'])) ?>"><span class="text-white">Shop <?= e(mb_strtolower($t['cat']['name'])) ?></span></a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<script>
  // /about#monitors opens the Monitors tab; choosing a tab updates the link.
  document.addEventListener("DOMContentLoaded", () => {
    const open = (slug) => { const b = document.getElementById("tab-" + slug); if (b && window.bootstrap) bootstrap.Tab.getOrCreateInstance(b).show(); };
    if (location.hash) open(location.hash.slice(1));
    document.querySelectorAll('.about-tabs [data-bs-toggle="tab"]').forEach((b) =>
      b.addEventListener("shown.bs.tab", () => history.replaceState(null, "", "#" + b.id.slice(4)))
    );
  });
</script>
<?php partial('page-end'); ?>
