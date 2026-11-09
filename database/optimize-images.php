<?php
declare(strict_types=1);

/*
 * Makes the pictures in assets/images/uploads lighter: at most 1600 px and saved as WebP (transparency kept).
 * Every database field that points to a picture is updated. Originals are moved to storage/original-images/.
 *
 *   php database/optimize-images.php            show what would change
 *   php database/optimize-images.php --apply    do it
 */
require __DIR__ . '/image-tools.php';

$apply = in_array('--apply', $argv, true);
$assets = realpath(__DIR__ . '/../assets');
$backup = __DIR__ . '/../storage/original-images';
$used = array_flip(used_image_paths());
$before = $after = 0;

foreach (glob($assets . '/images/uploads/*.{png,jpg,jpeg,webp,PNG,JPG,JPEG}', GLOB_BRACE) as $file) {
    $rel = 'images/uploads/' . basename($file);
    $small = optimize_image($file);
    if ($small === $file) {
        printf("  keep  %-36s %6d KB (already small)\n", basename($file), filesize($file) / 1024);
        continue;
    }
    $oldKb = filesize($file) / 1024;
    $newKb = filesize($small) / 1024;
    $newRel = preg_replace('/\.\w+$/', '.webp', $rel);
    if (is_file($assets . '/' . $newRel)) {
        $newRel = preg_replace('/\.webp$/', '-o.webp', $newRel); // don't overwrite another picture
    }
    $before += $oldKb;
    $after += $newKb;
    printf("  %s %-36s %6d KB -> %5d KB%s\n", $apply ? 'done' : 'would', basename($file), $oldKb, $newKb, isset($used[$rel]) ? '' : '  (not used)');
    if (!$apply) {
        unlink($small);
        continue;
    }
    rename($small, $assets . '/' . $newRel);
    replace_image_path($rel, $newRel);
    if (!is_dir($backup)) {
        mkdir($backup, 0775, true);
    }
    rename($file, $backup . '/' . basename($file));
}
printf("\nTotal: %d KB -> %d KB%s\n", $before, $after, $apply ? '' : '  (nothing changed: add --apply)');
