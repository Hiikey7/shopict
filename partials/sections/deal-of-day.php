<?php if (!section_visible('deal_of_day')) { return; } ?>
<section class="tf-sp-2 pt-xl-0">
  <div class="container">
    <div class="section-wrap wow fadeInUp" data-wow-delay="0s">
      <div class="flat-title">
        <h5 class="fw-semibold text-primary flat-title-has-icon"><span class="icon"><i class="icon-fire tf-ani-tada"></i></span><?= e(section_title('deal_of_day')) ?></h5>
        <div class="box-btn-slide relative">
          <div class="swiper-button-prev nav-swiper nav-prev-products snbp35"><i class="icon-arrow-left-lg"></i></div>
          <div class="swiper-button-next nav-swiper nav-next-products snbn35"><i class="icon-arrow-right-lg"></i></div>
        </div>
      </div>
      <div class="swiper tf-sw-products" data-swiper="deal">
        <div class="swiper-wrapper">
          <?php foreach (section_products('deal_of_day') as $p): ?>
          <div class="swiper-slide">
            <div class="card-product style-img-border style-row type-row-2 flex-lg-row">
              <div class="card-product-wrapper">
                <a class="product-img" href="<?= e(product_url($p)) ?>"><?= product_images_pair($p) ?></a>
                <?php if ($tag = discount_tag($p)): ?><span class="sale-tag z-5"><?= e($tag) ?></span><?php endif; ?>
              </div>
              <div class="card-product-info gap-xxl-51">
                <div class="box-title">
                  <div class="bg-white relative z-5">
                    <p class="caption text-main-2 font-2"><?= e($p['category']) ?></p>
                    <a class="name-product product-title fw-semibold text-secondary link" href="<?= e(product_url($p)) ?>"><?= e($p['title']) ?></a>
                  </div>
                  <p class="price-wrap fw-medium"><span class="new-price price-text fw-medium text-primary mb-0"><?= money($p['price']) ?></span><?php if (has_old_price($p)): ?><span class="old-price body-md-2 text-main-2 fw-normal"><?= money($p['old_price']) ?></span><?php endif; ?></p>
                </div>
                <div class="box-infor-detail">
                  <div class="product-progress-sale gap-0">
                    <div class="box-quantity d-flex justify-content-between">
                      <p class="text-avaiable body-small">Available: <span class="fw-bold"><?= e($p['available']) ?></span></p>
                      <p class="text-avaiable body-small">Sold: <span class="fw-bold"><?= e($p['sold']) ?></span></p>
                    </div>
                    <div class="progress-sold progress style-3" role="progressbar" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-primary" style="width:<?= e($p['progress_width']) ?>"></div></div>
                  </div>
                  <div class="countdown-box d-flex gap-10 flex-wrap">
                    <div class="d-flex gap-10 flex-xxl-column gap-xxl-0">
                      <h6 class="fw-semibold">Hurry Up</h6>
                      <p class="body-small text-main-2">offer ends in:</p>
                    </div>
                    <div class="js-countdown" data-timer="<?= e($p['countdown_timer']) ?>" data-labels="Days,Hours,Mins,Secs"><?= countdown_placeholder() ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="d-flex d-xl-none sw-dot-default sw-pagination-products justify-content-center spd35"></div>
      </div>
    </div>
  </div>
</section>
