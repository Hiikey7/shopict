<?php
$mains = array_slice(banners('hero_main'), 0, 3);
$sides = array_slice(banners('hero_side_1'), 0, 2);
$side2 = banner('hero_side_2');
if (count($sides) >= 2) {
    $side2 = null; // the column is full
}
if (!$mains && !$sides && !$side2) {
    return;
}
$bg = fn (?string $path) => $path ? ' style="background-image: url(' . e(asset($path)) . ')"' : '';
?>
<div class="has-bg-img tf-sp-4"<?= $bg(setting('hero_bg_image')) ?>>
  <div class="container">
    <div class="slide-wrap-2 flex-xl-nowrap">
      <?php if ($mains): ?>
      <div class="hero-main-slider swiper" data-swiper="hero-main" role="region" aria-roledescription="carousel" aria-label="Featured products">
        <div class="swiper-wrapper">
          <?php foreach ($mains as $i => $main): ?>
          <div class="swiper-slide" role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> of <?= count($mains) ?>">
              <div class="banner-slide-product flex-md-nowrap hover-img has-bg-img"<?= $bg($main['bg_image']) ?>>
                <?php if (!empty($main['link_url']) && $main['link_url'] !== '#'): ?>
                <a href="<?= e($main['link_url']) ?>" class="image img-style overflow-visible"><?= $main['image'] ? img($main['image'], 794, 794, 'lazyload', '', 'color:transparent', $i === 0 ? 'eager' : 'lazy') : '' ?></a>
                <?php else: ?>
                <div class="image img-style overflow-visible"><?= $main['image'] ? img($main['image'], 794, 794, 'lazyload', '', 'color:transparent', $i === 0 ? 'eager' : 'lazy') : '' ?></div>
                <?php endif; ?>
                <div class="content text-center">
                  <div class="box-title hero-main-copy">
                    <p class="fw-semibold<?= $main['title_color'] ? '' : ' text-white' ?> h1 mb-0 lh-xxl-73"<?= $main['title_color'] ? ' style="color: ' . e($main['title_color']) . '"' : '' ?>><?= e($main['title']) ?><?php if ($main['title_highlight']): ?> <span class="fw-1"<?= $main['title_highlight_color'] ? ' style="color: ' . e($main['title_highlight_color']) . '"' : '' ?>><?= e($main['title_highlight']) ?></span><?php endif; ?></p>
                    <p class="fw-lighter<?= $main['subtitle_color'] ? '' : ' text-white' ?> fw-1 main-title-3 lh-xl-33"<?= $main['subtitle_color'] ? ' style="color: ' . e($main['subtitle_color']) . '"' : '' ?>><?= e($main['subtitle']) ?></p>
                    <?php if (!empty($main['hero_price_visible']) && ($main['price_label'] || $main['price'])): ?>
                    <p class="fw-semibold h3 mb-0 hero-main-price" style="color: <?= e($main['hero_price_color'] ?: '#ffffff') ?>"><?php if (($main['price_label'] ?? '') === 'Now'): ?><span><?= e($main['price_label']) ?><?= $main['price'] ? ' ' : '' ?></span><?php endif; ?><?php if ($main['price']): ?><span><?= e(money_text($main['price'])) ?></span><?php endif; ?></p>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex justify-content-center"><span class="br-line"></span></div>
                  <?php $features = json_decode((string) $main['meta'], true) ?: []; ?>
                  <?php if ($features): ?>
                  <ul class="property-list">
                    <?php foreach ($features as $feature): ?>
                    <li>
                      <span class="icon"><?= feature_icon($feature) ?></span>
                      <div class="box-infor">
                        <p class="fw-semibold text-white body-text-3 lh-xl-17"><?= e($feature['label'] ?? '') ?></p>
                        <p class="fw-2 text-white caption lh-xl-15"><?= e($feature['value'] ?? '') ?></p>
                      </div>
                    </li>
                    <?php endforeach; ?>
                  </ul>
                  <?php endif; ?>
                  <?php if ($main['button_text'] && !empty($main['link_url']) && $main['link_url'] !== '#'): ?>
                  <div class="box-btn">
                    <a href="<?= e($main['link_url']) ?>" class="tf-btn d-inline-flex style-2 hover-link-icon hero-button-<?= ($main['button_color'] ?? 'white') === 'red' ? 'red' : 'white' ?>"><span class="fw-bold caption text-uppercase"><?= e($main['button_text']) ?></span><i class="icon-arrow-right1 fs-12 link-icon"></i></a>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($mains) > 1): ?>
        <div class="hero-main-dots sw-dot-default"></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="wrap flex-xl-column">
        <?php foreach ($sides as $side1):
            $linked = $side1['product_id'] ? shop_product((int) $side1['product_id']) : null;
            $sideName = $side1['title'] ?: ($linked['title'] ?? '');
            $sidePrice = $side1['price'] ?: ($linked ? money($linked['price']) : '');
            $sideLink = ($side1['link_url'] && $side1['link_url'] !== '#') ? $side1['link_url'] : ($linked ? product_url($linked) : '#');
            $sideImage = $side1['image'] ?: ($linked['img_src'] ?? null);
            $hasSideLink = $sideLink !== '' && $sideLink !== '#';
        ?>
        <?php
            $sideText = $side1['text_hex'] ? ' style="color: ' . e($side1['text_hex']) . '"' : '';
            $sideWhite = $side1['text_hex'] ? '' : ' text-white';
            $sideBg = trim(($side1['bg_image'] ? 'background-image: url(' . e(asset($side1['bg_image'])) . ');' : '') . ($side1['bg_hex'] ? ' background-color: ' . e($side1['bg_hex']) . ';' : ''));
        ?>
        <div class="cls-product hero-side-card has-bg-img hover-img"<?= $sideBg !== '' ? ' style="' . $sideBg . '"' : '' ?>>
          <div class="content">
            <div class="box-title">
              <p class="font-4 text-uppercase<?= $sideWhite ?> title-sidebar hero-side-company"<?= $sideText ?>><?= e($side1['eyebrow']) ?></p>
              <p class="font-4 fw-bold text-uppercase<?= $sideWhite ?> main-title-3 hero-side-name"<?= $sideText ?>><?= e($sideName) ?></p>
              <?php if ($sidePrice !== ''): ?><p class="font-4 text-uppercase<?= $sideWhite ?> title-sidebar hero-side-price"<?= $sideText ?>><?= e(money_text($sidePrice)) ?></p><?php endif; ?>
            </div>
            <?php if ($side1['button_text'] && $hasSideLink): ?>
            <div class="box-btn">
              <a href="<?= e($sideLink) ?>" class="tf-btn d-inline-flex style-2 hover-link-icon hero-button-<?= ($side1['button_color'] ?? 'white') === 'red' ? 'red' : 'white' ?>"><span class="fw-bold small-text text-uppercase"><?= e($side1['button_text']) ?></span><i class="icon-arrow-right1 fs-12 link-icon"></i></a>
            </div>
            <?php endif; ?>
          </div>
          <?php if ($hasSideLink): ?>
          <a href="<?= e($sideLink) ?>" class="d-inline-flex w-100 img-style"><?= $sideImage ? img($sideImage, 688, 688, 'lazyload', $sideName) : '' ?></a>
          <?php else: ?>
          <div class="d-inline-flex w-100 img-style"><?= $sideImage ? img($sideImage, 688, 688, 'lazyload', $sideName) : '' ?></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if ($side2): ?>
        <div class="cls-product style-2 hero-side-card has-bg-img hover-img"<?= $bg($side2['bg_image']) ?>>
          <div class="content">
            <div class="box-title">
              <p class="font-5 text-uppercase text-white caption lh-xl-14 fw-light" style="letter-spacing: 4.2px"><?= e($side2['eyebrow']) ?></p>
              <p class="font-5 fw-medium text-uppercase text-white main-title-2 lh-xl-31 mb-xl--5"><?= e($side2['title']) ?></p>
              <p class="font-5 text-uppercase text-white body-small lh-16"><?= e($side2['subtitle']) ?></p>
            </div>
            <?php if ($side2['price']): ?>
            <div class="box-price">
              <p class="font-5 fw-light text-white"><span class="text"><?= e($side2['price_label']) ?></span><span class="fw-medium h3 mb-0 text-third-2 lh-xl-49"><?= e(money_text($side2['price'])) ?></span></p>
            </div>
            <?php endif; ?>
            <?php if ($side2['button_text']): ?>
            <div class="box-btn">
              <a href="<?= e($side2['link_url'] ?: '#') ?>" class="tf-btn d-inline-flex style-2 hover-link-icon"><span class="text-white fw-bold small-text"><?= e($side2['button_text']) ?></span><i class="icon-arrow-right1 text-white fs-12 link-icon"></i></a>
            </div>
            <?php endif; ?>
          </div>
          <a href="<?= e($side2['link_url'] ?: '#') ?>" class="d-inline-flex w-100 img-style"><?= $side2['image'] ? img($side2['image'], 808, 808, 'lazyload') : '' ?></a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
