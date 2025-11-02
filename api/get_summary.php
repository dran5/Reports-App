<?php
// api/get_summary.php
require_once __DIR__ . '/../config.php';
require_login();

$pdo = getPDO();

// helper: build date array (inclusive)
function date_range_array(string $start, string $end, string $format = 'Y-m-d'): array {
    $s = new DateTimeImmutable($start);
    $e = new DateTimeImmutable($end);
    $out = [];
    for ($d = $s; $d <= $e; $d = $d->add(new DateInterval('P1D'))) {
        $out[] = $d->format($format);
    }
    return $out;
}

// --- 1) simple category totals (keep your approach) ---
$stmt = $pdo->query("SELECT category, COALESCE(SUM(amount),0) as total_amount, COALESCE(SUM(visitors),0) as total_visitors FROM reports GROUP BY category");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$summary = ['sales_total' => 0.0, 'marketing_total' => 0.0, 'total_visitors' => 0];

foreach ($rows as $r) {
    $cat = strtolower(trim($r['category'] ?? ''));
    if ($cat === 'sales') {
        $summary['sales_total'] = (float)$r['total_amount'];
    }
    if ($cat === 'marketing') {
        $summary['marketing_total'] = (float)$r['total_amount'];
    }
    $summary['total_visitors'] += (int)$r['total_visitors'];
}

// --- 2) date ranges for trend/delta calculations ---
// last 7 days: inclusive of today
$today = new DateTimeImmutable('today');
$end_last = $today;
$start_last = $end_last->sub(new DateInterval('P6D')); // 7 days total

// previous 7-day period immediately before start_last
$end_prev = $start_last->sub(new DateInterval('P1D'));
$start_prev = $end_prev->sub(new DateInterval('P6D'));

// formatted strings for SQL
$fmt = 'Y-m-d';
$start_last_f = $start_last->format($fmt);
$end_last_f   = $end_last->format($fmt);
$start_prev_f = $start_prev->format($fmt);
$end_prev_f   = $end_prev->format($fmt);

// helper: fetch daily aggregates optionally filtered by category
function fetch_daily_map(PDO $pdo, string $start, string $end, ?string $category = null): array {
    $sql = "
      SELECT DATE(date) as dt,
             COALESCE(SUM(amount),0) as total_amount,
             COALESCE(SUM(visitors),0) as total_visitors
      FROM reports
      WHERE DATE(date) BETWEEN :start AND :end
    ";
    $params = [':start' => $start, ':end' => $end];
    if ($category !== null) {
        $sql .= " AND category = :cat ";
        $params[':cat'] = $category;
    }
    $sql .= " GROUP BY DATE(date) ORDER BY DATE(date) ASC";
    $s = $pdo->prepare($sql);
    $s->execute($params);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);

    $mapAmount = [];
    $mapVis = [];
    foreach ($rows as $r) {
        $mapAmount[$r['dt']] = (float)$r['total_amount'];
        $mapVis[$r['dt']] = (int)$r['total_visitors'];
    }
    return [$mapAmount, $mapVis];
}

// categories used in your UI
$catSales = 'Sales';
$catMarketing = 'Marketing';

// fetch maps
list($salesMapLast, $_) = fetch_daily_map($pdo, $start_last_f, $end_last_f, $catSales);
list($salesMapPrev, $_p) = fetch_daily_map($pdo, $start_prev_f, $end_prev_f, $catSales);

list($marketingMapLast, $_2) = fetch_daily_map($pdo, $start_last_f, $end_last_f, $catMarketing);
list($marketingMapPrev, $_3) = fetch_daily_map($pdo, $start_prev_f, $end_prev_f, $catMarketing);

// visitors (no category filter)
list($_a, $visMapLast) = fetch_daily_map($pdo, $start_last_f, $end_last_f, null);
list($_b, $visMapPrev) = fetch_daily_map($pdo, $start_prev_f, $end_prev_f, null);

// ordered date arrays for last/prev windows
$datesLast = date_range_array($start_last_f, $end_last_f);
$datesPrev = date_range_array($start_prev_f, $end_prev_f);

// build trend arrays (7 values)
$salesTrend = [];
foreach ($datesLast as $d) {
    $salesTrend[] = $salesMapLast[$d] ?? 0.0;
}
$marketingTrend = [];
foreach ($datesLast as $d) {
    $marketingTrend[] = $marketingMapLast[$d] ?? 0.0;
}
$visitorsTrend = [];
foreach ($datesLast as $d) {
    $visitorsTrend[] = $visMapLast[$d] ?? 0;
}

// sums for delta calculation
$sumSalesLast = array_sum($salesTrend);
$sumSalesPrev = 0.0;
foreach ($datesPrev as $d) { $sumSalesPrev += $salesMapPrev[$d] ?? 0.0; }

$sumMarketingLast = array_sum($marketingTrend);
$sumMarketingPrev = 0.0;
foreach ($datesPrev as $d) { $sumMarketingPrev += $marketingMapPrev[$d] ?? 0.0; }

$sumVisLast = array_sum($visitorsTrend);
$sumVisPrev = 0;
foreach ($datesPrev as $d) { $sumVisPrev += $visMapPrev[$d] ?? 0; }

// compute percent delta safely (rounded to 2 decimals)
function percent_delta($prev, $last) {
    if ($prev == 0) {
        if ($last == 0) return 0.0;
        return 100.0; // arbitrary: 100% increase from zero
    }
    return round((($last - $prev) / $prev) * 100, 2);
}

$salesDelta = percent_delta($sumSalesPrev, $sumSalesLast);
$marketingDelta = percent_delta($sumMarketingPrev, $sumMarketingLast);
$visitorsDelta = percent_delta($sumVisPrev, $sumVisLast);

// --- 3) recent report rows (include id) ---
$stmt = $pdo->prepare("SELECT id, DATE_FORMAT(date, '%Y-%m-%d') AS date, category, source, amount, visitors FROM reports ORDER BY date DESC, id DESC LIMIT 200");
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- output ---
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    // keep your simple totals
    'sales_total' => (float)$summary['sales_total'],
    'marketing_total' => (float)$summary['marketing_total'],
    'total_visitors' => (int)$summary['total_visitors'],

    // trends & deltas
    'sales_trend' => $salesTrend,
    'marketing_trend' => $marketingTrend,
    'visitors_trend' => $visitorsTrend,

    'sales_delta' => $salesDelta,
    'marketing_delta' => $marketingDelta,
    'visitors_delta' => $visitorsDelta,

    // rows (each row includes id)
    'reports' => $reports,

    // debugging windows (optional — remove in production)
    'window' => [
        'start_last' => $start_last_f,
        'end_last'   => $end_last_f,
        'start_prev' => $start_prev_f,
        'end_prev'   => $end_prev_f,
    ],
]);
