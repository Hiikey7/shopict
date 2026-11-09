<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$count = fn (string $sql, array $params = []) => (function () use ($sql, $params) {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
})();
$today = date('Y-m-d 00:00:00');
$month = date('Y-m-d 00:00:00', strtotime('-29 days'));

$stats = [
    ['New orders', (int) $count("SELECT COUNT(*) FROM orders WHERE status = 'new'"), 'orders.php?status=new', true],
    ['Orders today', (int) $count('SELECT COUNT(*) FROM orders WHERE created_at >= ?', [$today]), 'orders.php', false],
];
$recent = db()->query(
    'SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS item_count
       FROM orders o ORDER BY o.id DESC LIMIT 8'
)->fetchAll();
$topViewed = db()->prepare(
    "SELECT p.id, p.title, p.img_src, COUNT(*) AS views
       FROM analytics_events e JOIN products p ON p.id = e.product_id
      WHERE e.type = 'product_view' AND e.created_at >= ?
      GROUP BY p.id ORDER BY views DESC LIMIT 5"
);
$topViewed->execute([$month]);
$topViewed = $topViewed->fetchAll();
$lowStock = db()->query(
    'SELECT v.*, p.title, p.img_src FROM product_variants v JOIN products p ON p.id = v.product_id
      WHERE v.stock IS NOT NULL AND v.stock <= 3 AND p.is_active = 1 ORDER BY v.stock, p.title LIMIT 6'
)->fetchAll();

admin_header('Dashboard', 'dashboard');
?>
<div class="row g-3 mb-4">
  <?php foreach ($stats as [$label, $value, $href, $alert]): ?>
  <div class="col-6 col-lg-3">
    <a class="card stat-card h-100 text-decoration-none<?= $alert && $value ? ' stat-alert' : '' ?>" href="<?= e($href) ?>">
      <div class="card-body">
        <div class="stat-value"><?= e($value) ?></div>
        <div class="stat-label"><?= e($label) ?> →</div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
      <h2 class="h6 mb-1">Homepage category tiles</h2>
      <p class="small text-muted mb-0">Change the category images and links shown below the hero section.</p>
    </div>
    <a class="btn btn-outline-primary" href="categories.php">Manage category images</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">Latest orders <a href="orders.php" class="small fw-normal">All orders</a></div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Order</th><th>Customer</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php if (!$recent): ?><tr><td colspan="5" class="text-center text-muted py-5">No orders yet. New orders from checkout and WhatsApp show up here.</td></tr><?php endif; ?>
            <?php foreach ($recent as $o): ?>
            <tr class="row-link<?= $o['status'] === 'new' ? ' order-new' : '' ?>" data-href="order-view.php?id=<?= (int) $o['id'] ?>">
              <td class="text-nowrap">
                <a class="fw-semibold text-decoration-none" href="order-view.php?id=<?= (int) $o['id'] ?>"><?= e($o['order_number']) ?></a>
                <?php if ($o['channel'] === 'whatsapp'): ?><span class="badge badge-wa ms-1">WhatsApp</span><?php endif; ?>
                <div class="small text-muted"><?= e(date('M j, H:i', strtotime($o['created_at']))) ?> · <?= (int) $o['item_count'] ?> item<?= (int) $o['item_count'] === 1 ? '' : 's' ?></div>
              </td>
              <td style="max-width:220px"><div class="text-truncate"><?= e($o['customer_name']) ?></div><div class="small text-muted text-truncate"><?= e($o['city']) ?></div></td>
              <td class="text-end fw-medium text-nowrap"><?= money($o['total']) ?></td>
              <td><span class="badge status-badge status-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']]) ?></span></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="order-view.php?id=<?= (int) $o['id'] ?>">Open</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-4 d-flex flex-column gap-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">Most viewed products <a href="analytics.php" class="small fw-normal">Analytics</a></div>
      <div class="card-body p-0">
        <?php if (!$topViewed): ?><p class="text-muted p-3 mb-0 small">No product views in the last 30 days yet.</p><?php endif; ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($topViewed as $p): ?>
          <li class="list-group-item d-flex align-items-center gap-3">
            <?= thumb($p['img_src'], 36) ?>
            <a class="flex-grow-1 text-truncate text-decoration-none link-dark small" href="product-edit.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a>
            <span class="badge badge-soft"><?= (int) $p['views'] ?> views</span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="card">
      <div class="card-header">Low stock</div>
      <div class="card-body p-0">
        <?php if (!$lowStock): ?><p class="text-muted p-3 mb-0 small">No variant is at 3 or fewer. (Only variants with a stock number are tracked.)</p><?php endif; ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($lowStock as $v): ?>
          <li class="list-group-item d-flex align-items-center gap-3">
            <?= thumb($v['img_src'], 36) ?>
            <a class="flex-grow-1 text-decoration-none link-dark small" href="product-edit.php?id=<?= (int) $v['product_id'] ?>"><span class="d-block text-truncate"><?= e($v['title']) ?></span><span class="text-muted"><?= e(variant_label(['options' => json_column($v['options'])])) ?></span></a>
            <span class="badge <?= (int) $v['stock'] === 0 ? 'badge-off' : 'badge-soft' ?>"><?= (int) $v['stock'] === 0 ? 'Out' : (int) $v['stock'] . ' left' ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
