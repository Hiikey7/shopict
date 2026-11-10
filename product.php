<?php
// Product detail page: gallery, price, variant options, quantity, add to cart / WhatsApp,
// description and specifications (tabs) and related products.
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
$id = (int) ($_GET['id'] ?? 0);
$p = $id ? shop_product($id) : null;

// One address per product: old "product.php?id=369" links and outdated names go to the current link.
if ($p && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $asked = basename(rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
    if ($asked !== product_url($p)) {
        $query = $_GET;
        unset($query['id']);
        header('Location: ' . product_url($p) . ($query ? '?' . http_build_query($query) : ''), true, 301);
        exit;
    }
}

if (!$p) {
    http_response_code(404);
    partial('page-start', ['title' => 'Product not found · ' . setting('store_name', 'Shop ICT Gadgets')]);
    ?>
    <section class="tf-sp-2 co-page">
      <div class="container">
        <div class="co-card text-center py-5">
          <h5 class="fw-semibold mb-2">This product isn't available</h5>
          <p class="body-text-3 text-main-2 mb-4">It may have been removed or hidden. Have a look at our other products.</p>
          <a class="tf-btn d-inline-flex" href="./"><span class="text-white">Back to the shop</span></a>
        </div>
      </div>
    </section>
    <?php
    partial('page-end');
    exit;
}

track_page_view('/' . product_url($p), (int) $p['id']);
track_event('product_view', (int) $p['id'], null, '/' . product_url($p));
register_product($p);

$images = array_values(array_unique([$p['img_src'], ...product_images((int) $p['id'])]));
$specs = product_specs($p);
if ($p['brand']) {
    array_unshift($specs, ['Brand', $p['brand']]);
}
$bullets = product_bullets($p);
$related = related_products($p);

// ---- Search engines and AI assistants: title, description, structured data (prices, stock, variants, brand,
// condition, returns), breadcrumbs and questions. Written by includes/seo.php from the product's details; anything
// typed in Admin → product → "Search engines (SEO)" is used instead.
$store = seo_store();
$offerFacts = seo_offer_facts($p);
$seo = seo_product($p, $offerFacts['low']);
$productUrl = seo_abs(product_url($p));
$alts = seo_image_alts($p, count($images));
// A link to one option (?v=12, used by Google Shopping) opens the page with that option and its price shown.
$chosenVariant = null;
foreach ($offerFacts['variants'] as $v) {
    if (isset($_GET['v']) && $v['id'] === (int) $_GET['v']) {
        $chosenVariant = $v;
        $p['price'] = $v['price'];
        $p['old_price'] = $v['oldPrice'];
    }
}
$cat = category_find(isset($p['category_id']) ? (int) $p['category_id'] : null);
$catMain = category_main($cat);
$crumbs = [['Home', seo_site_url() . '/']];
if ($cat && $catMain && $catMain['id'] !== $cat['id']) {
    $crumbs[] = [$catMain['name'], seo_abs(category_url($catMain))];
}
if ($cat) {
    $crumbs[] = [$cat['name'], seo_abs(category_url($cat))];
}
$crumbs[] = [$p['title'], $productUrl];
$faqs = $seo['faqs'];
$brandName = $p['brand'] ? brand_display((string) $p['brand']) : null;
$brandPage = $brandName && brand_by_slug(slugify($brandName)) ? brand_url($brandName) : null;
// "More to explore": the product's categories, its brand and related articles (links search engines follow too).
$explore = [];
if ($cat) {
    $explore[] = [$cat['name'], category_url($cat)];
}
if ($catMain && (!$cat || $catMain['id'] !== $cat['id'])) {
    $explore[] = ['All ' . mb_strtolower($catMain['name']), category_url($catMain)];
}
foreach (product_category_ids($p) as $cid) {
    $extra = category_find($cid);
    if ($extra && $extra['is_active'] && (!$cat || $extra['id'] !== $cat['id']) && count($explore) < 5) {
        $explore[] = [$extra['name'], category_url($extra)];
    }
}
if ($brandPage) {
    $explore[] = ['More from ' . $brandName, $brandPage];
}
foreach (posts_for_product($p) as $article) {
    $explore[] = ['Article: ' . $article['title'], post_url($article)];
}
$explore = array_values(array_column($explore, null, 1)); // no link twice
$jsonLd = array_values(array_filter([seo_product_ld($p, $images, $specs, $bullets, $productUrl), seo_breadcrumb_ld($crumbs), seo_faq_ld($faqs)]));

partial('page-start', [
    'title'       => $seo['title'],
    'description' => $seo['description'],
    'canonical'   => product_url($p),
    'robots'      => !empty($p['noindex']) ? 'noindex, follow' : null,
    'ogImage'     => $p['img_src'],
    'ogType'      => 'product',
    'jsonLd'      => $jsonLd,
]);
?>
<section class="tf-sp-2 co-page pv-page">
  <div class="container">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb">
      <a class="link co-crumb-link" href="./" title="Home">Home</a><span class="co-crumb-separator" aria-hidden="true">/</span>
      <?php if ($cat && $catMain && $catMain['id'] !== $cat['id']): ?><a class="link co-crumb-link" href="<?= e(category_url($catMain)) ?>" title="<?= e($catMain['name']) ?>"><?= e($catMain['name']) ?></a><span class="co-crumb-separator" aria-hidden="true">/</span><?php endif; ?>
      <?php if ($cat): ?><a class="link co-crumb-link" href="<?= e(category_url($cat)) ?>" title="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></a><span class="co-crumb-separator" aria-hidden="true">/</span><?php elseif ($p['category']): ?><span class="co-crumb-link" title="<?= e($p['category']) ?>"><?= e($p['category']) ?></span><span class="co-crumb-separator" aria-hidden="true">/</span><?php endif; ?>
      <span class="co-crumb-current text-truncate" title="<?= e($p['title']) ?>" aria-current="page"><?= e($p['title']) ?></span>
    </nav>

    <div class="row g-4 g-xl-5" data-product-page data-product-id="<?= (int) $p['id'] ?>">
      <div class="col-lg-5">
        <div class="product-thumb-slider pv-gallery" data-pv-gallery>
          <?php $saved = in_wishlist((int) $p["id"]); ?>
          <button type="button" class="pv-gallery-heart d-lg-none<?= $saved ? " is-on" : "" ?>" data-wishlist="<?= (int) $p["id"] ?>" data-wishlist-heart aria-pressed="<?= $saved ? "true" : "false" ?>" aria-label="<?= $saved ? "Remove from wishlist" : "Add to wishlist" ?>">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20s-7.5-4.6-9.2-9.4C1.7 7.4 3.8 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.2 0 5.3 2.9 4.2 6.1C19.5 15.4 12 20 12 20z"/></svg>
          </button>
          <div class="swiper tf-product-view-main">
            <div class="swiper-wrapper">
              <?php foreach ($images as $i => $image): $tag = img($image, 900, 900, '', $alts[$i]); ?>
              <div class="swiper-slide"><div class="pv-main-img"><?= $i === 0 ? str_replace('loading="lazy"', 'loading="eager" fetchpriority="high"', $tag) : $tag ?></div></div>
              <?php endforeach; ?>
            </div>
            <?php if (count($images) > 1): ?>
            <div class="swiper-button-prev nav-swiper-2 single-slide-prev snbppv"></div>
            <div class="swiper-button-next nav-swiper-2 single-slide-next snbnpv"></div>
            <?php endif; ?>
          </div>
          <div class="swiper tf-product-view-thumbs<?= count($images) > 1 ? '' : ' d-none' ?>">
            <div class="swiper-wrapper">
              <?php foreach ($images as $i => $image): ?>
              <div class="swiper-slide"><div class="item"><?= img($image, 200, 200, '', $alts[$i]) ?></div></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="pv-info" data-product-info>
          <?php if (!empty($p['is_preorder'])): ?><span class="preorder-pill">Pre-order</span><?php endif; ?>
          <h1 class="pv-title"><?= e($p['title']) ?></h1>

          <div class="pv-price-box">
            <div class="product-info-price" data-qv-price>
              <h4 class="text-primary"><?= money($p['price']) ?></h4><?php if (has_old_price($p)): ?><span class="price-text text-main-2 old-price"><?= money($p['old_price']) ?></span><span class="pv-off"><?= (int) round((1 - (float) $p['price'] / (float) $p['old_price']) * 100) ?>% off</span><?php endif; ?>
            </div>
          </div>

          <div class="pv-rows">
            <?php if ($p['brand']): ?>
            <div class="pv-row">
              <span class="pv-row-label">Brand:</span>
              <div class="pv-row-value"><a class="pv-brand link" href="<?= e($brandPage ?: shop_url(['q' => $p['brand']])) ?>"><?= e($brandName) ?></a></div>
            </div>
            <?php endif; ?>

            <div class="qv-variants pv-variants" data-qv-options></div>


            <div class="pv-row">
              <span class="pv-row-label" id="qtyLabel">Quantity</span>
              <div class="pv-row-value">
                <div class="wg-quantity pv-qty">
                  <span class="btn-quantity minus-btn" data-qv-minus role="button" tabindex="0" aria-label="Fewer"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M2 7h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span><input class="quantity-product" type="text" readonly name="number" value="1" aria-labelledby="qtyLabel" data-qv-qty /><span class="btn-quantity plus-btn" data-qv-plus role="button" tabindex="0" aria-label="More"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M2 7h10M7 2v10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                </div>
              </div>
            </div>
          </div>

          <div class="pv-actions">
            <a href="#shoppingCart" class="pv-btn pv-btn-cart" data-bs-toggle="offcanvas" data-qv-add><span><?= in_cart((int) $p['id']) ? 'Already Added' : (!empty($p['is_preorder']) ? 'Pre-order' : 'Add To Cart') ?></span></a>
            <a href="checkout.php" class="pv-btn pv-btn-buy" data-qv-buy><span>Buy Now</span></a>
            <?php $saved = in_wishlist((int) $p['id']); ?>
            <button type="button" class="pv-icon-btn pv-wish-desktop d-none d-lg-inline-flex<?= $saved ? ' is-on' : '' ?>" data-wishlist="<?= (int) $p['id'] ?>" data-wishlist-heart aria-pressed="<?= $saved ? 'true' : 'false' ?>" aria-label="<?= $saved ? 'Remove from wishlist' : 'Add to wishlist' ?>">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 20s-7.5-4.6-9.2-9.4C1.7 7.4 3.8 4.5 7 4.5c2 0 3.4 1.1 5 3 1.6-1.9 3-3 5-3 3.2 0 5.3 2.9 4.2 6.1C19.5 15.4 12 20 12 20z"/></svg>
            </button>
            <button type="button" class="pv-icon-btn pv-share" data-share data-share-title="<?= e($p['title']) ?>" aria-label="Share this product">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 5l6 6-6 6M20 11H11c-4 0-7 3-7 7v1"/></svg>
            </button>
          </div>
          <?php if (whatsapp_number()): ?>
          <a href="<?= e(whatsapp_url('Hi! I would like to order: ' . $p['title'])) ?>" class="tf-btn btn-whatsapp w-100 mt-3" target="_blank" rel="noopener" data-qv-whatsapp><?= whatsapp_icon(20) ?><span class="text-white">Order via WhatsApp</span></a>
          <?php endif; ?>
          <ul class="pv-perks body-text-3">
            <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span><strong>Free pickup</strong> at our shop<?php if ($addr = setting('store_address')): ?> · <a class="link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode(setting('map_query') ?: $addr)) ?>" target="_blank" rel="noopener"><?= e($addr) ?></a><?php endif; ?></span></li>
            <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 6h11v10H3zM14 9h4l3 3.5V16h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/></svg><span><strong>Countrywide delivery</strong> across Kenya</span></li>
          </ul>
        </div>
      </div>
    </div>

    <?php $richDescription = product_description_is_rich($p); if ($bullets || $richDescription || $specs || $faqs): $tabs = array_filter(['description' => $bullets || $richDescription ? 'Description' : null, 'specifications' => $specs ? 'Specifications' : null, 'faq' => $faqs ? 'Questions' : null]); $first = array_key_first($tabs); ?>
    <div class="co-card pv-tabs mt-4">
      <ul class="nav pv-tab-list" role="tablist">
        <?php foreach ($tabs as $key => $label): ?>
        <li class="nav-item pv-tab-<?= $key ?>" role="presentation">
          <button class="pv-tab<?= $key === $first ? ' active' : '' ?>" id="tab-<?= $key ?>" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#pane-<?= $key ?>" aria-controls="pane-<?= $key ?>" aria-selected="<?= $key === $first ? 'true' : 'false' ?>"><?= e($label) ?></button>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="tab-content pv-tab-panes">
        <?php if ($richDescription): ?>
        <div class="tab-pane fade<?= $first === 'description' ? ' show active' : '' ?>" id="pane-description" role="tabpanel" aria-labelledby="tab-description" tabindex="0">
          <div class="pv-description"><?= product_description_html($p) ?></div>
        </div>
        <?php elseif ($bullets): ?>
        <div class="tab-pane fade<?= $first === 'description' ? ' show active' : '' ?>" id="pane-description" role="tabpanel" aria-labelledby="tab-description" tabindex="0">
          <ul class="product-about-list pv-about">
            <?php foreach ($bullets as $line): $line = preg_replace('/^[\s•·\-\*]+/u', '', $line); if ($line === '') continue; ?><li><p class="body-text-3"><?= e($line) ?></p></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if ($specs): ?>
        <div class="tab-pane fade<?= $first === 'specifications' ? ' show active' : '' ?>" id="pane-specifications" role="tabpanel" aria-labelledby="tab-specifications" tabindex="0">
          <table class="pv-specs">
            <tbody>
              <?php foreach ($specs as [$label, $value]): ?><tr><th scope="row"><?= e($label) ?></th><td><?= e($value) ?></td></tr><?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <?php if ($faqs): ?>
        <div class="tab-pane fade<?= $first === 'faq' ? ' show active' : '' ?>" id="pane-faq" role="tabpanel" aria-labelledby="tab-faq" tabindex="0">
          <dl class="pv-faq">
            <?php foreach ($faqs as [$question, $answer]): ?><div class="pv-faq-item"><dt><?= e($question) ?></dt><dd><?= e($answer) ?></dd></div><?php endforeach; ?>
          </dl>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($explore): ?>
    <nav class="pv-explore" aria-label="More to explore">
      <span class="pv-explore-label">More to explore:</span>
      <ul class="listing-links"><?php foreach ($explore as [$label, $href]): ?><li><a href="<?= e($href) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
    </nav>
    <?php endif; ?>
  </div>
</section>

<?php if ($related): ?>
<section class="tf-sp-2 pt-0">
  <div class="container">
    <div class="flat-title">
      <h5 class="fw-semibold">Related products</h5>
      <div class="box-btn-slide relative">
        <div class="swiper-button-prev nav-swiper nav-prev-products snbprel"><i class="icon-arrow-left-lg"></i></div>
        <div class="swiper-button-next nav-swiper nav-next-products snbnrel"><i class="icon-arrow-right-lg"></i></div>
      </div>
    </div>
    <div class="swiper tf-sw-products" data-swiper="products-5" data-nav="rel">
      <div class="swiper-wrapper">
        <?php foreach ($related as $r): ?>
        <div class="swiper-slide"><?php partial('cards/product', ['p' => $r]); ?></div>
        <?php endforeach; ?>
      </div>
      <div class="d-flex d-xl-none sw-dot-default sw-pagination-products justify-content-center spdrel"></div>
    </div>
  </div>
</section>
<?php endif; ?>
<?php partial('page-end'); ?>
