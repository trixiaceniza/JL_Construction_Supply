<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('staff');
$stmt = $pdo->prepare("SELECT * FROM sales WHERE staff_id=? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$_SESSION['user_id']]);
$sales = $stmt->fetchAll();
$pageTitle = 'My Sales';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>My Sales History</h1>
<section class="panel">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $s): ?>
                    <tr>
                        <td><?= e($s['transaction_no']) ?></td>
                        <td><?= e($s['created_at']) ?></td>
                        <td><?= money($s['total_amount']) ?></td>
                        <td><a class="btn small-btn" target="_blank"
                                href="<?= e(url('receipts/receipt.php?id=' . (int) $s['id'])) ?>">View / Print</a></td>
                    </tr><?php endforeach; ?>
                <?php if (!$sales): ?>
                    <tr>
                        <td colspan="4">You have no sales yet.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>