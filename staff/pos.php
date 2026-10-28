<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('staff');
ensureProductUnitColumns($pdo);
$products = $pdo->query("SELECT id,name,category,unit,pieces_per_box,price,stock_quantity FROM products WHERE is_active=1 AND stock_quantity>0 ORDER BY name")->fetchAll();
$pageTitle = 'Cashier / POS';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Cashier / Point of Sale</h1>
<p class="muted">Add items to the cart, review quantities, then save the sale. No payment is processed by this system.
</p>
<section class="panel"><label>Search products<input id="productSearch"
            placeholder="Search by product name or category"></label>
    <div class="product-grid" id="productGrid">
        <?php foreach ($products as $p): ?><button type="button" class="product-card" data-id="<?= (int) $p['id'] ?>"
                data-name="<?= e($p['name']) ?>" data-category="<?= e($p['category']) ?>"
                data-price="<?= e((string) $p['price']) ?>"
                data-stock="<?= (int) $p['stock_quantity'] ?>" data-unit="<?= e($p['unit']) ?>"><strong><?= e($p['name']) ?></strong><span><?= e($p['category']) ?></span><b><?= money($p['price']) ?> / <?= e($p['unit']) ?></b><small>Stock:
                    <?= (int) $p['stock_quantity'] ?> <?= e($p['unit'] === 'box' ? 'boxes (' . $p['pieces_per_box'] . ' pcs each)' : $p['unit']) ?></small></button><?php endforeach; ?>
        <?php if (!$products): ?>
            <p>No available products in stock. Ask the Owner to update inventory.</p><?php endif; ?>
    </div>
</section>
<section class="panel">
    <h2>Current Cart</h2>
    <form method="post" action="<?= e(url('staff/save_sale.php')) ?>" id="saleForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div id="cartEmpty" class="muted">No products selected.</div>
        <div class="table-wrap">
            <table id="cartTable" hidden>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Unit price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cartBody"></tbody>
            </table>
        </div>
        <div class="total-row">Grand total: <strong id="grandTotal">₱0.00</strong></div>
        <p class="muted small">Confirming records the sale and reduces stock. It does not accept or verify cash or
            e-money.</p>
        <button class="btn primary" id="completeSale" disabled>Save Sale &amp; Generate Receipt</button>
    </form>
</section>
<script src="<?= e(url('assets/js/pos.js')) ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
