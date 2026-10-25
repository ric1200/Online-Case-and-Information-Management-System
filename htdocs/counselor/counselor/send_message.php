<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$studentId=filter_input(INPUT_POST,'recipient_id',FILTER_VALIDATE_INT) ?: 0;
$content=trim((string)($_POST['content']??''));
if (!$studentId || $content==='' || strlen($content)>5000) { flash('error','Enter a message of up to 5,000 characters.'); redirect_to('counselor/counselor/messages.php'); }
$check=$pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='STUDENT' AND status='ACTIVE' LIMIT 1"); $check->execute([$studentId]);
if (!$check->fetchColumn()) { http_response_code(404); exit('Student not found.'); }
try {
 $stmt=$pdo->prepare("INSERT INTO messages (sender_id,recipient_id,subject,agenda,content,status) VALUES (?,?,'Counselor Reply','General Inquiry',?,'Unread')");
 $stmt->execute([(int)$_SESSION['user_id'],$studentId,$content]);
 flash('success','Message sent.');
} catch(Throwable $e) { error_log('Counselor message could not be sent.'); flash('error','Message could not be sent.'); }
redirect_to('counselor/counselor/messages.php?student_id='.$studentId);
