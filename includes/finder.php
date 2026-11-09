<?php
declare(strict_types=1);

// Product finder (shop page pop-up): pick an item type, brand and specs; results are matched against
// each product's name, brand, specifications, description and variant options.

/** Item types, their brands and spec groups. Keys are category slugs (Admin → Product categories). */
function finder_config(): array
{
    return [
        'laptops' => [
            'label'  => 'Laptop',
            'icon'   => '<rect x="4" y="5" width="16" height="11" rx="1.5"/><path d="M2 19h20"/>',
            'brands' => ['HP', 'Dell', 'Lenovo', 'Apple', 'Acer', 'Asus', 'Microsoft'],
            'specs'  => [
                'ram'     => ['label' => 'RAM', 'options' => ['4GB' => '4GB RAM', '8GB' => '8GB RAM', '16GB' => '16GB RAM', '32GB' => '32GB RAM', '64GB' => '64GB RAM']],
                'storage' => ['label' => 'Storage', 'options' => ['256GB' => '256GB SSD', '512GB' => '512GB SSD', '1TB' => '1TB SSD', '2TB' => '2TB SSD']],
            ],
        ],
        'monitors' => [
            'label'  => 'Monitor',
            'icon'   => '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M9 20h6M12 16v4"/>',
            'brands' => ['HP', 'Dell', 'Lenovo', 'Samsung', 'LG', 'Acer', 'Asus'],
            'specs'  => [
                'size' => ['label' => 'Size', 'options' => ['19' => '19 inch', '22' => '22 inch', '24' => '24 inch', '27' => '27 inch', '32' => '32 inch']],
                'res'  => ['label' => 'Resolution', 'options' => ['hd' => 'HD', 'fhd' => 'Full HD', '2k' => '2K', '4k' => '4K UHD']],
            ],
        ],
    ];
}

/** Text patterns (SQL LIKE) that count as a match for a chosen spec. */
function finder_patterns(string $spec, string $value): array
{
    $n = preg_replace('/\D+/', '', $value);
    return match ($spec) {
        'ram'     => ["%{$n}GB%", "%{$n} GB%"],
        'storage' => str_ends_with($value, 'TB') ? ["%{$n}TB%", "%{$n} TB%"] : ["%{$n}GB%", "%{$n} GB%"],
        'size'    => ["%{$n}-inch%", "%{$n} inch%", "%{$n}\"%", "%{$n}”%", "%{$n}in %", "%{$n}-in%"],
        'res'     => match ($value) {
            'hd'  => ['%720p%', '%1366%768%', '%HD Ready%', '%HD+%'],
            'fhd' => ['%Full HD%', '%FHD%', '%1080p%', '%1920%1080%'],
            '2k'  => ['%2K%', '%QHD%', '%1440p%', '%2560%1440%'],
            '4k'  => ['%4K%', '%UHD%', '%2160p%', '%3840%2160%'],
            default => [],
        },
        default   => [],
    };
}

/**
 * The finder choices in the current request, checked against the configuration. Every group can hold
 * several values: ['item' => slug|null, 'brand' => [..], 'specs' => [key => [..]]].
 */
function finder_selection(?array $cat): array
{
    $config = finder_config();
    $item = $cat && isset($config[$cat['slug']]) ? $cat['slug'] : null;
    $pick = function (string $param, array $allowed): array {
        $raw = $_GET[$param] ?? [];
        $values = array_map(fn ($v) => trim((string) $v), is_array($raw) ? $raw : [$raw]);
        return array_values(array_unique(array_filter($values, fn ($v) => in_array($v, $allowed, true))));
    };
    $brands = $item ? $config[$item]['brands'] : array_merge(...array_column($config, 'brands'));
    $specs = [];
    if ($item) {
        foreach ($config[$item]['specs'] as $key => $group) {
            $values = $pick($key, array_map('strval', array_keys($group['options'])));
            if ($values) {
                $specs[$key] = $values;
            }
        }
    }
    return ['item' => $item, 'brand' => $pick('brand', $brands), 'specs' => $specs];
}

/** The finder choices as link parameters, e.g. ['brand' => ['HP', 'Dell'], 'ram' => ['16GB']]. */
function finder_params(array $sel): array
{
    return array_filter(['brand' => $sel['brand']] + $sel['specs']);
}

/** SQL conditions for the finder choices: any value within a group, every group: [conditions, parameters]. */
function finder_conditions(array $sel, string $table = 'products'): array
{
    $where = [];
    $params = [];
    // $table: the products table name or alias in the calling query (the admin list uses "p").
    $fields = array_map(fn ($col) => "$table.$col", ['title', 'brand', 'specs', 'description']);
    $anyField = function (array $patterns) use ($fields, $table, &$params): string {
        $ors = [];
        foreach ($patterns as $p) {
            foreach ($fields as $f) {
                $ors[] = "$f LIKE ?";
                $params[] = $p;
            }
            $ors[] = "EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = {$table}.id AND v.options LIKE ?)";
            $params[] = $p;
        }
        return '(' . implode(' OR ', $ors) . ')';
    };
    if ($sel['brand']) {
        $where[] = $anyField(array_map(fn ($b) => '%' . $b . '%', $sel['brand']));
    }
    foreach ($sel['specs'] as $key => $values) {
        $patterns = array_merge(...array_map(fn ($v) => finder_patterns($key, (string) $v), $values));
        if ($patterns) {
            $where[] = $anyField($patterns);
        }
    }
    return [$where, $params];
}

/** One entry per chosen value: [param, value, label], e.g. ['ram', '32GB', '32GB RAM']. */
function finder_labels(array $sel): array
{
    $config = finder_config();
    $labels = [];
    foreach ($sel['brand'] as $b) {
        $labels[] = ['brand', $b, $b];
    }
    foreach ($sel['specs'] as $key => $values) {
        foreach ($values as $v) {
            $labels[] = [$key, $v, $config[$sel['item']]['specs'][$key]['options'][$v]];
        }
    }
    return $labels;
}
