<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('owner');

$periods = ['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'];
$period = (string) ($_GET['period'] ?? 'month');
if (!isset($periods[$period])) $period = 'month';
$dashboardError = '';
$rangeStart = new DateTimeImmutable('today');
$rangeEnd = $rangeStart->modify('+1 day');
$bucketFormat = '%Y-%m-%d %H:00:00';
$bucketStep = 'hour';

switch ($period) {
    case 'week':
        $rangeStart = new DateTimeImmutable('monday this week');
        $rangeEnd = $rangeStart->modify('+7 days');
        $bucketFormat = '%Y-%m-%d';
        $bucketStep = 'day';
        break;
    case 'month':
        $rangeStart = new DateTimeImmutable('first day of this month');
        $rangeEnd = $rangeStart->modify('first day of next month');
        $bucketFormat = '%Y-%m-%d';
        $bucketStep = 'day';
        break;
    case 'year':
        $rangeStart = new DateTimeImmutable(date('Y-01-01'));
        $rangeEnd = $rangeStart->modify('+1 year');
        $bucketFormat = '%Y-%m';
        $bucketStep = 'month';
        break;
}
$startSql = $rangeStart->format('Y-m-d H:i:s');
$endSql = $rangeEnd->format('Y-m-d H:i:s');
$bucketData = [];
$cursor = $rangeStart;
while ($cursor < $rangeEnd) {
    $key = $bucketStep === 'hour' ? $cursor->format('Y-m-d H:00:00') : ($bucketStep === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d'));
    $label = $bucketStep === 'hour' ? $cursor->format('ga') : ($bucketStep === 'month' ? $cursor->format('M') : ($period === 'week' ? $cursor->format('D') : $cursor->format('j')));
    $bucketData[$key] = ['label' => $label, 'revenue' => 0.0];
    $cursor = $cursor->modify($bucketStep === 'hour' ? '+1 hour' : ($bucketStep === 'month' ? '+1 month' : '+1 day'));
}
$summary = ['revenue' => 0.0, 'transactions' => 0, 'products' => 0, 'low' => 0, 'out' => 0, 'profit' => 0.0, 'cogs' => 0.0, 'costed_revenue' => 0.0, 'costed_transactions' => 0, 'uncosted_transactions' => 0];
$bestProducts = $inventory = $categorySales = $staffSales = [];

try {
    ensureProductUnitColumns($pdo);
    ensureDashboardCostColumns($pdo);

    $trend = $pdo->prepare("SELECT DATE_FORMAT(created_at, ?) AS bucket, SUM(total_amount) AS revenue FROM sales WHERE status='completed' AND created_at >= ? AND created_at < ? GROUP BY bucket ORDER BY bucket");
    $trend->execute([$bucketFormat, $startSql, $endSql]);
    foreach ($trend->fetchAll() as $row) {
        if (isset($bucketData[$row['bucket']])) $bucketData[$row['bucket']]['revenue'] = (float) $row['revenue'];
    }

    $sum = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) AS revenue, COUNT(*) AS transactions FROM sales WHERE status='completed' AND created_at >= ? AND created_at < ?");
    $sum->execute([$startSql, $endSql]);
    $rangeTotals = $sum->fetch();
    $summary['revenue'] = (float) $rangeTotals['revenue'];
    $summary['transactions'] = (int) $rangeTotals['transactions'];

    $costs = $pdo->prepare("SELECT COALESCE(SUM(costed.revenue),0) AS revenue, COALESCE(SUM(costed.cogs),0) AS cogs, COALESCE(SUM(costed.gross_profit),0) AS gross_profit, COUNT(*) AS transactions FROM (SELECT s.id, SUM(si.line_total) AS revenue, SUM(si.quantity * si.unit_cost) AS cogs, SUM(si.line_total - si.quantity * si.unit_cost) AS gross_profit FROM sales s JOIN sale_items si ON si.sale_id=s.id WHERE s.status='completed' AND s.created_at >= ? AND s.created_at < ? GROUP BY s.id HAVING SUM(CASE WHEN si.unit_cost IS NULL THEN 1 ELSE 0 END)=0) AS costed");
    $costs->execute([$startSql, $endSql]);
    $costTotals = $costs->fetch();
    $summary['costed_revenue'] = (float) $costTotals['revenue'];
    $summary['cogs'] = (float) $costTotals['cogs'];
    $summary['profit'] = (float) $costTotals['gross_profit'];
    $summary['costed_transactions'] = (int) $costTotals['transactions'];
    $summary['uncosted_transactions'] = max(0, $summary['transactions'] - $summary['costed_transactions']);

    $productStats = $pdo->query("SELECT COUNT(*) AS products, SUM(CASE WHEN stock_quantity=0 THEN 1 ELSE 0 END) AS out_count, SUM(CASE WHEN stock_quantity>0 AND stock_quantity<=low_stock_threshold THEN 1 ELSE 0 END) AS low_count FROM products WHERE is_active=1")->fetch();
    $summary['products'] = (int) $productStats['products'];
    $summary['low'] = (int) $productStats['low_count'];
    $summary['out'] = (int) $productStats['out_count'];

    $best = $pdo->prepare("SELECT si.product_name AS name, SUM(si.quantity) AS quantity_sold, SUM(si.line_total) AS revenue FROM sale_items si JOIN sales s ON s.id=si.sale_id WHERE s.status='completed' AND s.created_at >= ? AND s.created_at < ? GROUP BY si.product_id, si.product_name ORDER BY quantity_sold DESC, revenue DESC LIMIT 10");
    $best->execute([$startSql, $endSql]);
    $bestProducts = $best->fetchAll();

    $inventory = $pdo->query("SELECT SUM(CASE WHEN stock_quantity>low_stock_threshold THEN 1 ELSE 0 END) AS in_stock, SUM(CASE WHEN stock_quantity>0 AND stock_quantity<=low_stock_threshold THEN 1 ELSE 0 END) AS low_stock, SUM(CASE WHEN stock_quantity=0 THEN 1 ELSE 0 END) AS out_of_stock FROM products WHERE is_active=1")->fetch();

    $category = $pdo->prepare("SELECT COALESCE(NULLIF(p.category,''),'Uncategorized') AS name, SUM(si.line_total) AS revenue FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id WHERE s.status='completed' AND s.created_at >= ? AND s.created_at < ? GROUP BY p.category ORDER BY revenue DESC");
    $category->execute([$startSql, $endSql]);
    $categorySales = $category->fetchAll();

    $staff = $pdo->prepare("SELECT u.id, u.full_name AS name, COUNT(s.id) AS transactions, COALESCE(SUM(s.total_amount),0) AS revenue FROM users u LEFT JOIN sales s ON s.staff_id=u.id AND s.status='completed' AND s.created_at >= ? AND s.created_at < ? WHERE u.role='staff' GROUP BY u.id, u.full_name HAVING COUNT(s.id)>0 ORDER BY revenue DESC");
    $staff->execute([$startSql, $endSql]);
    $staffSales = $staff->fetchAll();
} catch (Throwable $exception) {
    error_log('Owner dashboard data error: ' . $exception->getMessage());
    $dashboardError = 'Dashboard data is temporarily unavailable. Check the database connection and try again.';
    $summary = ['revenue' => 0.0, 'transactions' => 0, 'products' => 0, 'low' => 0, 'out' => 0, 'profit' => 0.0, 'cogs' => 0.0, 'costed_revenue' => 0.0, 'costed_transactions' => 0, 'uncosted_transactions' => 0];
    $bestProducts = $inventory = $categorySales = $staffSales = [];
    foreach ($bucketData as &$bucket) $bucket['revenue'] = 0.0;
    unset($bucket);
}

$labels = array_column($bucketData, 'label');
$revenues = array_map(static fn($item) => round($item['revenue'], 2), array_values($bucketData));
$chartData = [
    'period' => $period,
    'labels' => $labels,
    'salesTrend' => $revenues,
    'financials' => ['labels' => ['Revenue', 'Cost of Goods Sold', 'Gross Profit'], 'values' => [round($summary['costed_revenue'], 2), round($summary['cogs'], 2), round($summary['profit'], 2)]],
    'bestProducts' => ['labels' => array_column($bestProducts, 'name'), 'quantities' => array_map('intval', array_column($bestProducts, 'quantity_sold')), 'revenue' => array_map('floatval', array_column($bestProducts, 'revenue'))],
    'inventory' => ['labels' => ['In Stock', 'Low Stock', 'Out of Stock'], 'values' => [(int) ($inventory['in_stock'] ?? 0), (int) ($inventory['low_stock'] ?? 0), (int) ($inventory['out_of_stock'] ?? 0)]],
    'categories' => ['labels' => array_column($categorySales, 'name'), 'values' => array_map('floatval', array_column($categorySales, 'revenue'))],
    'staff' => ['labels' => array_column($staffSales, 'name'), 'values' => array_map('floatval', array_column($staffSales, 'revenue')), 'transactions' => array_map('intval', array_column($staffSales, 'transactions'))],
];
$chartJson = json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
$grossProfitLabel = $summary['uncosted_transactions'] > 0 ? 'Gross Profit*' : 'Gross Profit';
$pageTitle = 'Owner Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="dashboard-heading">
    <div class="dashboard-heading-copy">
        <h1>Owner Dashboard</h1>
        <p class="muted">Sales overview · <?= e($rangeStart->format('M j, Y')) ?>–<?= e($rangeEnd->modify('-1 day')->format('M j, Y')) ?></p>
    </div>
    <form method="get" class="dashboard-filter" id="dashboardFilter">
        <label for="period">Sales period</label>
        <select id="period" name="period" onchange="this.form.submit()" aria-label="Filter dashboard sales period">
            <?php foreach ($periods as $value => $label): ?><option value="<?= e($value) ?>" <?= $period === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select>
    </form>
</div>
<?php if ($dashboardError): ?><div class="alert error"><?= e($dashboardError) ?></div><?php endif; ?>
<div class="grid dashboard-summary">
    <a class="stat dashboard-stat-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="View sales reports"><span>Total Sales</span><strong><?= money($summary['revenue']) ?></strong></a>
    <a class="stat dashboard-stat-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="View gross profit reports"><span><?= e($grossProfitLabel) ?></span><strong><?= $summary['costed_transactions'] ? money($summary['profit']) : '—' ?></strong><small><?= $summary['costed_transactions'] ? (int) $summary['costed_transactions'] . ' costed transactions' : 'Add product costs to track profit' ?></small></a>
    <a class="stat dashboard-stat-link" href="<?= e(url('owner/sales.php')) ?>" aria-label="View sales transactions"><span>Total Transactions</span><strong><?= (int) $summary['transactions'] ?></strong></a>
    <a class="stat dashboard-stat-link" href="<?= e(url('owner/products.php')) ?>" aria-label="Manage active products"><span>Total Products</span><strong><?= (int) $summary['products'] ?></strong><small>Active products</small></a>
    <a class="stat dashboard-stat-link" href="<?= e(url('owner/inventory.php')) ?>" aria-label="Review low-stock products"><span>Low-Stock Products</span><strong><?= (int) $summary['low'] ?></strong><small>Above zero, at/below reorder level</small></a>
    <a class="stat dashboard-stat-link" href="<?= e(url('owner/inventory.php')) ?>" aria-label="Review out-of-stock products"><span>Out-of-Stock Products</span><strong><?= (int) $summary['out'] ?></strong><small>Active products with zero stock</small></a>
</div>
<div class="dashboard-chart-grid">
    <a class="panel dashboard-chart-panel dashboard-chart-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="Open sales reports"><h2>Sales Overview</h2><div class="chart-wrap"><canvas id="salesTrendChart" aria-label="Sales revenue trend"></canvas><div class="chart-state is-loading">Loading chart…</div></div></a>
    <a class="panel dashboard-chart-panel dashboard-chart-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="Open revenue and profit reports"><h2>Revenue, COGS &amp; Profit</h2><div class="chart-wrap"><canvas id="financialChart" aria-label="Revenue, cost of goods sold and gross profit"></canvas><div class="chart-state is-loading">Loading chart…</div></div><?php if ($summary['uncosted_transactions'] > 0): ?><p class="chart-note"><?= (int) $summary['uncosted_transactions'] ?> transaction(s) lack saved costs and are excluded from this comparison.</p><?php endif; ?></a>
    <a class="panel dashboard-chart-panel dashboard-chart-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="Open best-selling product reports"><h2>Best-Selling Products · Top 10</h2><div class="chart-wrap"><canvas id="bestProductsChart" aria-label="Top selling products"></canvas><div class="chart-state is-loading">Loading chart…</div></div></a>
    <a class="panel dashboard-chart-panel dashboard-chart-link" href="<?= e(url('owner/inventory.php')) ?>" aria-label="Open inventory management"><h2>Inventory Overview</h2><div class="chart-wrap chart-doughnut"><canvas id="inventoryChart" aria-label="Inventory status distribution"></canvas><div class="chart-state is-loading">Loading chart…</div></div></a>
    <a class="panel dashboard-chart-panel dashboard-chart-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="Open category sales reports"><h2>Sales by Category</h2><div class="chart-wrap chart-doughnut"><canvas id="categoryChart" aria-label="Sales revenue by product category"></canvas><div class="chart-state is-loading">Loading chart…</div></div></a>
    <a class="panel dashboard-chart-panel dashboard-chart-link" href="<?= e(url('owner/reports.php')) ?>" aria-label="Open staff sales reports"><h2>Staff Sales Performance</h2><div class="chart-wrap"><canvas id="staffChart" aria-label="Sales handled by staff member"></canvas><div class="chart-state is-loading">Loading chart…</div></div></a>
</div>
<script type="application/json" id="dashboardChartData"><?= $chartJson ?: '{}' ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="<?= e(url('assets/js/owner-dashboard.js')) ?>" defer></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
