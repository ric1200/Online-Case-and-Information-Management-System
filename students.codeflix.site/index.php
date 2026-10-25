<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Union College of Laguna - Phinma Education</title>

<link rel="stylesheet" href="assets/css/login.css">
<link rel="stylesheet" href="assets/css/back-button.css">

</head>

<body>

<!-- BACK BUTTON -->
<a href="//" class="back-btn">← Back to Website</a>

<div class="container">

    <!-- LEFT SIDE -->
    <div class="left">
        <div class="brand">
            <h1>Welcome</h1>
            <p>Please login to access your dashboard</p>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right">
        <form action="login_process.php" method="POST" class="login-box">

            <h2>Login Account</h2>
            <span>Enter your credentials</span>

            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit">Login</button>

            <a href="forgot-password.php">Forgot your password?</a>

            <!-- COPYRIGHT BELOW FORGOT PASSWORD -->
            <div style="text-align:center; margin-top:15px; font-size:14px; color:#555;">
                © 2026 Phinma Union College of Laguna.
            </div>

        </form>
    </div>

</div>

</body>
</html>
