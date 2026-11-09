<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$ranges = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];
$days = (int) ($_GET['days'] ?? 30);
$days = isset($ranges[$days]) ? $days : 30;

$from = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
$prevFrom = date('Y-m-d 00:00:00', strtotime('-' . (2 * $days - 1) . ' days'));
$pdo = db();

$q = function (string $sql, array $params = []) use ($pdo): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
};

// Headline numbers for this period and the one before it.
$totals = function (string $start, ?string $end) use ($q): array {
    $where = 'created_at >= ?' . ($end ? ' AND created_at < ?' : '');
    $params = $end ? [$start, $end] : [$start];
    $row = $q(
        "SELECT SUM(type = 'page_view') AS views,
                COUNT(DISTINCT CASE WHEN type = 'page_view' THEN visitor_id END) AS visitors,
                SUM(type = 'product_view') AS product_views,
                SUM(type = 'add_to_cart') AS adds,
                COUNT(DISTINCT CASE WHEN type = 'add_to_cart' THEN visitor_id END) AS adders
           FROM analytics_events WHERE $where",
        $params
    )[0];
    return array_map('intval', $row);
};
$now = $totals($from, null);
$prev = $totals($prevFrom, $from);
$rate = fn (array $t) => $t['visitors'] ? $t['adders'] / $t['visitors'] * 100 : 0.0;

$kpis = [
    ['Page views', $now['views'], $prev['views'], number_format($now['views'])],
    ['Unique visitors', $now['visitors'], $prev['visitors'], number_format($now['visitors'])],
    ['Product views', $now['product_views'], $prev['product_views'], number_format($now['product_views'])],
    ['Add-to-carts', $now['adds'], $prev['adds'], number_format($now['adds'])],
    ['Visitors who added to cart', $rate($now), $rate($prev), number_format($rate($now), 1) . '%'],
];

// Daily series, with empty days filled in.
$daily = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $daily[date('Y-m-d', strtotime("-$i days"))] = ['views' => 0, 'visitors' => 0];
}
foreach ($q(
    "SELECT DATE(created_at) AS d, COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
       FROM analytics_events WHERE type = 'page_view' AND created_at >= ?
      GROUP BY DATE(created_at)",
    [$from]
) as $r) {
    if (isset($daily[$r['d']])) {
        $daily[$r['d']] = ['views' => (int) $r['views'], 'visitors' => (int) $r['visitors']];
    }
}

$topPages = $q(
    "SELECT path, COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
       FROM analytics_events WHERE type = 'page_view' AND created_at >= ?
      GROUP BY path ORDER BY views DESC LIMIT 10",
    [$from]
);
$topProducts = $q(
    "SELECT p.id, p.title, p.img_src,
            SUM(e.type = 'product_view') AS views,
            SUM(e.type = 'add_to_cart') AS adds
       FROM analytics_events e JOIN products p ON p.id = e.product_id
      WHERE e.created_at >= ? AND e.type IN ('product_view', 'add_to_cart')
      GROUP BY p.id ORDER BY views DESC, adds DESC LIMIT 10",
    [$from]
);
$topAdded = $q(
    "SELECT p.id, p.title, p.img_src, COUNT(*) AS adds
       FROM analytics_events e JOIN products p ON p.id = e.product_id
      WHERE e.created_at >= ? AND e.type = 'add_to_cart'
      GROUP BY p.id ORDER BY adds DESC LIMIT 5",
    [$from]
);
$referrers = $q(
    "SELECT COALESCE(referrer, '') AS source, COUNT(*) AS views
       FROM analytics_events WHERE type = 'page_view' AND created_at >= ?
      GROUP BY source ORDER BY views DESC LIMIT 8",
    [$from]
);
$devices = $q(
    "SELECT device, COUNT(DISTINCT visitor_id) AS visitors
       FROM analytics_events WHERE type = 'page_view' AND created_at >= ?
      GROUP BY device ORDER BY visitors DESC",
    [$from]
);

/** "+12%" / "−5%" / "new" compared with the previous period. */
$delta = function (float $cur, float $before): array {
    if ($before == 0.0) {
        return $cur > 0 ? ['new', 'delta-up'] : ['—', 'delta-flat'];
    }
    $pct = ($cur - $before) / $before * 100;
    if (abs($pct) < 0.5) {
        return ['0%', 'delta-flat'];
    }
    return [($pct > 0 ? '▲ ' : '▼ ') . number_format(abs($pct), 0) . '%', $pct > 0 ? 'delta-up' : 'delta-down'];
};

/** Rounds a maximum up to a tidy axis value and returns the tick values. */
$ticks = function (int $max): array {
    $max = max(4, $max);
    $raw = $max / 4;
    $mag = 10 ** floor(log10($raw));
    $step = (float) (array_values(array_filter([1, 2, 2.5, 5, 10], fn ($s) => $s * $mag >= $raw))[0] * $mag);
    $step = max(1, (int) ceil($step));
    return range(0, $step * (int) ceil($max / $step), $step);
};

// Chart geometry (SVG user units; the SVG scales to the card width).
$W = 820; $H = 260; $padL = 44; $padR = 16; $padT = 16; $padB = 30;
$plotW = $W - $padL - $padR; $plotH = $H - $padT - $padB;
$yTicks = $ticks(max(array_column($daily, 'views') ?: [0]));
$yMax = end($yTicks);
$n = count($daily);
$x = fn (int $i) => $padL + ($n > 1 ? $i * $plotW / ($n - 1) : $plotW / 2);
$y = fn (float $v) => $padT + $plotH - ($v / $yMax) * $plotH;
$path = function (string $key) use ($daily, $x, $y): string {
    $d = '';
    foreach (array_values($daily) as $i => $row) {
        $d .= ($i ? 'L' : 'M') . round($x($i), 1) . ' ' . round($y($row[$key]), 1);
    }
    return $d;
};
$labelEvery = $days <= 7 ? 1 : ($days <= 30 ? 5 : 15);
$hasData = $now['views'] + $now['product_views'] + $now['adds'] > 0;
$chartData = array_map(fn ($d, $r) => ['label' => date('D, M j', strtotime($d)), 'views' => $r['views'], 'visitors' => $r['visitors']], array_keys($daily), $daily);

/** Readable name for a page path: product pages show the product name, the shop shows its category or search. */
$titles = $pdo->query('SELECT id, title FROM products')->fetchAll(PDO::FETCH_KEY_PAIR);
$pageLabel = function (array $r) use ($titles): string {
    $path = (string) $r['path'];
    parse_str((string) parse_url($path, PHP_URL_QUERY), $query);
    $code = ' <code class="small text-muted">' . e($path) . '</code>';
    if (str_starts_with($path, '/product.php') && isset($titles[(int) ($query['id'] ?? 0)])) {
        return '<a class="link-dark text-decoration-none" href="product-edit.php?id=' . (int) $query['id'] . '">' . e($titles[(int) $query['id']]) . '</a>';
    }
    $names = ['/' => 'Home page', '/cart.php' => 'Shopping cart', '/checkout.php' => 'Checkout', '/order.php' => 'Order confirmation'];
    if (str_starts_with($path, '/shop.php')) {
        $catNode = isset($query['cat']) ? category_by_slug((string) $query['cat']) : null;
        $name = isset($query['q']) ? 'Search: ' . $query['q'] : ($catNode ? 'Category: ' . $catNode['name'] : (isset($query['category']) ? 'Category: ' . $query['category'] : 'Shop'));
        return e($name) . $code;
    }
    return isset($names[$path]) ? e($names[$path]) . $code : '<code>' . e($path) . '</code>';
};

/** Horizontal bar list: label, value, bar width relative to the largest value. */
$bars = function (array $rows, string $labelKey, string $valueKey, ?callable $label = null, ?callable $extra = null): string {
    if (!$rows) {
        return '<p class="text-muted small p-3 mb-0">No data for this period yet.</p>';
    }
    $max = max(array_map(fn ($r) => (int) $r[$valueKey], $rows)) ?: 1;
    $html = '<ol class="bar-list">';
    foreach ($rows as $r) {
        $pct = round((int) $r[$valueKey] / $max * 100, 1);
        $text = $label ? $label($r) : e($r[$labelKey]);
        $html .= '<li><div class="bar-list-row"><span class="bar-list-label">' . $text . '</span>'
            . '<span class="bar-list-value">' . number_format((int) $r[$valueKey]) . ($extra ? $extra($r) : '') . '</span></div>'
            . '<div class="bar-list-track"><span class="bar-list-fill" style="width:' . $pct . '%"></span></div></li>';
    }
    return $html . '</ol>';
};

admin_header('Analytics', 'analytics');
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <div class="btn-group" role="group" aria-label="Date range">
    <?php foreach ($ranges as $d => $label): ?>
    <a class="btn btn-sm <?= $d === $days ? 'btn-dark' : 'btn-outline-secondary' ?>" href="?days=<?= $d ?>" <?= $d === $days ? 'aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <span class="small text-muted ms-auto">Compared with the <?= $days ?> days before. Bots and admin pages are not counted.</span>
</div>

<?php if (!$hasData): ?>
<div class="alert alert-light border small">No visits recorded in this period yet. Visits are counted from now on: open the <a href="../" target="_blank" rel="noopener">store</a> and the numbers appear here.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <?php foreach ($kpis as [$label, $cur, $before, $shown]): [$dText, $dClass] = $delta((float) $cur, (float) $before); ?>
  <div class="col-6 col-md-4 col-xl">
    <div class="card stat-card h-100">
      <div class="card-body">
        <div class="stat-value"><?= e($shown) ?></div>
        <div class="stat-label"><?= e($label) ?></div>
        <div class="stat-delta <?= $dClass ?>" title="Previous <?= $days ?> days"><?= e($dText) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header d-flex flex-wrap gap-3 align-items-center">
    <span>Traffic per day</span>
    <span class="chart-legend ms-auto small">
      <span><i class="legend-line series-1"></i>Page views</span>
      <span><i class="legend-line series-2"></i>Unique visitors</span>
    </span>
  </div>
  <div class="card-body">
    <div class="viz-chart" data-viz-chart>
      <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Page views and unique visitors per day, <?= e(strtolower($ranges[$days])) ?>">
        <?php foreach ($yTicks as $t): ?>
        <line class="viz-grid" x1="<?= $padL ?>" x2="<?= $W - $padR ?>" y1="<?= round($y($t), 1) ?>" y2="<?= round($y($t), 1) ?>"/>
        <text class="viz-axis" x="<?= $padL - 8 ?>" y="<?= round($y($t), 1) + 4 ?>" text-anchor="end"><?= number_format($t) ?></text>
        <?php endforeach; ?>
        <?php foreach (array_keys($daily) as $i => $d): if (($n - 1 - $i) % $labelEvery !== 0) { continue; } ?>
        <text class="viz-axis" x="<?= round($x($i), 1) ?>" y="<?= $H - 8 ?>" text-anchor="<?= $i === 0 ? 'start' : ($i === $n - 1 ? 'end' : 'middle') ?>"><?= e(date($days <= 7 ? 'D j' : 'M j', strtotime($d))) ?></text>
        <?php endforeach; ?>
        <path class="viz-line series-1" d="<?= $path('views') ?>"/>
        <path class="viz-line series-2" d="<?= $path('visitors') ?>"/>
        <line class="viz-crosshair" data-crosshair x1="0" x2="0" y1="<?= $padT ?>" y2="<?= $padT + $plotH ?>" visibility="hidden"/>
        <circle class="viz-dot series-1" data-dot="views" r="4.5" cx="0" cy="0" visibility="hidden"/>
        <circle class="viz-dot series-2" data-dot="visitors" r="4.5" cx="0" cy="0" visibility="hidden"/>
        <rect class="viz-hit" x="<?= $padL ?>" y="<?= $padT ?>" width="<?= $plotW ?>" height="<?= $plotH ?>" tabindex="0" aria-label="Move across the chart to read daily values" data-hit/>
      </svg>
      <div class="viz-tooltip" data-tooltip hidden></div>
      <script type="application/json" data-chart-data><?= json_encode(['points' => $chartData, 'padL' => $padL, 'plotW' => $plotW, 'padT' => $padT, 'plotH' => $plotH, 'yMax' => $yMax, 'W' => $W, 'H' => $H], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </div>
    <details class="mt-2">
      <summary class="small text-muted">Show as a table</summary>
      <div class="table-responsive mt-2" style="max-height:300px">
        <table class="table table-sm mb-0">
          <thead><tr><th>Day</th><th class="text-end">Page views</th><th class="text-end">Unique visitors</th></tr></thead>
          <tbody>
            <?php foreach (array_reverse($daily, true) as $d => $r): ?>
            <tr><td><?= e(date('D, M j', strtotime($d))) ?></td><td class="text-end"><?= number_format($r['views']) ?></td><td class="text-end"><?= number_format($r['visitors']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </details>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">Most viewed pages <span class="small fw-normal text-muted">views · visitors</span></div>
      <div class="card-body"><?= $bars($topPages, 'path', 'views', $pageLabel, fn ($r) => ' <span class="text-muted fw-normal">· ' . number_format((int) $r['visitors']) . '</span>') ?></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between">Most viewed products <span class="small fw-normal text-muted">Quick View opens · added to cart</span></div>
      <div class="card-body"><?= $bars(
          $topProducts, 'title', 'views',
          fn ($r) => thumb($r['img_src'], 24) . ' <a class="link-dark text-decoration-none" href="product-edit.php?id=' . (int) $r['id'] . '">' . e($r['title']) . '</a>',
          fn ($r) => ' <span class="text-muted fw-normal">· ' . number_format((int) $r['adds']) . '</span>'
      ) ?></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">Most added to cart</div>
      <div class="card-body"><?= $bars($topAdded, 'title', 'adds', fn ($r) => '<a class="link-dark text-decoration-none" href="product-edit.php?id=' . (int) $r['id'] . '">' . e($r['title']) . '</a>') ?></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">Where visitors come from</div>
      <div class="card-body"><?= $bars($referrers, 'source', 'views', fn ($r) => $r['source'] === '' ? '<span class="text-muted">Direct / typed address</span>' : e($r['source'])) ?></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">Devices <span class="small fw-normal text-muted">(visitors)</span></div>
      <div class="card-body"><?= $bars($devices, 'device', 'visitors', fn ($r) => e(ucfirst($r['device']))) ?></div>
    </div>
  </div>
</div>
<?php admin_footer(); ?>
