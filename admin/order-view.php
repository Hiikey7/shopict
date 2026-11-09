<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$order = find_order($id);
if (!$order) {
    flash('That order no longer exists.', 'error');
    redirect('orders.php');
}

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'update') {
        $status = (string) ($_POST['status'] ?? '');
        if (!isset(ORDER_STATUSES[$status])) {
            flash('Choose a valid status.', 'error');
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            // Cancelling puts tracked variant stock back; un-cancelling takes it again.
            if (($status === 'cancelled') !== ($order['status'] === 'cancelled')) {
                $sign = $status === 'cancelled' ? 1 : -1;
                $stock = $pdo->prepare('UPDATE product_variants SET stock = GREATEST(CAST(stock AS SIGNED) + ?, 0) WHERE id = ? AND stock IS NOT NULL');
                $sold = $pdo->prepare('UPDATE products SET sold = GREATEST(CAST(COALESCE(sold, 0) AS SIGNED) - ?, 0), available = CASE WHEN available IS NULL THEN NULL ELSE GREATEST(CAST(available AS SIGNED) + ?, 0) END WHERE id = ?');
                foreach (order_items($id) as $it) {
                    if ($it['variant_id']) {
                        $stock->execute([$sign * (int) $it['quantity'], $it['variant_id']]);
                    }
                    if ($it['product_id']) {
                        $sold->execute([$sign * (int) $it['quantity'], $sign * (int) $it['quantity'], $it['product_id']]);
                    }
                }
            }
            $pdo->prepare('UPDATE orders SET status = ?, admin_note = ? WHERE id = ?')->execute([$status, post_str('admin_note'), $id]);
            $pdo->commit();
            flash('Order updated.');
        }
    } elseif ($action === 'resend') {
        $sent = send_order_emails($order);
        flash($sent ? 'Confirmation email sent to ' . $order['email'] . '.' : 'The email could not be sent. Check the mail settings in config.php.', $sent ? 'success' : 'error');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
        flash('Order ' . $order['order_number'] . ' deleted.');
        redirect('orders.php');
    }
    redirect('order-view.php?id=' . $id);
}

$items = order_items($id);
$phoneDigits = preg_replace('/\D+/', '', $order['phone']);
// Local numbers like 0712… are written for WhatsApp with the shop's country code, taken from its own number.
$shopWa = whatsapp_number();
if ($phoneDigits !== '' && $phoneDigits[0] === '0' && $shopWa && preg_match('/^(254|255|256|250|27|234|233|1|44|91)/', $shopWa, $m)) {
    $phoneDigits = $m[1] . substr($phoneDigits, 1);
}
$customerWa = $phoneDigits !== '' ? whatsapp_url('Hi ' . $order['customer_name'] . ', this is ' . setting('store_name', 'Shop ICT Gadgets') . ' about your order ' . $order['order_number'] . '.', $phoneDigits) : null;

admin_header('Order ' . $order['order_number'], 'orders');
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <a href="orders.php" class="small text-decoration-none me-auto">← All orders</a>
  <button class="btn btn-sm btn-outline-secondary" type="button" onclick="window.print()">Print</button>
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card mb-4">
      <div class="card-header d-flex flex-wrap gap-2 align-items-center">
        <span><?= e($order['order_number']) ?></span>
        <span class="badge status-badge status-<?= e($order['status']) ?>"><?= e(ORDER_STATUSES[$order['status']]) ?></span>
        <?php if ($order['channel'] === 'whatsapp'): ?><span class="badge badge-wa">WhatsApp</span><?php endif; ?>
        <span class="small text-muted fw-normal ms-auto">Placed <?= e(date('D, M j, Y \a\t H:i', strtotime($order['created_at']))) ?></span>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0 order-items-table">
          <thead><tr><th class="order-thumb-col" style="width:56px"></th><th>Item</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
              <td class="order-thumb-col"><?= thumb($it['image'], 44) ?></td>
              <td class="order-item-cell">
                <?php if ($it['product_id']): ?><a class="fw-medium order-item-link" href="product-edit.php?id=<?= (int) $it['product_id'] ?>" title="<?= e($it['title']) ?>"><?= e(short_title($it['title'], 32)) ?></a><?php else: ?><span class="fw-medium" title="<?= e($it['title']) ?>"><?= e(short_title($it['title'], 32)) ?></span> <span class="small text-muted">(product deleted)</span><?php endif; ?>
                <?php if ($it['variant_label']): ?><div class="small text-muted"><?= e($it['variant_label']) ?></div><?php endif; ?>
                <?php if (!empty($it['is_preorder'])): ?><span class="badge badge-preorder">Pre-order</span><?php endif; ?>
              </td>
              <td class="text-end text-nowrap"><?= money($it['price']) ?></td>
              <td class="text-end"><?= (int) $it['quantity'] ?></td>
              <td class="text-end text-nowrap fw-medium"><?= money($it['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td class="order-thumb-col"></td><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end"><?= money($order['subtotal']) ?></td></tr>
            <?php if ((float) $order['discount'] > 0): ?><tr><td class="order-thumb-col"></td><td colspan="3" class="text-end text-muted">Promo code <strong><?= e((string) $order['promo_code']) ?></strong></td><td class="text-end text-success">−<?= money($order['discount']) ?></td></tr><?php endif; ?>
            <tr><td class="order-thumb-col"></td><td colspan="3" class="text-end text-muted">Delivery</td><td class="text-end"><?= e(delivery_cost_text($order['delivery_method'], (float) $order['delivery_fee'])) ?></td></tr>
            <tr><td class="order-thumb-col"></td><td colspan="3" class="text-end fw-semibold">Total (pay on delivery)</td><td class="text-end fw-bold"><?= money($order['total']) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <form method="post" class="card mb-4">
      <?= csrf_field() ?><input type="hidden" name="action" value="update">
      <div class="card-header">Status &amp; notes</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
              <?php foreach (ORDER_STATUSES as $k => $label): ?><option value="<?= $k ?>" <?= $k === $order['status'] ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select>
            <div class="form-text">Cancelling puts the items' stock back.</div>
          </div>
          <div class="col-md-7">
            <label class="form-label" for="admin_note">Internal note</label>
            <textarea class="form-control" id="admin_note" name="admin_note" rows="3" placeholder="Only visible to admins"><?= e($order['admin_note']) ?></textarea>
          </div>
        </div>
      </div>
      <div class="card-footer bg-white"><button class="btn btn-primary">Save</button></div>
    </form>
  </div>

  <div class="col-xl-4">
    <div class="card mb-4">
      <div class="card-header">Customer</div>
      <div class="card-body">
        <p class="fw-semibold mb-1"><?= e($order['customer_name']) ?></p>
        <p class="mb-1"><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $order['phone'])) ?>"><?= e($order['phone']) ?></a></p>
        <p class="mb-3"><a href="mailto:<?= e($order['email']) ?>?subject=<?= rawurlencode('Your order ' . $order['order_number']) ?>"><?= e($order['email']) ?></a></p>
        <?php if ($customerWa): ?><a class="btn btn-sm btn-success w-100 mb-2" href="<?= e($customerWa) ?>" target="_blank" rel="noopener">Message on WhatsApp</a><?php endif; ?>
      </div>
    </div>
    <div class="card mb-4">
      <div class="card-header"><?= $order['delivery_method'] === 'pickup' ? 'Pickup at the shop' : 'Countrywide delivery' ?></div>
      <div class="card-body">
        <p class="mb-1"><?= e(order_address_line($order)) ?></p>
        <?php if ($order['notes']): ?><p class="small text-muted mt-2 mb-0"><strong>Customer notes:</strong> <?= e($order['notes']) ?></p><?php endif; ?>
        <a class="small d-inline-block mt-2" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode(order_address_line($order)) ?>" target="_blank" rel="noopener">Open in Google Maps ↗</a>
      </div>
    </div>
    <div class="card mb-4">
      <div class="card-header">Confirmation email</div>
      <div class="card-body">
        <p class="small mb-2"><?= $order['email_sent_at'] ? 'Sent ' . e(date('M j, H:i', strtotime($order['email_sent_at']))) . '.' : '<span class="text-danger">Not sent.</span>' ?></p>
        <?php if (((array) config('mail'))['driver'] !== 'smtp'): ?><p class="small text-muted">Email is in test mode: messages are saved in <code>storage/mail/</code> instead of being sent. Set up SMTP in <code>config.php</code>.</p><?php endif; ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="resend"><button class="btn btn-sm btn-outline-secondary">Send again</button></form>
      </div>
    </div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <button class="btn btn-sm btn-outline-danger w-100" data-confirm="Delete this order permanently? Stock is not put back (cancel it first for that).">Delete order</button>
    </form>
  </div>
</div>
<?php admin_footer(); ?>
