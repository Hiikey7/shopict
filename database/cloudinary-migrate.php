<?php
declare(strict_types=1);

/*
 * Moves every picture the shop uses to Cloudinary and points the database at the Cloudinary links.
 * Fill in 'cloudinary' in config.php first. Pictures are made lighter before uploading.
 * Local files are kept, so you can undo by restoring a database backup.
 *
 *   php database/cloudinary-migrate.php            show what would be uploaded
 *   php database/cloudinary-migrate.php --apply    upload and update the database
 */
require __DIR__ . '/image-tools.php';

$apply = in_array('--apply', $argv, true);
if (!cloudinary_enabled()) {
    exit("Cloudinary is not set up: fill in cloud_name, api_key and api_secret under 'cloudinary' in config.php.\n");
}
$assets = realpath(__DIR__ . '/../assets');
$done = $skipped = $failed = 0;
foreach (used_image_paths() as $path) {
    if (preg_match('#^https?://#i', $path)) {
        $skipped++; // already online
        continue;
    }
    $file = $assets . '/' . ltrim($path, '/');
    if (!is_file($file)) {
        echo "  missing  $path\n";
        $failed++;
        continue;
    }
    if (!$apply) {
        echo "  would upload  $path\n";
        continue;
    }
    $small = optimize_image($file);
    try {
        $publicId = preg_replace('/\.\w+$/', '', str_replace('/', '_', ltrim($path, '/')));
        $up = cloudinary_upload($small, $publicId);
        $rows = replace_image_path($path, $up['path']);
        echo "  uploaded  $path  ->  {$up['path']}  ($rows rows)\n";
        $done++;
    } catch (RuntimeException $e) {
        echo "  FAILED  $path: {$e->getMessage()}\n";
        $failed++;
    } finally {
        if ($small !== $file) {
            @unlink($small);
        }
    }
}
echo "\nUploaded $done, already online $skipped, problems $failed." . ($apply ? '' : '  (nothing changed: add --apply)') . "\n";
