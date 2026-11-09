<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

const MAX_OPTIONS = 4;

$id = (int) ($_GET['id'] ?? 0);
$product = $id ? find_product($id) : null;
if ($id && !$product) {
    flash('That product no longer exists.', 'error');
    redirect('products.php');
}

$productSections = db()->query('SELECT * FROM sections ORDER BY position')->fetchAll();
$productSections = array_values(array_filter($productSections, 'section_takes_products'));
$inSections = [];
$gallery = [];
if ($product) {
    $stmt = db()->prepare('SELECT DISTINCT section FROM section_products WHERE product_id = ?');
    $stmt->execute([$id]);
    $inSections = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = db()->prepare('SELECT id, image FROM product_images WHERE product_id = ? ORDER BY position, id');
    $stmt->execute([$id]);
    $gallery = $stmt->fetchAll();
}
// Pictures shown in the form, in order. Older products may have a hover picture that isn't in the gallery: it goes second.
$pictures = [];
if ($product) {
    $pictures = array_values(array_unique(array_filter([$product['img_src'], $product['img_hover'], ...array_column($gallery, 'image')])));
}
$ownPictures = $pictures; // the only existing paths a submitted order may refer to

$errors = [];
$blank = ['title' => '', 'sku' => '', 'category' => '', 'category_id' => null, 'brand' => '', 'description' => '', 'price' => '', 'old_price' => '',
          'sale_label' => '', 'available' => '', 'sold' => '', 'progress_width' => '', 'discount' => '', 'countdown_timer' => null,
          'is_active' => 1, 'is_preorder' => 0, 'img_src' => null, 'img_hover' => null];
$form = $product ?? $blank;

// Option names padded to MAX_OPTIONS, specs as [label, value] rows, variants with option values by column.
$optionNames = array_pad(array_slice($product ? product_option_names($product) : [], 0, MAX_OPTIONS), MAX_OPTIONS, '');
$specs = $product ? product_specs($product) : [];
$variants = [];
if ($product) {
    foreach (product_variants($id) as $v) {
        $variants[] = [
            'key'       => (string) $v['id'],
            'id'        => (int) $v['id'],
            'values'    => array_map(fn ($name) => $name === '' ? '' : (string) ($v['options'][$name] ?? ''), $optionNames),
            'price'     => $v['price'],
            'old_price' => $v['old_price'],
            'sku'       => $v['sku'],
            'stock'     => $v['stock'],
            'is_default'=> (bool) $v['is_default'],
        ];
    }
}

if (is_post()) {
    verify_csrf();

    // Duplicate: a hidden copy with the same details, pictures, categories, specs and variants, opened for editing.
    if (($_POST['action'] ?? '') === 'duplicate' && $product) {
        $pdo = db();
        $pdo->beginTransaction();
        $row = $product;
        unset($row['id'], $row['created_at'], $row['updated_at'], $row['promotion'], $row['regular_price']);
        $row = array_intersect_key($row, array_flip($pdo->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN)));
        $row['title'] = mb_substr($product['title'] . ' (copy)', 0, 255);
        $row['is_active'] = 0;
        $row['sold'] = null;
        $row['sku'] = null;
        foreach (['seo_title', 'meta_description', 'focus_keyword', 'secondary_keywords', 'faqs', 'image_alts', 'gtin', 'mpn'] as $seoField) {
            $row[$seoField] = null;
        }
        $cols = array_keys($row);
        $pdo->prepare('INSERT INTO products (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute(array_values($row));
        $newId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE products SET sku = ? WHERE id = ?')->execute([product_sku($product['brand'] ?? null, $newId), $newId]);
        $pdo->prepare('INSERT INTO product_images (product_id, image, position) SELECT ?, image, position FROM product_images WHERE product_id = ?')->execute([$newId, $id]);
        $pdo->prepare('INSERT INTO product_category_links (product_id, category_id) SELECT ?, category_id FROM product_category_links WHERE product_id = ?')->execute([$newId, $id]);
        $variants = $pdo->prepare('SELECT options, price, old_price, stock, is_default, position FROM product_variants WHERE product_id = ? ORDER BY position, id');
        $variants->execute([$id]);
        $addVariant = $pdo->prepare('INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($variants->fetchAll() as $i => $v) {
            $addVariant->execute([$newId, $v['options'], $v['price'], $v['old_price'], product_sku($product['brand'] ?? null, $newId) . '-' . ($i + 1), $v['stock'], $v['is_default'], $v['position']]);
        }
        $pdo->commit();
        flash('Copy created and hidden from the store. Change the name, pictures and details, then switch off “Hide from store”.');
        redirect('product-edit.php?id=' . $newId);
    }

    if (($_POST['action'] ?? '') === 'delete' && $product) {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        flash('Product "' . $product['title'] . '" deleted.');
        redirect('products.php');
    }

    $form = array_merge($form, [
        'title'          => post_str('title'),
        'sku'            => null, // set automatically after saving (brand letters + product number)
        'brand'          => post_str('brand'),
        'description'    => post_str('description'),
        'price'          => post_num('price'),
        'old_price'      => post_num('old_price'),
        'sale_label'     => post_str('sale_label'),
        'available'      => post_int('available'),
        'sold'           => post_int('sold'),
        'progress_width' => post_str('progress_width'),
        'discount'       => post_num('discount'),
        'is_active'      => empty($_POST['hide_from_store']) ? 1 : 0, // "Hide from store" switch
        'is_preorder'    => empty($_POST['is_preorder']) ? 0 : 1,
        // Search engines (SEO): empty = written automatically from the product's details.
        'seo_title'          => post_str('seo_title'),
        'meta_description'   => post_str('meta_description'),
        'focus_keyword'      => post_str('focus_keyword'),
        'secondary_keywords' => post_str('secondary_keywords'),
        'faqs'               => post_str('faqs'),
        'image_alts'         => post_str('image_alts'),
        'gtin'               => ($g = preg_replace('/\D+/', '', (string) post_str('gtin'))) !== '' ? $g : null,
        'mpn'                => post_str('mpn'),
        'noindex'            => empty($_POST['noindex']) ? 0 : 1,
        // The countdown's value is not used by the page, only whether it is set.
        'countdown_timer'=> empty($_POST['show_countdown']) ? null : ($product['countdown_timer'] ?? null ?: 1),
    ]);
    if ($form['gtin'] !== null && !in_array(strlen($form['gtin']), [8, 12, 13, 14], true)) {
        $errors['gtin'] = "A barcode (GTIN) has 8, 12, 13 or 14 digits. Leave it empty if you are not sure.";
    }
    // Categories: any number of main categories and subcategories. A ticked subcategory counts its main category
    // too. The product's own category (label above the name) is the first ticked subcategory, else the first main
    // category; every ticked one is kept in product_category_links.
    $valid = array_column(array_filter(categories_all(), fn ($x) => $x['is_active']), null, 'id');
    $pickIds = fn (string $key) => array_values(array_unique(array_filter(array_map('intval', (array) ($_POST[$key] ?? [])), fn ($cid) => isset($valid[$cid]))));
    $subIds = array_values(array_filter($pickIds('sub_category_ids'), fn ($cid) => $valid[$cid]['parent_id'] !== null));
    $mainIds = array_values(array_filter($pickIds('main_category_ids'), fn ($cid) => $valid[$cid]['parent_id'] === null));
    foreach ($subIds as $sid) {
        $parent = (int) $valid[$sid]['parent_id'];
        if (!in_array($parent, $mainIds, true)) {
            $mainIds[] = $parent;
        }
    }
    $chosenCat = $subIds ? category_find($subIds[0]) : ($mainIds ? category_find($mainIds[0]) : null);
    $form['category_id'] = $chosenCat['id'] ?? null;
    $form['category'] = $chosenCat['name'] ?? (($product && $product['category_id'] === null) ? $product['category'] : null);
    $selectedMains = $mainIds;
    $selectedSubs = $subIds;
    if ($form['progress_width'] !== null && is_numeric($form['progress_width'])) {
        $form['progress_width'] .= '%';
    }
    $selectedSections = array_values(array_intersect((array) ($_POST['sections'] ?? []), array_column($productSections, 'code')));
    $inSections = $selectedSections;

    // Specifications: one per line, "Color: Black" (also "Color = Black" or a tab). Split at the first separator.
    $specsText = (string) ($_POST['specs_text'] ?? '');
    $specs = [];
    foreach (preg_split('/\r\n|\r|\n/', $specsText) as $n => $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (!preg_match('/^([^:=\t]+?)\s*(?::|=|\t)\s*(.+)$/u', $line, $m)) {
            $errors['specs'] ??= 'Line ' . ($n + 1) . ' (“' . mb_substr($line, 0, 40) . '”) needs a colon between the name and the value, e.g. Color: Black.';
            continue;
        }
        $specs[] = [mb_substr(trim($m[1]), 0, 60), mb_substr(trim($m[2]), 0, 200)];
    }

    // Variants
    $optionNames = [];
    for ($i = 0; $i < MAX_OPTIONS; $i++) {
        $optionNames[] = mb_substr(trim((string) ($_POST['option_name'][$i] ?? '')), 0, 40);
    }
    $defaultKey = (string) ($_POST['default_variant'] ?? '');
    $variants = [];
    foreach ((array) ($_POST['variants'] ?? []) as $key => $row) {
        $row = (array) $row;
        $num = function (string $field) use ($row): ?float {
            $v = str_ireplace([',', '$', ' ', 'ksh', 'kes', " "], '', trim((string) ($row[$field] ?? '')));
            return $v === '' ? null : (is_numeric($v) ? (float) $v : NAN);
        };
        $stock = trim((string) ($row['stock'] ?? ''));
        $variants[] = [
            'key'        => (string) $key,
            'id'         => (int) ($row['id'] ?? 0),
            'values'     => array_map(fn ($i) => mb_substr(trim((string) ($row['values'][$i] ?? '')), 0, 60), range(0, MAX_OPTIONS - 1)),
            'price'      => $num('price'),
            'old_price'  => $num('old_price'),
            'sku'        => trim((string) ($row['sku'] ?? '')) ?: null,
            // "Sold out" ticked = stock 0 (shows the Sold out tag). Unticked with stock 0 = available again (not counted).
            'stock'      => !empty($row['sold_out']) ? 0 : ($stock === '' || (int) $stock <= 0 ? null : (int) $stock),
            'is_default' => (string) $key === $defaultKey,
        ];
    }

    if ($form['title'] === null) {
        $errors['title'] = 'Enter a product name.';
    }
    if ($variants) {
        $usedNames = array_filter($optionNames, fn ($n) => $n !== '');
        if (!$usedNames) {
            $errors['options'] = 'Name at least one option (e.g. RAM) for the variants.';
        }
        if (count(array_unique(array_map('mb_strtolower', $usedNames))) < count($usedNames)) {
            $errors['options'] = 'Each option needs a different name.';
        }
        $seen = [];
        foreach ($variants as $i => $v) {
            $combo = [];
            foreach ($optionNames as $c => $name) {
                if ($name === '') {
                    continue;
                }
                if ($v['values'][$c] === '') {
                    $errors['variants'] = 'Every variant needs a value for each option (row ' . ($i + 1) . ', ' . $name . ').';
                }
                $combo[] = mb_strtolower($v['values'][$c]);
            }
            $comboKey = implode("\x1f", $combo);
            if (isset($seen[$comboKey]) && !isset($errors['variants'])) {
                $errors['variants'] = 'Rows ' . ($seen[$comboKey] + 1) . ' and ' . ($i + 1) . ' have the same options.';
            }
            $seen[$comboKey] = $i;
            if (!valid_amount($v['price']) && !isset($errors['variants'])) {
                $errors['variants'] = 'Enter a price for every variant (row ' . ($i + 1) . ').';
            }
            if ($v['old_price'] !== null && !valid_amount($v['old_price']) && !isset($errors['variants'])) {
                $errors['variants'] = 'The old price in row ' . ($i + 1) . ' is not a number.';
            }
        }
        if (!array_filter(array_column($variants, 'is_default'))) {
            $variants[0]['is_default'] = true;
        }
        // The product's own price follows the default variant, so cards show it.
        $default = array_values(array_filter($variants, fn ($v) => $v['is_default']))[0];
        if (!isset($errors['variants'])) {
            $form['price'] = $default['price'];
            $form['old_price'] = $default['old_price'];
        }
    } elseif (!valid_amount($form['price'])) {
        $errors['price'] = 'Enter a price as a number (0 or more).';
    }
    if ($form['old_price'] !== null && !valid_amount($form['old_price'])) {
        $errors['old_price'] = 'Enter the old price as a number, or leave it empty.';
    }
    if ($form['discount'] !== null && !valid_amount($form['discount'])) {
        $errors['discount'] = 'Enter the "Save" amount as a number, or leave it empty.';
    }
    if ($form['progress_width'] !== null && !preg_match('/^\d{1,3}(\.\d+)?%$/', $form['progress_width'])) {
        $errors['progress_width'] = 'Use a percentage, e.g. 40%.';
    }

    // Pictures: image_order[] lists "e:<path>" (a picture the product already has) or "n:<index>" (a new upload),
    // in the order shown. New uploads are only saved when the rest of the form is valid.
    $order = array_values(array_filter(array_map('strval', (array) ($_POST['image_order'] ?? []))));
    $keptPictures = [];
    foreach ($order as $token) {
        if (str_starts_with($token, 'e:') && in_array(substr($token, 2), $ownPictures, true)) {
            $keptPictures[] = substr($token, 2);
        }
    }
    $newCount = count(array_filter((array) ($_FILES['gallery_new']['error'] ?? []), fn ($e) => $e !== UPLOAD_ERR_NO_FILE));
    if (!$keptPictures && !$newCount) {
        $errors['image'] = 'Add at least one picture. The first picture is the main image.';
    }
    $pictures = array_values(array_unique($keptPictures)); // shown again if the form has errors
    $uploads = ['gallery' => []];
    if (!$errors) {
        try {
            foreach (array_keys((array) ($_FILES['gallery_new']['name'] ?? [])) as $i) {
                if ($img = upload_image('gallery_new', $i)) {
                    $uploads['gallery'][$i] = $img;
                }
            }
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        $data = $form;
        $data['specs'] = $specs ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null;
        $usedNames = array_values(array_filter($optionNames, fn ($n) => $n !== ''));
        $data['option_names'] = $variants ? json_encode($usedNames, JSON_UNESCAPED_UNICODE) : null;
        // Final picture list in the chosen order; uploads not placed in the order (no JavaScript) go at the end.
        $size = function (string $path): array {
            $info = @getimagesize(__DIR__ . '/../assets/' . $path);
            return $info ? [$info[0], $info[1]] : [null, null];
        };
        foreach ($uploads['gallery'] as $i => $up) { // "hp-elitebook-840-g8-laptop-3f9a1c.webp" (for Google Images)
            $uploads['gallery'][$i]['path'] = seo_image_filename($up['path'], (string) $data['title']);
        }
        $final = [];
        $placed = [];
        foreach ($order as $token) {
            if (str_starts_with($token, 'e:') && in_array(substr($token, 2), $ownPictures, true)) {
                $final[] = [substr($token, 2), ...$size(substr($token, 2))];
            } elseif (str_starts_with($token, 'n:') && isset($uploads['gallery'][(int) substr($token, 2)])) {
                $up = $uploads['gallery'][(int) substr($token, 2)];
                $final[] = [$up['path'], $up['width'], $up['height']];
                $placed[(int) substr($token, 2)] = true;
            }
        }
        foreach ($uploads['gallery'] as $i => $up) {
            if (!isset($placed[$i])) {
                $final[] = [$up['path'], $up['width'], $up['height']];
            }
        }
        $final = array_values(array_column($final, null, 0)); // no picture twice
        [$data['img_src'], $data['img_width'], $data['img_height']] = $final[0];
        [$data['img_hover'], $data['hover_width'], $data['hover_height']] = $final[1] ?? [null, null, null];
        $cols = ['title', 'sku', 'category', 'category_id', 'brand', 'description', 'specs', 'option_names', 'price', 'old_price', 'sale_label',
                 'available', 'sold', 'progress_width', 'countdown_timer', 'discount', 'is_active', 'is_preorder',
                 'seo_title', 'meta_description', 'focus_keyword', 'secondary_keywords', 'faqs', 'image_alts', 'gtin', 'mpn', 'noindex',
                 'img_src', 'img_width', 'img_height', 'img_hover', 'hover_width', 'hover_height'];
        $values = array_map(fn ($c) => $data[$c] ?? null, $cols);
        if ($product) {
            $sql = 'UPDATE products SET ' . implode(', ', array_map(fn ($c) => "$c = ?", $cols)) . ' WHERE id = ?';
            $pdo->prepare($sql)->execute([...$values, $id]);
        } else {
            $sql = 'INSERT INTO products (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
            $pdo->prepare($sql)->execute($values);
            $id = (int) $pdo->lastInsertId();
        }
        $sku = product_sku($data['brand'] ?? null, $id);
        $pdo->prepare('UPDATE products SET sku = ? WHERE id = ?')->execute([$sku, $id]);

        // Categories: every ticked subcategory, plus each ticked main category that has no ticked subcategory.
        $pdo->prepare('DELETE FROM product_category_links WHERE product_id = ?')->execute([$id]);
        $link = $pdo->prepare('INSERT IGNORE INTO product_category_links (product_id, category_id) VALUES (?, ?)');
        $coveredMains = array_map(fn ($sid) => (int) $valid[$sid]['parent_id'], $subIds);
        foreach ([...$subIds, ...array_diff($mainIds, $coveredMains)] as $cid) {
            $link->execute([$id, $cid]);
        }

        // Variants: update kept rows, insert new ones, delete removed ones (and their cart items).
        $existing = $pdo->prepare('SELECT id FROM product_variants WHERE product_id = ?');
        $existing->execute([$id]);
        $existing = array_map('intval', $existing->fetchAll(PDO::FETCH_COLUMN));
        $kept = [];
        $defaultId = 0;
        $update = $pdo->prepare('UPDATE product_variants SET options = ?, price = ?, old_price = ?, sku = ?, stock = ?, is_default = ?, position = ? WHERE id = ? AND product_id = ?');
        $insert = $pdo->prepare('INSERT INTO product_variants (product_id, options, price, old_price, sku, stock, is_default, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($variants as $pos => $v) {
            $options = [];
            foreach ($optionNames as $c => $name) {
                if ($name !== '') {
                    $options[$name] = $v['values'][$c];
                }
            }
            $row = [json_encode($options, JSON_UNESCAPED_UNICODE), $v['price'], $v['old_price'], $sku . '-' . ($pos + 1), $v['stock'], $v['is_default'] ? 1 : 0, $pos + 1];
            if ($v['id'] && in_array($v['id'], $existing, true)) {
                $update->execute([...$row, $v['id'], $id]);
                $vid = $v['id'];
            } else {
                $insert->execute([$id, ...$row]);
                $vid = (int) $pdo->lastInsertId();
            }
            $kept[] = $vid;
            if ($v['is_default']) {
                $defaultId = $vid;
            }
        }
        if ($removed = array_diff($existing, $kept)) {
            $in = implode(',', array_fill(0, count($removed), '?'));
            $pdo->prepare("DELETE FROM product_variants WHERE product_id = ? AND id IN ($in)")->execute([$id, ...$removed]);
            $pdo->prepare("DELETE FROM cart_items WHERE product_id = ? AND variant_id IN ($in)")->execute([$id, ...$removed]);
        }
        // Cart items saved before the product had variants move to the default variant, and back again.
        $pdo->prepare('UPDATE IGNORE cart_items SET variant_id = ? WHERE product_id = ? AND variant_id ' . ($defaultId ? '= 0' : '<> 0'))
            ->execute([$defaultId, $id]);
        $pdo->prepare('DELETE FROM cart_items WHERE product_id = ? AND variant_id ' . ($defaultId ? '= 0' : '<> 0'))->execute([$id]);

        // Gallery: every picture after the main one, in the chosen order (the 2nd is also the hover picture).
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
        $addImage = $pdo->prepare('INSERT INTO product_images (product_id, image, position) VALUES (?, ?, ?)');
        foreach (array_slice($final, 1) as $pos => [$path]) {
            $addImage->execute([$id, $path, $pos + 1]);
        }

        // Sections: add to newly ticked ones (at the end), remove from unticked ones.
        $notes = [];
        $current = $pdo->prepare('SELECT DISTINCT section FROM section_products WHERE product_id = ?');
        $current->execute([$id]);
        $current = $current->fetchAll(PDO::FETCH_COLUMN);
        foreach (array_diff($current, $selectedSections) as $code) {
            $pdo->prepare('DELETE FROM section_products WHERE section = ? AND product_id = ?')->execute([$code, $id]);
        }
        foreach (array_diff($selectedSections, $current) as $code) {
            $section = section($code);
            $last = $pdo->prepare('SELECT COUNT(*) AS n, MAX(position) AS pos FROM section_products WHERE section = ?');
            $last->execute([$code]);
            $last = $last->fetch();
            $limit = section_limit($section);
            if ($limit !== null && (int) $last['n'] >= $limit) {
                $notes[] = '"' . $section['title'] . '" is full (' . $limit . ' max), so the product was not added there.';
                continue;
            }
            // New entries reuse the animation of the section's last product so they blend in.
            $style = $pdo->prepare('SELECT animation, wow_delay FROM section_products WHERE section = ? ORDER BY position DESC, id DESC LIMIT 1');
            $style->execute([$code]);
            $style = $style->fetch() ?: ['animation' => null, 'wow_delay' => null];
            $pdo->prepare('INSERT INTO section_products (section, position, product_id, animation, wow_delay) VALUES (?, ?, ?, ?, ?)')
                ->execute([$code, (int) $last['pos'] + 1, $id, $style['animation'], $style['wow_delay']]);
        }
        $pdo->commit();

        flash(($product ? 'Product saved.' : 'Product created.') . ($notes ? ' ' . implode(' ', $notes) : ''), $notes ? 'error' : 'success');
        redirect('product-edit.php?id=' . $id);
    }
}

$catTree = category_tree(true);
// Brand dropdown: the brands from Admin → Brands (tidied: "hp" → HP, "DELL" → Dell), plus this product's own brand if it isn't listed.
$brandName = fn (string $n) => $n === mb_strtolower($n) || $n === mb_strtoupper($n) ? (mb_strlen($n) <= 3 ? mb_strtoupper($n) : ucfirst(mb_strtolower($n))) : $n;
$brandChoices = [];
foreach (db()->query('SELECT name FROM brands ORDER BY is_active DESC, position, name')->fetchAll(PDO::FETCH_COLUMN) as $b) {
    $brandChoices[mb_strtolower($b)] = $brandName($b);
}
$currentBrand = trim((string) ($form['brand'] ?? ''));
if ($currentBrand !== '' && !isset($brandChoices[mb_strtolower($currentBrand)])) {
    $brandChoices[mb_strtolower($currentBrand)] = $currentBrand;
}
// Category + subcategories currently chosen (from the form after a failed save, or the saved product).
if (!isset($selectedMains)) {
    $ids = [];
    if ($product) {
        $stmt = db()->prepare('SELECT category_id FROM product_category_links WHERE product_id = ?');
        $stmt->execute([$product['id']]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
    if (!empty($form['category_id'])) {
        array_unshift($ids, (int) $form['category_id']);
    }
    $selectedMains = $selectedSubs = [];
    foreach (array_unique($ids) as $cid) {
        if (!($cat = category_find($cid))) {
            continue;
        }
        if ($cat['parent_id'] === null) {
            $selectedMains[] = $cat['id'];
        } else {
            $selectedSubs[] = $cat['id'];
            $selectedMains[] = (int) $cat['parent_id'];
        }
    }
    $selectedMains = array_values(array_unique($selectedMains));
}
$promos = [];
if ($product) {
    $promo = best_promotion($product);
    if ($promo) {
        $promos[] = $promo;
    }
}

/** Amount shown with 3 decimals; invalid input is shown as typed. */
$money = function ($v, ?string $raw = null): string {
    if ($v === null || $v === '') {
        return '';
    }
    return is_float($v) && is_nan($v) ? (string) $raw : plain_number($v);
};
$amount = fn (string $field) => $money($form[$field] ?? null, (string) ($_POST[$field] ?? ''));
$invalid = fn (string $field) => isset($errors[$field]) ? ' is-invalid' : '';
$error = fn (string $field) => isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . e($errors[$field]) . '</div>' : '';

/** One variant row; also used (with key "__KEY__") as the template for new rows. */
$variantRow = function (string $key, array $v) use ($money): string {
    $name = fn (string $f) => 'variants[' . e($key) . '][' . $f . ']';
    ob_start(); ?>
    <tr data-variant-row>
      <?php for ($c = 0; $c < MAX_OPTIONS; $c++): ?>
      <td data-opt-col="<?= $c ?>"><input class="form-control form-control-sm" name="<?= $name('values') ?>[<?= $c ?>]" value="<?= e($v['values'][$c] ?? '') ?>" maxlength="60"></td>
      <?php endfor; ?>
      <td><input class="form-control form-control-sm" name="<?= $name('price') ?>" value="<?= e($money($v['price'] ?? null, '')) ?>" inputmode="decimal" placeholder="0"></td>
      <td><input class="form-control form-control-sm" name="<?= $name('old_price') ?>" value="<?= e($money($v['old_price'] ?? null, '')) ?>" inputmode="decimal"></td>
      <td><input class="form-control form-control-sm" type="number" min="0" name="<?= $name('stock') ?>" value="<?= isset($v['stock']) && (int) $v['stock'] > 0 ? (int) $v['stock'] : '' ?>" placeholder="∞"></td>
      <td class="text-center"><input class="form-check-input" type="checkbox" name="<?= $name('sold_out') ?>" value="1" <?= isset($v['stock']) && $v['stock'] !== null && $v['stock'] !== '' && (int) $v['stock'] === 0 ? 'checked' : '' ?> aria-label="Sold out"></td>
      <td class="text-center"><input class="form-check-input" type="radio" name="default_variant" value="<?= e($key) ?>" <?= !empty($v['is_default']) ? 'checked' : '' ?> aria-label="Default variant"></td>
      <td class="text-end">
        <input type="hidden" name="<?= $name('id') ?>" value="<?= (int) ($v['id'] ?? 0) ?>">
        <button class="btn btn-sm btn-link text-danger p-0" type="button" data-remove-variant title="Remove">✕</button>
      </td>
    </tr>
    <?php return (string) ob_get_clean();
};

admin_header($product ? 'Edit product' : 'Add product', 'products');
?>
<a href="products.php" class="small text-decoration-none">← All products</a>
<?php if ($errors): ?><div class="alert alert-danger mt-3">Please fix the highlighted fields.<?php foreach (['options', 'variants', 'gtin'] as $k): ?><?= isset($errors[$k]) ? '<br>' . e($errors[$k]) : '' ?><?php endforeach; ?></div><?php endif; ?>
<?php foreach ($promos as $promo): ?>
<div class="alert alert-info mt-3 mb-0 small">Promotion running: <strong><?= e($promo['name']) ?></strong> (<?= e(promotion_summary($promo)) ?>). Shoppers see the discounted price. <a href="promotion-edit.php?id=<?= (int) $promo['id'] ?>">Edit promotion</a></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="mt-3" data-product-form>
  <?= csrf_field() ?>
  <div class="product-toggles card card-body mb-4">
    <div class="product-toggle">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="is_preorder" name="is_preorder" value="1" <?= !empty($form['is_preorder']) ? 'checked' : '' ?> aria-describedby="is_preorder_help">
        <label class="form-check-label fw-semibold" for="is_preorder">Pre-order</label>
      </div>
      <p class="small text-muted mb-0" id="is_preorder_help">Customers can order it now; it's marked “Pre-order” in the shop, cart and on the order.</p>
    </div>
    <div class="product-toggle">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="hide_from_store" name="hide_from_store" value="1" <?= empty($form['is_active']) ? 'checked' : '' ?> aria-describedby="hide_help">
        <label class="form-check-label fw-semibold" for="hide_from_store">Hide from store</label>
      </div>
      <p class="small text-muted mb-0" id="hide_help">Hidden products can't be seen or bought, but stay saved here.</p>
    </div>
  </div>
  <div class="row g-4">
    <div class="col-xl-8">
      <div class="card mb-4">
        <div class="card-header">Details</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="title">Name *</label>
              <input class="form-control<?= $invalid('title') ?>" id="title" name="title" value="<?= e($form['title']) ?>" maxlength="255" required>
              <?= $error('title') ?>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brand">Brand</label>
              <select class="form-select" id="brand" name="brand">
                <option value="">— No brand —</option>
                <?php foreach ($brandChoices as $key => $label): ?><option value="<?= e($label) ?>" <?= $currentBrand !== '' && mb_strtolower($currentBrand) === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
              </select>
              <div class="form-text">Add brands in <a href="brands.php">Brands</a>.</div>
            </div>
            <div class="col-12">
              <fieldset class="cat-tree" data-cat-tree>
                <legend class="form-label mb-1">Categories <span class="text-muted fw-normal small">(tick as many as fit)</span></legend>
                <?php if (empty($form['category_id']) && !empty($form['category'])): ?><p class="small text-danger mb-2">Not in a category yet (old label: “<?= e($form['category']) ?>”).</p><?php endif; ?>
                <?php foreach ($catTree as $main): $mainOn = in_array($main['id'], $selectedMains, true); ?>
                <div class="cat-tree-group<?= $mainOn ? ' is-on' : '' ?>" data-cat-group>
                  <div class="cat-tree-main">
                    <label class="subcat-chip is-main"><input type="checkbox" name="main_category_ids[]" value="<?= (int) $main['id'] ?>" <?= $mainOn ? 'checked' : '' ?> data-cat-main><span><?= e($main['name']) ?></span></label>
                    <?php if ($main['children']): ?><button class="cat-tree-toggle" type="button" aria-expanded="<?= $mainOn ? 'true' : 'false' ?>" aria-controls="subs-<?= (int) $main['id'] ?>" data-cat-toggle><span class="visually-hidden">Show <?= e($main['name']) ?> subcategories</span><svg width="14" height="14" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button><?php endif; ?>
                  </div>
                  <?php if ($main['children']): ?>
                  <div class="subcat-group" id="subs-<?= (int) $main['id'] ?>"<?= $mainOn ? '' : ' hidden' ?>>
                    <?php foreach ($main['children'] as $sub): ?>
                    <label class="subcat-chip"><input type="checkbox" name="sub_category_ids[]" value="<?= (int) $sub['id'] ?>" <?= in_array($sub['id'], $selectedSubs, true) ? 'checked' : '' ?> data-cat-sub><span><?= e($sub['name']) ?></span></label>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="form-text">The product shows in every ticked category and subcategory (menu, shop filters, promotions). The first ticked subcategory is shown above the product name. <a href="product-categories.php">Manage categories</a></div>
              </fieldset>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="price">Price (KSh) *</label>
              <input class="form-control<?= $invalid('price') ?>" id="price" name="price" value="<?= e($amount('price')) ?>" inputmode="decimal" data-base-price>
              <?= $error('price') ?>
              <div class="form-text d-none" data-price-note>Set by the default variant.</div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="old_price">Old price (KSh)</label>
              <input class="form-control<?= $invalid('old_price') ?>" id="old_price" name="old_price" value="<?= e($amount('old_price')) ?>" inputmode="decimal" data-base-price>
              <?= $error('old_price') ?>
              <div class="form-text">Shown crossed out.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">Variants</div>
        <div class="card-body">
          <p class="section-note">Sell versions of one product at different prices, e.g. a laptop with <em>RAM</em> 8GB or 16GB and <em>Storage</em> 256GB or 512GB. Name up to four options, then add a row for each combination you sell. Leave empty for a product with a single price.</p>
          <div class="row g-2 mb-3<?= isset($errors['options']) ? ' is-invalid-group' : '' ?>">
            <?php for ($c = 0; $c < MAX_OPTIONS; $c++): ?>
            <div class="col-6 col-md-3">
              <label class="form-label small" for="option_name_<?= $c ?>">Option <?= $c + 1 ?> name</label>
              <input class="form-control form-control-sm<?= isset($errors['options']) && $c === 0 ? ' is-invalid' : '' ?>" id="option_name_<?= $c ?>" name="option_name[]" value="<?= e($optionNames[$c]) ?>" placeholder="<?= ['e.g. RAM', 'e.g. Storage', 'e.g. Colour', 'e.g. Size'][$c] ?>" maxlength="40" data-option-name="<?= $c ?>">
            </div>
            <?php endfor; ?>
          </div>
          <div class="table-responsive variant-table<?= $variants ? '' : ' d-none' ?>" data-variant-table>
            <table class="table table-sm align-middle mb-2">
              <thead>
                <tr>
                  <?php for ($c = 0; $c < MAX_OPTIONS; $c++): ?><th data-opt-col="<?= $c ?>" data-opt-head="<?= $c ?>"><?= e($optionNames[$c] ?: 'Option ' . ($c + 1)) ?></th><?php endfor; ?>
                  <th style="min-width:100px">Price (KSh) *</th><th style="min-width:100px">Old price</th><th style="min-width:80px">Stock</th><th class="text-center">Sold out</th><th class="text-center">Default</th><th></th>
                </tr>
              </thead>
              <tbody data-variant-rows>
                <?php foreach ($variants as $v): ?><?= $variantRow($v['key'], $v) ?><?php endforeach; ?>
              </tbody>
            </table>
            <p class="form-text mt-0">Tick <strong>Sold out</strong> to show a “Sold out” tag on that option (it can't be added to the cart). Stock is optional: leave it empty if you don't count stock; when counted stock runs out, Sold out is ticked automatically. The default variant is pre-selected and its price shows on the product cards.</p>
          </div>
          <template data-variant-template><?= $variantRow('__KEY__', ['values' => [], 'is_default' => false]) ?></template>
          <button class="btn btn-sm btn-outline-secondary" type="button" data-add-variant>+ Add variant</button>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">Description &amp; specifications</div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label" for="description">About this item</label>
            <textarea class="form-control" id="description" name="description" rows="5" placeholder="One point per line"><?= e($form['description'] ?? '') ?></textarea>
            <div class="form-text d-flex flex-wrap gap-2 align-items-center">
              <span>Shown as a bullet list in Quick View. Each line becomes one bullet.</span>
              <?php if ($product): ?><button type="button" class="btn btn-link btn-sm p-0 ms-auto" data-fill="#description" data-fill-confirm="Replace the current description with a draft written from the saved specifications?" data-value="<?= e(implode("\n", seo_description_draft($product))) ?>">Write from specs</button><?php endif; ?>
            </div>
          </div>
          <label class="form-label" for="specs_text">Specifications</label>
          <textarea class="form-control specs-text<?= isset($errors['specs']) ? ' is-invalid' : '' ?>" id="specs_text" name="specs_text" rows="6" spellcheck="false" placeholder="Color: Black&#10;Size: 27 inch&#10;Resolution: 1920 x 1080&#10;Refresh rate: 75Hz" data-specs-text><?= e(isset($specsText) ? $specsText : implode("\n", array_map(fn ($r) => $r[0] . ': ' . $r[1], $specs))) ?></textarea>
          <?php if (isset($errors['specs'])): ?><div class="invalid-feedback d-block"><?= e($errors['specs']) ?></div><?php endif; ?>

        </div>
      </div>

      <?php $dealOpen = isset($errors['discount']) || isset($errors['progress_width']); ?>
      <details class="card mb-4 card-collapse"<?= $dealOpen ? ' open' : '' ?>>
        <summary class="card-header">Deal &amp; stock details <span class="small text-muted fw-normal">(optional)</span></summary>
        <div class="card-body">
          <p class="section-note">Used by "Deal Of The Day", the search popup and the highlighted Hot Product card. Leave empty if not needed.</p>
          <div class="row g-3">
            <div class="col-6 col-md-4">
              <label class="form-label" for="sale_label">Sale badge</label>
              <input class="form-control" id="sale_label" name="sale_label" value="<?= e($form['sale_label']) ?>" maxlength="20">
              <div class="form-text">Deal Of The Day adds "%": enter 22. The search popup shows it as typed: enter 22%.</div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="discount">"Save" amount (KSh)</label>
              <input class="form-control<?= $invalid('discount') ?>" id="discount" name="discount" value="<?= e($amount('discount')) ?>" inputmode="decimal">
              <?= $error('discount') ?>
              <div class="form-text">Badge on the highlighted Hot Product card.</div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="progress_width">Sold bar</label>
              <input class="form-control<?= $invalid('progress_width') ?>" id="progress_width" name="progress_width" value="<?= e($form['progress_width']) ?>" placeholder="40%" maxlength="10">
              <?= $error('progress_width') ?>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="available">Available</label>
              <input class="form-control" type="number" min="0" id="available" name="available" value="<?= e($form['available']) ?>">
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label" for="sold">Sold</label>
              <input class="form-control" type="number" min="0" id="sold" name="sold" value="<?= e($form['sold']) ?>">
              <div class="form-text">Also shown in Quick View.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label d-block">Countdown</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="show_countdown" name="show_countdown" value="1" <?= $form['countdown_timer'] !== null ? 'checked' : '' ?>>
                <label class="form-check-label" for="show_countdown">Show the deal countdown on the highlighted Hot Product card</label>
              </div>
              <div class="form-text">Counts down to the deal end date in <a href="settings.php">Settings</a>.</div>
            </div>
          </div>
        </div>
      </details>

      <div class="card mb-4">
        <div class="card-header">Pictures<?= $product ? '' : ' *' ?></div>
        <div class="card-body">
          <p class="section-note">The <strong>first</strong> picture is the main image and the <strong>second</strong> shows when the cursor is over the product. All of them appear in the product gallery. Drag to rearrange (or use ‹ ›).</p>
          <ul class="pic-list" data-pic-list aria-label="Product pictures">
            <?php foreach ($pictures as $pi => $path): ?>
            <li class="pic-item" draggable="true" data-pic>
              <img src="<?= e(admin_asset($path)) ?>" alt="Picture <?= $pi + 1 ?>">
              <span class="pic-badge" data-pic-badge></span>
              <div class="pic-actions">
                <button type="button" data-pic-move="-1" aria-label="Move earlier">‹</button>
                <button type="button" data-pic-move="1" aria-label="Move later">›</button>
                <button type="button" class="pic-delete" data-pic-delete aria-label="Delete picture"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 12.5h9l1-12.5"/></svg></button>
              </div>
              <input type="hidden" name="image_order[]" value="e:<?= e($path) ?>">
            </li>
            <?php endforeach; ?>
          </ul>
          <label class="pic-add btn btn-outline-secondary btn-sm">
            + Add pictures
            <input class="visually-hidden" type="file" name="gallery_new[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple data-pic-input>
          </label>
          <?= $error('image') ?>
          <div class="form-text">Square pictures look best (500 × 500 px or larger). JPG, PNG, WebP or GIF up to 5 MB each.</div>
        </div>
      </div>

      <?php partial('admin/product-seo', ['product' => $product, 'form' => $form, 'pictureCount' => count($pictures), 'errors' => $errors]); ?>
    </div>

    <div class="col-xl-4">
      <div class="card mb-4">
        <div class="card-header">Show on the home page</div>
        <div class="card-body">
          <p class="section-note">New products are added at the end of a section. Reorder them in <a href="sections.php">Sections</a>.</p>
          <?php foreach ($productSections as $s): ?>
          <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="sections[]" value="<?= e($s['code']) ?>" id="sec-<?= e($s['code']) ?>" <?= in_array($s['code'], $inSections, true) ? 'checked' : '' ?>>
            <label class="form-check-label" for="sec-<?= e($s['code']) ?>">
              <?= e($s['title']) ?>
              <?php if (!$s['is_active']): ?><span class="badge badge-off ms-1">hidden</span><?php endif; ?>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($product): ?>
      <div class="card mb-4">
        <div class="card-header">Last 30 days</div>
        <div class="card-body">
          <?php $s = product_stats($id, 30); ?>
          <div class="d-flex justify-content-between small mb-1"><span>Quick View opens</span><strong><?= (int) $s['product_view'] ?></strong></div>
          <div class="d-flex justify-content-between small"><span>Added to cart</span><strong><?= (int) $s['add_to_cart'] ?></strong></div>
          <a class="small d-inline-block mt-2" href="analytics.php">Open analytics →</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="sticky-actions d-flex gap-2 align-items-center">
    <button class="btn btn-primary px-4" type="submit" name="action" value="save"><?= $product ? 'Save changes' : 'Create product' ?></button>
    <a class="btn btn-outline-secondary" href="products.php">Cancel</a>
    <?php if ($product): ?>
    <button class="btn btn-outline-secondary" type="submit" name="action" value="duplicate" formnovalidate title="Make a hidden copy of this product to edit">Duplicate</button>
    <button class="btn btn-outline-danger ms-auto" type="submit" name="action" value="delete" formnovalidate data-confirm="Delete this product? It will also be removed from every section, cart and wishlist.">Delete</button>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
