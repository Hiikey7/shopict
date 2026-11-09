<?php
// Shopping Cart page: change quantities, remove items, see totals, then go to checkout.
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
track_page_view();

partial('page-start', ['title' => 'Shopping cart · ' . setting('store_name', 'Shop ICT Gadgets')]);
?>
<section class="tf-sp-2 co-page">
  <div class="container">
    <nav class="co-crumbs body-text-3" aria-label="Breadcrumb"><a class="link" href="./">Home</a><span>/</span><span aria-current="page">Shopping cart</span></nav>
    <h3 class="fw-semibold mb-4">Shopping cart</h3>
    <div data-cart-page>
      <?php partial('cart-page', ['items' => page_state()['cart']]); ?>
    </div>
  </div>
</section>
<?php partial('page-end'); ?>
