<?php
require_once __DIR__ . '/auth.php';

// Keep existing XAMPP databases usable when new product fields are introduced.
function ensureProductUnitColumns(PDO $pdo): void
{
    $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('unit', $columns, true)) {
        $pdo->exec("ALTER TABLE products ADD unit VARCHAR(20) NOT NULL DEFAULT 'pcs' AFTER category");
    }
    if (!in_array('pieces_per_box', $columns, true)) {
        $pdo->exec("ALTER TABLE products ADD pieces_per_box INT UNSIGNED NOT NULL DEFAULT 1 AFTER unit");
    }
}

function ensureDashboardCostColumns(PDO $pdo): void
{
    $productColumns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('cost_per_unit', $productColumns, true)) {
        $pdo->exec("ALTER TABLE products ADD cost_per_unit DECIMAL(12,2) NULL DEFAULT NULL AFTER price");
    }
    $itemColumns = $pdo->query("SHOW COLUMNS FROM sale_items")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('unit_cost', $itemColumns, true)) {
        $pdo->exec("ALTER TABLE sale_items ADD unit_cost DECIMAL(12,2) NULL DEFAULT NULL AFTER unit_price");
    }
}

function money($amount): string
{
    return '₱' . number_format((float) $amount, 2);
}
function currentUserName(): string
{
    return $_SESSION['full_name'] ?? 'User';
}
function getDashboardStats(PDO $pdo): array
{
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE DATE(created_at)=? AND status='completed'");
    $stmt->execute([$today]);
    $todaySales = (float) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1");
    $products = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1 AND stock_quantity <= low_stock_threshold");
    $lowStock = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE DATE(created_at)=? AND status='completed'");
    $stmt->execute([$today]);
    $transactions = (int) $stmt->fetchColumn();

    return compact('todaySales', 'products', 'lowStock', 'transactions');
}
