-- Converts the template's US-dollar prices to Kenyan shillings at 129 KES = 1 USD, rounded to
-- whole shillings. Safe to run twice: it does nothing once settings.currency is 'KES'.
--   mysql -u root onsus_shop < database/migrations/003_prices_to_kes.sql
SET NAMES utf8mb4;

SET @todo := (SELECT COUNT(*) = 0 FROM settings WHERE `key` = 'currency' AND `value` = 'KES');
SET @rate := 129;

UPDATE products
   SET price     = ROUND(price * @rate),
       old_price = ROUND(old_price * @rate),
       discount  = ROUND(discount * @rate)
 WHERE @todo;

UPDATE product_variants
   SET price     = ROUND(price * @rate),
       old_price = ROUND(old_price * @rate)
 WHERE @todo;

UPDATE promotions SET discount_value = ROUND(discount_value * @rate) WHERE @todo AND discount_type = 'fixed';

-- Banner prices are free text such as "$1.399" (= $1,399), "2,361" or "$250".
UPDATE banners
   SET price = CONCAT(
         IF(placement = 'featured_card', '', 'KSh '),
         FORMAT(CAST(REPLACE(REPLACE(REPLACE(TRIM(price), '$', ''), '.', ''), ',', '') AS UNSIGNED) * @rate, 0)
       )
 WHERE @todo AND price REGEXP '^\\$?[0-9][0-9.,]*$';

UPDATE settings SET `value` = ROUND(`value` * @rate) WHERE @todo AND `key` IN ('delivery_fee', 'free_delivery_over') AND `value` REGEXP '^[0-9.]+$';

INSERT INTO settings (`key`, `value`) VALUES ('currency', 'KES') ON DUPLICATE KEY UPDATE `value` = 'KES';
