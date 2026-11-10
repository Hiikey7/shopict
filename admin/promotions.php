<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $promoId = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'toggle') {
        try {
            db()->prepare('UPDATE promotions SET is_active = 1 - is_active WHERE id = ?')->execute([$promoId]);
            flash('Promotion updated.');
        } catch (Throwable $ex) {
            error_log('admin promotion toggle failed: ' . $ex->getMessage());
            flash('The promotion could not be updated. Check the server error log for details.', 'error');
        }
    }
    redirect('promotions.php');
}

$promos = db()->query(
    'SELECT pr.*, (SELECT COUNT(*) FROM promotion_products pp WHERE pp.promotion_id = pr.id) AS product_count
       FROM promotions pr
      ORDER BY pr.is_active DESC, COALESCE(pr.ends_at, "9999-12-31") DESC, pr.id DESC'
)->fetchAll();

$when = function (?string $start, ?string $end): string {
    $fmt = fn (string $d) => date('M j, Y H:i', strtotime($d));
    if (!$start && !$end) {
        return 'Always';
    }
    if (!$end) {
        return 'From ' . $fmt($start);
    }
    return ($start ? $fmt($start) : 'Now') . ' → ' . $fmt($end);
};

admin_header('Promotions', 'promotions');
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <p class="text-muted small mb-0 flex-grow-1">Automatic discounts for a period. While a promotion runs, shoppers see the discounted price with the regular price crossed out, on cards, in Quick View and in the cart. If several apply to a product, the biggest discount wins.</p>
  <a class="btn btn-primary" href="promotion-edit.php">+ New promotion</a>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover">
      <thead><tr><th>Promotion</th><th>Discount</th><th>When</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php if (!$promos): ?>
        <tr><td colspan="5" class="text-center text-muted py-5">No promotions yet. <a href="promotion-edit.php">Create the first one</a>.</td></tr>
        <?php endif; ?>
        <?php foreach ($promos as $p): [$status, $badge] = promotion_status($p); ?>
        <tr>
          <td>
            <a class="fw-medium text-decoration-none link-dark" href="promotion-edit.php?id=<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a>
            <?php if ($p['badge']): ?><div class="small text-muted">Badge: <?= e($p['badge']) ?></div><?php endif; ?>
          </td>
          <td class="text-nowrap"><?= e(promotion_summary($p)) ?></td>
          <td class="small text-nowrap"><?= e($when($p['starts_at'], $p['ends_at'])) ?></td>
          <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
          <td class="text-end text-nowrap">
            <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-outline-secondary"><?= $p['is_active'] ? 'Turn off' : 'Turn on' ?></button>
            </form>
            <a class="btn btn-sm btn-outline-secondary" href="promotion-edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php admin_footer(); ?>
