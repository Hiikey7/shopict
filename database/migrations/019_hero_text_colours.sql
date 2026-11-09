-- Per-text colour controls for the main hero banner title, highlight and subtitle.
--   mysql -u root onsus_shop < database/migrations/019_hero_text_colours.sql
SET NAMES utf8mb4;
ALTER TABLE banners
  ADD COLUMN title_color CHAR(7) NULL COMMENT 'Hero main title colour' AFTER bg_hex,
  ADD COLUMN title_highlight_color CHAR(7) NULL COMMENT 'Hero main title highlight colour' AFTER title_color,
  ADD COLUMN subtitle_color CHAR(7) NULL COMMENT 'Hero main subtitle colour' AFTER title_highlight_color,
  ADD COLUMN button_color VARCHAR(10) NULL COMMENT 'Hero main button background: red or white' AFTER subtitle_color;
