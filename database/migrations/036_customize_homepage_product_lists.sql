-- Reorder homepage product sections using the supplied price ranges and model hints.
-- Updates each section with all requested listings that resolve. Unmatched rows are reported.
-- Product rows, prices, variants, visibility and inventory are left unchanged.
-- Run in phpMyAdmin against the storefront database. Review the mapping output afterwards.

START TRANSACTION;

INSERT INTO sections (code, title, is_active, max_products, note, position)
VALUES ('homepage_featured_bottom', 'Featured Product', 1, 10,
        'Additional product slider below the homepage product lists, near On-sale Products.', 16)
ON DUPLICATE KEY UPDATE title = VALUES(title), is_active = 1,
                        max_products = 10, note = VALUES(note);

CREATE TEMPORARY TABLE tmp_home_targets (
  section_code VARCHAR(50) NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  title_hint VARCHAR(120) NOT NULL,
  low_price DECIMAL(12,2) NOT NULL,
  high_price DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (section_code, position)
) ENGINE=InnoDB;

INSERT INTO tmp_home_targets (section_code, position, title_hint, low_price, high_price) VALUES
  ('trending',1,'ProBook 440 G11',112000,128000),
  ('trending',2,'Essential S30GD',16995,24995),
  ('trending',3,'EcoTank L3250',30000,30000),
  ('trending',4,'MacBook Neo',112000,127000),
  ('trending',5,'C270 HD',3900,3900),
  ('trending',6,'Starlink Mini',32000,32000),

  ('smart_home',1,'EliteBook 830 G8',39500,58500),
  ('smart_home',2,'Essential S30GD',16995,24995),
  ('smart_home',3,'XPS 13 DX13260',123000,123000),
  ('smart_home',4,'EcoTank L3250',30000,30000),
  ('smart_home',5,'C270 HD',3900,3900),
  ('smart_home',6,'Pro Tower 290',98500,98500),
  ('smart_home',7,'OmniBook 5 Flip',148000,148000),
  ('smart_home',8,'P22',15000,15000),
  ('smart_home',9,'PIXMA TS3640',16000,16000),
  ('smart_home',10,'ProBook 440 G11',112000,128000),
  ('smart_home',11,'Back-UPS 650VA',9000,9000),
  ('smart_home',12,'OmniBook Ultra Flip',204000,204000),
  ('smart_home',13,'Precision 5560',85000,85000),
  ('smart_home',14,'Odyssey G5 G55C',42000,47000),
  ('smart_home',15,'EliteBook 1040 G9',79000,79000),
  ('smart_home',16,'MX Master 4',18000,18000),
  ('smart_home',17,'DeskJet 2975',15500,15500),
  ('smart_home',18,'MacBook Neo',112000,127000),
  ('smart_home',19,'527sa',35000,35000),
  ('smart_home',20,'ThinkPad X13',36000,49500),
  ('smart_home',21,'Starlink Mini',32000,32000),
  ('smart_home',22,'Victus 15',123000,188000),
  ('smart_home',23,'Celebrat A28',2500,2500),
  ('smart_home',24,'Pavilion x360 14',113000,113000),
  ('smart_home',25,'All-in-One Desktop',113000,139000),
  ('smart_home',26,'G3410',27000,27000),
  ('smart_home',27,'ZBook Firefly 14 G9',85000,85000),
  ('smart_home',28,'Smart Monitor M7',61230,79435),
  ('smart_home',29,'EliteBook 1030 G8',61500,61500),
  ('smart_home',30,'LaserJet 141w',31000,31000),
  ('smart_home',31,'ThinkPad E14 Gen 4',79500,79500),
  ('smart_home',32,'EliteBook 630 G10',65500,65500),
  ('smart_home',33,'EcoTank L3210',28000,28000),
  ('smart_home',34,'OmniBook X Flip 14',131000,166000),
  ('smart_home',35,'1040 G7',58500,64000),
  ('smart_home',36,'ViewFinity S6',39995,59995),
  ('smart_home',37,'MacBook Pro 13-inch M1',79000,133500),
  ('smart_home',38,'IdeaPad Slim 3',98000,98000),
  ('smart_home',39,'OMEN 16',253000,253000),
  ('smart_home',40,'UPS 1000VA',15000,15000),

  ('top_20',1,'EliteBook 1040 G8',61000,65000),
  ('top_20',2,'G3410',27000,27000),
  ('top_20',3,'ThinkPad E14 Gen 4',79500,79500),
  ('top_20',4,'24sa',21000,21000),
  ('top_20',5,'EcoTank L3210',28000,28000),
  ('top_20',6,'MacBook Pro 13-inch M1',79000,133500),
  ('top_20',7,'Smart Monitor M7',61230,79435),
  ('top_20',8,'LaserJet 141w',31000,31000),
  ('top_20',9,'IdeaCentre Neo 50A 24',115000,115000),
  ('top_20',10,'Mecer UPS 1KVA',15000,15000),

  ('featured_products',1,'1040 G7',58500,64000),
  ('featured_products',2,'Curved Gaming Monitor',45000,45000),
  ('featured_products',3,'LaserJet 236dw',42000,42000),
  ('featured_products',4,'Celebrat A18',2500,2500),
  ('featured_products',5,'OmniBook 5 Flip 14',148000,148000),
  ('featured_products',6,'CanoScan LiDE 400',14500,14500),
  ('featured_products',7,'ViewFinity S6',39995,59995),
  ('featured_products',8,'All-in-One Desktop',155000,155000),
  ('featured_products',9,'UPS 1000VA',15000,15000),
  ('featured_products',10,'IdeaPad Slim 3',98000,98000),

  ('top_selling',1,'EliteBook 830 G8',39500,58500),
  ('top_selling',2,'ProBook 440 G11',112000,128000),
  ('top_selling',3,'EcoTank L3250',30000,30000),
  ('top_selling',4,'P22',15000,15000),
  ('top_selling',5,'G3410',27000,27000),
  ('top_selling',6,'ThinkPad X13',36000,49500),
  ('top_selling',7,'C270 HD',3900,3900),
  ('top_selling',8,'DeskJet 2975',15500,15500),
  ('top_selling',9,'MacBook Neo',112000,127000),
  ('top_selling',10,'Back-UPS 650VA',9000,9000),

  ('on_sale',1,'EliteBook 830 G8',39500,58500),
  ('on_sale',2,'P22',15000,15000),
  ('on_sale',3,'PIXMA TS3640',16000,16000),
  ('on_sale',4,'Celebrat A28',2500,2500),
  ('on_sale',5,'Back-UPS 650VA',9000,9000),
  ('on_sale',6,'EcoTank L3210',28000,28000),
  ('on_sale',7,'DeskJet 2975',15500,15500),
  ('on_sale',8,'Mecer UPS 650VA',6800,6800),
  ('on_sale',9,'C270 HD',3900,3900),
  ('on_sale',10,'Portable Projector',18000,18000),

  ('recently_viewed',1,'Victus 15',123000,188000),
  ('recently_viewed',2,'Odyssey G5 G55C',42000,47000),
  ('recently_viewed',3,'MacBook Pro 13-inch M1',79000,133500),
  ('recently_viewed',4,'Starlink Mini',32000,32000),
  ('recently_viewed',5,'MX Master 4',18000,18000),
  ('recently_viewed',6,'EcoTank L5290',56000,56000),
  ('recently_viewed',7,'Legion 5 16RX9',218000,218000),
  ('recently_viewed',8,'Smart Monitor M7',61230,79435),
  ('recently_viewed',9,'Onyx Studio 9',35000,35000),
  ('recently_viewed',10,'OmniBook X Flip 14',131000,166000),

  ('homepage_featured_bottom',1,'Precision 5560',85000,85000),
  ('homepage_featured_bottom',2,'Victus 15',123000,188000),
  ('homepage_featured_bottom',3,'Odyssey G5 G55C',42000,47000),
  ('homepage_featured_bottom',4,'EcoTank L3250',30000,30000),
  ('homepage_featured_bottom',5,'MacBook Neo',112000,127000),
  ('homepage_featured_bottom',6,'Starlink Mini',32000,32000),
  ('homepage_featured_bottom',7,'All-in-One Desktop',113000,139000),
  ('homepage_featured_bottom',8,'MX Master 4',18000,18000),
  ('homepage_featured_bottom',9,'OmniBook X Flip 14',131000,166000),
  ('homepage_featured_bottom',10,'Smart Monitor M7',61230,79435);

CREATE TEMPORARY TABLE tmp_home_candidates ENGINE=InnoDB AS
SELECT p.id AS product_id, p.title,
       ROUND(COALESCE(v.low_price, p.price)) AS low_price,
       ROUND(COALESCE(v.high_price, p.price)) AS high_price
  FROM products p
  LEFT JOIN (
    SELECT product_id, MIN(price) AS low_price, MAX(price) AS high_price
      FROM product_variants GROUP BY product_id
  ) v ON v.product_id = p.id
 WHERE p.is_active = 1;

CREATE TEMPORARY TABLE tmp_home_resolved ENGINE=InnoDB AS
SELECT t.section_code, t.position, t.title_hint, t.low_price AS requested_low,
       t.high_price AS requested_high,
       (SELECT c.product_id
          FROM tmp_home_candidates c
         WHERE (
                 LOWER(c.title) LIKE CONCAT('%', LOWER(t.title_hint), '%')
                 OR (
                   c.low_price <= t.low_price AND c.high_price >= t.high_price
                   AND (SELECT COUNT(*) FROM tmp_home_candidates c2
                         WHERE c2.low_price <= t.low_price AND c2.high_price >= t.high_price) = 1
                 )
               )
         ORDER BY (LOWER(c.title) LIKE CONCAT('%', LOWER(t.title_hint), '%')) DESC,
                  (c.low_price = t.low_price AND c.high_price = t.high_price) DESC,
                  (c.high_price - c.low_price),
                  c.product_id
         LIMIT 1) AS product_id
  FROM tmp_home_targets t;

CREATE TEMPORARY TABLE tmp_home_section_status ENGINE=InnoDB AS
SELECT t.section_code, COUNT(*) AS requested_count,
       SUM(r.product_id IS NOT NULL) AS matched_count
  FROM tmp_home_targets t
  JOIN tmp_home_resolved r ON r.section_code = t.section_code AND r.position = t.position
 GROUP BY t.section_code;

SET @homepage_mapping_complete := (
  SELECT COUNT(*) = (SELECT COUNT(*) FROM tmp_home_targets)
    FROM tmp_home_resolved WHERE product_id IS NOT NULL
);

-- Replace a list when at least one requested product matched; keep matched products in requested order.
DELETE sp FROM section_products sp
JOIN tmp_home_section_status s ON s.section_code = sp.section
WHERE s.matched_count > 0;

INSERT INTO section_products (section, position, product_id, animation, wow_delay)
SELECT r.section_code, r.position, r.product_id, NULL, NULL
  FROM tmp_home_resolved r
  JOIN tmp_home_section_status s ON s.section_code = r.section_code
 WHERE s.matched_count > 0 AND r.product_id IS NOT NULL
 ORDER BY r.section_code, r.position;

UPDATE sections
   SET is_active = 1,
       max_products = CASE code
         WHEN 'trending' THEN 6
         WHEN 'smart_home' THEN 40
         WHEN 'homepage_featured_bottom' THEN 10
         ELSE 10
       END,
       note = CASE WHEN code = 'smart_home'
                   THEN 'Featured Products grid: up to 40 on desktop, 12 on tablets, 8 on phones.'
                   ELSE note END
 WHERE code IN ('trending','smart_home','top_20','featured_products','top_selling','on_sale','recently_viewed','homepage_featured_bottom')
   AND EXISTS (
     SELECT 1 FROM tmp_home_section_status s
      WHERE s.section_code = sections.code AND s.matched_count > 0
   );

COMMIT;

-- Review section completeness and product matches. Incomplete sections keep their existing list.
SELECT @homepage_mapping_complete AS all_products_matched;
SELECT section_code, requested_count, matched_count,
       CASE WHEN matched_count = 0 THEN 'kept existing list'
            WHEN matched_count = requested_count THEN 'updated all products'
            ELSE 'updated matched products only' END AS action_taken
  FROM tmp_home_section_status ORDER BY section_code;
SELECT r.section_code, r.position, r.title_hint, r.requested_low, r.requested_high,
       p.title AS matched_product_title, r.product_id
  FROM tmp_home_resolved r
  LEFT JOIN products p ON p.id = r.product_id
 ORDER BY r.section_code, r.position;

DROP TEMPORARY TABLE tmp_home_resolved;
DROP TEMPORARY TABLE tmp_home_section_status;
DROP TEMPORARY TABLE tmp_home_candidates;
DROP TEMPORARY TABLE tmp_home_targets;
