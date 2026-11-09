-- Footer: about text, shop links, contact details and "Trusted Distributor For" brands.
--   mysql -u root onsus_shop < database/migrations/009_footer_columns.sql
SET NAMES utf8mb4;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('store_email', 'ictgadgetsshop@gmail.com'),
  ('store_city', 'Nairobi, Kenya'),
  ('footer_about', 'Dealers in IT products, Electronics, Accessories, Phones, Homewear, Servers, Networking Accessories, etc.');

-- Brands shown under "Trusted Distributor For" (in this order). Others are hidden, not deleted.
UPDATE brands SET is_active = 0;
INSERT INTO brands (name, position, is_active)
SELECT n.name, n.pos, 1 FROM (
  SELECT 'hp' AS name, 1 AS pos UNION ALL SELECT 'Lenovo', 2 UNION ALL SELECT 'DELL', 3 UNION ALL
  SELECT 'SAMSUNG', 4 UNION ALL SELECT 'EPSON', 5 UNION ALL SELECT 'Canon', 6 UNION ALL
  SELECT 'acer', 7 UNION ALL SELECT 'Apple', 8 UNION ALL SELECT 'Logitech', 9
) n
WHERE NOT EXISTS (SELECT 1 FROM brands b WHERE LOWER(b.name) = LOWER(n.name));
UPDATE brands b JOIN (
  SELECT 'hp' AS name, 1 AS pos UNION ALL SELECT 'Lenovo', 2 UNION ALL SELECT 'DELL', 3 UNION ALL
  SELECT 'SAMSUNG', 4 UNION ALL SELECT 'EPSON', 5 UNION ALL SELECT 'Canon', 6 UNION ALL
  SELECT 'acer', 7 UNION ALL SELECT 'Apple', 8 UNION ALL SELECT 'Logitech', 9
) n ON LOWER(b.name) = LOWER(n.name)
SET b.name = n.name, b.position = n.pos, b.is_active = 1;
UPDATE brands SET position = position + 100 WHERE is_active = 0;
