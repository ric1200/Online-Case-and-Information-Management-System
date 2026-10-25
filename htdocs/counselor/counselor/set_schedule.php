<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$studentId=filter_input(INPUT_POST,'student_user_id',FILTER_VALIDATE_INT) ?: 0;
$date=(string)($_POST['schedule_date']??''); $time=(string)($_POST['schedule_time']??''); $notes=trim((string)($_POST['notes']??''));
$d=DateTime::createFromFormat('!Y-m-d',$date); $t=DateTime::createFromFormat('!H:i',$time);
if (!$studentId || !$d || $d->format('Y-m-d')!==$date || !$t || $t->format('H:i')!==$time || strlen($notes)>2000) { flash('error','Enter a valid student, date, time, and notes.'); redirect_to('counselor/counselor/messages.php'); }
try {
 $student=$pdo->prepare("SELECT u.user_id,sp.student_id FROM users u JOIN student_profiles sp ON sp.user_id=u.user_id WHERE u.user_id=? AND u.role='STUDENT' AND u.status='ACTIVE' LIMIT 1"); $student->execute([$studentId]); $row=$student->fetch();
 if (!$row) { flash('error','Student record was not found.'); redirect_to('counselor/counselor/messages.php'); }
 $stmt=$pdo->prepare("INSERT INTO counseling_schedules (counselor_id,student_user_id,student_id,schedule_date,schedule_time,status,notes) VALUES (?,?,?, ?,?,'Pending',?)");
 $stmt->execute([(int)$_SESSION['user_id'],$studentId,$row['student_id'],$date,$time,$notes ?: null]);
 flash('success','Schedule request saved.');
} catch(Throwable $e) { error_log('Counseling schedule could not be saved.'); flash('error','The schedule could not be saved.'); }
redirect_to('counselor/counselor/messages.php?student_id='.$studentId);
