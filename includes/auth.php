<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . url('auth/login.php'));
        exit;
    }
}
function requireRole(string $role): void
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}
function redirectByRole(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . url('auth/login.php'));
    } elseif ($_SESSION['role'] === 'owner') {
        header('Location: ' . url('owner/dashboard.php'));
    } else {
        header('Location: ' . url('staff/dashboard.php'));
    }
    exit;
}
function url(string $path = ''): string
{
    // Project folder name must match the folder under htdocs.
    return '/JL_Construction_Supply/' . ltrim($path, '/');
}
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function getFlash(): ?array
{
    if (empty($_SESSION['flash']))
        return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
