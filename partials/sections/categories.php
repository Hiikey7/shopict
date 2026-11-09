<?php
if (!section_visible('categories')) {
    return;
}
// One tile per main product category (Admin → Categories); each opens that category on the shop page.
// A fixed grid filling the content width: 6 across on desktop, 3 across on tablets and phones.
$categories = db()->query('SELECT c.*, pc.slug FROM categories c LEFT JOIN product_categories pc ON pc.id = c.category_id ORDER BY c.position, c.id')->fetchAll();
if (!$categories) {
    return;
} ?>
<section class="">
  <div class="container">
    <div class="flat-title wow fadeInUp" data-wow-delay="0s">
      <h5 class="fw-semibold"><?= e(section_title('categories')) ?></h5>
    </div>
    <ul class="home-cats wow fadeInUp" data-wow-delay="0s">
      <?php foreach ($categories as $c): $href = $c['slug'] ? shop_url(['cat' => $c['slug']]) : 'shop.php'; ?>
      <li>
        <a class="home-cat hover-img" href="<?= e($href) ?>">
          <span class="home-cat-img img-style-2"><?= img($c['img_src'], 296, 296) ?></span>
          <span class="home-cat-name body-text-3"><?= e(str_replace("\n", ' ', $c['title'])) ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
