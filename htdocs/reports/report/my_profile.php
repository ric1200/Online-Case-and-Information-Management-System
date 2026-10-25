<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $uid = $_SESSION['user_id'];

    // Kinukuha ang kumpletong detalye para sa Full Profile
    $stmt = $pdo->prepare("
        SELECT
            u.first_name, u.last_name, u.email, u.role,
            cp.specialization, cp.office_location, cp.phone_number AS c_phone,
            cp.address AS c_address, cp.city AS c_city, cp.zip_code AS c_zip,
            cp.birth_date AS c_birth, cp.gender AS c_gender, cp.avatar_path AS c_avatar,
            ap.phone_number AS a_phone, ap.avatar_path AS a_avatar, ap.department, ap.admin_level
        FROM users u
        LEFT JOIN counselor_profiles cp ON u.user_id = cp.user_id
        LEFT JOIN admin_profiles ap ON u.user_id = ap.user_id
        WHERE u.user_id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) { die("Error: Profile not found."); }

    // Logic para pagsamahin ang data mula sa dalawang table (Counselor o Admin)
    $fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
    $avatar = $user['c_avatar'] ?? $user['a_avatar'] ?? 'default_admin.png';
    $phone = $user['c_phone'] ?? $user['a_phone'] ?? 'N/A';

    // Dahil walang address, birth_date, at gender sa admin_profiles, mag f-fallback ito sa N/A kung admin ang naka-login
    $address = isset($user['c_address']) ? $user['c_address'] . ' ' . ($user['c_city'] ?? '') : 'N/A';
    $birthDate = $user['c_birth'] ?? 'N/A';
    $gender = $user['c_gender'] ?? 'N/A';

} catch (PDOException $e) {
    die("Database Error: " . 'Profile is temporarily unavailable.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Full Profile | UCL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/shared/assets/css/design-system.css">
</head>
<body class="bg-gray-50 font-sans">

    <nav class="bg-[#1a237e] text-white p-4 shadow-lg">
        <div class="container mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="flex items-center gap-2 hover:opacity-80 transition">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
            <span class="font-bold tracking-widest uppercase text-sm">User Profile Information</span>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-10 max-w-4xl">

        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">

            <div class="bg-gradient-to-r from-blue-600 to-indigo-800 p-10 text-center text-white">
                <div class="relative inline-block">
                    <img src="/reports/assets/image/union_logo.jpg"
                         class="w-32 h-32 rounded-full border-4 border-white/30 object-cover mx-auto shadow-2xl"
                         onerror="this.src='/reports/assets/image/union_logo.jpg'">
                </div>
                <h1 class="text-3xl font-black mt-4"><?php echo $fullName; ?></h1>
                <p class="opacity-80 uppercase tracking-widest text-xs font-bold mt-1">
                    Verified <?php echo htmlspecialchars($user['role']); ?>
                    <?php echo !empty($user['admin_level']) ? ' - ' . htmlspecialchars($user['admin_level']) : ''; ?>
                </p>
            </div>

            <div class="p-8 md:p-12 grid grid-cols-1 md:grid-cols-2 gap-10">

                <div class="space-y-6">
                    <h3 class="text-blue-800 font-black uppercase text-xs tracking-widest border-b pb-2">Account Details</h3>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fa fa-envelope"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Email Address</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fa fa-phone"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Phone Number</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($phone); ?></p>
                        </div>
                    </div>

                    <?php if(strtolower($user['role']) == 'counselor'): ?>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fa fa-star"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Specialization</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['specialization'] ?? 'Counseling'); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($user['department'])): ?>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fa fa-building"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Department</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['department']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="space-y-6">
                    <h3 class="text-blue-800 font-black uppercase text-xs tracking-widest border-b pb-2">Personal Information</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Birth Date</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($birthDate); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Gender</p>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($gender); ?></p>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Home Address</p>
                        <p class="font-semibold text-gray-800 leading-tight mt-1">
                            <?php echo !empty(trim($address)) ? htmlspecialchars($address) : 'No address provided'; ?>
                        </p>
                    </div>

                    <div class="mt-4 p-4 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <p class="text-[10px] text-gray-500 italic flex items-center gap-2">
                            <i class="fa fa-lock"></i> This information is managed by the system administrator. Editing is currently disabled.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <p class="text-center text-gray-400 text-xs mt-8">
            &copy; 2026 PHINMA Union College of Laguna - System
        </p>
    </div>

  <script defer src="/shared/assets/js/portal.js"></script>
</body>
</html>
