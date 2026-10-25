<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$id=filter_input(INPUT_POST,'schedule_id',FILTER_VALIDATE_INT) ?: 0;
$status=(string)($_POST['new_status']??'');
$allowed=['Pending','Confirmed','Completed','Cancelled'];
if (!$id || !in_array($status,$allowed,true)) { http_response_code(400); exit('Invalid schedule update.'); }
$owner=$pdo->prepare('SELECT 1 FROM counseling_schedules WHERE schedule_id=? AND counselor_id=?');
$owner->execute([$id,(int)$_SESSION['user_id']]);
if (!$owner->fetchColumn()) { http_response_code(404); exit('Schedule not found.'); }
$stmt=$pdo->prepare('UPDATE counseling_schedules SET status=? WHERE schedule_id=? AND counselor_id=?');
$stmt->execute([$status,$id,(int)$_SESSION['user_id']]);

flash('success','Schedule status updated.');
redirect_to('counselor/counselor/view_schedules.php');
