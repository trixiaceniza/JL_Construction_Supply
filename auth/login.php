<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn())
    redirectByRole();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        redirectByRole();
    }
    $error = 'Invalid email or password, or this account is inactive.';
}
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="auth-card">
    <h1>Welcome back</h1>
    <p class="muted">Sign in to the J&L management system.</p>
    <?php if ($error): ?>
        <div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Email address<input type="email" name="email" required autocomplete="username"></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn primary full" type="submit">Sign in</button>
    </form>
    <p class="muted small">Staff accounts are created by the Owner. There is no public registration.</p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>