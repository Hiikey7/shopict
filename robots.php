<?php
// /robots.txt: which parts of the site crawlers may visit, and where the sitemap is.
// Public pages (products, categories, brands, guides, pictures, CSS and JavaScript) stay open; private and
// checkout pages are closed. Search and AI-search crawlers are allowed. AI *training* crawlers follow the
// choice in Admin → SEO → Settings (allowed unless switched off).
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$site = seo_site_url();
$private = ['/admin/', '/api/', '/cart.php', '/checkout.php', '/order.php', '/wishlist.php', '/offline.html', '/*?*sort=', '/*?*q='];
$lines = ['# ' . seo_store() . ' – ' . $site, '', 'User-agent: *'];
foreach ($private as $path) {
    $lines[] = 'Disallow: ' . $path;
}
$lines[] = 'Allow: /';
$lines[] = '';
// AI search and answer engines: they fetch pages to answer people's questions and link back to the shop.
$lines[] = '# AI search assistants (they show and link to our pages in answers)';
foreach (['OAI-SearchBot', 'ChatGPT-User', 'PerplexityBot', 'Perplexity-User', 'Claude-SearchBot', 'Claude-User', 'DuckAssistBot'] as $bot) {
    $lines[] = 'User-agent: ' . $bot;
}
foreach ($private as $path) {
    $lines[] = 'Disallow: ' . $path;
}
$lines[] = 'Allow: /';
$lines[] = '';
if (setting('seo_ai_training', 'allow') === 'block') {
    $lines[] = '# AI model training (switched off in Admin → SEO → Settings)';
    foreach (['GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'meta-externalagent', 'Bytespider'] as $bot) {
        $lines[] = 'User-agent: ' . $bot;
    }
    $lines[] = 'Disallow: /';
    $lines[] = '';
}
$lines[] = 'Sitemap: ' . $site . '/sitemap.xml';
echo implode("\n", $lines), "\n";
