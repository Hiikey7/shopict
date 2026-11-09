<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$placements = banner_placements();

// Show/hide toggle and reordering from the list.
if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = db()->prepare('SELECT * FROM banners WHERE id = ?');
    $stmt->execute([$id]);
    if ($banner = $stmt->fetch()) {
        if (($_POST['action'] ?? '') === 'toggle') {
            db()->prepare('UPDATE banners SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            flash('Banner ' . ($banner['is_active'] ? 'hidden' : 'shown') . '.');
        } elseif (in_array($_POST['action'] ?? '', ['up', 'down'], true)) {
            $siblings = db()->prepare('SELECT id FROM banners WHERE placement = ? ORDER BY position, id');
            $siblings->execute([$banner['placement']]);
            $order = array_map('intval', $siblings->fetchAll(PDO::FETCH_COLUMN));
            $i = array_search($id, $order, true);
            $j = $_POST['action'] === 'up' ? $i - 1 : $i + 1;
            if (isset($order[$j])) {
                [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
                $update = db()->prepare('UPDATE banners SET position = ? WHERE id = ?');
                foreach ($order as $pos => $bid) {
                    $update->execute([$pos + 1, $bid]);
                }
            }
        }
    }
    redirect('banners.php#' . ($banner['placement'] ?? ''));
}

$byPlacement = [];
foreach (db()->query('SELECT * FROM banners ORDER BY position, id') as $b) {
    $byPlacement[$b['placement']][] = $b;
}

admin_header('Banners', 'banners');
?>
<p class="text-muted">Promotional banners on the home page, grouped by where they appear. Hidden banners are kept but not shown.</p>

<?php foreach ($placements as $key => $pl): $list = $byPlacement[$key] ?? []; $canAdd = $pl['max'] === null || count($list) < $pl['max']; ?>
<div class="card mb-4" id="<?= e($key) ?>">
  <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <div>
      <?= e($pl['label']) ?>
      <div class="section-note fw-normal"><?= e($pl['where']) ?></div>
    </div>
    <?php if ($canAdd): ?><a class="btn btn-sm btn-primary" href="banner-edit.php?placement=<?= urlencode($key) ?>">+ Add banner</a><?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table">
      <tbody>
        <?php if (!$list): ?>
        <tr><td class="text-muted text-center py-4">No banner here yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($list as $i => $b): ?>
        <tr>
          <td style="width:90px"><?= thumb($b['image'] ?: $b['bg_image'], 64) ?></td>
          <td>
            <div class="fw-medium"><?= e(trim(str_replace("\n", ' ', (string) $b['title']) . ' ' . $b['title_highlight']) ?: '(no title)') ?></div>
            <div class="small text-muted"><?= e(implode(' · ', array_filter([$b['eyebrow'], $b['subtitle'], trim($b['price_label'] . ' ' . $b['price'])]))) ?></div>
          </td>
          <td style="width:110px">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="toggle">
              <button class="badge border-0 <?= $b['is_active'] ? 'badge-on' : 'badge-off' ?>" title="Click to <?= $b['is_active'] ? 'hide' : 'show' ?>"><?= $b['is_active'] ? 'Visible' : 'Hidden' ?></button>
            </form>
          </td>
          <td class="text-end text-nowrap" style="width:220px">
            <?php if (count($list) > 1): ?>
            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <button class="btn btn-sm btn-light" name="action" value="up" <?= $i === 0 ? 'disabled' : '' ?> title="Move up">↑</button>
              <button class="btn btn-sm btn-light" name="action" value="down" <?= $i === count($list) - 1 ? 'disabled' : '' ?> title="Move down">↓</button>
            </form>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-secondary" href="banner-edit.php?id=<?= (int) $b['id'] ?>">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<div class="card">
  <div class="card-header">Hero background</div>
  <div class="card-body">
    The background behind the hero banners is set on the <a href="settings.php">Settings</a> page.
  </div>
</div>
<?php admin_footer(); ?>
