<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('owner');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) ($_POST['product_id'] ?? 0);
    $qty = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    $note = trim((string) ($_POST['note'] ?? 'Stock received'));
    if ($id < 1 || $qty === false || $qty < 1) {
        $error = 'Select a product and enter a stock quantity greater than zero.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id=? AND is_active=1 FOR UPDATE");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            if (!$product)
                throw new RuntimeException('Product not found.');
            $before = (int) $product['stock_quantity'];
            $after = $before + $qty;
            $pdo->prepare("UPDATE products SET stock_quantity=? WHERE id=?")->execute([$after, $id]);
            $pdo->prepare("INSERT INTO inventory_history (product_id,user_id,movement_type,quantity,before_quantity,after_quantity,note) VALUES (?,?,'stock_in',?,?,?,?)")
                ->execute([$id, $_SESSION['user_id'], $qty, $before, $after, $note]);
            $pdo->commit();
            flash('Stock quantity updated.');
            header('Location: ' . url('owner/inventory.php'));
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            $error = 'Unable to update stock. Check that the product exists.';
        }
    }
}
$products = $pdo->query("SELECT * FROM products WHERE is_active=1 ORDER BY name")->fetchAll();
$history = $pdo->query("SELECT h.*,p.name AS product_name,u.full_name FROM inventory_history h JOIN products p ON p.id=h.product_id LEFT JOIN users u ON u.id=h.user_id ORDER BY h.created_at DESC LIMIT 30")->fetchAll();
$pageTitle = 'Inventory';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Inventory</h1>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div><?php endif; ?>
<section class="panel">
    <h2>Record Stock Received</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Product<select name="product_id" required>
                <option value="">Choose product</option><?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> (current: <?= (int) $p['stock_quantity'] ?>)
                    </option><?php endforeach; ?>
            </select></label>
        <label>Quantity received<input type="number" name="quantity" min="1" required></label><label>Note<input
                name="note" value="Stock received"></label>
        <div class="form-actions"><button class="btn primary">Update Stock</button></div>
    </form>
</section>
<section class="panel">
    <h2>Current Stock</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Available</th>
                    <th>Low-stock threshold</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= e($p['name']) ?></td>
                        <td><?= (int) $p['stock_quantity'] ?></td>
                        <td><?= (int) $p['low_stock_threshold'] ?></td>
                        <td><?= (int) $p['stock_quantity'] <= (int) $p['low_stock_threshold'] ? '<span class="pill danger">Low stock</span>' : '<span class="pill success">OK</span>' ?>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="panel">
    <h2>Recent Inventory History</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Movement</th>
                    <th>Quantity</th>
                    <th>Before</th>
                    <th>After</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= e($h['created_at']) ?></td>
                        <td><?= e($h['product_name']) ?></td>
                        <td><?= e($h['movement_type']) ?></td>
                        <td><?= (int) $h['quantity'] ?></td>
                        <td><?= (int) $h['before_quantity'] ?></td>
                        <td><?= (int) $h['after_quantity'] ?></td>
                        <td><?= e($h['full_name'] ?? 'System') ?></td>
                    </tr><?php endforeach; ?>
                <?php if (!$history): ?>
                    <tr>
                        <td colspan="7">No inventory history yet.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>