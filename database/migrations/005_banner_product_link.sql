-- Lets a banner point at a product: an empty product name or price is then filled from it.
--   mysql -u root onsus_shop < database/migrations/005_banner_product_link.sql
SET NAMES utf8mb4;
ALTER TABLE banners
  ADD COLUMN product_id INT UNSIGNED NULL AFTER meta,
  ADD CONSTRAINT fk_banners_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- Side banners now read: company name / product name / price. The old third line moves to the price.
UPDATE banners SET price = subtitle, subtitle = NULL WHERE placement = 'hero_side_1' AND (price IS NULL OR price = '') AND subtitle IS NOT NULL;
