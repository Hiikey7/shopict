-- Promo codes: a promotion with a code applies only when the shopper enters it at checkout
-- (promotions without a code keep applying automatically). Orders record the code and the saving.
--   mysql -u root onsus_shop < database/migrations/012_promo_codes.sql
SET NAMES utf8mb4;

ALTER TABLE promotions
  ADD COLUMN code VARCHAR(30) NULL COMMENT 'Promo code, stored in capitals; NULL = applies automatically' AFTER badge,
  ADD COLUMN min_spend DECIMAL(12,2) NULL COMMENT 'Cart subtotal needed for the code to work' AFTER code,
  ADD UNIQUE KEY uq_promotions_code (code);

ALTER TABLE orders
  ADD COLUMN promo_code VARCHAR(30) NULL AFTER delivery_fee,
  ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER promo_code;
