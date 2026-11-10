-- Repair the complete checkout order schema after partial/older migrations.
-- Safe to run repeatedly in phpMyAdmin; it preserves existing order data.
SET NAMES utf8mb4;

-- Checkout order columns. Add each independently so partial migration states recover.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'country');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN country VARCHAR(60) NOT NULL DEFAULT ''Kenya'' AFTER postal_code', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'delivery_method');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN delivery_method ENUM(''pickup'',''delivery'') NOT NULL DEFAULT ''delivery'' AFTER notes', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'promo_code');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN promo_code VARCHAR(30) NULL AFTER delivery_fee', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'promotion_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN promotion_id INT UNSIGNED NULL AFTER promo_code', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'discount');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN discount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER promotion_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'email_sent_at');
SET @sql := IF(@has_col = 0, 'ALTER TABLE orders ADD COLUMN email_sent_at DATETIME NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Pickup orders do not require a street address or city.
SET @address_nullable := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'address' AND is_nullable = 'YES');
SET @sql := IF(@address_nullable = 0, 'ALTER TABLE orders MODIFY address VARCHAR(255) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @city_nullable := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'city' AND is_nullable = 'YES');
SET @sql := IF(@city_nullable = 0, 'ALTER TABLE orders MODIFY city VARCHAR(100) NULL', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The checkout line-item INSERT also stores variant and preorder details.
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'variant_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN variant_id INT UNSIGNED NULL AFTER product_id', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'variant_label');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN variant_label VARCHAR(255) NULL AFTER title', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'sku');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN sku VARCHAR(64) NULL AFTER variant_label', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'image');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN image VARCHAR(255) NULL AFTER sku', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'is_preorder');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER line_total', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_col := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'order_items' AND column_name = 'promotion_id');
SET @sql := IF(@has_col = 0, 'ALTER TABLE order_items ADD COLUMN promotion_id INT UNSIGNED NULL AFTER is_preorder', 'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
