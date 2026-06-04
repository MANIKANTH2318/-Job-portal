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

// PRG Session Message Catching Loop
if (isset($_SESSION['success_flash'])) {
    $alert_message = $_SESSION['success_flash'];
    $alert_class = "alert-success";
    unset($_SESSION['success_flash']); 
}

// 4. SECURE SIGN-UP INSERTION PROCESSING LOGIC WITH GMAIL VALIDATION
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
                    header("Location: aboutus.php?signup=success");
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

// 5. FETCH DYNAMIC QUANTITY METRICS
$user_count = 0; $job_count = 0; $app_count = 0;
if ($result = mysqli_query($conn, "SELECT id FROM users")) { $user_count = mysqli_num_rows($result); }
if ($result = mysqli_query($conn, "SELECT id FROM job_postings")) { $job_count = mysqli_num_rows($result); }
if ($result = mysqli_query($conn, "SELECT id FROM application_tracking")) { $app_count = mysqli_num_rows($result); }

// Fallback numbers for placeholder if database tables are empty
if ($user_count == 0) { $user_count = 1420; }
if ($job_count == 0) { $job_count = 310; }
if ($app_count == 0) { $app_count = 10450; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobPortal - Connect Employers and Job Seekers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease; font-family: 'Poppins', sans-serif; }
        body { background-color: #f8fafc; line-height: 1.6; color:#1f2937; }

        /* ================= 🌗 DARK MODE STYLES ================= */
        body.dark-mode { background:#0f172a; color:#f8fafc; }
        body.dark-mode .navbar-custom { background:rgba(15,23,42,0.85); border-bottom:1px solid rgba(255,255,255,0.05); }
        body.dark-mode .nav-link-custom { color:#cbd5e1 !important; }
        body.dark-mode .nav-link-custom:hover { color:#0AC4E0 !important; }
        body.dark-mode .text-secondary { color:#94a3b8 !important; }
        body.dark-mode .detail-item-custom { color:#e2e8f0; }
        body.dark-mode .stats-box-custom { background:rgba(30,41,59,0.85); border:1px solid rgba(255,255,255,0.05); }
        body.dark-mode .stats-box-custom p { color:#cbd5e1; }
        body.dark-mode .modal-content-custom { background:#1e293b; color:#fff; }
        body.dark-mode .form-control, body.dark-mode .form-select { background:#334155; border-color:#475569; color:#fff; }
        body.dark-mode .form-control::placeholder { color:#94a3b8; }
        body.dark-mode .form-group-label { color:#cbd5e1; }
        body.dark-mode .btn-close { filter:invert(1); }
        body.dark-mode .about-section-custom { background-image: linear-gradient(rgba(15,23,42,0.92), rgba(15,23,42,0.95)), url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'); }

        /* General Layout Classes */
        .navbar-custom { background-color: rgba(245, 245, 245, 0.95); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); border-bottom: 1px solid #e5e7eb; }
        .logo-custom { font-size: 1.25rem; font-weight: bold; color: #0AC4E0; text-decoration: none; }
        .nav-link-custom { text-decoration: none; color: #4b5563 !important; font-weight: 500; font-size: 0.9rem; transition: color 0.2s; position: relative; }
        .nav-link-custom::after { content: ''; position: absolute; width: 0%; height: 2px; left: 0; bottom: -4px; background: #0AC4E0; transition: 0.3s; }
        .nav-link-custom:hover::after { width: 100%; }
        .nav-link-custom:hover { color: #0AC4E0 !important; }

        .btn-primary-custom { background-color: #0AC4E0; color: white; padding: 0.5rem 1.25rem; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; transition: background-color 0.2s; }
        .btn-primary-custom:hover, .btn-primary-custom:focus { background-color: #08a3ba; color: white; }
        
        .about-section-custom { background-image: linear-gradient(rgba(255, 255, 255, 0.9), rgba(227, 253, 253, 0.95)), url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80'); background-size: cover; background-position: center; background-repeat: no-repeat; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .heading-accent-line::after { content: ''; display: block; width: 60px; height: 4px; background-color: #0AC4E0; margin-top: 0.5rem; border-radius: 2px; }
        .detail-item-custom { font-size: 0.95rem; color: #1f2937; font-weight: 500; }
        .detail-item-custom span { color: #0AC4E0; font-size: 1.2rem; }

        .stats-box-custom { background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-radius: 16px; border: 1px solid rgba(10, 196, 224, 0.2); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .stats-box-custom h3 { font-size: 2.5rem; color: #0284c7; font-weight: 800; }
        .stats-box-custom p { font-size: 0.85rem; color: #4b5563; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        .main-footer-custom { background-color: #111827; color: #9ca3af; font-size: 0.9rem; }
        .footer-brand-title span { color: #0AC4E0; }
        .footer-link-item { color: #9ca3af; text-decoration: none; transition: color 0.2s ease; }
        .footer-link-item:hover { color: #0AC4E0; }

        .input-newsletter-custom { background-color: #1f2937 !important; border: 1px solid #374151 !important; color: #ffffff !important; font-size: 0.85rem; }
        .btn-newsletter-custom { background-color: #0284c7; color: #ffffff; font-weight: 600; transition: background-color 0.2s ease; }
        .btn-newsletter-custom:hover { background-color: #0369a1; color: #ffffff; }

        .modal-content-custom { border-radius: 24px; border: none; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); padding: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); }
        .form-group-label { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; color: #4b5563; margin-bottom: 0.4rem; }
        .theme-toggle-btn { background:none; border:none; font-size:1.3rem; color:#475569; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:8px; border-radius:50%; }
        .theme-toggle-btn:hover { background:rgba(0,0,0,0.05); }
        body.dark-mode .theme-toggle-btn { color:#fbbf24; }
        body.dark-mode .theme-toggle-btn:hover { background:rgba(255,255,255,0.08); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
        <div class="container">
            <a class="logo-custom" href="index.php">JobPortal</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-3 mt-3 mt-lg-0">
                    <a class="nav-link-custom" href="index.php#features">Features</a>
                    <a class="nav-link-custom" href="application.php">Jobs</a>
                    <a class="nav-link-custom" href="aboutus.php">About Us</a>

                    <button class="theme-toggle-btn" id="themeToggleBtn">
                        <i class="bi bi-moon-fill" id="themeIcon"></i>
                    </button>

                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#authModal" onclick="setModalRole('job_seeker')">
                        Get Started
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <?php if (!empty($alert_message)): ?>
        <div class="container mt-4" style="max-width: 550px;">
            <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show text-center" role="alert">
                <strong><?php echo $alert_message; ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

    <section id="about-us" class="about-section-custom py-5 my-auto">
        <div class="container py-md-4">
            <div class="row align-items-center g-5">
                <div class="col-12 col-md-7 text-start">
                    <h2 class="fw-bold display-5 heading-accent-line mb-4">Our Mission</h2>
                    <p class="text-secondary mb-3 fs-5">
                        JobPortal simplifies the job-seeking and hiring process, bridging the gap between innovative employers and top-tier talent worldwide.
                    </p>
                    <p class="text-secondary mb-4 fs-5">
                        We believe that finding the right job or candidate shouldn't be complicated.
                    </p>
                    <div class="row row-cols-1 row-cols-sm-2 g-3 mt-2">
                        <div class="col d-flex align-items-center gap-2 detail-item-custom"><span>✓</span> Global Opportunities</div>
                        <div class="col d-flex align-items-center gap-2 detail-item-custom"><span>✓</span> Verified Employers</div>
                        <div class="col d-flex align-items-center gap-2 detail-item-custom"><span>✓</span> Smart Match Architecture</div>
                        <div class="col d-flex align-items-center gap-2 detail-item-custom"><span>✓</span> 24/7 Support</div>
                    </div>
                </div>

                <div class="col-12 col-md-5 text-center">
                    <div class="stats-box-custom p-5 mx-auto m-md-0" style="max-width:380px;">
                        <h3 class="fw-bold mb-1"><?php echo number_format($app_count); ?></h3>
                        <p class="mb-0">Successful Placements</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="main-footer-custom mt-auto">
        <div class="container pt-5 pb-4">
            <div class="row g-4 pb-4 border-bottom border-secondary">
                <div class="col-12 col-sm-6 col-lg-4">
                    <h3 class="h4 fw-bold text-white footer-brand-title mb-3">Job<span>Portal</span></h3>
                    <p class="mb-0 pe-lg-4">Connecting the world's best talent with top companies.</p>
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <h4 class="h6 fw-bold text-white text-uppercase mb-3">For Candidates</h4>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="application.php" class="footer-link-item">Browse Jobs</a></li>
                        <li class="mb-2"><a href="resume.php" class="footer-link-item">Build Resume</a></li>
                    </ul>
                </div>
                <div class="col-6 col-sm-3 col-lg-2">
                    <h4 class="h6 fw-bold text-white text-uppercase mb-3">For Employers</h4>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="job-posting.php" class="footer-link-item">Post a Job</a></li>
                    </ul>
                </div>
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                    <h4 class="h6 fw-bold text-white text-uppercase mb-3">Stay Updated</h4>
                    <form class="input-group" onsubmit="event.preventDefault();">
                        <input type="email" class="form-control input-newsletter-custom py-2" placeholder="Enter your email">
                        <button class="btn btn-newsletter-custom px-4">Join</button>
                    </form>
                </div>
            </div>
            <div class="row pt-4 align-items-center">
                <div class="col-12 col-sm-6">
                    <p class="mb-0 small">&copy; 2026 JobPortal. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content modal-content-custom">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title fs-4 fw-bold">Create Account</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="aboutus.php" method="POST">
                        <div class="mb-3">
                            <label class="form-group-label">Full Name</label>
                            <input type="text" name="fullName" class="form-control py-2" placeholder="Jane Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-group-label">Email Address</label>
                            <input type="email" name="email" class="form-control py-2" placeholder="jane@gmail.com" pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" title="Please enter a valid Gmail address ending in @gmail.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-group-label">Account Type</label>
                            <select id="userRole" name="userRole" class="form-select">
                                <option value="job_seeker">Job Seeker</option>
                                <option value="employer">Employer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-group-label">Password</label>
                            <input type="password" name="password" class="form-control py-2" placeholder="••••••••" required>
                        </div>
                        <button type="submit" name="signup_submit" class="btn btn-primary-custom w-100 py-2">Sign Up</button>
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
            if (role && select) { select.value = role; }
        }
    </script>
</body>
</html>