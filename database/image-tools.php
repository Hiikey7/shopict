<?php
declare(strict_types=1);

// Shared by optimize-images.php and cloudinary-migrate.php (command line only).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this from the command line.');
}
require __DIR__ . '/../includes/bootstrap.php';

/** Every database field that holds a picture path: [table, column]. Settings are handled separately. */
function image_columns(): array
{
    return [
        ['products', 'img_src'], ['products', 'img_hover'], ['product_images', 'image'],
        ['banners', 'image'], ['banners', 'bg_image'], ['brands', 'logo'],
        ['categories', 'img_src'], ['order_items', 'image'],
    ];
}

/** Settings whose value is a picture path. */
function image_settings(): array
{
    return ['logo', 'hero_bg_image'];
}

/** Distinct picture paths used anywhere in the database. */
function used_image_paths(): array
{
    $paths = [];
    foreach (image_columns() as [$table, $col]) {
        foreach (db()->query("SELECT DISTINCT `$col` FROM `$table` WHERE `$col` IS NOT NULL AND `$col` <> ''")->fetchAll(PDO::FETCH_COLUMN) as $p) {
            $paths[$p] = true;
        }
    }
    $in = implode(',', array_map(fn ($k) => db()->quote($k), image_settings()));
    foreach (db()->query("SELECT value FROM settings WHERE `key` IN ($in) AND value IS NOT NULL AND value <> ''")->fetchAll(PDO::FETCH_COLUMN) as $p) {
        $paths[$p] = true;
    }
    return array_keys($paths);
}

/** Replaces a picture path everywhere in the database. Returns the number of rows changed. */
function replace_image_path(string $old, string $new): int
{
    $n = 0;
    foreach (image_columns() as [$table, $col]) {
        $stmt = db()->prepare("UPDATE `$table` SET `$col` = ? WHERE `$col` = ?");
        $stmt->execute([$new, $old]);
        $n += $stmt->rowCount();
    }
    $in = implode(',', array_map(fn ($k) => db()->quote($k), image_settings()));
    $stmt = db()->prepare("UPDATE settings SET value = ? WHERE `key` IN ($in) AND value = ?");
    $stmt->execute([$new, $old]);
    return $n + $stmt->rowCount();
}
