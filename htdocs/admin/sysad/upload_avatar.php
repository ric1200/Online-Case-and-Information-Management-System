<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT) ?: (int)$_SESSION['user_id'];
    $stmt=$pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='ADMIN' LIMIT 1"); $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) { http_response_code(404); exit('Administrator not found.'); }
    $file=$_FILES['avatar_file']??null;
    if (!$file || $file['error']!==UPLOAD_ERR_OK || $file['size']>2*1024*1024) { flash('error','Image upload failed or exceeds 2 MB.'); redirect_to('admin/sysad/upload_avatar.php?user_id='.$userId); }
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($types[$mime])) { flash('error','Use a JPEG, PNG, or WebP image.'); redirect_to('admin/sysad/upload_avatar.php?user_id='.$userId); }
    $dir=__DIR__.'/../uploads/admins'; if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) { flash('error','Avatar storage is unavailable.'); redirect_to('admin/sysad/profile.php'); }
    $name=bin2hex(random_bytes(16)).'.'.$types[$mime];
    if (!move_uploaded_file($file['tmp_name'],$dir.'/'.$name)) { flash('error','Avatar could not be saved.'); redirect_to('admin/sysad/profile.php'); }
    $pdo->prepare('INSERT INTO admin_profiles (user_id,avatar_path) VALUES (?,?) ON DUPLICATE KEY UPDATE avatar_path=VALUES(avatar_path)')->execute([$userId,$name]);
    flash('success','Profile image updated.'); redirect_to('admin/sysad/profile.php?user_id='.$userId);
}
$userId=filter_input(INPUT_GET,'user_id',FILTER_VALIDATE_INT) ?: (int)$_SESSION['user_id'];
$stmt=$pdo->prepare("SELECT first_name FROM users WHERE user_id=? AND role='ADMIN' LIMIT 1"); $stmt->execute([$userId]); $name=$stmt->fetchColumn();
if (!$name) { http_response_code(404); exit('Administrator not found.'); }
$notice=take_flash();
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Update profile image</title><link rel="stylesheet" href="/shared/assets/css/design-system.css"></head><body><main class="page-content"><h1>Update profile image</h1><p><?= h($name) ?></p><?php if($notice): ?><div class="alert alert-<?= h($notice['kind']) ?>" role="status"><?= h($notice['message']) ?></div><?php endif; ?><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$userId ?>"><label for="avatar_file">JPEG, PNG, or WebP (max 2 MB)</label><input type="file" id="avatar_file" name="avatar_file" accept="image/jpeg,image/png,image/webp" required><button type="submit">Save image</button></form></main></body></html>
