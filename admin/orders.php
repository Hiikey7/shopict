<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$status = (string) ($_GET['status'] ?? '');
$status = isset(ORDER_STATUSES[$status]) ? $status : '';
$channels = ['web' => 'Online', 'whatsapp' => 'WhatsApp'];
$channel = (string) ($_GET['type'] ?? '');
$channel = isset($channels[$channel]) ? $channel : '';
$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;

$where = [];
$params = [];
if ($status !== '') {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($channel !== '') {
    $where[] = 'o.channel = ?';
    $params[] = $channel;
}
if ($q !== '') {
    $where[] = '(o.order_number LIKE ? OR o.customer_name LIKE ? OR o.email LIKE ? OR o.phone LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = db()->prepare("SELECT COUNT(*) FROM orders o $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);

$stmt = db()->prepare(
    "SELECT o.*, (SELECT SUM(quantity) FROM order_items i WHERE i.order_id = o.id) AS item_count
       FROM orders o $whereSql
      ORDER BY o.id DESC
      LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$stmt->execute($params);
$orders = $stmt->fetchAll();
// Counts for the dropdowns: statuses within the chosen order type, and order types within the chosen status.
$stmt = db()->prepare('SELECT status, COUNT(*) FROM orders' . ($channel !== '' ? ' WHERE channel = ?' : '') . ' GROUP BY status');
$stmt->execute($channel !== '' ? [$channel] : []);
$counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$stmt = db()->prepare('SELECT channel, COUNT(*) FROM orders' . ($status !== '' ? ' WHERE status = ?' : '') . ' GROUP BY channel');
$stmt->execute($status !== '' ? [$status] : []);
$channelCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$url = fn (array $over) => 'orders.php?' . http_build_query(array_filter(array_merge(['status' => $status, 'type' => $channel, 'q' => $q], $over), fn ($v) => $v !== '' && $v !== null && $v !== 1));

admin_header('Orders', 'orders');
?>
<form class="order-filters card card-body mb-3" method="get" data-auto-filter>
  <div class="row g-2 align-items-end">
    <div class="col-lg-4">
      <label class="form-label small mb-1" for="f-q">Search</label>
      <input class="form-control" id="f-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Order number, name, email or phone">
    </div>
    <div class="col-sm-6 col-lg-3">
      <label class="form-label small mb-1" for="f-status">Status</label>
      <select class="form-select" id="f-status" name="status">
        <option value="">All (<?= array_sum($counts) ?>)</option>
        <?php foreach (ORDER_STATUSES as $k => $label): ?>
        <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-sm-6 col-lg-3">
      <label class="form-label small mb-1" for="f-type">Order type</label>
      <select class="form-select" id="f-type" name="type">
        <option value="">All (<?= array_sum($channelCounts) ?>)</option>
        <?php foreach ($channels as $k => $label): ?>
        <option value="<?= e($k) ?>" <?= $channel === $k ? 'selected' : '' ?>><?= e($label) ?> (<?= (int) ($channelCounts[$k] ?? 0) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-2 d-flex gap-2">
      <button class="btn btn-primary flex-grow-1">Filter</button>
      <?php if ($status !== '' || $channel !== '' || $q !== ''): ?><a class="btn btn-outline-secondary" href="orders.php" title="Clear filters">Clear</a><?php endif; ?>
    </div>
  </div>
</form>

<div class="table-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Deliver to</th><th class="text-end">Items</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php if (!$orders): ?>
        <tr><td colspan="8" class="text-center text-muted py-5"><?= $q !== '' || $status !== '' || $channel !== '' ? 'No orders match these filters.' : 'No orders yet. They appear here as soon as a customer confirms checkout.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
        <tr class="row-link<?= $o['status'] === 'new' ? ' order-new' : '' ?>" data-href="order-view.php?id=<?= (int) $o['id'] ?>">
          <td class="text-nowrap">
            <a class="fw-semibold text-decoration-none" href="order-view.php?id=<?= (int) $o['id'] ?>"><?= e($o['order_number']) ?></a>
            <?php if ($o['channel'] === 'whatsapp'): ?><span class="badge badge-wa ms-1" title="Confirmed on WhatsApp">WhatsApp</span><?php else: ?><span class="badge badge-soft ms-1" title="Placed on the website">Online</span><?php endif; ?>
          </td>
          <td class="small text-nowrap"><?= e(date('M j, H:i', strtotime($o['created_at']))) ?></td>
          <td style="max-width:220px"><div class="text-truncate fw-medium"><?= e($o['customer_name']) ?></div><div class="small text-muted text-truncate"><?= e($o['phone']) ?></div></td>
          <td class="small text-muted" style="max-width:240px"><div class="text-truncate"><?= $o['delivery_method'] === 'pickup' ? 'Pickup at shop' : e(implode(', ', array_filter([$o['city'], $o['country']]))) ?></div></td>
          <td class="text-end"><?= (int) $o['item_count'] ?></td>
          <td class="text-end fw-medium text-nowrap"><?= money($o['total']) ?></td>
          <td><span class="badge status-badge status-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']]) ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="order-view.php?id=<?= (int) $o['id'] ?>">Open</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
  <span class="text-muted small"><?= $total ?> order<?= $total === 1 ? '' : 's' ?></span>
  <?php if ($pages > 1): ?>
  <nav><ul class="pagination pagination-sm mb-0">
    <?php for ($n = 1; $n <= $pages; $n++): ?>
    <li class="page-item <?= $n === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e($url(['page' => $n])) ?>"><?= $n ?></a></li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</div>
<?php admin_footer(); ?>
