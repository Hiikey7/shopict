<?php
// Promo slider: moves by itself in an endless loop. Desktop shows two full ads with half an ad
// peeking in on each side. Slides alternate between the template's two layouts.
$slides = banners('promo_slider');
if (!$slides) {
    return;
}
// The loop needs enough slides to fill the view on both sides: repeat short lists.
$list = $slides;
while (count($list) < 6) {
    $list = array_merge($list, $slides);
}
?>
<section>
  <div class="container promo-loop">
    <div class="swiper tf-sw-categories promo-loop-swiper" data-swiper="banner-laptops">
      <div class="swiper-wrapper">
        <?php foreach ($list as $i => $b): $alt = $i % 2 === 1; ?>
        <div class="swiper-slide">
          <a class="banner-image-product-2 <?= $alt ? 'style-2 ' : '' ?>type-sp-2 hover-img d-block" href="<?= e($b['link_url'] ?: './') ?>"><div class="item-image img-style overflow-visible <?= $alt ? 'position3' : 'position2' ?>">
              <?= $b['image'] ? img($b['image'], $alt ? 231 : 239, $alt ? 230 : 227, 'lazyload') : '' ?>
            </div>
            <div
              class="item-banner has-bg-img"
              style="<?= $b['bg_image'] ? 'background-image: url(&quot;' . e(asset((string) $b['bg_image'])) . '&quot;); background-size: cover; background-repeat: no-repeat;' : '' ?><?= $b['bg_hex'] ? ' background-color: ' . e($b['bg_hex']) . ';' : '' ?>"
            >
              <?php if ($alt): ?>
              <div class="inner">
                <h4 class="name fw-normal <?= $b['text_hex'] ? '' : 'text-white ' ?>lh-lg-38 text-xxl-center text-line-clamp-2"<?= $b['text_hex'] ? ' style="color: ' . e($b['text_hex']) . '"' : '' ?>><?= e($b['title']) ?><br class="d-none d-sm-block" /><span class="fw-bold"> <?= e($b['title_highlight']) ?> </span></h4>
              </div>
              <?php else: ?>
              <div class="inner justify-content-xl-end">
                <h4 class="name fw-normal <?= $b['text_hex'] ? '' : 'text-white ' ?>lh-lg-38 text-xl-end"<?= $b['text_hex'] ? ' style="color: ' . e($b['text_hex']) . '"' : '' ?>><?= e($b['title']) ?><br /><span class="fw-bold"> <?= e($b['title_highlight']) ?> </span></h4>
              </div>
              <?php endif; ?>
            </div></a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
