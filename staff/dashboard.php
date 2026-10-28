<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('staff');
$stats = getDashboardStats($pdo);
$stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_amount),0) FROM sales WHERE staff_id=? AND status='completed' AND DATE(created_at)=CURDATE()");
$stmt->execute([$_SESSION['user_id']]);
[$transactions, $total] = $stmt->fetch(PDO::FETCH_NUM);
$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Staff Dashboard</h1>
<p class="muted">Welcome, <?= e(currentUserName()) ?>.</p>
<div class="grid stats">
    <div class="stat"><span>My Sales Today</span><strong><?= money($total) ?></strong></div>
    <div class="stat"><span>My Transactions Today</span><strong><?= (int) $transactions ?></strong></div>
    <div class="stat"><span>Active Products</span><strong><?= (int) $stats['products'] ?></strong></div>
</div>
<section class="panel">
    <h2>Ready to serve the next customer?</h2>
    <p>Use the cashier page to select products, calculate the total, save the sale, and print a receipt. This system
        does not process payments.</p><a class="btn primary" href="<?= e(url('staff/pos.php')) ?>">Open Cashier /
        POS</a>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>