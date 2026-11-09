<?php
// Quick View opens on a default product until a card's quick view button is clicked;
// main.js then fills in the clicked product (images, details, variant options, price)
// from window.ONSUS.products.
$qv = shop_product((int) setting('quickview_default_product', '1'));
if ($qv) {
    register_product($qv);
}
$qvImages = $qv ? array_values(array_unique([$qv['img_src'], ...product_images((int) $qv['id'])])) : [];
$qvSpecs = $qv ? product_specs($qv) : [];
if ($qv && $qv['brand']) {
    array_unshift($qvSpecs, ['Brand', $qv['brand']]);
}
$qvBullets = $qv ? product_bullets($qv) : [];
?>
<div class="modal fade modalCentered modal-def modal-quick-view" id="quickView" data-product-id="<?= (int) ($qv['id'] ?? 0) ?>">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content flex-md-row">
      <span class="icon-close icon-close-popup link" data-bs-dismiss="modal"></span>
      <div class="quickview-image">
        <div class="product-thumb-slider">
          <div class="swiper tf-product-view-main" data-qv-main>
            <div class="swiper-wrapper">
              <?php foreach ($qvImages as $image): ?>
              <div class="swiper-slide"><a class="d-block tf-image-view" href="<?= $qv ? e(product_url($qv)) : './' ?>"><?= img($image, 900, 1000, 'lazyload') ?></a></div>
              <?php endforeach; ?>
            </div>
            <div class="swiper-button-prev nav-swiper-2 single-slide-prev snbpqv"></div>
            <div class="swiper-button-next nav-swiper-2 single-slide-next snbnqv"></div>
          </div>
          <div class="swiper tf-product-view-thumbs" data-direction="horizontal" data-qv-thumbs>
            <div class="swiper-wrapper">
              <?php foreach ($qvImages as $image): ?>
              <div class="swiper-slide"><div class="item"><?= img($image, 900, 1000) ?></div></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="quickview-info-wrap">
        <div class="quickview-info-inner">
          <div class="tf-product-info-content">
            <div class="infor-heading">
              <p class="caption<?= empty($qv['category']) ? ' d-none' : '' ?>" data-qv-category-wrap>Categories:<a class="link text-secondary" href="./" data-qv-category><?= e($qv['category'] ?? '') ?></a></p>
              <h5 class="product-info-name fw-semibold"><a class="link" href="<?= $qv ? e(product_url($qv)) : './' ?>" data-qv-title><?= e($qv['title'] ?? '') ?></a></h5>
              <ul class="product-info-rate-wrap" data-qv-meta>
                <?php if (($qv['sold'] ?? null) !== null): ?><li><p class="caption text-main-2">Sold: <?= (int) $qv['sold'] ?></p></li><?php endif; ?>
                <?php if (!empty($qv['sku'])): ?><li><p class="caption text-main-2">SKU: <?= e($qv['sku']) ?></p></li><?php endif; ?>
              </ul>
            </div>
            <div class="infor-center">
              <div class="product-info-price" data-qv-price>
                <h4 class="text-primary"><?= money($qv['price'] ?? 0) ?></h4><?php if ($qv && has_old_price($qv)): ?><span class="price-text text-main-2 old-price"><?= money($qv['old_price']) ?></span><?php endif; ?>
              </div>
              <div class="qv-promo<?= empty($qv['promotion']) ? ' d-none' : '' ?>" data-qv-promo><?= e($qv['promotion']['badge'] ?? $qv['promotion']['name'] ?? '') ?></div>
              <div class="qv-variants" data-qv-options></div>
              <ul class="product-fearture-list<?= $qvSpecs ? '' : ' d-none' ?>" data-qv-specs>
                <?php foreach ($qvSpecs as [$label, $value]): ?>
                <li><p class="body-md-2 fw-semibold"><?= e($label) ?></p><span class="body-text-3"><?= e($value) ?></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="infor-bottom<?= $qvBullets ? '' : ' d-none' ?>" data-qv-about>
              <h6 class="fw-semibold">About this item</h6>
              <ul class="product-about-list" data-qv-bullets>
                <?php foreach ($qvBullets as $line): ?>
                <li><p class="body-text-3"><?= e($line) ?></p></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
          <div class="box-quantity-wrap">
            <div class="wg-quantity">
              <span class="btn-quantity minus-btn" data-qv-minus><i class="icon-minus"></i></span><input class="quantity-product" type="text" readonly name="number" value="1" data-qv-qty /><span class="btn-quantity plus-btn" data-qv-plus><i class="icon-plus"></i></span>
            </div>
            <a href="#shoppingCart" class="tf-btn btn-gray" data-bs-toggle="offcanvas" data-qv-add><span class="text-white"><?= $qv && in_cart((int) $qv['id']) ? 'Already Added' : 'Add To Cart' ?></span></a>
          </div>
          <?php if (whatsapp_number()): ?>
          <a href="<?= e(whatsapp_url('Hi! I would like to order: ' . ($qv['title'] ?? ''))) ?>" class="tf-btn btn-whatsapp w-100 mt-3" target="_blank" rel="noopener" data-qv-whatsapp><?= whatsapp_icon(20) ?><span class="text-white">Order via WhatsApp</span></a>
          <?php endif; ?>
          <a class="link text-secondary body-md-2 d-inline-block mt-3" href="<?= $qv ? e(product_url($qv)) : './' ?>" data-qv-details>View full details →</a>
        </div>
      </div>
    </div>
  </div>
</div>
