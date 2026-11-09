<?php
// Wishlist page: the products the visitor has hearted (kept per browser, like the cart).
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
track_page_view();
$products = wishlist_products();

partial('page-start', ['title' => 'Wishlist · ' . setting('store_name', 'Shop ICT Gadgets')]);
?>
<section class="tf-sp-2 co-page">
  <div class="container">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">Wishlist</span></nav>
    <div class="shop-toolbar">
      <div>
        <h3 class="fw-semibold mb-1">Wishlist</h3>
        <p class="body-text-3 text-main-2 mb-0" data-wishlist-summary><?= count($products) ?> saved product<?= count($products) === 1 ? '' : 's' ?></p>
      </div>
    </div>
    <div class="co-card text-center py-5<?= $products ? ' d-none' : '' ?>" data-wishlist-empty>
      <h5 class="fw-semibold mb-2">Your wishlist is empty</h5>
      <p class="body-text-3 text-main-2 mb-4">Tap the heart on any product to save it here for later.</p>
      <a class="tf-btn d-inline-flex" href="shop.php"><span class="text-white">Browse products</span></a>
    </div>
    <?php if ($products): ?>
    <div class="shop-grid shop-grid-wide" data-wishlist-grid>
      <?php foreach ($products as $p): ?>
      <div data-wishlist-item="<?= (int) $p['id'] ?>"><?php partial('cards/product', ['p' => $p, 'class' => 'style-img-border', 'delay' => false]); ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php partial('page-end'); ?>
