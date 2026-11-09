<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();
    // Product category the tile opens on the shop page (main categories only).
    $linkTo = function () use ($pdo): ?int {
        $cid = (int) ($_POST['category_id'] ?? 0);
        $ok = $pdo->prepare('SELECT id FROM product_categories WHERE id = ? AND parent_id IS NULL');
        $ok->execute([$cid]);
        return $ok->fetchColumn() ? $cid : null;
    };
    try {
        switch ($action) {
            case 'create':
                $title = post_str('title');
                $img = upload_image('image');
                $link = $linkTo();
                if ($title === null || !$img) {
                    throw new RuntimeException('Enter a name and choose an image for the new category.');
                }
                $pos = (int) $pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM categories')->fetchColumn();
                $pdo->prepare('INSERT INTO categories (title, img_src, category_id, position) VALUES (?, ?, ?, ?)')->execute([$title, $img['path'], $link, $pos]);
                flash('Category added.');
                break;
            case 'update':
                $title = post_str('title');
                if ($title === null) {
                    throw new RuntimeException('The category name cannot be empty.');
                }
                $img = upload_image('image');
                $pdo->prepare('UPDATE categories SET title = ?, category_id = ?' . ($img ? ', img_src = ?' : '') . ' WHERE id = ?')
                    ->execute($img ? [$title, $linkTo(), $img['path'], $id] : [$title, $linkTo(), $id]);
                flash('Category saved.');
                break;
            case 'delete':
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                flash('Category deleted.');
                break;
            case 'up':
            case 'down':
                $order = array_map('intval', $pdo->query('SELECT id FROM categories ORDER BY position, id')->fetchAll(PDO::FETCH_COLUMN));
                $i = array_search($id, $order, true);
                $j = $action === 'up' ? $i - 1 : $i + 1;
                if ($i !== false && isset($order[$j])) {
                    [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
                    $update = $pdo->prepare('UPDATE categories SET position = ? WHERE id = ?');
                    foreach ($order as $pos => $cid) {
                        $update->execute([$pos + 1, $cid]);
                    }
                }
                break;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('categories.php');
}

$categories = db()->query('SELECT * FROM categories ORDER BY position, id')->fetchAll();
$section = section('categories');
$mains = db()->query('SELECT id, name FROM product_categories WHERE parent_id IS NULL ORDER BY position, id')->fetchAll();
$linkSelect = function (?int $current, string $form = '') use ($mains): string {
    $h = '<select class="form-select form-select-sm" name="category_id"' . ($form !== '' ? ' form="' . e($form) . '"' : '') . ' aria-label="Opens category"><option value="">All products</option>';
    foreach ($mains as $m) {
        $h .= '<option value="' . (int) $m['id'] . '"' . ((int) $m['id'] === $current ? ' selected' : '') . '>' . e($m['name']) . '</option>';
    }
    return $h . '</select>';
};

admin_header('Homepage categories', 'categories');
?>
<p class="text-muted">
  Manage the images, names, order and links for the category tiles shown below the homepage hero in the
  "<?= e($section['title'] ?? 'Product Category') ?>" section<?= $section && !$section['is_active'] ? ' (the section is currently <strong>hidden</strong>)' : '' ?>.
  Rename or hide that section in <a href="section-edit.php?code=categories">Sections</a>.
</p>

<div class="card mb-4">
  <div class="card-header">Add a category</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" name="title" required maxlength="100"></div>
      <div class="col-md-3"><label class="form-label">Opens category</label>
<?= str_replace('form-select-sm', '', $linkSelect(null)) ?></div>
      <div class="col-md-4"><label class="form-label">Image</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required><div class="form-text">Square, 296 × 296 px or larger.</div></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
    </form>
  </div>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th style="width:72px">Image</th><th>Name</th><th style="width:180px">Opens category</th><th style="width:240px">Replace image</th><th style="width:260px"></th></tr></thead>
      <tbody>
        <?php foreach ($categories as $i => $c): $formId = 'cat-' . (int) $c['id']; ?>
        <tr>
          <td><?= thumb($c['img_src'], 52) ?></td>
          <td>
            <form id="<?= $formId ?>" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"></form>
            <textarea class="form-control form-control-sm" name="title" rows="2" form="<?= $formId ?>" maxlength="100" required><?= e($c['title']) ?></textarea>
          </td>
          <td><?= $linkSelect($c['category_id'] !== null ? (int) $c['category_id'] : null, $formId) ?></td>
          <td><input class="form-control form-control-sm" type="file" name="image" form="<?= $formId ?>" accept="image/jpeg,image/png,image/gif,image/webp"></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-light" form="<?= $formId ?>" name="action" value="up" formnovalidate <?= $i === 0 ? 'disabled' : '' ?> title="Move up">↑</button>
            <button class="btn btn-sm btn-light" form="<?= $formId ?>" name="action" value="down" formnovalidate <?= $i === count($categories) - 1 ? 'disabled' : '' ?> title="Move down">↓</button>
            <button class="btn btn-sm btn-outline-secondary" form="<?= $formId ?>" name="action" value="update">Save</button>
            <button class="btn btn-sm btn-outline-danger" form="<?= $formId ?>" name="action" value="delete" formnovalidate data-confirm="Delete this category?">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$categories): ?><tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="form-text mt-2">A line break in a name is shown on large screens only (e.g. "Game" / "controller").</p>
<?php admin_footer(); ?>
