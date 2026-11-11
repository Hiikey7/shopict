-- Group additional listings that are clear configurations of the same product family.
-- Run after 031_group_samsung_monitor_variants.sql in the HostPinacle storefront database.
-- Safe to rerun: source SKU variants are inserted only once; source product rows are hidden, not deleted.

START TRANSACTION;

-- HP ProBook 440 G11: add the separately listed part-number configurations to the existing variant product.
UPDATE products
   SET title = 'HP ProBook 440 G11',
       option_names = '["Configuration"]',
       description = 'HP ProBook 440 G11 business laptop. Select the listed processor, memory, storage and included accessories. Prices vary by configuration.',
       specs = '[["Series","ProBook 440 G11"],["Display","14-inch"],["Condition","Brand New"]]',
       updated_at = NOW()
 WHERE sku = 'HP-0475';

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id,
       JSON_OBJECT('Configuration', CONCAT(source.title, ' — ', source.sku)),
       source.price, source.old_price, source.sku, NULL, 0,
       CASE source.sku WHEN 'HP-0519' THEN 4 ELSE 5 END
  FROM products base
  JOIN products source ON source.sku IN ('HP-0519', 'HP-0520')
 WHERE base.sku = 'HP-0475'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- HP OmniBook X Flip: group the 14-inch and 16-inch configurations in one family.
UPDATE products
   SET title = 'HP OmniBook X Flip',
       option_names = '["Configuration"]',
       description = 'HP OmniBook X Flip convertible laptop family. Select a listed screen size and hardware configuration. Prices and included accessories vary by option.',
       specs = '[["Series","OmniBook X Flip"],["Form factor","Convertible touchscreen laptop"],["Condition","Brand New"]]',
       updated_at = NOW()
 WHERE sku = 'HP-0472';

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Configuration', CONCAT(source.title, ' — ', source.sku)), source.price, source.old_price, source.sku, NULL, 0,
       CASE source.sku WHEN 'HP-0473' THEN 4 WHEN 'HP-0514' THEN 5 WHEN 'HP-0515' THEN 6 ELSE 7 END
  FROM products base
  JOIN products source ON source.sku IN ('HP-0473', 'HP-0514', 'HP-0515', 'HP-0516')
 WHERE base.sku = 'HP-0472'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- HP Victus 15: add the individually listed 15-inch model configurations to its existing option set.
UPDATE products
   SET title = 'HP Victus 15 Gaming Laptop',
       description = 'HP Victus 15 gaming laptop family. Select a listed processor, memory and graphics configuration. Prices vary by model.',
       updated_at = NOW()
 WHERE sku = 'HP-0501';

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Configuration', CONCAT(source.title, ' — ', source.sku)), source.price, source.old_price, source.sku, NULL, 0,
       CASE source.sku WHEN 'HP-0522' THEN 3 WHEN 'HP-0523' THEN 4 ELSE 5 END
  FROM products base
  JOIN products source ON source.sku IN ('HP-0522', 'HP-0523', 'HP-0524')
 WHERE base.sku = 'HP-0501'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- HP All-in-One desktops: distinguish 24-inch and 27-inch configurations in the same selector.
UPDATE products
   SET title = 'HP All-in-One Desktop',
       description = 'HP All-in-One desktop family. Select a screen size and listed hardware configuration. Prices and features vary by option.',
       specs = '[["Product","HP All-in-One desktop"],["Condition","Brand New"]]',
       updated_at = NOW()
 WHERE sku = 'HP-0477';

UPDATE product_variants
   SET options = JSON_SET(options, '$.Configuration', CONCAT('24-inch — ', JSON_UNQUOTE(JSON_EXTRACT(options, '$.Configuration'))))
 WHERE product_id = (SELECT id FROM products WHERE sku = 'HP-0477')
   AND JSON_UNQUOTE(JSON_EXTRACT(options, '$.Configuration')) NOT LIKE '24-inch — %';

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id,
       JSON_SET(source_variant.options, '$.Configuration', CONCAT('27-inch — ', JSON_UNQUOTE(JSON_EXTRACT(source_variant.options, '$.Configuration')))),
       source_variant.price, source_variant.old_price, source_variant.sku, source_variant.stock, 0,
       source_variant.position + 4
  FROM products base
  JOIN products source ON source.sku = 'HP-0478'
  JOIN product_variants source_variant ON source_variant.product_id = source.id
 WHERE base.sku = 'HP-0477'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source_variant.sku);

-- HP Color LaserJet 3303 family: retain the exact suffix/model on each option.
UPDATE products SET title = 'HP Color LaserJet 3303', option_names = '["Model"]', updated_at = NOW() WHERE sku = 'HP-0554';
INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Model', source.title), source.price, source.old_price, source.sku, NULL, 0, 2
  FROM products base JOIN products source ON source.sku = 'HP-0555'
 WHERE base.sku = 'HP-0554'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- HP LaserJet 4103 family: retain the exact suffix/model on each option.
UPDATE products SET title = 'HP LaserJet 4103', option_names = '["Model"]', updated_at = NOW() WHERE sku = 'HP-0562';
INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Model', source.title), source.price, source.old_price, source.sku, NULL, 0, 2
  FROM products base JOIN products source ON source.sku = 'HP-0563'
 WHERE base.sku = 'HP-0562'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- Mecer UPS family: capacity is the selectable specification; each source price and SKU is retained.
UPDATE products
   SET title = 'Mecer UPS',
       brand = 'Mecer',
       option_names = '["Capacity"]',
       price = (SELECT price FROM (SELECT price FROM products WHERE sku = 'ICT-0595') AS p),
       old_price = (SELECT old_price FROM (SELECT old_price FROM products WHERE sku = 'ICT-0595') AS p),
       description = 'Mecer UPS units in several power capacities. Select a capacity to see its price and exact SKU.',
       specs = '[["Product","Mecer uninterruptible power supply"],["Condition","New"]]',
       updated_at = NOW()
 WHERE sku = 'ICT-0595';

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id,
       JSON_OBJECT('Capacity', CASE source.sku
           WHEN 'ICT-0595' THEN '650VA'
           WHEN 'ICT-0592' THEN '1KVA'
           WHEN 'ICT-0593' THEN '2KVA'
           WHEN 'ICT-0594' THEN '3KVA'
       END),
       source.price, source.old_price, source.sku, NULL,
       IF(source.sku = 'ICT-0595', 1, 0),
       CASE source.sku WHEN 'ICT-0595' THEN 1 WHEN 'ICT-0592' THEN 2 WHEN 'ICT-0593' THEN 3 ELSE 4 END
  FROM products base
  JOIN products source ON source.sku IN ('ICT-0595', 'ICT-0592', 'ICT-0593', 'ICT-0594')
 WHERE base.sku = 'ICT-0595'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- Apple MacBook Pro M5: list the distinct size, chip, memory, storage and colour configurations together.
UPDATE products
   SET title = 'Apple MacBook Pro M5',
       brand = 'Apple',
       option_names = '["Configuration"]',
       description = 'Apple MacBook Pro M5 models. Select a listed size and hardware configuration. Prices vary by processor, memory and storage; condition details are not specified in the source listings.',
       specs = '[["Series","MacBook Pro M5"],["Condition","Not specified"]]',
       updated_at = NOW()
 WHERE sku = 'ICT-0608';

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id,
       JSON_OBJECT('Configuration', CASE source.sku
           WHEN 'ICT-0608' THEN '14-inch, M5 Max, 36GB memory, 1TB SSD, Silver'
           WHEN 'ICT-0609' THEN '14-inch, M5 Pro, 24GB memory, 1TB SSD'
           WHEN 'ICT-0610' THEN '16-inch, M5 Max, 48GB memory, 2TB SSD'
       END),
       source.price, source.old_price, source.sku, NULL,
       IF(source.sku = 'ICT-0608', 1, 0),
       CASE source.sku WHEN 'ICT-0608' THEN 1 WHEN 'ICT-0609' THEN 2 ELSE 3 END
  FROM products base
  JOIN products source ON source.sku IN ('ICT-0608', 'ICT-0609', 'ICT-0610')
 WHERE base.sku = 'ICT-0608'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id AND existing.sku = source.sku);

-- Preserve all source categories on each canonical product.
INSERT IGNORE INTO product_category_links (product_id, category_id)
SELECT base.id, links.category_id
  FROM product_category_links links
  JOIN products source ON source.id = links.product_id
  JOIN products base ON base.sku = CASE
      WHEN source.sku IN ('HP-0519', 'HP-0520') THEN 'HP-0475'
      WHEN source.sku IN ('HP-0473', 'HP-0514', 'HP-0515', 'HP-0516') THEN 'HP-0472'
      WHEN source.sku IN ('HP-0522', 'HP-0523', 'HP-0524') THEN 'HP-0501'
      WHEN source.sku = 'HP-0478' THEN 'HP-0477'
      WHEN source.sku = 'HP-0555' THEN 'HP-0554'
      WHEN source.sku = 'HP-0563' THEN 'HP-0562'
      WHEN source.sku IN ('ICT-0592', 'ICT-0593', 'ICT-0594') THEN 'ICT-0595'
      WHEN source.sku IN ('ICT-0609', 'ICT-0610') THEN 'ICT-0608'
  END
 WHERE source.sku IN ('HP-0519', 'HP-0520', 'HP-0473', 'HP-0514', 'HP-0515', 'HP-0516', 'HP-0522', 'HP-0523', 'HP-0524', 'HP-0478', 'HP-0555', 'HP-0563', 'ICT-0592', 'ICT-0593', 'ICT-0594', 'ICT-0609', 'ICT-0610');

-- Carry over gallery images from the hidden product cards.
INSERT INTO product_images (product_id, image, position)
SELECT base.id, images.image, images.position + 1000
  FROM product_images images
  JOIN products source ON source.id = images.product_id
  JOIN products base ON base.sku = CASE
      WHEN source.sku IN ('HP-0519', 'HP-0520') THEN 'HP-0475'
      WHEN source.sku IN ('HP-0473', 'HP-0514', 'HP-0515', 'HP-0516') THEN 'HP-0472'
      WHEN source.sku IN ('HP-0522', 'HP-0523', 'HP-0524') THEN 'HP-0501'
      WHEN source.sku = 'HP-0478' THEN 'HP-0477'
      WHEN source.sku = 'HP-0555' THEN 'HP-0554'
      WHEN source.sku = 'HP-0563' THEN 'HP-0562'
      WHEN source.sku IN ('ICT-0592', 'ICT-0593', 'ICT-0594') THEN 'ICT-0595'
      WHEN source.sku IN ('ICT-0609', 'ICT-0610') THEN 'ICT-0608'
  END
 WHERE source.sku IN ('HP-0519', 'HP-0520', 'HP-0473', 'HP-0514', 'HP-0515', 'HP-0516', 'HP-0522', 'HP-0523', 'HP-0524', 'HP-0478', 'HP-0555', 'HP-0563', 'ICT-0592', 'ICT-0593', 'ICT-0594', 'ICT-0609', 'ICT-0610')
   AND NOT EXISTS (SELECT 1 FROM product_images existing WHERE existing.product_id = base.id AND existing.image = images.image);

-- Hide duplicate cards, retaining source product rows for historical references.
UPDATE products SET is_active = 0, updated_at = NOW()
 WHERE sku IN ('HP-0519', 'HP-0520', 'HP-0473', 'HP-0514', 'HP-0515', 'HP-0516', 'HP-0522', 'HP-0523', 'HP-0524', 'HP-0478', 'HP-0555', 'HP-0563', 'ICT-0592', 'ICT-0593', 'ICT-0594', 'ICT-0609', 'ICT-0610');

COMMIT;

-- Verify grouped product options, prices and source SKUs.
SELECT p.sku, p.title, v.options, v.price, v.sku AS variant_sku
  FROM products p
  JOIN product_variants v ON v.product_id = p.id
 WHERE p.sku IN ('HP-0475', 'HP-0472', 'HP-0501', 'HP-0477', 'HP-0554', 'HP-0562', 'ICT-0595', 'ICT-0608')
 ORDER BY p.sku, v.position;
