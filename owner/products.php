<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('owner');
ensureProductUnitColumns($pdo);
$error = '';
$categoryOptions = ['Building Materials', 'Tools', 'Plumbing', 'Electrical', 'Paint', 'Safety Equipment'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'Building Materials'));
        $unit = (string) ($_POST['unit'] ?? 'pcs');
        $piecesPerBox = filter_var($_POST['pieces_per_box'] ?? 1, FILTER_VALIDATE_INT);
        if ($unit !== 'box') $piecesPerBox = 1;
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT);
        $threshold = filter_var($_POST['low_stock_threshold'] ?? 5, FILTER_VALIDATE_INT);
        if ($name === '' || !in_array($category, $categoryOptions, true) || !in_array($unit, ['pcs', 'kg', 'meter', 'g', 'cm', 'box', 'bag'], true) || $piecesPerBox === false || $piecesPerBox < 1 || $price === false || $price < 0 || $stock === false || $stock < 0 || $threshold === false || $threshold < 0) {
            $error = 'Enter a product name and valid non-negative price and quantities.';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, unit=?, pieces_per_box=?, price=?, stock_quantity=?, low_stock_threshold=? WHERE id=?");
                $stmt->execute([$name, $category, $unit, $piecesPerBox, $price, $stock, $threshold, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, category, unit, pieces_per_box, price, stock_quantity, low_stock_threshold) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$name, $category, $unit, $piecesPerBox, $price, $stock, $threshold]);
            }
            flash('Product saved.');
            header('Location: ' . url('owner/products.php'));
            exit;
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?");
        $stmt->execute([$id]);
        flash('Product archived. Existing sales history is preserved.');
        header('Location: ' . url('owner/products.php'));
        exit;
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=? AND is_active=1");
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch() ?: null;
}
$products = $pdo->query("SELECT * FROM products WHERE is_active=1 ORDER BY name")->fetchAll();
$pageTitle = 'Products';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Products</h1>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<section class="panel">
    <h2><?= $edit ? 'Edit Product' : 'Add Product' ?></h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action"
            value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <label>Product name<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
        <?php $savedCategory = (string) ($edit['category'] ?? 'Building Materials'); $savedCategory = in_array($savedCategory, $categoryOptions, true) ? $savedCategory : 'Building Materials'; ?>
        <label>Category<select name="category">
                <?php foreach ($categoryOptions as $option): ?><option value="<?= e($option) ?>" <?= $savedCategory === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?>
            </select></label>
        <label>Unit<select name="unit" id="productUnit">
                <?php foreach (['pcs' => 'Pieces (pcs)', 'kg' => 'Kilogram (kg)', 'meter' => 'Meter (m)', 'g' => 'Gram (g)', 'cm' => 'Centimeter (cm)', 'box' => 'Box', 'bag' => 'Bag'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($edit['unit'] ?? 'pcs') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select></label>
        <label id="piecesPerBoxField">Pieces per box<input type="number" min="1" step="1" name="pieces_per_box"
                value="<?= e((string) ($edit['pieces_per_box'] ?? 1)) ?>"><small>Set how many pieces are in one box. Stock and price are counted per box.</small></label>
        <label>Unit price (₱)<input type="number" min="0" step="0.01" name="price" required
                value="<?= e(isset($edit['price']) ? (string) $edit['price'] : '') ?>"></label>
        <label>Stock quantity<input type="number" min="0" name="stock_quantity" required
                value="<?= e(isset($edit['stock_quantity']) ? (string) $edit['stock_quantity'] : '0') ?>"></label>
        <label>Low-stock threshold<input type="number" min="0" name="low_stock_threshold"
                value="<?= e(isset($edit['low_stock_threshold']) ? (string) $edit['low_stock_threshold'] : '5') ?>"></label>
        <div class="form-actions"><button class="btn primary">Save Product</button><?php if ($edit): ?><a class="btn"
                    href="<?= e(url('owner/products.php')) ?>">Cancel</a><?php endif; ?></div>
    </form>
</section>
<section class="panel">
    <h2>Product List</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= e($p['category']) ?></td><td><?= e($p['unit'] === 'box' ? 'box (' . $p['pieces_per_box'] . ' pcs)' : $p['unit']) ?></td><td><?= money($p['price']) ?></td><td><?= (int) $p['stock_quantity'] ?></td><td class="actions"><a class="btn small-btn" href="?edit=<?= (int) $p['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Archive this product?')"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn danger small-btn">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$products): ?><tr><td colspan="6">No products added yet.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script>
(() => { const unit = document.getElementById('productUnit'), field = document.getElementById('piecesPerBoxField');
  const update = () => { field.hidden = unit.value !== 'box'; };
  unit.addEventListener('change', update); update();
})();
</script>
