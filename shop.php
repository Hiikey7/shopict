<?php
// Shop page: every product, or one category, or search results. Sortable and paginated.
require __DIR__ . '/includes/bootstrap.php';

visitor_id();

// Short addresses: /laptops (a category) or /hp (a brand) arrive here as ?seg=… (router.php / .htaccess).
$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$requestQuery = $_GET;
unset($requestQuery['seg']);
if (isset($_GET['seg'])) {
    $seg = strtolower((string) $_GET['seg']);
    unset($_GET['seg']);
    if (category_by_slug($seg)) {
        $_GET['cat'] = $seg;
    } elseif (brand_by_slug($seg)) {
        $_GET['make'] = $seg;
    } else {
        http_response_code(404);
        partial('page-start', ['title' => 'Page not found · ' . setting('store_name', 'Shop ICT Gadgets'), 'robots' => 'noindex, follow']);
        echo '<section class="tf-sp-2 co-page"><div class="container"><div class="co-card text-center py-5"><h1 class="h5 fw-semibold mb-2">Page not found</h1><p class="body-text-3 text-main-2 mb-4">This page doesn’t exist or has moved. Browse our products instead.</p><a class="tf-btn d-inline-flex" href="shop.php"><span class="text-white">See all products</span></a></div></div></section>';
        partial('page-end');
        exit;
    }
    if (str_ends_with($requestPath, '/') && $_SERVER['REQUEST_METHOD'] === 'GET') { // "/laptops/" → "/laptops"
        header('Location: ../' . $seg . ($requestQuery ? '?' . http_build_query($requestQuery) : ''), true, 301);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && str_ends_with($requestPath, 'shop.php')
    && ((isset($_GET['cat']) && is_string($_GET['cat']) && category_by_slug($_GET['cat'])) || (isset($_GET['make']) && is_string($_GET['make']) && brand_by_slug($_GET['make'])))) {
    // Old links (shop.php?cat=laptops) move to the short address for good.
    header('Location: ' . shop_url($_GET), true, 301);
    exit;
}
$brand = isset($_GET['make']) && is_string($_GET['make']) ? brand_by_slug($_GET['make']) : null;

$category = trim((string) ($_GET['category'] ?? '')); // old-style links (?category=Name)
// One category (?cat=laptops) or several ticked in the sidebar (?cat[]=laptops&cat[]=monitors).
$catRaw = $_GET['cat'] ?? [];
$cats = [];
foreach (array_slice(is_array($catRaw) ? $catRaw : [$catRaw], 0, 30) as $slug) {
    if (is_string($slug) && ($found = category_by_slug(trim($slug)))) {
        $cats[$found['id']] = $found;
    }
}
// A ticked main category already covers its subcategories.
$cats = array_filter($cats, fn ($c) => $c['parent_id'] === null || !isset($cats[$c['parent_id']]));
$cats = array_values($cats);
$cat = count($cats) === 1 ? $cats[0] : null; // exactly one: breadcrumb, heading and Product finder work as before
$catMain = category_main($cat);
$catSlugs = array_column($cats, 'slug');
$catParam = $cats ? (count($cats) === 1 ? $catSlugs[0] : $catSlugs) : null;
$catIds = array_values(array_unique(array_merge([], ...array_map('category_ids_within', $cats))));
$checkedIds = array_column($cats, 'id');
$openMainIds = array_values(array_unique(array_map(fn ($c) => category_main($c)['id'], $cats)));
$search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$sorts = ['relevance' => 'Most relevant', 'random' => 'Random', 'newest' => 'Newest', 'popular' => 'Best selling', 'price-asc' => 'Price: low to high', 'price-desc' => 'Price: high to low'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$priceParam = fn (string $k) => ($v = preg_replace('/\D+/', '', (string) ($_GET[$k] ?? ''))) === '' ? null : (int) $v;
$minPrice = $priceParam('min');
$maxPrice = $priceParam('max');
if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
}
$perPage = 20;

track_page_view('/' . shop_url(['cat' => $catParam, 'make' => $brand['slug'] ?? null, 'category' => $category, 'q' => $search]));
$finderSel = finder_selection($cat);
$finderParams = finder_params($finderSel);
$defaultSort = $search !== ''
    ? 'relevance'
    : (!$cats && !$brand && $category === '' && $minPrice === null && $maxPrice === null && !$finderParams ? 'random' : 'newest');
$sort = isset($sorts[$_GET['sort'] ?? '']) ? $_GET['sort'] : $defaultSort;
[$products, $total] = shop_products($category ?: null, $search, $sort, $page, $perPage, $catIds, $minPrice, $maxPrice, $finderSel, $brand);
[$boundMin, $boundMax] = shop_price_bounds($category ?: null, $search, $catIds, $finderSel, $brand);
$keep = ['q' => $search, 'min' => $minPrice, 'max' => $maxPrice, 'make' => $brand['slug'] ?? null]; // kept when switching category
$pages = max(1, (int) ceil($total / $perPage));
$tree = category_tree();
$catNames = array_column($cats, 'name');
$catName = $cats ? (count($cats) <= 3 ? implode(', ', $catNames) : count($cats) . ' categories') : ($category !== '' ? $category : '');
$heading = $search !== '' ? 'Results for “' . $search . '”' : ($catName !== '' ? ($brand ? $brand['name'] . ' ' : '') . $catName : ($brand ? $brand['name'] . ' products' : 'All products'));
$link = fn (array $over) => shop_url(array_merge(['cat' => $catParam, 'make' => $brand['slug'] ?? null, 'category' => $category, 'q' => $search, 'min' => $minPrice, 'max' => $maxPrice, 'sort' => $sort === 'newest' ? null : $sort] + $finderParams, $over));

// ---- Search engines. Only plain category, brand and "all products" pages are indexed; searches, filters and
// several categories at once are kept out (noindex) and point to the plain page, so Google doesn't see thousands
// of near-identical pages. Pages 2, 3… are their own pages. Sorting doesn't make a new page (same canonical).
$store = setting('store_name', 'Shop ICT Gadgets');
$filtered = $search !== '' || $minPrice !== null || $maxPrice !== null || $finderParams || count($cats) > 1 || $category !== '' || ($brand && $cats);
$plainParams = ['cat' => $cat['slug'] ?? null, 'make' => $cat ? null : ($brand['slug'] ?? null)];
$canonical = shop_url($plainParams + ['page' => $filtered ? null : $page]);
$robots = $filtered || $total === 0 || $page > $pages ? 'noindex, follow' : null;
$pageNote = $page > 1 ? ' – Page ' . $page : '';
$listingBrands = [];
if (!$filtered && !$brand) {
    [$lw, $lp] = shop_filters(null, '', $catIds);
    $listingBrands = seo_listing_brands($lw, $lp);
}
$brandCats = [];
if ($cat) {
    $seoName = $cat['name'];
    $seoTitle = trim((string) ($cat['seo_title'] ?? '')) ?: 'Buy ' . $cat['name'] . ' in Kenya – Prices & Deals | ' . $store;
    $seoDesc = trim((string) ($cat['meta_description'] ?? '')) ?: seo_cut(seo_listing_intro($cat['name'], $total, $boundMin, $boundMax, $listingBrands), 158);
} elseif ($brand) {
    $brandRow = $brand['row'] ?? [];
    foreach ($tree as $main) { // the brand's main categories, for its title and links
        [$bw, $bp] = shop_filters(null, '', category_ids_within($main), [], $brand);
        $cnt = db()->prepare('SELECT COUNT(*) FROM products WHERE ' . implode(' AND ', $bw));
        $cnt->execute($bp);
        if ((int) $cnt->fetchColumn() > 0) {
            $brandCats[] = $main;
        }
    }
    $seoName = $brand['name'] . ' products';
    $catList = array_column($brandCats, 'name');
    $seoTitle = trim((string) ($brandRow['seo_title'] ?? '')) ?: $brand['name'] . ' in Kenya' . ($catList ? ' – ' . (count($catList) > 1 ? implode(', ', array_slice($catList, 0, -1)) . ' & ' . end($catList) : $catList[0]) : ' – Shop Online') . ' | ' . $store;
    $seoDesc = trim((string) ($brandRow['meta_description'] ?? '')) ?: seo_cut(seo_listing_intro($brand['name'] . ' products', $total, $boundMin, $boundMax, []), 158);
} else {
    $seoName = 'All products';
    $seoTitle = 'Shop Laptops, Monitors, Phones & Accessories in Kenya | ' . $store;
    $seoDesc = seo_cut(seo_listing_intro('products', $total, $boundMin, $boundMax, $listingBrands), 158);
}
if ($filtered) {
    $seoTitle = $heading . ' · ' . $store;
}
$crumbsLd = [['Home', seo_site_url() . '/'], ['Shop', seo_abs('shop.php')]];
if ($cat && $catMain && $catMain['id'] !== $cat['id']) {
    $crumbsLd[] = [$catMain['name'], seo_abs(category_url($catMain))];
}
if ($cat) {
    $crumbsLd[] = [$cat['name'], seo_abs(category_url($cat))];
} elseif ($brand) {
    $crumbsLd[] = [$brand['name'], seo_abs(brand_url($brand['name']))];
}
partial('page-start', [
    'title'       => $seoTitle . ($filtered ? '' : $pageNote),
    'description' => $seoDesc,
    'canonical'   => $canonical,
    'robots'      => $robots,
    'jsonLd'      => $filtered ? [] : [seo_collection_ld($seoName, seo_abs($canonical), $seoDesc, $products), seo_breadcrumb_ld($crumbsLd)],
]);
?>
<section class="tf-sp-2 co-page">
  <div class="container">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb">
      <a class="link" href="./">Home</a><span>/</span>
      <?php if ($catName !== '' || $search !== '' || $brand): ?><a class="link" href="shop.php">Shop</a><span>/</span><?php endif; ?>
      <?php if ($brand && $catName !== ''): ?><a class="link" href="<?= e(brand_url($brand['name'])) ?>"><?= e($brand['name']) ?></a><span>/</span><?php endif; ?>
      <?php if ($cat && $catMain && $catMain['id'] !== $cat['id']): ?><a class="link" href="<?= e(category_url($catMain)) ?>"><?= e($catMain['name']) ?></a><span>/</span><?php endif; ?>
      <span aria-current="page"><?= e($catName !== '' ? $catName : ($brand ? $brand['name'] : ($search !== '' ? 'Search' : 'Shop'))) ?></span>
    </nav>

    <div class="row g-4">
      <aside class="col-lg-3 d-none d-lg-block">
        <div class="co-card shop-side">
          <form class="shop-price" method="get" action="shop.php" aria-labelledby="priceTitle">
            <h6 class="shop-side-title" id="priceTitle">Price</h6>
            <?php if ($cats): foreach ($catSlugs as $slug): ?><input type="hidden" name="cat[]" value="<?= e($slug) ?>"><?php endforeach; elseif ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
            <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif; ?>
            <?php if ($brand): ?><input type="hidden" name="make" value="<?= e($brand['slug']) ?>"><?php endif; ?>
            <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
            <?php foreach ($finderParams as $fk => $fvs): foreach ($fvs as $fv): ?><input type="hidden" name="<?= e($fk) ?>[]" value="<?= e($fv) ?>"><?php endforeach; endforeach; ?>
            <div class="shop-price-row">
              <input type="number" name="min" min="0" step="1" inputmode="numeric" value="<?= $minPrice !== null ? $minPrice : '' ?>" placeholder="Min" aria-label="Minimum price in KSh<?= $boundMin !== null ? ' (lowest here: ' . number_format($boundMin) . ')' : '' ?>">
              <span class="shop-price-dash" aria-hidden="true"></span>
              <input type="number" name="max" min="0" step="1" inputmode="numeric" value="<?= $maxPrice !== null ? $maxPrice : '' ?>" placeholder="Max" aria-label="Maximum price in KSh<?= $boundMax !== null ? ' (highest here: ' . number_format($boundMax) . ')' : '' ?>">
              <button class="shop-price-apply" type="submit">Apply</button>
            </div>
            <?php if ($minPrice !== null || $maxPrice !== null): ?>
            <p class="shop-price-note">Showing <?= $minPrice !== null ? money($minPrice) : 'any' ?> – <?= $maxPrice !== null ? money($maxPrice) : 'any' ?> · <a class="link" href="<?= e($link(['min' => null, 'max' => null, 'page' => null])) ?>">Clear</a></p>
            <?php endif; ?>
          </form>
          <form class="shop-cat-form" method="get" action="shop.php" data-cat-form aria-labelledby="catTitle">
            <div class="shop-side-head">
              <h6 class="shop-side-title" id="catTitle">Category</h6>
              <?php if ($cats): ?><a class="shop-cat-clear link" href="<?= e(shop_url($keep)) ?>">Clear</a><?php endif; ?>
            </div>
            <?php foreach (['q' => $search, 'make' => $brand['slug'] ?? null, 'min' => $minPrice, 'max' => $maxPrice, 'sort' => $sort === 'newest' ? null : $sort] as $hk => $hv): if ($hv !== null && $hv !== ''): ?><input type="hidden" name="<?= $hk ?>" value="<?= e((string) $hv) ?>"><?php endif; endforeach; ?>
            <?php foreach ($finderParams as $fk => $fvs): foreach ($fvs as $fv): ?><input type="hidden" name="<?= e($fk) ?>[]" value="<?= e($fv) ?>"><?php endforeach; endforeach; ?>
            <ul class="shop-cats">
              <li class="shop-cat-main<?= !$cats && $category === '' ? ' is-current' : '' ?>"><div class="shop-cat-row"><a href="<?= e(shop_url($keep)) ?>"><span>All products</span></a></div></li>
              <?php foreach ($tree as $main): $mainOn = in_array($main['id'], $checkedIds, true); $open = in_array($main['id'], $openMainIds, true); ?>
              <li class="shop-cat-main<?= $open ? ' is-open' : '' ?><?= $mainOn ? ' is-current' : '' ?>">
                <div class="shop-cat-row">
                  <label class="shop-cat-check">
                    <input type="checkbox" name="cat[]" value="<?= e($main['slug']) ?>" <?= $mainOn ? 'checked' : '' ?> data-cat-main="<?= (int) $main['id'] ?>">
                    <span class="shop-cat-box" aria-hidden="true"></span><span class="shop-cat-name"><?= e($main['name']) ?></span>
                  </label>
                  <?php if ($main['children']): ?><button type="button" class="shop-cat-toggle" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="subcats-<?= (int) $main['id'] ?>" aria-label="<?= $open ? 'Hide' : 'Show' ?> <?= e($main['name']) ?> subcategories" data-subcat-toggle><svg width="14" height="14" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button><?php endif; ?>
                </div>
                <?php if ($main['children']): ?>
                <ul class="shop-cats-sub" id="subcats-<?= (int) $main['id'] ?>"<?= $open ? '' : ' hidden' ?>>
                  <?php foreach ($main['children'] as $sub): $subOn = $mainOn || in_array($sub['id'], $checkedIds, true); ?>
                  <li class="<?= $subOn && !$mainOn ? 'is-checked' : '' ?>">
                    <label class="shop-cat-check">
                      <input type="checkbox" name="cat[]" value="<?= e($sub['slug']) ?>" <?= $subOn ? 'checked' : '' ?> <?= $mainOn ? 'disabled' : '' ?> data-cat-sub="<?= (int) $main['id'] ?>">
                      <span class="shop-cat-box" aria-hidden="true"></span><span class="shop-cat-name"><?= e($sub['name']) ?></span>
                    </label>
                  </li>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
              </li>
              <?php endforeach; ?>
            </ul>
            <button type="submit" class="shop-price-apply shop-cat-apply" data-cat-apply>Show products</button>
          </form>
        </div>
      </aside>

      <div class="col-lg-9">
        <div class="shop-toolbar">
          <div>
            <h1 class="shop-h1 fw-semibold mb-1"><?= e($heading) ?></h1>
            <p class="body-text-3 text-main-2 mb-0"><?= $total ?> product<?= $total === 1 ? '' : 's' ?><?php if ($search !== '' && $catName !== ''): ?> in <?= e($catName) ?><?php endif; ?></p>
          </div>
          <button type="button" class="finder-open" data-bs-toggle="modal" data-bs-target="#productFinder">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/></svg>
            <span>Product finder</span>
          </button>
        </div>

        <?php if (count($cats) > 1): ?>
        <div class="finder-chips" aria-label="Selected categories">
          <?php foreach ($cats as $one): $rest = array_values(array_diff($catSlugs, [$one['slug']])); ?><a class="finder-chip" href="<?= e($link(['cat' => count($rest) === 1 ? $rest[0] : $rest, 'page' => null])) ?>" aria-label="Remove category <?= e($one['name']) ?>"><?= e($one['name']) ?> <span aria-hidden="true">×</span></a><?php endforeach; ?>
          <a class="finder-chip-clear" href="<?= e($link(['cat' => null, 'page' => null])) ?>">Clear categories</a>
        </div>
        <?php endif; ?>
        <?php $finderLabels = finder_labels($finderSel); if ($finderLabels): ?>
        <div class="finder-chips" aria-label="Product finder filters">
          <?php foreach ($finderLabels as [$fk, $fv, $label]): $rest = array_values(array_diff($finderParams[$fk], [$fv])); ?><a class="finder-chip" href="<?= e($link([$fk => $rest ?: null, 'page' => null])) ?>" aria-label="Remove filter <?= e($label) ?>"><?= e($label) ?> <span aria-hidden="true">×</span></a><?php endforeach; ?>
          <a class="finder-chip-clear" href="<?= e($link(array_fill_keys(array_keys($finderParams), null) + ['page' => null])) ?>">Clear all</a>
        </div>
        <?php endif; ?>

        <?php if (!$products): ?>
        <div class="co-card text-center py-5">
          <h5 class="fw-semibold mb-2">No products found</h5>
          <?php $filtered = $finderParams || $minPrice !== null || $maxPrice !== null; ?>
          <p class="body-text-3 text-main-2 mb-4"><?= $filtered ? 'No products match these filters. Try fewer choices or a wider amount range.' : ($search !== '' ? 'Try different words, or browse a category.' : 'There are no products in this category yet.') ?></p>
          <?php if ($filtered): ?>
          <a class="tf-btn d-inline-flex" href="<?= e($link(array_fill_keys(array_keys($finderParams), null) + ['min' => null, 'max' => null, 'page' => null])) ?>"><span class="text-white">Clear filters</span></a>
          <?php else: ?>
          <a class="tf-btn d-inline-flex" href="shop.php"><span class="text-white">See all products</span></a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="shop-grid">
          <?php foreach ($products as $p): ?>
          <?php partial('cards/product', ['p' => $p, 'class' => 'style-img-border', 'delay' => false]); ?>
          <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="shop-pages" aria-label="Pages">
          <?php if ($page > 1): ?><a href="<?= e($link(['page' => $page - 1])) ?>" aria-label="Previous page">‹</a><?php endif; ?>
          <?php for ($n = 1; $n <= $pages; $n++): ?>
          <a href="<?= e($link(['page' => $n])) ?>" class="<?= $n === $page ? 'active' : '' ?>" <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
          <?php endfor; ?>
          <?php if ($page < $pages): ?><a href="<?= e($link(['page' => $page + 1])) ?>" aria-label="Next page">›</a><?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php partial('product-finder', ['sel' => $finderSel, 'minPrice' => $minPrice, 'maxPrice' => $maxPrice, 'search' => $search]); ?>
<?php partial('page-end'); ?>
