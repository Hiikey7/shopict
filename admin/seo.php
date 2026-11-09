<?php
// SEO: how well each product is set up for Google (an internal score, not a Google ranking), bulk actions,
// category and brand page text, articles, the Google Shopping feed check and search-engine settings.
require __DIR__ . '/../includes/admin.php';
require_admin();

$tabs = ['products' => 'Products', 'categories' => 'Categories', 'brands' => 'Brands', 'guides' => 'Articles', 'feed' => 'Google Shopping', 'settings' => 'Settings'];
$tab = isset($tabs[$_GET['t'] ?? '']) ? $_GET['t'] : 'products';
$pdo = db();
$saveSetting = function (string $key, ?string $value) use ($pdo): void {
    $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')->execute([$key, $value]);
};

// ---------------------------------------------------------------- actions
if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['ids'] ?? []))));
    $back = 'seo.php?' . http_build_query(array_filter(['t' => $tab, 'show' => $_GET['show'] ?? null, 'q' => $_GET['q'] ?? null]));

    if (in_array($action, ['fill', 'reset', 'describe'], true)) {
        if (!$ids) {
            flash('Tick at least one product first.', 'error');
            redirect($back);
        }
        $done = 0;
        foreach ($ids as $pid) {
            $p = find_product($pid);
            if (!$p) {
                continue;
            }
            if ($action === 'fill') {
                // Store the automatic text in the fields that are still empty; written fields are never replaced.
                $faqs = seo_faqs_auto($p);
                $set = array_filter([
                    'seo_title'          => trim((string) $p['seo_title']) === '' ? seo_title_auto($p) : null,
                    'meta_description'   => trim((string) $p['meta_description']) === '' ? seo_meta_auto($p) : null,
                    'focus_keyword'      => trim((string) $p['focus_keyword']) === '' ? seo_focus_auto($p) : null,
                    'secondary_keywords' => trim((string) $p['secondary_keywords']) === '' ? mb_substr(implode(', ', seo_keywords_auto($p)), 0, 600) : null,
                    'faqs'               => trim((string) $p['faqs']) === '' && $faqs ? seo_faqs_text($faqs) : null,
                ]);
            } elseif ($action === 'reset') {
                $set = ['seo_title' => null, 'meta_description' => null, 'focus_keyword' => null, 'secondary_keywords' => null, 'faqs' => null];
            } else { // describe: a draft "About this item" for products that have none
                $set = trim((string) $p['description']) === '' ? ['description' => implode("\n", seo_description_draft($p))] : [];
            }
            if ($set || $action === 'reset') {
                $cols = array_keys($set);
                $pdo->prepare('UPDATE products SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?')->execute([...array_values($set), $pid]);
                $done += $set ? 1 : 0;
            }
        }
        flash(match ($action) {
            'fill'     => "Saved the automatic SEO text into the empty fields of $done product" . ($done === 1 ? '' : 's') . '. You can now edit it in each product.',
            'reset'    => 'SEO fields set back to automatic for ' . count($ids) . ' product' . (count($ids) === 1 ? '' : 's') . '.',
            'describe' => "Wrote a draft description from the specs for $done product" . ($done === 1 ? '' : 's') . ' that had none. Please read and improve each one.',
        });
        redirect($back);
    }

    if ($action === 'category') {
        $cid = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE product_categories SET seo_title = ?, meta_description = ?, intro = ?, guide = ? WHERE id = ?')
            ->execute([post_str('seo_title'), post_str('meta_description'), post_str('intro'), post_str('guide'), $cid]);
        flash('Category page text saved.');
        redirect('seo.php?t=categories#cat-' . $cid);
    }
    if ($action === 'brand') {
        $bid = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE brands SET seo_title = ?, meta_description = ?, intro = ? WHERE id = ?')
            ->execute([post_str('seo_title'), post_str('meta_description'), post_str('intro'), $bid]);
        flash('Brand page text saved.');
        redirect('seo.php?t=brands#brand-' . $bid);
    }
    if ($action === 'settings') {
        $ga = strtoupper(trim((string) post_str('seo_ga4_id')));
        if ($ga !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/', $ga)) {
            flash('The Google Analytics ID looks like G-XXXXXXXXXX. Nothing was saved.', 'error');
            redirect('seo.php?t=settings');
        }
        // Verification: accept the whole <meta …> tag or just the code inside content="…".
        $code = function (?string $v): ?string {
            $v = trim((string) $v);
            if (preg_match('/content=["\']([^"\']+)["\']/', $v, $m)) {
                $v = $m[1];
            }
            return $v !== '' ? mb_substr(preg_replace('/[^A-Za-z0-9_\-]/', '', $v), 0, 120) : null;
        };
        $saveSetting('seo_google_verification', $code(post_str('seo_google_verification')));
        $saveSetting('seo_bing_verification', $code(post_str('seo_bing_verification')));
        $saveSetting('seo_ga4_id', $ga !== '' ? $ga : null);
        $saveSetting('seo_ai_training', ($_POST['seo_ai_training'] ?? 'allow') === 'block' ? 'block' : 'allow');
        $saveSetting('seo_return_days', (string) max(0, min(365, (int) ($_POST['seo_return_days'] ?? 3))));
        flash('SEO settings saved.');
        redirect('seo.php?t=settings');
    }
}

// ---------------------------------------------------------------- product scores (also for the CSV export)
$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$imageCounts = [];
foreach ($pdo->query('SELECT product_id, COUNT(*) FROM product_images GROUP BY product_id')->fetchAll(PDO::FETCH_KEY_PAIR) as $pid => $n) {
    $imageCounts[(int) $pid] = (int) $n;
}
$dupes = seo_duplicate_maps(array_values(array_filter($products, fn ($p) => $p['is_active'])));
$rows = [];
foreach ($products as $p) {
    $gallery = $imageCounts[(int) $p['id']] ?? 0;
    $score = seo_product_score($p, $p['is_active'] ? $dupes : [], ($p['img_src'] ? 1 : 0) + $gallery); // main picture + gallery
    $rows[] = ['p' => $p, 'score' => $score['score'], 'fails' => array_values(array_filter($score['checks'], fn ($c) => !$c[1]))];
}

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="seo-report-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel reads the file as UTF-8
    fputcsv($out, ['ID', 'Product', 'Visible', 'Score', 'To improve', 'Search title', 'Search description', 'Main keyword', 'Link'], ',', '"', '');
    foreach ($rows as $r) {
        $seo = seo_product($r['p']);
        fputcsv($out, [$r['p']['id'], $r['p']['title'], $r['p']['is_active'] ? 'yes' : 'hidden', $r['score'], implode(' | ', array_column($r['fails'], 0)), $seo['title'], $seo['description'], $seo['focus'], seo_abs(product_url($r['p']))], ',', '"', '');
    }
    fclose($out);
    exit;
}

admin_header('SEO', 'seo');
$scoreClass = fn (int $s) => $s >= 80 ? 'badge-on' : ($s >= 55 ? 'badge-warn' : 'badge-off');
$tabUrl = fn (string $t) => 'seo.php' . ($t === 'products' ? '' : '?t=' . $t);
?>
<ul class="nav nav-pills order-tabs seo-tabs mb-3" role="tablist">
  <?php foreach ($tabs as $key => $label): ?><li class="nav-item"><a class="nav-link<?= $key === $tab ? ' active' : '' ?>" href="<?= $tabUrl($key) ?>"<?= $key === $tab ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li><?php endforeach; ?>
</ul>

<?php if ($tab === 'products'):
    $show = (string) ($_GET['show'] ?? '');
    $q = trim((string) ($_GET['q'] ?? ''));
    $active = array_values(array_filter($rows, fn ($r) => $r['p']['is_active']));
    $avg = $active ? (int) round(array_sum(array_column($active, 'score')) / count($active)) : 0;
    $needs = count(array_filter($active, fn ($r) => $r['score'] < 70));
    $hasFail = fn (array $r, string $label) => (bool) array_filter($r['fails'], fn ($c) => str_starts_with($c[0], $label));
    $filters = [
        ''        => ['All products', fn ($r) => true],
        'needs'   => ['Score under 70', fn ($r) => $r['p']['is_active'] && $r['score'] < 70],
        'dupes'   => ['Duplicates', fn ($r) => $hasFail($r, 'Search title unique') || $hasFail($r, 'Search description unique') || $hasFail($r, 'Description not copied')],
        'nobrand' => ['No brand', fn ($r) => $hasFail($r, 'Brand chosen')],
        'nospecs' => ['Few specs', fn ($r) => $hasFail($r, 'Specifications')],
        'thin'    => ['Short description', fn ($r) => $hasFail($r, 'Description has enough')],
        'hidden'  => ['Hidden', fn ($r) => !$r['p']['is_active']],
    ];
    $show = isset($filters[$show]) ? $show : '';
    $list = array_values(array_filter($rows, fn ($r) => $filters[$show][1]($r) && ($q === '' || mb_stripos($r['p']['title'], $q) !== false)));
?>
<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-3"><div class="card card-body h-100"><div class="small text-muted">Average score (visible products)</div><div class="fs-3 fw-semibold"><?= $avg ?><span class="fs-6 text-muted">/100</span></div></div></div>
  <div class="col-sm-6 col-lg-3"><div class="card card-body h-100"><div class="small text-muted">Products scoring under 70</div><div class="fs-3 fw-semibold"><?= $needs ?></div><a class="small" href="seo.php?show=needs">Show them →</a></div></div>
  <div class="col-lg-6"><div class="card card-body h-100 small">
    <div class="fw-semibold mb-1">For search engines</div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="../sitemap.xml" target="_blank" rel="noopener">Sitemap ↗</a>
      <a class="btn btn-sm btn-outline-secondary" href="../robots.txt" target="_blank" rel="noopener">robots.txt ↗</a>
      <a class="btn btn-sm btn-outline-secondary" href="../llms.txt" target="_blank" rel="noopener">llms.txt ↗</a>
      <a class="btn btn-sm btn-outline-secondary" href="../google-merchant-feed.xml" target="_blank" rel="noopener">Shopping feed ↗</a>
    </div>
    <div class="text-muted mt-2">The score is our own checklist to make each product easy for Google to understand. It is not a Google ranking.</div>
  </div></div>
</div>

<form class="order-filters card card-body mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-lg-5"><label class="form-label small mb-1" for="f-q">Search</label><input class="form-control" id="f-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Product name"></div>
    <div class="col-sm-8 col-lg-4"><label class="form-label small mb-1" for="f-show">Show</label>
      <select class="form-select" id="f-show" name="show"><?php foreach ($filters as $key => [$label]): ?><option value="<?= e($key) ?>" <?= $key === $show ? 'selected' : '' ?>><?= e($label) ?> (<?= count(array_filter($rows, $filters[$key][1])) ?>)</option><?php endforeach; ?></select></div>
    <div class="col-sm-4 col-lg-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button><a class="btn btn-outline-secondary" href="seo.php?export=csv" title="Download the SEO report for every product">Export CSV</a></div>
  </div>
</form>

<form method="post" data-seo-bulk>
  <?= csrf_field() ?>
  <div class="seo-bulk card card-body mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <label class="form-check mb-0 me-2"><input class="form-check-input" type="checkbox" data-check-all> <span class="small">Select all</span></label>
      <button class="btn btn-sm btn-outline-primary" name="action" value="fill" title="Copies the automatic title, description, keywords and questions into empty fields so you can edit them">Save automatic SEO text</button>
      <button class="btn btn-sm btn-outline-secondary" name="action" value="describe" data-confirm="Write a draft “About this item” from the specs for the ticked products that have no description? Products with a description are not changed.">Write missing descriptions</button>
      <button class="btn btn-sm btn-outline-danger" name="action" value="reset" data-confirm="Clear the saved SEO title, description, keywords and questions of the ticked products? They go back to automatic text. Product names and descriptions are not changed.">Reset SEO text to automatic</button>
      <span class="small text-muted ms-auto" data-selected-count>0 selected</span>
    </div>
  </div>
  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle seo-table">
        <thead><tr><th style="width:36px"></th><th>Product</th><th class="text-center">Score</th><th>To improve</th><th></th></tr></thead>
        <tbody>
          <?php if (!$list): ?><tr><td colspan="5" class="text-center text-muted py-5">No products here.</td></tr><?php endif; ?>
          <?php foreach ($list as $r): $p = $r['p']; ?>
          <tr class="row-link" data-href="product-edit.php?id=<?= (int) $p['id'] ?>#seo">
            <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>" aria-label="Select <?= e(short_title($p['title'], 40)) ?>" data-row-check></td>
            <td><div class="d-flex align-items-center gap-2"><?= thumb($p['img_src'], 40) ?><div class="min-w-0"><div class="fw-medium text-truncate" style="max-width:340px" title="<?= e($p['title']) ?>"><?= e(short_title($p['title'], 48)) ?></div><?php if (!$p['is_active']): ?><span class="badge badge-off">Hidden</span><?php endif; ?></div></div></td>
            <td class="text-center"><span class="badge <?= $scoreClass($r['score']) ?> seo-score"><?= $r['score'] ?></span></td>
            <td class="small text-muted"><?= $r['fails'] ? e(implode(' · ', array_slice(array_column($r['fails'], 0), 0, 3))) . (count($r['fails']) > 3 ? ' · +' . (count($r['fails']) - 3) : '') : '<span class="text-success">All checks pass</span>' ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="product-edit.php?id=<?= (int) $p['id'] ?>#seo">Edit</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<?php elseif ($tab === 'categories'): $guides = seo_category_guides(); ?>
<p class="section-note">Text for each category page: the title and description shown on Google, an introduction and buying advice shown under the products. Leave a field empty to use the automatic text (shown in grey). Formatting: <code>## Heading</code>, <code>- point</code>, <code>**bold**</code>, <code>[link](laptops)</code>.</p>
<?php foreach (category_tree(true) as $main): foreach ([$main, ...$main['children']] as $c):
    [$sql, $params] = products_in_categories_sql(category_ids_within($c));
    $n = $pdo->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1 AND $sql");
    $n->execute($params);
    $n = (int) $n->fetchColumn();
    $filled = trim((string) $c['seo_title'] . $c['meta_description'] . $c['intro'] . $c['guide']) !== '';
?>
<details class="card mb-2 card-collapse seo-item<?= $c['parent_id'] ? ' seo-item-sub' : '' ?>" id="cat-<?= (int) $c['id'] ?>">
  <summary class="card-header d-flex align-items-center gap-2"><span class="fw-semibold"><?= e($c['name']) ?></span><span class="small text-muted"><?= $n ?> product<?= $n === 1 ? '' : 's' ?></span><?php if ($n === 0): ?><span class="badge badge-soft">Not on Google while empty</span><?php endif; ?><?php if ($filled): ?><span class="badge badge-on">Custom text</span><?php endif; ?><a class="small ms-auto" href="../<?= e(category_url($c)) ?>" target="_blank" rel="noopener">View ↗</a></summary>
  <form class="card-body" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="category"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <div class="row g-3">
      <div class="col-lg-6"><label class="form-label">Google title</label><input class="form-control" name="seo_title" maxlength="120" value="<?= e($c['seo_title']) ?>" placeholder="<?= e('Buy ' . $c['name'] . ' in Kenya – Prices & Deals | ' . seo_store()) ?>" data-count="70"></div>
      <div class="col-lg-6"><label class="form-label">Google description</label><input class="form-control" name="meta_description" maxlength="320" value="<?= e($c['meta_description']) ?>" placeholder="Written from the products in this category" data-count="160"></div>
      <div class="col-12"><label class="form-label">Introduction</label><textarea class="form-control" name="intro" rows="3" placeholder="Written from the products: how many, prices from … to …, brands, pickup and delivery."><?= e($c['intro']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Buying advice</label><textarea class="form-control" name="guide" rows="<?= $c['parent_id'] ? 4 : 8 ?>" placeholder="<?= e($c['parent_id'] ? 'Optional advice for this type of product.' : ($guides[$c['slug']] ?? '')) ?>"><?= e($c['guide']) ?></textarea></div>
    </div>
    <button class="btn btn-primary btn-sm mt-3">Save</button>
  </form>
</details>
<?php endforeach; endforeach; ?>

<?php elseif ($tab === 'brands'): ?>
<p class="section-note">Each brand has a page, e.g. <code><?= e(seo_site_url()) ?>/hp</code>, listing its products (Brand field, or a name starting with the brand). Add brands in <a href="brands.php">Brands</a>. Please only call the shop an “authorised dealer” of a brand if the brand has confirmed it.</p>
<?php foreach (brands_public() as $b):
    [$sql, $params] = brand_products_sql($b);
    $n = $pdo->prepare("SELECT COUNT(*) FROM products WHERE is_active = 1 AND $sql");
    $n->execute($params);
    $n = (int) $n->fetchColumn();
    $row = $b['row'];
?>
<details class="card mb-2 card-collapse seo-item" id="brand-<?= (int) ($row['id'] ?? 0) ?>">
  <summary class="card-header d-flex align-items-center gap-2"><span class="fw-semibold"><?= e($b['name']) ?></span><span class="small text-muted"><?= $n ?> product<?= $n === 1 ? '' : 's' ?></span><?php if ($n === 0): ?><span class="badge badge-soft">Not on Google while empty</span><?php endif; ?><a class="small ms-auto" href="../<?= e(brand_url($b['name'])) ?>" target="_blank" rel="noopener">View ↗</a></summary>
  <?php if ($row): ?>
  <form class="card-body" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="brand"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
    <div class="row g-3">
      <div class="col-lg-6"><label class="form-label">Google title</label><input class="form-control" name="seo_title" maxlength="120" value="<?= e($row['seo_title']) ?>" placeholder="<?= e($b['name'] . ' in Kenya – … | ' . seo_store()) ?>" data-count="70"></div>
      <div class="col-lg-6"><label class="form-label">Google description</label><input class="form-control" name="meta_description" maxlength="320" value="<?= e($row['meta_description']) ?>" placeholder="Written from the brand’s products" data-count="160"></div>
      <div class="col-12"><label class="form-label">Introduction</label><textarea class="form-control" name="intro" rows="4" placeholder="E.g. which <?= e($b['name']) ?> ranges you stock (EliteBook, ProBook…), who they suit, and your warranty help."><?= e($row['intro']) ?></textarea></div>
    </div>
    <button class="btn btn-primary btn-sm mt-3">Save</button>
  </form>
  <?php else: ?>
  <div class="card-body small text-muted">This brand comes from a product’s Brand field. Add it in <a href="brands.php">Brands</a> to write its page text.</div>
  <?php endif; ?>
</details>
<?php endforeach; ?>

<?php elseif ($tab === 'guides'):
    $posts = $pdo->query('SELECT * FROM posts ORDER BY updated_at DESC')->fetchAll();
    $statusLabel = ['draft' => ['Draft', 'badge-soft'], 'review' => ['Ready for review', 'badge-warn'], 'published' => ['Published', 'badge-on']];
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <p class="section-note mb-0 me-auto">Helpful articles answer shoppers’ questions and link them to relevant products. Add a focus keyword and related phrases to guide your writing; only publish original, accurate articles. Each article goes Draft → Ready for review → Published.</p>
  <a class="btn btn-primary" href="post-edit.php">+ New article</a>
</div>
<div class="table-card"><div class="table-responsive"><table class="table table-hover align-middle">
  <thead><tr><th>Article</th><th>Status</th><th>Updated</th><th></th></tr></thead>
  <tbody>
    <?php if (!$posts): ?><tr><td colspan="4" class="text-center text-muted py-5">No articles yet.</td></tr><?php endif; ?>
    <?php foreach ($posts as $post): [$label, $class] = $statusLabel[$post['status']]; ?>
    <tr class="row-link" data-href="post-edit.php?id=<?= (int) $post['id'] ?>">
      <td class="fw-medium"><?= e($post['title']) ?></td>
      <td><span class="badge <?= $class ?>"><?= e($label) ?></span></td>
      <td class="small text-muted text-nowrap"><?= e(date('M j, Y', strtotime((string) $post['updated_at']))) ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="post-edit.php?id=<?= (int) $post['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div></div>

<?php elseif ($tab === 'feed'):
    $issues = [];
    foreach ($rows as $r) {
        $p = $r['p'];
        if (!$p['is_active'] || $p['noindex']) {
            continue;
        }
        $e = [];
        $w = [];
        if ((float) $p['price'] <= 0) {
            $e[] = 'No price';
        }
        if (!$p['img_src']) {
            $e[] = 'No picture';
        }
        if (mb_strlen($p['title']) > 150) {
            $e[] = 'Name longer than 150 characters (cut in the feed)';
        }
        if (!trim((string) $p['brand'])) {
            $w[] = 'No brand';
        }
        if (!trim((string) $p['gtin']) && !trim((string) $p['mpn'])) {
            $w[] = 'No GTIN or MPN (sent as “no identifier”)';
        }
        if (!empty($p['is_preorder'])) {
            $w[] = 'Pre-order: Google needs an availability date, set it in Merchant Center';
        }
        if (mb_strlen(seo_plain((string) $p['description'])) < 50) {
            $w[] = 'Very short description';
        }
        if ($e || $w) {
            $issues[] = [$p, $e, $w];
        }
    }
    $visible = count(array_filter($rows, fn ($r) => $r['p']['is_active'] && !$r['p']['noindex']));
?>
<div class="card card-body mb-3 small">
  <div class="fw-semibold mb-1">Google Merchant Center feed</div>
  <p class="mb-2">Address to add in Merchant Center → Products → Feeds → “Scheduled fetch” (daily): <code><?= e(seo_site_url()) ?>/google-merchant-feed.xml</code> · <a href="../google-merchant-feed.xml" target="_blank" rel="noopener">Open ↗</a></p>
  <p class="mb-2">It sends <?= $visible ?> visible products (one item per option for products with options), with the same name, price, stock, condition, pictures and links as the product pages, and updates itself whenever you change a product.</p>
  <p class="mb-0 text-muted">Delivery cost: <?= (float) setting('delivery_fee', '0') > 0 ? 'sent from Settings (' . money((float) setting('delivery_fee')) . ').' : 'not sent, because delivery is “confirmed by phone”. Set your delivery rates and the 3-day return policy in Merchant Center → Shipping and returns.' ?></p>
</div>
<div class="table-card"><div class="table-responsive"><table class="table align-middle">
  <thead><tr><th>Product</th><th>Errors (not shown on Google)</th><th>Warnings</th><th></th></tr></thead>
  <tbody>
    <?php if (!$issues): ?><tr><td colspan="4" class="text-center text-success py-5">No problems found.</td></tr><?php endif; ?>
    <?php foreach ($issues as [$p, $e, $w]): ?>
    <tr>
      <td><div class="d-flex align-items-center gap-2"><?= thumb($p['img_src'], 36) ?><span class="text-truncate" style="max-width:280px"><?= e(short_title($p['title'], 44)) ?></span></div></td>
      <td class="small text-danger"><?= e(implode(' · ', $e)) ?: '–' ?></td>
      <td class="small text-muted"><?= e(implode(' · ', $w)) ?: '–' ?></td>
      <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="product-edit.php?id=<?= (int) $p['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div></div>

<?php else: /* settings */ ?>
<form class="card mb-4" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="settings">
  <div class="card-header">Search engines</div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-lg-6">
        <label class="form-label" for="gsv">Google Search Console verification</label>
        <input class="form-control" id="gsv" name="seo_google_verification" value="<?= e(setting('seo_google_verification', '')) ?>" placeholder='Paste the <meta name="google-site-verification" …> tag or its code'>
        <div class="form-text">Search Console → Add property → URL prefix <code><?= e(seo_site_url()) ?></code> → HTML tag. After verifying, submit the sitemap: <code>sitemap.xml</code>.</div>
      </div>
      <div class="col-lg-6">
        <label class="form-label" for="bsv">Bing Webmaster Tools verification</label>
        <input class="form-control" id="bsv" name="seo_bing_verification" value="<?= e(setting('seo_bing_verification', '')) ?>" placeholder='Paste the <meta name="msvalidate.01" …> tag or its code'>
        <div class="form-text">Bing can also import the site straight from Search Console. Bing powers ChatGPT search results too.</div>
      </div>
      <div class="col-lg-6">
        <label class="form-label" for="ga4">Google Analytics 4 measurement ID</label>
        <input class="form-control" id="ga4" name="seo_ga4_id" value="<?= e(setting('seo_ga4_id', '')) ?>" placeholder="G-XXXXXXXXXX" style="max-width:240px">
        <div class="form-text">Leave empty to not load Google Analytics. The shop’s own analytics keep working either way.</div>
      </div>
      <div class="col-lg-6">
        <label class="form-label" for="rdays">Return period (days)</label>
        <input class="form-control" id="rdays" type="number" min="0" max="365" name="seo_return_days" value="<?= e(setting('seo_return_days', '3')) ?>" style="max-width:120px">
        <div class="form-text">Sent to Google with every product. Keep it the same as Terms → Returns &amp; exchanges.</div>
      </div>
      <div class="col-12">
        <span class="form-label d-block">AI assistants</span>
        <div class="small text-muted mb-2">AI search assistants (ChatGPT search, Perplexity, Claude, Copilot) are always allowed: they read pages to answer shoppers and link to the shop. This choice is only about crawlers that collect pages to <em>train</em> AI models.</div>
        <div class="form-check"><input class="form-check-input" type="radio" name="seo_ai_training" id="ait1" value="allow" <?= setting('seo_ai_training', 'allow') !== 'block' ? 'checked' : '' ?>><label class="form-check-label" for="ait1">Allow AI training crawlers (GPTBot, ClaudeBot, Google-Extended…)</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="seo_ai_training" id="ait2" value="block" <?= setting('seo_ai_training', 'allow') === 'block' ? 'checked' : '' ?>><label class="form-check-label" for="ait2">Block AI training crawlers</label></div>
      </div>
    </div>
    <button class="btn btn-primary mt-3">Save settings</button>
  </div>
</form>
<?php endif; ?>
<?php admin_footer(); ?>
