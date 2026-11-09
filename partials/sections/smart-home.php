<?php
/**
 * Featured Products (section code smart_home): the promo banner fills two columns × two rows of a six-column grid;
 * products fill the rest (4 + 4 beside the banner, then four full rows of 6 below).
 * Tablets show 12 products, phones 8 (the banner is hidden below 1200px, as before).
 */
if (!section_visible('smart_home')) {
    return;
}
$promo = banner('smart_home');
$products = section_products('smart_home');
?>
<section class="tf-sp-2">
  <div class="container">
    <div class="flat-title wow fadeInUp" data-wow-delay="0s">
      <h5 class="fw-semibold"><?= e(section_title('smart_home')) ?></h5>
      <a class="link text-secondary body-md-2 fw-semibold" href="shop.php">View all →</a>
    </div>
    <div class="smart-grid<?= $promo ? ' has-banner' : '' ?>">
      <?php if ($promo): ?>
      <div class="smart-grid-banner banner-product-2 d-none d-xl-block wow fadeInLeft" data-wow-delay="0s">
        <a href="<?= e($promo['link_url'] ?: '#') ?>" class="image h-100"><?= $promo['image'] ? img($promo['image'], 400, 650, 'lazyload') : '' ?></a>
        <div class="content">
          <div class="box-top"><p class="text-white body-text-3 text-uppercase fw-2" style="letter-spacing: 9.8px"><?= e($promo['eyebrow']) ?></p></div>
          <div class="box-bottom">
            <a class="link h3 mb-0 lh-xl-49 text-white" href="<?= e($promo['link_url'] ?: './') ?>"><?= lines($promo['title'], '<br />') ?></a>
            <div class="box-price"><span class="text fw-2 price-text text-white"><?= e($promo['price_label']) ?></span><span class="h2 mb-0 fw-bold text-price"><?= e($promo['price']) ?></span></div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php foreach ($products as $i => $p):
          // How many show: 8 on phones, 12 on tablets, 32 with the banner on computers (36 without it).
          $cls = ($i >= 8 ? ' sg-from-md' : '') . ($i >= 12 ? ' sg-from-xl' : '') . ($i >= ($promo ? 32 : 36) ? ' sg-never' : '');
      ?>
      <div class="smart-grid-item<?= $cls ?>"><?php partial('cards/product', ['p' => $p, 'class' => 'style-small style-img-border', 'delay' => false]); ?></div>
      <?php endforeach; ?>
    </div>
    <a class="smart-more d-md-none" href="shop.php">Show more<svg width="14" height="14" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M4.5 2.5 8 6l-3.5 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
  </div>
</section>
