<?php
require __DIR__ . '/includes/bootstrap.php';

visitor_id();
track_page_view();

partial('page-start', [
    'title'       => setting('store_name', 'Shop ICT Gadgets') . ' – Laptops, Monitors, Phones & Accessories in Nairobi',
    'description' => 'Buy laptops, monitors, phones, audio, printers and computer accessories in Kenya from ' . setting('store_name', 'Shop ICT Gadgets') . ', Laxmi Plaza, Nairobi. Free pickup or countrywide delivery.',
    'canonical'   => '',
    'jsonLd'      => [seo_store_ld(), seo_website_ld()],
]);
partial('sections/hero');
echo '<div class="home-sections">'; // every section below gets the same space above and below (custom.css)
partial('sections/deal-of-day');
partial('sections/categories');
partial('sections/product-slider', ['section' => 'trending', 'nav' => '36']);
partial('sections/featured-banners');
partial('sections/product-slider', ['section' => 'best_sellers', 'nav' => '37']);
partial('sections/hot-products');
partial('sections/smart-home');
partial('sections/promo-slider');
partial('sections/top-lists');
partial('sections/product-slider', ['section' => 'homepage_featured_bottom', 'nav' => '38']);
partial('sections/product-slider', ['section' => 'recently_viewed', 'nav' => '12']); // "Trending Products" (same slider as Trending Now)
partial('sections/brands'); // Authorised Brands logo carousel (hidden in Admin → Sections)
echo '</div>';
partial('page-end');
