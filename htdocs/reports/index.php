<?php require_once __DIR__ . '/../_private/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Login - Reports & Analytics</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/image/images.jpg" />
  <link rel="stylesheet" href="/shared/assets/css/design-system.css">
</head>
<body>

  <button type="button" class="back-button" onclick="window.location.href='/';">
    <span class="arrow">←</span> Go Back
  </button>

  <main class="main-wrapper">
    <div class="login-card">

      <section class="left-section">
        <div class="brand-content">
          <h1>Reports & Analytics</h1>
          <p>Center For Student Development and Leadership</p>
          <small>Phinma Union College of Laguna</small>
        </div>
        <div class="illustration-box">
            <img src="assets/image/phinma_logo.jpg" alt="PHINMA College of Laguna" onerror="this.style.display='none'">
            <div class="fallback-icon">📊</div>
        </div>
      </section>

      <section class="right-section">
        <div class="form-container">
          <h2>Please Login to Access Dashboard</h2>
          <p class="subtitle">Welcome please enter your account</p>

          <form action="login_process.php" method="POST" class="login-form"><?= csrf_field() ?>
            <div class="input-group">
              <input type="text" name="username" id="username" placeholder="Enter your username" required />
            </div>

            <div class="input-group">
              <input type="password" name="password" id="password" placeholder="Enter your password" required />
              <div class="forgot-link">
                <a href="forget-password.php">Forgot Password?</a>
              </div>
            </div>

            <button type="submit" class="login-btn">Login In</button>
          </form>
    </div>
  </main>

  <script defer src="/shared/assets/js/portal.js"></script>
</body>
</html>
