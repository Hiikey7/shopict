-- Online orders (checkout and WhatsApp); removes the newsletter and storefront customer accounts.
-- Run once on an existing onsus_shop database:
--   mysql -u root onsus_shop < database/migrations/002_orders_remove_newsletter_accounts.sql
SET NAMES utf8mb4;

-- ---------------------------------------------------------------- removed features
DROP TABLE IF EXISTS newsletter_subscribers;
-- Only admin accounts remain (the storefront no longer has customer logins).
DELETE FROM users WHERE is_admin = 0;

-- ---------------------------------------------------------------- orders
CREATE TABLE orders (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number   VARCHAR(20) NULL UNIQUE COMMENT 'e.g. ONS-1001, set right after insert',
  access_token   CHAR(32) NOT NULL COMMENT 'Lets the customer open their confirmation page',
  channel        ENUM('web','whatsapp') NOT NULL DEFAULT 'web',
  status         ENUM('new','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
  customer_name  VARCHAR(120) NOT NULL,
  email          VARCHAR(255) NOT NULL,
  phone          VARCHAR(30) NOT NULL,
  address        VARCHAR(255) NOT NULL,
  city           VARCHAR(100) NOT NULL,
  region         VARCHAR(100) NULL,
  postal_code    VARCHAR(20) NULL,
  notes          TEXT NULL COMMENT 'Delivery instructions from the customer',
  payment_method VARCHAR(30) NOT NULL DEFAULT 'pay_on_delivery',
  subtotal       DECIMAL(12,2) NOT NULL,
  delivery_fee   DECIMAL(12,2) NOT NULL DEFAULT 0,
  total          DECIMAL(12,2) NOT NULL,
  admin_note     TEXT NULL COMMENT 'Internal note, not shown to the customer',
  visitor_id     CHAR(32) NULL,
  email_sent_at  DATETIME NULL,
  created_at     DATETIME NOT NULL,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_orders_status (status, created_at),
  KEY idx_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A snapshot of each line, so an order still reads correctly after the product changes.
CREATE TABLE order_items (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id      INT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED NULL,
  variant_id    INT UNSIGNED NULL,
  title         VARCHAR(255) NOT NULL,
  variant_label VARCHAR(255) NULL,
  sku           VARCHAR(64) NULL,
  image         VARCHAR(255) NULL,
  price         DECIMAL(12,2) NOT NULL,
  quantity      INT UNSIGNED NOT NULL,
  line_total    DECIMAL(12,2) NOT NULL,
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store settings used by checkout (edit them in Admin → Settings).
INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('store_name', 'Onsus'),
  ('whatsapp_number', NULL),
  ('delivery_fee', '0'),
  ('free_delivery_over', '250'),
  ('order_notify_email', NULL);
