<?php
// Customer's order page (confirmation after checkout). Opened with the order number and its secret token.
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
$stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ?');
$stmt->execute([(string) ($_GET['n'] ?? '')]);
$order = $stmt->fetch() ?: null;
if (!$order || !hash_equals($order['access_token'], (string) ($_GET['t'] ?? ''))) {
    http_response_code(404);
    $order = null;
}
$items = $order ? order_items((int) $order['id']) : [];
$placed = !empty($_GET['placed']);
$waUrl = $order && !empty($_GET['wa']) && whatsapp_number()
    ? whatsapp_url(whatsapp_order_text($items, (float) $order['total'], $order))
    : null;

partial('page-start', ['title' => ($order ? 'Order ' . $order['order_number'] : 'Order not found') . ' · ' . setting('store_name', 'Shop ICT Gadgets')]);
?>
<section class="tf-sp-2 co-page">
  <div class="container" style="max-width:860px">
    <?php if (!$order): ?>
    <div class="co-card text-center py-5">
      <h5 class="fw-semibold mb-2">We couldn't find that order</h5>
      <p class="body-text-3 text-main-2 mb-4">Use the link in your confirmation email to open your order.</p>
      <a class="tf-btn d-inline-flex" href="./"><span class="text-white">Back to the shop</span></a>
    </div>
    <?php else: ?>
    <div class="co-card text-center mb-4">
      <?php if ($placed): ?>
      <div class="co-check" aria-hidden="true">✓</div>
      <h4 class="fw-semibold mb-2">Thank you, <?= e($order['customer_name']) ?>!</h4>
      <p class="body-md-2 text-main-2 mb-1">Your order <strong class="text-secondary"><?= e($order['order_number']) ?></strong> has been received.</p>
      <p class="body-text-3 text-main-2 mb-0">
        <?php if ($order['email_sent_at']): ?>A confirmation has been sent to <strong><?= e($order['email']) ?></strong>.<?php else: ?>We'll email <strong><?= e($order['email']) ?></strong> once your order is confirmed.<?php endif; ?>
        We'll call <?= e($order['phone']) ?> to arrange delivery.
      </p>
      <?php else: ?>
      <h4 class="fw-semibold mb-2">Order <?= e($order['order_number']) ?></h4>
      <p class="body-md-2 text-main-2 mb-0">Placed <?= e(date('M j, Y \a\t H:i', strtotime($order['created_at']))) ?></p>
      <?php endif; ?>
      <p class="mt-3 mb-0"><span class="co-status status-<?= e($order['status']) ?>"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span></p>
      <?php if ($waUrl): ?>
      <a class="tf-btn btn-whatsapp d-inline-flex mt-4" href="<?= e($waUrl) ?>" target="_blank" rel="noopener" data-auto-whatsapp><?= whatsapp_icon(20) ?><span class="text-white">Send order on WhatsApp</span></a>
      <p class="caption text-main-2 mt-2 mb-0">If WhatsApp didn't open, tap the button.</p>
      <?php endif; ?>
    </div>

    <div class="row g-4">
      <div class="col-md-7">
        <div class="co-card co-summary h-100">
          <h5 class="fw-semibold mb-3">Items</h5>
          <ul class="co-items">
            <?php foreach ($items as $it): ?>
            <li>
              <?php $itUrl = $it['product_id'] ? product_url((int) $it['product_id']) : null; ?>
              <?php if ($itUrl): ?><a class="co-thumb" href="<?= e($itUrl) ?>" tabindex="-1" aria-hidden="true"><?php else: ?><span class="co-thumb"><?php endif; ?><?= $it['image'] ? img($it['image'], 64, 64, '', '') : '' ?><span class="co-qty"><?= (int) $it['quantity'] ?></span><?= $itUrl ? '</a>' : '</span>' ?>
              <span class="co-item-text">
                <?php if ($itUrl): ?><a class="body-md-2 fw-semibold d-block link co-item-link" href="<?= e($itUrl) ?>" title="<?= e($it['title']) ?>"><?= e(short_title($it['title'], 32)) ?></a><?php else: ?><span class="body-md-2 fw-semibold d-block" title="<?= e($it['title']) ?>"><?= e(short_title($it['title'], 32)) ?></span><?php endif; ?>
                <?php if ($it['variant_label']): ?><span class="caption text-main-2 d-block"><?= e($it['variant_label']) ?></span><?php endif; ?>
                <?php if (!empty($it['is_preorder'])): ?><span class="preorder-pill is-small">Pre-order</span><?php endif; ?>
                <span class="caption text-main-2 d-block"><?= money($it['price']) ?> each</span>
              </span>
              <span class="body-md-2 fw-semibold text-nowrap"><?= money($it['line_total']) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <dl class="co-totals mb-0">
            <div><dt>Subtotal</dt><dd><?= money($order['subtotal']) ?></dd></div>
            <?php if ((float) $order['discount'] > 0): ?><div class="co-discount"><dt>Promo code <?= e((string) $order['promo_code']) ?></dt><dd>−<?= money($order['discount']) ?></dd></div><?php endif; ?>
            <div><dt>Delivery</dt><dd><?= e(delivery_cost_text($order['delivery_method'], (float) $order['delivery_fee'])) ?></dd></div>
            <div class="co-grand"><dt>Total</dt><dd><?= money($order['total']) ?></dd></div>
          </dl>
        </div>
      </div>
      <div class="col-md-5">
        <div class="co-card h-100">
          <h5 class="fw-semibold mb-3"><?= $order['delivery_method'] === 'pickup' ? 'Pickup' : 'Delivery' ?></h5>
          <p class="body-md-2 mb-1 fw-semibold"><?= e($order['customer_name']) ?></p>
          <p class="body-text-3 mb-1"><?= e(order_address_line($order)) ?></p>
          <p class="body-text-3 mb-1"><?= e($order['phone']) ?></p>
          <p class="body-text-3 mb-3"><?= e($order['email']) ?></p>
          <?php if ($order['notes']): ?><p class="caption text-main-2 mb-3">Notes: <?= e($order['notes']) ?></p><?php endif; ?>
        </div>
      </div>
    </div>
    <p class="text-center mt-4"><a class="link text-secondary body-md-2" href="./">← Continue shopping</a></p>
    <?php endif; ?>
  </div>
</section>
<?php if ($waUrl && $placed): ?>
<script>
  // Open WhatsApp with the order details once, right after a WhatsApp checkout.
  (function () {
    var key = "wa-opened-<?= e($order['order_number']) ?>";
    try { if (sessionStorage.getItem(key)) return; sessionStorage.setItem(key, "1"); } catch (e) {}
    setTimeout(function () { var a = document.querySelector("[data-auto-whatsapp]"); if (a) window.location.href = a.href; }, 900);
  })();
</script>
<?php endif; ?>
<?php partial('page-end'); ?>
