<?php
/**
 * "Authorised Brands": brand logos scrolling slowly and continuously (Admin → Brands).
 * A brand without a logo is shown as its name; each one links to its products in the shop.
 * The strip is two identical halves moved left by half their width, so the loop has no seam;
 * short lists are repeated so each half is wider than the screen.
 */
if (!section_visible('brands')) {
    return;
}
$brands = db()->query('SELECT * FROM brands WHERE is_active = 1 ORDER BY position, id')->fetchAll();
if (!$brands) {
    return;
}
$half = $brands;
while (count($half) < 12) {
    $half = array_merge($half, $brands);
}
$tiles = function (array $list, bool $copy): string {
    $out = '';
    foreach ($list as $b) {
        $href = $b['link_url'] && $b['link_url'] !== '#' ? $b['link_url'] : brand_url($b['name']); // the brand page
        $inner = $b['logo']
            ? '<img src="' . e(asset($b['logo'])) . '" alt="' . e($b['name']) . '" loading="lazy" decoding="async">'
            : '<span class="brand-wordmark">' . e($b['name']) . '</span>';
        $out .= '<li><a class="brand-tile" href="' . e($href) . '" title="' . e($b['name']) . '"' . ($copy ? ' tabindex="-1"' : '') . '>' . $inner . '</a></li>';
    }
    return $out;
};
$seconds = max(20, count($half) * 4); // about 4 seconds per logo
?>
<section class="tf-sp-2 pt-0 brands-section" aria-labelledby="brandsTitle">
  <div class="container">
    <div class="flat-title">
      <h5 class="fw-semibold" id="brandsTitle"><?= e(section_title('brands')) ?></h5>
    </div>
    <div class="brands-marquee">
      <div class="brands-track" style="--brands-duration: <?= $seconds ?>s">
        <ul class="brands-list"><?= $tiles($half, false) ?></ul>
        <ul class="brands-list" aria-hidden="true"><?= $tiles($half, true) ?></ul>
      </div>
    </div>
  </div>
</section>
