<?php
require_once __DIR__ . '/../_private/bootstrap.php';
$portal = (string)($_GET['portal'] ?? $_POST['portal'] ?? '');
$roles = ['admin' => ['ADMIN'], 'students' => ['STUDENT'], 'counselor' => ['COUNSELOR'], 'reports' => ['ADMIN_REPORTS']];
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$valid = isset($roles[$portal]) && preg_match('/^[a-f0-9]{64}$/', $token);
$pdo = app_pdo();
$reset = null;
if ($valid) {
    $marks = implode(',', array_fill(0, count($roles[$portal]), '?'));
    $stmt = $pdo->prepare("SELECT pr.id,pr.user_id FROM password_resets pr JOIN users u ON u.user_id=pr.user_id WHERE pr.token=? AND pr.used=0 AND pr.expires_at>NOW() AND u.status='ACTIVE' AND u.role IN ($marks) LIMIT 1");
    $stmt->execute(array_merge([hash('sha256', $token)], $roles[$portal]));
    $reset = $stmt->fetch();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['password_confirm'] ?? '');
    if (!$reset) $error = 'This link is invalid or has expired. Request a new link.';
    elseif (strlen($password) < 12 || strlen($password) > 200) $error = 'Use a password between 12 and 200 characters.';
    elseif (!hash_equals($password, $confirm)) $error = 'The passwords do not match.';
    else {
        try {
            $pdo->beginTransaction();
            $consume = $pdo->prepare('UPDATE password_resets SET used=1 WHERE id=? AND used=0 AND expires_at>NOW()');
            $consume->execute([$reset['id']]);
            if ($consume->rowCount() !== 1) throw new RuntimeException('Reset token has already been consumed.');
            $pdo->prepare('UPDATE users SET password_hash=? WHERE user_id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $pdo->commit();
            unset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['division']);
            session_regenerate_id(true);
            redirect_to($portal . '/');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Password update failed.');
            $error = 'The password could not be updated. Please try again.';
        }
    }
}
$title = 'Set a new password';
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= h($title) ?></title><link rel="stylesheet" href="<?= h(app_url('shared/assets/css/design-system.css')) ?>"></head><body><main class="login-card"><h1><?= h($title) ?></h1><p>Choose a private password for your account. Minimum 12 characters.</p><?php if ($error): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?><?php if (!$reset): ?><div class="alert alert-warning">This password link is invalid or has expired. Request a new one from your portal.</div><a class="btn" href="<?= h(app_url(($portal === 'reports' ? 'reports/forget-password.php' : $portal . '/forgot-password.php'))) ?>">Request a new link</a><?php else: ?><form method="post" action=""><input type="hidden" name="portal" value="<?= h($portal) ?>"><input type="hidden" name="token" value="<?= h($token) ?>"><?= csrf_field() ?><div class="field"><label for="password">New password</label><input type="password" name="password" id="password" autocomplete="new-password" minlength="12" maxlength="200" required></div><div class="field"><label for="password_confirm">Confirm new password</label><input type="password" name="password_confirm" id="password_confirm" autocomplete="new-password" minlength="12" maxlength="200" required></div><button type="submit">Save password</button></form><?php endif; ?></main></body></html>
