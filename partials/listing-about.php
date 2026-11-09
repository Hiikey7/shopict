<?php
/**
 * Under the products of a category or brand page: what the page is about, buying advice, and links to related
 * categories, brands and buying guides. Text written in Admin → SEO is used first; otherwise it is written from
 * the products on the page (count, prices, brands) and the built-in buying advice for each main category.
 *
 * @var array|null $cat  @var array|null $brand  @var array $brandCats  @var array $tree
 * @var int $total  @var int|null $min  @var int|null $max  @var array $brands
 * @var bool $embed  inside a tab of the About page: no card around it, the heading is an H2 without "About"
 */
$embed = $embed ?? false;
if (!$cat && !$brand) {
    return;
}
$name = $cat ? $cat['name'] : $brand['name'];
$row = $cat ?: ($brand['row'] ?? []);
$intro = trim((string) ($row['intro'] ?? ''));
$introHtml = $intro !== '' ? seo_markdown($intro, 3) : '<p>' . e(seo_listing_intro($cat ? $cat['name'] : $brand['name'] . ' products', $total, $min, $max, $brands)) . '</p>';
$guide = $cat ? trim((string) ($cat['guide'] ?? '')) : '';
if ($cat && $guide === '' && $cat['parent_id'] === null) {
    $guide = seo_category_guides()[$cat['slug']] ?? '';
}

// Related links
$typeLinks = [];
$typeTitle = '';
if ($cat) {
    $main = category_main($cat);
    foreach ($tree as $m) {
        if ($m['id'] === $main['id']) {
            foreach ($m['children'] as $sub) {
                if ($sub['id'] !== $cat['id']) {
                    $typeLinks[] = [$sub['name'], category_url($sub)];
                }
            }
        }
    }
    if ($cat['parent_id'] !== null) {
        array_unshift($typeLinks, ['All ' . $main['name'], category_url($main)]);
    }
    $typeTitle = $cat['parent_id'] === null ? 'Shop by type' : 'More ' . mb_strtolower($main['name']);
} else {
    foreach ($brandCats as $m) {
        $typeLinks[] = [$brand['name'] . ' ' . mb_strtolower($m['name']), shop_url(['cat' => $m['slug'], 'make' => $brand['slug']])];
    }
    $typeTitle = $brand['name'] . ' by category';
}
$brandLinks = [];
foreach ($brands as $b) {
    if (brand_by_slug(slugify($b))) {
        $brandLinks[] = [$b, brand_url($b)];
    }
}
$guides = $cat ? posts_published(4, $cat['id']) : [];
if ($cat && $cat['parent_id'] !== null && count($guides) < 4) {
    $guides = array_merge($guides, posts_published(4 - count($guides), category_main($cat)['id']));
}
?>
<section class="<?= $embed ? 'listing-about listing-about-embed' : 'co-card listing-about' ?>" aria-labelledby="listingAboutTitle-<?= e($cat['slug'] ?? $brand['slug']) ?>">
  <h2 class="listing-about-title" id="listingAboutTitle-<?= e($cat['slug'] ?? $brand['slug']) ?>"><?= $embed ? e($name) . ' at ' . e(seo_store()) : 'About ' . e($name) ?></h2>
  <div class="listing-about-text"><?= $introHtml ?></div>
  <?php if ($guide !== ''): ?><div class="listing-about-text listing-guide"><?= seo_markdown($guide, 3) ?></div><?php endif; ?>
  <?php if ($typeLinks): ?>
  <h3 class="listing-about-sub"><?= e($typeTitle) ?></h3>
  <ul class="listing-links"><?php foreach ($typeLinks as [$label, $href]): ?><li><a href="<?= e($href) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
  <?php endif; ?>
  <?php if ($brandLinks): ?>
  <h3 class="listing-about-sub">Shop by brand</h3>
  <ul class="listing-links"><?php foreach ($brandLinks as [$label, $href]): ?><li><a href="<?= e($href) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
  <?php endif; ?>
  <?php if ($guides): ?>
  <h3 class="listing-about-sub">Buying guides</h3>
  <ul class="listing-links listing-links-guides"><?php foreach ($guides as $g): ?><li><a href="<?= e(post_url($g)) ?>"><?= e($g['title']) ?></a></li><?php endforeach; ?></ul>
  <?php endif; ?>
</section>
