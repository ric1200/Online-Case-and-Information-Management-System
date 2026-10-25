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

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper: get display name from users table and remove "Dr."
$getUserName = function (int $uid) use ($conn): string {
    if ($uid <= 0) return 'Unknown';

    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, username, email FROM users WHERE user_id = ? LIMIT 1");
    if (!$stmt) return 'Unknown';

    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = null;

    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if ($res instanceof mysqli_result) {
            $row = $res->fetch_assoc();
            $res->free();
        }
    } else {
        $stmt->bind_result($fn, $mn, $ln, $uname, $email);
        if ($stmt->fetch()) {
            $row = [
                'first_name'  => $fn,
                'middle_name' => $mn,
                'last_name'   => $ln,
                'username'    => $uname,
                'email'       => $email,
            ];
        }
    }

    $stmt->close();
    if (!$row) return 'Unknown';

    $parts = array_filter([
        $row['first_name']  ?? '',
        $row['middle_name'] ?? '',
        $row['last_name']   ?? '',
    ]);
    $name = trim(implode(' ', $parts));

    if ($name === '') {
        $name = $row['username'] ?? ($row['email'] ?? 'Unknown');
    }

    $name = trim(preg_replace('/^Dr\.?\s+/i', '', $name));
    return $name;
};

// Load list of ALL active counselors for the dropdown
$counselors = [];
$stmt = $conn->prepare("
    SELECT user_id,
           TRIM(CONCAT_WS(' ',
               NULLIF(first_name, ''),
               NULLIF(middle_name, ''),
               NULLIF(last_name, '')
           )) AS display_name,
           username,
           email
    FROM users
    WHERE role = 'COUNSELOR' AND status = 'ACTIVE'
    ORDER BY first_name ASC
");
if ($stmt) {
    $stmt->execute();
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $dn = trim($row['display_name']);
                if (empty($dn)) $dn = $row['username'] ?? ($row['email'] ?? 'Unknown');
                $dn = trim(preg_replace('/^Dr\.?\s+/i', '', $dn));
                $row['display_name'] = $dn;
                $counselors[] = $row;
            }
            $result->free();
        }
    }
    $stmt->close();
}

// Handle sending messages
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error_msg = 'Invalid request. Please refresh and try again.';
    } else {
        $recipient_id = isset($_POST['recipient_id']) ? (int) $_POST['recipient_id'] : 0;
        $msg_subject  = isset($_POST['subject']) ? trim($_POST['subject']) : 'No Subject';
        $msg_agenda   = isset($_POST['agenda']) ? trim($_POST['agenda']) : 'General Inquiry';
        $msg_content  = isset($_POST['content']) ? trim($_POST['content']) : '';

        if ($recipient_id > 0 && $msg_content !== '' && $msg_subject !== '') {
            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, recipient_id, subject, agenda, status, content, created_at)
                VALUES (?, ?, ?, ?, 'Unread', ?, NOW())
            ");
            if ($stmt) {
                $stmt->bind_param("iisss", $user_id, $recipient_id, $msg_subject, $msg_agenda, $msg_content);
                if ($stmt->execute()) {
                    $success_msg = "Your message has been sent successfully!";
                } else {
                    $error_msg = "Something went wrong. Could not send the message.";
                }
                $stmt->close();
            }
        } else {
            $error_msg = "Please fill out all required fields.";
        }
    }
}

// Fetch Inbox Messages (All recent messages involving the user)
$inbox_messages = [];
$stmt = $conn->prepare("
    SELECT sender_id, recipient_id, subject, agenda, content, created_at
    FROM messages
    WHERE sender_id = ? OR recipient_id = ?
    ORDER BY created_at DESC
");
if ($stmt) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $other_id = (int) ($row['sender_id'] == $user_id ? $row['recipient_id'] : $row['sender_id']);
                $is_from_me = ($row['sender_id'] == $user_id);

                $inbox_messages[] = [
                    'other_id'   => $other_id,
                    'other_name' => $getUserName($other_id),
                    'is_from_me' => $is_from_me,
                    'subject'    => $row['subject'] !== '' ? $row['subject'] : 'No Subject',
                    'agenda'     => $row['agenda'] ?? 'General',
                    'content'    => $row['content'],
                    'created_at' => date('M j, Y - g:i A', strtotime($row['created_at']))
                ];
            }
            $result->free();
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Messages</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/css/student-dashboard.css" />

  <style>
    /* CSS para sa Message Preview at Action Buttons */
    .inbox-preview {
      display: -webkit-box;
      -webkit-line-clamp: 2; /* Hanggang 2 lines lang ang ipapakita */
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #64748b;
      font-size: 0.95rem;
      margin-bottom: 12px;
    }
    .btn-read-msg {
      background: #f1f5f9;
      color: #0f172a;
      border: 1px solid #cbd5e1;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-read-msg:hover {
      background: #e2e8f0;
    }

    /* CSS para sa Modal Pop-up */
    .msg-modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.7);
      z-index: 9999;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(3px);
    }
    .msg-modal {
      background: #ffffff;
      padding: 24px;
      border-radius: 12px;
      max-width: 550px;
      width: 90%;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    .msg-modal-header {
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .msg-modal-title {
      font-size: 1.25rem;
      color: #1e293b;
      margin: 0 0 8px 0;
    }
    .msg-modal-meta {
      font-size: 0.85rem;
      color: #64748b;
      margin: 0;
    }
    .msg-modal-body {
      color: #334155;
      line-height: 1.6;
      font-size: 1rem;
      white-space: pre-wrap;
      max-height: 50vh;
      overflow-y: auto;
      padding-right: 8px;
    }
    .msg-modal-footer {
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }
    .btn-close {
      background: white;
      color: #475569;
      border: 1px solid #cbd5e1;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn-close:hover {
      background: #f8fafc;
    }
    .btn-reply {
      background: #1e3a8a;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn-reply:hover {
      background: #172554;
    }
  </style>
</head>
<body>
  <div class="app-shell">

    <header class="top-nav">
      <div class="nav-left">
        <span class="logo-mark">SCP</span>
        <h1 class="nav-title">Student Counseling Portal</h1>
      </div>
      <nav class="nav-links">
        <a href="/student/dashboard.php" class="nav-link">Dashboard</a>
        <a href="/student/records.php" class="nav-link">Counseling Records</a>
        <a href="/student/schedule.php" class="nav-link">Schedule</a>
        <a href="/student/messages.php" class="nav-link is-active">Messages</a>
        <a href="/student/profile.php" class="nav-link">Profile</a>
      </nav>
      <div class="nav-user">
        <span class="user-name">Hi, <?php echo htmlspecialchars($getUserName($user_id)); ?></span>
        <form method="POST" action="/logout.php" style="margin: 0;">
          <button type="submit" class="btn-outline">Logout</button>
        </form>
      </div>
    </header>

    <main class="page">
      <section class="page-header">
        <div>
          <h1>Messages</h1>
          <p class="page-subtitle">Communicate with counselors and view your recent messages.</p>
        </div>
      </section>

      <?php if ($success_msg): ?>
        <div class="alert alert-success">✓ <?php echo htmlspecialchars($success_msg); ?></div>
      <?php endif; ?>
      <?php if ($error_msg): ?>
        <div class="alert alert-error">⚠ <?php echo htmlspecialchars($error_msg); ?></div>
      <?php endif; ?>

      <section class="messages-grid">

        <article class="styled-card" id="composeSection">
          <div class="styled-card-header">
            Compose Message
          </div>
          <div class="styled-card-body">
            <form method="post" action="/student/messages.php" class="compose-form">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <input type="hidden" name="subject" value="No Subject">

              <div class="form-group">
                <label for="recipient_id">Send To (Counselor)</label>
                <select id="recipient_id" name="recipient_id" required>
                  <option value="" disabled selected>-- Choose a Counselor --</option>
                  <?php foreach ($counselors as $c): ?>
                    <option value="<?php echo (int) $c['user_id']; ?>">
                      <?php echo htmlspecialchars($c['display_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="agenda">Reason / Agenda</label>
                <select id="agenda" name="agenda" required>
                  <option value="" disabled selected>Select an agenda</option>
                  <option value="Self-Referral">Self-Referral</option>
                  <option value="Referring a Friend">Referring a Friend</option>
                  <option value="General Inquiry">General Inquiry</option>
                  <option value="Follow-up">Follow-up</option>
                  <option value="Academic Concerns">Academic Concerns</option>
                </select>
              </div>

              <div class="form-group">
                <label for="content">Message Content</label>
                <textarea id="composeContent" name="content" rows="6" required placeholder="Write your message here..."></textarea>
              </div>

              <button type="submit" class="btn-primary-block">Send Message</button>
            </form>
          </div>
        </article>

        <article class="styled-card">
          <div class="styled-card-header">
            Recent Inbox
          </div>
          <div class="styled-card-body">
            <?php if (empty($inbox_messages)): ?>
              <div class="empty-state">
                No recent messages found. When you send or receive messages, they will appear here.
              </div>
            <?php else: ?>
              <div class="inbox-container">
                <?php foreach ($inbox_messages as $msg):
                    $headerText = $msg['is_from_me'] ? 'To: ' . htmlspecialchars($msg['other_name']) : 'From: Counselor ' . htmlspecialchars($msg['other_name']);
                ?>
                  <div class="inbox-item">
                    <div class="inbox-header">
                      <p class="inbox-sender"><strong><?php echo $headerText; ?></strong></p>
                      <span class="inbox-date"><?php echo htmlspecialchars($msg['created_at']); ?></span>
                    </div>
                    <p class="inbox-subject">[<?php echo htmlspecialchars($msg['agenda']); ?>]</p>

                    <p class="inbox-preview"><?php echo htmlspecialchars($msg['content']); ?></p>

                    <button type="button" class="btn-read-msg" onclick='openMsgModal(
                      <?php echo json_encode($headerText); ?>,
                      <?php echo json_encode($msg['created_at']); ?>,
                      <?php echo json_encode($msg['agenda']); ?>,
                      <?php echo json_encode($msg['content']); ?>,
                      <?php echo $msg['other_id']; ?>,
                      <?php echo $msg['is_from_me'] ? 'true' : 'false'; ?>
                    )'>
                      Read Full Message
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>

      </section>
    </main>
  </div>

  <div id="readMsgModal" class="msg-modal-overlay">
    <div class="msg-modal">
      <div class="msg-modal-header">
        <h3 class="msg-modal-title" id="modalMsgSender"></h3>
        <p class="msg-modal-meta">
          <span id="modalMsgDate"></span> &middot; Agenda: <strong id="modalMsgAgenda"></strong>
        </p>
      </div>
      <div class="msg-modal-body" id="modalMsgText"></div>

      <div class="msg-modal-footer">
        <button class="btn-close" onclick="closeMsgModal()">Close</button>
        <button id="btnQuickReply" class="btn-reply" style="display:none;" onclick="quickReply()">Reply to Counselor</button>
      </div>
    </div>
  </div>

  <script>
    let currentCounselorId = null;

    function openMsgModal(sender, date, agenda, content, counselorId, isFromMe) {
      document.getElementById('modalMsgSender').innerText = sender;
      document.getElementById('modalMsgDate').innerText = date;
      document.getElementById('modalMsgAgenda').innerText = agenda;
      document.getElementById('modalMsgText').textContent = content; // Para safe sa line breaks at special characters

      // Kung ang message ay HINDI galing sakin (galing sa counselor), pwede ko siyang reply-an.
      const replyBtn = document.getElementById('btnQuickReply');
      if (!isFromMe) {
        replyBtn.style.display = 'inline-block';
        currentCounselorId = counselorId;
      } else {
        replyBtn.style.display = 'none';
        currentCounselorId = null;
      }

      document.getElementById('readMsgModal').style.display = 'flex';
    }

    function closeMsgModal() {
      document.getElementById('readMsgModal').style.display = 'none';
    }

    function quickReply() {
      closeMsgModal();

      // Auto-select yung pangalan ng Counselor sa compose form
      if (currentCounselorId) {
        document.getElementById('recipient_id').value = currentCounselorId;
      }

      // Auto-select yung agenda as 'Follow-up'
      document.getElementById('agenda').value = 'Follow-up';

      // Focus agad sa Textarea para makapag-type
      const contentBox = document.getElementById('composeContent');
      contentBox.focus();

      // Scroll pataas papunta sa Compose box para makita agad
      document.getElementById('composeSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Isasara kapag pinindot ang paligid ng box
    window.onclick = function(event) {
      var modal = document.getElementById('readMsgModal');
      if (event.target === modal) {
        closeMsgModal();
      }
    }
  </script>

</body>
</html>
