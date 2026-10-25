<?php
declare(strict_types=1);

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Application configuration is missing. See DEPLOYMENT_INFINITYFREE.md.');
}
$config = require $configFile;
foreach (['APP_BASE_URL','DB_HOST','DB_NAME','DB_USER','DB_PASS','SMTP_HOST','SMTP_PORT','SMTP_USERNAME','SMTP_PASSWORD','SMTP_ENCRYPTION','SMTP_FROM_EMAIL','SMTP_FROM_NAME'] as $key) {
    if (!array_key_exists($key, $config)) {
        http_response_code(503);
        exit('Application configuration is incomplete.');
    }
}

define('APP_BASE_URL', rtrim((string)$config['APP_BASE_URL'], '/'));
date_default_timezone_set('Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        ;
    session_name('support_portal');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function app_url(string $path = ''): string
{
    return APP_BASE_URL . '/' . ltrim($path, '/');
}
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect_to(string $path): void
{
    header('Location: ' . (preg_match('~^https?://~i', $path) ? $path : app_url($path)));
    exit;
}
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}
function verify_csrf(): void
{
    $sent = (string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('This form expired or could not be verified. Refresh and try again.');
    }
}
function require_role(string ...$roles): void
{
    if (empty($_SESSION['user_id'])) {
        $area = $roles[0] ?? '';
        $login = match ($area) {
            'ADMIN' => 'admin/', 'STUDENT' => 'students/', 'COUNSELOR' => 'counselor/', 'ADMIN_REPORTS' => 'reports/', default => ''
        };
        redirect_to($login);
    }
    if (!in_array((string)($_SESSION['role'] ?? ''), $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}
function app_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    global $config;
    try {
        $pdo = new PDO(
            'mysql:host=' . $config['DB_HOST'] . ';dbname=' . $config['DB_NAME'] . ';charset=utf8mb4',
            $config['DB_USER'], $config['DB_PASS'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
        );
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(503);
        exit('The service is temporarily unavailable. Please contact the administrator.');
    }
    return $pdo;
}
function flash(string $kind, string $message): void
{
    $_SESSION['_flash'] = ['kind' => $kind, 'message' => $message];
}
function take_flash(): ?array
{
    $value = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($value) ? $value : null;
}
