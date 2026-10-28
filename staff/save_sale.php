<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('staff');
ensureDashboardCostColumns($pdo);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('staff/pos.php'));
    exit;
}
verifyCsrf();
$rawItems = $_POST['items'] ?? [];
if (!is_array($rawItems) || !$rawItems) {
    flash('Your cart is empty.', 'error');
    header('Location: ' . url('staff/pos.php'));
    exit;
}
$quantities = [];
foreach ($rawItems as $id => $qty) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $qty = filter_var($qty, FILTER_VALIDATE_INT);
    if (!$id || !$qty || $qty < 1) {
        flash('Invalid cart quantities.', 'error');
        header('Location: ' . url('staff/pos.php'));
        exit;
    }
    $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
}
try {
    $pdo->beginTransaction();
    $items = [];
    $total = 0.0;
    $select = $pdo->prepare("SELECT id,name,price,cost_per_unit,stock_quantity FROM products WHERE id=? AND is_active=1 FOR UPDATE");
    foreach ($quantities as $id => $qty) {
        $select->execute([$id]);
        $p = $select->fetch();
        if (!$p)
            throw new RuntimeException('A selected product is unavailable.');
        if ((int) $p['stock_quantity'] < $qty)
            throw new RuntimeException('Not enough stock for ' . $p['name'] . '. Please revise the cart.');
        $unit = (float) $p['price'];
        $line = round($unit * $qty, 2);
        $total += $line;
        $items[] = ['product' => $p, 'qty' => $qty, 'unit' => $unit, 'cost' => $p['cost_per_unit'], 'line' => $line, 'before' => (int) $p['stock_quantity']];
    }
    $transactionNo = 'JL-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $stmt = $pdo->prepare("INSERT INTO sales (transaction_no,staff_id,total_amount,status) VALUES (?,?,?,'completed')");
    $stmt->execute([$transactionNo, $_SESSION['user_id'], round($total, 2)]);
    $saleId = (int) $pdo->lastInsertId();
    $itemInsert = $pdo->prepare("INSERT INTO sale_items (sale_id,product_id,product_name,quantity,unit_price,unit_cost,line_total) VALUES (?,?,?,?,?,?,?)");
    $stockUpdate = $pdo->prepare("UPDATE products SET stock_quantity=stock_quantity-? WHERE id=?");
    foreach ($items as $item) {
        $p = $item['product'];
        $qty = $item['qty'];
        $after = $item['before'] - $qty;
        $itemInsert->execute([$saleId, $p['id'], $p['name'], $qty, $item['unit'], $item['cost'], $item['line']]);
        $stockUpdate->execute([$qty, $p['id']]);
        $hist = $pdo->prepare("INSERT INTO inventory_history (product_id,user_id,movement_type,quantity,before_quantity,after_quantity,note) VALUES (?,?,'sale',?,?,?,?)");
        $hist->execute([$p['id'], $_SESSION['user_id'], $qty, $item['before'], $after, 'Sale ' . $transactionNo]);
    }
    $pdo->commit();
    header('Location: ' . url('receipts/receipt.php?id=' . $saleId . '&print=1'));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    flash($e instanceof RuntimeException ? $e->getMessage() : 'The sale could not be saved. Please try again.', 'error');
    header('Location: ' . url('staff/pos.php'));
    exit;
}
