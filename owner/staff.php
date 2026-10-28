<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('owner');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $error = 'Enter a name, valid email, and password with at least 8 characters.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,'staff')");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                flash('Staff account created.');
                header('Location: ' . url('owner/staff.php'));
                exit;
            } catch (PDOException $e) {
                $error = 'That email may already be registered.';
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE users SET is_active=IF(is_active=1,0,1) WHERE id=? AND role='staff'");
        $stmt->execute([$id]);
        flash('Staff account status updated.');
        header('Location: ' . url('owner/staff.php'));
        exit;
    }
}
$staff = $pdo->query("SELECT u.id,u.full_name,u.email,u.is_active,COALESCE(SUM(CASE WHEN s.status='completed' AND DATE(s.created_at)=CURDATE() THEN s.total_amount ELSE 0 END),0) AS daily_sales,COUNT(CASE WHEN s.status='completed' AND DATE(s.created_at)=CURDATE() THEN s.id END) AS daily_transactions FROM users u LEFT JOIN sales s ON s.staff_id=u.id WHERE u.role='staff' GROUP BY u.id ORDER BY u.full_name")->fetchAll();
$pageTitle = 'Staff Accounts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<h1>Staff Accounts</h1>
<?php if ($error): ?>
    <div class="alert error"><?= e($error) ?></div><?php endif; ?>
<section class="panel">
    <h2>Create Staff Account</h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action"
            value="add">
        <label>Full name<input name="full_name" required></label><label>Email<input type="email" name="email"
                required></label><label>Temporary password<input type="password" name="password" minlength="8"
                required></label>
        <div class="form-actions"><button class="btn primary">Create Account</button></div>
    </form>
</section>
<section class="panel">
    <h2>Daily Staff Performance</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Email</th>
                    <th>Transactions Today</th>
                    <th>Sales Today</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $s): ?>
                    <tr>
                        <td><?= e($s['full_name']) ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= (int) $s['daily_transactions'] ?></td>
                        <td><?= money($s['daily_sales']) ?></td>
                        <td><?= (int) $s['is_active'] ? 'Active' : 'Inactive' ?></td>
                        <td>
                            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input
                                    type="hidden" name="action" value="toggle"><input type="hidden" name="id"
                                    value="<?= (int) $s['id'] ?>"><button
                                    class="btn small-btn"><?= (int) $s['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$staff): ?>
                    <tr>
                        <td colspan="6">No staff accounts created yet.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>