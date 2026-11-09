<?php
/**
 * @var string     $title
 * @var string     $active  key of the current menu item
 * @var array|null $admin
 */
$menu = [
    'dashboard'   => ['index.php', 'Dashboard'],
    'orders'      => ['orders.php', 'Orders'],
    'products'    => ['products.php', 'Products'],
    'product-categories' => ['product-categories.php', 'Product categories'],
    'promotions'  => ['promotions.php', 'Promotions'],
    'analytics'   => ['analytics.php', 'Analytics'],
    'seo'         => ['seo.php', 'SEO'],
    'sections'    => ['sections.php', 'Sections'],
    'banners'     => ['banners.php', 'Banners'],
    'categories'  => ['categories.php', 'Homepage categories'],
    'brands'      => ['brands.php', 'Brands'],
    'settings'    => ['settings.php', 'Settings'],
];
// Bottom tabs; everything else is under "Others".
$tabs = ['dashboard', 'orders', 'products'];
$others = array_diff_key($menu, array_flip($tabs));
$othersActive = isset($others[$active]);
$tabIcon = fn (string $p) => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
$tabIcons = [
    'dashboard' => $tabIcon('<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>'),
    'orders'    => $tabIcon('<path d="M6 3h12l2 4v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/><path d="M4 7h16M9 11h6"/>'),
    'products'  => $tabIcon('<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>'),
    'others'    => $tabIcon('<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>'),
];
$flash = flash();
$newOrders = $admin ? (int) db()->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn() : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title><?= e($title) ?> · <?= e(setting('store_name', 'Shop ICT Gadgets')) ?> Admin</title>
  <link rel="icon" href="../favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/admin/bootstrap.min.css">
  <link rel="stylesheet" href="../<?= asset_v('admin/admin.css') ?>">
</head>
<body>
<?php if ($admin): ?>
<div class="admin-shell">
  <aside class="admin-desk-nav" aria-label="Admin menu">
    <a class="admin-desk-brand" href="<?= e(admin_url()) ?>"><img src="<?= e(admin_asset(store_logo())) ?>" alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" height="40" style="width:auto"></a>
    <nav class="admin-desk-links">
      <?php foreach ($menu as $key => [$href, $label]): $on = $key === $active; ?>
      <a href="<?= e(admin_url($href)) ?>" class="<?= $on ? 'active' : '' ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= isset($tabIcons[$key]) ? $tabIcons[$key] : '' ?><span><?= e($label) ?></span><?php if ($key === 'orders' && $newOrders): ?><span class="nav-badge" title="New orders"><?= $newOrders ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-desk-foot"><a href="../" target="_blank" rel="noopener">View store ↗</a></div>
  </aside>
  <aside class="admin-sidebar" id="adminSidebar" aria-label="More admin pages">
    <div class="admin-sidebar-head">
      <a class="admin-brand" href="<?= e(admin_url()) ?>"><img src="<?= e(admin_asset(store_logo())) ?>" alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" height="34" style="width:auto"></a>
      <button class="admin-menu-close" type="button" aria-label="Close menu" data-close-sidebar><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
    </div>
    <nav class="admin-nav">
      <?php foreach ($others as $key => [$href, $label]): ?>
      <a href="<?= e(admin_url($href)) ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= e($label) ?><?php if ($key === 'orders' && $newOrders): ?><span class="nav-badge" title="New orders"><?= $newOrders ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-foot">
      <a class="d-none d-md-inline" href="../" target="_blank" rel="noopener">View store ↗</a>
      <form method="post" action="logout.php" class="d-md-none"><?= csrf_field() ?><button class="admin-panel-logout">Log out</button></form>
    </div>
  </aside>
  <div class="admin-backdrop" data-close-sidebar hidden></div>
  <div class="admin-main">
    <header class="admin-topbar">
      <a class="admin-top-brand" href="<?= e(admin_url()) ?>"><img src="<?= e(admin_asset(store_logo())) ?>" alt="<?= e(setting('store_name', 'Shop ICT Gadgets')) ?>" height="32" style="width:auto"></a>
      <h1 class="visually-hidden"><?= e($title) ?></h1>
      <span class="admin-topbar-spacer" aria-hidden="true"></span>
      <div class="admin-user">
        <span class="text-muted small d-none d-sm-inline"><?= e($admin['name']) ?></span>
        <a class="btn btn-sm btn-outline-secondary d-md-none" href="../" target="_blank" rel="noopener">View store ↗</a>
        <form method="post" action="logout.php" class="d-none d-md-inline"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Log out</button></form>
      </div>
    </header>
    <nav class="admin-tabbar" aria-label="Admin">
      <?php foreach ($tabs as $key): [$href, $label] = $menu[$key]; $on = $key === $active; ?>
      <a class="admin-tab<?= $on ? ' active' : '' ?>" href="<?= e(admin_url($href)) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= $tabIcons[$key] ?><span><?= e($label) ?></span><?php if ($key === 'orders' && $newOrders): ?><span class="admin-tab-badge" title="New orders"><?= $newOrders ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <button class="admin-tab<?= $othersActive ? ' active' : '' ?>" type="button" aria-controls="adminSidebar" aria-expanded="false" data-toggle-sidebar><?= $tabIcons['others'] ?><span>Others</span></button>
    </nav>
    <main class="admin-content">
      <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>
<?php else: ?>
<div class="admin-auth">
<?php endif; ?>
