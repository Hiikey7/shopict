<?php
/** Phone/tablet menu opened by the hamburger button: categories (expandable) first, then the main pages. */
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$mbCat = $script === 'shop.php' ? shop_single_category() : null;
$mbMain = category_main($mbCat);
?>
<div class="offcanvas offcanvas-start canvas-mb" id="mobileMenu" aria-label="Menu">
  <span class="icon-close btn-close-mb link" data-bs-dismiss="offcanvas" role="button" aria-label="Close menu"></span>
  <div class="logo-site">
    <a href="./"
      ><img alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" loading="lazy" decoding="async" src="<?= e(asset(store_logo())) ?>"
    /></a>
  </div>
  <div class="mb-canvas-content">
    <div class="mb-body">
      <p class="mb-cat-title fw-semibold">Categories</p>
      <ul class="mb-cat-list">
        <li><a href="shop.php" class="<?= $script === 'shop.php' && !$mbCat ? 'text-primary' : '' ?>">All products</a></li>
        <?php foreach (category_tree() as $main): ?>
        <li>
          <?php if ($main['children']): ?>
          <details class="mb-cat-group"<?= $mbMain && $mbMain['id'] === $main['id'] ? ' open' : '' ?>>
            <summary><?= e($main['name']) ?></summary>
            <ul class="mb-cat-sub">
              <li><a href="<?= e(category_url($main)) ?>" class="<?= $mbCat && $mbCat['id'] === $main['id'] ? 'text-primary' : '' ?>">All <?= e($main['name']) ?></a></li>
              <?php foreach ($main['children'] as $sub): ?>
              <li><a href="<?= e(category_url($sub)) ?>" class="<?= $mbCat && $mbCat['id'] === $sub['id'] ? 'text-primary' : '' ?>"><?= e($sub['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </details>
          <?php else: ?>
          <a href="<?= e(category_url($main)) ?>"><?= e($main['name']) ?></a>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="mb-links-title fw-semibold">Pages</p>
      <ul class="nav-ul-mb" id="wrapper-menu-navigation">
        <li class="nav-mb-item<?= $script === 'index.php' ? ' active' : '' ?>"><a class="mb-menu-link" href="./">Home</a></li>
        <li class="nav-mb-item<?= $script === 'wishlist.php' ? ' active' : '' ?>"><a class="mb-menu-link" href="wishlist.php">Wishlist</a></li>
        <li class="nav-mb-item<?= $script === 'cart.php' ? ' active' : '' ?>"><a class="mb-menu-link" href="cart.php">Cart</a></li>
      </ul>
      <button type="button" class="app-install-btn" data-install-app hidden>
        <img src="<?= asset('images/pwa/icon-192.png') ?>" alt="" width="36" height="36">
        <span><strong>Install our app</strong><small>Shop faster from your home screen</small></span>
      </button>
      <p class="app-install-ios" data-install-ios hidden>To install the app: tap <strong>Share</strong>, then <strong>Add to Home Screen</strong>.</p>
    </div>
  </div>
</div>
