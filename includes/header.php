
<?php
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'J&L Construction Supply';
$flashMessage = getFlash();
$isAuthenticated = isLoggedIn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | J&L Construction Supply</title>

    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <script defer src="<?= e(url('assets/js/app.js')) ?>"></script>
</head>
<body>

<?php if ($isAuthenticated): ?>
    <div class="app-layout">
<?php else: ?>
    <header class="guest-header">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <span class="brand-icon">JL</span>
            <span>J&L <small>Construction Supply</small></span>
        </a>
    </header>
<?php endif; ?>

<?php if ($isAuthenticated && $flashMessage): ?>
    <div class="global-alert">
        <div class="alert <?= e($flashMessage['type']) ?>">
            <?= e($flashMessage['message']) ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!$isAuthenticated): ?>
    <main class="container guest-container">
        <?php if ($flashMessage): ?>
            <div class="alert <?= e($flashMessage['type']) ?>">
                <?= e($flashMessage['message']) ?>
            </div>
        <?php endif; ?>
<?php endif; ?>
</body>
</html>
