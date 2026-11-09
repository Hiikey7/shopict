-- "Visit Our Shop" map section (replaces the Authorised Brands strip on the home page).
--   mysql -u root onsus_shop < database/migrations/008_store_map.sql
SET NAMES utf8mb4;

INSERT INTO sections (code, title, is_active, max_products, note, position)
VALUES ('store_map', 'Visit Our Shop', 1, 0, 'Google map to the shop with address, hours and directions. The map location is set in Settings.', 15)
ON DUPLICATE KEY UPDATE code = code;

-- What Google Maps searches for (keep it simple: building and town).
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('map_query', 'Laxmi Plaza, Nairobi');

-- The brands strip is hidden, not deleted (turn it back on in Admin → Sections).
UPDATE sections SET is_active = 0 WHERE code = 'brands';
