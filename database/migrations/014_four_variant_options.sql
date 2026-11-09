-- Products can have up to four variant options (e.g. RAM, Storage, Colour, Size): room for the longer option JSON.
--   mysql -u root onsus_shop < database/migrations/014_four_variant_options.sql
SET NAMES utf8mb4;
ALTER TABLE product_variants MODIFY options VARCHAR(1000) NOT NULL COMMENT 'JSON object: option name => value';
