<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('owner');
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
$stmt = $pdo->prepare("SELECT s.*,u.full_name FROM sales s JOIN users u ON u.id=s.staff_id WHERE DATE(s.created_at) BETWEEN ? AND ? ORDER BY s.created_at DESC");
$stmt->execute([$from, $to]);
$sales = $stmt->fetchAll();
$pageTitle = 'Sales';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Sales Transactions</h1>
<section class="panel">
    <form class="inline-form" method="get"><label>From<input type="date" name="from" value="<?= e($from) ?>"
                required></label><label>To<input type="date" name="to" value="<?= e($to) ?>" required></label><button
            class="btn primary">Filter</button></form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Staff</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><?= e($s['transaction_no']) ?></td>
                        <td><?= e($s['created_at']) ?></td>
                        <td><?= e($s['full_name']) ?></td>
                        <td><?= money($s['total_amount']) ?></td>
                        <td><?= e($s['status']) ?></td>
                        <td><a class="btn small-btn" href="<?= e(url('receipts/receipt.php?id=' . (int) $s['id'])) ?>"
                                target="_blank">Receipt</a></td>
                    </tr><?php endforeach; ?>
                <?php if (!$sales): ?>
                    <tr>
                        <td colspan="6">No sales in this date range.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>