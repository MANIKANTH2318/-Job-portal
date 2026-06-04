<?php
// 1. INITIALIZE SYSTEM SESSIONS FOR POST-REDIRECT-GET HANDLING
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

// Ensure the correct users table structure exists seamlessly without job columns
$table_check = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $table_check);

$alert_message = "";
$alert_class = "";

// PRG Session Message Catching Loop
if (isset($_SESSION['reg_flash'])) {
    $alert_message = $_SESSION['reg_flash']['msg'];
    $alert_class = $_SESSION['reg_flash']['class'];
    unset($_SESSION['reg_flash']);
}

// 4. SECURE USER REGISTRATION PROCESSING ENGINE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registration_submit'])) {

    $fullName = mysqli_real_escape_string($conn, trim($_POST['fullName']));
    $email    = mysqli_real_escape_string($conn, strtolower(trim($_POST['email']))); 
    $userRole = mysqli_real_escape_string($conn, $_POST['userRole']);
    $password = $_POST['password'];

    if (!empty($fullName) && !empty($email) && !empty($password)) {

        // BACKEND GMAIL VALIDATION
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $alert_message = "Please enter a valid structure email address.";
            $alert_class = "alert-danger";

        } elseif (!str_ends_with($email, '@gmail.com')) {

            $alert_message = "Registration restricted! You must sign up with a valid @gmail.com address.";
            $alert_class = "alert-danger";

        } else {

            // Check if email already exists in the system
            $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");

            if (mysqli_num_rows($check_email) > 0) {

                $alert_message = "This Gmail address is already registered! Please use a different one.";
                $alert_class = "alert-danger";

            } else {

                // Cryptographically hash the plain-text password for security
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // INSERT USER
                $insert_query = "INSERT INTO users (fullname, email, password, role, status) 
                                 VALUES ('$fullName', '$email', '$hashedPassword', '$userRole', 'Active')";

                if (mysqli_query($conn, $insert_query)) {

                    $_SESSION['reg_flash'] = [
                        'msg' => "Success! Your profile account has been created successfully.",
                        'class' => "alert-success"
                    ];

                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;

                } else {

                    $alert_message = "Database insertion error: " . mysqli_error($conn);
                    $alert_class = "alert-danger";
                }
            }
        }

    } else {

        $alert_message = "Validation Error: Please fill out all required fields.";
        $alert_class = "alert-danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Registration - JobPortal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        *{
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        body {
            background-color: #E3FDFD;
            color: #1f2937;
            font-family: 'Poppins', sans-serif;
        }

        /* 🌙 DARK MODE */
        body.dark-mode {
            background: #0f172a;
            color: #f8fafc;
        }

        body.dark-mode .navbar-custom {
            background: rgba(15, 23, 42, 0.8) !important;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        body.dark-mode .card-form-custom {
            background: #1e293b;
            border-color: rgba(255,255,255,0.05);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        body.dark-mode .text-dark {
            color: #ffffff !important;
        }

        body.dark-mode .text-muted {
            color: #94a3b8 !important;
        }

        body.dark-mode .form-group-label {
            color: #cbd5e1;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #334155;
            border-color: #475569;
            color: #ffffff;
        }

        body.dark-mode .form-control::placeholder {
            color: #94a3b8;
        }

        body.dark-mode .logo-custom {
            color: #0AC4E0;
        }

        .navbar-custom {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .logo-custom {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0AC4E0;
            text-decoration: none;
        }

        .card-form-custom {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        }

        .form-control,
        .form-select {
            border-radius: 12px !important;
            padding: 12px !important;
            border: 1px solid #d1d5db;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0AC4E0;
            box-shadow: 0 0 0 0.15rem rgba(10,196,224,0.25);
        }

        .form-group-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .btn-submit-custom {
            background: linear-gradient(135deg, #0AC4E0, #0284c7);
            border: none;
            color: white;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 10px 25px rgba(10,196,224,0.2);
        }

        .btn-submit-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(10,196,224,0.3);
            color: white;
        }

        /* THEME BUTTON */
        .theme-toggle-btn {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 50%;
        }

        body.dark-mode .theme-toggle-btn {
            color: #fbbf24;
        }

        .theme-toggle-btn:hover {
            background: rgba(0,0,0,0.05);
        }

    </style>

</head>

<body class="d-flex flex-column min-vh-100">

    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">

        <div class="container d-flex justify-content-between align-items-center">

            <a class="logo-custom" href="index.php">← Back to JobPortal</a>

            <button class="theme-toggle-btn" id="themeToggleBtn">
                <i class="bi bi-moon-fill" id="themeIcon"></i>
            </button>

        </div>

    </nav>

    <?php if (!empty($alert_message)): ?>

        <div class="container mt-4" style="max-width: 550px;">

            <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show text-center mb-0" role="alert">

                <strong><?php echo $alert_message; ?></strong>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>

            </div>

        </div>

    <?php endif; ?>

    <div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">

        <div class="w-100" style="max-width: 550px;">

            <div class="card-form-custom p-4 p-md-5">

                <div class="text-center mb-4">

                    <h2 class="fw-bold text-dark mb-1">Create Account</h2>

                    <p class="text-muted small">
                        Join our network as either a candidate job seeker or hiring manager.
                    </p>

                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">

                    <div class="mb-3">

                        <label class="form-group-label">Full Name *</label>

                        <input type="text" name="fullName" class="form-control" placeholder="Enter your full name" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-group-label">Email Address *</label>

                        <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required>

                    </div>

                    <div class="mb-3">

                        <label class="form-group-label">Account Type *</label>

                        <select name="userRole" class="form-select">

                            <option value="job_seeker">Job Seeker</option>

                            <option value="employer">Employer</option>

                        </select>

                    </div>

                    <div class="mb-4">

                        <label class="form-group-label">Password *</label>

                        <input type="password" name="password" class="form-control" placeholder="Create secure password" required>

                    </div>

                    <button type="submit" name="registration_submit" class="btn btn-submit-custom w-100">
                        Register Profile
                    </button>

                </form>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;

        // Load Saved Theme
        if (localStorage.getItem('theme') === 'dark') {

            body.classList.add('dark-mode');

            themeIcon.classList.replace('bi-moon-fill', 'bi-sun-fill');
        }

        // Toggle Theme
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