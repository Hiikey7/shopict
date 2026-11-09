<?php
if (!section_visible('hot_grid')) {
    return;
}
// The grid has fixed slots: one card, the highlighted card, then three cards.
$gridClasses = ['grid-item1', 'grid-item3', 'grid-item4', 'grid-item5'];
$grid = array_slice(section_products('hot_grid'), 0, 4);
$featured = section_visible('hot_featured') ? (section_products('hot_featured')[0] ?? null) : null;
$rowCard = fn ($p) => partial('cards/row', ['p' => $p, 'class' => 'style-3 style-row h-100 flex-sm-row', 'nameClass' => 'line-clamp-xl-3']);
?>
<section class="tf-sp-6 position-relative">
  <div class="relative z-5">
    <div class="container">
      <div class="flat-title mb-20 wow fadeInUp" data-wow-delay="0s"><h5 class="fw-semibold"><?= e(section_title('hot_grid')) ?></h5></div>
      <div class="grid-cls grid-cls-v2 wow fadeInUp" data-wow-delay="0s">
        <?php foreach (array_slice($grid, 0, 1) as $p): ?>
        <div class="<?= $gridClasses[0] ?>"><?php $rowCard($p); ?></div>
        <?php endforeach; ?>
        <?php if ($featured): ?>
        <div class="grid-item2"><?php partial('cards/thumbs', ['p' => $featured, 'class' => 'card-product bg-white style-border type-bd-2 style-thums-2 h-100', 'typeClass' => 'type-right-2']); ?></div>
        <?php endif; ?>
        <?php foreach (array_slice($grid, 1, 3) as $i => $p): ?>
        <div class="<?= $gridClasses[$i + 1] ?>"><?php $rowCard($p); ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="tf-overlay"><?= img('images/section/parallax-4.jpg', 1920, 900, 'lazyload effect-paralax') ?></div>
</section>
