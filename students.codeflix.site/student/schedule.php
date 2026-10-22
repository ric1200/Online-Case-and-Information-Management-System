<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

require_once __DIR__ . '/db_connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection not available.');
}

// 1. KUNIN ANG UPCOMING SESSIONS (Ngayong araw pataas)
// UPDATE: Nilagyan natin ng check sa parehong student_user_id at student_id para sigurado
$upcoming_sessions = [];
$stmt_up = $conn->prepare("
    SELECT cs.*, u.first_name, u.last_name 
    FROM counseling_schedules cs
    LEFT JOIN users u ON cs.counselor_id = u.user_id
    WHERE (cs.student_user_id = ? OR cs.student_id = ?) AND cs.schedule_date >= CURDATE()
    ORDER BY cs.schedule_date ASC, cs.schedule_time ASC
");
if ($stmt_up) {
    $stmt_up->bind_param("ii", $user_id, $user_id);
    $stmt_up->execute();
    $result = $stmt_up->get_result();
    while ($row = $result->fetch_assoc()) {
        $upcoming_sessions[] = $row;
    }
    $stmt_up->close();
}

// 2. KUNIN ANG PAST SESSIONS (Nakaraan na araw)
$past_sessions = [];
$stmt_past = $conn->prepare("
    SELECT cs.*, u.first_name, u.last_name 
    FROM counseling_schedules cs
    LEFT JOIN users u ON cs.counselor_id = u.user_id
    WHERE (cs.student_user_id = ? OR cs.student_id = ?) AND cs.schedule_date < CURDATE()
    ORDER BY cs.schedule_date DESC, cs.schedule_time DESC
");
if ($stmt_past) {
    $stmt_past->bind_param("ii", $user_id, $user_id);
    $stmt_past->execute();
    $result = $stmt_past->get_result();
    while ($row = $result->fetch_assoc()) {
        $past_sessions[] = $row;
    }
    $stmt_past->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Schedule</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/css/student-dashboard.css" />
</head>
<body>
  <div class="app-shell">
    <header class="top-nav">
      <div class="nav-left">
        <span class="logo-mark">SCP</span>
        <span class="nav-title">Student Counseling Portal</span>
      </div>
      <nav class="nav-links">
        <a href="/student/dashboard.php" class="nav-link">Dashboard</a>
        <a href="/student/records.php" class="nav-link">Counseling Records</a>
        <a href="/student/schedule.php" class="nav-link is-active">Schedule</a>
        <a href="/student/messages.php" class="nav-link">Messages</a>
        <a href="/student/profile.php" class="nav-link">Profile</a>
      </nav>
      <div class="nav-user">
        <span class="user-name">Hi, Student</span>
        <form method="POST" action="/logout.php">
          <button type="submit" class="btn-outline">Logout</button>
        </form>
      </div>
    </header>

    <main class="page">
      <section class="page-header">
        <div>
          <h1>Schedule</h1>
          <p class="page-subtitle">
            Review your upcoming counseling appointments and sessions.
          </p>
        </div>
      </section>

      <section class="dashboard-grid">
        <article class="card">
          <header class="card-header">
            <div class="card-title-group">
              <span class="card-icon schedule-icon"></span>
              <div>
                <h2>Upcoming Sessions</h2>
                <p class="card-subtitle">Confirmed counseling appointments</p>
              </div>
            </div>
          </header>
          <div class="card-body">
            <?php if (empty($upcoming_sessions)): ?>
               <div class="empty-state">
                  <p class="empty-text" style="color:#666; font-style:italic;">You have no upcoming sessions scheduled.</p>
               </div>
            <?php else: ?>
                <ul class="item-list">
                  <?php foreach ($upcoming_sessions as $session): 
                      // Formatting date and time
                      $date_formatted = date("l, F j, Y", strtotime($session['schedule_date']));
                      $time_formatted = date("g:i A", strtotime($session['schedule_time']));
                      
                      // TINANGGAL ANG "Dr. ", ipapakita ang first name at last name
                      $counselor_name = !empty($session['first_name']) ? $session['first_name'] . ' ' . $session['last_name'] : "Counselor";
                      
                      // FIX: Handling empty strings or NULL values properly
                      $status = !empty($session['status']) ? ucfirst(trim($session['status'])) : 'Scheduled'; 
                  ?>
                  <li class="item-row">
                    <div class="item-main">
                      <p class="item-title">Counseling Session</p>
                      <p class="item-meta">
                          <?php echo $date_formatted . ' &middot; ' . $time_formatted . ' &middot; ' . htmlspecialchars($counselor_name); ?>
                      </p>
                      <?php if (!empty($session['notes'])): ?>
                          <p class="item-meta" style="font-size: 0.85em; color: #555;">Note: <?php echo htmlspecialchars($session['notes']); ?></p>
                      <?php endif; ?>
                    </div>
                    <?php if ($status === 'Pending'): ?>
                        <span class="tag-pill" style="background:#fff3cd; color:#856404;"><?php echo htmlspecialchars($status); ?></span>
                    <?php else: ?>
                        <span class="tag-pill tag-upcoming"><?php echo htmlspecialchars($status); ?></span>
                    <?php endif; ?>
                  </li>
                  <?php endforeach; ?>
                </ul>
            <?php endif; ?>
          </div>
        </article>

        <article class="card">
          <header class="card-header">
            <div class="card-title-group">
              <span class="card-icon cases-icon"></span>
              <div>
                <h2>Past Sessions</h2>
                <p class="card-subtitle">Your recent counseling history</p>
              </div>
            </div>
            <a href="/student/records.php" class="link-small">View full records</a>
          </header>
          <div class="card-body">
            <?php if (empty($past_sessions)): ?>
               <div class="empty-state">
                  <p class="empty-text" style="color:#666; font-style:italic;">No past sessions found.</p>
               </div>
            <?php else: ?>
                <ul class="item-list">
                  <?php foreach ($past_sessions as $session): 
                      $date_formatted = date("M d, Y", strtotime($session['schedule_date']));
                      
                      
                      $counselor_name = !empty($session['first_name']) ? $session['first_name'] . ' ' . $session['last_name'] : "Counselor";
                      
                     
                      $status = !empty($session['status']) ? ucfirst(trim($session['status'])) : 'Completed';
                  ?>
                  <li class="item-row">
                    <div class="item-main">
                      <p class="item-title">Counseling Session</p>
                      <p class="item-meta"><?php echo htmlspecialchars($status); ?> &middot; <?php echo $date_formatted; ?> &middot; <?php echo htmlspecialchars($counselor_name); ?></p>
                    </div>
                  </li>
                  <?php endforeach; ?>
                </ul>
            <?php endif; ?>
          </div>
        </article>
      </section>
    </main>
  </div>
</body>
</html>