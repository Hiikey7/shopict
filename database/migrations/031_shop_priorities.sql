-- Allow admins to pin products to the top of the shop listing globally or within a category.
-- Safe to run more than once.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS product_shop_priorities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL,
  position INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_shop_priority_product_scope (product_id, category_id, position),
  KEY idx_shop_priority_scope_position (category_id, position),
  CONSTRAINT fk_shop_priority_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_shop_priority_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
