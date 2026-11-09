<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $pdo = db();
    try {
        $link = post_str('link_url');
        if ($link !== null && preg_match('/^\s*javascript:/i', $link)) {
            throw new RuntimeException('Enter a normal web address for the link.');
        }
        switch ($action) {
            case 'create':
                $name = post_str('name');
                if ($name === null) {
                    throw new RuntimeException('Enter the brand name.');
                }
                $img = upload_image('logo');
                $pos = (int) $pdo->query('SELECT COALESCE(MAX(position), 0) + 1 FROM brands')->fetchColumn();
                $pdo->prepare('INSERT INTO brands (name, logo, link_url, position) VALUES (?, ?, ?, ?)')
                    ->execute([mb_substr($name, 0, 100), $img['path'] ?? null, $link, $pos]);
                flash('Brand added.');
                break;
            case 'update':
                $name = post_str('name');
                if ($name === null) {
                    throw new RuntimeException('The brand name cannot be empty.');
                }
                $img = upload_image('logo');
                $logo = $img['path'] ?? (empty($_POST['remove_logo']) ? null : '');
                $sql = 'UPDATE brands SET name = ?, link_url = ?, is_active = ?' . ($logo !== null ? ', logo = ?' : '') . ' WHERE id = ?';
                $params = [mb_substr($name, 0, 100), $link, empty($_POST['is_active']) ? 0 : 1];
                if ($logo !== null) {
                    $params[] = $logo === '' ? null : $logo;
                }
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);
                flash('Brand saved.');
                break;
            case 'delete':
                $pdo->prepare('DELETE FROM brands WHERE id = ?')->execute([$id]);
                flash('Brand deleted.');
                break;
            case 'up':
            case 'down':
                $order = array_map('intval', $pdo->query('SELECT id FROM brands ORDER BY position, id')->fetchAll(PDO::FETCH_COLUMN));
                $i = array_search($id, $order, true);
                $j = $action === 'up' ? $i - 1 : $i + 1;
                if ($i !== false && isset($order[$j])) {
                    [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
                    $update = $pdo->prepare('UPDATE brands SET position = ? WHERE id = ?');
                    foreach ($order as $pos => $bid) {
                        $update->execute([$pos + 1, $bid]);
                    }
                }
                break;
        }
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('brands.php');
}

$brands = db()->query('SELECT * FROM brands ORDER BY position, id')->fetchAll();
$section = section('brands');

admin_header('Brands', 'brands');
?>
<p class="text-muted">
  Visible brands appear in the "<?= e($section['title'] ?? 'Authorised Brands') ?>" carousel on the home page<?= $section && !$section['is_active'] ? ' (that section is currently <strong>hidden</strong>)' : '' ?>.
  Rename or hide the section in <a href="section-edit.php?code=brands">Sections</a>.
  A brand without a logo is shown as its name. Without a link, a brand opens a shop search for its name.
</p>

<div class="card mb-4">
  <div class="card-header">Add a brand</div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="col-md-3"><label class="form-label" for="new_name">Name</label><input class="form-control" id="new_name" name="name" required maxlength="100"></div>
      <div class="col-md-4"><label class="form-label" for="new_logo">Logo</label><input class="form-control" id="new_logo" type="file" name="logo" accept="image/png,image/webp,image/jpeg,image/gif"><div class="form-text">Transparent PNG, about 300 × 120 px. Optional.</div></div>
      <div class="col-md-3"><label class="form-label" for="new_link">Link</label><input class="form-control" id="new_link" name="link_url" maxlength="255" placeholder="Optional"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
    </form>
  </div>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th style="width:130px">Logo</th><th>Name &amp; link</th><th style="width:230px">Upload logo</th><th style="width:90px">Visible</th><th style="width:250px"></th></tr></thead>
      <tbody>
        <?php foreach ($brands as $i => $b): $formId = 'brand-' . (int) $b['id']; ?>
        <tr>
          <td>
            <?php if ($b['logo']): ?>
            <img src="<?= e(admin_asset($b['logo'])) ?>" alt="" class="brand-admin-logo">
            <label class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" form="<?= $formId ?>"> Remove</label>
            <?php else: ?>
            <span class="badge badge-soft">Text: <?= e($b['name']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <form id="<?= $formId ?>" method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"></form>
            <input class="form-control form-control-sm mb-1" name="name" form="<?= $formId ?>" value="<?= e($b['name']) ?>" maxlength="100" required aria-label="Name">
            <input class="form-control form-control-sm" name="link_url" form="<?= $formId ?>" value="<?= e($b['link_url']) ?>" maxlength="255" placeholder="Link (optional)" aria-label="Link">
          </td>
          <td><input class="form-control form-control-sm" type="file" name="logo" form="<?= $formId ?>" accept="image/png,image/webp,image/jpeg,image/gif" aria-label="Logo"></td>
          <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" form="<?= $formId ?>" <?= $b['is_active'] ? 'checked' : '' ?> aria-label="Visible"></div></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-light" form="<?= $formId ?>" name="action" value="up" formnovalidate <?= $i === 0 ? 'disabled' : '' ?> title="Move up">↑</button>
            <button class="btn btn-sm btn-light" form="<?= $formId ?>" name="action" value="down" formnovalidate <?= $i === count($brands) - 1 ? 'disabled' : '' ?> title="Move down">↓</button>
            <button class="btn btn-sm btn-outline-secondary" form="<?= $formId ?>" name="action" value="update">Save</button>
            <button class="btn btn-sm btn-outline-danger" form="<?= $formId ?>" name="action" value="delete" formnovalidate data-confirm="Delete this brand?">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$brands): ?><tr><td colspan="5" class="text-center text-muted py-4">No brands yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer(); ?>
