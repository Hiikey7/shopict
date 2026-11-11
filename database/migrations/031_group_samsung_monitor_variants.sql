-- Group matching Samsung monitor size listings as priced variants.
-- Run this against the Shop ICT storefront database in HostPinacle/phpMyAdmin.
-- Safe to rerun: variants are inserted only when a target product has none.
-- Product records are hidden instead of deleted so existing order references remain intact.

START TRANSACTION;

-- Samsung Essential S30GD: 22", 24" and 27".
UPDATE products
   SET title = 'Samsung Essential S30GD Monitor',
       option_names = '["Screen size and model"]',
       price = (SELECT price FROM (SELECT price FROM products WHERE sku = 'SAM-0568') AS p),
       old_price = (SELECT old_price FROM (SELECT old_price FROM products WHERE sku = 'SAM-0568') AS p),
       description = 'Samsung Essential S30GD monitor series. Choose the screen size and exact model you need. Available sizes: 22-inch, 24-inch and 27-inch. Prices and model codes vary by selected option.',
       specs = '[["Series","Essential S30GD"],["Resolution","Full HD"],["Finish","Black"],["Condition","New"]]',
       updated_at = NOW()
 WHERE sku = 'SAM-0568'
   AND EXISTS (SELECT 1 FROM products WHERE sku = 'SAM-0569')
   AND EXISTS (SELECT 1 FROM products WHERE sku = 'SAM-0570');

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Screen size and model', CONCAT(size_info.size, '" — ', source.title)), source.price, source.old_price, source.sku, NULL,
       IF(source.sku = 'SAM-0568', 1, 0), size_info.position
  FROM products base
  JOIN (
      SELECT 'SAM-0568' AS sku, 22 AS size, 1 AS position UNION ALL
      SELECT 'SAM-0569', 24, 2 UNION ALL
      SELECT 'SAM-0570', 27, 3
  ) size_info ON 1 = 1
  JOIN products source ON source.sku = size_info.sku
 WHERE base.sku = 'SAM-0568'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id);

-- Samsung ViewFinity S6 S60D: 24" and 27".
UPDATE products
   SET title = 'Samsung ViewFinity S6 S60D Monitor',
       option_names = '["Screen size and model"]',
       price = (SELECT price FROM (SELECT price FROM products WHERE sku = 'SAM-0580') AS p),
       old_price = (SELECT old_price FROM (SELECT old_price FROM products WHERE sku = 'SAM-0580') AS p),
       description = 'Samsung ViewFinity S6 S60D monitor series. Choose the screen size and exact model you need. Available sizes: 24-inch and 27-inch. Prices and model codes vary by selected option.',
       specs = '[["Series","ViewFinity S6 S60D"],["Resolution","QHD 2K"],["Finish","Black"],["Condition","New"]]',
       updated_at = NOW()
 WHERE sku = 'SAM-0580'
   AND EXISTS (SELECT 1 FROM products WHERE sku = 'SAM-0581');

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Screen size and model', CONCAT(size_info.size, '" — ', source.title)), source.price, source.old_price, source.sku, NULL,
       IF(source.sku = 'SAM-0580', 1, 0), size_info.position
  FROM products base
  JOIN (
      SELECT 'SAM-0580' AS sku, 24 AS size, 1 AS position UNION ALL
      SELECT 'SAM-0581', 27, 2
  ) size_info ON 1 = 1
  JOIN products source ON source.sku = size_info.sku
 WHERE base.sku = 'SAM-0580'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id);

-- Samsung Smart Monitor M7 M70F: 32" and 43".
UPDATE products
   SET title = 'Samsung Smart Monitor M7 M70F',
       option_names = '["Screen size and model"]',
       price = (SELECT price FROM (SELECT price FROM products WHERE sku = 'SAM-0577') AS p),
       old_price = (SELECT old_price FROM (SELECT old_price FROM products WHERE sku = 'SAM-0577') AS p),
       description = 'Samsung Smart Monitor M7 M70F series. Choose the screen size and exact model you need. Available sizes: 32-inch and 43-inch. Prices and model codes vary by selected option.',
       specs = '[["Series","Smart Monitor M7 M70F"],["Resolution","4K UHD"],["Finish","Black"],["Condition","New"]]',
       updated_at = NOW()
 WHERE sku = 'SAM-0577'
   AND EXISTS (SELECT 1 FROM products WHERE sku = 'SAM-0578');

INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position)
SELECT base.id, JSON_OBJECT('Screen size and model', CONCAT(size_info.size, '" — ', source.title)), source.price, source.old_price, source.sku, NULL,
       IF(source.sku = 'SAM-0577', 1, 0), size_info.position
  FROM products base
  JOIN (
      SELECT 'SAM-0577' AS sku, 32 AS size, 1 AS position UNION ALL
      SELECT 'SAM-0578', 43, 2
  ) size_info ON 1 = 1
  JOIN products source ON source.sku = size_info.sku
 WHERE base.sku = 'SAM-0577'
   AND NOT EXISTS (SELECT 1 FROM product_variants existing WHERE existing.product_id = base.id);

-- Copy categories and images from each size listing to its canonical product.
INSERT IGNORE INTO product_category_links (product_id, category_id)
SELECT CASE source.sku
         WHEN 'SAM-0569' THEN base568.id WHEN 'SAM-0570' THEN base568.id
         WHEN 'SAM-0581' THEN base580.id WHEN 'SAM-0578' THEN base577.id
       END, links.category_id
  FROM product_category_links links
  JOIN products source ON source.id = links.product_id
  LEFT JOIN products base568 ON base568.sku = 'SAM-0568'
  LEFT JOIN products base580 ON base580.sku = 'SAM-0580'
  LEFT JOIN products base577 ON base577.sku = 'SAM-0577'
 WHERE source.sku IN ('SAM-0569', 'SAM-0570', 'SAM-0581', 'SAM-0578');

INSERT INTO product_images (product_id, image, position)
SELECT CASE source.sku
         WHEN 'SAM-0569' THEN base568.id WHEN 'SAM-0570' THEN base568.id
         WHEN 'SAM-0581' THEN base580.id WHEN 'SAM-0578' THEN base577.id
       END, images.image, images.position + 1000
  FROM product_images images
  JOIN products source ON source.id = images.product_id
  LEFT JOIN products base568 ON base568.sku = 'SAM-0568'
  LEFT JOIN products base580 ON base580.sku = 'SAM-0580'
  LEFT JOIN products base577 ON base577.sku = 'SAM-0577'
 WHERE source.sku IN ('SAM-0569', 'SAM-0570', 'SAM-0581', 'SAM-0578')
   AND NOT EXISTS (
       SELECT 1 FROM product_images existing
        WHERE existing.product_id = CASE source.sku
                 WHEN 'SAM-0569' THEN base568.id WHEN 'SAM-0570' THEN base568.id
                 WHEN 'SAM-0581' THEN base580.id WHEN 'SAM-0578' THEN base577.id
             END
          AND existing.image = images.image
   );

-- Hide duplicate cards while retaining their product rows and historical references.
UPDATE products SET is_active = 0, updated_at = NOW()
 WHERE sku IN ('SAM-0569', 'SAM-0570', 'SAM-0581', 'SAM-0578');

COMMIT;

-- Verify: each canonical product should be active and have 2 or 3 priced variants.
SELECT p.sku, p.title, p.is_active, v.options, v.price, v.sku AS variant_sku
  FROM products p
  JOIN product_variants v ON v.product_id = p.id
 WHERE p.sku IN ('SAM-0568', 'SAM-0580', 'SAM-0577')
 ORDER BY p.sku, v.position;
