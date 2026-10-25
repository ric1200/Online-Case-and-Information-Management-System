<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$title=trim((string)($_POST['subject']??'')); $content=trim((string)($_POST['message']??''));
$start=(string)($_POST['start_date']??date('Y-m-d')); $end=(string)($_POST['end_date']??date('Y-m-d'));
$isDate=static function($value){$d=DateTime::createFromFormat('!Y-m-d',$value);return $d && $d->format('Y-m-d')===$value;};
if ($title==='' || strlen($title)>255 || $content==='' || strlen($content)>10000 || !$isDate($start) || !$isDate($end) || $end<$start) { flash('error','Enter a title, message, and valid date range.'); redirect_to('counselor/counselor/messages.php?broadcast=1'); }
try {
 $pdo->beginTransaction(); $counselor=(int)$_SESSION['user_id'];
 $pdo->prepare("INSERT INTO announcements (counselor_id,title,content,start_date,end_date,status) VALUES (?,?,?,?,?,'ACTIVE')")->execute([$counselor,$title,$content,$start,$end]);
 $pdo->prepare("INSERT INTO messages (sender_id,recipient_id,subject,content,status) SELECT ?,user_id,?,?,'Unread' FROM users WHERE role='STUDENT' AND status='ACTIVE'")->execute([$counselor,$title,$content]);
 $pdo->commit(); flash('success','Announcement published and sent to active students.');
} catch (Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); error_log('Broadcast could not be published.'); flash('error','The announcement could not be sent.'); }
redirect_to('counselor/counselor/messages.php?broadcast=1');
