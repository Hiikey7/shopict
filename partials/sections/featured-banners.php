<?php
// Three-up featured promo cards (banners with placement "featured_card").
$cards = banners('featured_card');
if (!$cards) {
    return;
}
// Every line ends with <br/>, as the original component did.
$lines = fn (?string $text) => lines($text) . '<br/>';
?>
<div class="themesFlat">
  <div class="container">
    <div class="swiper tf-sw-categories" data-swiper="featured-banners">
      <div class="swiper-wrapper">
        <?php foreach ($cards as $b): ?>
        <div class="swiper-slide">
          <a class="wg-product-view style-2 hover-img d-block" href="<?= e($b['link_url'] ?: './') ?>"><div class="image img-style">
              <?= $b['image'] ? img($b['image'], 972, 424, 'lazyload') : '' ?>
            </div>
            <div class="content">
              <div class="box-title">
                <p class="tag body-md-2 fw-semibold <?= e($b['tag_color']) ?>"><?= e($b['eyebrow']) ?></p>
                <h4 class="name link <?= e($b['text_color']) ?>"><?= $lines($b['title']) ?></h4>
                <p class="caption lh-15 fw-3 <?= e($b['text_color']) ?>"><?= $lines($b['subtitle']) ?></p>
              </div>
              <div class="box-price <?= e($b['price_color']) ?> fw-medium"><span class="text">KSh&nbsp;</span><span class="price"><?= e($b['price']) ?></span></div>
            </div></a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="sw-dot-default sw-pagination-categories justify-content-center spd4"></div>
    </div>
  </div>
</div>
