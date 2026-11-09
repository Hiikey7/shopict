-- Free colour choice for banners (used by the promo slider): text and background colours as #rrggbb.
--   mysql -u root onsus_shop < database/migrations/007_banner_custom_colours.sql
SET NAMES utf8mb4;
ALTER TABLE banners
  ADD COLUMN text_hex CHAR(7) NULL COMMENT 'Custom title colour, e.g. #1d2733' AFTER price_color,
  ADD COLUMN bg_hex   CHAR(7) NULL COMMENT 'Custom background colour, shown under / instead of the background image' AFTER text_hex;
