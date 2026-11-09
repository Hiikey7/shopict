-- Reduce scans for the common active-product listing and filter queries.
SET @has_index := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_active_id');
SET @sql := IF(@has_index = 0, 'ALTER TABLE products ADD INDEX idx_products_active_id (is_active, id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_index := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_active_category_id');
SET @sql := IF(@has_index = 0, 'ALTER TABLE products ADD INDEX idx_products_active_category_id (is_active, category_id, id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_index := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_active_price_id');
SET @sql := IF(@has_index = 0, 'ALTER TABLE products ADD INDEX idx_products_active_price_id (is_active, price, id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_index := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'products' AND index_name = 'idx_products_active_brand');
SET @sql := IF(@has_index = 0, 'ALTER TABLE products ADD INDEX idx_products_active_brand (is_active, brand)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
