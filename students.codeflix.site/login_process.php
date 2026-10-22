<?php
// Show errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Correct path to your DB
require __DIR__ . '/config/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request.');
}

// Validate input
if (empty($_POST['username']) || empty($_POST['password'])) {
    die('Please enter username and password.');
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Fetch user (STUDENT division only)
$stmt = $pdo->prepare("
    SELECT user_id, role, password_hash
    FROM users
    WHERE username = ?
      AND division = 'STUDENT'
      AND status = 'ACTIVE'
    LIMIT 1
");

$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify password
if ($user && password_verify($password, $user['password_hash'])) {

    // Prevent session fixation
    session_regenerate_id(true);

    // Store session info
    $_SESSION['user_id']  = $user['user_id'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['division'] = 'STUDENT';

    // Role-based redirect within STUDENT division
    if ($user['role'] === 'ADMIN') {
        header('Location: student_admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

// Generic error message
die('Invalid username or password.');
