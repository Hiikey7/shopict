-- Store contact details shown in the bar above the header (edit them in Admin → Settings).
--   mysql -u root onsus_shop < database/migrations/004_store_contact_bar.sql
SET NAMES utf8mb4;
INSERT IGNORE INTO settings (`key`, `value`) VALUES
  ('store_phone', '+254713869018'),
  ('store_address', 'Nairobi, Laxmi Plaza, 3rd Flr, Room No 5'),
  ('store_hours', 'Open Mon-Sat 8am-6pm');
