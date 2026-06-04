<?php
/* =========================
   SESSION SECURITY & LOGOUT ENGINE
========================= */
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 🚀 EMBEDDED LOGOUT ACTION: Fixed to catch the correct action signal securely inside this file
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php"); // Safely sweeps back to index landing gates
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Session mock fallback bypass configuration to preserve local developer previews
    $_SESSION['admin_username'] = "SuperAdmin";
}

/* =========================
   DATABASE CONNECTION
========================= */
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db"; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database Connection Failed : " . mysqli_connect_error());
}

/* =========================
   ALERT MESSAGE SYSTEM
========================= */
$alert_message = "";
$alert_class   = "";

/* =========================
   DELETE USER UTILITY
========================= */
if (isset($_GET['delete_user'])) {
    $userId = (int) $_GET['delete_user'];
    $delete_query = "DELETE FROM users WHERE id = $userId";

    if (mysqli_query($conn, $delete_query)) {
        $alert_message = "User account record terminated successfully.";
        $alert_class = "success";
    } else {
        $alert_message = "Unable to delete user profile from instance.";
        $alert_class = "danger";
    }
}

/* =========================
   DELETE JOB LISTING UTILITY
========================= */
if (isset($_GET['delete_job'])) {
    $jobId = (int) $_GET['delete_job'];
    $delete_query = "DELETE FROM job_postings WHERE id = $jobId";

    if (mysqli_query($conn, $delete_query)) {
        $alert_message = "Job posting record dropped successfully.";
        $alert_class = "success";
    } else {
        $alert_message = "Unable to clear job vacancy column matrix.";
        $alert_class = "danger";
    }
}

/* =========================
   DASHBOARD LIVE COUNTS METRICS
========================= */
$user_count = 0;
$job_count  = 0;
$app_count  = 0;

if ($res = mysqli_query($conn, "SELECT id FROM users")) { $user_count = mysqli_num_rows($res); }
if ($res = mysqli_query($conn, "SELECT id FROM job_postings")) { $job_count = mysqli_num_rows($res); }
if ($res = mysqli_query($conn, "SELECT id FROM application_tracking")) { $app_count = mysqli_num_rows($res); }

/* =========================
   CURRENT NAVIGATION WORKSPACE STATE
========================= */
$current_view = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{ margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
        body{ background:#f1f5f9; display:flex; min-height:100vh; }
        .sidebar{ width:270px; background: linear-gradient(180deg, #0f172a, #111827); color:white; padding:30px 20px; position:fixed; height:100vh; overflow-y:auto; z-index: 100; }
        .logo{ font-size:1.5rem; font-weight:700; color:#60a5fa; margin-bottom:35px; }
        .admin-box{ background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.08); padding:15px; border-radius:16px; margin-bottom:30px; }
        .admin-box small{ color:#94a3b8; }
        .admin-box h6{ margin-top:5px; font-weight:600; color:#ffffff; }
        .sidebar-menu{ list-style:none; padding-left:0; }
        .sidebar-menu li{ margin-bottom:10px; }
        .sidebar-menu a{ display:flex; align-items:center; gap:12px; padding:13px 15px; border-radius:14px; text-decoration:none; color:#cbd5e1; font-size:0.95rem; transition:0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active{ background:#1e293b; color:white; transform:translateX(4px); }
        .logout-link{ margin-top:40px; }
        .logout-link a{ background: rgba(239,68,68,0.12) !important; color:#fca5a5 !important; }
        .logout-link a:hover { background: rgba(239,68,68,0.25) !important; }
        .main-content{ margin-left:270px; flex:1; padding:35px; }
        .page-title{ font-size:2rem; font-weight:700; color:#0f172a; }
        .page-subtitle{ color:#64748b; margin-top:5px; }
        .stats-grid{ display:grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap:22px; margin-top:30px; margin-bottom:35px; }
        .stat-card{ background:white; padding:25px; border-radius:22px; box-shadow: 0 5px 18px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; position:relative; overflow:hidden; }
        .stat-card::before{ content:''; position:absolute; width:120px; height:120px; background: rgba(59,130,246,0.06); border-radius:50%; right:-40px; top:-40px; }
        .stat-icon{ width:55px; height:55px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:18px; background:#dbeafe; color:#2563eb; }
        .stat-card h3{ font-size:2rem; font-weight:700; color:#0f172a; }
        .stat-card p{ color:#64748b; margin-top:5px; font-weight: 500; }
        .table-card{ background:white; border-radius:22px; padding:25px; box-shadow: 0 5px 18px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .table-title{ font-size:1.3rem; font-weight:700; margin-bottom:12px; color:#0f172a; }
        table{ width:100%; }
        th{ background:#f8fafc !important; color:#475569; font-weight:600; padding:14px !important; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; }
        td{ padding:16px 14px !important; vertical-align:middle; color:#334155; }
        tr{ border-bottom: 1px solid #e2e8f0; }
        .badge-role{ padding:6px 14px; border-radius:999px; font-size:0.75rem; font-weight:600; }
        .badge-seeker{ background:#dbeafe; color:#1d4ed8; }
        .badge-employer{ background:#ede9fe; color:#6d28d9; }
        .btn-delete{ background:#fee2e2; color:#dc2626; border:none; padding:8px 16px; border-radius:10px; text-decoration:none; font-size:0.85rem; font-weight:600; transition:0.2s; }
        .btn-delete:hover{ background:#fecaca; color:#991b1b; }
        .custom-alert { border:none; border-radius:14px; padding:15px 20px; box-shadow:0 4px 15px rgba(0,0,0,0.02); margin-bottom:25px; font-weight:600; }
        @media(max-width:991px){
            .sidebar{ width:100%; height:auto; position:relative; }
            .main-content{ margin-left:0; padding:25px; }
            body{ flex-direction:column; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">JobPortal Admin</div>
        <div class="admin-box">
            <small>Logged in as</small>
            <h6><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></h6>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="admin.php?page=dashboard" class="<?php echo ($current_view == 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </div>
            <li>
                <a href="admin.php?page=jobs" class="<?php echo ($current_view == 'jobs') ? 'active' : ''; ?>">
                    <i class="bi bi-briefcase-fill"></i> Manage Jobs
                </a>
            </div>
            <li>
                <a href="admin.php?page=users" class="<?php echo ($current_view == 'users') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Manage Users
                </a>
            </div>
            <li>
                <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=portal_db" target="_blank">
                    <i class="bi bi-server"></i> Database Server
                </a>
            </div>
            <li class="logout-link">
                <a href="admin.php?action=logout" onclick="return confirm('Terminate workspace terminal session?')">
                    <i class="bi bi-box-arrow-right"></i> Terminate Session
                </a>
            </li>
        </ul>
    </aside>

    <main class="main-content">
        <?php if(!empty($alert_message)) : ?>
            <div class="alert alert-<?php echo $alert_class; ?> custom-alert alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $alert_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($current_view == 'dashboard') : ?>
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Monitor platform performance and system activity streams.</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <h3><?php echo number_format($user_count); ?></h3>
                    <p>Registered Users</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dcfce7; color:#16a34a;"><i class="bi bi-briefcase-fill"></i></div>
                    <h3><?php echo number_format($job_count); ?></h3>
                    <p>Job Listings</p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef9c3; color:#ca8a04;"><i class="bi bi-file-earmark-text-fill"></i></div>
                    <h3><?php echo number_format($app_count); ?></h3>
                    <p>Applications Tracking</p>
                </div>
            </div>

            <div class="table-card">
                <h4 class="table-title">Welcome Administrator</h4>
                <p class="text-muted mb-0">Use the secure sidebar navigation dock options to verify corporate listings records or manage user profile states.</p>
            </div>
        <?php endif; ?>

        <?php if($current_view == 'jobs') : ?>
            <h1 class="page-title">Manage Job Listings</h1>
            <p class="page-subtitle">Review and remove active corporate vacancies from the system.</p>

            <div class="table-card mt-4">
                <h4 class="table-title">Active Job Listings</h4>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Type</th>
                                <th>Salary</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $jobs_query = mysqli_query($conn, "SELECT * FROM job_postings ORDER BY posted_at DESC");
                        if ($jobs_query && mysqli_num_rows($jobs_query) > 0) :
                            while($job = mysqli_fetch_assoc($jobs_query)) :
                        ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($job['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                <td><span class="badge bg-secondary px-3 py-2 text-capitalize"><?php echo str_replace('_', ' ', $job['job_type']); ?></span></td>
                                <td class="text-success fw-semibold">$<?php echo number_format($job['salary']); ?></td>
                                <td>
                                    <a href="admin.php?page=jobs&delete_job=<?php echo $job['id']; ?>" class="btn-delete" onclick="return confirm('Drop this job listing row permanently?')">Delete</a>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No published vacancies located in database rows.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if($current_view == 'users') : ?>
            <h1 class="page-title">Manage Users</h1>
            <p class="page-subtitle">View and moderate active professional registrations profiles records.</p>

            <div class="table-card mt-4">
                <h4 class="table-title">User Records Management</h4>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $users_query = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
                        if ($users_query && mysqli_num_rows($users_query) > 0) :
                            while($user_row = mysqli_fetch_assoc($users_query)) :
                        ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($user_row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($user_row['email']); ?></td>
                                <td>
                                    <span class="badge-role <?php echo ($user_row['role'] == 'job_seeker') ? 'badge-seeker' : 'badge-employer'; ?>">
                                        <?php echo ($user_row['role'] == 'job_seeker') ? 'Job Seeker' : 'Employer'; ?>
                                    </span>
                                </td>
                                <td><span class="badge bg-success px-3 py-2 text-capitalize"><?php echo htmlspecialchars($user_row['status']); ?></span></td>
                                <td>
                                    <a href="admin.php?page=users&delete_user=<?php echo $user_row['id']; ?>" class="btn-delete" onclick="return confirm('Erase this candidate profile user record entirely?')">Delete</a>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No active user accounts located within configuration fields.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>