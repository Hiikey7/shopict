<?php
/**
 * Contents of the Shopping Cart page. Rendered by cart.php and returned (as pageHtml) by
 * api/cart.php after every change made on that page.
 *
 * @var array $items cart rows from cart_items()
 */
$subtotal = cart_subtotal($items);
$fee = delivery_fee_for($subtotal);
$delivery = free_delivery_progress($subtotal);
$wa = $items && whatsapp_number() ? whatsapp_url(whatsapp_order_text($items, $subtotal)) : null;
?>
<?php if (!$items): ?>
<div class="co-card text-center py-5">
  <h5 class="fw-semibold mb-2">Your cart is empty</h5>
  <p class="body-text-3 text-main-2 mb-4">Browse the shop and add the products you like.</p>
  <a class="tf-btn d-inline-flex" href="./"><span class="text-white">Continue shopping</span></a>
</div>
<?php else: ?>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="co-card p-0">
      <div class="cart-table-head d-none d-md-grid body-text-3 text-main-2">
        <span>Product</span><span>Price</span><span>Quantity</span><span class="text-end">Total</span><span></span>
      </div>
      <ul class="cart-lines">
        <?php foreach ($items as $it):
            $variant = $it['variant_id'] ? find_variant((int) $it['id'], (int) $it['variant_id']) : null;
            $max = $variant && $variant['stock'] !== null ? (int) $variant['stock'] : 99;
            $qty = (int) $it['quantity'];
        ?>
        <li class="cart-line" data-cart-row>
          <div class="cart-line-product">
            <a class="co-thumb" href="<?= e(product_url($it)) ?>"><?= img($it['img_src'], 80, 80, '', $it['title']) ?></a>
            <div class="min-w-0">
              <a class="body-md-2 fw-semibold link d-block cart-line-title" href="<?= e(product_url($it)) ?>" title="<?= e($it['title']) ?>"><?= e(short_title($it['title'])) ?></a>
              <?php if ($it['variant_label'] !== ''): ?><span class="caption text-main-2 d-block"><?= e($it['variant_label']) ?></span><?php endif; ?>
              <?php if (!empty($it['is_preorder'])): ?><span class="preorder-pill is-small">Pre-order</span><?php endif; ?>
              <?php if (!empty($it['promotion'])): ?><span class="caption text-primary d-block"><?= e($it['promotion']['badge'] ?: $it['promotion']['name']) ?></span><?php endif; ?>
              <?php if ($variant && $variant['stock'] !== null && $qty >= $max): ?><span class="caption text-main-2 d-block">Only <?= $max ?> in stock</span><?php endif; ?>
            </div>
          </div>
          <div class="cart-line-price">
            <span class="d-md-none caption text-main-2">Price</span>
            <span class="body-md-2"><?= money($it['price']) ?></span>
            <?php if (has_old_price($it)): ?><br><span class="caption text-main-2 old-price"><?= money($it['old_price']) ?></span><?php endif; ?>
          </div>
          <div class="cart-line-qty">
            <div class="cart-qty">
              <button type="button" aria-label="One fewer" data-cart-step="-1" data-item="<?= (int) $it['item_id'] ?>" data-qty="<?= $qty ?>" <?= $qty <= 1 ? 'disabled' : '' ?>><svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M2 6h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
              <input type="number" min="1" max="<?= $max ?>" value="<?= $qty ?>" aria-label="Quantity of <?= e($it['title']) ?>" data-cart-qty data-item="<?= (int) $it['item_id'] ?>">
              <button type="button" aria-label="One more" data-cart-step="1" data-item="<?= (int) $it['item_id'] ?>" data-qty="<?= $qty ?>" <?= $qty >= $max ? 'disabled' : '' ?>><svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M2 6h8M6 2v8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></button>
            </div>
          </div>
          <div class="cart-line-total text-md-end">
            <span class="d-md-none caption text-main-2">Total</span>
            <span class="body-md-2 fw-semibold"><?= money($it['price'] * $qty) ?></span>
          </div>
          <div class="cart-line-remove text-end">
            <button type="button" class="cart-trash" aria-label="Remove <?= e($it['title']) ?> from cart" title="Remove" data-cart-remove="<?= (int) $it['item_id'] ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 12.5h9l1-12.5M10 11v5.5M14 11v5.5"/></svg></button>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <p class="mt-3 mb-0"><a class="link text-secondary body-md-2" href="./">← Continue shopping</a></p>
  </div>

  <div class="col-lg-4">
    <div class="co-card co-summary">
      <h5 class="fw-semibold mb-3">Cart totals</h5>
      <dl class="co-totals">
        <div><dt>Subtotal (<?= array_sum(array_map(fn ($i) => (int) $i['quantity'], $items)) ?> items)</dt><dd><?= money($subtotal) ?></dd></div>
        <div><dt>Delivery</dt><dd>Chosen at checkout</dd></div>
        <div class="co-grand"><dt>Total</dt><dd><?= money($subtotal) ?></dd></div>
      </dl>
      <?php if ($delivery): ?>
      <div class="delivery-progress mb-3">
        <div class="progress" role="progressbar" aria-label="Progress toward free delivery" aria-valuenow="<?= $delivery['percent'] ?>" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: <?= $delivery['percent'] ?>%"></div></div>
        <p class="caption text-main-2 mt-2 mb-0"><?php if ($delivery['left'] > 0): ?>Add <strong><?= $delivery['remaining'] ?></strong> more for free delivery.<?php else: ?>Your order qualifies for <strong>free delivery</strong>.<?php endif; ?></p>
      </div>
      <?php endif; ?>
      <a class="tf-btn w-100" href="checkout.php"><span class="text-white">Proceed to checkout</span></a>
      <?php if ($wa): ?>
      <div class="co-or"><span>or</span></div>
      <a class="tf-btn btn-whatsapp w-100" href="<?= e($wa) ?>" target="_blank" rel="noopener" data-cart-whatsapp><?= whatsapp_icon(20) ?><span class="text-white">Order via WhatsApp</span></a>
      <?php endif; ?>
      <p class="caption text-main-2 mt-3 mb-0">Pay on delivery. You'll enter your delivery address on the next step.</p>
    </div>
  </div>
</div>
<?php endif; ?>
