-- Make every existing hidden product visible on the storefront.
-- This updates only products.is_active. It does not insert, delete, or relist products.
-- Run once against the HostPinacle storefront database in phpMyAdmin.

START TRANSACTION;

-- Review how many currently hidden products will be made visible.
SELECT COUNT(*) AS hidden_products_to_show
  FROM products
 WHERE is_active = 0;

UPDATE products
   SET is_active = 1
 WHERE is_active = 0;

COMMIT;

-- Confirm there are no hidden product rows remaining.
SELECT COUNT(*) AS hidden_products_remaining
  FROM products
 WHERE is_active = 0;

SELECT COUNT(*) AS visible_products
  FROM products
 WHERE is_active = 1;
