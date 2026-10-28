<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid receipt ID.');
}
$stmt = $pdo->prepare("SELECT s.*,u.full_name FROM sales s JOIN users u ON u.id=s.staff_id WHERE s.id=?");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale || ($_SESSION['role'] !== 'owner' && (int) $sale['staff_id'] !== (int) $_SESSION['user_id'])) {
    http_response_code(404);
    exit('Receipt not found.');
}
$stmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id=? ORDER BY id");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
$autoPrint = isset($_GET['print']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Receipt <?= e($sale['transaction_no']) ?></title>
    <link rel="stylesheet" href="<?= e(url('receipts/receipt.css')) ?>">
</head>

<body>
    <div class="receipt">
        <div class="center">
            <h1>J&amp;L Construction Supply</h1>
            <p>Badiang, Inabanga, Bohol</p>
            <h2>SALES RECEIPT</h2>
        </div>
        <p><strong>Receipt No:</strong> <?= e($sale['transaction_no']) ?><br><strong>Date:</strong>
            <?= e($sale['created_at']) ?><br><strong>Staff:</strong> <?= e($sale['full_name']) ?></p>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= money($item['unit_price']) ?></td>
                        <td><?= money($item['line_total']) ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
        <h3 class="total">TOTAL: <?= money($sale['total_amount']) ?></h3>
        <p class="center">Sales record only. Payment is not processed by this system.</p>
        <p class="center">Thank you for your purchase!</p>
        <div class="no-print center"><button onclick="window.print()">Print Receipt</button> <button
                onclick="window.close()">Close</button></div>
    </div>
    <?php if ($autoPrint): ?>
        <script>window.addEventListener('load', () => window.print());</script><?php endif; ?>
</body>

</html>