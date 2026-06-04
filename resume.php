<?php
// 1. INITIALIZE SYSTEM SESSIONS FOR ALERT MANAGEMENT
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. TURN ON DIAGNOSTICS FOR DEVELOPMENT
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 3. DATABASE CONFIGURATION SETUP
$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database Connection Failed : " . mysqli_connect_error());
}

$alert_message = "";
$alert_class = "";

// 🚀 FOREIGN KEY VALIDATION SCANNER BLOCK
$user_query = mysqli_query($conn, "SELECT id FROM users WHERE role = 'job_seeker' LIMIT 1");
if (mysqli_num_rows($user_query) > 0) {
    $user_row = mysqli_fetch_assoc($user_query);
    $mock_user_id = $user_row['id'];
} else {
    mysqli_query($conn, "INSERT INTO users (fullname, email, password, role, status) VALUES ('Test Candidate', 'candidate@gmail.com', '123', 'job_seeker', 'Active')");
    $mock_user_id = mysqli_insert_id($conn);
}

/* =========================================================================
   HANDLE FORM SUBMISSIONS (BOTH FILE UPLOADER AND ONLINE BUILDER)
   ========================================================================= */

// MODE A: HARDCOPY FILE UPLOADER POOL INPUT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_resume'])) {
    $profileTitle = mysqli_real_escape_string($conn, trim($_POST['profileTitle']));
    $experienceYears = mysqli_real_escape_string($conn, trim($_POST['experience']));

    if (isset($_FILES['resumeFile']) && $_FILES['resumeFile']['error'] == 0) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . "_" . basename($_FILES['resumeFile']['name']);
        $targetFile = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['pdf', 'doc', 'docx'];

        if ($_FILES['resumeFile']['size'] > 5000000) {
            $alert_message = "File is too large. Maximum size is 5MB.";
            $alert_class = "danger";
        } elseif (!in_array($fileType, $allowedTypes)) {
            $alert_message = "Only PDF, DOC and DOCX files are allowed.";
            $alert_class = "danger";
        } else {
            if (move_uploaded_file($_FILES['resumeFile']['tmp_name'], $targetFile)) {
                $insert = "INSERT INTO resumes (user_id, profile_title, experience_years, file_path) VALUES ('$mock_user_id', '$profileTitle', '$experienceYears', '$targetFile')";
                if (mysqli_query($conn, $insert)) {
                    $alert_message = "Resume uploaded successfully to system folder!";
                    $alert_class = "success";
                } else {
                    $alert_message = "Database Error: " . mysqli_error($conn);
                    $alert_class = "danger";
                }
            } else {
                $alert_message = "File upload failed.";
                $alert_class = "danger";
            }
        }
    } else {
        $alert_message = "Please choose a valid resume file.";
        $alert_class = "danger";
    }
}

// MODE B: NEW ONLINE RESUME BUILDER LOGIC FIELD HANDLER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['build_resume_submit'])) {
    $profileTitle = mysqli_real_escape_string($conn, trim($_POST['builderTitle']));
    $experienceYears = mysqli_real_escape_string($conn, trim($_POST['builderExp']));
    $skills = mysqli_real_escape_string($conn, trim($_POST['builderSkills']));
    $projects = mysqli_real_escape_string($conn, trim($_POST['builderProjects']));

    // Simulating instant layout compiling text data 
    $generatedContent = "Candidate Profile: " . $profileTitle . "\nExperience: " . $experienceYears . " Years\nSkills: " . $skills . "\nCore Projects: " . $projects;
    
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
    
    $fileName = "built_" . time() . ".txt";
    $targetFile = $uploadDir . $fileName;
    
    if (file_put_contents($targetFile, $generatedContent)) {
        $insert = "INSERT INTO resumes (user_id, profile_title, experience_years, file_path) VALUES ('$mock_user_id', '$profileTitle', '$experienceYears', '$targetFile')";
        if (mysqli_query($conn, $insert)) {
            $alert_message = "Success! Your resume has been cleanly built online and saved to your profile.";
            $alert_class = "success";
        } else {
            $alert_message = "Database Sync Error: " . mysqli_error($conn);
            $alert_class = "danger";
        }
    } else {
        $alert_message = "Unable to write temporary document layout matrix.";
        $alert_class = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload & Build Resume | JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease; }
        body { min-height:100vh; background:linear-gradient(135deg, #dff6ff, #b8e8fc, #98c1ff); font-family:'Poppins',sans-serif; }

        /* ================= DYNAMIC 🌗 DARK MASTER CONTROLS ================= */
        body.dark-mode { background:#0f172a; color:#f8fafc; }
        body.dark-mode .navbar-custom { background:rgba(15,23,42,0.8) !important; border-bottom:1px solid rgba(255,255,255,0.05); }
        body.dark-mode .upload-card { background:#1e293b; border-color:rgba(255,255,255,0.05); box-shadow:0 10px 30px rgba(0,0,0,0.3); }
        body.dark-mode .upload-area { background:#334155; border-color:#0AC4E0; }
        body.dark-mode .upload-area:hover { background:#475569; }
        body.dark-mode .text-muted { color:#94a3b8 !important; }
        body.dark-mode .form-control { background:#334155; border-color:#475569; color:#ffffff; }
        body.dark-mode .form-control:focus { border-color:#0AC4E0; }
        body.dark-mode .form-control::placeholder { color:#94a3b8; }
        body.dark-mode label { color:#e2e8f0; }
        body.dark-mode .nav-link-custom { color:#cbd5e1 !important; }
        body.dark-mode .btn-mode-switch { background: #334155; color: #ffffff; border-color: #475569; }
        body.dark-mode .btn-mode-switch.active { background: #0AC4E0; color: #0f172a; border-color: #0AC4E0; }

        .navbar-custom { background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); border-bottom:1px solid rgba(255,255,255,0.2); }
        .logo { text-decoration:none; font-size:1.6rem; font-weight:800; color:#0AC4E0; }
        .upload-card { background:rgba(255,255,255,0.25); backdrop-filter:blur(15px); border-radius:25px; border:1px solid rgba(255,255,255,0.2); box-shadow:0 8px 32px rgba(0,0,0,0.15); padding:40px; }
        .upload-area { border:2px dashed #0AC4E0; border-radius:20px; padding:35px; background:rgba(255,255,255,0.4); cursor:pointer; transition:0.3s; }
        .upload-area:hover { background:rgba(255,255,255,0.7); transform:translateY(-2px); }
        .upload-icon { font-size:3rem; color:#0AC4E0; }
        .form-control { border-radius:12px; padding:12px; border: 1px solid #cbd5db; }
        .form-control:focus { box-shadow:none; border-color:#0AC4E0; }
        .btn-upload { background:linear-gradient(135deg, #0AC4E0, #0284c7); border:none; padding:12px; border-radius:12px; font-weight:600; transition:0.3s; }
        .btn-upload:hover { transform:translateY(-2px); box-shadow: 0 5px 15px rgba(10, 196, 224, 0.3); }
        .theme-toggle-btn { background:none; border:none; font-size:1.3rem; color:#475569; cursor:pointer; display:flex; align-items:center; justify-content:center; padding:8px; border-radius:50%; }
        body.dark-mode .theme-toggle-btn { color:#fbbf24; }
        
        /* SLEEK VIEW SWITCH CONTROL BUTTON GROUPS */
        .btn-mode-switch { background: #ffffff; color: #475569; font-weight: 600; border: 1px solid #cbd5db; padding: 10px 20px; border-radius: 10px; }
        .btn-mode-switch.active { background: #0AC4E0; color: white; border-color: #0AC4E0; }
        textarea.form-control { resize: none; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="logo">JobPortal</a>
            <button class="theme-toggle-btn" id="themeToggleBtn"><i class="bi bi-moon-fill" id="themeIcon"></i></button>
        </div>
    </nav>

    <?php if(!empty($alert_message)) : ?>
        <div class="container mt-4">
            <div class="alert alert-<?php echo $alert_class; ?> alert-dismissible fade show text-center">
                <strong><?php echo $alert_message; ?></strong>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12">
                
                <div class="d-flex justify-content-center gap-3 mb-4">
                    <button type="button" id="switchUploadBtn" class="btn btn-mode-switch active" onclick="switchMode('upload')"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload File</button>
                    <button type="button" id="switchBuilderBtn" class="btn btn-mode-switch" onclick="switchMode('builder')"><i class="bi bi-magic me-2"></i>Build Online</button>
                </div>

                <div class="upload-card">
                    
                    <div id="uploadPanel">
                        <h2 class="fw-bold mb-2 text-center">Upload Your Resume</h2>
                        <p class="text-muted mb-4 text-center">Apply to jobs faster with your professional document.</p>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="upload-area mb-4 text-center" onclick="document.getElementById('resumeFile').click()">
                                <i class="bi bi-cloud-arrow-up-fill upload-icon"></i>
                                <h5 class="mt-3">Drag & Drop Resume</h5>
                                <p class="text-muted mb-0">or click to browse local folders</p>
                                <input type="file" name="resumeFile" id="resumeFile" class="d-none" onchange="showFileName()">
                                <div id="fileName" class="file-name mt-2 fw-medium text-success"></div>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold mb-2">Professional Title</label>
                                <input type="text" name="profileTitle" class="form-control" placeholder="Software Developer">
                            </div>
                            <div class="mb-4">
                                <label class="fw-semibold mb-2">Years of Experience</label>
                                <input type="number" name="experience" class="form-control" placeholder="3">
                            </div>
                            <button type="submit" name="submit_resume" class="btn btn-upload w-100 text-white">Upload Hardcopy Profile</button>
                        </form>
                    </div>

                    <div id="builderPanel" class="d-none">
                        <h2 class="fw-bold mb-2 text-center">Build Professional Resume</h2>
                        <p class="text-muted mb-4 text-center">Type in your credentials to map a sleek corporate text resume profile instantly.</p>
                        
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="fw-semibold mb-2">Target Job Designation</label>
                                <input type="text" name="builderTitle" class="form-control" placeholder="Senior Web Developer" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold mb-2">Total Experience Years</label>
                                <input type="number" name="builderExp" class="form-control" placeholder="4" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold mb-2">Key Frameworks & Core Skills</label>
                                <textarea name="builderSkills" rows="3" class="form-control" placeholder="HTML5, CSS3, PHP, MySQL, Object-Oriented Python, Git Version Control" required></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="fw-semibold mb-2">Core Projects Descriptions</label>
                                <textarea name="builderProjects" rows="4" class="form-control" placeholder="1. Job Portal App - Full stack web development with active search metrics filtering.&#10;2. Café Management System - Local server database integration layout handling." required></textarea>
                            </div>
                            <button type="submit" name="build_resume_submit" class="btn btn-upload w-100 text-white">Compile & Build Profile</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        // 🔄 ANIMATION TRANSITION TOGGLE KEY VIEW MODULE
        function switchMode(mode) {
            const uploadBtn = document.getElementById('switchUploadBtn');
            const builderBtn = document.getElementById('switchBuilderBtn');
            const uploadPanel = document.getElementById('uploadPanel');
            const builderPanel = document.getElementById('builderPanel');

            if (mode === 'upload') {
                uploadBtn.classList.add('active');
                builderBtn.classList.remove('active');
                uploadPanel.classList.remove('d-none');
                builderPanel.classList.add('d-none');
            } else {
                builderBtn.classList.add('active');
                uploadBtn.classList.remove('active');
                builderPanel.classList.remove('d-none');
                uploadPanel.classList.add('d-none');
            }
        }

        function showFileName(){
            const input = document.getElementById('resumeFile');
            const fileName = document.getElementById('fileName');
            if(input.files.length > 0){
                fileName.innerHTML = "<i class='bi bi-check2-all me-1'></i> Ready: " + input.files[0].name;
            }
        }

        // 🌙 LOCAL MEMORY SAVED THEME TOGGLER LOCKS
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
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>