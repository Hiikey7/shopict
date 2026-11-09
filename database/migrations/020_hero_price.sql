-- Add optional label, price and colour to the main hero banner.
--   mysql -u root onsus_shop < database/migrations/020_hero_price.sql
SET NAMES utf8mb4;
ALTER TABLE banners
  ADD COLUMN hero_price_color CHAR(7) NULL COMMENT 'Hero main price colour' AFTER button_color;
