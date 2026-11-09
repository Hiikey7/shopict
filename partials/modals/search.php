<?php
$searchBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/.');
$searchSuggestUrl = ($searchBase !== '' ? $searchBase : '') . '/api/search-suggest.php';
?>
<div class="offcanvas offcanvas-top offcanvas-search" id="search">
  <div class="offcanvas-content">
    <div class="popup-header"><button class="icon-close icon-close-popup link" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="looking-for-wrap">
            <h3 class="heading fw-semibold text-center">What are you looking for?</h3>
            <form class="form-search" action="shop.php" method="get" role="search" data-mobile-search-form>
              <fieldset><input type="search" placeholder="Search for anything" tabindex="2" aria-label="Search for products" name="q" value="" autocomplete="off" data-search-suggest-input data-suggest-url="<?= e($searchSuggestUrl) ?>" aria-controls="search-suggestions" aria-autocomplete="list" /></fieldset>
              <button type="submit" class="button-submit"><i class="icon-search"></i></button>
            </form>
            <section class="mobile-search-activity d-md-none" data-mobile-search-activity aria-label="Recent searches">
              <div class="mobile-search-heading"><span class="text fw-semibold body-text-3">Recent searches</span><button type="button" class="mobile-search-clear" data-search-clear hidden>Clear</button></div>
              <ul class="mobile-search-recent" data-search-recent></ul>
              <p class="mobile-search-empty" data-search-recent-empty>No recent searches yet.</p>
            </section>
            <section class="search-suggestions" data-search-suggestions hidden aria-label="Product suggestions">
              <span class="text fw-semibold body-text-3">Products</span>
              <ul id="search-suggestions" role="listbox" data-search-suggestion-list></ul>
              <p class="search-suggestions-status" data-search-suggestion-status aria-live="polite"></p>
            </section>
            <div class="popular-searches justify-content-md-center">
              <span class="text fw-semibold body-text-3">Popular categories:</span>
              <ul>
                <?php foreach (array_slice(category_tree(), 0, 6) as $c): ?>
                <li><a class="link body-text-3 fw-medium" href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
        <?php if (section_visible('search_featured')): ?>
        <div class="col-lg-10">
          <div class="popup-feature">
            <h5 class="fw-semibold mb-30"><?= e(section_title('search_featured')) ?></h5>
            <div class="box-btn-slide-2 sw-nav-effect">
              <div class="swiper tf-sw-products" data-swiper="products-5" data-nav="s11">
                <div class="swiper-wrapper">
                  <?php foreach (section_products('search_featured') as $p): ?>
                  <div class="swiper-slide"><?php partial('cards/product', ['p' => $p, 'class' => 'style-img-border', 'variant' => 'search']); ?></div>
                  <?php endforeach; ?>
                </div>
                <div class="sw-dot-default sw-pagination-products justify-content-center spds11"></div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
