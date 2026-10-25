<?php
require_once __DIR__ . '/../config/auth_guard.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /reports/');
    exit;
}

$uid = $_SESSION['user_id'];
$currentMonthName = date('F');
$currentYear = date('Y');

try {
    $stmt = $pdo->prepare("
        SELECT u.*, ap.avatar_path AS admin_pic, cp.avatar_path AS counselor_pic, sp.avatar_path AS student_pic
        FROM users u
        LEFT JOIN admin_profiles ap ON u.user_id = ap.user_id
        LEFT JOIN counselor_profiles cp ON u.user_id = cp.user_id
        LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
        WHERE u.user_id = :uid LIMIT 1
    ");
    $stmt->execute([':uid' => $uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

    $dbImage = $user['admin_pic'] ?? $user['counselor_pic'] ?? $user['student_pic'] ?? '';

    if (!empty($dbImage)) {
        $avatarPath = '/reports/assets/image/union_logo.jpg';
    } else {
        $avatarPath = "/reports/assets/image/union_logo.jpg";
    }

    $casePage = max(1, (int)($_GET['page'] ?? 1));
    $casePerPage = 50;
    $totalCaseRows = (int)$pdo->query('SELECT COUNT(*) FROM cases')->fetchColumn();
    $totalCasePages = max(1, (int)ceil($totalCaseRows / $casePerPage));
    $casePage = min($casePage, $totalCasePages);
    $caseOffset = ($casePage - 1) * $casePerPage;
    $stmtAll = $pdo->prepare("SELECT c.case_id,c.student_user_id,c.title,c.status,c.created_at,CONCAT(u.first_name,' ',u.last_name) AS student_name FROM cases c LEFT JOIN users u ON c.student_user_id=u.user_id ORDER BY c.created_at DESC LIMIT $casePerPage OFFSET $caseOffset");
    $stmtAll->execute();
    $allCases = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

    $stats = ['Total' => 0, 'Open' => 0, 'In Progress' => 0, 'Pending' => 0, 'Resolved' => 0, 'Closed' => 0];
    foreach ($pdo->query('SELECT status,COUNT(*) AS n FROM cases GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) as $summary) {
        if (array_key_exists($summary['status'], $stats)) {
            $stats[$summary['status']] = (int)$summary['n'];
            if ($summary['status'] !== 'Closed') $stats['Total'] += (int)$summary['n'];
        }
    }

    // Fetch Monthly Data for specific statuses
    $monthly_data = [
        'In Progress' => array_fill(1, 12, 0),
        'Pending' => array_fill(1, 12, 0),
        'Resolved' => array_fill(1, 12, 0)
    ];

    $monthlyDataQuery = $pdo->query("SELECT status, MONTH(created_at) as m_num, COUNT(*) as m_count FROM cases WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY status, MONTH(created_at)")->fetchAll(PDO::FETCH_ASSOC);
    foreach($monthlyDataQuery as $row) {
        if (isset($monthly_data[$row['status']])) {
            $monthly_data[$row['status']][(int)$row['m_num']] = (int)$row['m_count'];
        }
    }

    $months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    $counts_resolved = array_values($monthly_data['Resolved']);
    $counts_inprogress = array_values($monthly_data['In Progress']);
    $counts_pending = array_values($monthly_data['Pending']);

} catch (PDOException $e) { die("Database Error: " . 'Report data is temporarily unavailable.'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCL Case Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
        }
        .stat-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.06);
        }
        .modal-active { display: flex !important; animation: fadeIn 0.2s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .dropdown-menu { display: none; }
        .dropdown-menu.show { display: block; animation: slideIn 0.2s ease-out; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
  <link rel="stylesheet" href="/shared/assets/css/design-system.css">
</head>
<body class="antialiased text-slate-800">

    <nav class="glass-nav sticky top-0 z-50 px-6 py-3 shadow-sm">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <img src="../assets/image/union_logo.jpg" alt="Logo" class="w-10 h-10 object-contain rounded-xl bg-white shadow-sm border border-slate-100">
                    <h1 class="text-lg font-black tracking-tight text-blue-600">PHINMA-<span class="text-blue-600">Union College of Laguna</span></h1>
                </div>
            </div>

            <div class="flex items-center gap-4 relative">
                <div class="text-right hidden sm:block">
                    <p class="text-[10px] text-blue-500 font-black uppercase tracking-widest leading-none mb-1">Admin Portal</p>
                    <p class="text-sm font-bold text-slate-800 leading-none"><?= $fullName ?></p>
                </div>
                <div class="relative">
                    <button id="profileBtn" class="focus:outline-none relative">
                        <img src="<?= $avatarPath ?>"
                             onerror="this.src='/reports/assets/image/union_logo.jpg'"
                             class="w-11 h-11 rounded-full border-2 border-white shadow-md object-cover hover:ring-4 hover:ring-blue-100 transition-all cursor-pointer">
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                    </button>
                    <div id="profileDropdown" class="dropdown-menu absolute right-0 mt-3 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-[60]">
                        <a href="my_profile.php" class="flex items-center gap-3 px-5 py-3 text-sm font-bold text-slate-600 hover:text-blue-600 hover:bg-slate-50 transition-colors">
                            <i class="fa-solid fa-user-circle text-lg"></i> My Profile
                        </a>
                        <a href="logout.php" class="flex items-center gap-3 px-5 py-3 text-sm font-bold text-red-500 hover:bg-red-50 border-t border-slate-50 transition-colors">
                            <i class="fa-solid fa-arrow-right-from-bracket text-lg"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-10">

        <div class="bg-gradient-to-r from-blue-600 via-blue-500 to-sky-400 rounded-[2rem] p-8 md:p-10 mb-10 text-white shadow-lg shadow-blue-500/20 flex justify-between items-center relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-white opacity-10 rounded-full blur-2xl"></div>

            <div class="relative z-10">
                <h2 class="text-3xl md:text-4xl font-black mb-2 tracking-tight">Reports & Analytics Dashboard</h2>
                <p class="text-blue-50 font-medium text-sm md:text-base opacity-90">Here's the overall case performance monitoring for <strong class="text-white"><?= $currentMonthName ?> <?= $currentYear ?></strong>.</p>
            </div>
            <div class="hidden md:flex relative z-10 bg-white/20 p-2 rounded-2xl backdrop-blur-sm border border-white/30">
                <img src="../assets/image/phinma_logo.jpg" alt="PHINMA Logo" class="w-14 h-14 object-contain rounded-xl">
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between mb-4 gap-4">
            <h3 class="text-lg font-black text-slate-800">Quick Stats</h3>
            <div class="flex items-center gap-4">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest hidden sm:block">
                    <i class="fa-solid fa-hand-pointer mr-1"></i> Click cards to view
                </p>
                <button onclick="openPrintModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-black shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Print Main Report
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-12">
            <?php
                $cards = [
                    'Total' =>       ['icon' => 'fa-layer-group', 'icon_bg' => 'bg-blue-100', 'icon_text' => 'text-blue-600', 'border' => 'hover:border-blue-300'],
                    'Open' =>        ['icon' => 'fa-folder-open', 'icon_bg' => 'bg-sky-100', 'icon_text' => 'text-sky-600', 'border' => 'hover:border-sky-300'],
                    'In Progress' => ['icon' => 'fa-arrows-rotate', 'icon_bg' => 'bg-amber-100', 'icon_text' => 'text-amber-500', 'border' => 'hover:border-amber-300'],
                    'Pending' =>     ['icon' => 'fa-clock', 'icon_bg' => 'bg-red-100', 'icon_text' => 'text-red-500', 'border' => 'hover:border-red-300'],
                    'Resolved' =>    ['icon' => 'fa-circle-check', 'icon_bg' => 'bg-emerald-100', 'icon_text' => 'text-emerald-500', 'border' => 'hover:border-emerald-300'],
                    'Closed' =>      ['icon' => 'fa-box-archive', 'icon_bg' => 'bg-slate-200', 'icon_text' => 'text-slate-600', 'border' => 'hover:border-slate-400']
                ];

                foreach ($cards as $label => $data):
            ?>
            <div onclick="openModal('<?= $label ?>')" class="stat-card group <?= $data['border'] ?> flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-[1rem] <?= $data['icon_bg'] ?> <?= $data['icon_text'] ?> flex items-center justify-center text-xl mb-4 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                    <i class="fa-solid <?= $data['icon'] ?>"></i>
                </div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1"><?= $label === 'Total' ? 'Active Total' : $label ?></p>
                <p class="text-3xl font-black text-slate-800"><?= $stats[$label] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
            <div class="lg:col-span-4 bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex flex-col items-center">
                <div class="w-full flex justify-between items-center mb-8">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center"><i class="fa-solid fa-chart-pie"></i></div>
                        <h3 class="text-xs font-black uppercase text-slate-500 tracking-widest">Distribution</h3>
                    </div>
                    <button onclick="downloadChart('pieChart', 'Distribution')" class="text-slate-400 hover:text-blue-600 transition-colors bg-slate-50 hover:bg-blue-50 w-8 h-8 rounded-full flex items-center justify-center"><i class="fa-solid fa-download text-xs"></i></button>
                </div>
                <div class="relative w-full h-64"><canvas id="pieChart"></canvas></div>
            </div>

            <div class="lg:col-span-8 bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                <div class="w-full flex justify-between items-center mb-8">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center"><i class="fa-solid fa-circle-check"></i></div>
                        <h3 class="text-xs font-black uppercase text-slate-500 tracking-widest">Resolved Growth (<?= $currentYear ?>)</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <select id="monthSelectResolved" class="text-xs font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl px-4 py-2.5 outline-none border border-slate-100 cursor-pointer transition-colors">
                            <option value="All">All Months</option>
                            <?php foreach($months as $m) echo "<option value='$m'>$m</option>"; ?>
                        </select>
                        <button onclick="previewMonthlyReport('Resolved', 'monthSelectResolved')" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-black hover:bg-blue-600 shadow-lg shadow-slate-200 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice"></i> Report
                        </button>
                    </div>
                </div>
                <div class="relative w-full h-64"><canvas id="resolvedChart"></canvas></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                <div class="w-full flex justify-between items-center mb-8">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center"><i class="fa-solid fa-arrows-rotate"></i></div>
                        <h3 class="text-xs font-black uppercase text-slate-500 tracking-widest">In Progress Growth (<?= $currentYear ?>)</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <select id="monthSelectInProgress" class="text-xs font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl px-4 py-2.5 outline-none border border-slate-100 cursor-pointer transition-colors">
                            <option value="All">All Months</option>
                            <?php foreach($months as $m) echo "<option value='$m'>$m</option>"; ?>
                        </select>
                        <button onclick="previewMonthlyReport('In Progress', 'monthSelectInProgress')" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-black hover:bg-blue-600 shadow-lg shadow-slate-200 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice"></i> Report
                        </button>
                    </div>
                </div>
                <div class="relative w-full h-64"><canvas id="inProgressChart"></canvas></div>
            </div>

            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-shadow flex flex-col">
                <div class="w-full flex justify-between items-center mb-8">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center"><i class="fa-solid fa-clock"></i></div>
                        <h3 class="text-xs font-black uppercase text-slate-500 tracking-widest">Pending Growth (<?= $currentYear ?>)</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <select id="monthSelectPending" class="text-xs font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-xl px-4 py-2.5 outline-none border border-slate-100 cursor-pointer transition-colors">
                            <option value="All">All Months</option>
                            <?php foreach($months as $m) echo "<option value='$m'>$m</option>"; ?>
                        </select>
                        <button onclick="previewMonthlyReport('Pending', 'monthSelectPending')" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-black hover:bg-blue-600 shadow-lg shadow-slate-200 transition-all flex items-center gap-2">
                            <i class="fa-solid fa-file-invoice"></i> Report
                        </button>
                    </div>
                </div>
                <div class="relative w-full h-64"><canvas id="pendingChart"></canvas></div>
            </div>
        </div>
    </main>

    <div id="caseModal" class="hidden fixed inset-0 z-[100] bg-slate-900/40 backdrop-blur-md justify-center items-center p-4">
        <div class="bg-white w-full max-w-5xl max-h-[85vh] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col border border-white/20">
            <div class="p-6 md:p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl shadow-inner">
                        <i id="modalIcon" class="fa-solid fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-2xl font-black text-slate-800 tracking-tight">Case List</h3>
                        <p id="modalCount" class="text-[10px] text-blue-500 font-black uppercase tracking-widest mt-1"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative w-full sm:w-auto">
                        <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="modalSearch" oninput="filterData()" placeholder="Search Student Name..." class="w-full sm:w-64 pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium focus:ring-4 focus:ring-blue-50 focus:border-blue-500 outline-none transition-all shadow-sm">
                    </div>
                    <button onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:bg-red-50 hover:border-red-100 transition-all shadow-sm">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 md:p-8 overflow-y-auto flex-grow bg-white">
                <table class="w-full text-sm">
                    <thead class="text-[10px] font-black uppercase text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="text-left pb-4 pl-2">Case ID</th>
                            <th class="text-left pb-4">Student Name</th>
                            <th class="text-left pb-4">Title</th>
                            <th class="text-left pb-4">Status</th>
                            <th class="text-right pb-4">Date Logged</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody"></tbody>
                </table>
                <?php if ($totalCasePages > 1): ?><nav class="pagination" aria-label="Report case pages"><span>Showing page <?= (int)$casePage ?> of <?= (int)$totalCasePages ?></span><?php if ($casePage > 1): ?><a class="btn btn-secondary" href="?page=<?= $casePage-1 ?>#caseModal">Previous</a><?php endif; ?><?php if ($casePage < $totalCasePages): ?><a class="btn btn-secondary" href="?page=<?= $casePage+1 ?>#caseModal">Next</a><?php endif; ?></nav><?php endif; ?>
            </div>
        </div>
    </div>

    <div id="printModal" class="hidden fixed inset-0 z-[110] bg-slate-900/40 backdrop-blur-md justify-center items-center p-4">
        <div class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden flex flex-col border border-white/20">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-print"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-800 tracking-tight">Generate Report</h3>
                </div>
                <button onclick="closePrintModal()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Select Status</label>
                    <select id="printStatus" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-bold rounded-xl px-4 py-3 outline-none focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all">
                        <option value="All">All Statuses</option>
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Pending">Pending</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Start Date</label>
                        <input type="date" id="printStartDate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">End Date</label>
                        <input type="date" id="printEndDate" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 outline-none focus:border-blue-400">
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50">
                <button onclick="closePrintModal()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 transition-all">Cancel</button>
                <button onclick="generatePrintDocument()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-black shadow-md transition-all flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice"></i> Preview Report
                </button>
            </div>
        </div>
    </div>

    <script>
        const allCases = <?= json_encode($allCases, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        let activeFilter = "Total";
        const escapeHTML = (value) => { const el = document.createElement('span'); el.textContent = String(value ?? ''); return el.innerHTML; };

        // Profile Dropdown
        const profileBtn = document.getElementById('profileBtn');
        const dropdown = document.getElementById('profileDropdown');
        if(profileBtn) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
        }
        window.addEventListener('click', () => { if(dropdown) dropdown.classList.remove('show'); });

        // Modals
        function openModal(label) {
            activeFilter = label;
            document.getElementById('modalSearch').value = '';

            const iconEl = document.getElementById('modalIcon');
            iconEl.className = 'fa-solid text-xl';
            if(label === 'Total') iconEl.classList.add('fa-layer-group');
            else if(label === 'Open') iconEl.classList.add('fa-folder-open');
            else if(label === 'In Progress') iconEl.classList.add('fa-arrows-rotate');
            else if(label === 'Pending') iconEl.classList.add('fa-clock');
            else if(label === 'Resolved') iconEl.classList.add('fa-circle-check');
            else if(label === 'Closed') iconEl.classList.add('fa-box-archive');

            filterData();
            document.getElementById('caseModal').classList.add('modal-active');
        }

        function closeModal() { document.getElementById('caseModal').classList.remove('modal-active'); }

        function filterData() {
            const searchTerm = document.getElementById('modalSearch').value.trim().toLowerCase();
            const body = document.getElementById('modalTableBody');
            body.innerHTML = '';

            let filtered;
            if (activeFilter === 'Total') {
                filtered = allCases.filter(c => c.status !== 'Closed');
            } else {
                filtered = allCases.filter(c => c.status === activeFilter);
            }

            if (searchTerm) {
                filtered = filtered.filter(c => String(c.student_name).toLowerCase().includes(searchTerm) || String(c.case_id).toLowerCase().includes(searchTerm));
            }

            document.getElementById('modalTitle').innerText = `${activeFilter === 'Total' ? 'Active Total' : activeFilter} Cases`;
            document.getElementById('modalCount').innerText = `${filtered.length} records found`;

            if(filtered.length === 0) {
                body.innerHTML = `<tr><td colspan="5" class="py-12 text-center text-slate-400 font-medium"><i class="fa-regular fa-folder-open text-3xl mb-3 opacity-50 block"></i> No cases found for this category.</td></tr>`;
                return;
            }

            filtered.forEach(item => {
                let badge = "";
                if(item.status === 'Open') badge = `<span style="color: #0ea5e9; font-weight: bold;">Open</span>`;
                else if(item.status === 'In Progress') badge = `<span style="color: #f59e0b; font-weight: bold;">In Progress</span>`;
                else if(item.status === 'Pending') badge = `<span style="color: #ef4444; font-weight: bold;">Pending</span>`;
                else if(item.status === 'Resolved') badge = `<span style="color: #10b981; font-weight: bold;">Resolved</span>`;
                else if(item.status === 'Closed') badge = `<span style="color: #64748b; font-weight: bold;">Closed</span>`;

                body.innerHTML += `
                <tr class="border-b border-slate-50 hover:bg-slate-50/80 transition-colors group">
                    <td class="py-4 pl-2 font-black text-slate-700">CAS-${item.case_id}</td>
                    <td class="py-4 text-slate-600 font-medium">${escapeHTML(item.student_name || 'N/A')}</td>
                    <td class="py-4 text-slate-600 font-medium">${escapeHTML(item.title)}</td>
                    <td class="py-4">${badge}</td>
                    <td class="py-4 text-right text-slate-400 font-semibold text-xs">${new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                </tr>`;
            });
        }

        // --- PRINT REPORT LOGIC ---
        function openPrintModal() {
            document.getElementById('printModal').classList.add('modal-active');

            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

            document.getElementById('printStartDate').value = firstDay.toISOString().split('T')[0];
            document.getElementById('printEndDate').value = today.toISOString().split('T')[0];
        }

        function closePrintModal() {
            document.getElementById('printModal').classList.remove('modal-active');
        }

        function generatePrintDocument() {
            const statusFilter = document.getElementById('printStatus').value;
            const startDateStr = document.getElementById('printStartDate').value;
            const endDateStr = document.getElementById('printEndDate').value;

            const startTime = startDateStr ? new Date(startDateStr).setHours(0,0,0,0) : 0;
            const endTime = endDateStr ? new Date(endDateStr).setHours(23,59,59,999) : Infinity;

            const filteredPrintData = allCases.filter(c => {
                const caseTime = new Date(c.created_at).getTime();
                const isStatusMatch = (statusFilter === 'All') ? true : c.status === statusFilter;
                const isDateMatch = caseTime >= startTime && caseTime <= endTime;
                return isStatusMatch && isDateMatch;
            });

            createReportPreviewWindow(filteredPrintData, statusFilter !== 'All' ? statusFilter : 'Overall');
            closePrintModal();
        }

        function previewMonthlyReport(statusType, selectId) {
            const selectedMonth = document.getElementById(selectId).value;

            let filteredData = allCases.filter(c => c.status === statusType);

            if (selectedMonth !== "All") {
                filteredData = filteredData.filter(c => new Date(c.created_at).toLocaleString('default', { month: 'short' }) === selectedMonth);
            }

            createReportPreviewWindow(filteredData, statusType, selectedMonth);
        }

        function createReportPreviewWindow(dataList, reportType = "Overall", reportMonth = "All") {
            let csvData = "Case ID,Student ID,Student Name,Title,Status,Date Logged\n";
            dataList.forEach(item => {
                const dateFmt = new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const safeTitle = item.title.replace(/"/g, '""');
                const safeName = (item.student_name || 'N/A').replace(/"/g, '""');
                csvData += `"${item.case_id}","${escapeHTML(item.student_user_id)}","${safeName}","${safeTitle}","${escapeHTML(item.status)}","${dateFmt}"\n`;
            });
            const encodedCsv = encodeURIComponent(csvData);

            const titleAddition = reportMonth !== "All" ? ` (${reportMonth})` : '';

            let printContent = `
                <html>
                <head>
                    <title>Cases Reports & Analytics - ${reportType}</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
                        body {
                            font-family: 'Poppins', sans-serif;
                            margin: 0;
                            padding: 0;
                            color: #333;
                            background: #f1f5f9;
                        }

                        .action-bar {
                            background: #ffffff;
                            padding: 15px 25px;
                            border-bottom: 1px solid #e2e8f0;
                            display: flex;
                            justify-content: flex-end;
                            gap: 12px;
                            position: sticky;
                            top: 0;
                            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
                            z-index: 100;
                        }
                        .btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: bold; cursor: pointer; border: none; transition: 0.2s; }
                        .btn-cancel { background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; }
                        .btn-cancel:hover { background: #e2e8f0; color: #ef4444; }
                        .btn-download { background: #10b981; color: white; }
                        .btn-download:hover { background: #059669; }
                        .btn-print { background: #3b82f6; color: white; }
                        .btn-print:hover { background: #2563eb; }

                        .container {
                            padding: 40px;
                            max-width: 900px;
                            margin: 30px auto;
                            background: white;
                            min-height: 100vh;
                            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                            border-radius: 8px;
                        }

                        .header-container {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 15px;
                            margin-bottom: 20px;
                            border-bottom: 2px solid #333;
                            padding-bottom: 15px;
                        }
                        .header-logo { width: 60px; height: 60px; object-fit: contain; }
                        .header-text { text-align: left; }
                        .header-text h1 { margin: 0; font-size: 20px; color: #1e3a8a; }
                        .header-text p { margin: 0; font-size: 14px; color: #64748b; text-transform: capitalize; }

                        h2 { text-align: left; margin-top: 10px; margin-bottom: 15px; font-size: 24px; color: #333; }

                        table { border-collapse: collapse; width: 100%; border: 1px solid #333; }
                        th, td { border: 1px solid #333; padding: 10px; text-align: left; font-size: 12px; }
                        th { text-transform: uppercase; font-size: 10px; font-weight: bold; color: #333; background-color: #f8fafc; }

                        .status-open { color: #0ea5e9; font-weight: bold; }
                        .status-progress { color: #f59e0b; font-weight: bold; }
                        .status-pending { color: #ef4444; font-weight: bold; }
                        .status-resolved { color: #10b981; font-weight: bold; }
                        .status-closed { color: #64748b; font-weight: bold; }

                        @media print {
                            body { background: white; -webkit-print-color-adjust: exact; }
                            .action-bar { display: none !important; }
                            .container { box-shadow: none; padding: 0; margin: 0; max-width: 100%; border-radius: 0; }
                        }
                    </style>
                </head>
                <body>
                    <div class="action-bar no-print">
                        <button class="btn btn-cancel" onclick="window.close()">Cancel</button>
                        <button class="btn btn-download" onclick="downloadCSV()">Download CSV</button>
                        <button class="btn btn-print" onclick="window.print()">Print Report</button>
                    </div>

                    <div class="container">
                        <div class="header-container">
                            <img src="../assets/image/union_logo.jpg" alt="Logo" class="header-logo">
                            <div class="header-text">
                                <h1>PHINMA - Union College of Laguna</h1>
                                <p>Official Case Analytics Report - ${reportType} ${titleAddition}</p>
                            </div>
                        </div>

                        <h2>Cases Reports & Analytics Management</h2>

                        <table>
                            <thead>
                                <tr>
                                    <th>Case ID</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Date Created</th>
                                </tr>
                            </thead>
                            <tbody>
            `;

            if (dataList.length === 0) {
                printContent += `<tr><td colspan="6" style="text-align: center; padding: 20px;">No records found.</td></tr>`;
            } else {
                dataList.forEach(item => {
                    const dateFormatted = new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                    let statusClass = "status-open";
                    if(item.status === 'In Progress') statusClass = "status-progress";
                    if(item.status === 'Pending') statusClass = "status-pending";
                    if(item.status === 'Resolved') statusClass = "status-resolved";
                    if(item.status === 'Closed') statusClass = "status-closed";

                    printContent += `
                        <tr>
                            <td><strong>CAS-${item.case_id}</strong></td>
                            <td>${escapeHTML(item.student_user_id)}</td>
                            <td>${escapeHTML(item.student_name || 'N/A')}</td>
                            <td>${escapeHTML(item.title)}</td>
                            <td class="${statusClass}">${escapeHTML(item.status)}</td>
                            <td>${dateFormatted}</td>
                        </tr>
                    `;
                });
            }

            printContent += `
                            </tbody>
                        </table>
                    </div>

                    <script>
                        function downloadCSV() {
                            const csvContent = decodeURIComponent("${encodedCsv}");
                            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                            const link = document.createElement("a");
                            const url = URL.createObjectURL(blob);
                            link.setAttribute("href", url);
                            link.setAttribute("download", "UCL_${reportType.replace(' ', '_')}_Cases_Report.csv");
                            link.style.visibility = 'hidden';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        }
                    <\/script>
                  <script defer src="/shared/assets/js/portal.js"></script>
</body>
                </html>
            `;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
        }

        // Charts Setup
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#94a3b8';

        // REUSABLE FUNCTION PARA SA 3 INDIVIDUAL GROWTH CHARTS
        function createSimpleBar(ctxId, label, data, color) {
            new Chart(document.getElementById(ctxId), {
                type: "bar",
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: color,
                        borderRadius: 8,
                        barThickness: 24
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, suggestedMax: 5, grid: { borderDash: [5, 5], color: '#f1f5f9' }, border: { display: false }, ticks: { stepSize: 1, font: { weight: '700' } } },
                        x: { grid: { display: false }, border: { display: false }, ticks: { font: { weight: '700', size: 10 } } }
                    }
                }
            });
        }

        // Generate Bar Charts
        createSimpleBar('resolvedChart', 'Resolved Cases', <?= json_encode($counts_resolved) ?>, '#10b981');  // Green
        createSimpleBar('inProgressChart', 'In Progress Cases', <?= json_encode($counts_inprogress) ?>, '#f59e0b'); // Amber
        createSimpleBar('pendingChart', 'Pending Cases', <?= json_encode($counts_pending) ?>, '#ef4444');    // Red

        // Pie Chart
        new Chart(document.getElementById("pieChart"), {
            type: "doughnut",
            data: {
                labels: ["Open", "In Progress", "Pending", "Resolved", "Closed"],
                datasets: [{
                    data: [<?= "{$stats['Open']}, {$stats['In Progress']}, {$stats['Pending']}, {$stats['Resolved']}, {$stats['Closed']}" ?>],
                    backgroundColor: ["#38bdf8", "#f59e0b", "#ef4444", "#10b981", "#64748b"],
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 10
                }]
            },
            options: { cutout: '75%', maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { weight: 'bold', size: 11 } } } } }
        });

        function downloadChart(id, name) {
            const a = document.createElement('a');
            a.href = document.getElementById(id).toDataURL('image/png');
            a.download = `${name}_Chart.png`;
            a.click();
        }
    </script>
</body>
</html>
