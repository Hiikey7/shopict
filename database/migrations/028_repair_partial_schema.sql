-- Repair missing schema pieces independently after a partial/older migration.
-- Safe to run repeatedly; does not drop tables or product data.
SET NAMES utf8mb4;

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
  status ENUM('draft', 'review', 'published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY posts_slug (slug),
  KEY posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add each product SEO field separately so partially applied migrations recover cleanly.
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

-- Older posts tables may predate the editorial keyword migration.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'posts' AND column_name = 'focus_keyword');
SET @sql := IF(@has_col = 0, 'ALTER TABLE posts ADD COLUMN focus_keyword VARCHAR(120) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'posts' AND column_name = 'secondary_keywords');
SET @sql := IF(@has_col = 0, 'ALTER TABLE posts ADD COLUMN secondary_keywords VARCHAR(600) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Ensure the SEO editor's fields exist even after a partially applied 018 migration.
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

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_google_verification', NULL), ('seo_bing_verification', NULL),
  ('seo_ga4_id', NULL), ('seo_ai_training', 'allow'), ('seo_return_days', '3');
