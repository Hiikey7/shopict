<?php
/**
 * Product card with a main image slider and vertical thumbnails (Hot Product grid).
 *
 * @var array  $p          product row
 * @var string $class      card classes
 * @var string $typeClass  extra class for the thumb slider
 */
$id = (int) $p['id'];
$images = product_images($id) ?: [$p['img_src']];
?>
<div class="<?= e($class) ?>" data-wow-delay="0">
  <div class="card-product-wrapper overflow-visible aspect-ratio-0">
    <div class="product-thumb-slider thumbs-right <?= e($typeClass) ?> " data-thumb-slider>
      <div class="swiper tf-product-view-main">
        <div class="swiper-wrapper">
          <?php foreach ($images as $image): ?>
          <div class="swiper-slide"><a class="d-block tf-image-view" href="<?= e(product_url($p)) ?>"><?= img($image, 857, 482, 'lazyload', $p['title']) ?></a></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="swiper tf-product-view-thumbs">
        <div class="swiper-wrapper">
          <?php foreach ($images as $image): ?>
          <div class="swiper-slide"><div class="item"><?= img($image, 857, 482, '', $p['title']) ?></div></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php if ($p['discount'] !== null): ?>
    <?php if ($tag = discount_tag($p)): ?><span class="sale-tag z-5"><?= e($tag) ?></span><?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="card-product-info">
    <div class="box-title gap-xl-6">
      <div class="d-flex flex-column">
        <h6 class="bg-white relative z-5"><a class="name-product fw-semibold text-secondary link" href="<?= e(product_url($p)) ?>"><?= e($p['title']) ?></a></h6>
      </div>
      <div class="group-btn">
        <p class="price-wrap fw-medium"><span class="new-price h4 fw-normal text-primary mb-0"><?= money($p['price']) ?></span><?php if (has_old_price($p)): ?><span class="old-price price-text text-main-2"><?= money($p['old_price']) ?></span><?php endif; ?></p>
        <ul class="list-product-btn flex-row">
          <li></li>
          <li class="wishlist"><?= btn_wishlist($id) ?></li>
          <li><?= btn_quickview($id) ?></li>
        </ul>
      </div>
    </div>
    <?php if ($p['countdown_timer'] !== null): ?>
    <div class="box-infor-detail gap-xl-20">
      <div class="countdown-box">
        <div class="js-countdown" data-timer="<?= e($p['countdown_timer']) ?>" data-labels="Days,Hours,Mins,Secs"><?= countdown_placeholder() ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
