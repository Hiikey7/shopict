-- Product categories: six main categories with subcategories (red menu bar, shop page, admin).
--   mysql -u root onsus_shop < database/migrations/010_product_categories.sql
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS product_categories (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NULL COMMENT 'NULL = main category',
  name      VARCHAR(100) NOT NULL,
  slug      VARCHAR(120) NOT NULL UNIQUE COMMENT 'Used in shop links: shop.php?cat=gaming-laptops',
  position  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_product_categories_parent (parent_id, position),
  CONSTRAINT fk_product_categories_parent FOREIGN KEY (parent_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Main categories
INSERT IGNORE INTO product_categories (parent_id, name, slug, position) VALUES
  (NULL, 'Laptops', 'laptops', 1),
  (NULL, 'Monitors', 'monitors', 2),
  (NULL, 'Audio', 'audio', 3),
  (NULL, 'Smartphones', 'smartphones', 4),
  (NULL, 'Printers', 'printers', 5),
  (NULL, 'Accessories', 'accessories', 6);

-- Subcategories (menu columns read top to bottom, then left to right)
INSERT IGNORE INTO product_categories (parent_id, name, slug, position)
SELECT p.id, s.name, s.slug, s.pos FROM (
  SELECT 'laptops' AS parent, 'Brand New Laptops' AS name, 'brand-new-laptops' AS slug, 1 AS pos UNION ALL
  SELECT 'laptops', 'Gaming Laptops', 'gaming-laptops', 2 UNION ALL
  SELECT 'laptops', 'MacBooks', 'macbooks', 3 UNION ALL
  SELECT 'laptops', 'Ex-UK Laptops', 'ex-uk-laptops', 4 UNION ALL
  SELECT 'laptops', 'Business Laptops', 'business-laptops', 5 UNION ALL
  SELECT 'laptops', '2-in-1 Convertible Laptops', '2-in-1-convertible-laptops', 6 UNION ALL
  SELECT 'laptops', 'Student Laptops', 'student-laptops', 7 UNION ALL
  SELECT 'laptops', 'Workstation Laptops', 'workstation-laptops', 8 UNION ALL

  SELECT 'monitors', 'Brand New Monitors', 'brand-new-monitors', 1 UNION ALL
  SELECT 'monitors', 'Gaming Monitors', 'gaming-monitors', 2 UNION ALL
  SELECT 'monitors', 'Ex-UK Monitors', 'ex-uk-monitors', 3 UNION ALL
  SELECT 'monitors', 'Curved Monitors', 'curved-monitors', 4 UNION ALL
  SELECT 'monitors', '4K UHD Monitors', '4k-uhd-monitors', 5 UNION ALL
  SELECT 'monitors', 'Office Monitors', 'office-monitors', 6 UNION ALL
  SELECT 'monitors', 'Ultrawide Monitors', 'ultrawide-monitors', 7 UNION ALL
  SELECT 'monitors', 'Portable Monitors', 'portable-monitors', 8 UNION ALL

  SELECT 'audio', 'Bluetooth Speakers', 'bluetooth-speakers', 1 UNION ALL
  SELECT 'audio', 'Soundbars', 'soundbars', 2 UNION ALL
  SELECT 'audio', 'Wireless Earbuds', 'wireless-earbuds', 3 UNION ALL
  SELECT 'audio', 'Wired Earphones', 'wired-earphones', 4 UNION ALL
  SELECT 'audio', 'Over-Ear Headphones', 'over-ear-headphones', 5 UNION ALL
  SELECT 'audio', 'Gaming Headsets', 'gaming-headsets', 6 UNION ALL
  SELECT 'audio', 'Smart Speakers', 'smart-speakers', 7 UNION ALL
  SELECT 'audio', 'Studio Microphones', 'studio-microphones', 8 UNION ALL

  SELECT 'smartphones', 'iPhones', 'iphones', 1 UNION ALL
  SELECT 'smartphones', 'Samsung Galaxy', 'samsung-galaxy', 2 UNION ALL
  SELECT 'smartphones', 'Android Phones', 'android-phones', 3 UNION ALL
  SELECT 'smartphones', 'Brand New Phones', 'brand-new-phones', 4 UNION ALL
  SELECT 'smartphones', 'Ex-UK Phones', 'ex-uk-phones', 5 UNION ALL
  SELECT 'smartphones', 'Feature Phones', 'feature-phones', 6 UNION ALL
  SELECT 'smartphones', 'Tablets', 'tablets', 7 UNION ALL
  SELECT 'smartphones', 'Smartwatches', 'smartwatches', 8 UNION ALL

  SELECT 'printers', 'Inkjet Printers', 'inkjet-printers', 1 UNION ALL
  SELECT 'printers', 'Laser Printers', 'laser-printers', 2 UNION ALL
  SELECT 'printers', 'All-in-One Printers', 'all-in-one-printers', 3 UNION ALL
  SELECT 'printers', 'Wireless Printers', 'wireless-printers', 4 UNION ALL
  SELECT 'printers', 'Receipt Printers', 'receipt-printers', 5 UNION ALL
  SELECT 'printers', 'Barcode Printers', 'barcode-printers', 6 UNION ALL
  SELECT 'printers', 'Photo Printers', 'photo-printers', 7 UNION ALL
  SELECT 'printers', 'Scanner Machines', 'scanner-machines', 8 UNION ALL

  SELECT 'accessories', 'Laptop Bags', 'laptop-bags', 1 UNION ALL
  SELECT 'accessories', 'Keyboards', 'keyboards', 2 UNION ALL
  SELECT 'accessories', 'Wireless Mice', 'wireless-mice', 3 UNION ALL
  SELECT 'accessories', 'USB Flash Drives', 'usb-flash-drives', 4 UNION ALL
  SELECT 'accessories', 'External Hard Drives', 'external-hard-drives', 5 UNION ALL
  SELECT 'accessories', 'HDMI Cables', 'hdmi-cables', 6 UNION ALL
  SELECT 'accessories', 'Power Banks', 'power-banks', 7 UNION ALL
  SELECT 'accessories', 'WiFi Routers', 'wifi-routers', 8 UNION ALL
  SELECT 'accessories', 'Desktop Computers', 'desktop-computers', 9 UNION ALL
  SELECT 'accessories', 'Projectors', 'projectors', 10 UNION ALL
  SELECT 'accessories', 'UPS & Power Protection', 'ups-power-protection', 11 UNION ALL
  SELECT 'accessories', 'Networking Equipment', 'network-equipment', 12
) s JOIN product_categories p ON p.slug = s.parent AND p.parent_id IS NULL;

-- Products point at a category (main or sub). The text "category" column keeps the shown name.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'category_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN category_id INT UNSIGNED NULL AFTER category, ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Move the existing products into the new categories (only those not assigned yet).
UPDATE products p JOIN product_categories c ON c.slug = CASE
    WHEN p.title LIKE '%MacBook%' THEN 'macbooks'
    WHEN p.title LIKE '%2-in-1%' THEN '2-in-1-convertible-laptops'
    WHEN p.category IN ('Laptop & Computer', 'Laptop & Computers') THEN 'laptops'
    WHEN p.title LIKE '%Monitor%' AND p.title NOT LIKE '%Mouse%' THEN 'brand-new-monitors'
    WHEN p.title LIKE '%iPad%' OR p.category = 'Tablets' THEN 'tablets'
    WHEN p.title LIKE '%Mouse%' THEN 'wireless-mice'
    WHEN p.title LIKE '%iPhone%' AND p.title NOT LIKE '%Case%' AND p.title NOT LIKE '%Tech21%' THEN 'iphones'
    WHEN p.title LIKE '%Galaxy%' AND p.title NOT LIKE '%Buds%' THEN 'samsung-galaxy'
    WHEN p.category = 'Smartphone' THEN 'smartphones'
    WHEN p.category = 'Smartwatch' THEN 'smartwatches'
    WHEN p.category IN ('Wireless Earphones', 'Wireless Earbuds') OR p.title LIKE '%Buds%' THEN 'wireless-earbuds'
    WHEN p.category = 'Wired Earphones' THEN 'wired-earphones'
    WHEN p.category = 'Headphone' THEN 'over-ear-headphones'
    WHEN p.category IN ('Electronics', 'Computer Accessories', 'Computer Accessories,') THEN 'accessories'
    ELSE NULL END
SET p.category_id = c.id, p.category = c.name
WHERE p.category_id IS NULL;
