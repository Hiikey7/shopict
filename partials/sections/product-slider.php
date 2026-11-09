<?php
/**
 * Titled product slider (Trending Products, Best Sellers).
 *
 * @var string $section  section code (title, visibility and products come from the database)
 * @var string $nav      suffix of the navigation/pagination classes (snbpXX, snbnXX, spdXX)
 */
if (!section_visible($section)) {
    return;
}
?>
<section class="tf-sp-2">
  <div class="container">
    <div class="flat-title wow fadeInUp" data-wow-delay="0s">
      <h5 class="fw-semibold"><?= e(section_title($section)) ?></h5>
      <div class="box-btn-slide relative">
        <div class="swiper-button-prev nav-swiper nav-prev-products snbp<?= e($nav) ?>"><i class="icon-arrow-left-lg"></i></div>
        <div class="swiper-button-next nav-swiper nav-next-products snbn<?= e($nav) ?>"><i class="icon-arrow-right-lg"></i></div>
      </div>
    </div>
    <div class="swiper tf-sw-products" data-swiper="products-5" data-nav="<?= e($nav) ?>">
      <div class="swiper-wrapper">
        <?php foreach (section_products($section) as $p): ?>
        <div class="swiper-slide"><?php partial('cards/product', ['p' => $p]); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="d-flex d-xl-none sw-dot-default sw-pagination-products justify-content-center spd<?= e($nav) ?>"></div>
    </div>
  </div>
</section>
