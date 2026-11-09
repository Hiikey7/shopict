-- Search engine optimisation: editable SEO fields (empty = written automatically from the product's details),
-- category and brand landing-page text, buying guides (blog) and site-wide SEO settings.

ALTER TABLE products
  ADD COLUMN seo_title VARCHAR(120) NULL AFTER specs,
  ADD COLUMN meta_description VARCHAR(320) NULL AFTER seo_title,
  ADD COLUMN focus_keyword VARCHAR(120) NULL AFTER meta_description,
  ADD COLUMN secondary_keywords VARCHAR(600) NULL AFTER focus_keyword,
  ADD COLUMN faqs TEXT NULL AFTER secondary_keywords,
  ADD COLUMN image_alts TEXT NULL AFTER faqs,
  ADD COLUMN gtin VARCHAR(20) NULL AFTER image_alts,
  ADD COLUMN mpn VARCHAR(64) NULL AFTER gtin,
  ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0 AFTER mpn;

ALTER TABLE product_categories
  ADD COLUMN seo_title VARCHAR(120) NULL,
  ADD COLUMN meta_description VARCHAR(320) NULL,
  ADD COLUMN intro TEXT NULL,
  ADD COLUMN guide TEXT NULL,
  ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE brands
  ADD COLUMN seo_title VARCHAR(120) NULL,
  ADD COLUMN meta_description VARCHAR(320) NULL,
  ADD COLUMN intro TEXT NULL,
  ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

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
  author VARCHAR(80) NULL,
  status ENUM('draft', 'review', 'published') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY posts_slug (slug),
  KEY posts_status (status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('seo_google_verification', NULL),
  ('seo_bing_verification', NULL),
  ('seo_ga4_id', NULL),
  ('seo_ai_training', 'allow'),
  ('seo_return_days', '3');
