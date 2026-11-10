-- Bring older or partially migrated databases up to the schema used by the current admin.
-- Safe to run repeatedly. This only adds missing fields/tables and preserves existing data.
SET NAMES utf8mb4;

-- Categories and products can be assigned to multiple product categories.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'category_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE categories ADD COLUMN category_id INT UNSIGNED NULL AFTER img_src', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
CREATE TABLE IF NOT EXISTS product_category_links (
  product_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, category_id),
  KEY idx_pcl_category (category_id),
  CONSTRAINT fk_pcl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pcl_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO product_category_links (product_id, category_id)
SELECT id, category_id FROM products WHERE category_id IS NOT NULL;

-- Promotion creation, targeting and checkout depend on these columns and tables.
CREATE TABLE IF NOT EXISTS promotions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  badge VARCHAR(30) NULL,
  code VARCHAR(30) NULL,
  min_spend DECIMAL(12,2) NULL,
  max_uses INT UNSIGNED NULL,
  discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(12,3) NOT NULL,
  applies_to ENUM('all','category','products') NOT NULL DEFAULT 'all',
  category VARCHAR(100) NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_promotions_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promotions' AND column_name = 'code');
SET @sql := IF(@has_col = 0, 'ALTER TABLE promotions ADD COLUMN code VARCHAR(30) NULL AFTER badge', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promotions' AND column_name = 'min_spend');
SET @sql := IF(@has_col = 0, 'ALTER TABLE promotions ADD COLUMN min_spend DECIMAL(12,2) NULL AFTER code', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'promotions' AND column_name = 'max_uses');
SET @sql := IF(@has_col = 0, 'ALTER TABLE promotions ADD COLUMN max_uses INT UNSIGNED NULL AFTER min_spend', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_code_index := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'promotions' AND column_name = 'code' AND non_unique = 0);
SET @duplicate_codes := (SELECT COUNT(*) FROM (SELECT code FROM promotions WHERE code IS NOT NULL GROUP BY code HAVING COUNT(*) > 1) AS duplicate_promo_codes);
SET @sql := IF(@has_code_index = 0 AND @duplicate_codes = 0, 'ALTER TABLE promotions ADD UNIQUE KEY uq_promotions_code (code)', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
CREATE TABLE IF NOT EXISTS promotion_products (
  promotion_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (promotion_id, product_id),
  KEY idx_promotion_products_product (product_id),
  CONSTRAINT fk_promo_products_promo FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_promo_products_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add columns individually to recover from migrations that stopped part-way through.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'is_preorder');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN seo_title VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'meta_description');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN meta_description VARCHAR(320) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'focus_keyword');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN focus_keyword VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'secondary_keywords');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN secondary_keywords VARCHAR(600) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'faqs');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN faqs TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'image_alts');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN image_alts TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'gtin');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN gtin VARCHAR(20) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'mpn');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN mpn VARCHAR(64) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'noindex');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Banner editor options used by the current home page.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'product_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN product_id INT UNSIGNED NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'text_hex');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN text_hex CHAR(7) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'bg_hex');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN bg_hex CHAR(7) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'title_color');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN title_color CHAR(7) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'title_highlight_color');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN title_highlight_color CHAR(7) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'subtitle_color');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN subtitle_color CHAR(7) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'button_color');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN button_color VARCHAR(10) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'hero_price_color');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN hero_price_color CHAR(7) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'banners' AND column_name = 'hero_price_visible');
SET @sql := IF(@has_col = 0, 'ALTER TABLE banners ADD COLUMN hero_price_visible TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Four-option variants need more room than early schemas allowed.
SET @options_length := (SELECT character_maximum_length FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_variants' AND column_name = 'options');
SET @sql := IF(COALESCE(@options_length, 0) < 1000, 'ALTER TABLE product_variants MODIFY options VARCHAR(1000) NOT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN seo_title VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'meta_description');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN meta_description VARCHAR(320) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'intro');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN intro TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'guide');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN guide TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'updated_at');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN seo_title VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'meta_description');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN meta_description VARCHAR(320) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'intro');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN intro TEXT NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'updated_at');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  excerpt VARCHAR(320) NULL,
  body MEDIUMTEXT NULL,
  image VARCHAR(255) NULL,
  category_id INT UNSIGNED NULL,
  product_ids VARCHAR(255) NULL,
  seo_title VARCHAR(120) NULL,
  meta_description VARCHAR(320) NULL,
  focus_keyword VARCHAR(120) NULL,
  secondary_keywords VARCHAR(600) NULL,
  author VARCHAR(80) NULL,
  status ENUM('draft','review','published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY posts_slug (slug),
  KEY posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'posts' AND column_name = 'focus_keyword');
SET @sql := IF(@has_col = 0, 'ALTER TABLE posts ADD COLUMN focus_keyword VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'posts' AND column_name = 'secondary_keywords');
SET @sql := IF(@has_col = 0, 'ALTER TABLE posts ADD COLUMN secondary_keywords VARCHAR(600) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Checkout and order management schema used by the Orders screens.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'country');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN country VARCHAR(60) NOT NULL DEFAULT ''Kenya'' AFTER postal_code', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'delivery_method');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN delivery_method ENUM(''pickup'',''delivery'') NOT NULL DEFAULT ''delivery'' AFTER notes', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'promo_code');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN promo_code VARCHAR(30) NULL AFTER delivery_fee', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'promotion_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN promotion_id INT UNSIGNED NULL AFTER promo_code', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'discount');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER promotion_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'email_sent_at');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN email_sent_at DATETIME NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @address_nullable := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'address' AND is_nullable = 'YES');
SET @sql := IF(@address_nullable = 0, 'ALTER TABLE orders MODIFY address VARCHAR(255) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @city_nullable := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'city' AND is_nullable = 'YES');
SET @sql := IF(@city_nullable = 0, 'ALTER TABLE orders MODIFY city VARCHAR(100) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'variant_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN variant_id INT UNSIGNED NULL AFTER product_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'variant_label');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN variant_label VARCHAR(255) NULL AFTER title', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'sku');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN sku VARCHAR(64) NULL AFTER variant_label', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'image');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN image VARCHAR(255) NULL AFTER sku', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'is_preorder');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER line_total', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'promotion_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN promotion_id INT UNSIGNED NULL AFTER is_preorder', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_google_verification', NULL), ('seo_bing_verification', NULL),
  ('seo_ga4_id', NULL), ('seo_ai_training', 'allow'), ('seo_return_days', '3');
