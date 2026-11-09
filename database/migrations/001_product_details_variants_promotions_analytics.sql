-- Adds full product details, product variants, promotions and visitor analytics.
-- Run once on an existing onsus_shop database:
--   mysql -u root onsus_shop < database/migrations/001_product_details_variants_promotions_analytics.sql
-- (database/onsus_shop.sql already includes these changes for fresh installs.)
SET NAMES utf8mb4;

-- ---------------------------------------------------------------- product details
ALTER TABLE products
  ADD COLUMN sku          VARCHAR(64)  NULL AFTER title,
  ADD COLUMN brand        VARCHAR(100) NULL AFTER category,
  ADD COLUMN description  TEXT         NULL COMMENT 'Quick View "About this item": one bullet per line' AFTER brand,
  ADD COLUMN specs        TEXT         NULL COMMENT 'JSON list of [label, value] pairs' AFTER description,
  ADD COLUMN option_names VARCHAR(255) NULL COMMENT 'JSON list of variant option names, e.g. ["RAM","Storage"]' AFTER specs,
  ADD COLUMN is_active    TINYINT(1)   NOT NULL DEFAULT 1 AFTER discount;

-- ---------------------------------------------------------------- variants
-- One row per sellable version of a product, e.g. a laptop with 8GB or 16GB RAM.
CREATE TABLE product_variants (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  options     VARCHAR(500) NOT NULL COMMENT 'JSON object: option name => value',
  price       DECIMAL(12,3) NOT NULL,
  old_price   DECIMAL(12,3) NULL,
  sku         VARCHAR(64) NULL,
  stock       INT UNSIGNED NULL COMMENT 'NULL = not tracked',
  is_default  TINYINT(1) NOT NULL DEFAULT 0,
  position    SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  KEY idx_variant_product (product_id, position),
  CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart rows now point at a variant (0 = product without variants).
ALTER TABLE cart_items
  ADD COLUMN variant_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER product_id,
  ADD UNIQUE KEY uq_cart_visitor_item (visitor_id, product_id, variant_id),
  DROP INDEX uq_cart_visitor_product;

-- ---------------------------------------------------------------- promotions
-- Automatic discounts for a period: on everything, one category, or chosen products.
CREATE TABLE promotions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  badge          VARCHAR(30) NULL COMMENT 'Short label shown in Quick View, e.g. Black Friday',
  discount_type  ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value DECIMAL(12,3) NOT NULL,
  applies_to     ENUM('all','category','products') NOT NULL DEFAULT 'all',
  category       VARCHAR(100) NULL,
  starts_at      DATETIME NULL,
  ends_at        DATETIME NULL,
  is_active      TINYINT(1) NOT NULL DEFAULT 1,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promotion_products (
  promotion_id INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NOT NULL,
  PRIMARY KEY (promotion_id, product_id),
  CONSTRAINT fk_promo_products_promo FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE CASCADE,
  CONSTRAINT fk_promo_products_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- analytics
-- page_view: a storefront page was loaded. product_view: Quick View opened. add_to_cart.
CREATE TABLE analytics_events (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type        VARCHAR(20) NOT NULL,
  path        VARCHAR(255) NOT NULL DEFAULT '/',
  product_id  INT UNSIGNED NULL,
  variant_id  INT UNSIGNED NULL,
  visitor_id  CHAR(32) NOT NULL,
  referrer    VARCHAR(255) NULL COMMENT 'Referring host, other sites only',
  device      VARCHAR(10) NOT NULL DEFAULT 'desktop',
  created_at  DATETIME NOT NULL,
  KEY idx_events_type_time (type, created_at),
  KEY idx_events_product (product_id, type),
  KEY idx_events_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
