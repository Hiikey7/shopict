<?php $features = db()->query('SELECT * FROM features ORDER BY position')->fetchAll(); ?>
<div class="tf-sp-2 pt-0">
  <div class="container">
    <div class="swiper tf-sw-iconbox" data-swiper="features">
      <div class="swiper-wrapper">
        <?php foreach ($features as $f): ?>
        <div class="swiper-slide">
          <div class="tf-icon-box wow fadeInLeft" data-wow-delay="<?= e($f['wow_delay']) ?>">
            <div class="icon-box"><i class="<?= e($f['icon_class']) ?> text-black"></i></div>
            <div class="content">
              <p class="body-text fw-semibold"><?= e($f['title']) ?></p>
              <p class="body-text-3 text-cl-1">Velit ullamco elit et aliqua magna</p>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="sw-pagination-iconbox sw-dot-default justify-content-center spf2"></div>
    </div>
  </div>
</div>
