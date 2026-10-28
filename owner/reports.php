<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('owner');
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$stmt = $pdo->prepare("SELECT DATE(created_at) AS sale_day, COUNT(*) AS transactions, SUM(total_amount) AS total FROM sales WHERE status='completed' AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY sale_day DESC");
$stmt->execute([$from, $to]);
$days = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT p.name, SUM(si.quantity) AS quantity_sold, SUM(si.line_total) AS revenue FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id WHERE s.status='completed' AND DATE(s.created_at) BETWEEN ? AND ? GROUP BY p.id,p.name ORDER BY quantity_sold DESC LIMIT 10");
$stmt->execute([$from, $to]);
$best = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT u.full_name, COUNT(s.id) AS transactions, COALESCE(SUM(s.total_amount),0) AS total FROM users u LEFT JOIN sales s ON s.staff_id=u.id AND s.status='completed' AND DATE(s.created_at) BETWEEN ? AND ? WHERE u.role='staff' GROUP BY u.id ORDER BY total DESC");
$stmt->execute([$from, $to]);
$staff = $stmt->fetchAll();
$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Sales Reports</h1>
<section class="panel">
    <form class="inline-form" method="get"><label>From<input type="date" name="from"
                value="<?= e($from) ?>"></label><label>To<input type="date" name="to"
                value="<?= e($to) ?>"></label><button class="btn primary">Run Report</button></form>
</section>
<div class="grid two">
    <section class="panel">
        <h2>Daily Sales</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($days as $d): ?>
                        <tr>
                            <td><?= e($d['sale_day']) ?></td>
                            <td><?= (int) $d['transactions'] ?></td>
                            <td><?= money($d['total']) ?></td>
                        </tr><?php endforeach; ?><?php if (!$days): ?>
                        <tr>
                            <td colspan="3">No sales for selected period.</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel">
        <h2>Best-Selling Products</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($best as $p): ?>
                        <tr>
                            <td><?= e($p['name']) ?></td>
                            <td><?= (int) $p['quantity_sold'] ?></td>
                            <td><?= money($p['revenue']) ?></td>
                        </tr><?php endforeach; ?><?php if (!$best): ?>
                        <tr>
                            <td colspan="3">No product sales yet.</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<section class="panel">
    <h2>Staff Performance</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Transactions</th>
                    <th>Total Sales</th>
                </tr>
            </thead>
            <tbody><?php foreach ($staff as $s): ?>
                    <tr>
                        <td><?= e($s['full_name']) ?></td>
                        <td><?= (int) $s['transactions'] ?></td>
                        <td><?= money($s['total']) ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>