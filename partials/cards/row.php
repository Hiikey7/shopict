<?php
/**
 * Horizontal product card (Hot Product grid, Top 20 / Featured / Top Selling / On-sale lists).
 *
 * @var array  $p          product row
 * @var string $class      card classes
 * @var string $nameClass  extra classes for the product name link
 */
$nameClass = $nameClass ?? '';
$id = (int) $p['id'];
?>
<div class="card-product <?= e($class) ?>">
  <div class="card-product-wrapper">
    <a class="product-img" href="<?= e(product_url($p)) ?>"><?= product_images_pair($p) ?></a>
    <?php if (!empty($p['is_preorder'])): ?><span class="preorder-tag">Pre-order</span><?php endif; ?>
  </div>
  <div class="card-product-info">
    <div class="box-title">
      <div class="bg-white relative z-5">
        <p class="caption text-main-2 font-2"><?= e($p['category']) ?></p>
        <a class="name-product body-md-2 fw-semibold text-secondary link<?= $nameClass !== '' ? ' ' . e($nameClass) : '' ?>" href="<?= e(product_url($p)) ?>"><?= e($p['title']) ?></a>
      </div>
      <div class="group-btn">
        <p class="price-wrap fw-medium"><span class="new-price price-text fw-medium"><?= money($p['price']) ?></span><?php if (has_old_price($p)): ?><span class="old-price body-md-2 text-main-2"><?= money($p['old_price']) ?></span><?php endif; ?></p>
        <ul class="list-product-btn flex-row">
          <li><?= btn_add_to_cart($id) ?></li>
          <li class="wishlist"><?= btn_wishlist($id) ?></li>
          <li><?= btn_quickview($id) ?></li>
        </ul>
      </div>
    </div>
  </div>
</div>
