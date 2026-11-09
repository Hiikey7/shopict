<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

/** A slug that no other category uses. */
function unique_category_slug(string $name, int $ignoreId = 0): string
{
    $base = slugify($name);
    $slug = $base;
    $check = db()->prepare('SELECT COUNT(*) FROM product_categories WHERE slug = ? AND id <> ?');
    for ($n = 2; ; $n++) {
        $check->execute([$slug, $ignoreId]);
        if (!(int) $check->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
    }
}

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();
    try {
        switch ($action) {
            case 'create':
                $name = post_str('name');
                if ($name === null) {
                    throw new RuntimeException('Enter a category name.');
                }
                $parent = (int) ($_POST['parent_id'] ?? 0) ?: null;
                if ($parent !== null && !category_find($parent)) {
                    throw new RuntimeException('That main category no longer exists.');
                }
                $stmt = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM product_categories WHERE parent_id ' . ($parent ? '= ?' : 'IS NULL'));
                $stmt->execute($parent ? [$parent] : []);
                $pdo->prepare('INSERT INTO product_categories (parent_id, name, slug, position) VALUES (?, ?, ?, ?)')
                    ->execute([$parent, mb_substr($name, 0, 100), unique_category_slug($name), (int) $stmt->fetchColumn()]);
                flash($parent ? 'Subcategory added.' : 'Main category added.');
                break;
            case 'update':
                $name = post_str('name');
                if ($name === null) {
                    throw new RuntimeException('The category name cannot be empty.');
                }
                $pdo->prepare('UPDATE product_categories SET name = ?, is_active = ? WHERE id = ?')
                    ->execute([mb_substr($name, 0, 100), empty($_POST['is_active']) ? 0 : 1, $id]);
                // Products show the category name, so keep it in step.
                $pdo->prepare('UPDATE products SET category = ? WHERE category_id = ?')->execute([mb_substr($name, 0, 100), $id]);
                flash('Category saved.');
                break;
            case 'delete':
                $cat = category_find($id);
                if ($cat) {
                    $ids = category_ids_within($cat);
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ($in)");
                    $stmt->execute($ids);
                    $used = (int) $stmt->fetchColumn();
                    $pdo->prepare('DELETE FROM product_categories WHERE id = ?')->execute([$id]);
                    flash('Category deleted.' . ($used ? " $used product" . ($used === 1 ? ' is' : 's are') . ' now without a category; give them a new one in Products.' : ''), $used ? 'error' : 'success');
                }
                break;
            case 'up':
            case 'down':
                $cat = category_find($id);
                if ($cat) {
                    $stmt = $pdo->prepare('SELECT id FROM product_categories WHERE parent_id ' . ($cat['parent_id'] ? '= ?' : 'IS NULL') . ' ORDER BY position, id');
                    $stmt->execute($cat['parent_id'] ? [$cat['parent_id']] : []);
                    $order = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                    $i = array_search($id, $order, true);
                    $j = $action === 'up' ? $i - 1 : $i + 1;
                    if ($i !== false && isset($order[$j])) {
                        [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
                        $update = $pdo->prepare('UPDATE product_categories SET position = ? WHERE id = ?');
                        foreach ($order as $pos => $cid) {
                            $update->execute([$pos + 1, $cid]);
                        }
                    }
                }
                break;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('product-categories.php');
}

$tree = category_tree(true);
$counts = db()->query('SELECT category_id, COUNT(*) FROM products WHERE category_id IS NOT NULL GROUP BY category_id')->fetchAll(PDO::FETCH_KEY_PAIR);
$unassigned = (int) db()->query('SELECT COUNT(*) FROM products WHERE category_id IS NULL')->fetchColumn();

/** One editable row (main or sub). */
$row = function (array $c, bool $first, bool $last, int $products, bool $isSub) {
    $formId = 'pc-' . $c['id'];
    ob_start(); ?>
    <tr class="<?= $isSub ? 'pc-sub' : 'pc-main' ?>">
      <td>
        <form id="<?= $formId ?>" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"></form>
        <div class="d-flex align-items-center gap-2">
          <?php if ($isSub): ?><span class="text-muted" aria-hidden="true">└</span><?php endif; ?>
          <input class="form-control form-control-sm<?= $isSub ? '' : ' fw-semibold' ?>" name="name" form="<?= $formId ?>" value="<?= e($c['name']) ?>" maxlength="100" required aria-label="Name">
        </div>
      </td>
      <td class="small text-muted text-nowrap"><a href="../<?= e(category_url($c)) ?>" target="_blank" rel="noopener"><?= e($c['slug']) ?></a></td>
      <td class="text-end small"><a class="text-decoration-none" href="products.php?cat=<?= (int) $c['id'] ?>"><?= $products ?></a></td>
      <td><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" form="<?= $formId ?>" <?= $c['is_active'] ? 'checked' : '' ?> aria-label="Visible"></div></td>
      <td class="text-end text-nowrap">
        <button class="btn btn-sm btn-light" form="<?= $formId ?>" name="action" value="up" formnovalidate <?= $first ? 'disabled' : '' ?> title="Move up">↑</button>
        <button class="btn btn-sm btn-light" form="<?= $formId ?>" name="action" value="down" formnovalidate <?= $last ? 'disabled' : '' ?> title="Move down">↓</button>
        <button class="btn btn-sm btn-outline-secondary" form="<?= $formId ?>" name="action" value="update">Save</button>
        <button class="btn btn-sm btn-outline-danger" form="<?= $formId ?>" name="action" value="delete" formnovalidate data-confirm="<?= $isSub ? 'Delete this subcategory?' : 'Delete this main category and all its subcategories?' ?> Its products keep their name label but leave the category.">Delete</button>
      </td>
    </tr>
    <?php return (string) ob_get_clean();
};

admin_header('Product categories', 'product-categories');
?>
<p class="text-muted">
  The main categories appear in the red menu bar on the shop (with their subcategories in the drop-down), in the phone menu, the footer and the shop page.
  Products are given a category in <a href="products.php">Products</a>.
  <?php if ($unassigned): ?><br><span class="text-danger"><?= $unassigned ?> product<?= $unassigned === 1 ? ' has' : 's have' ?> no category yet</span> — <a href="products.php?cat=none">show them</a>.<?php endif; ?>
</p>

<div class="card mb-4">
  <div class="card-header">Add a category</div>
  <div class="card-body">
    <form method="post" class="row g-3 align-items-end">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="col-md-5"><label class="form-label" for="new_name">Name</label><input class="form-control" id="new_name" name="name" required maxlength="100" placeholder="e.g. Refurbished Laptops"></div>
      <div class="col-md-5">
        <label class="form-label" for="new_parent">Inside</label>
        <select class="form-select" id="new_parent" name="parent_id">
          <option value="">— It is a main category —</option>
          <?php foreach ($tree as $main): ?><option value="<?= (int) $main['id'] ?>"><?= e($main['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
    </form>
  </div>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Name</th><th>Link</th><th class="text-end">Products</th><th style="width:80px">Visible</th><th style="width:250px"></th></tr></thead>
      <tbody>
        <?php foreach ($tree as $mi => $main):
            $total = (int) ($counts[$main['id']] ?? 0);
            foreach ($main['children'] as $sub) {
                $total += (int) ($counts[$sub['id']] ?? 0);
            }
        ?>
        <?= $row($main, $mi === 0, $mi === count($tree) - 1, $total, false) ?>
        <?php foreach ($main['children'] as $si => $sub): ?>
        <?= $row($sub, $si === 0, $si === count($main['children']) - 1, (int) ($counts[$sub['id']] ?? 0), true) ?>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$tree): ?><tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer(); ?>
