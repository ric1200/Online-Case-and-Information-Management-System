<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../shared/mail.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$first=trim((string)($_POST['first_name']??'')); $middle=trim((string)($_POST['middle_name']??'')); $last=trim((string)($_POST['last_name']??''));
$email=trim((string)($_POST['email']??'')); $username=trim((string)($_POST['username']??'')); $role=(string)($_POST['role']??'');
$divisionByRole=['ADMIN'=>'ADMIN','COUNSELOR'=>'COUNSELOR','STUDENT'=>'STUDENT','ADMIN_REPORTS'=>'REPORT'];
if ($first==='' || $last==='' || strlen($first)>100 || strlen($last)>100 || strlen($middle)>50 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>150 || !preg_match('/^[A-Za-z0-9._-]{3,50}$/',$username) || !isset($divisionByRole[$role])) {
    flash('error','Please correct the required fields.'); redirect_to('admin/sysad/create_user.php');
}
$pdo=app_pdo(); $raw=bin2hex(random_bytes(32)); $tokenHash=hash('sha256',$raw); $passwordHash=password_hash(bin2hex(random_bytes(48)),PASSWORD_DEFAULT);
try {
    $pdo->beginTransaction();
    $stmt=$pdo->prepare("INSERT INTO users (first_name,middle_name,last_name,email,username,password_hash,division,role,status) VALUES (?,?,?,?,?,?,?,?, 'ACTIVE')");
    $stmt->execute([$first,$middle?:null,$last,$email,$username,$passwordHash,$divisionByRole[$role],$role]);
    $userId=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO password_resets (user_id,token,expires_at,used) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 1 HOUR),0)')->execute([$userId,$tokenHash]);
    $pdo->commit();
    $portalForRole=['ADMIN'=>'admin','COUNSELOR'=>'counselor','STUDENT'=>'students','ADMIN_REPORTS'=>'reports'][$role];
    $link=app_url('shared/reshared/reset-password.php?portal=' . rawurlencode($portalForRole) . '&token=' . rawurlencode($raw));
    $sent=send_system_email($email,'Set up your Student Support Portal account','<p>An account has been created for you. Use the one-time link below to set your password. It expires in one hour.</p><p><a href="'.h($link).'">Set your password</a></p>');
    insertAudit($pdo,'USER_CREATED','users',$userId,['role'=>$role],null,(int)$_SESSION['user_id']);
    flash($sent?'success':'warning',$sent?'Account created and password setup link sent.':'Account created, but email delivery is not configured or failed. Configure/test SMTP, then use Forgot Password to issue a fresh link.');
    redirect_to('admin/sysad/manage_users.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Admin user creation failed.');
    flash('error','Account could not be created. Check for a duplicate username/email or database migration issue.');
    redirect_to('admin/sysad/create_user.php');
}
