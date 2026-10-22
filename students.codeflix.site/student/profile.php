<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$success = isset($_GET['updated']) && $_GET['updated'] === '1';
$profile = null;
$student_name = 'Student';
$avatar = '/assets/images/default_student.png';

require_once __DIR__ . '/db_connect.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection not available. Check db_connect.php.');
}

// Kunin ang profile details mula sa student_profiles table
$stmt = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $profile = $result->fetch_assoc();
        }
    }
    $stmt->close();
}

if ($profile && !empty($profile['avatar_path'])) {
    $avatar = $profile['avatar_path'];
}

// UPDATE: Kunin ang totoong pangalan mula sa users table gamit ang first_name at last_name
$name_stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ? LIMIT 1");
if ($name_stmt) {
    $name_stmt->bind_param("i", $user_id);
    if ($name_stmt->execute()) {
        $name_result = $name_stmt->get_result();
        if ($name_result && $name_row = $name_result->fetch_assoc()) {
            $fname = trim($name_row['first_name'] ?? '');
            $lname = trim($name_row['last_name'] ?? '');
            
            if ($fname !== '' && $lname !== '') {
                $student_name = $fname . ' ' . $lname;
            } elseif ($fname !== '') {
                $student_name = $fname;
            } else {
                 $student_name = 'Student';
            }
        }
    }
    $name_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #062456;
            --primary-light: #0d3b8e;
            --bg-page: #f4f7fb;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-soft: #e5e7eb;
            --input-bg: #f9fafb;
            --shadow-soft: 0 10px 25px rgba(6, 36, 86, 0.05);
            --shadow-hover: 0 15px 35px rgba(6, 36, 86, 0.1);
            --radius-card: 16px;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: "Inter", system-ui, -apple-system, sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
        }

        /* Layout */
        .profile-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .profile-header {
            background: var(--primary);
            color: #ffffff;
            padding: 24px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .profile-back-link { 
            display: inline-block; 
            margin-bottom: 8px; 
            color: #93c5fd; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .profile-back-link:hover { 
            color: #ffffff;
        }

        .profile-header h1 {
            margin: 0 0 4px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .profile-header-subtitle {
            margin: 0;
            font-size: 15px;
            color: #bfdbfe;
        }

        /* Main Grid for Landscape */
        .profile-main {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 32px auto;
            padding: 0 24px;
        }

        .landscape-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 24px;
            align-items: flex-start;
        }

        /* Cards */
        .profile-card {
            background: var(--card-bg);
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border-soft);
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .card-body {
            padding: 32px;
        }

        /* Profile Left Side (View) */
        .profile-top-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            height: 100px;
            width: 100%;
        }

        .profile-avatar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -50px;
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border-soft);
        }

        .avatar-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            background: #fff;
            margin-bottom: 16px;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .student-name {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
        }

        /* Added Student ID CSS */
        .student-id {
            margin: 0 0 8px;
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            font-weight: 500;
        }

        .student-program {
            margin: 0;
            font-size: 14px;
            color: var(--text-muted);
            text-align: center;
            font-weight: 500;
        }

        /* Info Sections inside Left Card */
        .info-list {
            padding: 24px;
        }

        .info-group {
            margin-bottom: 24px;
        }

        .info-group:last-child {
            margin-bottom: 0;
        }

        .info-group-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--primary);
            font-weight: 700;
            margin: 0 0 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-wrap: break-word;
        }

        /* Form Right Side */
        .form-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-soft);
        }

        .form-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .form-section {
            margin-bottom: 32px;
        }
        
        .form-section:last-of-type {
            margin-bottom: 0;
        }

        .form-section-title {
            margin: 0 0 16px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section-title::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 16px;
            background: var(--primary);
            border-radius: 4px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-field-full {
            grid-column: 1 / -1;
        }

        .form-field label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-main);
        }

        .form-field input,
        .form-field select {
            border-radius: 8px;
            border: 1px solid var(--border-soft);
            background-color: var(--input-bg);
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
        }

        .form-field input:focus,
        .form-field select:focus {
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(6, 36, 86, 0.1);
        }

        .form-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .form-actions {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border-soft);
            display: flex;
            justify-content: flex-end;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(6, 36, 86, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(6, 36, 86, 0.3);
        }

        /* Alerts */
        .alert-success {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            color: #065f46;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .no-profile-text {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .landscape-grid {
                grid-template-columns: 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="profile-page">
    <header class="profile-header">
        <div class="profile-header-inner">
            <a href="/student/dashboard.php" class="profile-back-link">← Back to Dashboard</a>
            <h1>Student Profile</h1>
            <p class="profile-header-subtitle">Review and manage your student information</p>
        </div>
    </header>

    <main class="profile-main">
        <?php if ($success): ?>
            <div class="alert-success">
                <strong>Success!</strong> &nbsp; Your profile has been updated successfully.
            </div>
        <?php endif; ?>

        <?php if (!$profile): ?>
            <p class="no-profile-text">
                No profile record found. Please contact the registrar or counseling office.
            </p>
        <?php else: ?>
            <div class="landscape-grid">
                
                <section class="profile-card">
                    <div class="profile-top-banner"></div>
                    <div class="profile-avatar-section">
                        <div class="avatar-wrapper">
                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Student Avatar" class="avatar-img">
                        </div>
                        <h2 class="student-name"><?php echo htmlspecialchars($student_name); ?></h2>
                        
                        <p class="student-id">
                            Student ID: <?php echo htmlspecialchars($profile['student_id'] ?? $user_id); ?>
                        </p>

                        <p class="student-program">
                            <?php echo htmlspecialchars($profile['program'] ?? ''); ?>
                            <?php if (!empty($profile['year_level'])): ?>
                                <br><?php echo htmlspecialchars($profile['year_level']); ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="info-list">
                        <div class="info-group">
                            <h3 class="info-group-title">Personal Details</h3>
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['phone_number'] ?? '—'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">City</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['city'] ?? '—'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">ZIP</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['zip_code'] ?? '—'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Gender</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['gender'] ?? '—'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Birth Date</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['birth_date'] ?? '—'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Religion</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['religion'] ?? '—'); ?></span>
                            </div>
                        </div>

                        <div class="info-group">
                            <h3 class="info-group-title">Guardian Info</h3>
                            <div class="info-item">
                                <span class="info-label">Name</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['guardian_name'] ?? '—'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Contact</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['guardian_contact'] ?? '—'); ?></span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="profile-card">
                    <div class="card-body">
                        <div class="form-header">
                            <h3 class="form-title">Update Information</h3>
                        </div>

                        <form id="edit-form" class="profile-form" action="update_profile.php" method="POST" enctype="multipart/form-data">
                            
                            <div class="form-section">
                                <h4 class="form-section-title">Profile Photo</h4>
                                <div class="form-grid">
                                    <div class="form-field form-field-full">
                                        <label for="avatar">Upload New Avatar (Optional)</label>
                                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <span class="form-hint">Format: JPG, PNG, GIF or WebP. Max size: 2MB. Leave empty to keep current photo.</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h4 class="form-section-title">Academic Information</h4>
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="program">Program</label>
                                        <input type="text" id="program" name="program" value="<?php echo htmlspecialchars($profile['program'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="year_level">Year Level</label>
                                        <select id="year_level" name="year_level">
                                            <?php
                                            $years = [
                                                '1' => '1',
                                                '2' => '2',
                                                '3' => '3',
                                                '4' => '4',
                                            ];
                                            $currentYear = $profile['year_level'] ?? '';
                                            foreach ($years as $val => $label) {
                                                $sel = ($currentYear == $val) ? 'selected' : '';
                                                echo "<option value=\"$val\" $sel>$label</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h4 class="form-section-title">Personal Information</h4>
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="birth_date">Birth Date</label>
                                        <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($profile['birth_date'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field form-field-full">
                                        <label for="address">Full Address</label>
                                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="zip_code">ZIP Code</label>
                                        <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($profile['zip_code'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="religion">Religion</label>
                                        <input type="text" id="religion" name="religion" value="<?php echo htmlspecialchars($profile['religion'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender">
                                            <?php
                                            $genders = ['Male', 'Female', 'Prefer not to say'];
                                            $currentGender = $profile['gender'] ?? '';
                                            foreach ($genders as $g) {
                                                $sel = ($currentGender === $g) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($g) . "\" $sel>" . htmlspecialchars($g) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h4 class="form-section-title">Guardian Information</h4>
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label for="guardian_name">Guardian Name</label>
                                        <input type="text" id="guardian_name" name="guardian_name" value="<?php echo htmlspecialchars($profile['guardian_name'] ?? ''); ?>">
                                    </div>
                                    <div class="form-field">
                                        <label for="guardian_contact">Guardian Contact</label>
                                        <input type="tel" id="guardian_contact" name="guardian_contact" value="<?php echo htmlspecialchars($profile['guardian_contact'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </section>

            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>