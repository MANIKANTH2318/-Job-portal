<?php
// 1. INITIALIZE SYSTEM SESSIONS
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. DIAGNOSTICS FOR DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. DATABASE CONNECTION SETUP
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db"; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("<div class='alert alert-danger text-center m-3'>Database Connection Failed: " . mysqli_connect_error() . "</div>");
}

// 4. DYNAMIC FILTER LOGIC SETUP
$search_keyword = "";
$filter_type    = "";

$query = "SELECT * FROM job_postings WHERE 1=1";

if (isset($_GET['keyword']) || isset($_GET['job_type'])) {
    if (!empty($_GET['keyword'])) {
        $search_keyword = mysqli_real_escape_string($conn, trim($_GET['keyword']));
        $query .= " AND (job_title LIKE '%$search_keyword%' OR description LIKE '%$search_keyword%' OR company_name LIKE '%$search_keyword%')";
    }
    
    if (!empty($_GET['job_type'])) {
        $filter_type = mysqli_real_escape_string($conn, $_GET['job_type']);
        $query .= " AND job_type = '$filter_type'";
    }
}

$query .= " ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Jobs - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease; }
        body { font-family: 'Poppins', sans-serif; background: #f4fbfd; color: #1f2937; padding-top: 110px; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* 🌗 DARK MODE STYLING HOOKS */
        body.dark-mode { background: #0f172a; color: #f8fafc; }
        body.dark-mode .navbar-custom { background: rgba(15, 23, 42, 0.8) !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        body.dark-mode .nav-link-custom { color: #cbd5e1 !important; }
        body.dark-mode .filter-card { background: #1e293b; border-color: rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        body.dark-mode .filter-label { color: #cbd5e1; }
        body.dark-mode .job-card { background: #1e293b; border-color: rgba(255,255,255,0.05); }
        body.dark-mode .job-card .card-title { color: #ffffff; }
        body.dark-mode .job-card .text-muted { color: #94a3b8 !important; }
        body.dark-mode .input-group-custom-text { background-color: #334155 !important; border-color: #475569 !important; color: #94a3b8 !important; }
        body.dark-mode .input-group-custom-field { background-color: #334155 !important; border-color: #475569 !important; color: #ffffff !important; }

        .navbar-custom { background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); }
        .logo-custom { font-size: 1.7rem; font-weight: 800; text-decoration: none; color: #0AC4E0; }
        .nav-link-custom { color: #374151 !important; font-weight: 500; position: relative; }
        .nav-link-custom::after { content: ''; position: absolute; width: 0%; height: 2px; left: 0; bottom: -5px; background: #0AC4E0; transition: 0.3s; }
        .nav-link-custom:hover::after { width: 100%; }
        .nav-link-custom:hover { color: #0AC4E0 !important; }

        .theme-toggle-btn { background: none; border: none; font-size: 1.3rem; color: #475569; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 8px; border-radius: 50%; }
        body.dark-mode .theme-toggle-btn { color: #fbbf24; }
        .theme-toggle-btn:hover { background: rgba(0, 0, 0, 0.05); }
        body.dark-mode .theme-toggle-btn:hover { background: rgba(255, 255, 255, 0.08); }

        .filter-card { background: white; border: 1px solid rgba(0,0,0,0.08); border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); padding: 24px; margin-bottom: 40px; }
        .filter-label { font-size: 0.85rem; font-weight: 600; color: #4b5563; text-transform: uppercase; margin-bottom: 8px; display: block; }
        
        .input-group-custom-container { display: flex; width: 100%; }
        .input-group-custom-text { background-color: #ffffff; border: 1px solid #d1d5db; border-right: none; color: #6b7280; padding: 10px 16px; border-radius: 10px 0 0 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .input-group-custom-field { background-color: #ffffff; border: 1px solid #d1d5db; border-left: none; color: #1f2937; padding: 10px 14px; border-radius: 0 10px 10px 0; width: 100%; font-size: 1rem; outline: none; }
        .input-group-custom-field:focus { border-color: #0AC4E0; }
        .input-group-custom-field:focus ~ .input-group-custom-text { border-color: #0AC4E0; }

        .form-select { border-radius: 10px !important; padding: 10px 14px !important; border: 1px solid #d1d5db; outline: none; }
        .form-select:focus { border-color: #0AC4E0; box-shadow: 0 0 0 0.15rem rgba(10, 196, 224, 0.25); }

        .btn-primary-custom { background: linear-gradient(135deg, #0AC4E0, #0284c7); border: none; color: white; padding: 10px 24px; border-radius: 10px; font-weight: 600; transition: 0.3s ease; width: 100%; height: 100%; min-height: 46px; }
        .btn-primary-custom:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(10, 196, 224, 0.25); color: white; }
        .btn-secondary-custom { background: #64748b; border: none; color: white; padding: 10px 24px; border-radius: 10px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; height: 100%; min-height: 46px; }
        .btn-secondary-custom:hover { background: #475569; color: white; }

        .job-card { background: white; border: 1px solid rgba(0,0,0,0.04); border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); margin-bottom: 20px; padding: 24px; }
        .job-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        .job-badge { background: rgba(10, 196, 224, 0.1); color: #0284c7; font-weight: 600; padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; }
        
        .main-footer-custom { background: linear-gradient(135deg, #0f172a, #111827); color: #cbd5e1; margin-top: auto; }
        .footer-brand-title { font-size: 1.8rem; font-weight: 700; }
        .footer-brand-title span { color: #0AC4E0; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top py-3">
        <div class="container">
            <a href="index.php" class="logo-custom">JobPortal</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-4">
                    <a class="nav-link-custom text-decoration-none" href="index.php#features">Features</a>
                    <a class="nav-link-custom text-decoration-none" href="application.php">Jobs</a>
                    <a class="nav-link-custom text-decoration-none" href="aboutus.php">Companies</a>
                    <button class="theme-toggle-btn" id="themeToggleBtn" title="Switch Theme">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <h2 class="fw-bold mb-4"><i class="bi bi-briefcase me-2 text-info"></i>Explore Active Openings</h2>

                <div class="filter-card">
                    <form method="GET" action="application.php" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="filter-label">What job are you looking for?</label>
                            <div class="input-group-custom-container">
                                <span class="input-group-custom-text"><i class="bi bi-search"></i></span>
                                <input type="text" name="keyword" class="input-group-custom-field" placeholder="Title, skills, or company..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="filter-label">Job Type</label>
                            <select name="job_type" class="form-select">
                                <option value="">All Arrangements</option>
                                <option value="full_time" <?php if($filter_type == 'full_time') echo 'selected'; ?>>Full Time</option>
                                <option value="part_time" <?php if($filter_type == 'part_time') echo 'selected'; ?>>Part Time</option>
                                <option value="contract" <?php if($filter_type == 'contract') echo 'selected'; ?>>Contract</option>
                                <option value="internship" <?php if($filter_type == 'internship') echo 'selected'; ?>>Internship</option>
                            </select>
                        </div>

                        <div class="col-md-2 col-6">
                            <button type="submit" name="search_submit" class="btn btn-primary-custom">Find Jobs</button>
                        </div>

                        <div class="col-md-2 col-6">
                            <a href="application.php" class="btn btn-secondary-custom">Clear</a>
                        </div>
                    </form>
                </div>

                <div id="jobListingsings">
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="job-card">
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="job-badge text-capitalize"><?php echo str_replace('_', ' ', htmlspecialchars($row['job_type'] ?? 'Full Time')); ?></span>
                                            <small class="text-muted"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($row['company_name'] ?? 'Corporate Partner'); ?></small>
                                        </div>
                                        <h4 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($row['job_title'] ?? 'Job Title'); ?></h4>
                                        <p class="text-muted mb-0">
                                            <?php 
                                                $description_text = $row['description'] ?? '';
                                                echo htmlspecialchars(substr($description_text, 0, 140)) . (strlen($description_text) > 140 ? '...' : ''); 
                                            ?>
                                        </p>
                                    </div>
                                    <div class="text-md-end w-100 w-md-auto">
                                        <a href="resume.php?job_id=<?php echo $row['id']; ?>" class="btn btn-outline-info rounded-3 px-4 w-100">Apply Now</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5 filter-card border border-dashed rounded-4">
                            <i class="bi bi-exclamation-triangle text-warning display-2 mb-3 d-block"></i>
                            <h4 class="fw-bold">No Positions Are Available Right Now</h4>
                            <p class="text-muted max-width-400 mx-auto">We couldn't find any job openings matching "<strong><?php echo htmlspecialchars($search_keyword); ?></strong>". Try checking your spelling or selecting an alternative arrangement role.</p>
                            <a href="application.php" class="btn btn-sm btn-primary-custom d-inline-block w-auto mt-3 px-4 py-2">View All Active Openings</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <footer class="main-footer-custom">
        <div class="container py-4 text-center">
            <h4 class="footer-brand-title mb-2">Job<span>Portal</span></h4>
            <p class="mb-0">&copy; 2026 JobPortal. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;

        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
            themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
        }

        themeToggleBtn.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
                localStorage.setItem('theme', 'dark');
            } else {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-fill');
                localStorage.setItem('theme', 'light');
            }
        });
    </script>
</body>
</html>