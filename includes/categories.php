<?php
declare(strict_types=1);

// Product categories: main categories with subcategories (table product_categories).

/** Every category (visible or not), keyed by id. Loaded once per request. */
function categories_all(bool $refresh = false): array
{
    static $all = null;
    if ($all === null || $refresh) {
        $all = [];
        foreach (db()->query('SELECT * FROM product_categories ORDER BY parent_id IS NOT NULL, position, id') as $row) {
            $row['id'] = (int) $row['id'];
            $row['parent_id'] = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $all[$row['id']] = $row;
        }
    }
    return $all;
}

/** Visible main categories, each with its visible subcategories under 'children'. */
function category_tree(bool $includeHidden = false): array
{
    $mains = [];
    foreach (categories_all() as $c) {
        if ($c['parent_id'] === null && ($includeHidden || $c['is_active'])) {
            $mains[$c['id']] = $c + ['children' => []];
        }
    }
    foreach (categories_all() as $c) {
        if ($c['parent_id'] !== null && isset($mains[$c['parent_id']]) && ($includeHidden || $c['is_active'])) {
            $mains[$c['parent_id']]['children'][] = $c;
        }
    }
    return array_values($mains);
}

function category_by_slug(string $slug): ?array
{
    foreach (categories_all() as $c) {
        if ($c['slug'] === $slug && $c['is_active']) {
            return $c;
        }
    }
    return null;
}

/** On the shop page: the category being browsed when exactly one is chosen (?cat=slug or a single ?cat[]=slug), else null. */
function shop_single_category(): ?array
{
    $raw = $_GET['cat'] ?? '';
    if (is_array($raw)) {
        $raw = count($raw) === 1 ? reset($raw) : '';
    }
    return is_string($raw) && $raw !== '' ? category_by_slug(trim($raw)) : null;
}

function category_find(?int $id): ?array
{
    return $id ? (categories_all()[$id] ?? null) : null;
}

/** The main category of a category (itself when it is a main category). */
function category_main(?array $c): ?array
{
    if (!$c) {
        return null;
    }
    return $c['parent_id'] !== null ? category_find($c['parent_id']) : $c;
}

/**
 * SQL condition (and its parameters) matching products in any of the given category ids: the product's own
 * category or any extra subcategory ticked for it (table product_category_links). $alias is the products table alias.
 */
function products_in_categories_sql(array $ids, string $alias = ''): array
{
    $ids = array_values(array_map('intval', $ids)) ?: [0];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $a = $alias !== '' ? $alias . '.' : '';
    return ["({$a}category_id IN ($in) OR {$a}id IN (SELECT product_id FROM product_category_links WHERE category_id IN ($in)))", [...$ids, ...$ids]];
}

/** All category ids a product is in: its own category plus any extra subcategories. Cached per request. */
function product_category_ids(array $p): array
{
    static $links = null;
    if ($links === null) {
        $links = [];
        foreach (db()->query('SELECT product_id, category_id FROM product_category_links')->fetchAll(PDO::FETCH_NUM) as [$pid, $cid]) {
            $links[(int) $pid][] = (int) $cid;
        }
    }
    $ids = $links[(int) $p['id']] ?? [];
    if (!empty($p['category_id'])) {
        $ids[] = (int) $p['category_id'];
    }
    return array_values(array_unique($ids));
}

/** A category's id plus the ids of its subcategories (for filtering a main category). */
function category_ids_within(array $c): array
{
    $ids = [$c['id']];
    foreach (categories_all() as $x) {
        if ($x['parent_id'] === $c['id']) {
            $ids[] = $x['id'];
        }
    }
    return $ids;
}

/**
 * Products to feature in a category's menu panel: its best sellers, topped up with the shop's
 * best sellers when the category has fewer than $limit products. Promotions are applied.
 */
function category_feature_products(array $c, int $limit = 3): array
{
    [$catSql, $catParams] = products_in_categories_sql(category_ids_within($c));
    $stmt = db()->prepare("SELECT * FROM products WHERE is_active = 1 AND $catSql ORDER BY COALESCE(sold, 0) DESC, id DESC LIMIT $limit");
    $stmt->execute($catParams);
    $rows = $stmt->fetchAll();
    if (count($rows) < $limit) {
        $have = array_map(fn ($r) => (int) $r['id'], $rows) ?: [0];
        $notIn = implode(',', array_fill(0, count($have), '?'));
        $more = db()->prepare("SELECT * FROM products WHERE is_active = 1 AND id NOT IN ($notIn) ORDER BY COALESCE(sold, 0) DESC, id DESC LIMIT " . ($limit - count($rows)));
        $more->execute($have);
        $rows = array_merge($rows, $more->fetchAll());
    }
    return array_map('price_product', $rows);
}

function category_url(array $c): string
{
    return shop_url(['cat' => $c['slug']]);
}

/** URL-friendly version of a name, e.g. "2-in-1 Convertible Laptops" → "2-in-1-convertible-laptops". */
function slugify(string $text): string
{
    $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
    return $slug !== '' ? $slug : 'category';
}
