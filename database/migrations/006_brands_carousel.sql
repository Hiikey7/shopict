-- "Authorised Brands" logo carousel (replaces the Free delivery / Support / Payment strip).
--   mysql -u root onsus_shop < database/migrations/006_brands_carousel.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS brands (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name      VARCHAR(100) NOT NULL,
  logo      VARCHAR(255) NULL COMMENT 'Uploaded logo; the name is shown as text when empty',
  link_url  VARCHAR(255) NULL COMMENT 'Empty = search the shop for the brand name',
  position  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sections (code, title, is_active, max_products, note, position)
VALUES ('brands', 'Authorised Brands', 1, 0, 'Logo carousel near the bottom of the home page. Manage the logos in Brands.', 14)
ON DUPLICATE KEY UPDATE code = code;

-- Brands found in the product catalogue (upload their logos in Admin → Brands).
INSERT INTO brands (name, position)
SELECT b.name, b.pos FROM (
  SELECT 'Apple' AS name, 1 AS pos UNION ALL SELECT 'Samsung', 2 UNION ALL SELECT 'Sony', 3 UNION ALL
  SELECT 'HP', 4 UNION ALL SELECT 'Lenovo', 5 UNION ALL SELECT 'Xiaomi', 6 UNION ALL SELECT 'JBL', 7 UNION ALL
  SELECT 'Beats', 8 UNION ALL SELECT 'Logitech', 9 UNION ALL SELECT 'TCL', 10 UNION ALL SELECT 'Corsair', 11 UNION ALL
  SELECT 'Fitbit', 12
) b
WHERE NOT EXISTS (SELECT 1 FROM brands);
