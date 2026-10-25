<?php
require_once __DIR__ . '/_private/bootstrap.php';
$pdo = app_pdo();
$exists = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
if ($exists) { http_response_code(410); exit('Initial setup is disabled.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $first = trim((string)($_POST['first_name'] ?? ''));
    $middle = trim((string)($_POST['middle_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) $error = 'Complete all required fields with a valid email and a 3–50 character username.';
    elseif (strlen($password) < 12 || strlen($password) > 200) $error = 'Password must be 12–200 characters.';
    else {
        try {
            $pdo->beginTransaction();
            // Prevent two initial setup requests from creating competing first administrators.
            $pdo->query('SELECT user_id FROM users FOR UPDATE');
            if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) throw new RuntimeException('Setup already completed.');
            $stmt = $pdo->prepare("INSERT INTO users (first_name,middle_name,last_name,email,username,password_hash,division,role,status) VALUES (?,?,?,?,?,?, 'ADMIN','ADMIN','ACTIVE')");
            $stmt->execute([$first,$middle ?: null,$last,$email,$username,password_hash($password,PASSWORD_DEFAULT)]);
            $pdo->commit();
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['role'] = 'ADMIN';
            $_SESSION['division'] = 'ADMIN';
            redirect_to('admin/sysad/dashboard.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Initial admin setup failed.');
            $error = 'Account setup failed. Please verify the schema and whether the account already exists.';
        }
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Initial administrator setup</title><link rel="stylesheet" href="<?= h(app_url('shared/assets/css/design-system.css')) ?>"></head><body><main class="login-card"><h1>Set up the first administrator</h1><p>This one-time page is available only while the users table is empty. Remove this file after account creation.</p><?php if ($error): ?><div class="alert alert-error" role="alert"><?= h($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><label for="first_name">First name *</label><input id="first_name" name="first_name" maxlength="100" required><label for="middle_name">Middle name</label><input id="middle_name" name="middle_name" maxlength="50"><label for="last_name">Last name *</label><input id="last_name" name="last_name" maxlength="100" required><label for="email">Email *</label><input id="email" type="email" name="email" maxlength="150" required><label for="username">Username *</label><input id="username" name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" required><label for="password">Password (12+ characters) *</label><input id="password" type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required><button type="submit">Create administrator</button></form></main></body></html>
