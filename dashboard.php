<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDB();

// Stats
$total = $db->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']}")->fetch_assoc()['c'];
$pending = $db->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']} AND status='pending'")->fetch_assoc()['c'];
$inprog  = $db->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']} AND status='in_progress'")->fetch_assoc()['c'];
$done    = $db->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']} AND (status='completed' OR status='ready')")->fetch_assoc()['c'];

// Recent tickets
$recentQ = $db->query("SELECT * FROM tickets WHERE user_id={$user['id']} ORDER BY created_at DESC LIMIT 10");
$tickets = $recentQ->fetch_all(MYSQLI_ASSOC);

// Notifications
$notifs = $db->query("SELECT * FROM notifications WHERE user_id={$user['id']} ORDER BY created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);
$unreadCount = $db->query("SELECT COUNT(*) c FROM notifications WHERE user_id={$user['id']} AND is_read=0")->fetch_assoc()['c'];

$db->close();

// Active page
$page = $_GET['page'] ?? 'dashboard';
$requestTypes = [
    'grades'          => ['label'=>'Grade Report','icon'=>'fas fa-star-of-life','color'=>'#007aff'],
    'permit'          => ['label'=>'Enrollment Permit','icon'=>'fas fa-id-badge','color'=>'#34c759'],
    'tor'             => ['label'=>'Transcript of Records (TOR)','icon'=>'fas fa-scroll','color'=>'#f5a623'],
    'soa'             => ['label'=>'Statement of Account','icon'=>'fas fa-receipt','color'=>'#e94560'],
    'study_load'      => ['label'=>'Study Load','icon'=>'fas fa-calendar-days','color'=>'#af52de'],
    'form137'         => ['label'=>'Form 137','icon'=>'fas fa-file-lines','color'=>'#ff9500'],
    'form138'         => ['label'=>'Form 138','icon'=>'fas fa-file-certificate','color'=>'#32ade6'],
    'coe'             => ['label'=>'Certificate of Enrollment','icon'=>'fas fa-certificate','color'=>'#34c759'],
    'good_moral'      => ['label'=>'Good Moral Certificate','icon'=>'fas fa-award','color'=>'#ff3b30'],
    'honorable'       => ['label'=>'Honorable Dismissal','icon'=>'fas fa-handshake','color'=>'#ff9f0a'],
    'diploma'         => ['label'=>'Diploma Replacement','icon'=>'fas fa-user-graduate','color'=>'#5e5ce6'],
    'other'           => ['label'=>'Other Request','icon'=>'fas fa-ellipsis','color'=>'#8892a4'],
];

function statusBadge($s) {
    $map = [
        'pending'     => ['bg'=>'rgba(245,166,35,0.15)','color'=>'#f5a623','label'=>'Pending','icon'=>'fa-clock'],
        'in_progress' => ['bg'=>'rgba(0,122,255,0.15)','color'=>'#007aff','label'=>'In Progress','icon'=>'fa-spinner'],
        'ready'       => ['bg'=>'rgba(52,199,89,0.15)','color'=>'#34c759','label'=>'Ready for Pickup','icon'=>'fa-circle-check'],
        'completed'   => ['bg'=>'rgba(88,86,214,0.15)','color'=>'#5e5ce6','label'=>'Completed','icon'=>'fa-check-double'],
        'rejected'    => ['bg'=>'rgba(255,59,48,0.15)','color'=>'#ff3b30','label'=>'Rejected','icon'=>'fa-xmark'],
    ];
    $d = $map[$s] ?? $map['pending'];
    return "<span style='background:{$d['bg']};color:{$d['color']};padding:4px 12px;border-radius:50px;font-size:0.75rem;font-weight:600;'><i class='fas {$d['icon']}' style='margin-right:5px'></i>{$d['label']}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Registrar Ticketing System</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d1117;
            --secondary: #161b22;
            --sidebar: #13171f;
            --card: #1c2128;
            --card2: #21262d;
            --gold: #e94560;
            --gold2: #f5a623;
            --blue: #007aff;
            --green: #34c759;
            --purple: #af52de;
            --white: #e6edf3;
            --gray: #7d8590;
            --border: rgba(255,255,255,0.06);
            --hover: rgba(255,255,255,0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--primary);
            color: var(--white);
            display: flex; min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px; flex-shrink: 0;
            background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 50; transition: transform 0.3s;
        }

        .sidebar-logo {
            padding: 1.5rem 1.5rem 1rem;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--border);
            text-decoration: none;
        }
        .logo-box {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }
        .logo-text {
            font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800;
            color: var(--white);
        }
        .logo-sub { font-size: 0.7rem; color: var(--gray); font-weight: 400; }

        .sidebar-user {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1rem;
            color: white; flex-shrink: 0;
        }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-name { font-size: 0.88rem; font-weight: 600; color: var(--white); }
        .user-id { font-size: 0.75rem; color: var(--gray); }
        .user-role {
            display: inline-block; font-size: 0.68rem; padding: 2px 8px;
            background: rgba(233,69,96,0.15); color: var(--gold);
            border-radius: 50px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
            margin-top: 2px;
        }

        .nav-section {
            padding: 0.8rem 1rem 0.3rem 1.5rem;
            font-size: 0.68rem; text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--gray); font-weight: 700;
        }

        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 0.65rem 1rem 0.65rem 1.5rem; margin: 1px 0.5rem;
            border-radius: 8px; text-decoration: none;
            color: var(--gray); font-size: 0.875rem; font-weight: 500;
            transition: all 0.2s; cursor: pointer;
        }
        .nav-item:hover { background: var(--hover); color: var(--white); }
        .nav-item.active { background: rgba(233,69,96,0.12); color: var(--gold); border: 1px solid rgba(233,69,96,0.15); }
        .nav-item i { width: 18px; text-align: center; font-size: 0.9rem; }
        .nav-badge {
            margin-left: auto; background: var(--gold); color: white;
            font-size: 0.65rem; font-weight: 800; padding: 2px 7px;
            border-radius: 50px; min-width: 20px; text-align: center;
        }

        .sidebar-bottom { margin-top: auto; padding: 1rem; border-top: 1px solid var(--border); }
        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 0.65rem 1rem; border-radius: 8px;
            color: var(--gray); font-size: 0.875rem; font-weight: 500;
            text-decoration: none; transition: all 0.2s;
            background: none; border: none; cursor: pointer; width: 100%;
        }
        .logout-btn:hover { background: rgba(255,59,48,0.1); color: #ff3b30; }

        /* MAIN */
        .main {
            margin-left: 260px; flex: 1; display: flex; flex-direction: column;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            height: 60px; padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            background: var(--secondary); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 40;
        }

        .topbar-left { display: flex; align-items: center; gap: 1rem; }
        .menu-toggle {
            display: none; background: none; border: none;
            color: var(--gray); font-size: 1.1rem; cursor: pointer; padding: 4px;
        }
        .page-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--white); }
        .breadcrumb { font-size: 0.78rem; color: var(--gray); margin-top: 1px; }

        .topbar-right { display: flex; align-items: center; gap: 1rem; }

        .notif-btn {
            position: relative; background: none; border: none;
            color: var(--gray); font-size: 1.1rem; cursor: pointer;
            padding: 6px; border-radius: 8px; transition: all 0.2s;
        }
        .notif-btn:hover { background: var(--hover); color: var(--white); }
        .notif-dot {
            position: absolute; top: 3px; right: 3px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--gold); border: 2px solid var(--secondary);
        }

        /* CONTENT AREA */
        .content { flex: 1; padding: 2rem; overflow-y: auto; }

        /* STAT CARDS */
        .stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem; margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; padding: 1.4rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            transition: all 0.3s;
        }
        .stat-card:hover { border-color: rgba(255,255,255,0.12); transform: translateY(-2px); }
        .stat-val { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--white); }
        .stat-lbl { font-size: 0.8rem; color: var(--gray); font-weight: 500; margin-top: 2px; }
        .stat-icon-box {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }

        /* CARDS */
        .card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden;
        }
        .card-header {
            padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; }
        .card-body { padding: 1.5rem; }

        /* REQUEST GRID */
        .request-types {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }
        .req-type-card {
            background: var(--card2); border: 1px solid var(--border);
            border-radius: 12px; padding: 1.3rem 1rem; text-align: center;
            cursor: pointer; transition: all 0.3s;
            text-decoration: none; color: var(--white);
        }
        .req-type-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .req-type-icon {
            font-size: 1.6rem; margin-bottom: 0.7rem;
            display: block;
        }
        .req-type-label { font-size: 0.8rem; font-weight: 600; line-height: 1.3; }

        /* TABLE */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 0.75rem 1rem; text-align: left;
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--gray); font-weight: 700; border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: 0.85rem 1rem; border-bottom: 1px solid var(--border);
            font-size: 0.875rem; color: rgba(230,237,243,0.85);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--hover); }
        .ticket-num { font-family: 'Syne', sans-serif; font-size: 0.78rem; font-weight: 700; color: var(--gold); }

        /* FORM */
        .form-section { display: none; }
        .form-section.active { display: block; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
        @media (max-width: 700px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group-d { margin-bottom: 0; }
        .form-group-d label {
            display: block; font-size: 0.8rem; font-weight: 600;
            color: rgba(230,237,243,0.6); margin-bottom: 0.4rem; letter-spacing: 0.3px;
        }
        .form-group-d input, .form-group-d select, .form-group-d textarea {
            width: 100%; padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.04); border: 1.5px solid var(--border);
            border-radius: 10px; color: var(--white); font-size: 0.88rem;
            font-family: 'DM Sans', sans-serif; transition: all 0.3s; outline: none;
        }
        .form-group-d input:focus, .form-group-d select:focus, .form-group-d textarea:focus {
            border-color: var(--gold); background: rgba(233,69,96,0.05);
        }
        .form-group-d select option { background: var(--secondary); }
        .form-group-d textarea { resize: vertical; min-height: 80px; }
        .form-group-d input::placeholder, .form-group-d textarea::placeholder { color: rgba(255,255,255,0.2); }
        .form-full { grid-column: 1 / -1; }

        .submit-btn {
            background: linear-gradient(135deg, var(--gold), #c73652);
            border: none; padding: 0.85rem 2rem; border-radius: 10px;
            color: white; font-size: 0.9rem; font-weight: 700;
            font-family: 'Syne', sans-serif; cursor: pointer; transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(233,69,96,0.3);
        }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(233,69,96,0.4); }

        .cancel-btn {
            background: var(--card2); border: 1px solid var(--border); padding: 0.85rem 2rem;
            border-radius: 10px; color: var(--gray); font-size: 0.9rem; font-weight: 600;
            font-family: 'DM Sans', sans-serif; cursor: pointer; transition: all 0.2s;
            margin-right: 0.8rem;
        }
        .cancel-btn:hover { border-color: rgba(255,255,255,0.15); color: var(--white); }

        /* NOTIFICATIONS PANEL */
        .notif-panel {
            position: fixed; top: 60px; right: 0; width: 360px; height: calc(100vh - 60px);
            background: var(--secondary); border-left: 1px solid var(--border);
            z-index: 200; transform: translateX(100%); transition: transform 0.3s;
            overflow-y: auto;
        }
        .notif-panel.open { transform: translateX(0); }
        .notif-panel-header {
            padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0;
            background: var(--secondary);
        }
        .notif-panel-title { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; }
        .close-notif { background: none; border: none; color: var(--gray); cursor: pointer; font-size: 1rem; }

        .notif-item {
            padding: 1rem 1.5rem; border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }
        .notif-item:hover { background: var(--hover); }
        .notif-item.unread { background: rgba(233,69,96,0.04); }
        .notif-msg { font-size: 0.85rem; color: rgba(230,237,243,0.8); line-height: 1.5; margin-bottom: 4px; }
        .notif-time { font-size: 0.75rem; color: var(--gray); }

        /* Profile card */
        .profile-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; padding: 2rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;
        }
        .profile-avatar-lg {
            width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--gold), var(--gold2));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2rem; color: white;
        }
        .profile-info h2 { font-family: 'Syne', sans-serif; font-size: 1.4rem; font-weight: 800; }
        .profile-info p { color: var(--gray); font-size: 0.88rem; margin-top: 2px; }
        .profile-meta { display: flex; gap: 1.5rem; margin-top: 0.8rem; flex-wrap: wrap; }
        .meta-item { font-size: 0.82rem; }
        .meta-item strong { color: var(--white); font-weight: 600; }
        .meta-item span { color: var(--gray); display: block; font-size: 0.72rem; margin-top: 1px; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .menu-toggle { display: block; }
            .form-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr 1fr; }
        }

        /* Overlay */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 45;
        }
        .overlay.show { display: block; }

        .empty-state {
            text-align: center; padding: 3rem; color: var(--gray);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.4; display: block; }
        .empty-state p { font-size: 0.9rem; }

        .alert-d {
            padding: 0.85rem 1rem; border-radius: 10px;
            font-size: 0.88rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        .alert-success-d { background: rgba(52,199,89,0.1); border: 1px solid rgba(52,199,89,0.25); color: #34c759; }
        .alert-error-d { background: rgba(233,69,96,0.12); border: 1px solid rgba(233,69,96,0.25); color: #ff6b87; }
    </style>
</head>
<body>

<?php
// Handle new ticket submission
$ticketSuccess = '';
$ticketError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $rtype  = $_POST['request_type'] ?? '';
    $purpose= trim($_POST['purpose'] ?? '');
    $sem    = $_POST['semester'] ?? '';
    $sy     = trim($_POST['school_year'] ?? '');
    $copies = max(1, intval($_POST['copies'] ?? 1));
    $notes  = trim($_POST['notes'] ?? '');

    if (empty($rtype) || empty($purpose)) {
        $ticketError = 'Please fill in all required fields.';
    } else {
        $db2 = getDB();
        $tnum = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        $uid5 = intval($user['id']);
        $copies_int = intval($copies);
        $stmt5 = $db2->prepare("INSERT INTO tickets (ticket_number, user_id, request_type, purpose, semester, school_year, copies, notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt5->bind_param("sissssis", $tnum, $uid5, $rtype, $purpose, $sem, $sy, $copies_int, $notes);
        if ($stmt5->execute()) {
            // notify
            $notifMsg = "Your request for '{$requestTypes[$rtype]['label']}' has been submitted successfully. Ticket: $tnum";
            $notifStmt = $db2->prepare("INSERT INTO notifications (user_id, ticket_id, message) VALUES (?,?,?)");
            $lastId = $stmt5->insert_id;
            $notifStmt->bind_param("iis", $uid5, $lastId, $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
            $ticketSuccess = "Ticket submitted successfully! Your ticket number is <strong>$tnum</strong>";
            $page = 'my_requests';
        } else {
            $ticketError = 'Failed to submit ticket. Please try again.';
        }
        $stmt5->close();
        $db2->close();

        // Refresh tickets
        $db3 = getDB();
        $recentQ3 = $db3->query("SELECT * FROM tickets WHERE user_id={$user['id']} ORDER BY created_at DESC LIMIT 10");
        $tickets = $recentQ3->fetch_all(MYSQLI_ASSOC);
        $total = $db3->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']}")->fetch_assoc()['c'];
        $pending = $db3->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']} AND status='pending'")->fetch_assoc()['c'];
        $done = $db3->query("SELECT COUNT(*) c FROM tickets WHERE user_id={$user['id']} AND (status='completed' OR status='ready')")->fetch_assoc()['c'];
        $db3->close();
    }
}
?>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <a href="index.php" class="sidebar-logo">
        <div class="logo-box"><i class="fas fa-graduation-cap"></i></div>
        <div>
            <div class="logo-text">RTS Portal</div>
            <div class="logo-sub">Registrar System</div>
        </div>
    </a>

    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="user-id"><?= htmlspecialchars($user['student_id']) ?></div>
                <div class="user-role"><?= ucfirst($user['role']) ?></div>
            </div>
        </div>
    </div>

    <nav style="flex:1;overflow-y:auto;padding:0.5rem 0">
        <div class="nav-section">Main</div>
        <a href="?page=dashboard" class="nav-item <?= $page=='dashboard'?'active':'' ?>">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
        <a href="?page=new_request" class="nav-item <?= $page=='new_request'?'active':'' ?>">
            <i class="fas fa-plus-circle"></i> New Request
        </a>
        <a href="?page=my_requests" class="nav-item <?= $page=='my_requests'?'active':'' ?>">
            <i class="fas fa-ticket"></i> My Requests
            <?php if ($pending > 0): ?>
            <span class="nav-badge"><?= $pending ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section" style="margin-top:0.5rem">Quick Requests</div>
        <?php foreach (array_slice($requestTypes, 0, 7, true) as $key => $rt): ?>
        <a href="?page=new_request&type=<?= $key ?>" class="nav-item">
            <i class="<?= $rt['icon'] ?>" style="color:<?= $rt['color'] ?>"></i>
            <span style="font-size:0.82rem"><?= $rt['label'] ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-section" style="margin-top:0.5rem">Account</div>
        <a href="?page=profile" class="nav-item <?= $page=='profile'?'active':'' ?>">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
        <?php if ($user['role'] === 'admin'): ?>
        <a href="admin.php" class="nav-item">
            <i class="fas fa-shield-halved"></i> Admin Panel
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div>
                <div class="page-title">
                    <?php
                    $titles = [
                        'dashboard'   => 'Dashboard',
                        'new_request' => 'New Request',
                        'my_requests' => 'My Requests',
                        'profile'     => 'My Profile',
                    ];
                    echo $titles[$page] ?? 'Dashboard';
                    ?>
                </div>
                <div class="breadcrumb">RTS Portal / <?= $titles[$page] ?? 'Dashboard' ?></div>
            </div>
        </div>
        <div class="topbar-right">
            <button class="notif-btn" onclick="toggleNotif()">
                <i class="fas fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                <span class="notif-dot"></span>
                <?php endif; ?>
            </button>
            <div style="font-size:0.82rem;color:var(--gray)">
                <?= date('D, M d Y') ?>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION PANEL -->
    <div class="notif-panel" id="notifPanel">
        <div class="notif-panel-header">
            <div class="notif-panel-title">
                <i class="fas fa-bell" style="margin-right:8px;color:var(--gold)"></i>
                Notifications
                <?php if ($unreadCount > 0): ?>
                <span style="background:var(--gold);color:white;font-size:0.7rem;padding:2px 7px;border-radius:50px;margin-left:6px"><?= $unreadCount ?></span>
                <?php endif; ?>
            </div>
            <button class="close-notif" onclick="toggleNotif()"><i class="fas fa-times"></i></button>
        </div>
        <?php if (empty($notifs)): ?>
        <div class="empty-state" style="padding:2rem">
            <i class="fas fa-bell-slash" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.8rem"></i>
            <p style="font-size:0.85rem;color:var(--gray)">No notifications yet.</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifs as $n): ?>
        <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
            <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
            <div class="notif-time"><i class="fas fa-clock" style="margin-right:4px"></i><?= date('M d, Y g:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <?php if ($ticketSuccess): ?>
        <div class="alert-d alert-success-d">
            <i class="fas fa-circle-check"></i> <?= $ticketSuccess ?>
        </div>
        <?php endif; ?>
        <?php if ($ticketError): ?>
        <div class="alert-d alert-error-d">
            <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($ticketError) ?>
        </div>
        <?php endif; ?>

        <!-- ===== DASHBOARD ===== -->
        <?php if ($page === 'dashboard'): ?>

        <div style="margin-bottom:2rem">
            <h1 style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800">
                Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 👋
            </h1>
            <p style="color:var(--gray);margin-top:4px;font-size:0.9rem">Here's an overview of your document requests.</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $total ?></div>
                    <div class="stat-lbl">Total Requests</div>
                </div>
                <div class="stat-icon-box" style="background:rgba(0,122,255,0.12)">
                    <i class="fas fa-ticket" style="color:#007aff"></i>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $pending ?></div>
                    <div class="stat-lbl">Pending</div>
                </div>
                <div class="stat-icon-box" style="background:rgba(245,166,35,0.12)">
                    <i class="fas fa-clock" style="color:#f5a623"></i>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $inprog ?></div>
                    <div class="stat-lbl">In Progress</div>
                </div>
                <div class="stat-icon-box" style="background:rgba(175,82,222,0.12)">
                    <i class="fas fa-spinner" style="color:#af52de"></i>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $done ?></div>
                    <div class="stat-lbl">Completed</div>
                </div>
                <div class="stat-icon-box" style="background:rgba(52,199,89,0.12)">
                    <i class="fas fa-circle-check" style="color:#34c759"></i>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem" class="dash-grid">
            <div class="card" style="grid-column:1/-1">
                <div class="card-header">
                    <span class="card-title"><i class="fas fa-history" style="margin-right:8px;color:var(--gold)"></i>Recent Requests</span>
                    <a href="?page=my_requests" style="font-size:0.8rem;color:var(--gold);text-decoration:none">View All →</a>
                </div>
                <div class="table-wrap">
                    <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No requests yet. <a href="?page=new_request" style="color:var(--gold)">Submit your first request →</a></p>
                    </div>
                    <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Request Type</th>
                                <th>Purpose</th>
                                <th>Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($tickets, 0, 5) as $t): ?>
                            <tr>
                                <td><span class="ticket-num"><?= htmlspecialchars($t['ticket_number']) ?></span></td>
                                <td><?= htmlspecialchars($requestTypes[$t['request_type']]['label'] ?? $t['request_type']) ?></td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($t['purpose']) ?></td>
                                <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                                <td><?= statusBadge($t['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-grid-2" style="margin-right:8px;color:var(--gold)"></i>Quick Request</span>
            </div>
            <div class="card-body">
                <div class="request-types">
                    <?php foreach ($requestTypes as $key => $rt): ?>
                    <a href="?page=new_request&type=<?= $key ?>" class="req-type-card" style="border-color:transparent"
                       onmouseover="this.style.borderColor='<?= $rt['color'] ?>';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.3)'"
                       onmouseout="this.style.borderColor='transparent';this.style.boxShadow='none'">
                        <i class="<?= $rt['icon'] ?> req-type-icon" style="color:<?= $rt['color'] ?>"></i>
                        <div class="req-type-label"><?= $rt['label'] ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ===== NEW REQUEST ===== -->
        <?php elseif ($page === 'new_request'): ?>

        <div style="margin-bottom:2rem">
            <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800">Submit a New Request</h1>
            <p style="color:var(--gray);margin-top:4px;font-size:0.88rem">Select the document you need and fill out the form below.</p>
        </div>

        <?php $selType = $_GET['type'] ?? ''; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:0.8rem;margin-bottom:2rem">
            <?php foreach ($requestTypes as $key => $rt): ?>
            <div class="req-type-card type-selector <?= $selType===$key?'selected':'' ?>"
                 onclick="selectType('<?= $key ?>')"
                 id="type_<?= $key ?>"
                 style="<?= $selType===$key?"border-color:{$rt['color']};background:rgba(255,255,255,0.06)":'' ?>">
                <i class="<?= $rt['icon'] ?> req-type-icon" style="color:<?= $rt['color'] ?>"></i>
                <div class="req-type-label"><?= $rt['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div id="requestForm" style="<?= empty($selType)?'display:none':'' ?>">
            <div class="card">
                <div class="card-header">
                    <span class="card-title" id="formTitle">Request Form</span>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="submit_ticket" value="1">
                        <input type="hidden" name="request_type" id="request_type_field" value="<?= htmlspecialchars($selType) ?>">
                        <div class="form-grid">
                            <div class="form-group-d">
                                <label>Purpose / Reason for Request *</label>
                                <input type="text" name="purpose" placeholder="e.g. For employment, board exam, etc." required>
                            </div>
                            <div class="form-group-d">
                                <label>Number of Copies</label>
                                <input type="number" name="copies" value="1" min="1" max="10">
                            </div>
                            <div class="form-group-d">
                                <label>Semester</label>
                                <select name="semester">
                                    <option value="">Select Semester</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            <div class="form-group-d">
                                <label>School Year</label>
                                <input type="text" name="school_year" placeholder="e.g. 2024–2025">
                            </div>
                            <div class="form-group-d form-full">
                                <label>Additional Notes / Special Instructions</label>
                                <textarea name="notes" placeholder="Any additional information for the registrar..."></textarea>
                            </div>
                        </div>
                        <div style="margin-top:1.5rem;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                            <button type="button" class="cancel-btn" onclick="clearType()">Cancel</button>
                            <button type="submit" class="submit-btn">
                                <i class="fas fa-paper-plane" style="margin-right:8px"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="noTypeMsg" style="<?= !empty($selType)?'display:none':'' ?>">
            <div class="card">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-hand-pointer"></i>
                        <p>Please select a request type above to continue.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MY REQUESTS ===== -->
        <?php elseif ($page === 'my_requests'): ?>

        <div style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800">My Requests</h1>
                <p style="color:var(--gray);margin-top:4px;font-size:0.88rem">All your document request tickets.</p>
            </div>
            <a href="?page=new_request" class="submit-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px">
                <i class="fas fa-plus"></i> New Request
            </a>
        </div>

        <!-- Filter -->
        <div style="display:flex;gap:0.6rem;margin-bottom:1.5rem;flex-wrap:wrap">
            <?php
            $filter = $_GET['filter'] ?? 'all';
            $fbuttons = ['all'=>'All','pending'=>'Pending','in_progress'=>'In Progress','ready'=>'Ready','completed'=>'Completed','rejected'=>'Rejected'];
            foreach ($fbuttons as $fk => $fl) {
                $act = $filter === $fk;
                echo "<a href='?page=my_requests&filter=$fk' style='padding:0.45rem 1rem;border-radius:8px;font-size:0.8rem;font-weight:600;text-decoration:none;border:1px solid;transition:all 0.2s;" .
                     ($act ? "background:rgba(233,69,96,0.12);color:var(--gold);border-color:rgba(233,69,96,0.25)" : "background:var(--card);color:var(--gray);border-color:var(--border)") .
                     "'>$fl</a>";
            }
            ?>
        </div>

        <div class="card">
            <div class="table-wrap">
                <?php
                $db4 = getDB();
                $filterWhere = $filter !== 'all' ? "AND status='$filter'" : '';
                $allTickets = $db4->query("SELECT * FROM tickets WHERE user_id={$user['id']} $filterWhere ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
                $db4->close();
                ?>
                <?php if (empty($allTickets)): ?>
                <div class="empty-state" style="padding:3rem">
                    <i class="fas fa-inbox" style="font-size:2.5rem;opacity:0.3;margin-bottom:1rem;display:block"></i>
                    <p>No requests found. <a href="?page=new_request" style="color:var(--gold)">Submit a new request →</a></p>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Type</th>
                            <th>Purpose</th>
                            <th>Copies</th>
                            <th>School Year</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTickets as $t): ?>
                        <tr>
                            <td><span class="ticket-num"><?= htmlspecialchars($t['ticket_number']) ?></span></td>
                            <td>
                                <?php $rt = $requestTypes[$t['request_type']] ?? ['label'=>$t['request_type'],'icon'=>'fas fa-file','color'=>'#888']; ?>
                                <span style="display:flex;align-items:center;gap:6px">
                                    <i class="<?= $rt['icon'] ?>" style="color:<?= $rt['color'] ?>"></i>
                                    <?= htmlspecialchars($rt['label']) ?>
                                </span>
                            </td>
                            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($t['purpose']) ?></td>
                            <td><?= $t['copies'] ?></td>
                            <td><?= htmlspecialchars($t['school_year'] ?: '—') ?></td>
                            <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                            <td><?= statusBadge($t['status']) ?></td>
                            <td style="max-width:150px;font-size:0.8rem;color:var(--gray)"><?= htmlspecialchars($t['admin_remarks'] ?: '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== PROFILE ===== -->
        <?php elseif ($page === 'profile'): ?>

        <div style="margin-bottom:1.5rem">
            <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800">My Profile</h1>
            <p style="color:var(--gray);margin-top:4px;font-size:0.88rem">Your account information and activity.</p>
        </div>

        <div class="profile-card">
            <div class="profile-avatar-lg"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['name']) ?></h2>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <div class="profile-meta">
                    <div class="meta-item">
                        <strong><?= htmlspecialchars($user['student_id']) ?></strong>
                        <span>Student ID</span>
                    </div>
                    <div class="meta-item">
                        <strong><?= htmlspecialchars($user['course'] ?: 'N/A') ?></strong>
                        <span>Course / Program</span>
                    </div>
                    <div class="meta-item">
                        <strong><?= htmlspecialchars($user['year_level'] ?: 'N/A') ?></strong>
                        <span>Year Level</span>
                    </div>
                    <div class="meta-item">
                        <strong><?= ucfirst($user['role']) ?></strong>
                        <span>Account Type</span>
                    </div>
                    <div class="meta-item">
                        <strong><?= $total ?></strong>
                        <span>Total Requests</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
            <div class="card">
                <div class="card-header"><span class="card-title">Request Summary</span></div>
                <div class="card-body">
                    <?php
                    $statRows = [
                        ['label'=>'Pending','val'=>$pending,'color'=>'#f5a623'],
                        ['label'=>'In Progress','val'=>$inprog,'color'=>'#007aff'],
                        ['label'=>'Completed / Ready','val'=>$done,'color'=>'#34c759'],
                        ['label'=>'Total','val'=>$total,'color'=>'#e94560'],
                    ];
                    foreach ($statRows as $sr):
                    ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0.7rem 0;border-bottom:1px solid var(--border)">
                        <span style="font-size:0.88rem;color:var(--gray)"><?= $sr['label'] ?></span>
                        <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:<?= $sr['color'] ?>"><?= $sr['val'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><span class="card-title">Quick Actions</span></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:0.8rem">
                    <a href="?page=new_request" style="display:flex;align-items:center;gap:10px;padding:0.8rem;background:var(--card2);border:1px solid var(--border);border-radius:10px;text-decoration:none;color:var(--white);font-size:0.88rem;transition:all 0.2s" onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--border)'">
                        <i class="fas fa-plus-circle" style="color:var(--gold)"></i> Submit New Request
                    </a>
                    <a href="?page=my_requests" style="display:flex;align-items:center;gap:10px;padding:0.8rem;background:var(--card2);border:1px solid var(--border);border-radius:10px;text-decoration:none;color:var(--white);font-size:0.88rem;transition:all 0.2s" onmouseover="this.style.borderColor='var(--blue)'" onmouseout="this.style.borderColor='var(--border)'">
                        <i class="fas fa-list" style="color:var(--blue)"></i> View All My Requests
                    </a>
                    <a href="logout.php" style="display:flex;align-items:center;gap:10px;padding:0.8rem;background:var(--card2);border:1px solid var(--border);border-radius:10px;text-decoration:none;color:var(--white);font-size:0.88rem;transition:all 0.2s" onmouseover="this.style.borderColor='#ff3b30'" onmouseout="this.style.borderColor='var(--border)'">
                        <i class="fas fa-sign-out-alt" style="color:#ff3b30"></i> Sign Out
                    </a>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div><!-- /content -->
</div><!-- /main -->

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
}
function toggleNotif() {
    document.getElementById('notifPanel').classList.toggle('open');
}

<?php $rtJson = json_encode($requestTypes); ?>
const requestTypes = <?= $rtJson ?>;

function selectType(key) {
    // Clear all
    document.querySelectorAll('.type-selector').forEach(el => {
        el.style.borderColor = 'transparent';
        el.style.background = '';
    });

    const el = document.getElementById('type_' + key);
    const rt = requestTypes[key];
    if (el && rt) {
        el.style.borderColor = rt.color;
        el.style.background = 'rgba(255,255,255,0.06)';
    }

    document.getElementById('request_type_field').value = key;
    document.getElementById('requestForm').style.display = 'block';
    document.getElementById('noTypeMsg').style.display = 'none';
    if (rt) {
        document.getElementById('formTitle').innerHTML = `<i class="${rt.icon}" style="margin-right:8px;color:${rt.color}"></i>${rt.label} — Request Form`;
    }
    document.getElementById('requestForm').scrollIntoView({behavior:'smooth',block:'start'});
}

function clearType() {
    document.querySelectorAll('.type-selector').forEach(el => {
        el.style.borderColor = 'transparent';
        el.style.background = '';
    });
    document.getElementById('request_type_field').value = '';
    document.getElementById('requestForm').style.display = 'none';
    document.getElementById('noTypeMsg').style.display = 'block';
}

// Auto init if type in URL
<?php if (!empty($selType)): ?>
document.addEventListener('DOMContentLoaded', () => selectType('<?= $selType ?>'));
<?php endif; ?>
</script>

</body>
</html>