<?php
/**
 * Vertical product card used by the product sliders.
 *
 * @var array  $p        product row
 * @var string $class    extra card classes (e.g. "style-img-border")
 * @var bool   $delay    output data-wow-delay
 * @var string $variant  "default" | "recent" (no old price) | "search" (sale badge + stock bar)
 */
$class = $class ?? '';
$delay = $delay ?? true;
$variant = $variant ?? 'default';
$id = (int) $p['id'];
?>
<div class="card-product <?= trim($class . ' ' . wow_class($p)) ?>"<?= $delay ? wow_delay_attr($p) : '' ?>>
  <div class="card-product-wrapper">
    <a class="product-img" href="<?= e(product_url($p)) ?>"><?= product_images_pair($p) ?></a>
    <?php if (!empty($p['is_preorder'])): ?><span class="preorder-tag">Pre-order</span><?php endif; ?>
    <?php if ($variant === 'search' && ($tag = discount_tag($p))): ?><span class="sale-tag z-5"><?= e($tag) ?></span><?php endif; ?>
    <ul class="list-product-btn">
      <li><?= btn_add_to_cart($id, 'tooltip-left') ?></li>
      <li class="d-none d-sm-block wishlist"><?= btn_wishlist($id, 'tooltip-left') ?></li>
      <li><?= btn_quickview($id, 'tooltip-left') ?></li>
    </ul>
  </div>
  <div class="card-product-info">
    <div class="box-title">
      <div class="d-flex flex-column">
        <p class="caption text-main-2 font-2"><?= e($p['category']) ?></p>
        <a class="name-product body-md-2 fw-semibold text-secondary link" href="<?= e(product_url($p)) ?>"><?= e($p['title']) ?></a>
      </div>
      <?php if ($variant === 'recent'): ?>
      <p class="price-wrap fw-medium"><span class="new-price price-text fw-medium"><?= money($p['price']) ?></span></p>
      <?php else: ?>
      <p class="price-wrap fw-medium"><span class="new-price price-text fw-medium <?= $variant === 'search' ? 'text-primary ' : '' ?>mb-0"><?= money($p['price']) ?></span><?php if (has_old_price($p)): ?><span class="old-price body-md-2 text-main-2 fw-normal"><?= money($p['old_price']) ?></span><?php endif; ?></p>
      <?php endif; ?>
    </div>
    <?php if ($variant === 'search'): ?>
    <div class="box-infor-detail">
      <div class="product-progress-sale">
        <div class="progress-sold progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-primary" style="width:<?= e($p['progress_width']) ?>"></div></div>
        <div class="box-quantity d-flex justify-content-between">
          <p class="text-avaiable caption">Sold: <span class="fw-bold"><?= e($p['sold']) ?></span></p>
          <p class="text-avaiable caption">Available: <span class="fw-bold"><?= e($p['available']) ?></span></p>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
