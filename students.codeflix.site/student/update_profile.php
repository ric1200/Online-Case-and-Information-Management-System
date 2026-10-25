<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$program          = $_POST['program'] ?? '';
$year_level       = $_POST['year_level'] ?? '';
$phone_number     = $_POST['phone_number'] ?? '';
$address          = $_POST['address'] ?? '';
$city             = $_POST['city'] ?? '';
$zip_code         = $_POST['zip_code'] ?? '';
$religion         = $_POST['religion'] ?? '';
$birth_date       = $_POST['birth_date'] ?? null;
$gender           = $_POST['gender'] ?? '';
$guardian_name    = $_POST['guardian_name'] ?? '';
$guardian_contact = $_POST['guardian_contact'] ?? '';

// Avatar upload: optional; store path in avatar_path
$avatar_path = '';
$allowed    = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_bytes   = 2 * 1024 * 1024; // 2MB

if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['avatar']['tmp_name']);
    if (in_array($mime, $allowed, true) && $_FILES['avatar']['size'] <= $max_bytes) {
        $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $ext = isset($ext_map[$mime]) ? $ext_map[$mime] : 'jpg';
        // Use document root so uploads always go to this site's assets folder (works in cPanel)
        $base = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) : __DIR__;
        $upload_dir = $base . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        $filename = $user_id . '_' . time() . '.' . $ext;
        $full_path = $upload_dir . DIRECTORY_SEPARATOR . $filename;
        if (is_writable($upload_dir) && move_uploaded_file($_FILES['avatar']['tmp_name'], $full_path)) {
            $avatar_path = '/assets/images/avatars/' . $filename;
        }
    }
}

// Check if profile exists and get current avatar_path if not uploading new one
$stmt = $conn->prepare("SELECT profile_id, avatar_path FROM student_profiles WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$hasProfile = (bool) $row;
if ($hasProfile && $avatar_path === '') {
    $avatar_path = $row['avatar_path'] ?? '';
}

if ($hasProfile) {
    $sql = "UPDATE student_profiles
            SET program = ?, year_level = ?, phone_number = ?, address = ?, city = ?,
                zip_code = ?, religion = ?, birth_date = ?, gender = ?, guardian_name = ?,
                guardian_contact = ?, avatar_path = ?, updated_at = NOW()
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssi",
        $program,
        $year_level,
        $phone_number,
        $address,
        $city,
        $zip_code,
        $religion,
        $birth_date,
        $gender,
        $guardian_name,
        $guardian_contact,
        $avatar_path,
        $user_id
    );
    $stmt->execute();
    $stmt->close();
} else {
    $sql = "INSERT INTO student_profiles
            (user_id, program, year_level, phone_number, address, city, zip_code, religion,
             birth_date, gender, guardian_name, guardian_contact, avatar_path, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssssssss",
        $user_id,
        $program,
        $year_level,
        $phone_number,
        $address,
        $city,
        $zip_code,
        $religion,
        $birth_date,
        $gender,
        $guardian_name,
        $guardian_contact,
        $avatar_path
    );
    $stmt->execute();
    $stmt->close();
}

header('Location: profile.php?updated=1');
exit;
