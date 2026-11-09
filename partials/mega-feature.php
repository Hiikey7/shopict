<?php
/**
 * "Featured" product carousel used inside the category menus (one product at a time).
 *
 * @var array  $featured products (with promotion prices applied)
 * @var string $label    category name, for screen readers
 */
if (!$featured) {
    return;
}
?>
<div class="mega-feature" data-mega-feature aria-roledescription="carousel" aria-label="Featured <?= e($label) ?>">
  <p class="mega-feature-title">Featured</p>
  <div class="mega-feature-slides">
    <?php foreach ($featured as $fi => $fp): ?>
    <a class="mega-feature-slide<?= $fi === 0 ? ' is-active' : '' ?>" href="<?= e(product_url($fp)) ?>" data-mf-slide aria-roledescription="slide" aria-label="<?= $fi + 1 ?> of <?= count($featured) ?>"<?= $fi === 0 ? '' : ' aria-hidden="true" tabindex="-1"' ?>>
      <span class="mega-feature-img"><?= img($fp['img_src'], 300, 300, '', $fp['title']) ?></span>
      <span class="mega-feature-name"><?= e($fp['title']) ?></span>
      <span class="mega-feature-price"><?= money($fp['price']) ?><?php if (has_old_price($fp)): ?> <s><?= money($fp['old_price']) ?></s><?php endif; ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if (count($featured) > 1): ?>
  <div class="mega-feature-nav">
    <button type="button" class="mega-feature-arrow" data-mf-prev aria-label="Previous product">‹</button>
    <span class="mega-feature-dots">
      <?php foreach ($featured as $fi => $fp): ?><button type="button" class="<?= $fi === 0 ? 'is-active' : '' ?>" data-mf-dot="<?= $fi ?>" aria-label="Show product <?= $fi + 1 ?>"></button><?php endforeach; ?>
    </span>
    <button type="button" class="mega-feature-arrow" data-mf-next aria-label="Next product">›</button>
  </div>
  <?php endif; ?>
</div>
