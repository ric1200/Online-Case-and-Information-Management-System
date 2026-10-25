<?php
require_once __DIR__ . '/../_private/bootstrap.php';
require_once __DIR__ . '/mail.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$portal = (string)($_POST['portal'] ?? '');
$roles = ['admin' => ['ADMIN'], 'students' => ['STUDENT'], 'counselor' => ['COUNSELOR'], 'reports' => ['ADMIN_REPORTS']];
if (!isset($roles[$portal])) { http_response_code(400); exit('Invalid request.'); }
$email = trim((string)($_POST['email'] ?? ''));
if (strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $pdo = app_pdo();
        $marks = implode(',', array_fill(0, count($roles[$portal]), '?'));
        $stmt = $pdo->prepare("SELECT user_id,email FROM users WHERE email = ? AND role IN ($marks) AND status = 'ACTIVE' LIMIT 1");
        $stmt->execute(array_merge([$email], $roles[$portal]));
        $user = $stmt->fetch();
        if ($user) {
            $raw = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $raw);
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')->execute([$user['user_id']]);
            $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), 0)')->execute([$user['user_id'], $tokenHash]);
            $pdo->commit();
            $link = app_url('shared/reset-password.php?portal=' . rawurlencode($portal) . '&token=' . rawurlencode($raw));
            send_system_email((string)$user['email'], 'Password reset link', '<p>Use this one-time link to set a new password. It expires in one hour.</p><p><a href="' . h($link) . '">Continue password setup</a></p>');
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log('Password reset request could not be processed.');
    }
}
// Identical response for known/unknown addresses; delivery is not claimed here.
$returnPath = $portal === 'reports' ? 'reports/forget-password.php?sent=1' : $portal . '/forgot-password.php?sent=1';
redirect_to($returnPath);
