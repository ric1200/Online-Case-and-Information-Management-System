<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once __DIR__ . '/../config/db.php';
$user_id=(int)$_SESSION['user_id']; $pdo=app_pdo();
$recent_cases=$pdo->prepare("SELECT c.case_id,c.title,c.status,c.priority,c.created_at,u.first_name,u.last_name FROM cases c LEFT JOIN users u ON u.user_id=c.counselor_id WHERE c.student_user_id=? ORDER BY c.created_at DESC LIMIT 2"); $recent_cases->execute([$user_id]); $recent_cases=$recent_cases->fetchAll();
$recent_messages=$pdo->prepare("SELECT m.message_id,m.subject,m.agenda,m.content,m.created_at,u.first_name,u.last_name FROM messages m LEFT JOIN users u ON u.user_id=m.sender_id WHERE m.recipient_id=? ORDER BY m.created_at DESC LIMIT 2"); $recent_messages->execute([$user_id]); $recent_messages=$recent_messages->fetchAll();
$upcoming_schedules=$pdo->prepare("SELECT cs.schedule_id,cs.schedule_date,cs.schedule_time,cs.status,cs.notes,u.first_name,u.last_name FROM counseling_schedules cs LEFT JOIN users u ON u.user_id=cs.counselor_id WHERE cs.student_user_id=? AND cs.schedule_date>=CURDATE() ORDER BY cs.schedule_date,cs.schedule_time LIMIT 2"); $upcoming_schedules->execute([$user_id]); $upcoming_schedules=$upcoming_schedules->fetchAll();
$announcements=$pdo->query("SELECT title,content,created_at FROM announcements WHERE status='ACTIVE' AND CURDATE() BETWEEN start_date AND end_date ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Student Counseling Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/students/assets/css/student-dashboard.css" />
  <link rel="stylesheet" href="/shared/assets/css/design-system.css">
</head>
<body>
  <div class="app-shell">
    <header class="top-nav">
      <div class="nav-left">
        <span class="logo-mark">SCP</span>
        <span class="nav-title">Student Counseling Portal</span>
      </div>

      <nav class="nav-links">
        <a href="/students/student/dashboard.php" class="nav-link is-active">Dashboard</a>
        <a href="/students/student/records.php" class="nav-link">Counseling Records</a>
        <a href="/students/student/schedule.php" class="nav-link">Schedule</a>
        <a href="/students/student/messages.php" class="nav-link">Messages</a>
        <a href="/students/student/profile.php" class="nav-link">Profile</a>
      </nav>

      <div class="nav-user">
        <span class="user-name">Hi, Student</span>
        <form method="POST" action="/students/logout.php">
          <button type="submit" class="btn-outline">Logout</button>
        </form>
      </div>
    </header>

    <main class="page">
      <section class="page-header">
        <div>
          <h1>Student Dashboard</h1>
          <p class="page-subtitle">
            View your counseling records, upcoming schedule, and messages.
          </p>
        </div>
      </section>

      <section class="dashboard-grid">
        <article class="card">
          <header class="card-header">
            <div class="card-title-group">
              <span class="card-icon cases-icon"></span>
              <div>
                <h2>Recent Cases</h2>
                <p class="card-subtitle">Your latest counseling sessions</p>
              </div>
            </div>
            <a href="/students/student/records.php" class="link-small">View all</a>
          </header>
          <div class="card-body">
            <?php if (empty($recent_cases)): ?>
                <div class="empty-state">
                  <span class="empty-icon cases-icon"></span>
                  <p class="empty-title">No recent cases found</p>
                  <p class="empty-text">
                    Once you attend counseling sessions, your records will appear here.
                  </p>
                </div>
            <?php else: ?>
                <ul class="item-list">
                    <?php foreach ($recent_cases as $case):
                        // TINANGGAL ANG "Dr. ", ipapakita ang first name at last name
                        $counselor = !empty($case['first_name']) ? $case['first_name'] . ' ' . $case['last_name'] : "Counselor";
                        $status = ucfirst($case['status'] ?: 'Unknown');
                    ?>
                    <li class="item-row">
                        <div class="item-main">
                            <p class="item-title">Case #<?php echo $case['case_id']; ?> – <?php echo htmlspecialchars($case['title']); ?></p>
                            <p class="item-meta"><?php echo htmlspecialchars($counselor); ?> &middot; Status: <?php echo htmlspecialchars($status); ?></p>
                        </div>
                        <a href="/students/student/records.php" class="btn-ghost-small">View</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
          </div>
        </article>

        <article class="card">
          <header class="card-header">
            <div class="card-title-group">
              <span class="card-icon messages-icon"></span>
              <div>
                <h2>New Messages</h2>
                <p class="card-subtitle">Messages from your counselors</p>
              </div>
            </div>
            <a href="/students/student/messages.php" class="link-small">Open inbox</a>
          </header>
          <div class="card-body">
            <?php if (empty($recent_messages)): ?>
                <div class="empty-state">
                  <p class="empty-text" style="color:#666; font-style:italic;">You have no new messages.</p>
                </div>
            <?php else: ?>
                <ul class="item-list">
                    <?php foreach ($recent_messages as $msg):
                        // TINANGGAL ANG "Dr. ", ipapakita ang first name at last name
                        $sender = !empty($msg['first_name']) ? $msg['first_name'] . ' ' . $msg['last_name'] : "Counselor";
                        $time = date("M d, g:i A", strtotime($msg['created_at']));
                    ?>
                    <li class="item-row">
                        <div class="item-main">
                            <p class="item-title"><?php echo htmlspecialchars($msg['subject'] ?: 'New Message'); ?></p>
                            <p class="item-meta"><?php echo htmlspecialchars($sender); ?> &middot; <?php echo $time; ?></p>
                        </div>
                        <a href="/students/student/messages.php" class="btn-ghost-small">View</a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
          </div>
        </article>

        <article class="card">
          <header class="card-header">
            <div class="card-title-group">
              <span class="card-icon announcements-icon"></span>
              <div>
                <h2>Announcements</h2>
                <p class="card-subtitle">Updates from the counseling office</p>
              </div>
            </div>
          </header>
          <div class="card-body">
            <?php if (empty($announcements)): ?>
                <div class="empty-state">
                  <span class="empty-icon announcements-icon"></span>
                  <p class="empty-title">No announcements</p>
                  <p class="empty-text">
                    New announcements from the counseling office will appear here.
                  </p>
                </div>
            <?php else: ?>
                <ul class="item-list">
                    <?php foreach ($announcements as $ann): ?>
                        <li class="item-row" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                            <p class="item-title" style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($ann['title']); ?></p>
                            <p class="item-meta" style="margin: 0; font-size: 0.85em; color: #666;"><?php echo date('M j, Y g:i A', strtotime($ann['created_at'])); ?></p>
                            <p style="margin: 4px 0 0; font-size: 0.9em; color: #444; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
          </div>
        </article>

        <article class="card">
          <header class="card-header">
            <div class="card-title-group">
              <span class="card-icon schedule-icon"></span>
              <div>
                <h2>Upcoming Schedule</h2>
                <p class="card-subtitle">Your next counseling appointments</p>
              </div>
            </div>
            <a href="/students/student/schedule.php" class="link-small">View schedule</a>
          </header>
          <div class="card-body">
            <?php if (empty($upcoming_schedules)): ?>
                <div class="empty-state">
                  <p class="empty-text" style="color:#666; font-style:italic;">No upcoming appointments.</p>
                </div>
            <?php else: ?>
                <ul class="item-list">
                    <?php foreach ($upcoming_schedules as $sched):
                        $date = date("l", strtotime($sched['schedule_date'])); // Day of the week (e.g., Monday)
                        $time = date("g:i A", strtotime($sched['schedule_time']));
                        $status = ucfirst($sched['status'] ?: 'Scheduled');
                    ?>
                    <li class="item-row">
                        <div class="item-main">
                            <p class="item-title">Counseling Session</p>
                            <p class="item-meta"><?php echo $date; ?> &middot; <?php echo $time; ?> &middot; Student Portal</p>
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
      </section>
    </main>
  </div>
  <script defer src="/shared/assets/js/portal.js"></script>
</body>
</html>
