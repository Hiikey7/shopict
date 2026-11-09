# SEO guide: how the shop helps Google and AI assistants find your products

## What happens automatically

Every visible product, category and brand gets the following, with no work from you. It's all built from what's
saved in the admin, so a price or stock change shows on Google the next time it visits.

| Where | What search engines get |
|---|---|
| Product pages | Search title ("HP EliteBook 840 G8 Laptop Core i5 11th Gen Price in Kenya"), search description, product data (price in KES, stock, new or Ex-UK condition, brand, SKU, specs, 3-day return policy), one entry per option (Core i5 / Core i7) with its own price and link, breadcrumbs, a "Questions" tab built from the specs, and picture descriptions |
| Category pages (`/laptops`, `/business-laptops`) | Their own title and description, an H1, an introduction from the products (count, price range, brands), buying advice for the 6 main categories, and links to subcategories, brands and guides |
| Brand pages (`/hp`, `/samsung`) | A page for every active brand that has products |
| Home page | The shop's name, address at Laxmi Plaza, phone, opening hours and return policy |
| `/sitemap.xml` | Every page that can appear on Google, updated straight away when you add, hide or delete a product |
| `/robots.txt` | Keeps admin, cart, checkout and search results out; lets Google, Bing and AI search assistants in |
| `/llms.txt` | A short description of the shop for AI assistants |
| `/google-merchant-feed.xml` | Product feed for Google Shopping |

Searches, filters, sorting and several categories at once are marked "noindex". This stops Google seeing
thousands of near-identical pages. Empty categories and brands stay off Google until they have products.

## One-time setup after the site is live on https://www.shopict.co.ke

1. **Check the address in `config.php`.** `site_url` must be `https://www.shopict.co.ke`.
2. **Google Search Console** (search.google.com/search-console):
   - Add the property `https://www.shopict.co.ke`.
   - Choose "HTML tag" and paste the tag in **Admin → SEO → Settings**.
   - Click Verify, then **Sitemaps → submit `sitemap.xml`**.
3. **Bing Webmaster Tools** (bing.com/webmasters): import the site from Search Console, or paste its tag in Admin → SEO → Settings. Bing also feeds ChatGPT search.
4. **Google Merchant Center** (merchants.google.com):
   - Add the feed `https://www.shopict.co.ke/google-merchant-feed.xml` as a daily "scheduled fetch".
   - In **Shipping and returns**, set your delivery rates and the 3-day return window. The feed has no delivery price, because delivery is "confirmed by phone".
5. **Google Analytics 4** (optional): paste the `G-…` ID in Admin → SEO → Settings.
6. **Google Business Profile:** claim the shop at Laxmi Plaza, using the same name, address and phone as the website. This matters most for "near me" and Nairobi searches.
7. **Test a product** with Google's Rich Results Test (search.google.com/test/rich-results) once the site is online. It can't test the local copy.

## Everyday work

- **New product:**
  1. Follow `product-listing-guide.md`.
  2. Open the **Search engines (SEO)** card at the bottom of the product editor and work through its checklist: at least 3 pictures, 5+ specs and a 5+ point description.
  3. Leave the SEO fields empty unless you want different wording. Empty fields stay up to date by themselves.
- **"Write from specs"** (above the description) drafts "About this item" from the saved specs. Read it and add what's special about the unit before saving.
- **Admin → SEO → Products:**
  - Sort out products scoring under 70.
  - Check the **Duplicates** filter: two products with the same name or description compete with each other on Google.
- **Admin → SEO → Categories / Brands:** add your own introduction where you have something useful to say, such as which HP ranges you stock.
- **Buying guides (Admin → SEO → Guides):**
  - A first guide, "How much RAM do you need in a laptop?", is waiting as a **draft**. Read it, change anything you disagree with, set it to Published, and it appears at `/buying-guides`.
  - Good next topics: Core i5 vs Core i7, SSD vs HDD, best laptops for students, Ex-UK vs new laptops, choosing a monitor for office work.
  - Check every fact before publishing.

## Things to keep honest

- Only enter a barcode (GTIN) or part number (MPN) printed on the box or the maker's spec sheet.
- The home page brand section (currently switched off) is titled "Authorised Brands". If you switch it on, keep that title only if the brands have confirmed you as an authorised dealer; otherwise rename it in Admin → Sections (e.g. "Shop by Brand").
- The SEO score is our own checklist. It doesn't measure or guarantee a Google position. Real results show in Search Console after Google has crawled the site, usually within a few weeks.
