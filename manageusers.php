<?php
// 1. INITIALIZE DIAGNOSTICS & SYSTEM POOLS
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. CONNECT TO DATABASE INSTANCE
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("<div style='color:red; font-family:sans-serif; padding:10px;'>User Directory Link Down.</div>");
}

// 3. SECURE INTERACTIVE MODERATION PIPELINE SWITCHER
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $action  = mysqli_real_escape_string($conn, $_GET['action']);
    
    if ($action === 'ban') {
        mysqli_query($conn, "UPDATE users SET status = 'Banned' WHERE id = $user_id");
    } elseif ($action === 'unban') {
        mysqli_query($conn, "UPDATE users SET status = 'Active' WHERE id = $user_id");
    }
    header("Location: manage-users.php");
    exit();
}

// 4. LIVE INTERCEPTOR FOR OPTIONAL SEARCH MATRIX STRINGS
$search_query = "";
if (isset($_POST['search_btn']) && !empty(trim($_POST['search_term']))) {
    $term = mysqli_real_escape_string($conn, trim($_POST['search_term']));
    $search_query = " WHERE fullname LIKE '%$term%' OR email LIKE '%$term%' ";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - JobPortal Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f8fafc; color: #1e2937; padding: 2rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .search-box { padding: 0.6rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; width: 300px; outline: none; }
        .search-box:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1); }
        .user-table-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #f1f5f9; padding: 1rem; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700; }
        td { padding: 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .role { padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; }
        .role-job_seeker { background: #e0e7ff; color: #4338ca; }
        .role-employer { background: #fef3c7; color: #92400e; }
        .status-active { color: #10b981; font-weight: 600; }
        .status-banned { color: #ef4444; font-weight: 600; }
        .btn-action { background: none; border: none; color: #64748b; cursor: pointer; font-weight: 600; margin-right: 10px; font-size: 0.85rem; text-decoration: none; }
        .btn-view:hover { color: #6366f1; }
        .btn-ban:hover { color: #ef4444; }
        tbody tr:nth-child(even) { background-color: #fcfcfc; }
        .search-form { display: flex; gap: 0.5rem; }
        .btn-search { background: #6366f1; color: white; border: none; border-radius: 8px; padding: 0.5rem 1rem; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2>Manage Users</h2>
            <form action="" method="POST" class="search-form">
                <input type="text" name="search_term" class="search-box" placeholder="Search name or email..." value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>">
                <button type="submit" name="search_btn" class="btn-search">Search</button>
            </form>
        </div>

        <div class="user-table-card">
            <table>
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Dynamic retrieval query binding search parameters
                    $users_sql = "SELECT id, fullname, email, role, status FROM users" . $search_query . " ORDER BY id DESC";
                    $fetch_users = mysqli_query($conn, $users_sql);
                    
                    if (mysqli_num_rows($fetch_users) > 0) {
                        while ($user_row = mysqli_fetch_assoc($fetch_users)) {
                            $user_status = !empty($user_row['status']) ? $user_row['status'] : 'Active';
                            
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($user_row['fullname']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($user_row['email']) . "</td>";
                            echo "<td><span class='role role-" . htmlspecialchars($user_row['role']) . "'>" . str_replace('_', ' ', htmlspecialchars($user_row['role'])) . "</span></td>";
                            
                            if (strtolower($user_status) === 'banned') {
                                echo "<td><span class='status-banned'>● Banned</span></td>";
                                echo "<td>";
                                echo "<a href='manage-users.php?action=unban&id=" . $user_row['id'] . "' class='btn-action' style='color:#10b981'>Unban</a>";
                                echo "</td>";
                            } else {
                                echo "<td><span class='status-active'>● Active</span></td>";
                                echo "<td>";
                                echo "<a href='manage-users.php?action=ban&id=" . $user_row['id'] . "' class='btn-action btn-ban' onclick='return confirm(\"Restrict this user from accessing the portal?\");'>Ban</a>";
                                echo "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; color:#94a3b8; padding:2rem;'>No accounts found matching filter metrics.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>