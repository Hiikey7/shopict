-- Allow choosing the hero price prefix and hiding the hero price when not needed.
--   mysql -u root onsus_shop < database/migrations/021_hero_price_visibility.sql
SET NAMES utf8mb4;
ALTER TABLE banners
  ADD COLUMN hero_price_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether to show the main hero price' AFTER hero_price_color;
