<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
verify_csrf();
$userId = (int)$_SESSION['user_id'];
$allowedLevels = ['1st Year','2nd Year','3rd Year','4th Year','Irregular'];
$allowedGenders = ['Male','Female','Other','Prefer not to say'];
$fields = [
    'program'=>[100,false], 'year_level'=>[20,false], 'phone_number'=>[20,false], 'address'=>[500,false],
    'city'=>[100,false], 'zip_code'=>[20,false], 'religion'=>[100,false], 'birth_date'=>[10,false],
    'gender'=>[30,false], 'guardian_name'=>[150,false], 'guardian_contact'=>[20,false]
];
$data=[];
$preserve=static function() use ($fields): void { $_SESSION['_old_profile']=array_intersect_key($_POST,$fields); };
foreach ($fields as $key=>$limits) {
    $value=trim((string)($_POST[$key]??''));
    if (strlen($value)>$limits[0]) { $preserve(); flash('error','Please check the field lengths and try again.'); redirect_to('students/student/profile.php'); }
    $data[$key]=$value===''?null:$value;
}
if ($data['year_level'] !== null && !in_array($data['year_level'],$allowedLevels,true)) { $preserve(); flash('error','Select a valid year level.'); redirect_to('students/student/profile.php'); }
if ($data['gender'] !== null && !in_array($data['gender'],$allowedGenders,true)) { $preserve(); flash('error','Select a valid gender.'); redirect_to('students/student/profile.php'); }
if ($data['birth_date'] !== null) { $date=DateTime::createFromFormat('!Y-m-d',$data['birth_date']); if (!$date || $date->format('Y-m-d')!==$data['birth_date']) { $preserve(); flash('error','Enter a valid birth date.'); redirect_to('students/student/profile.php'); } }
$pdo=app_pdo();
$existing=$pdo->prepare('SELECT profile_id,avatar_path,student_id FROM student_profiles WHERE user_id=? LIMIT 1');
$existing->execute([$userId]); $profile=$existing->fetch();
if (!$profile) { $preserve(); flash('error','Your student record is not initialized. Contact the counselor or system administrator.'); redirect_to('students/student/profile.php'); }
$avatar=$profile['avatar_path'];
if (isset($_FILES['avatar']) && $_FILES['avatar']['error']!==UPLOAD_ERR_NO_FILE) {
    $file=$_FILES['avatar'];
    if ($file['error']!==UPLOAD_ERR_OK || $file['size']>2*1024*1024) { $preserve(); flash('error','Avatar upload failed or exceeds 2 MB.'); redirect_to('students/student/profile.php'); }
    $finfo=new finfo(FILEINFO_MIME_TYPE); $mime=$finfo->file($file['tmp_name']);
    $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$mime])) { $preserve(); flash('error','Avatar must be a JPEG, PNG, or WebP image.'); redirect_to('students/student/profile.php'); }
    $directory=__DIR__.'/../uploads/avatars';
    if (!is_dir($directory) && !mkdir($directory,0755,true) && !is_dir($directory)) { $preserve(); flash('error','Avatar storage is unavailable.'); redirect_to('students/student/profile.php'); }
    $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'],$directory.'/'.$filename)) { $preserve(); flash('error','Avatar could not be saved.'); redirect_to('students/student/profile.php'); }
    $avatar=$filename;
}
try {
    $stmt=$pdo->prepare('UPDATE student_profiles SET program=?,year_level=?,phone_number=?,address=?,city=?,zip_code=?,religion=?,birth_date=?,gender=?,guardian_name=?,guardian_contact=?,avatar_path=?,updated_at=NOW() WHERE user_id=?');
    $stmt->execute([$data['program'],$data['year_level'],$data['phone_number'],$data['address'],$data['city'],$data['zip_code'],$data['religion'],$data['birth_date'],$data['gender'],$data['guardian_name'],$data['guardian_contact'],$avatar,$userId]);
    flash('success','Profile updated.');
    redirect_to('students/student/profile.php?updated=1');
} catch (Throwable $e) { error_log('Student profile update failed.'); $preserve(); flash('error','Profile could not be updated. Please verify your entries and try again.'); redirect_to('students/student/profile.php'); }
