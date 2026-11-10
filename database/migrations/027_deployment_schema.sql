-- Complete the schema expected by the current storefront after importing
-- onsus_shop.sql. Safe to run more than once; does not drop or delete data.
SET NAMES utf8mb4;

-- Home category tiles link to product_categories.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'category_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE categories ADD COLUMN category_id INT UNSIGNED NULL COMMENT ''Product category the tile links to'' AFTER img_src, ADD CONSTRAINT fk_categories_product_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Multiple subcategory assignments.
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

-- Buying guides and their current SEO fields.
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

-- Product SEO fields used by storefront and admin pages.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE products ADD COLUMN seo_title VARCHAR(120) NULL, ADD COLUMN meta_description VARCHAR(320) NULL, ADD COLUMN focus_keyword VARCHAR(120) NULL, ADD COLUMN secondary_keywords VARCHAR(600) NULL, ADD COLUMN faqs TEXT NULL, ADD COLUMN image_alts TEXT NULL, ADD COLUMN gtin VARCHAR(20) NULL, ADD COLUMN mpn VARCHAR(64) NULL, ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Category and brand landing-page SEO fields.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'product_categories' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE product_categories ADD COLUMN seo_title VARCHAR(120) NULL, ADD COLUMN meta_description VARCHAR(320) NULL, ADD COLUMN intro TEXT NULL, ADD COLUMN guide TEXT NULL, ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'brands' AND column_name = 'seo_title');
SET @sql := IF(@has_col = 0, 'ALTER TABLE brands ADD COLUMN seo_title VARCHAR(120) NULL, ADD COLUMN meta_description VARCHAR(320) NULL, ADD COLUMN intro TEXT NULL, ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_google_verification', NULL), ('seo_bing_verification', NULL),
  ('seo_ga4_id', NULL), ('seo_ai_training', 'allow'), ('seo_return_days', '3');

-- Connect existing legacy home tiles to a sensible main product category.
UPDATE categories c
JOIN product_categories pc ON pc.slug = CASE
  WHEN LOWER(c.title) LIKE '%laptop%' OR LOWER(c.title) LIKE '%computer%' THEN 'laptops'
  WHEN LOWER(c.title) LIKE '%monitor%' OR LOWER(c.title) LIKE '%screen%' THEN 'monitors'
  WHEN LOWER(c.title) LIKE '%audio%' OR LOWER(c.title) LIKE '%speaker%' THEN 'audio'
  WHEN LOWER(c.title) LIKE '%phone%' OR LOWER(c.title) LIKE '%tablet%' OR LOWER(c.title) LIKE '%mobile%' THEN 'smartphones'
  WHEN LOWER(c.title) LIKE '%printer%' THEN 'printers'
  ELSE 'accessories'
END
SET c.category_id = pc.id
WHERE c.category_id IS NULL;

-- The starter banner CTAs should open the catalog, not loop back to the homepage.
UPDATE banners SET link_url = 'shop.php' WHERE link_url = './';
