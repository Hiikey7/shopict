-- Checkout: customers choose free pickup at the shop or countrywide delivery; no free-delivery threshold.
-- Delivery details are now name, phone, email, town/city, country (Kenya) and notes.
--   mysql -u root onsus_shop < database/migrations/013_delivery_options.sql
SET NAMES utf8mb4;

ALTER TABLE orders
  ADD COLUMN delivery_method ENUM('pickup','delivery') NOT NULL DEFAULT 'delivery' AFTER notes,
  ADD COLUMN country VARCHAR(60) NOT NULL DEFAULT 'Kenya' AFTER postal_code,
  MODIFY address VARCHAR(255) NULL,
  MODIFY city VARCHAR(100) NULL;

DELETE FROM settings WHERE `key` = 'free_delivery_over';
