<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

// Quick show/hide toggle from the list.
if (is_post()) {
    verify_csrf();
    $code = (string) ($_POST['code'] ?? '');
    if (section($code)) {
        db()->prepare('UPDATE sections SET is_active = 1 - is_active WHERE code = ?')->execute([$code]);
        flash('Section "' . section($code)['title'] . '" ' . (section($code)['is_active'] ? 'hidden' : 'shown') . '.');
    }
    redirect('sections.php');
}

$sections = db()->query(
    'SELECT s.*, (SELECT COUNT(*) FROM section_products sp WHERE sp.section = s.code) AS product_count
       FROM sections s ORDER BY s.position'
)->fetchAll();
$previews = [];
$stmt = db()->prepare('SELECT p.img_src FROM section_products sp JOIN products p ON p.id = sp.product_id WHERE sp.section = ? ORDER BY sp.position, sp.id LIMIT 5');
foreach ($sections as $s) {
    $stmt->execute([$s['code']]);
    $previews[$s['code']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

admin_header('Sections', 'sections');
?>
<p class="text-muted">The home page sections, in the order they appear. Edit a section to rename it or to choose and reorder its products.</p>
<div class="table-card">
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Section</th><th>Products</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($sections as $s): $limit = section_limit($s); ?>
        <tr>
          <td style="max-width:420px">
            <div class="fw-medium"><?= e($s['title']) ?></div>
            <div class="section-note"><?= e($s['note']) ?></div>
          </td>
          <td class="text-nowrap">
            <?php if (section_takes_products($s)): ?>
            <div class="d-flex align-items-center gap-1">
              <?php foreach ($previews[$s['code']] as $img): ?><?= thumb($img, 30) ?><?php endforeach; ?>
              <span class="ms-1 small text-muted"><?= (int) $s['product_count'] ?><?= $limit ? ' / ' . $limit : '' ?></span>
            </div>
            <?php else: ?>
            <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="code" value="<?= e($s['code']) ?>">
              <button class="badge border-0 <?= $s['is_active'] ? 'badge-on' : 'badge-off' ?>" title="Click to <?= $s['is_active'] ? 'hide' : 'show' ?>"><?= $s['is_active'] ? 'Visible' : 'Hidden' ?></button>
            </form>
          </td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-secondary" href="section-edit.php?code=<?= urlencode($s['code']) ?>">Edit</a>
            <?php if ($s['code'] === 'categories'): ?><a class="btn btn-sm btn-outline-secondary" href="categories.php">Categories</a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer(); ?>
