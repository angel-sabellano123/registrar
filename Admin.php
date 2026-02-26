<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$user = $_SESSION['user'];
$db = getDB();
$msg = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $tid     = intval($_POST['ticket_id']);
    $status  = $_POST['status'];
    $remarks = trim($_POST['remarks'] ?? '');
    $allowed = ['pending','in_progress','ready','completed','rejected'];
    if (in_array($status, $allowed)) {
        $stmt = $db->prepare("UPDATE tickets SET status=?, admin_remarks=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $remarks, $tid);
        $stmt->execute();
        $stmt->close();

        // Get ticket owner
        $trow = $db->query("SELECT * FROM tickets WHERE id=$tid")->fetch_assoc();
        if ($trow) {
            $statusLabels = ['pending'=>'Pending','in_progress'=>'In Progress','ready'=>'Ready for Pickup','completed'=>'Completed','rejected'=>'Rejected'];
            $lbl = $statusLabels[$status] ?? $status;
            $nm = "Your ticket {$trow['ticket_number']} status has been updated to: $lbl." . ($remarks ? " Remarks: $remarks" : '');
            $ns = $db->prepare("INSERT INTO notifications (user_id, ticket_id, message) VALUES (?,?,?)");
            $ns->bind_param("iis", $trow['user_id'], $tid, $nm);
            $ns->execute(); $ns->close();
        }
        $msg = 'Ticket status updated successfully.';
    }
}

// Stats
$totalTickets  = $db->query("SELECT COUNT(*) c FROM tickets")->fetch_assoc()['c'];
$pendingCount  = $db->query("SELECT COUNT(*) c FROM tickets WHERE status='pending'")->fetch_assoc()['c'];
$inProgCount   = $db->query("SELECT COUNT(*) c FROM tickets WHERE status='in_progress'")->fetch_assoc()['c'];
$totalUsers    = $db->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'];

$filter = $_GET['filter'] ?? 'all';
$filterWhere = $filter !== 'all' ? "WHERE t.status='$filter'" : "WHERE 1=1";
$search = trim($_GET['s'] ?? '');
if ($search) $filterWhere .= " AND (t.ticket_number LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR t.request_type LIKE '%$search%')";

$tickets = $db->query("SELECT t.*, u.first_name, u.last_name, u.student_id, u.email, u.course
    FROM tickets t JOIN users u ON t.user_id = u.id
    $filterWhere ORDER BY t.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$db->close();

$requestLabels = [
    'grades'=>'Grade Report','permit'=>'Enrollment Permit','tor'=>'TOR',
    'soa'=>'Statement of Account','study_load'=>'Study Load',
    'form137'=>'Form 137','form138'=>'Form 138','coe'=>'COE',
    'good_moral'=>'Good Moral','honorable'=>'Honorable Dismissal',
    'diploma'=>'Diploma Replacement','other'=>'Other',
];

function sBadge($s) {
    $map = [
        'pending'     => ['bg'=>'rgba(245,166,35,0.15)','color'=>'#f5a623','label'=>'Pending'],
        'in_progress' => ['bg'=>'rgba(0,122,255,0.15)','color'=>'#007aff','label'=>'In Progress'],
        'ready'       => ['bg'=>'rgba(52,199,89,0.15)','color'=>'#34c759','label'=>'Ready'],
        'completed'   => ['bg'=>'rgba(88,86,214,0.15)','color'=>'#5e5ce6','label'=>'Completed'],
        'rejected'    => ['bg'=>'rgba(255,59,48,0.15)','color'=>'#ff3b30','label'=>'Rejected'],
    ];
    $d = $map[$s] ?? $map['pending'];
    return "<span style='background:{$d['bg']};color:{$d['color']};padding:3px 10px;border-radius:50px;font-size:0.72rem;font-weight:700'>{$d['label']}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — RTS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d1117; --secondary: #161b22; --sidebar: #13171f;
            --card: #1c2128; --card2: #21262d; --gold: #e94560; --gold2: #f5a623;
            --blue: #007aff; --green: #34c759; --white: #e6edf3;
            --gray: #7d8590; --border: rgba(255,255,255,0.06); --hover: rgba(255,255,255,0.04);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--primary); color:var(--white); display:flex; min-height:100vh; }

        .sidebar {
            width:240px; flex-shrink:0; background:var(--sidebar);
            border-right:1px solid var(--border); display:flex; flex-direction:column;
            position:fixed; top:0; left:0; bottom:0; z-index:50;
        }
        .sidebar-logo {
            padding:1.4rem 1.4rem 1rem; display:flex; align-items:center; gap:10px;
            border-bottom:1px solid var(--border); text-decoration:none;
        }
        .logo-box { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--gold),var(--gold2)); display:flex; align-items:center; justify-content:center; font-size:1rem; }
        .logo-text { font-family:'Syne',sans-serif; font-size:0.95rem; font-weight:800; color:var(--white); }
        .logo-sub { font-size:0.68rem; color:var(--gray); }
        .nav-section { padding:0.8rem 1rem 0.3rem 1.4rem; font-size:0.65rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--gray); font-weight:700; }
        .nav-item { display:flex; align-items:center; gap:10px; padding:0.6rem 1rem 0.6rem 1.4rem; margin:1px 0.5rem; border-radius:8px; text-decoration:none; color:var(--gray); font-size:0.85rem; font-weight:500; transition:all 0.2s; cursor:pointer; }
        .nav-item:hover { background:var(--hover); color:var(--white); }
        .nav-item.active { background:rgba(233,69,96,0.12); color:var(--gold); border:1px solid rgba(233,69,96,0.15); }
        .nav-item i { width:18px; text-align:center; font-size:0.88rem; }
        .sidebar-bottom { margin-top:auto; padding:1rem; border-top:1px solid var(--border); }
        .logout-btn { display:flex; align-items:center; gap:10px; padding:0.6rem 1rem; border-radius:8px; color:var(--gray); font-size:0.85rem; font-weight:500; text-decoration:none; transition:all 0.2s; background:none; border:none; cursor:pointer; width:100%; }
        .logout-btn:hover { background:rgba(255,59,48,0.1); color:#ff3b30; }

        .main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
        .topbar { height:56px; padding:0 2rem; display:flex; align-items:center; justify-content:space-between; background:var(--secondary); border-bottom:1px solid var(--border); position:sticky; top:0; z-index:40; }
        .topbar-title { font-family:'Syne',sans-serif; font-size:1.05rem; font-weight:700; }
        .admin-badge { background:rgba(233,69,96,0.15); color:var(--gold); font-size:0.72rem; font-weight:700; padding:3px 10px; border-radius:50px; text-transform:uppercase; letter-spacing:0.5px; }

        .content { flex:1; padding:1.8rem; }
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.8rem; }
        .stat-card { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:1.2rem 1.4rem; display:flex; align-items:center; justify-content:space-between; }
        .stat-val { font-family:'Syne',sans-serif; font-size:1.8rem; font-weight:800; }
        .stat-lbl { font-size:0.75rem; color:var(--gray); font-weight:500; margin-top:2px; }
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }

        .card { background:var(--card); border:1px solid var(--border); border-radius:12px; overflow:hidden; }
        .card-header { padding:1.1rem 1.4rem; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.8rem; }
        .card-title { font-family:'Syne',sans-serif; font-size:0.95rem; font-weight:700; }

        .search-form { display:flex; gap:0.6rem; }
        .search-input { padding:0.5rem 1rem; background:var(--card2); border:1px solid var(--border); border-radius:8px; color:var(--white); font-size:0.82rem; outline:none; width:220px; }
        .search-input:focus { border-color:var(--gold); }
        .search-btn { padding:0.5rem 0.9rem; background:var(--gold); border:none; border-radius:8px; color:white; cursor:pointer; font-size:0.85rem; }

        .filter-tabs { display:flex; gap:0.5rem; flex-wrap:wrap; }
        .ftab { padding:0.35rem 0.9rem; border-radius:6px; font-size:0.78rem; font-weight:600; text-decoration:none; border:1px solid; transition:all 0.2s; }

        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding:0.65rem 1rem; text-align:left; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--gray); font-weight:700; border-bottom:1px solid var(--border); white-space:nowrap; }
        td { padding:0.75rem 1rem; border-bottom:1px solid var(--border); font-size:0.83rem; color:rgba(230,237,243,0.85); vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:var(--hover); }
        .tnum { font-family:'Syne',sans-serif; font-size:0.75rem; font-weight:700; color:var(--gold); }

        .action-btn {
            padding:0.3rem 0.8rem; border-radius:6px; font-size:0.75rem; font-weight:600;
            border:none; cursor:pointer; transition:all 0.2s;
        }
        .btn-edit { background:rgba(0,122,255,0.15); color:#007aff; }
        .btn-edit:hover { background:rgba(0,122,255,0.25); }

        /* Modal */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.7); z-index:1000;
            align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal {
            background:var(--secondary); border:1px solid var(--border);
            border-radius:16px; padding:2rem; width:500px; max-width:95vw;
        }
        .modal-title { font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:800; margin-bottom:1.5rem; }
        .modal label { display:block; font-size:0.8rem; font-weight:600; color:rgba(230,237,243,0.6); margin-bottom:0.4rem; }
        .modal select, .modal textarea, .modal input {
            width:100%; padding:0.7rem 1rem; background:rgba(255,255,255,0.04);
            border:1.5px solid var(--border); border-radius:8px; color:var(--white);
            font-family:'DM Sans',sans-serif; font-size:0.88rem; outline:none; margin-bottom:1rem;
        }
        .modal select:focus, .modal textarea:focus { border-color:var(--gold); }
        .modal select option { background:var(--secondary); }
        .modal-btns { display:flex; gap:0.8rem; justify-content:flex-end; }
        .modal-cancel { padding:0.65rem 1.4rem; background:var(--card2); border:1px solid var(--border); border-radius:8px; color:var(--gray); cursor:pointer; font-size:0.88rem; }
        .modal-save { padding:0.65rem 1.4rem; background:linear-gradient(135deg,var(--gold),#c73652); border:none; border-radius:8px; color:white; font-weight:700; font-family:'Syne',sans-serif; cursor:pointer; font-size:0.88rem; }

        .alert-s { padding:0.75rem 1rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.2rem; background:rgba(52,199,89,0.1); border:1px solid rgba(52,199,89,0.25); color:#34c759; }

        .empty-state { text-align:center; padding:3rem; color:var(--gray); }
        .empty-state i { font-size:2.5rem; opacity:0.3; display:block; margin-bottom:1rem; }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="index.php" class="sidebar-logo">
        <div class="logo-box"><i class="fas fa-graduation-cap"></i></div>
        <div><div class="logo-text">RTS Admin</div><div class="logo-sub">Management Panel</div></div>
    </a>
    <nav style="flex:1;overflow-y:auto;padding:0.5rem 0">
        <div class="nav-section">Admin</div>
        <a href="admin.php" class="nav-item active"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="admin.php?view=users" class="nav-item <?= ($_GET['view']??'')==='users'?'active':'' ?>"><i class="fas fa-users"></i> Students</a>
        <div class="nav-section" style="margin-top:0.5rem">Requests</div>
        <a href="admin.php?filter=pending" class="nav-item <?= $filter==='pending'?'active':'' ?>"><i class="fas fa-clock" style="color:#f5a623"></i> Pending</a>
        <a href="admin.php?filter=in_progress" class="nav-item <?= $filter==='in_progress'?'active':'' ?>"><i class="fas fa-spinner" style="color:#007aff"></i> In Progress</a>
        <a href="admin.php?filter=ready" class="nav-item <?= $filter==='ready'?'active':'' ?>"><i class="fas fa-circle-check" style="color:#34c759"></i> Ready</a>
        <a href="admin.php?filter=completed" class="nav-item"><i class="fas fa-check-double" style="color:#5e5ce6"></i> Completed</a>
        <div class="nav-section" style="margin-top:0.5rem">Account</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </nav>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <div class="topbar-title"><i class="fas fa-shield-halved" style="margin-right:8px;color:var(--gold)"></i>Admin Control Panel</div>
        <div style="display:flex;align-items:center;gap:1rem">
            <span class="admin-badge"><i class="fas fa-star" style="margin-right:4px"></i>Administrator</span>
            <span style="font-size:0.82rem;color:var(--gray)"><?= htmlspecialchars($user['name']) ?></span>
        </div>
    </div>

    <div class="content">
        <?php if ($msg): ?>
        <div class="alert-s"><i class="fas fa-circle-check" style="margin-right:8px"></i><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div><div class="stat-val"><?= $totalTickets ?></div><div class="stat-lbl">Total Tickets</div></div>
                <div class="stat-icon" style="background:rgba(0,122,255,0.12)"><i class="fas fa-ticket" style="color:#007aff"></i></div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $pendingCount ?></div><div class="stat-lbl">Pending</div></div>
                <div class="stat-icon" style="background:rgba(245,166,35,0.12)"><i class="fas fa-clock" style="color:#f5a623"></i></div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $inProgCount ?></div><div class="stat-lbl">In Progress</div></div>
                <div class="stat-icon" style="background:rgba(175,82,222,0.12)"><i class="fas fa-spinner" style="color:#af52de"></i></div>
            </div>
            <div class="stat-card">
                <div><div class="stat-val"><?= $totalUsers ?></div><div class="stat-lbl">Students</div></div>
                <div class="stat-icon" style="background:rgba(52,199,89,0.12)"><i class="fas fa-users" style="color:#34c759"></i></div>
            </div>
        </div>

        <?php
        // USER MANAGEMENT VIEW
        if (($_GET['view'] ?? '') === 'users') {
            $db5 = getDB();
            $users = $db5->query("SELECT u.*, (SELECT COUNT(*) FROM tickets WHERE user_id=u.id) as ticket_count FROM users WHERE role='student' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
            $db5->close();
        ?>
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-users" style="margin-right:8px;color:var(--gold)"></i>Registered Students</span>
                <span style="font-size:0.8rem;color:var(--gray)"><?= count($users) ?> total</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Student ID</th><th>Name</th><th>Email</th><th>Course</th><th>Year</th><th>Tickets</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><span class="tnum"><?= htmlspecialchars($u['student_id']) ?></span></td>
                            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                            <td style="color:var(--gray)"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="font-size:0.8rem"><?= htmlspecialchars($u['course'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($u['year_level'] ?: '—') ?></td>
                            <td><span style="font-family:'Syne',sans-serif;font-weight:700;color:var(--blue)"><?= $u['ticket_count'] ?></span></td>
                            <td style="color:var(--gray)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($users)): ?>
                <div class="empty-state"><i class="fas fa-users-slash"></i><p>No students registered yet.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <?php } else { // TICKETS VIEW ?>

        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fas fa-ticket" style="margin-right:8px;color:var(--gold)"></i>All Tickets</span>
                <div style="display:flex;gap:0.8rem;flex-wrap:wrap;align-items:center">
                    <form class="search-form" method="GET">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                        <input type="text" name="s" class="search-input" placeholder="Search tickets..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <div style="padding:0.8rem 1.4rem;border-bottom:1px solid var(--border);display:flex;gap:0.5rem;flex-wrap:wrap">
                <?php
                $ftabs = ['all'=>'All','pending'=>'Pending','in_progress'=>'In Progress','ready'=>'Ready','completed'=>'Completed','rejected'=>'Rejected'];
                $ftcolors = ['all'=>'#8892a4','pending'=>'#f5a623','in_progress'=>'#007aff','ready'=>'#34c759','completed'=>'#5e5ce6','rejected'=>'#ff3b30'];
                foreach ($ftabs as $fk => $fl) {
                    $act = $filter === $fk;
                    $c = $ftcolors[$fk];
                    echo "<a href='admin.php?filter=$fk' class='ftab' style='" . ($act ? "background:rgba(0,0,0,0.2);color:$c;border-color:$c" : "background:transparent;color:var(--gray);border-color:var(--border)") . "'>$fl</a>";
                }
                ?>
            </div>
            <div class="table-wrap">
                <?php if (empty($tickets)): ?>
                <div class="empty-state"><i class="fas fa-inbox"></i><p>No tickets found.</p></div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Ticket #</th><th>Student</th><th>Request Type</th><th>Purpose</th><th>Copies</th><th>Submitted</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><span class="tnum"><?= htmlspecialchars($t['ticket_number']) ?></span></td>
                            <td>
                                <div style="font-weight:600;font-size:0.85rem"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                                <div style="font-size:0.75rem;color:var(--gray)"><?= htmlspecialchars($t['student_id']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($requestLabels[$t['request_type']] ?? $t['request_type']) ?></td>
                            <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.8rem;color:var(--gray)"><?= htmlspecialchars($t['purpose']) ?></td>
                            <td><?= $t['copies'] ?></td>
                            <td style="font-size:0.8rem;color:var(--gray)"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                            <td><?= sBadge($t['status']) ?></td>
                            <td>
                                <button class="action-btn btn-edit" onclick="openModal(<?= $t['id'] ?>,'<?= $t['ticket_number'] ?>','<?= $t['status'] ?>','<?= addslashes($t['admin_remarks'] ?? '') ?>')">
                                    <i class="fas fa-pen" style="margin-right:4px"></i>Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <?php } ?>
    </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-title"><i class="fas fa-pen" style="margin-right:8px;color:var(--gold)"></i>Update Ticket Status</div>
        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="ticket_id" id="modalTicketId">
            <div style="margin-bottom:1rem">
                <div style="font-size:0.8rem;color:var(--gray)">Ticket Number</div>
                <div id="modalTicketNum" style="font-family:'Syne',sans-serif;font-weight:700;color:var(--gold);font-size:1rem;margin-top:2px"></div>
            </div>
            <label>Status *</label>
            <select name="status" id="modalStatus">
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="ready">Ready for Pickup</option>
                <option value="completed">Completed</option>
                <option value="rejected">Rejected</option>
            </select>
            <label>Admin Remarks</label>
            <textarea name="remarks" id="modalRemarks" rows="3" placeholder="Add remarks or instructions for the student..."></textarea>
            <div class="modal-btns">
                <button type="button" class="modal-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="modal-save"><i class="fas fa-check" style="margin-right:6px"></i>Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id, num, status, remarks) {
    document.getElementById('modalTicketId').value = id;
    document.getElementById('modalTicketNum').textContent = num;
    document.getElementById('modalStatus').value = status;
    document.getElementById('modalRemarks').value = remarks;
    document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('open');
}
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>