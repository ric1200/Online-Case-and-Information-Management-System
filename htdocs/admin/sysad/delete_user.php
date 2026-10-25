<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id === (int)$_SESSION['user_id']) { http_response_code(400); exit('Invalid account.'); }
try {
    $pdo = app_pdo();
    $stmt = $pdo->prepare("UPDATE users SET status='INACTIVE' WHERE user_id=? AND status='ACTIVE'");
    $stmt->execute([$id]);
    if ($stmt->rowCount()) insertAudit($pdo, 'USER_DEACTIVATED', 'users', $id, null, null, (int)$_SESSION['user_id']);
    flash('success', 'Account status updated.');
} catch (Throwable $e) { error_log('User deactivation failed.'); flash('error', 'The account could not be updated.'); }
redirect_to('admin/sysad/manage_users.php');
