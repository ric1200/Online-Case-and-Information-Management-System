<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// I-connect ang database
require_once __DIR__ . '/../config/db.php'; 

// Kunin ang student ID mula sa session (Fallback test ID)
$student_id = $_SESSION['user_id'] ?? 'STD-001'; 

// ==========================================
// KUNIN ANG MGA COUNSELORS SA DATABASE
// ==========================================
try {
    // Kinukuha natin lahat ng ACTIVE na counselors sa 'users' table
    $counselor_sql = "SELECT user_id, first_name, last_name FROM users WHERE role = 'COUNSELOR' AND status = 'ACTIVE'";
    $counselor_stmt = $pdo->query($counselor_sql);
    $counselors = $counselor_stmt->fetchAll();
} catch (PDOException $e) {
    die("Error loading counselors: " . $e->getMessage());
}

// ==========================================
// LOGIC PARA SA PAG-SEND NG MESSAGE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = $_POST['recipient_id']; 
    $subject = $_POST['subject'];
    $agenda = $_POST['agenda'];
    $content = $_POST['content'];

    try {
        $insert_sql = "INSERT INTO messages (sender_id, recipient_id, subject, agenda, content, status) 
                       VALUES (:sender, :recipient, :subject, :agenda, :content, 'Unread')";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute([
            ':sender' => $student_id,
            ':recipient' => $recipient_id,
            ':subject' => $subject,
            ':agenda' => $agenda,
            ':content' => $content
        ]);
        echo "<p style='color: green;'>Message sent successfully!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error sending message: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Messages</title>
</head>
<body>

    <h1>Welcome, <?php echo htmlspecialchars($student_id); ?></h1>
    
    <hr>

    <h2>Send a Message to Counselor</h2>
    <form method="POST" action="">
        <p>
            <label>Send to (Select Counselor):</label><br>
            <select name="recipient_id" required>
                <option value="">-- Choose a Counselor --</option>
                <?php foreach ($counselors as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['user_id']); ?>">
                        <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?> (<?php echo htmlspecialchars($c['user_id']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label>Agenda:</label><br>
            <select name="agenda" required>
                <option value="General Inquiry">General Inquiry</option>
                <option value="Self-Referral">Self-Referral</option>
                <option value="Referring a Friend">Referring a Friend</option>
                <option value="Follow-up">Follow-up</option>
            </select>
        </p>

        <p>
            <label>Subject:</label><br>
            <input type="text" name="subject" required>
        </p>

        <p>
            <label>Message Content:</label><br>
            <textarea name="content" rows="5" cols="40" required></textarea>
        </p>

        <p>
            <button type="submit" name="send_message">Send Message</button>
        </p>
    </form>

    <hr>

    <h2>Your Conversation History</h2>
    
    <?php
    try {
        $fetch_sql = "SELECT * FROM messages 
                      WHERE sender_id = :sid OR recipient_id = :sid2 
                      ORDER BY created_at DESC";
        $fetchStmt = $pdo->prepare($fetch_sql);
        $fetchStmt->execute([
            ':sid' => $student_id, 
            ':sid2' => $student_id
        ]);
        $all_messages = $fetchStmt->fetchAll();

        if (count($all_messages) > 0) {
            echo "<ul>";
            foreach ($all_messages as $msg) {
                $type = ($msg['sender_id'] === $student_id) ? "Sent to: " . $msg['recipient_id'] : "Received from: " . $msg['sender_id'];
                
                $date = date("M d, Y h:i A", strtotime($msg['created_at']));
                $subject = htmlspecialchars($msg['subject']);
                $agenda = htmlspecialchars($msg['agenda']);
                $content = nl2br(htmlspecialchars($msg['content']));

                echo "<li>";
                echo "<b>{$type}</b> <small>({$date})</small><br>";
                echo "<i>Agenda: {$agenda} | Subject: {$subject}</i><br>";
                echo "<p>{$content}</p>";
                echo "</li><br>";
            }
            echo "<ul>";
        } else {
            echo "<p>No messages found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error loading messages: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>
</html>