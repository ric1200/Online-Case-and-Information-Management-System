<?php
require_once __DIR__ . '/../_private/bootstrap.php';
require_once __DIR__ . '/config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
if ($username === '' || $password === '' || strlen($username) > 150 || strlen($password) > 200) { redirect_to('counselor/?error=1'); }
try {
    $stmt = $pdo->prepare("SELECT user_id,role,division,password_hash FROM users WHERE username=? AND role=? AND status='ACTIVE' LIMIT 1");
    $stmt->execute([$username,'COUNSELOR']);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string)$user['password_hash'])) { redirect_to('counselor/?error=1'); }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['user_id'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['division'] = (string)$user['division'];
    if ('counselor' === 'admin') insertAudit($pdo, 'LOGIN_SUCCESS', 'users', (int)$user['user_id'], null, null, (int)$user['user_id']);
    redirect_to('counselor/counselor/dashboard.php');
} catch (Throwable $e) {
    error_log('Login attempt could not be completed.');
    redirect_to('counselor/?error=1');
}
