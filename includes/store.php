<?php
declare(strict_types=1);

// ---------------------------------------------------------------- sections & banners

/** A home page section's settings (title, visibility), or null if it does not exist. */
function section(string $code): ?array
{
    static $sections = null;
    if ($sections === null) {
        $sections = [];
        foreach (db()->query('SELECT * FROM sections') as $row) {
            $sections[$row['code']] = $row;
        }
    }
    return $sections[$code] ?? null;
}

/**
 * Whether a home page section is shown: switched on in Admin → Sections and, for a section that lists products,
 * with at least one visible product in it (so an empty section never leaves a blank gap on the page).
 */
function section_visible(string $code): bool
{
    static $hasProducts = [];
    $row = section($code);
    if (!$row || !$row['is_active']) {
        return false;
    }
    $takesProducts = $row['max_products'] === null || (int) $row['max_products'] > 0;
    if ($takesProducts) {
        $hasProducts[$code] ??= section_products($code) !== [];
        return $hasProducts[$code];
    }
    return true;
}

function section_title(string $code): string
{
    return section($code)['title'] ?? '';
}

/** Active banners for a placement, in display order. */
function banners(string $placement): array
{
    $stmt = db()->prepare('SELECT * FROM banners WHERE placement = ? AND is_active = 1 ORDER BY position, id');
    $stmt->execute([$placement]);
    return $stmt->fetchAll();
}

function banner(string $placement): ?array
{
    return banners($placement)[0] ?? null;
}

// ---------------------------------------------------------------- products

/** Products placed in a home page section, in display order, with per-placement options merged in. */
function section_products(string $section): array
{
    $stmt = db()->prepare(
        'SELECT p.*, sp.animation, sp.wow_delay
           FROM section_products sp
           JOIN products p ON p.id = sp.product_id
          WHERE sp.section = ? AND p.is_active = 1
          ORDER BY sp.position, sp.id'
    );
    $stmt->execute([$section]);
    $products = array_map('price_product', $stmt->fetchAll());
    foreach ($products as $p) {
        register_product($p);
    }
    return $products;
}

/**
 * Link to the shop. One category or one brand gets its own short address ("laptops", "business-laptops", "hp");
 * filters, sorting and pages are added after it ("laptops?page=2").
 */
function shop_url(array $params = []): string
{
    // Leave out empty values and page 1 (the default).
    $params = array_filter($params, fn ($v, $k) => $v !== null && $v !== '' && $v !== [] && !($k === 'page' && (int) $v === 1), ARRAY_FILTER_USE_BOTH);
    return 'shop.php' . ($params ? '?' . http_build_query($params) : '');
}

/**
 * Visible products for the shop page, filtered by category and search words, sorted, one page at a time.
 * Returns [products, total].
 */
function shop_products(?string $category, string $search, string $sort, int $page, int $perPage, array $categoryIds = [], ?int $minPrice = null, ?int $maxPrice = null, array $finder = [], ?array $brand = null): array
{
    [$where, $params] = shop_filters($category, $search, $categoryIds, $finder, $brand);
    if ($minPrice !== null) {
        $where[] = 'price >= ?';
        $params[] = $minPrice;
    }
    if ($maxPrice !== null) {
        $where[] = 'price <= ?';
        $params[] = $maxPrice;
    }
    $whereSql = implode(' AND ', $where);
    [$searchRank, $rankParams] = shop_search_rank($search);
    $order = match ($sort) {
        'random'     => "CRC32(CONCAT(CURRENT_DATE, '-', id)), id DESC",
        'relevance'  => "($searchRank) DESC, COALESCE(sold, 0) DESC, id DESC",
        'price-asc'  => 'price ASC, id DESC',
        'price-desc' => 'price DESC, id DESC',
        'popular'    => 'COALESCE(sold, 0) DESC, id DESC',
        default      => 'id DESC',
    };
    $stmt = db()->prepare("SELECT COUNT(*) FROM products WHERE $whereSql");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    $offset = max(0, ($page - 1) * $perPage);
    $stmt = db()->prepare("SELECT * FROM products WHERE $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset");
    $stmt->execute($sort === 'relevance' ? [...$params, ...$rankParams] : $params);
    $products = array_map('price_product', $stmt->fetchAll());
    foreach ($products as $p) {
        register_product($p);
    }
    return [$products, $total];
}

/** Cheapest and dearest price for the current category / search, ignoring the price filter: [min, max]. */
function shop_price_bounds(?string $category, string $search, array $categoryIds = [], array $finder = [], ?array $brand = null): array
{
    [$where, $params] = shop_filters($category, $search, $categoryIds, $finder, $brand);
    $stmt = db()->prepare('SELECT MIN(price), MAX(price) FROM products WHERE ' . implode(' AND ', $where));
    $stmt->execute($params);
    [$min, $max] = $stmt->fetch(PDO::FETCH_NUM);
    return [$min !== null ? (int) floor((float) $min) : null, $max !== null ? (int) ceil((float) $max) : null];
}

/** WHERE parts shared by the shop listing and its price bounds: [conditions, parameters]. */
function shop_search_terms(string $search): array
{
    $normalized = mb_strtolower($search, 'UTF-8');
    $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? $normalized;
    $terms = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $terms = array_map(fn ($term) => preg_replace('/inches$/u', 'inch', $term) ?? $term, $terms);
    return array_slice(array_values(array_unique($terms)), 0, 8);
}

function shop_search_pattern(string $term): array
{
    if (preg_match('/^\d+$/', $term)) {
        return ['REGEXP', '(^|[^0-9])' . $term . '([^0-9]|$)'];
    }
    return ['LIKE', '%' . $term . '%'];
}

function shop_search_is_screen_size_term(array $terms, int $index): bool
{
    return preg_match('/^\d+$/', $terms[$index]) === 1 && ($terms[$index + 1] ?? null) === 'inch';
}

/** Match every query term against product data, category labels and variant options. */
function shop_search_conditions(string $search, string $table = 'products'): array
{
    $where = [];
    $params = [];
    $prefix = $table . '.';
    $fields = [
        'title', 'category', 'brand', 'sku', 'description', 'specs', 'option_names',
        'seo_title', 'meta_description', 'focus_keyword', 'secondary_keywords', 'mpn', 'gtin',
    ];

    $terms = shop_search_terms($search);
    foreach ($terms as $index => $term) {
        [$operator, $pattern] = shop_search_pattern($term);
        $matches = [];
        $termFields = shop_search_is_screen_size_term($terms, $index)
            ? ['title', 'category', 'specs', 'option_names', 'seo_title', 'focus_keyword', 'secondary_keywords', 'mpn']
            : $fields;
        foreach ($termFields as $field) {
            $matches[] = $prefix . $field . ' ' . $operator . ' ?';
            $params[] = $pattern;
        }
        $matches[] = "EXISTS (
            SELECT 1
            FROM product_variants search_variant
            WHERE search_variant.product_id = {$prefix}id
              AND (search_variant.options {$operator} ? OR search_variant.sku {$operator} ?)
        )";
        $params[] = $pattern;
        $params[] = $pattern;
        $matches[] = "EXISTS (
            SELECT 1
            FROM product_category_links search_link
            JOIN product_categories search_category ON search_category.id = search_link.category_id
            WHERE search_link.product_id = {$prefix}id
              AND search_category.name {$operator} ?
        )";
        $params[] = $pattern;
        $where[] = '(' . implode(' OR ', $matches) . ')';
    }

    return [$where, $params];
}

/** Relevance score for matching query terms in product titles, brand, specs, descriptions and variants. */
function shop_search_rank(string $search, string $table = 'products'): array
{
    $prefix = $table . '.';
    $rank = [];
    $params = [];
    $terms = shop_search_terms($search);
    foreach ($terms as $index => $term) {
        [$operator, $pattern] = shop_search_pattern($term);
        $screenSize = shop_search_is_screen_size_term($terms, $index);
        $rank[] = '(CASE WHEN ' . $prefix . 'title ' . $operator . ' ? THEN 12 ELSE 0 END'
            . ($screenSize ? '' : ' + CASE WHEN ' . $prefix . 'brand ' . $operator . ' ? THEN 10 ELSE 0 END')
            . ' + CASE WHEN ' . $prefix . 'category ' . $operator . ' ? THEN 6 ELSE 0 END'
            . ' + CASE WHEN ' . $prefix . 'specs ' . $operator . ' ? THEN 8 ELSE 0 END'
            . ($screenSize ? '' : ' + CASE WHEN ' . $prefix . 'description ' . $operator . ' ? THEN 1 ELSE 0 END')
            . " + CASE WHEN EXISTS (SELECT 1 FROM product_variants rank_variant WHERE rank_variant.product_id = {$prefix}id AND rank_variant.options {$operator} ?) THEN 7 ELSE 0 END"
            . ' + CASE WHEN ' . $prefix . 'option_names ' . $operator . ' ? THEN 4 ELSE 0 END'
            . ' + CASE WHEN ' . $prefix . 'focus_keyword ' . $operator . ' ? THEN 2 ELSE 0 END'
            . ' + CASE WHEN ' . $prefix . 'secondary_keywords ' . $operator . ' ? THEN 2 ELSE 0 END)';
        array_push($params, ...array_fill(0, $screenSize ? 7 : 9, $pattern));
    }
    return [$rank ? implode(' + ', $rank) : '0', $params];
}

function shop_filters(?string $category, string $search, array $categoryIds = [], array $finder = [], ?array $brand = null): array
{
    $where = ['is_active = 1'];
    $params = [];
    if ($brand) { // a brand page (brand_by_slug)
        [$brandSql, $brandParams] = brand_products_sql($brand);
        $where[] = $brandSql;
        array_push($params, ...$brandParams);
    }
    if ($categoryIds) {
        [$catSql, $catParams] = products_in_categories_sql($categoryIds);
        $where[] = $catSql;
        array_push($params, ...$catParams);
    } elseif ($category !== null && $category !== '') {
        $where[] = 'category = ?'; // old-style links: ?category=Name
        $params[] = $category;
    }
    if (trim($search) !== '') {
        [$searchWhere, $searchParams] = shop_search_conditions($search);
        array_push($where, ...$searchWhere);
        array_push($params, ...$searchParams);
    }
    if ($finder) {
        [$fw, $fp] = finder_conditions($finder);
        array_push($where, ...$fw);
        array_push($params, ...$fp);
    }
    return [$where, $params];
}

/**
 * Link to a product's page, with its name in it: "samsung-essential-monitor-p369".
 * The id at the end is what finds the product (router.php / .htaccess send it to product.php).
 */
function product_url(array|int $p): string
{
    $id = (int) (is_array($p) ? $p['id'] : $p);
    return 'product.php?id=' . $id;
}

/** URL-friendly product name, cut at a word boundary to at most 70 characters. */
function product_slug(string $title): string
{
    $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
    if (strlen($slug) > 70) {
        $cut = substr($slug, 0, 71);
        $slug = rtrim(substr($cut, 0, (int) (strrpos($cut, '-') ?: 70)), '-');
    }
    return $slug !== '' ? $slug : 'product';
}

/** Other visible products from the same category (topped up with newer products), for "Related products". */
function related_products(array $p, int $limit = 10): array
{
    // Same subcategory first, then same main category, then same brand, then newest.
    $ids = product_category_ids($p) ?: [0];
    $mains = array_values(array_unique(array_filter(array_map(fn ($cid) => category_main(category_find($cid))['id'] ?? null, $ids)))) ?: [0];
    [$inCats, $catParams] = products_in_categories_sql($ids);
    [$inMains, $mainParams] = products_in_categories_sql(array_merge(...array_map(fn ($mid) => category_ids_within(category_find($mid) ?? ['id' => 0]), $mains)));
    $stmt = db()->prepare(
        "SELECT * FROM products
          WHERE is_active = 1 AND id <> ?
          ORDER BY ($inCats) DESC, ($inMains) DESC, (brand <=> ?) DESC, (category <=> ?) DESC, id DESC
          LIMIT " . (int) $limit
    );
    $stmt->execute([$p['id'], ...$catParams, ...$mainParams, $p['brand'], $p['category']]);
    $products = array_map('price_product', $stmt->fetchAll());
    foreach ($products as $row) {
        register_product($row);
    }
    return $products;
}

/** A product row exactly as stored (used by the admin). */
function find_product(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** A product as shoppers see it: only when active, with any running promotion applied. */
function shop_product(int $id): ?array
{
    $p = find_product($id);
    return $p && $p['is_active'] ? price_product($p) : null;
}

function product_images(int $productId): array
{
    $stmt = db()->prepare('SELECT image FROM product_images WHERE product_id = ? ORDER BY position');
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/** Decodes a JSON column, returning $default when it is empty or invalid. */
function json_column(?string $json, array $default = []): array
{
    if ($json === null || $json === '') {
        return $default;
    }
    $value = json_decode($json, true);
    return is_array($value) ? $value : $default;
}

/** Specification rows as [label, value] pairs. */
function product_specs(array $p): array
{
    return array_values(array_filter(json_column($p['specs'] ?? null), fn ($row) => is_array($row) && count($row) === 2));
}

/** "About this item" bullets: one per non-empty line of the description. */
/**
 * The short "About this item" points (Quick View, search checks). A description written in sections
 * ("## Special Features" headings, "- " points) gives only its first list of "- " points; an older one-point-per-line
 * description gives every line.
 */
function product_bullets(array $p): array
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($p['description'] ?? '')))));
    if (!product_description_is_rich($p)) {
        return $lines;
    }
    $points = [];
    foreach ($lines as $line) {
        if (str_starts_with($line, '#')) {
            break;
        }
        if (preg_match('/^[-•*]\s+(.+)$/u', $line, $m)) {
            $points[] = $m[1];
        }
    }
    return $points;
}

/** Whether a description is written in sections ("## Heading" lines). */
function product_description_is_rich(array $p): bool
{
    return (bool) preg_match('/^##\s/m', (string) ($p['description'] ?? ''));
}

/** The whole description as plain text (headings left out), for search engines and the Shopping feed. */
function product_description_text(array $p): string
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string) ($p['description'] ?? '')) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $line = preg_replace('/^[-•*]\s+/u', '', $line);
        $out[] = preg_match('/[.!?]$/u', $line) ? $line : $line . '.';
    }
    return trim(preg_replace('/\s+/u', ' ', implode(' ', $out)));
}

/**
 * A description written in sections, as HTML: "## Heading" → heading, "- Label: text" → point with the label in bold,
 * other lines → paragraphs. Everything is escaped.
 */
function product_description_html(array $p): string
{
    $html = '';
    $list = [];
    $flush = function () use (&$html, &$list) {
        if ($list) {
            $html .= '<ul class="product-about-list pv-about">' . implode('', $list) . '</ul>';
            $list = [];
        }
    };
    foreach (preg_split('/\r\n|\r|\n/', (string) ($p['description'] ?? '')) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^(#{2,3})\s+(.+)$/u', $line, $m)) {
            $flush();
            $html .= '<h' . (strlen($m[1]) + 1) . ' class="pv-about-h">' . e($m[2]) . '</h' . (strlen($m[1]) + 1) . '>';
        } elseif (preg_match('/^[-•*]\s+(.+)$/u', $line, $m)) {
            $text = preg_match('/^([^:]{2,48}):\s+(.+)$/u', $m[1], $lm) ? '<strong>' . e($lm[1]) . ':</strong> ' . e($lm[2]) : e($m[1]);
            $list[] = '<li><p class="body-text-3">' . $text . '</p></li>';
        } else {
            $flush();
            $html .= '<p class="pv-about-p">' . e($line) . '</p>';
        }
    }
    $flush();
    return $html;
}

/** Variant option names in order, e.g. ["RAM", "Storage"]. */
function product_option_names(array $p): array
{
    return array_values(array_filter(json_column($p['option_names'] ?? null), 'is_string'));
}

/** Variants of a product in display order, with options decoded. Results are cached per request. */
function product_variants(int $productId): array
{
    static $cache = [];
    if (!isset($cache[$productId])) {
        $stmt = db()->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY position, id');
        $stmt->execute([$productId]);
        $cache[$productId] = array_map(function ($v) {
            $v['options'] = json_column($v['options']);
            return $v;
        }, $stmt->fetchAll());
    }
    return $cache[$productId];
}

function find_variant(int $productId, int $variantId): ?array
{
    foreach (product_variants($productId) as $v) {
        if ((int) $v['id'] === $variantId) {
            return $v;
        }
    }
    return null;
}

function default_variant(int $productId): ?array
{
    $variants = product_variants($productId);
    foreach ($variants as $v) {
        if ($v['is_default']) {
            return $v;
        }
    }
    return $variants[0] ?? null;
}

/** Automatic SKU: first 3 letters of the brand (or ICT) + the product number, e.g. "SAM-0369". */
function product_sku(?string $brand, int $id): string
{
    $letters = strtoupper(substr((string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $brand), 0, 3));
    return ($letters !== '' ? $letters : 'ICT') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

/** e.g. "16GB / 512GB SSD" */
function variant_label(?array $variant): string
{
    return $variant ? implode(' / ', array_map('strval', $variant['options'])) : '';
}

/** Products rendered on the page, exposed to JavaScript so Quick View can show them without a request. */
function register_product(?array $p = null): array
{
    static $registry = [];
    if ($p !== null && !isset($registry[(int) $p['id']])) {
        $id = (int) $p['id'];
        $promo = $p['promotion'] ?? null;
        $images = array_map('asset', array_values(array_unique([$p['img_src'], ...product_images($id)])));
        $registry[$id] = [
            'id'          => $id,
            'title'       => $p['title'],
            'url'         => product_url($id),
            'category'    => $p['category'],
            'brand'       => $p['brand'],
            'sku'         => $p['sku'],
            'sold'        => $p['sold'] !== null ? (int) $p['sold'] : null,
            'price'       => (float) $p['price'],
            'oldPrice'    => $p['old_price'] !== null ? (float) $p['old_price'] : null,
            'imgSrc'      => asset($p['img_src']),
            'images'      => $images,
            'specs'       => product_specs($p),
            'bullets'     => product_bullets($p),
            'promo'       => $promo ? ($promo['badge'] ?: $promo['name']) : null,
            'preorder'    => !empty($p['is_preorder']),
            'optionNames' => product_option_names($p),
            'variants'    => array_map(function ($v) use ($promo) {
                $priced = apply_promotion((float) $v['price'], $v['old_price'] !== null ? (float) $v['old_price'] : null, $promo);
                return [
                    'id'        => (int) $v['id'],
                    'options'   => $v['options'],
                    'price'     => $priced[0],
                    'oldPrice'  => $priced[1],
                    'sku'       => $v['sku'],
                    'stock'     => $v['stock'] !== null ? (int) $v['stock'] : null,
                    'isDefault' => (bool) $v['is_default'],
                ];
            }, product_variants($id)),
        ];
    }
    return $registry;
}

// ---------------------------------------------------------------- promotions

/** How many (not cancelled) orders used each promotion: [promotion id => count]. */
function promotion_uses(): array
{
    static $uses = null;
    if ($uses === null) {
        $uses = db()->query(
            "SELECT p.id, COUNT(DISTINCT o.id)
               FROM promotions p
               JOIN orders o ON o.status <> 'cancelled'
                AND (o.promotion_id = p.id OR EXISTS (SELECT 1 FROM order_items i WHERE i.order_id = o.id AND i.promotion_id = p.id))
              GROUP BY p.id"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $uses = array_map('intval', $uses);
    }
    return $uses;
}

/** Places left on a promotion with a usage limit, or null when it has no limit. */
function promotion_left(array $promo): ?int
{
    if ($promo['max_uses'] === null || $promo['max_uses'] === '') {
        return null;
    }
    return max(0, (int) $promo['max_uses'] - (promotion_uses()[(int) $promo['id']] ?? 0));
}

/**
 * Promotions running right now, each with the ids of the products it targets. Loaded once per request.
 * Promotions that have reached their usage limit are left out.
 * By default only automatic ones (no promo code); $coded = true gives the ones that need a code.
 */
function active_promotions(bool $coded = false): array
{
    static $all = null;
    if ($all === null) {
        $now = date('Y-m-d H:i:s');
        $stmt = db()->prepare(
            'SELECT * FROM promotions
              WHERE is_active = 1
                AND (starts_at IS NULL OR starts_at <= ?)
                AND (ends_at IS NULL OR ends_at > ?)'
        );
        $stmt->execute([$now, $now]);
        $all = array_values(array_filter($stmt->fetchAll(), fn ($p) => promotion_left($p) !== 0));
        $targets = db()->prepare('SELECT product_id FROM promotion_products WHERE promotion_id = ?');
        foreach ($all as &$promo) {
            $targets->execute([$promo['id']]);
            $promo['product_ids'] = array_map('intval', $targets->fetchAll(PDO::FETCH_COLUMN));
        }
        unset($promo);
    }
    return array_values(array_filter($all, fn ($p) => ($p['code'] !== null && $p['code'] !== '') === $coded));
}

// ---------------------------------------------------------------- promo codes

/** Promo codes that work on this product (shown on the product page), biggest saving first. */
function promo_codes_for(array $p): array
{
    $codes = array_values(array_filter(active_promotions(true), fn ($promo) => promotion_applies($promo, $p)));
    $price = (float) $p['price'];
    usort($codes, fn ($a, $b) => promotion_discount($price, $b) <=> promotion_discount($price, $a));
    return $codes;
}

/** "KSh 500 off" or "10% off". */
function promo_code_label(array $promo): string
{
    $v = (float) $promo['discount_value'];
    return $promo['discount_type'] === 'percent'
        ? rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . '% off'
        : money($v) . ' off';
}

function normalize_promo_code(string $code): string
{
    return strtoupper(preg_replace('/\s+/', '', $code));
}

/**
 * Checks a code against the cart. Returns [promotion, discount, null] when it works,
 * or [null, 0, reason] when it doesn't.
 */
function promo_code_check(string $code, array $items): array
{
    $code = normalize_promo_code($code);
    if ($code === '') {
        return [null, 0.0, 'Enter a promo code.'];
    }
    $promo = null;
    foreach (active_promotions(true) as $candidate) {
        if (strcasecmp($candidate['code'], $code) === 0) {
            $promo = $candidate;
            break;
        }
    }
    if (!$promo) {
        return [null, 0.0, 'That promo code is not valid or has expired.'];
    }
    $subtotal = cart_subtotal($items);
    if ($promo['min_spend'] !== null && $subtotal < (float) $promo['min_spend']) {
        return [null, 0.0, 'Code ' . $promo['code'] . ' needs a minimum spend of ' . money($promo['min_spend']) . '.'];
    }
    // The discount counts only the items the code covers. A fixed amount comes off once per order.
    $eligible = 0.0;
    foreach ($items as $it) {
        if (promotion_applies($promo, $it)) {
            $eligible += (float) $it['price'] * (int) $it['quantity'];
        }
    }
    if ($eligible <= 0) {
        return [null, 0.0, 'Code ' . $promo['code'] . ' does not cover the items in your cart.'];
    }
    $discount = round(promotion_discount($eligible, $promo));
    return [$promo, $discount, null];
}

function promotion_applies(array $promo, array $p): bool
{
    return match ($promo['applies_to']) {
        'all'      => true,
        // The product's own category, or its main category (a "Laptops" promotion covers every kind of laptop).
        'category' => $promo['category'] !== null && (
            strcasecmp((string) $p['category'], $promo['category']) === 0
            || (bool) array_filter(product_category_ids($p), function (int $cid) use ($promo): bool {
                $cat = category_find($cid);
                return $cat && (strcasecmp($cat['name'], $promo['category']) === 0
                    || strcasecmp((string) (category_main($cat)['name'] ?? ''), $promo['category']) === 0);
            })
        ),
        'products' => in_array((int) $p['id'], $promo['product_ids'], true),
        default    => false,
    };
}

function promotion_discount(float $price, array $promo): float
{
    $value = (float) $promo['discount_value'];
    $off = $promo['discount_type'] === 'percent' ? $price * min(100, $value) / 100 : $value;
    return min($price, max(0, $off));
}

/** The running promotion that gives this product the biggest discount, if any. */
function best_promotion(array $p): ?array
{
    $best = null;
    $bestOff = 0.0;
    foreach (active_promotions() as $promo) {
        if (!promotion_applies($promo, $p)) {
            continue;
        }
        $off = promotion_discount((float) $p['price'], $promo);
        if ($off > $bestOff) {
            [$best, $bestOff] = [$promo, $off];
        }
    }
    return $best;
}

/**
 * [new price, crossed-out price] after a promotion. During a promotion the crossed-out
 * price is the price before the promotion; otherwise the stored old price is kept.
 */
function apply_promotion(float $price, ?float $oldPrice, ?array $promo): array
{
    if (!$promo) {
        return [$price, $oldPrice];
    }
    $new = round($price - promotion_discount($price, $promo));
    return $new < $price ? [$new, $price] : [$price, $oldPrice];
}

/** Applies the best running promotion to a product row's price and old price. */
function price_product(array $p): array
{
    $promo = best_promotion($p);
    [$price, $old] = apply_promotion((float) $p['price'], $p['old_price'] !== null ? (float) $p['old_price'] : null, $promo);
    $p['regular_price'] = $p['price'];
    $p['price'] = $price;
    $p['old_price'] = $old;
    $p['promotion'] = $promo;
    return $p;
}

/**
 * Discount tag text such as "-22%": from the crossed-out price when there is one,
 * otherwise from the product's sale label (e.g. "22" or "22%"). Null when there is no discount.
 */
function discount_tag(array $p): ?string
{
    if (has_old_price($p)) {
        $pct = (int) round((1 - (float) $p['price'] / (float) $p['old_price']) * 100);
    } else {
        $pct = (int) preg_replace('/\D+/', '', (string) ($p['sale_label'] ?? ''));
    }
    return $pct > 0 && $pct < 100 ? '-' . $pct . '%' : null;
}

/** Whether a card should show the crossed-out price. */
function has_old_price(array $p): bool
{
    return $p['old_price'] !== null && (float) $p['old_price'] > (float) $p['price'];
}

// ---------------------------------------------------------------- cart

/** Cart rows for the current visitor: product columns plus item_id, variant, variant_label, quantity and the current price. */
function cart_items(): array
{
    $stmt = db()->prepare(
        'SELECT p.*, c.id AS item_id, c.variant_id, c.quantity
           FROM cart_items c
           JOIN products p ON p.id = c.product_id
          WHERE c.visitor_id = ? AND p.is_active = 1
          ORDER BY c.id'
    );
    $stmt->execute([visitor_id()]);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $variant = $row['variant_id'] ? find_variant((int) $row['id'], (int) $row['variant_id']) : null;
        if ($row['variant_id'] && !$variant) {
            continue; // the variant was deleted
        }
        if ($variant) {
            $row['price'] = $variant['price'];
            $row['old_price'] = $variant['old_price'];
        }
        $row = price_product($row);
        $row['variant_label'] = variant_label($variant);
        $items[] = $row;
    }
    return $items;
}

/**
 * Adds a product (or one of its variants) to the cart. Products with variants use the
 * default variant when none is given. Like the original store, an item already in the
 * cart is left unchanged.
 */
function cart_add(int $productId, int $quantity = 1, ?int $variantId = null): void
{
    if (!shop_product($productId)) {
        throw new InvalidArgumentException('Product not found');
    }
    $variant = null;
    if (product_variants($productId)) {
        $variant = $variantId ? find_variant($productId, $variantId) : default_variant($productId);
        if (!$variant) {
            throw new InvalidArgumentException('That option is no longer available');
        }
        if ($variant['stock'] !== null && (int) $variant['stock'] < 1) {
            throw new InvalidArgumentException('That option is out of stock');
        }
    }
    $stmt = db()->prepare('INSERT IGNORE INTO cart_items (visitor_id, product_id, variant_id, quantity) VALUES (?, ?, ?, ?)');
    $stmt->execute([visitor_id(), $productId, $variant ? (int) $variant['id'] : 0, max(1, $quantity)]);
    if ($stmt->rowCount()) {
        track_event('add_to_cart', $productId, $variant ? (int) $variant['id'] : null);
    }
}

/** Changes an item's quantity, capped at the variant's stock when stock is tracked. */
function cart_update(int $itemId, int $quantity): void
{
    if ($quantity < 1) {
        return;
    }
    $stmt = db()->prepare(
        'SELECT v.stock FROM cart_items c LEFT JOIN product_variants v ON v.id = c.variant_id
          WHERE c.visitor_id = ? AND c.id = ?'
    );
    $stmt->execute([visitor_id(), $itemId]);
    $stock = $stmt->fetchColumn();
    if ($stock !== false && $stock !== null) {
        $quantity = max(1, min($quantity, (int) $stock));
    }
    $quantity = min($quantity, 99);
    $stmt = db()->prepare('UPDATE cart_items SET quantity = ? WHERE visitor_id = ? AND id = ?');
    $stmt->execute([$quantity, visitor_id(), $itemId]);
}

function cart_remove(int $itemId): void
{
    $stmt = db()->prepare('DELETE FROM cart_items WHERE visitor_id = ? AND id = ?');
    $stmt->execute([visitor_id(), $itemId]);
}

function cart_subtotal(array $items): float
{
    return array_reduce($items, fn ($sum, $p) => $sum + $p['quantity'] * $p['price'], 0.0);
}

/** "productId:variantId" keys of the cart items, so Quick View knows which option is already added. */
function cart_keys(array $items): array
{
    return array_map(fn ($p) => (int) $p['id'] . ':' . (int) $p['variant_id'], $items);
}

/** Everything the front end needs to redraw the cart (and the cart page, when $withPage). */
function cart_state(bool $withPage = false): array
{
    $items = cart_items();
    $subtotal = cart_subtotal($items);
    $extra = $withPage ? ['pageHtml' => render('cart-page', ['items' => $items])] : [];
    return $extra + [
        'ok'       => true,
        'whatsapp' => $items && whatsapp_number() ? whatsapp_url(whatsapp_order_text($items, $subtotal)) : null,
        'delivery' => free_delivery_progress($subtotal),
        'count'    => count($items),
        'ids'      => array_values(array_unique(array_map(fn ($p) => (int) $p['id'], $items))),
        'keys'     => cart_keys($items),
        'subtotal' => money($subtotal),
        'html'     => render('cart-body', ['items' => $items]),
    ];
}

// ---------------------------------------------------------------- wishlist

function wishlist_ids(): array
{
    $stmt = db()->prepare('SELECT product_id FROM wishlist_items WHERE visitor_id = ? ORDER BY created_at');
    $stmt->execute([visitor_id()]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** The visitor's wishlisted products (visible ones only), most recently added first. */
function wishlist_products(): array
{
    $stmt = db()->prepare(
        'SELECT p.* FROM wishlist_items w JOIN products p ON p.id = w.product_id
          WHERE w.visitor_id = ? AND p.is_active = 1
          ORDER BY w.created_at DESC'
    );
    $stmt->execute([visitor_id()]);
    $products = array_map('price_product', $stmt->fetchAll());
    foreach ($products as $p) {
        register_product($p);
    }
    return $products;
}

/** Adds the product to the wishlist, or removes it if it is already there. */
function wishlist_toggle(int $productId): void
{
    if (in_array($productId, wishlist_ids(), true)) {
        $stmt = db()->prepare('DELETE FROM wishlist_items WHERE visitor_id = ? AND product_id = ?');
        $stmt->execute([visitor_id(), $productId]);
        return;
    }
    if (!shop_product($productId)) {
        throw new InvalidArgumentException('Product not found');
    }
    $stmt = db()->prepare('INSERT IGNORE INTO wishlist_items (visitor_id, product_id) VALUES (?, ?)');
    $stmt->execute([visitor_id(), $productId]);
}

// ---------------------------------------------------------------- page state

/** Cart and wishlist ids for the current visitor, loaded once per request. */
function page_state(): array
{
    static $state = null;
    if ($state === null) {
        $items = cart_items();
        $state = [
            'cart'     => $items,
            'cartIds'  => array_values(array_unique(array_map(fn ($p) => (int) $p['id'], $items))),
            'cartKeys' => cart_keys($items),
            'wishlist' => wishlist_ids(),
        ];
    }
    return $state;
}

function in_cart(int $id): bool
{
    return in_array($id, page_state()['cartIds'], true);
}

function in_wishlist(int $id): bool
{
    return in_array($id, page_state()['wishlist'], true);
}
