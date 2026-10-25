<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../public_html/shared/config/mail_config.php';

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifier = trim($_POST['identifier'] ?? '');

    if (!empty($identifier)) {

        $stmt = $pdo->prepare("
            SELECT user_id, email, username
            FROM users
            WHERE email = :identifier
               OR username = :identifier
            LIMIT 1
        ");

        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $submitted = true;

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (:user_id, :token, :expires_at)
            ");

            $stmt->execute([
                'user_id'    => $user['user_id'],
                'token'      => $token,
                'expires_at' => $expires
            ]);

            $link = "/admin//set-password.php?token=" . urlencode($token);

            $subject = "Counseling Office Reset Request";

            $body = "
                <p>Good day,</p>
                <p>A request was made to reset the password for your Counseling Office account.</p>
                <p><strong>Username:</strong> {$user['username']}</p>
                <p>Click the link below to reset your password:</p>
                <p><a href='{$link}'>Reset Password</a></p>
                <p>This link will expire in <strong>1 hour</strong>.</p>
                <p>If you did not request this reset, please ignore this email.</p>
                <p>- Counseling Office System Administrator</p>
            ";

            sendSystemEmail($user['email'], $subject, $body);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===== Reset & Base ===== */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Tahoma, sans-serif;
}

body {
    min-height: 100vh;
    background: linear-gradient(135deg, #0b3d91, #1e88e5);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===== Main Card ===== */
.box {
    background: #ffffff;
    padding: 40px 35px;
    width: 100%;
    max-width: 420px;
    border-radius: 14px;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
    text-align: center;
}

/* ===== Title ===== */
.box h2 {
    color: #0b3d91;
    margin-bottom: 25px;
    font-size: 26px;
    font-weight: 700;
}

/* ===== Input Field ===== */
.box input[type="text"] {
    width: 100%;
    padding: 13px 14px;
    margin-bottom: 18px;
    border: 1px solid #cfd8dc;
    border-radius: 8px;
    font-size: 15px;
    transition: 0.2s ease;
}

.box input[type="text"]:focus {
    outline: none;
    border-color: #1e88e5;
    box-shadow: 0 0 0 2px rgba(30, 136, 229, 0.2);
}

/* ===== Button ===== */
.box button {
    width: 100%;
    padding: 13px;
    background: #0b3d91;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s ease;
}

.box button:hover {
    background: #1565c0;
    transform: translateY(-1px);
}

/* ===== Info Message ===== */
.info {
    background: #e3f2fd;
    color: #0b3d91;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 14px;
    line-height: 1.5;
}

/* ===== Links ===== */
.box a {
    display: inline-block;
    margin-top: 12px;
    color: #1565c0;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.box a:hover {
    text-decoration: underline;
}

/* ===== Responsive ===== */
@media (max-width: 480px) {
    .box {
        padding: 30px 22px;
    }

    .box h2 {
        font-size: 22px;
    }
}
</style>

</head>
<body>

<div class="box">

    <h2>Forgot Password</h2>

    <?php if ($submitted): ?>
        <p class="info">
            If the account exists, a password reset email has been sent.<br>
            Please check your inbox.
        </p>
        <a href="/index.php">Return to Login</a>
    <?php else: ?>
        <form method="POST">
            <input
                type="text"
                name="identifier"
                placeholder="Email or Username"
                required
            >
            <button type="submit">Send Reset Link</button>
        </form>
    <?php endif; ?>

    <a href="/index.php">Return to Login</a>

</div>

</body>
</html>
