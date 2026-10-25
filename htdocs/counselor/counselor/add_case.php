<?php
require_once __DIR__ . '/../config/counselor_auth_guard.php';



require_once __DIR__ . '/../config/db.php';


$successMessage = '';
$errorMessage = '';

$counselor_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $student_user_id_input = $_POST['student_user_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'Medium';
    $status = $_POST['status'] ?? 'Pending';
    $student_user_id_input = filter_var($student_user_id_input, FILTER_VALIDATE_INT) ?: 0;
    $title = trim((string)$title); $description = trim((string)$description);
    $priorities = ['Low','Medium','High','Urgent']; $statuses = ['Open','In Progress','Pending','Resolved','Closed'];

    if (!$counselor_id) {
        $errorMessage = "Error: Counselor session not found. Please re-login.";
    } elseif ($student_user_id_input > 0 && $title !== '' && $description !== '' && strlen($title) <= 255 && strlen($description) <= 10000 && in_array($priority, $priorities, true) && in_array($status, $statuses, true)) {
        $verifyStudent = $pdo->prepare("SELECT user_id FROM users WHERE user_id=? AND role='STUDENT' AND status='ACTIVE' LIMIT 1");
        $verifyStudent->execute([$student_user_id_input]);
        if (!$verifyStudent->fetchColumn()) { $errorMessage = 'Select an active student.'; } else {
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO cases (student_user_id, counselor_id, title, description, priority, status, created_at)
                VALUES (:student_user_id, :counselor_id, :title, :description, :priority, :status, NOW())
            ");

            $insertStmt->execute([
                ':student_user_id' => $student_user_id_input,
                ':counselor_id'    => $counselor_id,
                ':title'           => $title,
                ':description'     => $description,
                ':priority'        => $priority,
                ':status'          => $status
            ]);

            flash('success', 'Case successfully created.');
            redirect_to('counselor/counselor/cases.php');
        } catch (PDOException $e) {
            error_log('Counseling case creation failed.');
            $errorMessage = 'The case could not be saved.';
        }
        }
    } else {
        $errorMessage = "Please fill in all required fields.";
    }
}

// FETCH STUDENTS - Dinagdag natin ang u.user_id sa SELECT query
try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.first_name, u.last_name, sp.student_id
        FROM users u
        LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
        WHERE u.role = 'STUDENT' AND u.status='ACTIVE'
        ORDER BY u.first_name ASC
    ");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Student picker query failed.'); $errorMessage = 'Student list is temporarily unavailable.';
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>


<style>
    .content-section { max-width: 1000px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .form-card { background: #fff; padding: 40px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eef2f7; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .full-width { grid-column: span 2; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
    .back-link { text-decoration: none; color: #6c757d; font-size: 0.9rem; margin-bottom: 10px; display: inline-block; transition: 0.3s; }
    .back-link:hover { color: #4267b2; }
    .btn-save { background: #4267b2; color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .btn-cancel { text-decoration: none; color: #6c757d; padding: 12px 20px; }
</style>

<div class="content-section">
    <div style="margin-bottom: 25px;">
        <h1 style="font-weight: 800; margin:0;">Add New Case</h1>
        <p style="color: #8c92a0;">Recording as Counselor ID: <?= htmlspecialchars($counselor_id) ?></p>

                    <a href="cases.php" class="back-link" style="display: block; text-align: right;"><i class="fas fa-arrow-left"></i> Back to Directory</a>
    </div>

    <div class="form-card">
        <?php if ($successMessage): ?>
            <div style="background: #eafaf1; color: #2ecc71; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= $successMessage ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div style="background: #fdf2f2; color: #e74c3c; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <form method="POST"><?= csrf_field() ?>
            <div class="form-grid">

                <div class="form-group full-width">
                    <label>Select Student</label>
                    <select name="student_user_id" id="student_user_id" class="form-control" required>
                        <option value=""></option>
                        <?php foreach ($students as $student):
                            // Display ID (Ito yung nakikita ng user)
                            $display_id = !empty($student['student_id']) ? $student['student_id'] : $student['username'];
                            // Database ID (Ito yung isesave sa database)
                            $db_id = $student['user_id'];
                        ?>
                            <option value="<?= htmlspecialchars($db_id) ?>">
                                <?= htmlspecialchars($display_id) ?> - <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Case Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Behavior Issue" required>
                </div>

                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-control">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea name="description" class="form-control" style="height: 150px;" placeholder="Enter case details..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Initial Status</label>
                    <select name="status" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 30px; text-align: right;">
                <a href="cases.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">Create Case Record</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
