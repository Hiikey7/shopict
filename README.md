# Shop ICT Gadgets – online store (shopictgadgets.co.ke)

The Shop ICT Gadgets electronics storefront (Nairobi, Laxmi Plaza) on plain PHP and MySQL (prices in Kenyan shillings), with an admin dashboard for products (with
variants), promotions, orders and visitor analytics. Customers choose free pickup at the shop or countrywide delivery,
can confirm on WhatsApp, and get an email confirmation.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `openssl`, and `zip` (Excel product import)
- MySQL 8 or MariaDB 10.4+

## Setup

1. Create an empty MySQL database and import `database/onsus_shop.sql` into that selected database using phpMyAdmin. The import includes the current storefront schema and seed content; it does not create a database, so it works with cPanel-prefixed database names. It contains `DROP TABLE` statements, so use only a new, empty database.

   In phpMyAdmin, select the new database before importing `database/onsus_shop.sql`. With command-line MySQL, include the database name: `mysql -u DB_USER -p DB_NAME < database/onsus_shop.sql`.

   For an existing database, back it up first, then apply only the unapplied migrations in order. If an earlier schema migration was only partly applied, run `database/migrations/028_repair_partial_schema.sql`; it checks each required field separately and does not drop tables. If the admin dashboard reports missing schema fields/tables (including promotion creation), run `database/migrations/030_admin_dashboard_repair.sql` in phpMyAdmin; it repairs the current admin schema and can be run repeatedly. This also covers the order and order-item fields from migration 029.
   (`003_prices_to_kes.sql` converts the template's dollar prices at 129 KES = 1 USD; it only runs once.)

2. Copy `config.example.php` to `config.php` and set the database credentials. Keep `config.php` out of Git; it contains private deployment settings.

3. Serve the folder:
   - **PHP built-in server:** `php -S localhost:8080 router.php`, then open `http://localhost:8080`.
     Always pass `router.php`: the built-in server ignores `.htaccess`, and the router blocks
     `database/`, `storage/`, `includes/`, `partials/` and `config.php`.
   - **Laragon / Apache:** put the folder in `C:\laragon\www` and open its `.test` address.
   - **Live site:** https://shopictgadgets.co.ke (set `site_url` in `config.php` if the address changes).
     The `.htaccess` files protect the private folders.

4. Log in to `/admin/` using the administrator credentials provisioned for your installation.
   Before putting the site online, replace any initial password with a unique password of at least
   12 characters under **Settings**. Use HTTPS for the live admin dashboard.

5. In **Admin → Settings → Orders & WhatsApp**, set your WhatsApp number (e.g. `254712345678`),
   and delivery fee (0 = cost confirmed by phone). Without a WhatsApp number the shop phone is used.

## HostPinacle / cPanel deployment

1. In **Domains**, confirm the main domain document root is `/home/CPANEL_USER/public_html`.
2. In **Git Version Control**, clone `https://github.com/Hiikey7/shopict.git` to a repository outside `public_html`.
3. The repository's `.cpanel.yml` deploys the application files to `/home3/shopictg/public_html`; change `DEPLOYPATH` there if the cPanel home path differs.
4. In phpMyAdmin, create/select a new, empty database and import `database/onsus_shop.sql`. This installer includes the schema fixes needed by the current storefront and its seed data. Do not import it into a POS database or a database containing data to keep.
5. Copy `config.example.php` to `config.php` in `public_html`, enter the cPanel database credentials, and set `site_url` to `https://shopictgadgets.co.ke`. Keep `config.php` private; it is ignored by Git and blocked by `.htaccess`.
6. In **Git Version Control → Manage → Pull or Deploy**, click **Update from Remote**, then **Deploy HEAD Commit**. Repeat after pushing code updates to GitHub.
7. Test the main domain and `/admin/` over HTTPS before making any DNS changes. Keep Vercel available until the HostPinacle copy is verified.

## Order confirmation emails

Emails are in test mode by default (`'driver' => 'log'` in `config.php`): each one is saved as an
`.html` file in `storage/mail/` instead of being sent. To send real emails, edit the `mail` block in
`config.php`:

```php
'mail' => [
    'driver'     => 'smtp',
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'you@gmail.com',
    'password'   => 'your-16-character-app-password',
    'from_email' => 'you@gmail.com',
    'from_name'  => 'Shop ICT Gadgets',
],
```

For a cPanel mailbox, use the outgoing server, port, and encryption shown in cPanel → Email Accounts → Connect Devices, and set `username` to the full mailbox address (not the display name) and `password` to that mailbox's password. Gmail requires an App Password. Any SMTP provider works (your web host, Zoho, Brevo, Mailgun…). Set "Email me new orders" in Settings to get a copy of every order. The order page marks a message sent only after the SMTP server accepts it; inbox placement still depends on the receiving mail provider.

## Storefront pages

| Page | What it is |
|------|------------|
| `index.php` | Home page |
| `shop.php` | All products, a category (`?category=`) or search results (`?q=`), with sorting and pages |
| `product.php?id=N` | Product page: gallery, options, quantity, add to cart, WhatsApp, specifications, related products |
| `cart.php` | Shopping cart: change quantities, remove items, totals |
| `checkout.php` | Delivery details and order confirmation |
| `order.php` | The customer's order page (private link) |
| `returns-policy.php` | Returns & Exchanges Policy (linked from the footer) |

A dark bar above the header shows the store phone, address and opening hours (Admin → Settings → Store contact);
on phones it scrolls slowly across the screen.

The red bar under the header has an **All Categories** menu, the five biggest categories and a search box;
all of them lead to `shop.php`. Categories come from the product "Category" field, so keep the spelling
consistent (e.g. pick one of "Laptop & Computer" / "Laptop & Computers").

## How ordering works

1. Shoppers add products to the cart from a card, Quick View or the product page (after choosing an option).
2. **View Cart** (`cart.php`) lets them change quantities; **Check Out** opens `checkout.php`: name, email, phone, delivery address, notes. Payment is on delivery.
3. **Confirm order** saves the order, takes the items out of stock, empties the cart, emails the
   confirmation and shows the order page (`order.php`, opened with a private link).
4. **Confirm & send on WhatsApp** does the same and then opens WhatsApp with the order details.
5. New orders appear in **Admin → Orders** (with a red count in the menu) and on the dashboard.
   Open one to see the items and address, call or WhatsApp the customer, change the status
   (New → Confirmed → Processing → Out for delivery → Delivered, or Cancelled) and keep notes.
   Cancelling puts the stock back.

Shoppers can also message the shop on WhatsApp straight from the cart or Quick View (that sends the
items, but does not create an order in the admin).

## Admin dashboard

| Page        | What it does |
|-------------|--------------|
| Dashboard   | New orders, sales, visitors, latest orders, most viewed products, low stock |
| Orders      | Every order with status tabs and search; order detail with status, notes, resend email, print |
| Products    | Every product field: name, SKU, brand, category, prices, visibility, description, specifications, images, gallery, deal fields, home page sections |
| Variants    | On the product page: up to three options (e.g. RAM, Storage, Colour) and one row per combination with its own price, old price, SKU and stock |
| Promotions  | Percentage or amount off, for all products, a category or chosen products, with start/end dates |
| Analytics   | Page views, visitors, Quick View opens, add-to-carts and orders per day; most viewed pages and products; traffic sources; devices |
| Product categories | Main categories (red menu bar) and their subcategories (drop-down panels); products get one in the product editor |
| Sections, Banners, Categories | Home page content |
| Brands      | Logos in the "Authorised Brands" strip near the bottom of the home page (name, logo, link, order, visible) |
| Settings    | Maintenance mode, deal end date, Quick View default, hero background, store name, WhatsApp, delivery fee, order emails, your password |

Use **Products → Bulk import** to download the Excel template and import up to 500 new products per workbook. The workbook includes product and SEO fields, specifications, categories, gallery images, and variant data. Rows require a title, price, and image; blank optional fields stay empty, and imported products are always hidden until reviewed and published.

Turn maintenance mode on or off under **Admin → Settings → Website availability**. Visitors see a 503 maintenance page with call and WhatsApp buttons using the store contact details; admin pages remain accessible.

### Traffic and performance

The storefront closes PHP's session lock early for read-only GET requests so simultaneous page views from one browser do not queue behind each other. Checkout, admin, and state-changing requests retain normal session locking. Product-list indexes are included in a fresh database import; existing installations must apply migration `023_product_browse_indexes.sql`.

For production, use PHP-FPM or an equivalent multi-worker PHP server with OPcache enabled, and enable APCu to keep request rate-limit counters in shared memory rather than the serialized file fallback. On multiple application servers, use a shared rate-limit/session service or a CDN/WAF with rate limiting. Cache static assets at the edge, but do not publicly cache personalized HTML, carts, checkout, admin pages, or API responses. Size MySQL and PHP workers for the host and monitor database connections, memory, and slow queries. Actual capacity depends on the hosting environment and must be confirmed with a load test against production-like infrastructure.

### Variants

A laptop sold with 8GB or 16GB RAM is one product with two variants. Name the option ("RAM"), add
a row per version with its price and stock, and pick the default. The product cards show the
default variant's price; Quick View shows option buttons and switches price, SKU and stock. Each
cart line and order line remembers the chosen variant.

### Promotions

While a promotion runs, shoppers see the lower price with the regular price crossed out on cards,
in Quick View, in the cart and at checkout. When several apply, the biggest discount wins.

### Analytics

Recorded in `analytics_events`: a page view for every storefront page load, a product view when
Quick View opens, an add-to-cart and an order. Bots and admin pages are skipped. To track a new
page, call `track_page_view()` at its top.

## Structure

```
index.php, shop.php, product.php,
cart.php, checkout.php, order.php     storefront pages
router.php             router for PHP's built-in server
config.php             database and mail settings
includes/
  bootstrap.php        session, PDO, visitor cookie, CSRF token, settings
  store.php            products, variants, promotions, cart, wishlist
  orders.php           checkout, orders, WhatsApp links, order emails
  mailer.php           SMTP / test-mode mailer
  analytics.php        event tracking
  admin.php            admin helpers
partials/              page layout, sections, cards, modals, emails/, admin/
api/                   cart.php, wishlist.php, track.php (JSON endpoints)
admin/                 dashboard pages
assets/                css (main.css theme + custom.css), js, images, fonts
database/onsus_shop.sql, database/migrations/
storage/mail/          saved emails in test mode
```

The Apache configuration caches static styles/scripts for 7 days, images for 30 days and fonts for a year. The service worker caches static assets for repeat visits while keeping storefront pages fresh. Storefront images load lazily with a shimmer placeholder; the first hero image loads eagerly.

Admin sessions expire after 30 minutes of inactivity, login and API requests are rate-limited per client IP, and private/admin responses are not cached. These application-level limits help deter brute-force attempts and abusive automation; use a trusted CDN/WAF and hosting-provider firewall for volumetric bot or denial-of-service protection.

## Prices

All prices are in Kenyan shillings, stored and shown as whole shillings (`KSh 12,499`). Enter plain numbers
in the admin (`12499`; "KSh" and commas are also accepted). Banner prices are free text: type them as you
want them shown.

## Still template placeholders

- The round category images on the home page (Admin → Categories) still link to the home page.
- The language selector and social links in the footer are decorative.
