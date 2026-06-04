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
    if (select) { select.value = role; }
  }
</script>
</body>
</html>