<?php
$cartItems = page_state()['cart'];
$cartSubtotal = cart_subtotal($cartItems);
$delivery = free_delivery_progress($cartSubtotal);
$wa = $cartItems && whatsapp_number() ? whatsapp_url(whatsapp_order_text($cartItems, $cartSubtotal)) : null;
?>
<div class="offcanvas offcanvas-end popup-style popup-shopping-cart" id="shoppingCart">
  <div class="popup-wrapper">
    <div class="popup-header">
      <h5 class="title fw-semibold">Shopping cart</h5>
      <span class="icon-close icon-close-popup link" data-bs-dismiss="offcanvas"></span>
    </div>
    <?php partial('cart-body', ['items' => $cartItems]); ?>
    <div class="popup-footer<?= $cartItems ? '' : ' d-none' ?>" data-cart-footer>
      <p class="cart-total fw-semibold"><span>Subtotal:</span><span class="price-amount product-title text-primary" data-cart-subtotal><?= money($cartSubtotal) ?></span></p>
      <div class="box-btn">
        <a class="tf-btn btn-gray" href="cart.php"><span class="text-white">View Cart</span></a
        ><a class="tf-btn" href="checkout.php"><span class="text-white">Check Out</span></a>
      </div>
      <?php if (whatsapp_number()): ?>
      <a class="tf-btn btn-whatsapp w-100 mt-2" href="<?= e($wa ?? '#') ?>" target="_blank" rel="noopener" data-cart-whatsapp><?= whatsapp_icon(20) ?><span class="text-white">Order via WhatsApp</span></a>
      <?php endif; ?>
      <?php if ($delivery): ?>
      <div class="delivery-progress" data-delivery>
        <div class="progress" role="progressbar" aria-label="Progress toward free delivery" aria-valuenow="<?= $delivery['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar" style="width: <?= $delivery['percent'] ?>%" data-delivery-bar></div>
        </div>
        <p class="body-text-3" data-delivery-text><i class="icon-delivery-2 fs-24"></i><?php if ($delivery['left'] > 0): ?>Add <span class="fw-bold"><?= $delivery['remaining'] ?></span> more for free delivery<?php else: ?>Your order qualifies for <span class="fw-bold">free delivery</span><?php endif; ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
