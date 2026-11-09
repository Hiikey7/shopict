<header class="tf-header style-4">
  <div class="inner-header">
    <div class="container">
      <div class="header-main">
        <a class="header-logo" href="./" aria-label="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?> home">
          <img alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" width="185" height="40" decoding="async" style="height: 40px; width: auto" src="<?= e(asset(store_logo())) ?>" />
        </a>
        <form class="header-search" action="shop.php" method="get" role="search">
          <input type="search" name="q" value="<?= e(basename($_SERVER['SCRIPT_NAME'] ?? '') === 'shop.php' ? (string) ($_GET['q'] ?? '') : '') ?>" placeholder="Search for products, brands and categories..." aria-label="Search for products" autocomplete="off" />
          <button type="submit" aria-label="Search"><i class="icon-search" aria-hidden="true"></i><span class="header-search-label">Search</span></button>
        </form>
        <ul class="header-actions">
          <li>
            <a href="wishlist.php" class="header-action" aria-label="Wishlist">
              <span class="header-action-icon"><i class="icon-heart2" aria-hidden="true"></i><span class="count-box" data-wishlist-count><?= count(page_state()['wishlist']) ?></span></span>
              <span class="header-action-label">Wishlist</span>
            </a>
          </li>
          <li>
            <a href="#shoppingCart" data-bs-toggle="offcanvas" class="header-action" aria-label="Cart">
              <span class="header-action-icon"><svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M8.55865 19.1096C6.8483 19.1096 5.46191 20.496 5.46191 22.2064C5.46191 23.9165 6.8483 25.3029 8.55865 25.3029C10.2688 25.3029 11.6552 23.9165 11.6552 22.2064C11.6534 20.4969 10.2681 19.1114 8.55865 19.1096ZM8.55865 24.1644C7.47712 24.1644 6.60037 23.2877 6.60037 22.2064C6.60037 21.1248 7.47712 20.2481 8.55865 20.2481C9.63996 20.2481 10.5167 21.1248 10.5167 22.2064C10.5167 23.2877 9.63996 24.1644 8.55865 24.1644Z"
                      fill="#333E48"
                    ></path>
                    <path
                      d="M25.436 6.1144H5.33643L4.92663 3.82036C4.67403 2.40819 3.56715 1.30353 2.15453 1.05382L0.668757 0.792113C0.359017 0.736969 0.0635073 0.943536 0.00836329 1.25305C-0.0465584 1.56279 0.159787 1.8583 0.469527 1.91345L1.96086 2.17516C2.90187 2.34193 3.63853 3.07859 3.80529 4.01959L5.82027 15.387C6.05819 16.7472 7.24001 17.7393 8.62083 17.738H20.5746C21.8305 17.7418 22.9396 16.9197 23.3014 15.7172L25.9767 6.84861C26.0263 6.67562 25.995 6.48929 25.8913 6.34209C25.7831 6.19956 25.6147 6.11551 25.436 6.1144ZM22.214 15.3813C21.9992 16.1035 21.3337 16.5975 20.5804 16.5938H8.62661C7.79745 16.596 7.08769 15.9994 6.94739 15.182L5.54144 7.24707H24.6731L22.214 15.3813Z"
                      fill="#333E48"
                    ></path>
                    <path
                      d="M20.512 19.1096C18.8017 19.1096 17.4153 20.496 17.4153 22.2064C17.4153 23.9165 18.8017 25.3029 20.512 25.3029C22.2221 25.3029 23.6085 23.9165 23.6085 22.2064C23.6068 20.4969 22.2215 19.1114 20.512 19.1096ZM20.512 24.1644C19.4305 24.1644 18.5537 23.2877 18.5537 22.2064C18.5537 21.1248 19.4305 20.2481 20.512 20.2481C21.5933 20.2481 22.4701 21.1248 22.4701 22.2064C22.4701 23.2877 21.5933 24.1644 20.512 24.1644Z"
                      fill="#333E48"
                    ></path></svg><span class="count-box" data-cart-count><?= count(page_state()['cart']) ?></span></span>
              <span class="header-action-label">Cart</span>
            </a>
          </li>
          <li class="btn-mobile d-flex d-xl-none align-items-center">
            <a href="#mobileMenu" class="mobile-button p-0" data-bs-toggle="offcanvas" aria-controls="mobileMenu" aria-label="Menu"><span></span></a>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="header-bottom bg-primary d-none d-xl-block">
    <div class="container">
      <div class="header-inner-bottom active-container">
        <?php
        $navTree = category_tree();
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $navCat = $script === 'shop.php' ? shop_single_category() : null;
        $navMain = category_main($navCat);
        $navFeatured = [];
        foreach ($navTree as $m) {
            $navFeatured[$m['id']] = $m['children'] ? category_feature_products($m, 3) : [];
        }
        ?>
        <div class="header-nav-left">
          <div class="nav-category-wrap style-2 cat-menu" data-cat-menu>
            <button type="button" class="nav-title cat-menu-toggle" aria-expanded="false" aria-controls="catMenuList" data-cat-menu-toggle>
              <i class="icon-menu-dots"></i><span class="title body-md-2 fw-semibold">All Categories</span><i class="icon-arrow-down cat-menu-caret"></i>
            </button>
            <ul class="cat-menu-list" id="catMenuList" hidden>
              <li><a href="shop.php"><span>All products</span></a></li>
              <?php foreach ($navTree as $main): ?>
              <?php if (!$main['children']): ?>
              <li><a href="<?= e(category_url($main)) ?>"><span><?= e($main['name']) ?></span></a></li>
              <?php else: ?>
              <li class="cat-menu-item" data-cat-sub>
                <a class="cat-menu-main" href="<?= e(category_url($main)) ?>" aria-haspopup="true" aria-expanded="false"><span><?= e($main['name']) ?></span><svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M4.5 2.5 8 6l-3.5 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                <div class="cat-flyout">
                  <div class="cat-flyout-main">
                    <a class="cat-flyout-all" href="<?= e(category_url($main)) ?>">View all <?= e($main['name']) ?> →</a>
                    <ul class="mega-list">
                      <?php foreach ($main['children'] as $sub): ?>
                      <li><a class="<?= $navCat && $navCat['id'] === $sub['id'] ? 'active' : '' ?>" href="<?= e(category_url($sub)) ?>"><?= e($sub['name']) ?></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php partial('mega-feature', ['featured' => $navFeatured[$main['id']], 'label' => $main['name']]); ?>
                </div>
              </li>
              <?php endif; ?>
              <?php endforeach; ?>
            </ul>
          </div>
          <nav class="main-nav-menu style-white mega-nav" aria-label="Product categories">
            <ul class="nav-list">
              <?php foreach ($navTree as $main): $isCurrent = $navMain && $navMain['id'] === $main['id']; ?>
              <li class="nav-item mega-item<?= $isCurrent ? ' active' : '' ?>" data-mega>
                <a class="item-link link body-md-2 fw-semibold mega-link" href="<?= e(category_url($main)) ?>"<?= $main['children'] ? ' aria-haspopup="true" aria-expanded="false"' : '' ?>>
                  <?= e($main['name']) ?><?php if ($main['children']): ?><svg class="mega-caret" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg><?php endif; ?>
                </a>
                <?php if ($main['children']): ?>
                <div class="mega-panel">
                  <ul class="mega-list">
                    <?php foreach ($main['children'] as $sub): ?>
                    <li><a class="<?= $navCat && $navCat['id'] === $sub['id'] ? 'active' : '' ?>" href="<?= e(category_url($sub)) ?>"><?= e($sub['name']) ?></a></li>
                    <?php endforeach; ?>
                  </ul>
                  <?php partial('mega-feature', ['featured' => $navFeatured[$main['id']], 'label' => $main['name']]); ?>
                </div>
                <?php endif; ?>
              </li>
              <?php endforeach; ?>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</header>
