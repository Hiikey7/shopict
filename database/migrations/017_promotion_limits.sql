-- Promotions: optional usage limit ("first 300 orders"). Orders remember which promotion they used.
--   mysql -u root onsus_shop < database/migrations/017_promotion_limits.sql
SET NAMES utf8mb4;
ALTER TABLE promotions ADD COLUMN max_uses INT UNSIGNED NULL COMMENT 'NULL = no limit' AFTER min_spend;
ALTER TABLE orders ADD COLUMN promotion_id INT UNSIGNED NULL COMMENT 'Promotion of the promo code used' AFTER promo_code;
ALTER TABLE order_items ADD COLUMN promotion_id INT UNSIGNED NULL COMMENT 'Automatic promotion applied to this line' AFTER is_preorder;

-- Earlier orders that used a promo code count towards that promotion.
UPDATE orders o JOIN promotions p ON p.code = o.promo_code SET o.promotion_id = p.id
 WHERE o.promotion_id IS NULL AND o.promo_code IS NOT NULL;
