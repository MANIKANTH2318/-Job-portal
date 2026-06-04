<?php
// 1. INITIALIZE SYSTEM SESSIONS FOR ALERT MANAGEMENT
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. TURN ON DIAGNOSTICS FOR DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. DATABASE CONFIGURATION DEFINITION
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db"; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("<div class='alert alert-danger text-center m-3'>Database Connection Failed: " . mysqli_connect_error() . "</div>");
}

$alert_message = "";
$alert_class = "";

// PRG Flash Message Interceptor
if (isset($_SESSION['success_flash'])) {
    $alert_message = $_SESSION['success_flash'];
    $alert_class = "alert-success";
    unset($_SESSION['success_flash']); 
}

// 4. PROCESS MODAL POPUP SIGNUP METHOD REQUESTS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {
    $fullName = mysqli_real_escape_string($conn, trim($_POST['fullName']));
    $email    = mysqli_real_escape_string($conn, strtolower(trim($_POST['email']))); 
    $userRole = mysqli_real_escape_string($conn, $_POST['userRole']);
    $password = $_POST['password'];

    if (!empty($fullName) && !empty($email) && !empty($password)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert_message = "Please enter a valid structured email address.";
            $alert_class = "alert-danger";
        } elseif (!str_ends_with($email, '@gmail.com')) {
            $alert_message = "Registration restricted! You must sign up with a valid @gmail.com address.";
            $alert_class = "alert-danger";
        } else {
            $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check_email) > 0) {
                $alert_message = "This Gmail address is already registered! Please use a different one.";
                $alert_class = "alert-danger";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insert_query = "INSERT INTO users (fullname, email, password, role, status) VALUES ('$fullName', '$email', '$hashedPassword', '$userRole', 'Active')";
                if (mysqli_query($conn, $insert_query)) {
                    $_SESSION['success_flash'] = "Account created successfully! You can now access your profile.";
                    header("Location: " . $_SERVER['PHP_SELF'] . "?signup=success");
                    exit;
                } else {
                    $alert_message = "Something went wrong during registration: " . mysqli_error($conn);
                    $alert_class = "alert-danger";
                }
            }
        }
    } else {
        $alert_message = "Please fill out all the fields in the sign-up form.";
        $alert_class = "alert-danger";
    }
}

// 5. PROCESS VACANCY PUBLICATION SUBMISSIONS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_job'])) {
    $jobTitle = mysqli_real_escape_string($conn, trim($_POST['jobTitle']));
    $companyName = mysqli_real_escape_string($conn, trim($_POST['companyName']));
    $jobType = mysqli_real_escape_string($conn, $_POST['jobType']);
    $salary = mysqli_real_escape_string($conn, trim($_POST['salary']));
    $description = mysqli_real_escape_string($conn, trim($_POST['jobDescription']));

    // FOREIGN KEY RELATIONSCANER
    $employer_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'employer' LIMIT 1");
    if (mysqli_num_rows($employer_query) > 0) {
        $employer_row = mysqli_fetch_assoc($employer_query);
        $mock_employer_id = $employer_row['id']; 
    } else {
        mysqli_query($conn, "INSERT INTO users (fullname, email, password, role, status) VALUES ('Corporate Admin', 'corporate@gmail.com', '123', 'employer', 'Active')");
        $mock_employer_id = mysqli_insert_id($conn);
    }

    if (!empty($jobTitle) && !empty($companyName) && !empty($salary) && !empty($description)) {
        $insert_query = "INSERT INTO job_postings (employer_id, job_title, company_name, job_type, salary, description, status) VALUES ('$mock_employer_id', '$jobTitle', '$companyName', '$jobType', '$salary', '$description', 'Pending')";
        if (mysqli_query($conn, $insert_query)) {
            $alert_message = "Job published successfully!";
            $alert_class = "alert-success";
        } else {
            $alert_message = "Database Error : " . mysqli_error($conn);
            $alert_class = "alert-danger";
        }
    } else {
        $alert_message = "Please fill all required fields.";
        $alert_class = "alert-danger";
    }
}

// Fetch job postings for display
$jobs = [];
$result = mysqli_query($conn, "SELECT * FROM job_postings WHERE status='Pending'");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Post Job | JobPortal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<style>
/* Your existing styles... (keep your original styles here) */

* { margin:0; padding:0; box-sizing:border-box; transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease; }
body { min-height:100vh; background:linear-gradient(135deg, #dff6ff, #b8e8fc, #98c1ff); font-family:'Poppins',sans-serif; padding-top: 20px; }

/* Dark mode styles... (keep your existing styles here) */

body.dark-mode { background:#0f172a; color:#f8fafc; }
body.dark-mode .navbar-custom { background:rgba(15,23,42,0.8) !important; border-bottom:1px solid rgba(255,255,255,0.05); }
body.dark-mode .job-box-custom { background:#1e293b; border-color:rgba(255,255,255,0.05); box-shadow:0 10px 30px rgba(0,0,0,0.3); }
body.dark-mode .main-title { color:#ffffff; }
body.dark-mode .sub-title { color:#94a3b8; }
body.dark-mode .form-label-custom { color:#e2e8f0; }
body.dark-mode .form-control-custom, body.dark-mode .form-select-custom { background:#334155; border-color:#475569; color:#ffffff; }
body.dark-mode .form-control-custom::placeholder { color:#94a3b8; }
body.dark-mode .input-group-text { background:#334155; border-color:#475569; color:#94a3b8; }
body.dark-mode .nav-link-custom { color:#cbd5e1 !important; }
body.dark-mode .modal-content-custom { background: #1e293b; color: #ffffff; }
body.dark-mode .modal-content-custom h3, body.dark-mode .form-group-label { color: #ffffff; }
body.dark-mode .form-control, body.dark-mode .form-select { background-color: #334155; border-color: #475569; color: white; }

.navbar-custom { background:rgba(255,255,255,0.7); backdrop-filter:blur(12px); border-bottom:1px solid rgba(255,255,255,0.2); }
.logo-custom { font-size:1.6rem; font-weight:700; color:#0AC4E0; text-decoration:none; }
.nav-link-custom { color:#444 !important; font-weight:500; transition:0.3s; position: relative; }
.nav-link-custom::after { content: ''; position: absolute; width: 0%; height: 2px; left: 0; bottom: -5px; background: #0AC4E0; transition: 0.3s; }
.nav-link-custom:hover::after { width: 100%; }
.nav-link-custom:hover { color:#0AC4E0 !important; }

.btn-primary-custom { background:linear-gradient(135deg, #0AC4E0, #0284c7); border:none; color:white; padding:10px 22px; border-radius:12px; font-weight:600; transition:0.3s; box-shadow: 0 6px 15px rgba(10, 196, 224, 0.2); }
.btn-primary-custom:hover { transform:translateY(-2px); box-shadow: 0 10px 25px rgba(10, 196, 224, 0.3); color:white; }
.job-box-custom { background:rgba(255,255,255,0.25); backdrop-filter:blur(15px); border-radius:25px; border:1px solid rgba(255,255,255,0.2); box-shadow:0 8px 32px rgba(0,0,0,0.15); padding:40px; }
.main-title { font-size:2rem; font-weight:700; color:#111; }
.sub-title { color:#555; font-size:0.95rem; }
.form-label-custom { font-size:0.85rem; font-weight:600; color:#444; margin-bottom:8px; }
.form-control-custom, .form-select-custom { border-radius:14px; padding:12px; border:1px solid #ddd; transition:0.3s; }
.form-control-custom:focus, .form-select-custom:focus { border-color:#0AC4E0 !important; box-shadow:0 0 0 3px rgba(10,196,224,0.15) !important; }
textarea { resize:none; }
.btn-submit-custom { background:linear-gradient(135deg, #0AC4E0, #0284c7); border:none; color:white; padding:12px; border-radius:14px; font-weight:600; transition:0.3s; }
.btn-submit-custom:hover { transform:translateY(-2px); opacity:0.95; color:white; }
.alert { border:none; border-radius:14px; box-shadow:0 4px 10px rgba(0,0,0,0.08); }
.theme-toggle-btn { background:none; border:none; font-size:1.3rem; color:#475569; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:8px; border-radius:50%; }
.modal-content-custom { border: none; border-radius: 24px; padding: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
.form-group-label { font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 6px; text-transform: uppercase; }
@media(max-width:576px) { .job-box-custom { padding:25px; } .main-title { font-size:1.6rem; } }

/* ====== New styles for job listing cards ====== */
.job-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    cursor: pointer;
    transition: box-shadow 0.3s, background-color 0.3s;
    background-color: #fff;
}
.job-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.compact-view { display: flex; justify-content: space-between; align-items: center; }
.detailed-view { display: none; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
</style>
</head>
<body>

<!-- Your existing navbar code... (keep as is) -->

<nav class="navbar navbar-expand-lg navbar-custom py-3 fixed-top">
    <div class="container">
        <a href="index.php" class="logo-custom">JobPortal</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-lg-center gap-3">
                <a href="index.php" class="nav-link nav-link-custom">Home</a>
                <a href="application.php" class="nav-link nav-link-custom">Jobs</a>
                <a href="aboutus.php" class="nav-link nav-link-custom">About</a>
                <button class="theme-toggle-btn" id="themeToggleBtn"><i class="bi bi-moon-fill" id="themeIcon"></i></button>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#authModal" onclick="setModalRole('job_seeker')">Get Started</button>
            </div>
        </div>
    </div>
</nav>

<?php if(!empty($alert_message)) : ?>
<div class="container" style="margin-top: 100px; margin-bottom: -60px;">
    <div class="alert alert-<?php echo ($alert_class === 'alert-success' || $alert_class === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show text-center">
        <?php echo $alert_message; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- ====== Job Posting Form ====== -->
<div class="container" style="margin-top: 120px; margin-bottom: 60px;">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-12">
            <div class="job-box-custom">
                <h2 class="main-title mb-2">Post a New Job</h2>
                <p class="sub-title mb-4">Reach thousands of skilled candidates and hire faster.</p>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label-custom">Job Title</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                            <input type="text" name="jobTitle" class="form-control form-control-custom" placeholder="Senior Web Developer" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Company Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <input type="text" name="companyName" class="form-control form-control-custom" placeholder="Tech Solutions Pvt Ltd" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Job Type</label>
                        <select name="jobType" class="form-select form-select-custom">
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Salary</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-cash-stack"></i></span>
                            <input type="number" name="salary" class="form-control form-control-custom" placeholder="50000" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom">Job Description</label>
                        <textarea name="jobDescription" rows="5" class="form-control form-control-custom" placeholder="Enter job roles, skills and requirements..." required></textarea>
                    </div>
                    <button type="submit" name="submit_job" class="btn btn-submit-custom w-100">Publish Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ====== Job Listings (with toggle), moved above Apply Now button ====== -->
<div class="container my-5">
    <div class="row" id="job-listings">
        <?php foreach ($jobs as $job): ?>
            <div class="col-12 mb-4">
                <div class="job-card" onclick="toggleDetails(this)">
                    <!-- Compact View -->
                    <div class="compact-view d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                            <p class="mb-1" style="font-size:0.9rem;">
                                <?php echo htmlspecialchars($job['company_name']); ?> | <?php echo htmlspecialchars($job['job_type']); ?> | ₹<?php echo htmlspecialchars($job['salary']); ?>
                            </p>
                        </div>
                        <!-- Remove Apply Now button here -->
                        <!-- <button class="btn btn-primary btn-sm apply-btn" onclick="event.stopPropagation(); toggleDetails(this.closest('.job-card'));">Apply Now</button> -->
                    </div>
                    <!-- Detailed View -->
                    <div class="detailed-view">
                        <h5 class="mb-2"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
                        <p><strong>Salary:</strong> ₹<?php echo htmlspecialchars($job['salary']); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        <button class="btn btn-secondary mt-3" onclick="toggleDetails(this.closest('.job-card'));">Close</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ====== Apply Now button outside listings, if needed elsewhere ====== -->
<!-- If you want a single Apply Now button outside all listings, add here -->
<!-- <button class="btn btn-primary btn-sm apply-btn" onclick="event.stopPropagation(); toggleDetails(this.closest('.job-card'));">Apply Now</button> -->

<!-- ====== Modal for Sign Up / Login ====== -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(4px);">
<div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
<div class="modal-content modal-content-custom">
<div class="modal-header border-0 pb-0">
<h3 class="fw-bold mb-0">Create Account</h3>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<form action="" method="POST">
<div class="mb-3">
<label class="form-group-label">Full Name</label>
<input type="text" name="fullName" class="form-control" placeholder="Enter your name" required>
</div>
<div class="mb-3">
<label class="form-group-label">Email Address</label>
<input type="email" name="email" class="form-control" placeholder="jane@gmail.com" pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" title="Please enter a valid Gmail address ending in @gmail.com" required>
</div>
<div class="mb-3">
<label class="form-group-label">Account Type</label>
<select id="userRole" name="userRole" class="form-select">
<option value="job_seeker">Job Seeker</option>
<option value="employer">Employer</option>
</select>
</div>
<div class="mb-4">
<label class="form-group-label">Password</label>
<input type="password" name="password" class="form-control" placeholder="Enter password" required>
</div>
<button type="submit" name="signup_submit" class="btn btn-primary-custom w-100 py-2.5">Sign Up</button>
</form>
</div>
</div>
</div>
</div>

<!-- ====== Scripts ====== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const themeToggleBtn = document.getElementById('themeToggleBtn');
const themeIcon = document.getElementById('themeIcon');
const body = document.body;

if(localStorage.getItem('theme') === 'dark'){
    body.classList.add('dark-mode');
    themeIcon.classList.replace('bi-moon-fill','bi-sun-fill');
}

themeToggleBtn.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if(body.classList.contains('dark-mode')){
        themeIcon.classList.replace('bi-moon-fill','bi-sun-fill');
        localStorage.setItem('theme','dark');
    } else {
        themeIcon.classList.replace('bi-sun-fill','bi-moon-fill');
        localStorage.setItem('theme','light');
    }
});
<?php
// 1. INITIALIZE SYSTEM SESSIONS FOR ALERT MANAGEMENT
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. TURN ON DIAGNOSTICS FOR DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. DATABASE CONFIGURATION DEFINITION
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db"; 

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("<div class='alert alert-danger text-center m-3'>Database Connection Failed: " . mysqli_connect_error() . "</div>");
}

$alert_message = "";
$alert_class = "";

// PRG Flash Message Interceptor
if (isset($_SESSION['success_flash'])) {
    $alert_message = $_SESSION['success_flash'];
    $alert_class = "alert-success";
    unset($_SESSION['success_flash']); 
}

// 4. PROCESS MODAL POPUP SIGNUP METHOD REQUESTS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup_submit'])) {
    $fullName = mysqli_real_escape_string($conn, trim($_POST['fullName']));
    $email    = mysqli_real_escape_string($conn, strtolower(trim($_POST['email']))); 
    $userRole = mysqli_real_escape_string($conn, $_POST['userRole']);
    $password = $_POST['password'];

    if (!empty($fullName) && !empty($email) && !empty($password)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alert_message = "Please enter a valid structured email address.";
            $alert_class = "alert-danger";
        } elseif (!str_ends_with($email, '@gmail.com')) {
            $alert_message = "Registration restricted! You must sign up with a valid @gmail.com address.";
            $alert_class = "alert-danger";
        } else {
            $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check_email) > 0) {
                $alert_message = "This Gmail address is already registered! Please use a different one.";
                $alert_class = "alert-danger";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insert_query = "INSERT INTO users (fullname, email, password, role, status) VALUES ('$fullName', '$email', '$hashedPassword', '$userRole', 'Active')";
                if (mysqli_query($conn, $insert_query)) {
                    $_SESSION['success_flash'] = "Account created successfully! You can now access your profile.";
                    header("Location: " . $_SERVER['PHP_SELF'] . "?signup=success");
                    exit;
                } else {
                    $alert_message = "Something went wrong during registration: " . mysqli_error($conn);
                    $alert_class = "alert-danger";
                }
            }
        }
    } else {
        $alert_message = "Please fill out all the fields in the sign-up form.";
        $alert_class = "alert-danger";
    }
}

// 5. PROCESS VACANCY PUBLICATION SUBMISSIONS INCLUDING NEW FIELDS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_job'])) {
    $jobTitle = mysqli_real_escape_string($conn, trim($_POST['jobTitle']));
    $companyName = mysqli_real_escape_string($conn, trim($_POST['companyName']));
    $jobType = mysqli_real_escape_string($conn, $_POST['jobType']);
    $salary = mysqli_real_escape_string($conn, trim($_POST['salary']));
    $description = mysqli_real_escape_string($conn, trim($_POST['jobDescription']));
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));
    $vacancies = intval($_POST['vacancies']);
    $expiry_date = mysqli_real_escape_string($conn, trim($_POST['expiry_date']));

    // Basic validation
    if (!empty($jobTitle) && !empty($companyName) && !empty($salary) && !empty($description) && !empty($location) && $vacancies > 0 && !empty($expiry_date)) {
        // Assume employer exists or create a mock employer
        $employer_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'employer' LIMIT 1");
        if (mysqli_num_rows($employer_query) > 0) {
            $employer_row = mysqli_fetch_assoc($employer_query);
            $mock_employer_id = $employer_row['id']; 
        } else {
            mysqli_query($conn, "INSERT INTO users (fullname, email, password, role, status) VALUES ('Corporate Admin', 'corporate@gmail.com', '123', 'employer', 'Active')");
            $mock_employer_id = mysqli_insert_id($conn);
        }

        // Insert job post with new fields
        $insert_query = "INSERT INTO job_postings 
            (employer_id, job_title, company_name, job_type, salary, description, location, vacancies, expiry_date, status, posted_date)
            VALUES ('$mock_employer_id', '$jobTitle', '$companyName', '$jobType', '$salary', '$description', '$location', '$vacancies', '$expiry_date', 'Pending', NOW())";

        if (mysqli_query($conn, $insert_query)) {
            $alert_message = "Job published successfully!";
            $alert_class = "alert-success";
        } else {
            $alert_message = "Database Error : " . mysqli_error($conn);
            $alert_class = "alert-danger";
        }
    } else {
        $alert_message = "Please fill all required fields.";
        $alert_class = "alert-danger";
    }
}

// Fetch jobs and update expiry status
$today = date('Y-m-d');
$result = mysqli_query($conn, "SELECT *, 
    CASE WHEN expiry_date < '$today' THEN 'Expired' ELSE 'Active' END AS job_status
    FROM job_postings
    ORDER BY posted_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Post Job | JobPortal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<style>
  /* Your existing styles here (unchanged for brevity) */
  * { margin:0; padding:0; box-sizing:border-box; transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease; }
  body { min-height:100vh; background:linear-gradient(135deg, #dff6ff, #b8e8fc, #98c1ff); font-family:'Poppins',sans-serif; padding-top: 20px; }
  /* ... (rest of your styles) ... */
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom py-3 fixed-top">
  <div class="container">
    <a href="index.php" class="logo-custom">JobPortal</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <div class="navbar-nav ms-auto align-items-lg-center gap-3">
        <a href="index.php" class="nav-link nav-link-custom">Home</a>
        <a href="application.php" class="nav-link nav-link-custom">Jobs</a>
        <a href="aboutus.php" class="nav-link nav-link-custom">About</a>
        <button class="theme-toggle-btn" id="themeToggleBtn">
          <i class="bi bi-moon-fill" id="themeIcon"></i>
        </button>
        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#authModal" onclick="setModalRole('job_seeker')">Get Started</button>
      </div>
    </div>
  </div>
</nav>

<?php if(!empty($alert_message)) : ?>
<div class="container" style="margin-top: 100px; margin-bottom: -60px;">
  <div class="alert alert-<?php echo ($alert_class === 'alert-success' || $alert_class === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show text-center">
    <?php echo $alert_message; ?>
    <button class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<div class="container" style="margin-top: 120px; margin-bottom: 60px;">
  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-12">
      <div class="job-box-custom">
        <h2 class="main-title mb-2">Post a New Job</h2>
        <p class="sub-title mb-4">Reach thousands of skilled candidates and hire faster.</p>
        <form action="" method="POST">
          <div class="mb-3">
            <label class="form-label-custom">Job Title</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
              <input type="text" name="jobTitle" class="form-control form-control-custom" placeholder="Senior Web Developer" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label-custom">Company Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-building"></i></span>
              <input type="text" name="companyName" class="form-control form-control-custom" placeholder="Tech Solutions Pvt Ltd" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label-custom">Job Type</label>
            <select name="jobType" class="form-select form-select-custom">
              <option value="full_time">Full Time</option>
              <option value="part_time">Part Time</option>
              <option value="contract">Contract</option>
              <option value="internship">Internship</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label-custom">Salary</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-cash-stack"></i></span>
              <input type="number" name="salary" class="form-control form-control-custom" placeholder="50000" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label-custom">Location</label>
            <input type="text" name="location" class="form-control form-control-custom" placeholder="City, State" required>
          </div>
          <div class="mb-3">
            <label class="form-label-custom">Vacancies</label>
            <input type="number" name="vacancies" class="form-control form-control-custom" placeholder="Number of Vacancies" required>
          </div>
          <div class="mb-3">
            <label class="form-label-custom">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control form-control-custom" required>
          </div>
          <div class="mb-4">
            <label class="form-label-custom">Job Description</label>
            <textarea name="jobDescription" rows="5" class="form-control form-control-custom" placeholder="Enter job roles, skills and requirements..." required></textarea>
          </div>
          <button type="submit" name="submit_job" class="btn btn-submit-custom w-100">Publish Job</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Display Jobs Table -->
<?php if(mysqli_num_rows($result) > 0): ?>
<div class="container" style="margin-top: 50px; margin-bottom: 50px;">
  <h3 class="mb-4 text-center">Current Job Listings</h3>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Job Title</th>
        <th>Company</th>
        <th>Type</th>
        <th>Location</th>
        <th>Vacancies</th>
        <th>Posted Date</th>
        <th>Expiry Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = mysqli_fetch_assoc($result)): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['job_title']); ?></td>
          <td><?php echo htmlspecialchars($row['company_name']); ?></td>
          <td><?php echo htmlspecialchars($row['job_type']); ?></td>
          <td><?php echo htmlspecialchars($row['location']); ?></td>
          <td><?php echo $row['vacancies']; ?></td>
          <td><?php echo date('Y-m-d', strtotime($row['posted_date'])); ?></td>
          <td><?php echo date('Y-m-d', strtotime($row['expiry_date'])); ?></td>
          <td>
            <?php if($row['job_status'] == 'Expired'): ?>
              <span class="text-danger"><?php echo $row['job_status']; ?></span>
            <?php else: ?>
              <span class="text-success"><?php echo $row['job_status']; ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Modal for Sign Up -->
<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(4px);">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content modal-content-custom">
      <div class="modal-header border-0 pb-0">
        <h3 class="fw-bold mb-0">Create Account</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="" method="POST">
          <div class="mb-3">
            <label class="form-group-label">Full Name</label>
            <input type="text" name="fullName" class="form-control" placeholder="Enter your name" required>
          </div>
          <div class="mb-3">
            <label class="form-group-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="jane@gmail.com" pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" title="Please enter a valid Gmail address ending in @gmail.com" required>
          </div>
          <div class="mb-3">
            <label class="form-group-label">Account Type</label>
            <select id="userRole" name="userRole" class="form-select">
              <option value="job_seeker">Job Seeker</option>
              <option value="employer">Employer</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-group-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
          </div>
          <button type="submit" name="signup_submit" class="btn btn-primary-custom w-100 py-2.5">Sign Up</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS & Theme Toggle Script -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  

  if(localStorage.getItem('theme') === 'dark'){
    body.classList.add('dark-mode');
    themeIcon.classList.replace('bi-moon-fill','bi-sun-fill');
  }

  themeToggleBtn.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if(body.classList.contains('dark-mode')){
      themeIcon.classList.replace('bi-moon-fill','bi-sun-fill');
      localStorage.setItem('theme','dark');
    } else {
      themeIcon.classList.replace('bi-sun-fill','bi-moon-fill');
      localStorage.setItem('theme','light');
    }
  });

  function setModalRole(role) {
    const select = document.getElementById('userRole');
    if (select) { select.value = role; }
  }
</script>
</body>
</html>
function setModalRole(role) {
    const select = document.getElementById('userRole');
    if (role && select) { select.value = role; }
}

// Toggle job details view
function toggleDetails(card) {
    const compact = card.querySelector('.compact-view');
    const detailed = card.querySelector('.detailed-view');

    if (detailed.style.display === 'none' || detailed.style.display === '') {
        detailed.style.display = 'block';
        compact.style.display = 'none';
    } else {
        detailed.style.display = 'none';
        compact.style.display = 'flex';
    }
}
</script>

</body>
</html>