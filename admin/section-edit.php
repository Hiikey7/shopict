<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$code = (string) ($_GET['code'] ?? '');
$section = section($code);
if (!$section) {
    flash('Unknown section.', 'error');
    redirect('sections.php');
}
$takesProducts = section_takes_products($section);
$limit = section_limit($section);

if (is_post()) {
    verify_csrf();
    $title = post_str('title');
    if ($title === null) {
        flash('The section title cannot be empty.', 'error');
        redirect('section-edit.php?code=' . urlencode($code));
    }
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE sections SET title = ?, is_active = ? WHERE code = ?')
        ->execute([mb_substr($title, 0, 150), empty($_POST['is_active']) ? 0 : 1, $code]);

    $note = '';
    if ($takesProducts) {
        // Replace the product list with the submitted one, in the submitted order.
        $ids = array_map('intval', (array) ($_POST['product_id'] ?? []));
        $animations = (array) ($_POST['animation'] ?? []);
        $delays = (array) ($_POST['wow_delay'] ?? []);
        if ($limit !== null && count($ids) > $limit) {
            $ids = array_slice($ids, 0, $limit);
            $note = ' Only the first ' . $limit . ' products were kept (this section holds ' . $limit . ').';
        }
        $valid = $ids ? $pdo->query('SELECT id FROM products WHERE id IN (' . implode(',', $ids) . ')')->fetchAll(PDO::FETCH_COLUMN) : [];
        $valid = array_map('intval', $valid);

        $pdo->prepare('DELETE FROM section_products WHERE section = ?')->execute([$code]);
        $insert = $pdo->prepare('INSERT INTO section_products (section, position, product_id, animation, wow_delay) VALUES (?, ?, ?, ?, ?)');
        $position = 0;
        foreach ($ids as $i => $productId) {
            if (!in_array($productId, $valid, true)) {
                continue;
            }
            $animation = in_array($animations[$i] ?? '', ANIMATIONS, true) ? ($animations[$i] ?: null) : null;
            $delay = preg_match('/^\d+(\.\d+)?s$/', trim((string) ($delays[$i] ?? ''))) ? trim($delays[$i]) : null;
            $insert->execute([$code, ++$position, $productId, $animation, $delay]);
        }
    }
    $pdo->commit();
    flash('Section saved.' . $note, $note ? 'error' : 'success');
    redirect('section-edit.php?code=' . urlencode($code));
}

$items = $takesProducts ? section_products($code) : [];
$all = db()->query('SELECT id, title, category, price, img_src FROM products ORDER BY title')->fetchAll();
$picker = array_map(fn ($p) => [
    'id'       => (int) $p['id'],
    'title'    => $p['title'],
    'category' => (string) $p['category'],
    'price'    => money($p['price']),
    'img'      => admin_asset($p['img_src']),
], $all);

admin_header('Edit section', 'sections');
?>
<a href="sections.php" class="small text-decoration-none">← All sections</a>
<form method="post" class="mt-3" data-section-editor data-limit="<?= $limit ?? '' ?>">
  <?= csrf_field() ?>
  <div class="card mb-4">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-7">
          <label class="form-label" for="title">Title</label>
          <input class="form-control" id="title" name="title" value="<?= e($section['title']) ?>" maxlength="150" required>
          <?php if ($code === 'hot_featured'): ?><div class="form-text">Not shown on the page; only used in the admin.</div><?php endif; ?>
        </div>
        <div class="col-md-5">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= $section['is_active'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Show this section on the home page</label>
          </div>
        </div>
      </div>
      <p class="section-note mt-3 mb-0"><?= e($section['note']) ?></p>
    </div>
  </div>

  <?php if ($takesProducts): ?>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between">
          <span>Products <span class="text-muted fw-normal" data-count><?= count($items) ?></span><?= $limit ? '<span class="text-muted fw-normal"> / ' . $limit . '</span>' : '' ?></span>
          <span class="small text-muted fw-normal">Displayed in this order</span>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th style="width:40px">#</th><th>Product</th><th style="width:150px">Animation</th><th style="width:90px">Delay</th><th style="width:130px"></th></tr></thead>
            <tbody data-rows>
              <?php foreach ($items as $p): ?>
              <tr class="sortable-row">
                <td class="text-muted" data-pos></td>
                <td>
                  <input type="hidden" name="product_id[]" value="<?= (int) $p['id'] ?>">
                  <div class="d-flex align-items-center gap-2">
                    <?= thumb($p['img_src'], 40) ?>
                    <div class="min-w-0">
                      <a class="text-decoration-none link-dark text-truncate-2 small fw-medium" href="product-edit.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a>
                      <div class="small text-muted"><?= money($p['price']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <select class="form-select form-select-sm" name="animation[]">
                    <?php foreach (ANIMATIONS as $a): ?><option value="<?= $a ?>" <?= ($p['animation'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?: 'None' ?></option><?php endforeach; ?>
                  </select>
                </td>
                <td><input class="form-control form-control-sm" name="wow_delay[]" value="<?= e($p['wow_delay']) ?>" placeholder="0s"></td>
                <td class="text-end handle-btns">
                  <button type="button" class="btn btn-sm btn-light" data-move="-1" title="Move up">↑</button>
                  <button type="button" class="btn btn-sm btn-light" data-move="1" title="Move down">↓</button>
                  <button type="button" class="btn btn-sm btn-light text-danger" data-remove title="Remove">✕</button>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr data-empty class="<?= $items ? 'd-none' : '' ?>"><td colspan="5" class="text-center text-muted py-4">No products yet. Add some from the list.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card">
        <div class="card-header">Add products</div>
        <div class="card-body">
          <input class="form-control mb-2" type="search" placeholder="Search products…" data-picker-search>
          <div class="picker-list" data-picker></div>
          <div class="form-text">Products already in this section are greyed out. <a href="product-edit.php">Create a new product</a></div>
        </div>
      </div>
    </div>
  </div>
  <template data-row-template>
    <tr class="sortable-row flash">
      <td class="text-muted" data-pos></td>
      <td>
        <input type="hidden" name="product_id[]">
        <div class="d-flex align-items-center gap-2">
          <img class="thumb" width="40" height="40" alt="">
          <div class="min-w-0"><span class="text-truncate-2 small fw-medium" data-title></span><div class="small text-muted" data-price></div></div>
        </div>
      </td>
      <td><select class="form-select form-select-sm" name="animation[]"><?php foreach (ANIMATIONS as $a): ?><option value="<?= $a ?>"><?= $a ?: 'None' ?></option><?php endforeach; ?></select></td>
      <td><input class="form-control form-control-sm" name="wow_delay[]" placeholder="0s"></td>
      <td class="text-end handle-btns">
        <button type="button" class="btn btn-sm btn-light" data-move="-1" title="Move up">↑</button>
        <button type="button" class="btn btn-sm btn-light" data-move="1" title="Move down">↓</button>
        <button type="button" class="btn btn-sm btn-light text-danger" data-remove title="Remove">✕</button>
      </td>
    </tr>
  </template>
  <script type="application/json" data-picker-data><?= json_encode($picker, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
  <?php elseif ($code === 'categories'): ?>
  <p>The items in this section are managed on the <a href="categories.php">Categories</a> page.</p>
  <?php endif; ?>

  <div class="sticky-actions d-flex gap-2">
    <button class="btn btn-primary px-4" type="submit">Save section</button>
    <a class="btn btn-outline-secondary" href="sections.php">Cancel</a>
    <a class="btn btn-link ms-auto" href="../" target="_blank" rel="noopener">View store ↗</a>
  </div>
</form>
<?php admin_footer(); ?>
