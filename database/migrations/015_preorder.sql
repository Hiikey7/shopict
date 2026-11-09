-- Pre-order products: marked in the shop, cart and on orders (Admin → Products → Pre-order switch).
--   mysql -u root onsus_shop < database/migrations/015_preorder.sql
SET NAMES utf8mb4;
ALTER TABLE products ADD COLUMN is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;
ALTER TABLE order_items ADD COLUMN is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER line_total;
