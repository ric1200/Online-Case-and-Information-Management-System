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

// Kunin ang lahat ng cases ng naka-login na student
$cases = [];
$stmt = $conn->prepare("
    SELECT c.*, u.first_name, u.last_name
    FROM cases c
    LEFT JOIN users u ON c.counselor_id = u.user_id
    WHERE c.student_user_id = ?
    ORDER BY c.created_at DESC
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cases[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Counseling Records</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/css/student-dashboard.css" />

  <style>
    .custom-modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.6); /* Dark semi-transparent background */
      z-index: 9999;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(2px);
    }
    .custom-modal {
      background: #ffffff;
      padding: 24px;
      border-radius: 12px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      position: relative;
      animation: modalFadeIn 0.3s ease;
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .custom-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 12px;
    }
    .custom-modal-title {
      margin: 0;
      font-size: 1.25rem;
      color: #1e293b;
      font-weight: 600;
    }
    .custom-modal-close {
      font-size: 1.5rem;
      color: #64748b;
      background: none;
      border: none;
      cursor: pointer;
      line-height: 1;
    }
    .custom-modal-close:hover {
      color: #0f172a;
    }
    .custom-modal-body {
      color: #475569;
      line-height: 1.6;
      font-size: 0.95rem;
      white-space: pre-wrap; /* Para ma-retain yung mga line breaks sa description */
    }
    .custom-modal-footer {
      margin-top: 24px;
      text-align: right;
    }
    .btn-primary-small {
      background: #1e3a8a;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
    }
    .btn-primary-small:hover {
      background: #172554;
    }
  </style>
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
        <a href="/student/records.php" class="nav-link is-active">Counseling Records</a>
        <a href="/student/schedule.php" class="nav-link">Schedule</a>
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
          <h1>Counseling Records</h1>
          <p class="page-subtitle">
            Review details of your past and ongoing counseling cases.
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
                <p class="card-subtitle">Last counseling records</p>
              </div>
            </div>
          </header>
          <div class="card-body">
            <?php if (empty($cases)): ?>
              <div class="empty-state">
                  <p class="empty-text" style="color:#666; font-style:italic;">You do not have any counseling records at this time.</p>
              </div>
            <?php else: ?>
              <ul class="item-list">
                <?php foreach ($cases as $case):
                    $date_opened = date("M d, Y", strtotime($case['created_at']));
                    $counselor_name = !empty($case['first_name']) ? $case['first_name'] . ' ' . $case['last_name'] : "Counselor";
                    $status = ucfirst($case['status'] ?: 'Unknown');
                ?>
                <li class="item-row">
                  <div class="item-main">
                    <p class="item-title">
                        Case #<?php echo $case['case_id']; ?> &ndash; <?php echo htmlspecialchars($case['title']); ?>
                    </p>
                    <p class="item-meta">
                        <?php echo htmlspecialchars($counselor_name); ?> &middot; Opened: <?php echo $date_opened; ?> &middot; Status: <?php echo htmlspecialchars($status); ?>
                    </p>
                  </div>
                  <button type="button" class="btn-ghost-small" onclick="openModal('<?php echo htmlspecialchars(addslashes($case['description'])); ?>')">
                      View
                  </button>
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
                <h2>How your records are used</h2>
                <p class="card-subtitle">Confidential and for counseling only</p>
              </div>
            </div>
          </header>
          <div class="card-body">
            <p class="page-subtitle">
              Your counseling records are kept confidential and are only used to
              support your academic, personal, and career development.
            </p>
            <p class="page-subtitle" style="margin-top: 1rem;">
              If you have questions about your records or wish to clarify any
              information, please contact your assigned counselor or the
              counseling office through the Messages page.
            </p>
          </div>
        </article>
      </section>
    </main>
  </div>

  <div id="caseModal" class="custom-modal-overlay">
    <div class="custom-modal">
      <div class="custom-modal-header">
        <h3 class="custom-modal-title">Case Description</h3>
        <button class="custom-modal-close" onclick="closeModal()">&times;</button>
      </div>
      <div class="custom-modal-body" id="modalDescriptionContent">
        </div>
      <div class="custom-modal-footer">
        <button class="btn-primary-small" onclick="closeModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    function openModal(description) {
      document.getElementById('modalDescriptionContent').textContent = description;
      document.getElementById('caseModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('caseModal').style.display = 'none';
    }

    // Isasara yung modal kapag pinindot mo yung labas nung box
    window.onclick = function(event) {
      var modalOverlay = document.getElementById('caseModal');
      if (event.target === modalOverlay) {
        closeModal();
      }
    }
  </script>

</body>
</html>
