<?php
// Gives existing product pictures file names that describe the product, for Google Images:
// "images/uploads/20261105-a636ddc6cb8d.webp" → "images/uploads/hp-elitebook-840-g8-laptop-3f9a1c.webp".
// Pictures are copied (the old files stay, in case anything else uses them) and each product is pointed to its
// copies. Pictures on Cloudinary keep their address. New uploads are named this way automatically.
//
//   php database/seo-image-names.php           show what would change
//   php database/seo-image-names.php --apply   make the copies and update the products
if (PHP_SAPI !== 'cli') {
    exit('Run from the command line.');
}
chdir(__DIR__ . '/..');
require 'includes/bootstrap.php';
$apply = in_array('--apply', $argv, true);
$pdo = db();
$changed = 0;
foreach ($pdo->query('SELECT id, title, img_src, img_hover FROM products ORDER BY id')->fetchAll() as $p) {
    $map = []; // old path → new path, for this product
    $rename = function (?string $path) use (&$map, $p, $apply): ?string {
        if (!$path) {
            return $path;
        }
        if (!isset($map[$path])) {
            $map[$path] = $apply ? seo_image_filename($path, $p['title'], true) : $path;
        }
        return $map[$path];
    };
    $gallery = $pdo->prepare('SELECT id, image FROM product_images WHERE product_id = ?');
    $gallery->execute([$p['id']]);
    $rows = $gallery->fetchAll();
    $local = array_filter([$p['img_src'], $p['img_hover'], ...array_column($rows, 'image')], fn ($x) => $x && !preg_match('#^https?://#', $x) && is_file('assets/' . $x));
    if (!$local) {
        continue;
    }
    if (!$apply) {
        echo "#{$p['id']} " . count(array_unique($local)) . ' picture(s) → ' . product_slug($p['title']) . "-xxxxxx\n";
        $changed++;
        continue;
    }
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE products SET img_src = ?, img_hover = ? WHERE id = ?')->execute([$rename($p['img_src']), $rename($p['img_hover']), $p['id']]);
    foreach ($rows as $r) {
        $pdo->prepare('UPDATE product_images SET image = ? WHERE id = ?')->execute([$rename($r['image']), $r['id']]);
    }
    $pdo->commit();
    $changed += count(array_filter($map, fn ($new, $old) => $new !== $old, ARRAY_FILTER_USE_BOTH)) ? 1 : 0;
}
echo ($apply ? 'Renamed pictures for ' : 'Would rename pictures for ') . "$changed product(s).\n";
