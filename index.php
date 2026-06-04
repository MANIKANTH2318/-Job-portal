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

// 4. SECURE SIGN-UP INSERTION PROCESSING LOGIC WITH GMAIL VALIDATION
$alert_message = "";
$alert_class = "";

if (isset($_SESSION['success_flash'])) {
    $alert_message = $_SESSION['success_flash'];
    $alert_class = "alert-success";
    unset($_SESSION['success_flash']); 
}

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
                    header("Location: index.php?signup=success");
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

// 5. FETCH DYNAMIC METRIC COUNTS
$user_count = 0; $resume_count = 0; $job_count = 0; $app_count = 0;
if ($result = mysqli_query($conn, "SELECT id FROM users")) { $user_count = mysqli_num_rows($result); }
if ($result = mysqli_query($conn, "SELECT id FROM resumes")) { $resume_count = mysqli_num_rows($result); }
if ($result = mysqli_query($conn, "SELECT id FROM job_postings")) { $job_count = mysqli_num_rows($result); }
if ($result = mysqli_query($conn, "SELECT id FROM application_tracking")) { $app_count = mysqli_num_rows($result); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>JobPortal - Professional Job Platform</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
         /* This single wrapper class handles the colors, heights, and perfect rounded corners */
    .custom-newsletter-group {
        background-color: #2b394a; /* Matches your exact dark input background */
        border-radius: 10px;       /* Smooth outer corners */
        overflow: hidden;          /* Keeps internal corners seamlessly snapped together */
        height: 50px;              /* Standardizes height across input and button */
    }
        * { margin: 0; padding: 0; box-sizing: border-box; transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; }
        body { font-family: 'Poppins', sans-serif; background: #f4fbfd; color: #1f2937; overflow-x: hidden; }
        html { scroll-behavior: smooth; }
        
        /* =========================================================================
           🌗 DARK THEME RE-ASSIGNMENT VARIABLES
           ========================================================================= */
        body.dark-mode { background: #0f172a; color: #f8fafc; }
        body.dark-mode .navbar-custom { background: rgba(15, 23, 42, 0.8) !important; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        body.dark-mode .nav-link-custom { color: #cbd5e1 !important; }
        body.dark-mode .feature-card-custom { background: #1e293b; border-color: rgba(255,255,255,0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        body.dark-mode .feature-card-custom h3 { color: #f8fafc; }
        body.dark-mode .feature-card-custom p { color: #94a3b8; }
        body.dark-mode .hero-custom { background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center; }
        body.dark-mode .hero-custom h1 { color: #ffffff; }
        body.dark-mode .hero-custom p { color: #cbd5e1; }
        body.dark-mode .section-title { color: #ffffff; }
        body.dark-mode .modal-content-custom { background: #1e293b; color: #ffffff; }
        body.dark-mode .modal-content-custom h3, body.dark-mode .form-group-label { color: #ffffff; }
        body.dark-mode .form-control, body.dark-mode .form-select { background-color: #334155; border-color: #475569; color: white; }

        /* NAVBAR MODULE LAYOUT */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }
        .logo-custom { font-size: 1.7rem; font-weight: 800; text-decoration: none; color: #0AC4E0; }
        .nav-link-custom { color: #374151 !important; font-weight: 500; position: relative; }
        .nav-link-custom::after { content: ''; position: absolute; width: 0%; height: 2px; left: 0; bottom: -5px; background: #0AC4E0; transition: 0.3s; }
        .nav-link-custom:hover::after { width: 100%; }
        .nav-link-custom:hover { color: #0AC4E0 !important; }

        /* THEME TOGGLE ICON STYLING */
        .theme-toggle-btn { background: none; border: none; font-size: 1.3rem; color: #475569; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 8px; border-radius: 50%; transition: 0.2s; }
        body.dark-mode .theme-toggle-btn { color: #fbbf24; }
        .theme-toggle-btn:hover { background: rgba(0, 0, 0, 0.05); }
        body.dark-mode .theme-toggle-btn:hover { background: rgba(255, 255, 255, 0.08); }

        /* BUTTONS & CARDS */
        .btn-primary-custom { background: linear-gradient(135deg, #0AC4E0, #0284c7); border: none; color: white; padding: 12px 28px; border-radius: 12px; font-weight: 600; box-shadow: 0 10px 25px rgba(10, 196, 224, 0.25); }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 15px 35px rgba(10, 196, 224, 0.35); color: white; }
        .hero-custom { min-height: 100vh; display: flex; align-items: center; background: linear-gradient(rgba(244, 251, 253, 0.9), rgba(244, 251, 253, 0.96)), url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center; padding-top: 100px; }
        .badge-custom { background: rgba(10, 196, 224, 0.12); color: #0284c7; padding: 10px 20px; border-radius: 999px; font-size: 0.8rem; font-weight: 700; letter-spacing: 1px; display: inline-block; margin-bottom: 20px; }
        .hero-custom h1 { font-size: 3rem; font-weight: 800; line-height: 1.2; color: #0f172a; }
        .hero-custom p { font-size: 1.1rem; color: #475569; max-width: 700px; margin: auto; margin-top: 20px; }
        #features { padding: 100px 0; }
        .section-title { font-size: 2.8rem; font-weight: 800; color: #111827; }
        .feature-card-custom { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); border-radius: 20px; padding: 35px 28px; border: 1px solid rgba(225, 225, 225, 0.3); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); position: relative; overflow: hidden; }
        .feature-card-custom::before { content: ''; position: absolute; width: 120px; height: 120px; background: rgba(10, 196, 224, 0.08); border-radius: 50%; top: -40px; right: -40px; }
        .feature-card-custom:hover { transform: translateY(-5px); box-shadow: 0 20px 45px rgba(0, 0, 0, 0.08); }
        .feature-card-custom h3 { font-size: 1.4rem; font-weight: 700; color: #111827; margin-bottom: 14px; }
        .feature-card-custom p { color: #4b5563; font-size: 0.95rem; }
        .metric-badge-pill { font-size: 0.8rem; padding: 5px 12px; font-weight: 700; }
        .main-footer-custom { background: linear-gradient(135deg, #0f172a, #111827); color: #cbd5e1; }
        .footer-brand-title { font-size: 1.8rem; font-weight: 700; }
        .footer-brand-title span { color: #0AC4E0; }
        .footer-link-item { color: #cbd5e1; text-decoration: none; }
        .footer-link-item:hover { color: #0AC4E0; padding-left: 5px; }
        .modal-content-custom { border: none; border-radius: 24px; padding: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
        .form-control, .form-select { border-radius: 12px !important; padding: 12px !important; border: 1px solid #d1d5db; }
        .form-group-label { font-size: 0.8rem; font-weight: 600; color: #4b5563; margin-bottom: 6px; text-transform: uppercase; }
        @media(max-width:768px) { .hero-custom h1 { font-size: 2.5rem; } .section-title { font-size: 2rem; } .hero-custom { text-align: center; } }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom fixed-top py-3">
        <div class="container">
            <a href="#" class="logo-custom">JobPortal</a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-4">
                    <a class="nav-link-custom text-decoration-none" href="#features">Features</a>
                    <a class="nav-link-custom text-decoration-none" href="application.php">Jobs</a>
                    <a class="nav-link-custom text-decoration-none" href="aboutus.php">Companies</a>
                    
                    <button class="theme-toggle-btn" id="themeToggleBtn" title="Switch Theme">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </button>

                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#authModal" onclick="setModalRole('job_seeker')">Get Started</button>
                </div>
            </div>
        </div>
    </nav>

    <?php if (!empty($alert_message)): ?>
        <div class="container" style="margin-top: 100px; mb-0;">
            <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show text-center" role="alert">
                <strong><?php echo $alert_message; ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <header class="hero-custom">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-9">
                    <span class="badge-custom">PROFESSIONAL JOB PLATFORM</span>
                    <h1>Find Your Dream Job <br> With Top Companies</h1>
                    <p>A modern recruitment platform that connects talented professionals with trusted employers around the world.</p>
                    
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 mt-5">
                        <a href="application.php" class="btn btn-primary-custom text-decoration-none d-flex align-items-center justify-content-center">Find Jobs</a>
                        <a href="job_postings.php" class="btn btn-light px-4 py-3 fw-semibold rounded-3 shadow-sm text-decoration-none">
                         Post a Job</a>
                </div>
            </div>
        </div>
    </header>

    <section id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Platform Features</h2>
                <p class="text-muted">Everything you need for modern hiring and job searching.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <?php
                    $target_registration_file = "user-registration.php"; 
                    if (file_exists("user_registration.php")) { $target_registration_file = "user_registration.php"; }
                    elseif (file_exists("user_regestration.php")) { $target_registration_file = "user_regestration.php"; }
                    ?>
                    <a href="<?php echo $target_registration_file; ?>" class="text-decoration-none h-100 d-block">
                        <div class="feature-card-custom h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3>User Registration</h3>
                                <span class="badge bg-dark rounded-pill metric-badge-pill"><?php echo $user_count; ?></span>
                            </div>
                            <p>Create accounts for job seekers and employers with secure authentication rules.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="resume.php" class="text-decoration-none h-100 d-block">
                        <div class="feature-card-custom h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3>Resume Upload</h3>
                                <span class="badge bg-dark rounded-pill metric-badge-pill"><?php echo $resume_count; ?></span>
                            </div>
                            <p>Upload resumes, CVs, and tracking parameters quickly with modern PDF support.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <?php
                    $target_posting_file = "job_postings.php";
                    if (file_exists("job_postings.php")) { $target_posting_file = "job_postings.php"; }
                    elseif (file_exists("job_postings.php")) { $target_posting_file = "job_postings.php"; }
                    ?>
                    <a href="<?php echo $target_posting_file; ?>" class="text-decoration-none h-100 d-block">
                        <div class="feature-card-custom h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3>Job Posting</h3>
                                <span class="badge bg-dark rounded-pill metric-badge-pill"><?php echo $job_count; ?></span>
                            </div>
                            <p>Employers can publish and manage vacancies with standard localized parameters.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="application.php" class="text-decoration-none h-100 d-block">
                        <div class="feature-card-custom h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3>Application Tracking</h3>
                                <span class="badge bg-dark rounded-pill metric-badge-pill"><?php echo $app_count; ?></span>
                            </div>
                            <p>Track candidate application statuses, lists, records, and active hiring states.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-lg-4">
                    <a href="aboutus.php" class="text-decoration-none h-100 d-block">
                        <div class="feature-card-custom h-100">
                            <h3>Career Growth</h3>
                            <p>Connect candidates with industrial opportunities designed to build scalable engineering and developer paths.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="main-footer-custom mt-auto">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h3 class="footer-brand-title">Job<span>Portal</span></h3>
                    <p class="mt-3">Connecting talented professionals with the best corporate structures worldwide.</p>
                </div>
                <div class="col-lg-2">
                    <h5 class="text-white mb-3">Candidates</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="application.php" class="footer-link-item">Browse Jobs</a></li>
                        <li class="mb-2"><a href="https://flowcv.com/" class="footer-link-item">Resume Builder</a></li>
                        <li class="mb-2"><a href="application.php" class="footer-link-item">Applications</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h5 class="text-white mb-3">Employers</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="job_postings.php" class="footer-link-item">Post Jobs</a></li>
                        <li class="mb-2"><a href="#" class="footer-link-item">Talent Pool</a></li>
                        <li class="mb-2"><a href="#" class="footer-link-item">Resources</a></li>
                    </ul>
                </div>
              <div class="col-lg-4">
    <h5 class="text-white mb-3 fw-bold">Stay Updated</h5>
    <p class="text-white-50 mb-3">Subscribe for latest job alerts and hiring updates.</p>
    
    <div class="input-group custom-newsletter-group">
        <input type="email" class="form-control border-0 text-white bg-transparent shadow-none" placeholder="Enter your email">
        <button class="btn btn-primary px-4 fw-medium" type="button">Join</button>
    </div>
</div>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <p class="mb-2 mb-md-0">&copy; 2026 JobPortal. All rights reserved.</p>
                <div class="d-flex gap-4">
                    <a href="#" class="footer-link-item">LinkedIn</a>
                    <a href="#" class="footer-link-item">Twitter</a>
                    <a href="#" class="footer-link-item">Facebook</a>
                    <a href="#" class="footer-link-item">GitHub</a>
                </div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content modal-content-custom">
                <div class="modal-header border-0 pb-0">
                    <h3 class="fw-bold mb-0">Create Account</h3>
                    <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;

        // On boot check local memory state
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

        function setModalRole(role) {
            const select = document.getElementById('userRole');
            if (role && select) { select.value = role; }
        }
    </script>
</body>
</html>