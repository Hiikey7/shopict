-- Home "Product Category" slider: one tile per main product category, each linking to its shop page.
--   mysql -u root onsus_shop < database/migrations/011_home_category_tiles.sql
SET NAMES utf8mb4;

ALTER TABLE categories
  ADD COLUMN category_id INT UNSIGNED NULL COMMENT 'Product category the tile links to (shop.php?cat=slug)' AFTER img_src,
  ADD CONSTRAINT fk_categories_product_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL;

-- Replace the template's ten tiles with the six main categories.
DELETE FROM categories;
INSERT INTO categories (title, img_src, category_id, position)
SELECT t.title, t.img, pc.id, t.pos FROM (
  SELECT 'Laptops' AS title, 'images/uploads/20261105-a636ddc6cb8d.png' AS img, 'laptops' AS slug, 1 AS pos UNION ALL
  SELECT 'Monitors', 'images/uploads/20261105-1424ff0b4de9.png', 'monitors', 2 UNION ALL
  SELECT 'Audio', 'images/uploads/20261105-bb29162fa573.png', 'audio', 3 UNION ALL
  SELECT 'Smartphones', 'images/uploads/20261105-01d9d14a12e1.png', 'smartphones', 4 UNION ALL
  SELECT 'Printers', 'images/uploads/20261105-c1fb095f2729.png', 'printers', 5 UNION ALL
  SELECT 'Accessories', 'images/collection/accessories.svg', 'accessories', 6
) t JOIN product_categories pc ON pc.slug = t.slug;
