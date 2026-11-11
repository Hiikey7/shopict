-- Permanently delete every currently active product from the storefront catalog.
-- Run against the HostPinacle storefront database only after exporting a database backup.
-- This does not delete inactive product records, product categories, promotions, or orders.
-- Foreign keys remove product variants, gallery images, category links, home-section links,
-- product promotion links, cart rows and wishlist rows. Banners are unlinked. Historical
-- order-item snapshots remain, with product_id set to NULL by the existing foreign key.

START TRANSACTION;

SELECT COUNT(*) AS active_products_to_delete FROM products WHERE is_active = 1;

DELETE FROM products WHERE is_active = 1;

COMMIT;

SELECT COUNT(*) AS active_products_remaining FROM products WHERE is_active = 1;
