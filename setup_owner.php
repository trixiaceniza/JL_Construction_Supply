<?php
require_once __DIR__ . '/config/database.php';
$message = '';
$count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='owner'")->fetchColumn();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $count === 0) {
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
        $message = 'Enter a name, valid email, and password with at least 10 characters.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name,email,password_hash,role) VALUES (?,?,?,'owner')");
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $message = 'Owner account created. DELETE setup_owner.php now, then sign in.';
        $count = 1;
    }
}
?><!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>One-time Owner Setup</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <main class="container">
        <section class="auth-card">
            <h1>One-time Owner Setup</h1><?php if ($message): ?>
                <div class="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?><?php if ($count > 0): ?>
                <p>An Owner account already exists or has just been created. For security, delete this file now.</p><a
                    class="btn primary" href="auth/login.php">Go to Login</a><?php else: ?>
                <p>Create the first Owner account. This page stops working once an Owner exists.</p>
                <form method="post"><label>Owner full name<input name="full_name" required></label><label>Email<input
                            type="email" name="email" required></label><label>Password (minimum 10 characters)<input
                            type="password" name="password" minlength="10" required></label><button
                        class="btn primary full">Create Owner</button></form><?php endif; ?>
        </section>
    </main>
</body>

</html>