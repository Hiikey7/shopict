<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$catParam = (string) ($_GET['cat'] ?? ''); // category id, or "none" (links from Product categories)
$catFilter = $catParam === 'none' ? null : category_find((int) $catParam);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

// Product finder pop-up: the same one as on the shop page (item, brand, specs, amount range).
$finderConfig = array_filter(finder_config(), fn ($slug) => category_by_slug($slug) !== null, ARRAY_FILTER_USE_KEY);
$itemSlug = (string) ($_GET['item'] ?? '');
$itemCat = isset($finderConfig[$itemSlug]) ? category_by_slug($itemSlug) : null;
$finderSel = finder_selection($itemCat);
$finderParams = finder_params($finderSel);
$num = fn (string $k) => ($v = preg_replace('/\D+/', '', (string) ($_GET[$k] ?? ''))) === '' ? null : (int) $v;
$minPrice = $num('min');
$maxPrice = $num('max');
if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(p.title LIKE ? OR p.sku LIKE ? OR p.brand LIKE ? OR p.id = ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = ctype_digit($q) ? (int) $q : 0;
}
if ($catParam === 'none') {
    $where[] = 'p.category_id IS NULL';
} elseif ($catFilter) {
    [$catSql, $catParams] = products_in_categories_sql(category_ids_within($catFilter), 'p');
    $where[] = $catSql;
    array_push($params, ...$catParams);
}
if ($itemCat) {
    [$catSql, $catParams] = products_in_categories_sql(category_ids_within($itemCat), 'p');
    $where[] = $catSql;
    array_push($params, ...$catParams);
}
[$finderWhere, $finderArgs] = finder_conditions($finderSel, 'p');
array_push($where, ...$finderWhere);
array_push($params, ...$finderArgs);
if ($minPrice !== null) {
    $where[] = 'p.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = 'p.price <= ?';
    $params[] = $maxPrice;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare("SELECT COUNT(*) FROM products p $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = db()->prepare(
    "SELECT p.*
       FROM products p
       $whereSql
      ORDER BY p.id DESC
      LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$stmt->execute($params);
$products = $stmt->fetchAll();
$catValue = $catParam === 'none' ? 'none' : ($catFilter ? (string) $catFilter['id'] : '');
$state = ['q' => $q, 'cat' => $catValue, 'item' => $itemCat ? $itemSlug : '', 'min' => $minPrice, 'max' => $maxPrice] + $finderParams;
$link = fn (array $over) => 'products.php?' . http_build_query(array_filter(array_merge($state, $over), fn ($v) => $v !== null && $v !== '' && $v !== []));
$pageUrl = fn (int $n) => $link(['page' => $n > 1 ? $n : null]);
// Chips for the active choices (each removable).
$chips = [];
if ($catFilter || $catValue === 'none') {
    $chips[] = [$catValue === 'none' ? 'No category' : $catFilter['name'], $link(['cat' => null])];
}
if ($itemCat) {
    $chips[] = [$finderConfig[$itemSlug]['label'] . 's', $link(['item' => null] + array_fill_keys(array_keys($finderSel['specs']), null))];
}
foreach (finder_labels($finderSel) as [$fk, $fv, $label]) {
    $rest = array_values(array_diff($finderParams[$fk], [$fv]));
    $chips[] = [$label, $link([$fk => $rest ?: null])];
}
if ($minPrice !== null || $maxPrice !== null) {
    $chips[] = [($minPrice !== null ? money($minPrice) : 'Any') . ' – ' . ($maxPrice !== null ? money($maxPrice) : 'any'), $link(['min' => null, 'max' => null])];
}
$finderCount = count($chips);
$currentItem = $itemCat ? $itemSlug : array_key_first($finderConfig);
$icon = fn (string $paths, int $size = 20) => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';

admin_header('Products', 'products');
?>
<div class="d-flex justify-content-end mb-3">
  <a class="btn btn-outline-primary me-2" href="product-import.php">Bulk import</a>
  <a class="btn btn-primary" href="product-edit.php">+ Add product</a>
</div>

<form class="product-filters card card-body mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-md-8 col-lg-9">
      <label class="form-label small mb-1" for="p-q">Search</label>
      <input class="form-control" id="p-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, SKU, brand or ID">
    </div>
    <?php foreach (['cat' => $catValue, 'item' => $state['item'], 'min' => $minPrice, 'max' => $maxPrice] as $hk => $hv): if ($hv !== null && $hv !== ''): ?><input type="hidden" name="<?= $hk ?>" value="<?= e((string) $hv) ?>"><?php endif; endforeach; ?>
    <?php foreach ($finderParams as $fk => $fvs): foreach ($fvs as $fv): ?><input type="hidden" name="<?= e($fk) ?>[]" value="<?= e($fv) ?>"><?php endforeach; endforeach; ?>
    <div class="col-md-4 col-lg-3 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1">Search</button>
      <button class="btn btn-outline-secondary finder-btn" type="button" data-bs-toggle="modal" data-bs-target="#productFinder" aria-label="Product finder<?= $finderCount ? ' (' . $finderCount . ' active)' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/></svg>
        <span class="d-none d-sm-inline">Finder</span><?php if ($finderCount): ?><span class="finder-btn-count"><?= $finderCount ?></span><?php endif; ?>
      </button>
    </div>
  </div>
  <?php if ($chips || $q !== ''): ?>
  <div class="finder-chips mt-3">
    <span class="small text-muted me-1"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></span>
    <?php foreach ($chips as [$label, $href]): ?><a class="finder-chip" href="<?= e($href) ?>" aria-label="Remove <?= e($label) ?>"><?= e($label) ?> <span aria-hidden="true">×</span></a><?php endforeach; ?>
    <a class="finder-clear" href="products.php">Clear all</a>
  </div>
  <?php endif; ?>
</form>

<?php if ($finderConfig): ?>
<div class="modal fade sf-finder-modal" id="productFinder" tabindex="-1" aria-labelledby="productFinderTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content sf-finder" method="get" action="products.php" data-finder>
      <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
      <div class="sf-finder-head">
        <span class="sf-finder-badge"><?= $icon('<rect x="5" y="8" width="14" height="11" rx="2.5"/><path d="M12 4v4M9 13h.01M15 13h.01M9.5 16.5h5M3 12v3M21 12v3"/>', 22) ?></span>
        <div>
          <h2 class="sf-finder-title" id="productFinderTitle">Product finder</h2>
          <p class="sf-finder-sub">Choose what you need and search.</p>
        </div>
        <button type="button" class="sf-finder-close" data-bs-dismiss="modal" aria-label="Close"><?= $icon('<path d="M6 6l12 12M18 6 6 18"/>', 18) ?></button>
      </div>

      <fieldset class="sf-finder-block">
        <legend class="sf-finder-label">Item</legend>
        <div class="sf-finder-items">
          <?php foreach ($finderConfig as $slug => $item): ?>
          <label class="sf-finder-item">
            <input type="radio" name="item" value="<?= e($slug) ?>" <?= $slug === $currentItem ? 'checked' : '' ?> data-finder-item>
            <span><?= $icon($item['icon'], 20) ?><?= e($item['label']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <?php foreach ($finderConfig as $slug => $item): $on = $slug === $currentItem; ?>
      <div class="sf-finder-group" data-finder-group="<?= e($slug) ?>"<?= $on ? '' : ' hidden' ?>>
        <fieldset class="sf-finder-block"<?= $on ? '' : ' disabled' ?>>
          <legend class="sf-finder-spec-head"><span class="sf-finder-label">Brand</span><button type="button" class="sf-finder-any" data-finder-any="brand">Any</button></legend>
          <div class="sf-finder-chips-row">
            <?php foreach ($item['brands'] as $brand): ?>
            <label class="sf-finder-chip-opt"><input type="checkbox" name="brand[]" value="<?= e($brand) ?>" <?= $on && in_array($brand, $finderSel['brand'], true) ? 'checked' : '' ?>><span><?= e($brand) ?></span></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <div class="sf-finder-block">
          <p class="sf-finder-label">Specs</p>
          <?php foreach ($item['specs'] as $key => $group): ?>
          <fieldset class="sf-finder-spec"<?= $on ? '' : ' disabled' ?>>
            <legend class="sf-finder-spec-head"><span class="sf-finder-sublabel"><?= e($group['label']) ?></span><button type="button" class="sf-finder-any" data-finder-any="<?= e($key) ?>">Any</button></legend>
            <div class="sf-finder-chips-row">
              <?php foreach ($group['options'] as $value => $label): ?>
              <label class="sf-finder-chip-opt"><input type="checkbox" name="<?= e($key) ?>[]" value="<?= e($value) ?>" <?= $on && in_array((string) $value, $finderSel['specs'][$key] ?? [], true) ? 'checked' : '' ?>><span><?= e($label) ?></span></label>
              <?php endforeach; ?>
            </div>
          </fieldset>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <fieldset class="sf-finder-block">
        <legend class="sf-finder-label">Amount range</legend>
        <div class="sf-finder-range">
          <input type="number" name="min" min="0" step="1" inputmode="numeric" placeholder="Min" aria-label="Minimum amount in KSh" value="<?= $minPrice ?? '' ?>">
          <input type="number" name="max" min="0" step="1" inputmode="numeric" placeholder="Max" aria-label="Maximum amount in KSh" value="<?= $maxPrice ?? '' ?>">
        </div>
      </fieldset>

      <button type="submit" class="sf-finder-search"><?= $icon('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>', 20) ?>Search</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 product-list">
      <thead>
        <tr><th style="width:64px"></th><th>Product</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$products): ?>
        <tr><td colspan="3" class="text-center text-muted py-5">No products found.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
        <tr class="row-link" data-href="product-edit.php?id=<?= (int) $p['id'] ?>">
          <td><?= thumb($p['img_src'], 48) ?></td>
          <td>
            <a class="fw-medium text-decoration-none link-dark" href="product-edit.php?id=<?= (int) $p['id'] ?>" title="<?= e($p['title']) ?>"><?= e(short_title($p['title'], 40)) ?></a>
            <?php if (!$p['is_active']): ?><span class="badge badge-off ms-1">Hidden</span><?php endif; ?><?php if (!empty($p['is_preorder'])): ?><span class="badge badge-preorder ms-1">Pre-order</span><?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-secondary" href="product-edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
            <form class="d-inline" method="post" action="product-edit.php?id=<?= (int) $p['id'] ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="delete" data-confirm="Delete <?= e($p['title']) ?>? It will also be removed from every section, cart and wishlist.">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
  <span class="text-muted small"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></span>
  <?php if ($pages > 1): ?>
  <nav><ul class="pagination pagination-sm mb-0">
    <?php for ($n = 1; $n <= $pages; $n++): ?>
    <li class="page-item <?= $n === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($pageUrl($n)) ?>"><?= $n ?></a></li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
