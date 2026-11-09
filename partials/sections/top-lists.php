<?php
// Four-column block of product lists. Each column is its own section, shown as slides of three products.
$columns = [];
foreach (['top_20' => '5', 'featured_products' => '6', 'top_selling' => '7', 'on_sale' => '8'] as $code => $nav) {
    if (section_visible($code)) {
        $columns[] = [section_title($code), $nav, array_chunk(section_products($code), 3)];
    }
}
if (!$columns) {
    return;
}
?>
<section class="tf-sp-2 pb-0">
  <div class="container">
    <div class="tf-grid-product sliders">
      <?php foreach ($columns as [$title, $nav, $slides]): ?>
      <div class="tf-grid-product-item box-btn-slide-item">
        <div class="flat-title wow fadeInUp" data-wow-delay="0s">
          <h5 class="fw-semibold"><?= e($title) ?></h5>
          <div class="box-btn-slide relative">
            <div class="swiper-button-prev nav-swiper nav-prev-products snbp<?= $nav ?>"><i class="icon-arrow-left-lg"></i></div>
            <div class="swiper-button-next nav-swiper nav-next-products snbn<?= $nav ?>"><i class="icon-arrow-right-lg"></i></div>
          </div>
        </div>
        <div class="swiper tf-sw-products" data-swiper="top-list" data-nav="<?= $nav ?>">
          <div class="swiper-wrapper">
            <?php foreach ($slides as $slide): ?>
            <div class="swiper-slide">
              <ul class="product-list-wrap">
                <?php foreach ($slide as $p): ?>
                <li class="wow fadeInUp"<?= wow_delay_attr($p) ?>><?php partial('cards/row', ['p' => $p, 'class' => 'style-row row-small-2']); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="d-flex d-lg-none sw-dot-default sw-pagination-products justify-content-center spd<?= $nav ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
