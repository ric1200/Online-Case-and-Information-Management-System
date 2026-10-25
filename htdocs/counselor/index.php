<?php require_once __DIR__ . '/../_private/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Counselor Login</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="/shared/assets/css/design-system.css">
</head>
<body>

  <a href="https://ric1200.github.io/John_Jeric_Nombre.github.io/index.html" class="back-btn">
    <i class="fa-solid fa-arrow-left"></i> Back to Home
  </a>

  <div class="login-container">

    <div class="left-section">
      <div class="overlay">
        <h1>UCL Counseling Office</h1>
        <p>Guiding students towards mental wellness and success.</p>
      </div>
    </div>

    <div class="right-section">
      <form id="loginForm" class="login-form" method="post" action="login_process.php"><?= csrf_field() ?>
        <h2>Welcome Back</h2>
        <?php if (isset($_GET['error'])): ?><p class="alert alert-error" role="alert">Sign-in failed. Check your details and try again.</p><?php endif; ?>
        <p class="subtitle">Login to access your dashboard</p>

        <!-- Dynamic Error Message Placeholder -->
        <p id="error-message" class="error-msg"></p>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />

        <button type="submit" class="login-btn">Login</button>

        <div class="links">
            <a href="forgot-password.php">Forgot your password?</a>
        </div>

        <div class="footer">
          © 2026 UCL Counseling Office. All rights reserved.
        </div>
      </form>
    </div>

  </div>


  <script defer src="/shared/assets/js/portal.js"></script>
</body>
</html>
