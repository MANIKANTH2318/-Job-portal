<?php
// 1. INITIALIZE DIAGNOSTICS & BACKEND LOGS
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. STABLE CONNECTIONS SETUP
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("<div style='color:red; font-family:sans-serif; padding:10px;'>Database Connection Error.</div>");
}

// 3. ACTION ROUTER MODULE: HANDLE INTERACTIVE RECORD DELETION
$alert_message = "";
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $job_id = (int)$_GET['id'];
    
    // Secure delete execution query mapping
    $delete_query = "DELETE FROM job_postings WHERE id = $job_id";
    if (mysqli_query($conn, $delete_query)) {
        header("Location: manage-jobs.php?msg=deleted");
        exit();
    } else {
        $alert_message = "Error removing listing: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Admin Panel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; display: flex; min-height: 100vh; }
        .content { flex: 1; padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h2 { font-size: 1.75rem; font-weight: 700; }
        .btn-add { background-color: #6366f1; color: white; padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; }
        .btn-add:hover { background-color: #4f46e5; }
        .table-container { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background-color: #f1f5f9; padding: 1rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        td { padding: 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        tr:hover { background-color: #f8fafc; }
        .status { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: capitalize; }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-closed { background: #fee2e2; color: #b91c1c; }
        .actions { display: flex; gap: 0.5rem; }
        .btn-edit { color: #6366f1; background: none; border: 1px solid #6366f1; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; text-align: center; }
        .btn-delete { color: #ef4444; background: none; border: 1px solid #ef4444; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; text-align: center; }
        .btn-edit:hover { background: #6366f1; color: white; }
        .btn-delete:hover { background: #ef4444; color: white; }
        .system-alert { background: #dbeafe; color: #1e40af; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 500; border-left: 4px solid #3b82f6; }
    </style>
</head>
<body>

    <div class="content">
        <div class="header">
            <h2>Manage Jobs</h2>
            <a href="job_postings.php" class="btn-add">+ Post New Job</a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="system-alert">Job listing removed successfully from the operational registry database.</div>
        <?php endif; ?>
        <?php if (!empty($alert_message)): ?>
            <div class="system-alert" style="background:#fee2e2; color:#b91c1c; border-color:#ef4444;"><?php echo $alert_message; ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Job Type</th>
                        <th>Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Dynamic record extraction routine query map
                    $fetch_jobs = mysqli_query($conn, "SELECT id, job_title, company_name, job_type, salary FROM job_postings ORDER BY id DESC");
                    
                    if (mysqli_num_rows($fetch_jobs) > 0) {
                        while ($row = mysqli_fetch_assoc($fetch_jobs)) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['job_title']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['job_type']) . "</td>";
                            echo "<td>₹" . number_format($row['salary']) . "</td>";
                            echo "<td class='actions'>";
                            echo "<a href='job_postings.php?edit_id=" . $row['id'] . "' class='btn-edit'>Edit</a>";
                            echo "<a href='manage-jobs.php?action=delete&id=" . $row['id'] . "' class='btn-delete' onclick='return confirm(\"Are you certain you want to remove this vacancy?\");'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; color:#94a3b8; padding:2rem;'>No active job openings discovered in database fields.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>