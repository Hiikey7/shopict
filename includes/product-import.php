<?php
declare(strict_types=1);

function product_import_columns(): array
{
    return [
        'title', 'sku', 'category', 'category_id', 'category_slugs', 'brand', 'description',
        'specs_json', 'seo_title', 'meta_description', 'focus_keyword', 'secondary_keywords',
        'faqs', 'image_alts', 'gtin', 'mpn', 'noindex', 'option_names_json', 'price',
        'old_price', 'img_src', 'img_hover', 'gallery_images', 'img_width', 'img_height',
        'hover_width', 'hover_height', 'sale_label', 'available', 'sold', 'progress_width',
        'countdown_timer', 'discount', 'is_active', 'is_preorder', 'variants_json',
    ];
}

function product_import_column_name(int $index): string
{
    $name = '';
    while ($index > 0) {
        $index--;
        $name = chr(65 + ($index % 26)) . $name;
        $index = intdiv($index, 26);
    }
    return $name;
}

function product_import_cell_xml(string $ref, string $value): string
{
    return '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">'
        . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        . '</t></is></c>';
}

function product_import_template_bytes(): string
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('The PHP Zip extension is required to create Excel templates.');
    }
    $columns = product_import_columns();
    $headers = '<row r="1">';
    foreach ($columns as $i => $column) {
        $headers .= product_import_cell_xml(product_import_column_name($i + 1) . '1', $column);
    }
    $headers .= '</row>';
    $instructions = [
        ['Product bulk import template'],
        ['Enter one product per row on the Products sheet. Keep the header row unchanged.'],
        ['The is_active column controls store visibility: TRUE/1 makes the product visible; FALSE/0 keeps it hidden.'],
        ['Required: title and price, plus an image path in img_src or the first gallery_images entry. Rows missing required data are rejected; other blank fields stay blank.'],
        ['img_src and img_hover are paths relative to assets/ (for example images/uploads/laptop.webp) or full image URLs. Local image files must already exist under assets/.'],
        ['category_id is one category id. category_slugs is optional: separate category slugs with | to assign multiple categories.'],
        ['specs_json: JSON array of pairs, e.g. [["Processor","Core i5"],["RAM","16GB"]].'],
        ['option_names_json: JSON array, e.g. ["RAM","Storage"].'],
        ['variants_json: JSON array, e.g. [{"options":{"RAM":"16GB","Storage":"512GB"},"price":85000,"old_price":90000,"sku":"ABC-16-512","stock":4,"is_default":true}].'],
        ['gallery_images: optional image paths separated by |. The first is the main image if img_src is blank; otherwise these are additional gallery images.'],
        ['Use TRUE/FALSE or 1/0 for boolean fields. Leave optional cells blank rather than entering guessed data.'],
        ['Do not edit id, created_at, or updated_at: they are assigned/managed by the system.'],
    ];
    $instructionRows = '';
    foreach ($instructions as $r => $cells) {
        $rowNumber = $r + 1;
        $instructionRows .= '<row r="' . $rowNumber . '">';
        foreach ($cells as $c => $value) {
            $instructionRows .= product_import_cell_xml(product_import_column_name($c + 1) . $rowNumber, $value);
        }
        $instructionRows .= '</row>';
    }
    $sheet = static fn (string $rows, string $dimension, string $autoFilter = ''): string =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<dimension ref="' . $dimension . '"/>'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/><sheetData>' . $rows . '</sheetData>'
        . $autoFilter . '</worksheet>';
    $lastColumn = product_import_column_name(count($columns));
    $temp = tempnam(sys_get_temp_dir(), 'product-template-');
    if ($temp === false) {
        throw new RuntimeException('Unable to create the Excel template file.');
    }
    $zip = new ZipArchive();
    try {
        if ($zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the Excel template workbook.');
        }
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Products" sheetId="1" r:id="rId1"/><sheet name="Instructions" sheetId="2" r:id="rId2"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
                . '</Relationships>',
            'xl/worksheets/sheet1.xml' => $sheet($headers, 'A1:' . $lastColumn . '1', '<autoFilter ref="A1:' . $lastColumn . '1"/>'),
            'xl/worksheets/sheet2.xml' => $sheet($instructionRows, 'A1:A' . count($instructions)),
        ];
        foreach ($files as $name => $contents) {
            if (!$zip->addFromString($name, $contents)) {
                throw new RuntimeException('Unable to write the Excel template workbook.');
            }
        }
        if (!$zip->close()) {
            throw new RuntimeException('Unable to finish the Excel template workbook.');
        }
        $contents = file_get_contents($temp);
        if ($contents === false) {
            throw new RuntimeException('Unable to read the completed Excel template workbook.');
        }
        return $contents;
    } finally {
        if (is_file($temp)) {
            unlink($temp);
        }
    }
}

function product_import_template(): never
{
    $contents = product_import_template_bytes();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="shop-ict-product-import-template.xlsx"');
    header('Content-Length: ' . strlen($contents));
    echo $contents;
    exit;
}

function product_import_parse_xlsx(string $file): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('The PHP Zip extension is required to read Excel workbooks.');
    }
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        throw new RuntimeException('The uploaded file is not a valid .xlsx workbook.');
    }
    try {
        $worksheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($worksheetXml === false || strlen($worksheetXml) > 8 * 1024 * 1024) {
            throw new RuntimeException('The workbook has no readable Products worksheet or it is too large.');
        }
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            if (strlen($sharedXml) > 8 * 1024 * 1024) {
                throw new RuntimeException('The workbook shared-string table is too large.');
            }
            $shared = simplexml_load_string($sharedXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if ($shared === false) {
                throw new RuntimeException('The workbook shared-string table is invalid.');
            }
            foreach ($shared->xpath('//*[local-name()="si"]') ?: [] as $item) {
                $text = '';
                foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $part) {
                    $text .= (string) $part;
                }
                $sharedStrings[] = $text;
            }
        }
        $xml = simplexml_load_string($worksheetXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        if ($xml === false) {
            throw new RuntimeException('The Products worksheet XML is invalid.');
        }
        $rows = [];
        foreach ($xml->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $row) {
            $rowNumber = (int) ($row['r'] ?? 0);
            if ($rowNumber > 502) {
                throw new RuntimeException('Import is limited to 500 product rows per workbook.');
            }
            $cells = [];
            foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $ref = (string) $cell['r'];
                if (!preg_match('/^([A-Z]+)\d+$/i', $ref, $match)) {
                    continue;
                }
                $column = 0;
                foreach (str_split(strtoupper($match[1])) as $letter) {
                    $column = $column * 26 + ord($letter) - 64;
                }
                $type = (string) $cell['t'];
                if ($type === 'inlineStr') {
                    $value = implode('', array_map('strval', $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: []));
                } else {
                    $valueNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
                    $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    }
                }
                $cells[$column - 1] = $value;
            }
            if ($cells) {
                $rows[$rowNumber] = $cells;
            }
        }
        if (!$rows) {
            throw new RuntimeException('The Products worksheet is empty.');
        }
        ksort($rows);
        $headerNumber = array_key_first($rows);
        $headerRow = $rows[$headerNumber];
        unset($rows[$headerNumber]);
        $headers = array_map(static fn ($value) => trim((string) $value), array_values($headerRow));
        $expected = product_import_columns();
        if ($headers !== $expected) {
            throw new RuntimeException('The Products sheet headers do not match the template. Download a fresh template and keep its first row unchanged.');
        }
        $result = [];
        foreach ($rows as $number => $cells) {
            $record = [];
            foreach ($expected as $index => $column) {
                $record[$column] = trim((string) ($cells[$index] ?? ''));
            }
            if (count(array_filter($record, static fn ($value) => $value !== '')) > 0) {
                $result[$number] = $record;
            }
        }
        return $result;
    } finally {
        $zip->close();
    }
}

function product_import_json(string $value, string $field): ?array
{
    if (trim($value) === '') {
        return null;
    }
    try {
        $decoded = json_decode($value, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new InvalidArgumentException("$field must contain valid JSON.");
    }
    if (!is_array($decoded)) {
        throw new InvalidArgumentException("$field must be a JSON array or object.");
    }
    return $decoded;
}

function product_import_nullable_number(string $value, string $field, bool $integer = false): int|float|null
{
    if ($value === '') {
        return null;
    }
    if (!is_numeric($value) || (float) $value < 0 || ($integer && (string) (int) $value !== $value)) {
        throw new InvalidArgumentException("$field must be a non-negative " . ($integer ? 'whole number' : 'number') . '.');
    }
    return $integer ? (int) $value : (float) $value;
}

function product_import_bool(string $value, string $field, int $default = 0): int
{
    if ($value === '') {
        return $default;
    }
    return match (strtolower($value)) {
        '1', 'true', 'yes' => 1,
        '0', 'false', 'no' => 0,
        default => throw new InvalidArgumentException("$field must be TRUE, FALSE, 1, or 0."),
    };
}

function product_import_image(string $path, string $field, bool $required = false): ?string
{
    $path = trim($path);
    if ($path === '') {
        if ($required) {
            throw new InvalidArgumentException("$field is required (local image path or full URL).");
        }
        return null;
    }
    if (preg_match('#^https://#i', $path)) {
        return $path;
    }
    if (preg_match('#^(?:https?://|data:|//)#i', $path) || str_contains($path, "\0") || preg_match('#(^|[\\\\/])\.\.([\\\\/]|$)#', $path)) {
        throw new InvalidArgumentException("$field must be an https URL or a path relative to assets/ without traversal.");
    }
    $relative = ltrim(str_replace('\\', '/', $path), '/');
    $assets = realpath(__DIR__ . '/../assets');
    $resolved = realpath(__DIR__ . '/../assets/' . $relative);
    if (!$assets || !$resolved || !str_starts_with(strtolower($resolved), strtolower($assets . DIRECTORY_SEPARATOR)) || !is_file($resolved)) {
        throw new InvalidArgumentException("$field file was not found inside assets/: $path");
    }
    if (!getimagesize($resolved)) {
        throw new InvalidArgumentException("$field must refer to a valid image.");
    }
    return $relative;
}

function product_import_record(array $row): array
{
    $title = trim($row['title']);
    if ($title === '') {
        throw new InvalidArgumentException('title is required.');
    }
    if ($row['price'] === '' || !is_numeric($row['price']) || (float) $row['price'] < 0) {
        throw new InvalidArgumentException('price is required and must be a non-negative number.');
    }
    $galleryRaw = $row['gallery_images'] === '' ? [] : array_values(array_filter(array_map('trim', explode('|', $row['gallery_images'])), static fn ($path) => $path !== ''));
    $mainImage = product_import_image($row['img_src'] !== '' ? $row['img_src'] : (string) ($galleryRaw[0] ?? ''), 'img_src', true);
    $gallery = [];
    foreach ($galleryRaw as $image) {
        $path = product_import_image($image, 'gallery_images');
        if ($path && $path !== $mainImage && !in_array($path, $gallery, true)) {
            $gallery[] = $path;
        }
    }
    $data = [
        'title' => mb_substr($title, 0, 255),
        'sku' => $row['sku'] !== '' ? mb_substr($row['sku'], 0, 64) : null,
        'category' => $row['category'] !== '' ? mb_substr($row['category'], 0, 100) : null,
        'category_id' => null,
        'brand' => $row['brand'] !== '' ? mb_substr($row['brand'], 0, 100) : null,
        'description' => $row['description'] !== '' ? $row['description'] : null,
        'specs' => null,
        'option_names' => null,
        'price' => (float) $row['price'],
        'old_price' => product_import_nullable_number($row['old_price'], 'old_price'),
        'img_src' => $mainImage,
        'img_hover' => product_import_image($row['img_hover'], 'img_hover'),
        'img_width' => product_import_nullable_number($row['img_width'], 'img_width', true),
        'img_height' => product_import_nullable_number($row['img_height'], 'img_height', true),
        'hover_width' => product_import_nullable_number($row['hover_width'], 'hover_width', true),
        'hover_height' => product_import_nullable_number($row['hover_height'], 'hover_height', true),
        'sale_label' => $row['sale_label'] !== '' ? mb_substr($row['sale_label'], 0, 20) : null,
        'available' => product_import_nullable_number($row['available'], 'available', true),
        'sold' => product_import_nullable_number($row['sold'], 'sold', true),
        'progress_width' => $row['progress_width'] !== '' ? mb_substr($row['progress_width'], 0, 10) : null,
        'countdown_timer' => product_import_nullable_number($row['countdown_timer'], 'countdown_timer', true),
        'discount' => product_import_nullable_number($row['discount'], 'discount'),
        'is_active' => product_import_bool($row['is_active'], 'is_active'),
        'is_preorder' => product_import_bool($row['is_preorder'], 'is_preorder'),
        'seo_title' => $row['seo_title'] !== '' ? mb_substr($row['seo_title'], 0, 120) : null,
        'meta_description' => $row['meta_description'] !== '' ? mb_substr($row['meta_description'], 0, 320) : null,
        'focus_keyword' => $row['focus_keyword'] !== '' ? mb_substr($row['focus_keyword'], 0, 120) : null,
        'secondary_keywords' => $row['secondary_keywords'] !== '' ? mb_substr($row['secondary_keywords'], 0, 600) : null,
        'faqs' => $row['faqs'] !== '' ? $row['faqs'] : null,
        'image_alts' => $row['image_alts'] !== '' ? $row['image_alts'] : null,
        'gtin' => $row['gtin'] !== '' ? preg_replace('/\D+/', '', $row['gtin']) : null,
        'mpn' => $row['mpn'] !== '' ? mb_substr($row['mpn'], 0, 64) : null,
        'noindex' => product_import_bool($row['noindex'], 'noindex'),
    ];
    $gtinLength = $data['gtin'] === null ? 0 : strlen($data['gtin']);
    if ($data['gtin'] !== null && !in_array($gtinLength, [8, 12, 13, 14], true)) {
        throw new InvalidArgumentException('gtin must contain 8, 12, 13, or 14 digits.');
    }

    $categories = [];
    if ($row['category_id'] !== '') {
        if (!ctype_digit($row['category_id']) || (int) $row['category_id'] < 1) {
            throw new InvalidArgumentException('category_id must be a positive whole number.');
        }
        $category = category_find((int) $row['category_id']);
        if (!$category || !(int) $category['is_active']) {
            throw new InvalidArgumentException('category_id must refer to an active category.');
        }
        $categories[(int) $category['id']] = $category;
    }
    if ($row['category_slugs'] !== '') {
        foreach (array_filter(array_map('trim', explode('|', $row['category_slugs']))) as $slug) {
            $category = category_by_slug($slug);
            if (!$category || !(int) $category['is_active']) {
                throw new InvalidArgumentException("Unknown or inactive category slug: $slug");
            }
            $categories[(int) $category['id']] = $category;
        }
    }
    foreach ($categories as $category) {
        $main = category_main($category);
        if ($main) {
            $categories[(int) $main['id']] = $main;
        }
    }
    $categories = array_values($categories);
    if ($categories) {
        $primary = current(array_filter($categories, static fn ($category) => $category['parent_id'] !== null)) ?: $categories[0];
        $data['category_id'] = (int) $primary['id'];
    }
    $specs = product_import_json($row['specs_json'], 'specs_json');
    if ($specs !== null) {
        if (array_is_list($specs)) {
            $normalized = [];
            foreach ($specs as $pair) {
                if (!is_array($pair) || count($pair) !== 2 || trim((string) ($pair[0] ?? '')) === '' || trim((string) ($pair[1] ?? '')) === '') {
                    throw new InvalidArgumentException('specs_json list items must be [label, value] pairs.');
                }
                $normalized[] = [mb_substr(trim((string) $pair[0]), 0, 60), mb_substr(trim((string) $pair[1]), 0, 200)];
            }
        } else {
            $normalized = [];
            foreach ($specs as $label => $value) {
                if (trim((string) $label) !== '' && trim((string) $value) !== '') {
                    $normalized[] = [mb_substr(trim((string) $label), 0, 60), mb_substr(trim((string) $value), 0, 200)];
                }
            }
        }
        $data['specs'] = $normalized ? json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;
    }
    $optionNames = product_import_json($row['option_names_json'], 'option_names_json');
    if ($optionNames !== null) {
        if (!array_is_list($optionNames) || count($optionNames) > 4) {
            throw new InvalidArgumentException('option_names_json must be a JSON array with up to 4 option names.');
        }
        $optionNames = array_values(array_filter(array_map(static fn ($name) => mb_substr(trim((string) $name), 0, 40), $optionNames)));
        if (count(array_unique(array_map('mb_strtolower', $optionNames))) !== count($optionNames)) {
            throw new InvalidArgumentException('option_names_json must not contain duplicate option names.');
        }
        $data['option_names'] = $optionNames ? json_encode($optionNames, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null;
    }
    $variants = product_import_json($row['variants_json'], 'variants_json') ?? [];
    if ($variants && (!$data['option_names'] || !array_is_list($variants) || count($variants) > 100)) {
        throw new InvalidArgumentException('variants_json requires option_names_json and may contain at most 100 variants.');
    }
    $normalizedVariants = [];
    foreach ($variants as $index => $variant) {
        if (!is_array($variant) || !is_array($variant['options'] ?? null) || !is_numeric($variant['price'] ?? null) || (float) $variant['price'] < 0) {
            throw new InvalidArgumentException('Each variant needs an options object and a non-negative price.');
        }
        $variantOldPrice = $variant['old_price'] ?? null;
        if ($variantOldPrice !== null && $variantOldPrice !== '' && (!is_numeric($variantOldPrice) || (float) $variantOldPrice < 0)) {
            throw new InvalidArgumentException('Variant old_price values must be non-negative numbers.');
        }
        $variantStock = $variant['stock'] ?? null;
        if ($variantStock !== null && $variantStock !== '' && (!is_numeric($variantStock) || (int) $variantStock < 0 || (string) (int) $variantStock !== (string) $variantStock)) {
            throw new InvalidArgumentException('Variant stock values must be non-negative whole numbers.');
        }
        $options = [];
        foreach ($optionNames ?? [] as $name) {
            $value = trim((string) ($variant['options'][$name] ?? ''));
            if ($value === '') {
                throw new InvalidArgumentException('Every variant needs a value for each option name.');
            }
            $options[$name] = mb_substr($value, 0, 60);
        }
        $normalizedVariants[] = [
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'price' => (float) $variant['price'],
            'old_price' => $variantOldPrice !== null && $variantOldPrice !== '' ? (float) $variantOldPrice : null,
            'sku' => isset($variant['sku']) && trim((string) $variant['sku']) !== '' ? mb_substr(trim((string) $variant['sku']), 0, 64) : null,
            'stock' => $variantStock !== null && $variantStock !== '' ? (int) $variantStock : null,
            'is_default' => !empty($variant['is_default']) ? 1 : 0,
            'position' => $index + 1,
        ];
    }
    if ($normalizedVariants && !array_filter(array_column($normalizedVariants, 'is_default'))) {
        $normalizedVariants[0]['is_default'] = 1;
    }

    return [$data, $categories, $gallery, $normalizedVariants];
}
