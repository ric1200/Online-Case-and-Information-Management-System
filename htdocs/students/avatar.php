<?php
require_once __DIR__ . '/../_private/bootstrap.php';
require_role('STUDENT');
$file=(string)($_GET['file']??'');
if (!preg_match('/^[a-f0-9]{32}\.(?:jpg|png|webp)$/',$file)) { http_response_code(404); exit; }
$stmt=app_pdo()->prepare('SELECT avatar_path FROM student_profiles WHERE user_id=? LIMIT 1'); $stmt->execute([(int)$_SESSION['user_id']]);
if (!hash_equals((string)$stmt->fetchColumn(),$file)) { http_response_code(404); exit; }
$path=__DIR__.'/uploads/avatars/'.$file;
if (!is_file($path)) { http_response_code(404); exit; }
$finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($path);
if (!in_array($mime,['image/jpeg','image/png','image/webp'],true)) { http_response_code(404); exit; }
header('Content-Type: '.$mime); header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store'); header('Content-Length: '.filesize($path)); readfile($path);
