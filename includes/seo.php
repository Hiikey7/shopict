<?php
declare(strict_types=1);

// Search engine optimisation: page titles and descriptions, keywords, FAQs, image text, structured data (JSON-LD),
// brand and category landing-page text, the internal SEO score and buying guides.
//
// Rule for everything written automatically: it only uses what is stored (the product's name, brand, categories,
// specifications, prices, pictures and the shop's own policies). Nothing is guessed. A field left empty in the
// admin is filled in automatically and stays up to date; a field the admin writes is used as written.

// ---------------------------------------------------------------- site basics

function seo_site_url(): string
{
    return rtrim((string) (config('site_url') ?: 'https://www.shopict.co.ke'), '/');
}

/** Absolute address of a site path or picture ("images/x.webp", "shop.php", already-absolute links unchanged). */
function seo_abs(string $path): string
{
    return preg_match('#^https?://#i', $path) ? $path : seo_site_url() . '/' . ltrim($path, '/');
}

function seo_store(): string
{
    return (string) setting('store_name', 'Shop ICT Gadgets');
}

/** Number of days a purchase can be returned (Terms → Returns & exchanges). */
function seo_return_days(): int
{
    return max(0, (int) setting('seo_return_days', '3'));
}

/** Plain one-line text: no tags, single spaces. */
function seo_plain(?string $s): string
{
    return trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $s)));
}

/** Text cut at a word boundary to at most $max characters. */
function seo_cut(string $s, int $max): string
{
    $s = seo_plain($s);
    if (mb_strlen($s) <= $max) {
        return $s;
    }
    $cut = mb_substr($s, 0, $max - 1);
    $space = mb_strrpos($cut, ' ');
    return rtrim($space !== false && $space > $max * 0.6 ? mb_substr($cut, 0, $space) : $cut, " ,.;:–-") . '…';
}

// ---------------------------------------------------------------- brands

/** Brand name as people write it: "hp" → HP, "DELL" → Dell, "SAMSUNG" → Samsung, "MacBook" unchanged. */
function brand_display(string $name): string
{
    $n = trim($name);
    if ($n === mb_strtolower($n) || $n === mb_strtoupper($n)) {
        return mb_strlen($n) <= 3 ? mb_strtoupper($n) : ucfirst(mb_strtolower($n));
    }
    return $n;
}

/**
 * Brands with a landing page, keyed by slug: the active brands from Admin → Brands, plus any brand typed on a
 * visible product. Each: ['name', 'slug', 'row' => brands table row or null].
 */
function brands_public(): array
{
    static $brands = null;
    if ($brands === null) {
        $brands = [];
        foreach (db()->query('SELECT * FROM brands WHERE is_active = 1 ORDER BY position, name') as $row) {
            $slug = slugify(brand_display($row['name']));
            $brands[$slug] = ['name' => brand_display($row['name']), 'slug' => $slug, 'row' => $row];
        }
        foreach (db()->query("SELECT DISTINCT brand FROM products WHERE is_active = 1 AND brand IS NOT NULL AND brand <> ''")->fetchAll(PDO::FETCH_COLUMN) as $b) {
            $slug = slugify(brand_display($b));
            $brands[$slug] ??= ['name' => brand_display($b), 'slug' => $slug, 'row' => null];
        }
    }
    return $brands;
}

function brand_by_slug(string $slug): ?array
{
    $slug = strtolower(trim($slug));
    // A category with the same address wins, so a brand never hides a category page.
    return category_by_slug($slug) ? null : (brands_public()[$slug] ?? null);
}

/** Address of a brand's landing page, e.g. "hp". */
function brand_url(string $name): string
{
    return shop_url(['make' => slugify(brand_display($name))]);
}

/** SQL condition for a brand's products: the Brand field, or a name starting with the brand ("HP EliteBook …"). */
function brand_products_sql(array $brand, string $alias = ''): array
{
    $a = $alias !== '' ? $alias . '.' : '';
    return ["({$a}brand = ? OR {$a}title LIKE ?)", [$brand['name'], $brand['name'] . ' %']];
}

// ---------------------------------------------------------------- reading a product's facts

/** Spec names that mean the same thing, so "CPU", "Processor" and "Chip" are all found as the processor. */
function seo_spec_aliases(): array
{
    return [
        'model'        => ['model', 'model number', 'model name'],
        'processor'    => ['processor', 'cpu', 'chip', 'chipset', 'processor type'],
        'ram'          => ['ram', 'memory', 'system memory', 'installed ram'],
        'storage'      => ['storage', 'ssd', 'hard drive', 'hdd', 'internal storage', 'rom', 'storage capacity', 'capacity'],
        'graphics'     => ['graphics', 'gpu', 'graphics card', 'video card'],
        'display'      => ['display', 'screen', 'screen size', 'display size', 'monitor size', 'size'],
        'resolution'   => ['resolution', 'native resolution', 'display resolution', 'max resolution'],
        'refresh'      => ['refresh rate'],
        'panel'        => ['panel', 'panel type', 'display type'],
        'response'     => ['response time'],
        'os'           => ['operating system', 'os', 'software'],
        'battery'      => ['battery', 'battery life', 'battery capacity', 'playtime', 'playback time'],
        'charging'     => ['charging', 'fast charging'],
        'camera'       => ['camera', 'rear camera', 'main camera', 'back camera', 'cameras'],
        'front_camera' => ['front camera', 'selfie camera'],
        'network'      => ['network', 'cellular', 'mobile network'],
        'sim'          => ['sim', 'sim slots', 'sim card'],
        'connectivity' => ['connectivity', 'wireless', 'bluetooth', 'connections', 'wi-fi', 'wifi'],
        'ports'        => ['ports', 'inputs', 'connectors', 'interfaces', 'i/o ports', 'i/o'],
        'functions'    => ['functions', 'function', 'printer functions'],
        'print_speed'  => ['print speed'],
        'noise'        => ['noise cancelling', 'noise cancellation', 'anc'],
        'water'        => ['water resistance', 'water resistant', 'ip rating'],
        'condition'    => ['condition'],
        'box'          => ['in the box', 'box contents', 'package contents', 'whats in the box', 'what s in the box', 'package includes'],
        'warranty'     => ['warranty'],
        'weight'       => ['weight'],
        'colour'       => ['colour', 'color'],
    ];
}

/** The value of one kind of spec (see seo_spec_aliases), from the product's specifications, or null. */
function seo_spec(array $p, string $key): ?string
{
    static $cache = [];
    $id = (int) ($p['id'] ?? 0);
    if (!isset($cache[$id]) || $id === 0) {
        $map = [];
        $byLabel = [];
        foreach (product_specs($p) as [$label, $value]) {
            $byLabel[trim((string) preg_replace('/[^a-z0-9\/]+/', ' ', mb_strtolower((string) $label)))] = trim((string) $value);
        }
        foreach (seo_spec_aliases() as $k => $names) {
            foreach ($names as $n) {
                if (isset($byLabel[$n]) && $byLabel[$n] !== '') {
                    $map[$k] = $byLabel[$n];
                    break;
                }
            }
        }
        $cache[$id] = $map;
    }
    return $cache[$id][$key] ?? null;
}

/**
 * The model: the name before the dash and before any comma
 * ("HP EliteBook 840 G8 Laptop – Core i5, …" → "HP EliteBook 840 G8 Laptop", "Galaxy Note 10+, 256GB" → "Galaxy Note 10+").
 */
function seo_model(array $p): string
{
    $model = trim(preg_split('/\s+[–—-]\s+/u', (string) $p['title'], 2)[0]);
    return trim(explode(',', $model, 2)[0]);
}

/** Specs written in the name after the model (after the dash or a comma), as a list. */
function seo_title_specs(array $p): array
{
    $parts = preg_split('/\s+[–—-]\s+/u', (string) $p['title'], 2);
    $head = explode(',', $parts[0]);
    array_shift($head);
    $tail = isset($parts[1]) ? explode(',', $parts[1]) : [];
    return array_values(array_filter(array_map('trim', [...$head, ...$tail])));
}

/** "a" or "an" before a word. */
function seo_a(string $word): string
{
    return (preg_match('/^(?:[aeio]|u(?!ni|s[eu])|hdmi|hd\b|8|11|18|rtx|mp3)/i', $word) ? 'an ' : 'a ') . $word;
}

/** All the text that describes the product (name, specs, description), for spotting facts like "16GB". */
function seo_haystack(array $p): string
{
    $specs = implode(' ', array_map(fn ($s) => $s[0] . ' ' . $s[1], product_specs($p)));
    return $p['title'] . ' ' . $specs;
}

/** The product's main category (Laptops, Monitors, …) or null. */
function seo_main_category(array $p): ?array
{
    $cat = category_find(isset($p['category_id']) ? (int) $p['category_id'] : null);
    if (!$cat) {
        foreach (product_category_ids($p) as $cid) {
            if ($cat = category_find($cid)) {
                break;
            }
        }
    }
    return category_main($cat);
}

/** What kind of item it is: laptop, monitor, phone, tablet, smartwatch, printer, audio, accessory or product. */
function seo_type(array $p): string
{
    $sub = category_find(isset($p['category_id']) ? (int) $p['category_id'] : null);
    if ($sub && in_array($sub['slug'], ['tablets', 'smartwatches'], true)) {
        return $sub['slug'] === 'tablets' ? 'tablet' : 'smartwatch';
    }
    $main = seo_main_category($p);
    $byCat = ['laptops' => 'laptop', 'monitors' => 'monitor', 'smartphones' => 'phone', 'printers' => 'printer', 'audio' => 'audio', 'accessories' => 'accessory'];
    if ($main && isset($byCat[$main['slug']])) {
        return $byCat[$main['slug']];
    }
    $t = mb_strtolower((string) $p['title']);
    return match (true) {
        (bool) preg_match('/\b(laptop|notebook|macbook|elitebook|thinkpad|latitude|probook)\b/', $t) => 'laptop',
        (bool) preg_match('/\bmonitor\b/', $t)                        => 'monitor',
        (bool) preg_match('/\b(iphone|galaxy [asmz]\d|smartphone|phone)\b/', $t) => 'phone',
        (bool) preg_match('/\bprinter\b/', $t)                        => 'printer',
        (bool) preg_match('/\b(earbuds|headphones?|speaker|soundbar|buds)\b/', $t) => 'audio',
        default                                                        => 'product',
    };
}

/** The word for the item ("laptop", "monitor", "wireless earbuds"…), used in sentences and keywords. */
function seo_noun(array $p, bool $plural = false): string
{
    $type = seo_type($p);
    $words = ['laptop' => ['laptop', 'laptops'], 'monitor' => ['monitor', 'monitors'], 'phone' => ['phone', 'phones'],
              'tablet' => ['tablet', 'tablets'], 'smartwatch' => ['smartwatch', 'smartwatches'], 'printer' => ['printer', 'printers']];
    if (isset($words[$type])) {
        return $words[$type][$plural ? 1 : 0];
    }
    $sub = category_find(isset($p['category_id']) ? (int) $p['category_id'] : null);
    if ($sub && $sub['parent_id'] !== null) {
        return mb_strtolower($sub['name']); // "wireless earbuds", "power banks"
    }
    return $plural ? 'products' : 'product';
}

/** Processor in short ("Core i5 11th Gen", "Ryzen 7", "Apple M2"), from the specs or the name. */
function seo_processor(array $p): ?string
{
    $src = seo_spec($p, 'processor') ?? (string) $p['title'];
    if (!preg_match('/\b(Core\s+Ultra\s+\d|Core\s+i[3579]|Core\s+[3579]\b|Ryzen\s+[3579]|Ryzen\s+AI\s+\d|Celeron|Pentium|Athlon|Xeon|Snapdragon\s+[\w+]+|Apple\s+M\d(?:\s+(?:Pro|Max|Ultra))?|\bM[1-4](?:\s+(?:Pro|Max|Ultra))?\b|Dimensity\s+\d+|Helio\s+\w+|Exynos\s+\d+|A1\d\s+Bionic)/i', $src, $m)) {
        return null;
    }
    $cpu = preg_replace('/\s+/', ' ', $m[1]);
    $cpu = preg_replace_callback('/^(core|ryzen|celeron|pentium|athlon|xeon|snapdragon|dimensity|helio|exynos)/i', fn ($x) => ucfirst(strtolower($x[1])), $cpu);
    if (preg_match('/^M\d/', $cpu)) {
        $cpu = 'Apple ' . $cpu;
    }
    if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)\s+Gen\b/i', $src, $g) && str_starts_with($cpu, 'Core i')) {
        $cpu .= ' ' . $g[1] . self_ordinal((int) $g[1]) . ' Gen';
    }
    return $cpu;
}

function self_ordinal(int $n): string
{
    return in_array($n % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
}

/** "16GB" of RAM, from the RAM spec or "16GB RAM" in the name. */
function seo_ram(array $p): ?string
{
    if (($v = seo_spec($p, 'ram')) && preg_match('/(\d{1,3})\s?GB/i', $v, $m)) {
        return $m[1] . 'GB';
    }
    return preg_match('/(\d{1,3})\s?GB\s+(?:DDR\d\s+|LPDDR\d\w*\s+|Unified\s+)?(?:RAM|Memory)/i', (string) $p['title'], $m) ? $m[1] . 'GB' : null;
}

/** "512GB SSD" storage, from the storage spec or the name. */
function seo_storage(array $p): ?string
{
    $src = seo_spec($p, 'storage');
    $re = '/(\d+(?:\.\d+)?)\s?(TB|GB)\s*(?:PCIe\s+|M\.2\s+)?(NVMe\s+SSD|SSD|HDD|eMMC|NVMe|Storage|ROM)?/i';
    if ($src && preg_match($re, $src, $m)) {
        $kind = isset($m[3]) && $m[3] !== '' ? strtoupper(str_ireplace(['nvme ssd', 'nvme'], 'SSD', strtolower($m[3]))) : '';
        return $m[1] . strtoupper($m[2]) . (in_array($kind, ['SSD', 'HDD', 'EMMC'], true) ? ' ' . ($kind === 'EMMC' ? 'eMMC' : $kind) : '');
    }
    if (preg_match('/(\d+(?:\.\d+)?)\s?(TB|GB)\s+(NVMe\s+SSD|SSD|HDD|eMMC)/i', (string) $p['title'], $m)) {
        return $m[1] . strtoupper($m[2]) . ' ' . (stripos($m[3], 'ssd') !== false ? 'SSD' : ($m[3] === 'eMMC' ? 'eMMC' : strtoupper($m[3])));
    }
    if (in_array(seo_type($p), ['phone', 'tablet'], true) && preg_match('/\b(\d{2,4})\s?GB\b/i', (string) $p['title'], $m) && $m[1] . 'GB' !== seo_ram($p)) {
        return $m[1] . 'GB';
    }
    return null;
}

/** Screen size in inches ("14"), from the display spec or the name. */
function seo_screen_inches(array $p): ?string
{
    foreach ([seo_spec($p, 'display'), (string) $p['title']] as $src) {
        if ($src && preg_match('/(\d{1,2}(?:\.\d)?)\s?(?:"|”|″|\'\'|-?\s?inch(?:es)?\b|in\b)/i', $src, $m)) {
            return $m[1];
        }
    }
    return null;
}

/** A dedicated graphics card ("RTX 4060"), or null when the graphics are built in or unknown. */
function seo_dedicated_gpu(array $p): ?string
{
    if (!preg_match('/\b(RTX\s?\d{4}\w*|GTX\s?\d{3,4}\w*|Radeon\s+RX\s?\d{3,4}\w*|Radeon\s+Pro\s+\w+|Quadro\s+\w+|RTX\s+A\d{3,4}|Arc\s+A\d{3})\b/i', (string) (seo_spec($p, 'graphics') ?? '') . ' ' . $p['title'], $m)) {
        return null;
    }
    return (string) preg_replace_callback('/^(rtx|gtx)\s?/i', fn ($x) => strtoupper($x[1]) . ' ', $m[1]); // "rtx4060" → "RTX 4060"
}

/** Resolution as shoppers say it: "4K UHD", "QHD", "Full HD", "HD", or null. */
function seo_resolution(array $p): ?string
{
    $src = (seo_spec($p, 'resolution') ?? '') . ' ' . (seo_spec($p, 'display') ?? '') . ' ' . $p['title'];
    return match (true) {
        (bool) preg_match('/\b(4K|UHD|3840\s?[x×]\s?2160|2160p)\b/i', $src)          => '4K UHD',
        (bool) preg_match('/\b(QHD|WQHD|2K|2560\s?[x×]\s?1440|1440p)\b/i', $src)    => 'QHD',
        (bool) preg_match('/\b(Full\s?HD|FHD|1920\s?[x×]\s?1080|1080p)\b/i', $src)  => 'Full HD',
        (bool) preg_match('/\b(HD\+?|1366\s?[x×]\s?768|720p)\b/i', $src)              => 'HD',
        default                                                                        => null,
    };
}

function seo_refresh(array $p): ?int
{
    $src = (seo_spec($p, 'refresh') ?? '') . ' ' . (seo_spec($p, 'display') ?? '') . ' ' . $p['title'];
    return preg_match('/\b(\d{2,3})\s?Hz\b/i', $src, $m) ? (int) $m[1] : null;
}

/** New or refurbished (Ex-UK, used): from the Condition spec, the name or the category. */
function seo_condition(array $p): string
{
    $src = (seo_spec($p, 'condition') ?? '') . ' ' . $p['title'] . ' ' . ($p['category'] ?? '');
    foreach (product_category_ids($p) as $cid) {
        $src .= ' ' . (category_find($cid)['name'] ?? '');
    }
    if (preg_match('/\b(used|pre-?owned|second-?hand)\b/i', $src)) {
        return 'used';
    }
    return preg_match('/\b(ex-?uk|refurb\w*|renewed)\b/i', $src) ? 'refurbished' : 'new';
}

/** A few facts that set this item apart, in short ("Core i5 11th Gen", "16GB RAM", "512GB SSD", "14-inch"). */
function seo_key_facts(array $p): array
{
    $facts = [];
    switch (seo_type($p)) {
        case 'laptop':
            $facts = [seo_processor($p), ($r = seo_ram($p)) ? $r . ' RAM' : null, seo_storage($p), seo_dedicated_gpu($p), ($s = seo_screen_inches($p)) ? $s . '-inch' : null];
            break;
        case 'monitor':
            $facts = [($s = seo_screen_inches($p)) ? $s . '-inch' : null, seo_resolution($p), ($r = seo_refresh($p)) ? $r . 'Hz' : null, seo_spec($p, 'panel')];
            break;
        case 'phone':
        case 'tablet':
            $facts = [seo_storage($p), ($r = seo_ram($p)) ? $r . ' RAM' : null, ($s = seo_screen_inches($p)) ? $s . '-inch' : null];
            break;
    }
    $facts = array_values(array_filter($facts));
    return $facts ?: array_slice(seo_title_specs($p), 0, 4);
}

// ---------------------------------------------------------------- what the item suits (from its hardware only)

/** Uses the hardware genuinely supports. Demanding uses only appear when the specs are strong enough. */
function seo_use_cases(array $p): array
{
    $type = seo_type($p);
    $text = seo_haystack($p);
    if ($type === 'laptop') {
        $cpu = (string) seo_processor($p);
        $ram = (int) seo_ram($p);
        $gpu = seo_dedicated_gpu($p);
        $high = (bool) preg_match('/Core i[79]|Core Ultra [79]|Core [79]|Ryzen [79]|Apple M\d (Pro|Max)|Xeon/i', $cpu);
        $mid = (bool) preg_match('/Core i5|Core Ultra 5|Core 5|Ryzen 5|Apple M\d/i', $cpu);
        $low = (bool) preg_match('/Celeron|Pentium|Athlon|Core i3|Ryzen 3/i', $cpu);
        $uses = ['web browsing, email and video calls'];
        if ($mid || $high || $low) {
            $uses[] = 'Microsoft Office and schoolwork';
        }
        if (($mid || $high) && $ram >= 8) {
            $uses[] = 'online classes and remote work with many apps open';
        }
        if (($mid || $high) && $ram >= 16) {
            $uses[] = 'programming and accounting software';
        }
        if ($high && $ram >= 16) {
            $uses[] = 'photo editing and heavier multitasking';
        }
        if ($gpu && $ram >= 16) {
            $uses[] = 'gaming, video editing and 3D work (dedicated ' . $gpu . ' graphics)';
        }
        if (preg_match('/fingerprint|TPM|vPro|Sure Start|EliteBook|ThinkPad|Latitude|ProBook/i', $text)) {
            $uses[] = 'business use (security features for company work)';
        }
        return $uses;
    }
    if ($type === 'monitor') {
        $uses = ['office work and home use'];
        $size = (float) seo_screen_inches($p);
        $refresh = seo_refresh($p);
        $res = seo_resolution($p);
        if ($size >= 27 || $res === 'QHD' || $res === '4K UHD') {
            $uses[] = 'multitasking with two windows side by side';
        }
        if ($res === '4K UHD' || $res === 'QHD') {
            $uses[] = 'photo editing and design (sharper detail)';
        }
        if ($refresh !== null && $refresh >= 144) {
            $uses[] = 'gaming (' . $refresh . 'Hz for smoother motion)';
        } elseif ($refresh !== null && $refresh >= 100) {
            $uses[] = 'smoother scrolling and casual gaming (' . $refresh . 'Hz)';
        }
        return $uses;
    }
    return [];
}

// ---------------------------------------------------------------- automatic SEO fields

/** Search-result title: "HP EliteBook 840 G8 Laptop Price in Kenya | Shop ICT Gadgets" (about 60 characters). */
function seo_title_auto(array $p): string
{
    $core = seo_model($p);
    if (!empty($p['brand']) && mb_stripos($core, brand_display((string) $p['brand'])) === false) {
        $core = brand_display((string) $p['brand']) . ' ' . $core;
    }
    foreach (seo_key_facts($p) as $fact) {
        if (mb_stripos($core, $fact) === false && mb_strlen($core . ' ' . $fact . ' Price in Kenya') <= 60) {
            $core .= ' ' . $fact;
        }
    }
    $title = $core . ' Price in Kenya';
    return mb_strlen($title . ' | ' . seo_store()) <= 70 ? $title . ' | ' . seo_store() : $title;
}

/**
 * Search-result description (at most about 158 characters): what it is, key specs, a buying benefit.
 * $price: shown when given (live pages); left out of copies saved in the admin so a stored text never shows an old price.
 */
function seo_meta_auto(array $p, ?float $price = null): string
{
    $model = seo_model($p);
    $start = 'Buy ' . $model . ($price !== null ? ' at ' . money($price) : '') . ' in Kenya.';
    $end = 'Free pickup in Nairobi & countrywide delivery.';
    $facts = seo_title_specs($p) ?: seo_key_facts($p);
    $room = 158 - mb_strlen($start) - mb_strlen($end) - 2;
    $specs = implode(', ', $facts);
    while ($specs !== '' && mb_strlen($specs) + 1 > $room) {
        $cut = mb_strrpos($specs, ',');
        $specs = $cut === false ? '' : trim(mb_substr($specs, 0, $cut));
    }
    if ($specs === '' && ($uses = seo_use_cases($p))) {
        $use = 'Great for ' . $uses[0];
        $specs = mb_strlen($use) + 1 <= $room ? $use : '';
    }
    return $start . ($specs !== '' ? ' ' . $specs . '.' : '') . ' ' . $end;
}

function seo_focus_auto(array $p): string
{
    $model = seo_model($p);
    if (!empty($p['brand']) && mb_stripos($model, brand_display((string) $p['brand'])) === false) {
        $model = brand_display((string) $p['brand']) . ' ' . $model;
    }
    return $model;
}

/** Secondary and long-tail keywords that match the product's real specs. */
function seo_keywords_auto(array $p): array
{
    $type = seo_type($p);
    $noun = seo_noun($p);
    $plural = seo_noun($p, true);
    $brand = !empty($p['brand']) ? brand_display((string) $p['brand']) : null;
    $model = seo_focus_auto($p);
    $kw = [mb_strtolower($model) . ' price in kenya'];
    if ($brand) {
        $kw[] = mb_strtolower($brand . ' ' . $plural) . ' in kenya';
    }
    foreach (product_category_ids($p) as $cid) {
        if (($c = category_find($cid)) && $c['is_active']) {
            $kw[] = mb_strtolower($c['name']) . ' in kenya';
        }
    }
    if ($type === 'laptop') {
        if ($cpu = seo_processor($p)) {
            $kw[] = mb_strtolower(preg_replace('/\s+\d+\w*\s+Gen$/', '', $cpu)) . ' laptop';
        }
        if ($r = seo_ram($p)) {
            $kw[] = strtolower($r) . ' ram laptop';
        }
        if ($s = seo_storage($p)) {
            $kw[] = strtolower($s) . ' laptop';
        }
        if ($g = seo_dedicated_gpu($p)) {
            $kw[] = strtolower($g) . ' laptop';
            $kw[] = 'gaming laptops in kenya';
        }
    } elseif ($type === 'monitor') {
        if ($s = seo_screen_inches($p)) {
            $kw[] = $s . ' inch monitor';
        }
        if ($r = seo_resolution($p)) {
            $kw[] = strtolower($r) . ' monitor';
        }
        if (($hz = seo_refresh($p)) && $hz >= 100) {
            $kw[] = $hz . 'hz monitor';
        }
    } elseif (in_array($type, ['phone', 'tablet'], true) && ($s = seo_storage($p))) {
        $kw[] = strtolower($s) . ' ' . $noun;
    }
    $kw[] = 'buy ' . mb_strtolower($model) . ' in nairobi';
    if (seo_condition($p) === 'refurbished') {
        $kw[] = 'ex-uk ' . $plural . ' in kenya';
    }
    return array_values(array_unique($kw));
}

/** Image description: the admin's line for that picture, else the product name (main) or name + picture number. */
function seo_image_alts(array $p, int $count): array
{
    $saved = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($p['image_alts'] ?? '')))));
    $model = seo_focus_auto($p);
    $alts = [];
    for ($i = 0; $i < $count; $i++) {
        $alts[] = $saved[$i] ?? ($i === 0 ? $model : $model . ' – picture ' . ($i + 1));
    }
    return $alts;
}

/** Questions and answers from the product's real specs and the shop's policies. Each: [question, answer]. */
function seo_faqs_auto(array $p): array
{
    $model = seo_focus_auto($p);
    $type = seo_type($p);
    $faqs = [];
    $spec = fn (string $k) => seo_spec($p, $k);

    if ($type === 'laptop' || $type === 'phone' || $type === 'tablet') {
        if ($v = $spec('processor')) {
            $faqs[] = ["What processor does the $model have?", "The $model has " . seo_a($v) . (stripos($v, 'processor') === false ? ' processor' : '') . '.'];
        }
        $ram = $spec('ram');
        $storage = $spec('storage');
        if ($ram && $storage) {
            $faqs[] = ["How much RAM and storage does the $model have?", "It has $ram RAM and $storage storage."];
        } elseif ($ram) {
            $faqs[] = ["How much RAM does the $model have?", "It has $ram RAM."];
        } elseif ($storage) {
            $faqs[] = ["How much storage does the $model have?", "It has $storage storage."];
        }
    }
    if ($type === 'laptop' && ($v = $spec('graphics'))) {
        $gpu = seo_dedicated_gpu($p);
        $g = $v . (stripos($v, 'graphics') === false ? ' graphics' : '');
        $faqs[] = ["Does the $model have dedicated graphics?", $gpu
            ? "Yes. It has dedicated $g."
            : (preg_match('/\b(UHD|Iris|integrated|Intel Graphics|Radeon Graphics|Adreno|Mali)\b/i', $v)
                ? "No. It uses built-in $g, which are fine for office work, video and light editing."
                : "It has $g.")];
    }
    if (in_array($type, ['laptop', 'monitor', 'phone', 'tablet'], true) && ($v = $spec('display'))) {
        $faqs[] = ["What screen does the $model have?", 'It has ' . seo_a($v) . (stripos($v, 'display') === false && stripos($v, 'screen') === false ? ' display' : '') . (($r = $spec('refresh')) && stripos($v, 'Hz') === false ? " with a $r refresh rate" : '') . '.'];
    }
    if ($type === 'monitor') {
        if ($v = $spec('resolution')) {
            $faqs[] = ["What is the resolution of the $model?", "The resolution is $v."];
        }
        if ($v = $spec('ports')) {
            $faqs[] = ["What ports and connections does the $model have?", "It has $v."];
        }
    } elseif ($v = $spec('ports')) {
        $faqs[] = ["What ports does the $model have?", "It has $v."];
    }
    if ($v = $spec('os')) {
        $faqs[] = ["What operating system does the $model come with?", "It comes with $v."];
    }
    if ($v = $spec('battery')) {
        $faqs[] = ["What battery does the $model have?", "Battery: $v" . (($c = $spec('charging')) ? ". Charging: $c" : '') . '.'];
    }
    if ($v = $spec('camera')) {
        $faqs[] = ["What camera does the $model have?", "The main camera is $v" . (($f = $spec('front_camera')) ? ", and the front camera is $f" : '') . '.'];
    }
    if ($v = $spec('functions')) {
        $faqs[] = ["What can the $model do?", "It can $v."];
    }
    if ($v = $spec('connectivity')) {
        $faqs[] = ["How does the $model connect?", "It supports $v."];
    }
    if ($v = $spec('noise')) {
        $faqs[] = ["Does the $model have noise cancelling?", "$v."];
    }
    if ($v = $spec('box')) {
        $faqs[] = ["What is in the box?", "The box contains: $v."];
    }
    $condition = seo_condition($p);
    if ($condition !== 'new' || $spec('condition')) {
        $faqs[] = ["Is the $model new or refurbished?", $spec('condition')
            ? 'Condition: ' . $spec('condition') . '.'
            : 'It is sold as ' . ($condition === 'used' ? 'a used' : 'a refurbished (Ex-UK)') . ' unit.'];
    }
    $faqs = array_slice($faqs, 0, 6); // room for the warranty and where-to-buy answers
    if (count($faqs) < 2) {
        return []; // without real product facts, a generic Q&A would just repeat on every page
    }
    $faqs[] = ['Does it come with a warranty?', ($w = $spec('warranty'))
        ? "Yes: $w."
        : 'Warranty depends on the product and is given by the manufacturer. Ask our team to confirm the warranty for this unit before you buy.'];
    $addr = trim((string) setting('store_address', ''));
    $days = seo_return_days();
    $faqs[] = ["Where can I buy the $model in Kenya?", 'At ' . seo_store() . ($addr !== '' ? ', ' . $addr : '') . '. Pick it up free at our shop or get countrywide delivery across Kenya. Order online or on WhatsApp.'
        . ($days ? " Returns are accepted within $days days of purchase if the item is in its original condition." : '')];
    return array_slice($faqs, 0, 8);
}

/** FAQs written in the admin ("Q: …" then "A: …" lines) as [question, answer] pairs. */
function seo_faqs_parse(?string $text): array
{
    $faqs = [];
    $q = null;
    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
        $line = trim($line);
        if (preg_match('/^Q\s*[:.)-]\s*(.+)$/iu', $line, $m)) {
            $q = $m[1];
        } elseif ($q !== null && preg_match('/^A\s*[:.)-]\s*(.+)$/iu', $line, $m)) {
            $faqs[] = [$q, $m[1]];
            $q = null;
        } elseif ($line !== '' && $faqs && $q === null) {
            $faqs[count($faqs) - 1][1] .= ' ' . $line; // answer continues on the next line
        }
    }
    return $faqs;
}

function seo_faqs_text(array $faqs): string
{
    return implode("\n\n", array_map(fn ($f) => 'Q: ' . $f[0] . "\nA: " . $f[1], $faqs));
}

/** Suggested product name in the "Brand Model Type – key specs" form, from the brand and specs. */
function seo_name_suggestion(array $p): string
{
    $model = seo_focus_auto($p);
    $noun = seo_noun($p);
    if (in_array(seo_type($p), ['laptop', 'monitor', 'printer'], true) && mb_stripos($model, $noun) === false) {
        $model .= ' ' . ucfirst($noun);
    }
    $facts = seo_title_specs($p);
    if (!$facts) {
        $facts = seo_key_facts($p);
        if (seo_type($p) === 'laptop' && ($os = seo_spec($p, 'os'))) {
            $facts[] = $os;
        }
    }
    return $facts ? $model . ' – ' . implode(', ', array_slice($facts, 0, 5)) : $model;
}

/** A first draft of "About this item" (one point per line) from the specs. The admin reviews it before saving. */
function seo_description_draft(array $p): array
{
    $model = seo_focus_auto($p);
    $noun = seo_noun($p);
    $brand = !empty($p['brand']) ? brand_display((string) $p['brand']) : null;
    $condition = seo_condition($p);
    $facts = seo_key_facts($p);
    $lines = [];
    $lines[] = 'Buy the ' . $model . ' in Kenya from ' . seo_store() . ', Nairobi – '
        . (str_ends_with($noun, 's') ? ($condition === 'new' ? '' : ($condition === 'used' ? 'used ' : 'refurbished (Ex-UK) ')) . $noun : ($condition === 'new' ? seo_a($noun) : ($condition === 'used' ? 'a used ' . $noun : 'a refurbished (Ex-UK) ' . $noun)))
        . ($brand && mb_stripos($model, $brand) === false ? ' from ' . $brand : '')
        . ($facts ? ' with ' . implode(', ', array_slice($facts, 0, 3)) : '') . '.';
    $add = function (string $key, string $label, string $tail = '') use ($p, &$lines) {
        if ($v = seo_spec($p, $key)) {
            $lines[] = $label . ': ' . $v . $tail;
        }
    };
    $add('processor', 'Processor');
    if (($ram = seo_spec($p, 'ram')) || seo_spec($p, 'storage')) {
        $storage = seo_spec($p, 'storage');
        $ssd = $storage && preg_match('/SSD|NVMe/i', $storage);
        $lines[] = trim(($ram ? $ram . ' RAM' : '') . ($ram && $storage ? ' and ' : '') . ($storage ? $storage . ' storage' : ''))
            . ($ssd ? ' – an SSD starts up and opens apps much faster than a hard drive.' : '.');
    }
    $add('display', 'Display');
    $add('resolution', 'Resolution');
    $add('refresh', 'Refresh rate');
    $add('graphics', 'Graphics');
    $add('camera', 'Camera');
    $add('battery', 'Battery');
    $add('ports', 'Ports');
    $add('connectivity', 'Connectivity');
    $add('functions', 'Functions');
    $add('os', 'Operating system');
    if ($uses = seo_use_cases($p)) {
        $lines[] = 'Suited to: ' . implode('; ', $uses) . '.';
    }
    $add('box', 'In the box');
    $lines[] = ($w = seo_spec($p, 'warranty')) ? 'Warranty: ' . $w . '.' : 'Warranty: ask our team to confirm the warranty for this unit.';
    $lines[] = 'Free pickup at ' . (setting('store_address') ?: 'our shop in Nairobi') . ', or countrywide delivery to Nairobi, Mombasa, Kisumu, Nakuru, Eldoret and all of Kenya. Order online or on WhatsApp.';
    return $lines;
}

/**
 * The SEO fields used on the live page: what the admin wrote, else the automatic text.
 * ['title', 'description', 'focus', 'keywords' => [], 'faqs' => [[q, a]], 'auto' => [field => bool]]
 */
function seo_product(array $p, ?float $price = null): array
{
    $title = trim((string) ($p['seo_title'] ?? ''));
    $desc = trim((string) ($p['meta_description'] ?? ''));
    $focus = trim((string) ($p['focus_keyword'] ?? ''));
    $kw = trim((string) ($p['secondary_keywords'] ?? ''));
    $faqs = seo_faqs_parse($p['faqs'] ?? '');
    return [
        'title'       => $title !== '' ? $title : seo_title_auto($p),
        'description' => $desc !== '' ? $desc : seo_meta_auto($p, $price),
        'focus'       => $focus !== '' ? $focus : seo_focus_auto($p),
        'keywords'    => $kw !== '' ? array_values(array_filter(array_map('trim', explode(',', $kw)))) : seo_keywords_auto($p),
        'faqs'        => $faqs ?: seo_faqs_auto($p),
        'auto'        => ['title' => $title === '', 'description' => $desc === '', 'focus' => $focus === '', 'keywords' => $kw === '', 'faqs' => !$faqs],
    ];
}

// ---------------------------------------------------------------- availability and prices

/** Price range and stock of a visible product, with promotions applied: ['low', 'high', 'availability', 'variants']. */
function seo_offer_facts(array $p): array
{
    $variants = register_product($p)[(int) $p['id']]['variants'] ?? [];
    $prices = array_column($variants, 'price');
    $stock = array_column($variants, 'stock');
    $soldOut = $stock && count(array_filter($stock, fn ($s) => $s === null || $s > 0)) === 0;
    return [
        'low'          => $prices ? (float) min($prices) : (float) $p['price'],
        'high'         => $prices ? (float) max($prices) : (float) $p['price'],
        'availability' => !empty($p['is_preorder']) ? 'PreOrder' : ($soldOut ? 'OutOfStock' : 'InStock'),
        'variants'     => $variants,
    ];
}

// ---------------------------------------------------------------- structured data (JSON-LD)

/** The shop as an organisation and electronics store, with address, phone, hours and return policy. */
function seo_store_ld(): array
{
    $site = seo_site_url();
    $ld = [
        '@context' => 'https://schema.org',
        '@type'    => 'ElectronicsStore',
        '@id'      => $site . '/#store',
        'name'     => seo_store(),
        'url'      => $site . '/',
        'logo'     => seo_abs(asset(store_logo())),
        'image'    => seo_abs(asset(store_logo())),
        'currenciesAccepted' => 'KES',
        'paymentAccepted'    => 'Cash',
        'areaServed'         => ['@type' => 'Country', 'name' => 'Kenya'],
    ];
    if ($about = trim((string) setting('footer_about', ''))) {
        $ld['description'] = $about;
    }
    if ($phone = trim((string) setting('store_phone', ''))) {
        $ld['telephone'] = $phone;
    }
    if ($email = trim((string) setting('store_email', ''))) {
        $ld['email'] = $email;
    }
    if ($addr = trim((string) setting('store_address', ''))) {
        $ld['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $addr, 'addressLocality' => 'Nairobi', 'addressCountry' => 'KE'];
    }
    if ($hours = seo_opening_hours((string) setting('store_hours', ''))) {
        $ld['openingHoursSpecification'] = $hours;
    }
    $ld['hasMerchantReturnPolicy'] = seo_return_policy_ld();
    return $ld;
}

/** "Open Mon-Sat 8am-6pm" → opening hours for search engines, or [] when the text can't be read. */
function seo_opening_hours(string $text): array
{
    $days = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
    if (!preg_match('/\b(mon|tue|wed|thu|fri|sat|sun)\w*\s*(?:-|–|to)\s*(mon|tue|wed|thu|fri|sat|sun)\w*\s*,?\s*(\d{1,2})(?::(\d\d))?\s*(am|pm)\s*(?:-|–|to)\s*(\d{1,2})(?::(\d\d))?\s*(am|pm)/i', $text, $m)) {
        return [];
    }
    $keys = array_keys($days);
    $from = array_search(strtolower($m[1]), $keys, true);
    $to = array_search(strtolower($m[2]), $keys, true);
    $range = [];
    for ($i = $from; ; $i = ($i + 1) % 7) {
        $range[] = $days[$keys[$i]];
        if ($i === $to || count($range) > 7) {
            break;
        }
    }
    $time = fn ($h, $min, $ap) => sprintf('%02d:%02d', ((int) $h % 12) + (strtolower($ap) === 'pm' ? 12 : 0), (int) $min);
    return [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $range, 'opens' => $time($m[3], $m[4] ?: 0, $m[5]), 'closes' => $time($m[6], $m[7] ?: 0, $m[8])]];
}

function seo_return_policy_ld(): array
{
    $days = seo_return_days();
    return array_filter([
        '@type'                => 'MerchantReturnPolicy',
        'applicableCountry'    => 'KE',
        'returnPolicyCategory' => 'https://schema.org/' . ($days > 0 ? 'MerchantReturnFiniteReturnWindow' : 'MerchantReturnNotPermitted'),
        'merchantReturnDays'   => $days > 0 ? $days : null,
        'merchantReturnLink'   => seo_abs('terms.php#returns'),
    ], fn ($v) => $v !== null);
}

/** Delivery cost for search engines: only when a fixed delivery fee is set (Admin → Settings), never guessed. */
function seo_shipping_ld(): ?array
{
    $fee = (float) setting('delivery_fee', '0');
    if ($fee <= 0) {
        return null;
    }
    return [
        '@type'               => 'OfferShippingDetails',
        'shippingRate'        => ['@type' => 'MonetaryAmount', 'value' => round($fee), 'currency' => 'KES'],
        'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'KE'],
    ];
}

function seo_website_ld(): array
{
    $site = seo_site_url();
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        '@id'             => $site . '/#website',
        'name'            => seo_store(),
        'url'             => $site . '/',
        'publisher'       => ['@type' => 'Organization', '@id' => $site . '/#store', 'name' => seo_store()],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $site . '/shop.php?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/** Breadcrumbs: [[name, absolute url], …]. */
function seo_breadcrumb_ld(array $crumbs): array
{
    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array_map(fn ($c, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]], $crumbs, array_keys($crumbs)),
    ];
}

/** One offer (price, currency, stock, condition, seller, delivery, returns), matching what the page shows. */
function seo_offer_ld(float $price, ?float $oldPrice, string $availability, string $condition, string $url): array
{
    $offer = [
        '@type'         => 'Offer',
        'url'           => $url,
        'price'         => round($price),
        'priceCurrency' => 'KES',
        'availability'  => 'https://schema.org/' . $availability,
        'itemCondition' => 'https://schema.org/' . ($condition === 'new' ? 'NewCondition' : ($condition === 'used' ? 'UsedCondition' : 'RefurbishedCondition')),
        'seller'        => ['@type' => 'Organization', '@id' => seo_site_url() . '/#store', 'name' => seo_store()],
        'hasMerchantReturnPolicy' => seo_return_policy_ld(),
    ];
    if ($oldPrice !== null && $oldPrice > $price) {
        $offer['priceSpecification'] = [
            ['@type' => 'UnitPriceSpecification', 'price' => round($price), 'priceCurrency' => 'KES'],
            ['@type' => 'UnitPriceSpecification', 'priceType' => 'https://schema.org/StrikethroughPrice', 'price' => round($oldPrice), 'priceCurrency' => 'KES'],
        ];
    }
    if ($ship = seo_shipping_ld()) {
        $offer['shippingDetails'] = $ship;
    }
    return $offer;
}

/**
 * Product data for a product page. A product with options (Core i5 / Core i7) is a ProductGroup whose variants each
 * have their own SKU, price, stock and link (?v=ID opens the page with that option chosen).
 */
function seo_product_ld(array $p, array $images, array $specs, array $bullets, string $url): array
{
    $facts = seo_offer_facts($p);
    $condition = seo_condition($p);
    $brand = !empty($p['brand']) ? ['@type' => 'Brand', 'name' => brand_display((string) $p['brand'])] : null;
    $description = product_description_text($p) ?: seo_meta_auto($p);
    $props = array_map(fn ($s) => ['@type' => 'PropertyValue', 'name' => $s[0], 'value' => $s[1]], array_values(array_filter($specs, fn ($s) => $s[0] !== 'Brand')));
    $alts = seo_image_alts($p, count($images));
    $imageObjects = array_map(fn ($img, $i) => seo_abs(asset($img)), $images, array_keys($images));
    $category = category_find(isset($p['category_id']) ? (int) $p['category_id'] : null);
    $categoryPath = $category ? implode(' > ', array_filter([category_main($category)['id'] !== $category['id'] ? category_main($category)['name'] : null, $category['name']])) : ($p['category'] ?: null);
    $base = array_filter([
        '@context'           => 'https://schema.org',
        'name'               => $p['title'],
        'description'        => mb_substr($description, 0, 5000),
        'image'              => $imageObjects,
        'brand'              => $brand,
        'manufacturer'       => $brand ? ['@type' => 'Organization', 'name' => $brand['name']] : null,
        'category'           => $categoryPath,
        'additionalProperty' => $props ?: null,
    ], fn ($v) => $v !== null && $v !== '');
    $variantsWithOptions = array_values(array_filter($facts['variants'], fn ($v) => !empty($v['options'])));

    if (count($variantsWithOptions) < 2) {
        return ['@type' => 'Product'] + $base + array_filter([
            'sku'    => $p['sku'] ?: null,
            'mpn'    => trim((string) ($p['mpn'] ?? '')) ?: null,
            'gtin'   => trim((string) ($p['gtin'] ?? '')) ?: null,
            'offers' => seo_offer_ld($facts['low'], $p['old_price'] !== null ? (float) $p['old_price'] : null, $facts['availability'], $condition, $url),
        ]);
    }

    // Options Google understands as variant properties; others are listed as properties of each variant.
    $known = ['color' => 'color', 'colour' => 'color', 'size' => 'size', 'material' => 'material', 'pattern' => 'pattern'];
    $varies = [];
    foreach (product_option_names($p) as $name) {
        if (isset($known[mb_strtolower($name)])) {
            $varies[] = 'https://schema.org/' . $known[mb_strtolower($name)];
        }
    }
    $hasVariant = [];
    foreach ($variantsWithOptions as $v) {
        $variant = [
            '@type' => 'Product',
            'name'  => $p['title'] . ' – ' . implode(', ', array_map('strval', array_values($v['options']))),
            'sku'   => $v['sku'] ?: ($p['sku'] . '-' . $v['id']),
            'image' => $imageObjects[0] ?? null,
        ];
        $other = [];
        foreach ($v['options'] as $name => $value) {
            $key = $known[mb_strtolower((string) $name)] ?? null;
            if ($key) {
                $variant[$key] = (string) $value;
            } else {
                $other[] = ['@type' => 'PropertyValue', 'name' => (string) $name, 'value' => (string) $value];
            }
        }
        if ($other) {
            $variant['additionalProperty'] = $other;
        }
        $stock = $v['stock'];
        $availability = !empty($p['is_preorder']) ? 'PreOrder' : ($stock !== null && $stock <= 0 ? 'OutOfStock' : 'InStock');
        $variant['offers'] = seo_offer_ld((float) $v['price'], $v['oldPrice'] !== null ? (float) $v['oldPrice'] : null, $availability, $condition, $url . '?v=' . $v['id']);
        $hasVariant[] = array_filter($variant, fn ($x) => $x !== null);
    }
    return ['@type' => 'ProductGroup'] + $base + array_filter([
        'url'            => $url,
        'productGroupID' => $p['sku'] ?: (string) $p['id'],
        'variesBy'       => $varies ?: null,
        'hasVariant'     => $hasVariant,
    ]);
}

function seo_faq_ld(array $faqs): ?array
{
    if (!$faqs) {
        return null;
    }
    return [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs),
    ];
}

/** A category, brand or "all products" listing page: its name, address, description and the products shown. */
function seo_collection_ld(string $name, string $url, string $description, array $products): array
{
    return [
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $name,
        'url'         => $url,
        'description' => $description,
        'isPartOf'    => ['@id' => seo_site_url() . '/#website'],
        'mainEntity'  => [
            '@type'           => 'ItemList',
            'numberOfItems'   => count($products),
            'itemListElement' => array_map(fn ($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => seo_abs(product_url($p)), 'name' => $p['title']], $products, array_keys($products)),
        ],
    ];
}

// ---------------------------------------------------------------- category and brand pages

/**
 * Buying advice for each main category, shown under the products on its page. Written once by hand and kept
 * general (true for any shop), with no prices or product claims.
 */
function seo_category_guides(): array
{
    return [
        'laptops' => "## How to choose a laptop\n"
            . "- **Processor:** Intel Core i3 / AMD Ryzen 3 handle browsing, email and schoolwork. Core i5 / Ryzen 5 suit office work, online classes and many open apps. Core i7 / Ryzen 7 and above are for programming, design and heavy multitasking.\n"
            . "- **RAM:** 8GB is the minimum we recommend for Windows 11; 16GB is better for multitasking, programming and accounting software.\n"
            . "- **Storage:** choose an SSD. It starts up and opens apps much faster than a hard drive. 256GB fits documents and apps; 512GB or more suits photos, videos and large software.\n"
            . "- **Graphics:** built-in graphics are fine for office use and video. For gaming, video editing and 3D work, look for a dedicated NVIDIA RTX or AMD Radeon graphics card.\n"
            . "- **Screen and weight:** 14-inch laptops are easier to carry; 15.6-inch screens give more room to work. Full HD (1920 × 1080) is sharper than HD.\n"
            . "- **New or Ex-UK:** brand new laptops come in sealed boxes; Ex-UK (refurbished) business laptops cost less and are tested before sale.\n\n"
            . "## Laptop questions\n"
            . "**Which laptop is best for students?** A Core i5 or Ryzen 5 laptop with 8GB RAM and an SSD handles online classes, assignments and research comfortably.\n\n"
            . "**Is an Ex-UK laptop a good buy?** Ex-UK business laptops (HP EliteBook, Dell Latitude, Lenovo ThinkPad) are built for heavy daily use and cost less than new. Check the condition and warranty with our team before you buy.\n\n"
            . "**Can I upgrade a laptop's RAM or storage?** Many laptops allow it, but some have RAM built into the board. Ask us about the exact model before you buy.",
        'monitors' => "## How to choose a monitor\n"
            . "- **Size:** 22–24 inch monitors suit most desks; 27 inch and larger give room for two windows side by side.\n"
            . "- **Resolution:** Full HD (1920 × 1080) is standard for office work. QHD (2560 × 1440) and 4K UHD give sharper text and detail for design and editing.\n"
            . "- **Refresh rate:** 60–75Hz is fine for office use; 100Hz feels smoother; 144Hz and above is for gaming.\n"
            . "- **Panel type:** IPS panels have the most accurate colours and wide viewing angles; VA panels give deeper blacks; TN panels are fast but have narrower angles.\n"
            . "- **Ports:** check that the monitor matches your computer: HDMI, DisplayPort or USB-C.\n\n"
            . "## Monitor questions\n"
            . "**What monitor size is best for office work?** A 24-inch Full HD monitor suits most office desks. Choose 27 inch if you work with spreadsheets or two windows at once.\n\n"
            . "**Do I need a high refresh rate?** Only for gaming or fast motion. For office work and video, 60–100Hz is enough.",
        'smartphones' => "## How to choose a phone\n"
            . "- **Storage:** 128GB is a good minimum for photos, apps and WhatsApp media; choose 256GB if you record a lot of video.\n"
            . "- **RAM:** 4–6GB handles everyday use; 8GB or more keeps games and many apps running smoothly.\n"
            . "- **Battery:** 5000mAh phones usually last a full day of heavy use.\n"
            . "- **Network:** 5G phones are ready for faster mobile data where it is available in Kenya.\n"
            . "- **New or Ex-UK:** brand new phones come sealed; Ex-UK phones cost less. Ask our team about condition and battery health.\n\n"
            . "## Phone questions\n"
            . "**Do your phones work on Safaricom, Airtel and Telkom?** Ask our team to confirm network support for the exact model before you buy.",
        'audio' => "## How to choose audio gear\n"
            . "- **Earbuds:** look at battery life (with the case), noise cancelling and the Bluetooth version.\n"
            . "- **Headphones:** over-ear headphones are more comfortable for long listening and block more outside sound.\n"
            . "- **Speakers and soundbars:** check the power output, battery (for portable speakers) and connections such as Bluetooth, HDMI ARC or optical.\n"
            . "- **For calls:** a built-in microphone with noise reduction makes calls clearer.",
        'printers' => "## How to choose a printer\n"
            . "- **Inkjet or laser:** inkjet printers suit colour and photo printing at home; laser printers print text faster and cost less per page for offices.\n"
            . "- **All-in-one:** choose a printer that also scans and copies if you handle documents.\n"
            . "- **Connectivity:** Wi-Fi printers print from phones and laptops without cables.\n"
            . "- **Running costs:** check the price and page yield of the ink or toner before you buy.",
        'accessories' => "## Choosing accessories\n"
            . "- **Compatibility:** check that the connector matches your device (USB-A, USB-C, HDMI).\n"
            . "- **Power banks:** capacity is measured in mAh; check the output (fast charging) for your phone or laptop.\n"
            . "- **Storage:** USB 3.0 flash drives and external SSDs copy files much faster than USB 2.0.\n"
            . "- **Laptop bags:** match the bag size to your laptop's screen size.",
    ];
}

/** Short opening text for a category page, from its real products (count, price range, brands). */
function seo_listing_intro(string $name, int $count, ?int $min, ?int $max, array $brands): string
{
    $store = seo_store();
    if ($count === 0) {
        return "$name at $store, Nairobi. New stock is added regularly. Contact us for availability.";
    }
    $text = "Shop " . mb_strtolower($name) . " in Kenya at $store, Laxmi Plaza, Nairobi: $count product" . ($count === 1 ? '' : 's');
    if ($min !== null && $max !== null) {
        $text .= $min === $max ? ', at ' . money($min) : ', priced from ' . money($min) . ' to ' . money($max);
    }
    $text .= '.';
    if ($brands) {
        $text .= ' Brands include ' . implode(', ', array_slice($brands, 0, 6)) . '.';
    }
    return $text . ' Free pickup at our shop or countrywide delivery across Kenya.';
}

/** Brands of the products in a listing (for the opening text and brand links). */
function seo_listing_brands(array $where, array $params): array
{
    $stmt = db()->prepare("SELECT brand, COUNT(*) n FROM products WHERE brand IS NOT NULL AND brand <> '' AND " . implode(' AND ', $where) . ' GROUP BY brand ORDER BY n DESC');
    $stmt->execute($params);
    return array_values(array_unique(array_map(fn ($r) => brand_display($r['brand']), $stmt->fetchAll())));
}

// ---------------------------------------------------------------- simple text formatting (guides and blog)

/**
 * Formats text written in the admin: "## Heading", "### Subheading", "- point", "1. step", **bold**, [link](url),
 * blank line = new paragraph. Everything is escaped first, so no HTML can be injected.
 */
function seo_markdown(string $text, int $topLevel = 2): string
{
    $inline = function (string $s): string {
        $s = e($s);
        $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
        return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
            $url = html_entity_decode($m[2], ENT_QUOTES);
            if (!preg_match('#^(https?://|/|[a-z0-9-]+(\.php)?([?\#].*)?$|[a-z0-9-]+-[pg]\d+)#i', $url) || preg_match('#^javascript:#i', $url)) {
                return $m[1];
            }
            $external = preg_match('#^https?://#i', $url) && !str_starts_with($url, seo_site_url());
            return '<a class="link" href="' . e($url) . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
        }, $s);
    };
    $html = '';
    $list = null;
    $para = [];
    $flush = function () use (&$html, &$list, &$para, $inline) {
        if ($para) {
            $html .= '<p>' . $inline(implode(' ', $para)) . '</p>';
            $para = [];
        }
        if ($list) {
            $html .= '<' . $list[0] . '>' . implode('', array_map(fn ($li) => '<li>' . $inline($li) . '</li>', $list[1])) . '</' . $list[0] . '>';
            $list = null;
        }
    };
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $t = trim($line);
        if ($t === '') {
            $flush();
        } elseif (preg_match('/^(#{2,4})\s+(.+)$/', $t, $m)) {
            $flush();
            $level = min(6, $topLevel + strlen($m[1]) - 2);
            $html .= "<h$level>" . $inline($m[2]) . "</h$level>";
        } elseif (preg_match('/^[-*•]\s+(.+)$/u', $t, $m) || preg_match('/^\d+[.)]\s+(.+)$/', $t, $m)) {
            $tag = preg_match('/^\d/', $t) ? 'ol' : 'ul';
            if ($para) {
                $html .= '<p>' . $inline(implode(' ', $para)) . '</p>';
                $para = [];
            }
            if ($list && $list[0] !== $tag) {
                $flush();
            }
            $list ??= [$tag, []];
            $list[1][] = $m[1];
        } else {
            if ($list) {
                $flush();
            }
            $para[] = $t;
        }
    }
    $flush();
    return $html;
}

// ---------------------------------------------------------------- buying guides (blog)

function post_url(array $post): string
{
    return 'post.php?id=' . (int) $post['id'];
}

/** Published guides, newest first. */
function posts_published(int $limit = 50, ?int $categoryId = null): array
{
    $sql = "SELECT * FROM posts WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())";
    $params = [];
    if ($categoryId) {
        $sql .= ' AND category_id = ?';
        $params[] = $categoryId;
    }
    $stmt = db()->prepare($sql . ' ORDER BY COALESCE(published_at, created_at) DESC LIMIT ' . (int) $limit);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Guides linked to a product's categories (for "Buying guides" links on the product page). */
function posts_for_product(array $p, int $limit = 3): array
{
    $ids = [];
    foreach (product_category_ids($p) as $cid) {
        $ids[] = $cid;
        if ($main = category_main(category_find($cid))) {
            $ids[] = $main['id'];
        }
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM posts WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW()) AND (category_id IN ($in) OR FIND_IN_SET(?, product_ids)) ORDER BY COALESCE(published_at, created_at) DESC LIMIT " . (int) $limit);
    $stmt->execute([...$ids, (string) $p['id']]);
    return $stmt->fetchAll();
}

function seo_article_ld(array $post, string $url): array
{
    return array_filter([
        '@context'      => 'https://schema.org',
        '@type'         => 'Article',
        'headline'      => mb_substr($post['title'], 0, 110),
        'description'   => $post['meta_description'] ?: $post['excerpt'],
        'image'         => $post['image'] ? [seo_abs(asset($post['image']))] : null,
        'datePublished' => date('c', strtotime((string) ($post['published_at'] ?: $post['created_at']))),
        'dateModified'  => date('c', strtotime((string) $post['updated_at'])),
        'author'        => $post['author'] ? ['@type' => 'Person', 'name' => $post['author']] : ['@type' => 'Organization', 'name' => seo_store(), 'url' => seo_site_url() . '/'],
        'publisher'     => ['@type' => 'Organization', '@id' => seo_site_url() . '/#store', 'name' => seo_store(), 'logo' => seo_abs(asset(store_logo()))],
        'mainEntityOfPage' => $url,
    ], fn ($v) => $v !== null && $v !== '');
}

// ---------------------------------------------------------------- internal SEO score (admin only)

/**
 * Checks a product's SEO and gives an internal score from 0 to 100 (a to-do list, not a Google ranking).
 * $dupes: ['title' => [lowercased title => count], 'meta' => [...], 'desc' => [hash => count]] from seo_duplicate_maps().
 * Returns ['score' => int, 'checks' => [[label, ok (bool), weight, tip]]].
 */
function seo_product_score(array $p, array $dupes = [], ?int $imageCount = null): array
{
    $seo = seo_product($p);
    $bullets = array_values(array_filter(array_map(fn ($b) => trim((string) preg_replace('/^[\s•·\-\*]+/u', '', $b)), product_bullets($p))));
    $words = str_word_count(implode(' ', $bullets));
    $specs = product_specs($p);
    $imageCount ??= count(array_unique(array_filter([$p['img_src'], ...product_images((int) $p['id'])])));
    $titleKey = mb_strtolower($seo['title']);
    $metaKey = mb_strtolower($seo['description']);
    $descKey = $bullets ? md5(mb_strtolower(implode(' ', $bullets))) : null;
    $focus = mb_strtolower($seo['focus']);
    $titleLen = mb_strlen($seo['title']);
    $metaLen = mb_strlen($seo['description']);
    $hasBrand = trim((string) $p['brand']) !== '';
    $checks = [
        ['Product name is descriptive (brand, model, type)', mb_strlen((string) $p['title']) >= 20 && mb_strlen((string) $p['title']) <= 150 && (!$hasBrand || mb_stripos((string) $p['title'], brand_display((string) $p['brand'])) !== false || mb_stripos((string) $p['title'], (string) $p['brand']) !== false), 8, 'Use “Brand Model Type – key specs”, 20–150 characters.'],
        ['Brand chosen', $hasBrand, 8, 'Pick the brand so it shows on Google and on the brand page.'],
        ['In a category', !empty($p['category_id']) || product_category_ids($p), 6, 'Tick at least one category.'],
        ['Description has enough detail (5+ points, 60+ words)', count($bullets) >= 5 && $words >= 60, 12, $bullets ? "Now: " . count($bullets) . " points, $words words." : 'Add “About this item” points (try “Write from specs”).'],
        ['Specifications listed (5+)', count($specs) >= 5, 12, 'Now: ' . count($specs) . '. Copy them from the maker’s spec sheet.'],
        ['At least 3 pictures', $imageCount >= 3, 10, "Now: $imageCount."],
        ['Search title 30–70 characters', $titleLen >= 30 && $titleLen <= 70, 6, "Now: $titleLen characters."],
        ['Search title unique', ($dupes['title'][$titleKey] ?? 1) <= 1, 4, 'Another product has the same search title.'],
        ['Search description 110–160 characters', $metaLen >= 110 && $metaLen <= 160, 6, "Now: $metaLen characters."],
        ['Search description unique', ($dupes['meta'][$metaKey] ?? 1) <= 1, 4, 'Another product has the same search description.'],
        ['Description not copied from another product', !$descKey || ($dupes['desc'][$descKey] ?? 1) <= 1, 6, 'Another product has the same “About this item” text.'],
        ['Main keyword in the name and description', $focus !== '' && mb_stripos(mb_strtolower((string) $p['title']), $focus) !== false && mb_stripos(mb_strtolower(product_description_text($p) . ' ' . $seo['description']), $focus) !== false, 8, 'Mention “' . $seo['focus'] . '” in the first description point.'],
        ['Price set', (float) $p['price'] > 0, 4, 'Set a price.'],
        ['Product code (GTIN / MPN) for Google Shopping', trim((string) ($p['gtin'] ?? '')) !== '' || trim((string) ($p['mpn'] ?? '')) !== '', 3, 'Optional: the barcode (GTIN) or maker’s part number (MPN) from the box.'],
        ['Visible and indexable', !empty($p['is_active']) && empty($p['noindex']), 3, empty($p['is_active']) ? 'Hidden from the store.' : 'Set to “Hide from Google”.'],
    ];
    $score = 0;
    foreach ($checks as $c) {
        $score += $c[1] ? $c[2] : 0;
    }
    return ['score' => $score, 'checks' => $checks];
}

/** How many products share each search title, search description and description text. */
function seo_duplicate_maps(?array $products = null): array
{
    $products ??= db()->query('SELECT * FROM products WHERE is_active = 1')->fetchAll();
    $maps = ['title' => [], 'meta' => [], 'desc' => []];
    foreach ($products as $p) {
        $seo = seo_product($p);
        $maps['title'][mb_strtolower($seo['title'])] = ($maps['title'][mb_strtolower($seo['title'])] ?? 0) + 1;
        $maps['meta'][mb_strtolower($seo['description'])] = ($maps['meta'][mb_strtolower($seo['description'])] ?? 0) + 1;
        $bullets = array_filter(array_map(fn ($b) => trim((string) preg_replace('/^[\s•·\-\*]+/u', '', $b)), product_bullets($p)));
        if ($bullets) {
            $k = md5(mb_strtolower(implode(' ', $bullets)));
            $maps['desc'][$k] = ($maps['desc'][$k] ?? 0) + 1;
        }
    }
    return $maps;
}

// ---------------------------------------------------------------- picture file names

/**
 * A picture file name that says what it shows ("hp-elitebook-840-g8-laptop-3f9a1c.webp"), for Google Images.
 * $copy: keep the old file (it may be used elsewhere). Pictures on Cloudinary or outside assets/ keep their name.
 * Returns the new path (relative to assets/), or the old one when nothing was changed.
 */
function seo_image_filename(string $path, string $title, bool $copy = false): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $file = __DIR__ . '/../assets/' . ltrim($path, '/');
    if (!is_file($file)) {
        return $path;
    }
    $base = product_slug($title);
    if (strlen($base) > 60) {
        $base = rtrim(substr($base, 0, (int) (strrpos(substr($base, 0, 61), '-') ?: 60)), '-');
    }
    if (str_starts_with(basename($path), $base . '-')) {
        return $path; // already named after the product
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'webp';
    $newPath = 'images/uploads/' . $base . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    $newFile = __DIR__ . '/../assets/' . $newPath;
    if (!is_dir(dirname($newFile))) {
        @mkdir(dirname($newFile), 0775, true);
    }
    $ok = $copy ? @copy($file, $newFile) : @rename($file, $newFile);
    return $ok ? $newPath : $path;
}
