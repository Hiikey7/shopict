<?php
// Router for PHP's built-in server, which ignores .htaccess:
//   php -S localhost:8080 router.php
// Blocks the folders that .htaccess protects on Apache, and serves everything else as usual.
// The same addresses are set up for Apache in .htaccess.
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(database|storage|includes|partials|docs)(/|$)|^/config\.php$|^/router\.php$|/\.#i', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

// [script, extra query values] for the short addresses; null = serve the file as usual.
$route = null;
$generated = ['/robots.txt' => 'robots.php', '/sitemap.xml' => 'sitemap.php', '/llms.txt' => 'llms.php', '/google-merchant-feed.xml' => 'feed.php'];
if (isset($generated[$path])) {
    $route = [$generated[$path], []]; // files for search engines and AI assistants, written from the database
} elseif (preg_match('#^/sitemap-(pages|products|guides)\.xml$#', $path, $m)) {
    $route = ['sitemap.php', ['part' => $m[1]]];
} elseif (is_file(__DIR__ . $path) || is_dir(__DIR__ . $path)) {
    $route = null;
} elseif (preg_match('#^/([a-z0-9-]+-)?p(\d+)/?$#', $path, $m)) {
    $route = ['product.php', ['id' => $m[2]]]; // /samsung-essential-monitor-p369
} elseif (preg_match('#^/about/?$#', $path)) {
    $route = ['about.php', []];
} elseif (preg_match('#^/buying-guides/?$#', $path)) {
    $route = ['blog.php', []];
} elseif (preg_match('#^/([a-z0-9-]+-)?g(\d+)/?$#', $path, $m)) {
    $route = ['post.php', ['id' => $m[2]]]; // /how-much-ram-do-you-need-g3
} elseif (preg_match('#^/([a-z0-9-]+)/?$#', $path, $m)) {
    $route = ['shop.php', ['seg' => $m[1]]]; // /laptops, /business-laptops, /hp: shop.php finds the category or brand
}
if ($route === null) {
    return false;
}
$_GET = array_merge($_GET, $route[1]);
$_SERVER['SCRIPT_NAME'] = '/' . $route[0];
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/' . $route[0];
chdir(__DIR__);
require __DIR__ . '/' . $route[0];
return true;
