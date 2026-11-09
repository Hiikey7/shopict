-- A product can be in several subcategories (Admin → Products → Subcategories tick boxes).
-- products.category_id stays the product's own (first) category; extra ones are listed here.
--   mysql -u root onsus_shop < database/migrations/016_product_subcategories.sql
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS product_category_links (
  product_id  INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, category_id),
  KEY idx_pcl_category (category_id),
  CONSTRAINT fk_pcl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_pcl_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing products: their current subcategory becomes their first ticked subcategory.
INSERT IGNORE INTO product_category_links (product_id, category_id)
SELECT p.id, p.category_id FROM products p JOIN product_categories c ON c.id = p.category_id WHERE c.parent_id IS NOT NULL;
