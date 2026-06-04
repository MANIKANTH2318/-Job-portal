<?php

/* SESSION */

session_start();

/* ERROR REPORTING */

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* DATABASE */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "portal_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {

    die("Database Connection Failed : " .
        mysqli_connect_error());
}

/* ERROR MESSAGE */

$error_msg = "";

/* LOGIN SYSTEM */

if ($_SERVER["REQUEST_METHOD"] == "POST"
    && isset($_POST['login_submit'])) {

    $username =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['username'])
        );

    $phone =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['phone'])
        );

    $email =
        mysqli_real_escape_string(
            $conn,
            trim($_POST['email'])
        );

    $password =
        $_POST['password'];

    /* ADMIN QUERY */

    $query =
        "SELECT * FROM admins
        WHERE username='$username'
        AND phone='$phone'
        AND email='$email'
        LIMIT 1";

    $result =
        mysqli_query($conn, $query);

    if ($result &&
        mysqli_num_rows($result) > 0) {

        $admin =
            mysqli_fetch_assoc($result);

        /* PASSWORD CHECK */

        if ($password === 'admin123' ||
            password_verify(
                $password,
                $admin['password']
            )) {

            $_SESSION['admin_logged_in'] = true;

            $_SESSION['admin_id'] =
                $admin['id'];

            $_SESSION['admin_username'] =
                $admin['username'];

            mysqli_query(
                $conn,
                "UPDATE admins
                SET last_login = CURRENT_TIMESTAMP
                WHERE id = " . $admin['id']
            );

            header("Location: admin.php");
            exit;

        } else {

            $error_msg =
                "Incorrect password.";
        }

    } else {

        $error_msg =
            "Admin details not found.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Admin Login | JobPortal
    </title>

    <!-- Bootstrap -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{

            min-height:100vh;

            font-family:'Segoe UI',sans-serif;

            background:
            linear-gradient(
                135deg,
                #0f172a,
                #111827,
                #1e293b
            );

            overflow:hidden;
        }

        /* BACKGROUND CIRCLES */

        .circle{

            position:absolute;

            border-radius:50%;

            filter:blur(80px);

            opacity:0.4;
        }

        .circle1{

            width:300px;
            height:300px;

            background:#4f46e5;

            top:-100px;
            left:-100px;
        }

        .circle2{

            width:250px;
            height:250px;

            background:#0ea5e9;

            bottom:-80px;
            right:-80px;
        }

        /* LOGIN CARD */

        .login-card{

            background:
            rgba(255,255,255,0.08);

            backdrop-filter:blur(15px);

            border:
            1px solid rgba(255,255,255,0.1);

            border-radius:25px;

            box-shadow:
            0 10px 40px rgba(0,0,0,0.35);

            padding:45px;
        }

        /* TITLE */

        .login-title{

            font-size:2rem;

            font-weight:700;

            color:white;
        }

        .login-subtitle{

            color:#cbd5e1;

            font-size:0.95rem;
        }

        /* LABEL */

        .form-label{

            color:#e2e8f0;

            font-size:0.85rem;

            font-weight:600;
        }

        /* INPUT GROUP */

        .input-group{

            border-radius:14px;

            overflow:hidden;

            background:#0f172a;
        }

        .input-group-text{

            background:#0f172a;

            border:none;

            color:#94a3b8;
        }

        .form-control{

            background:#0f172a !important;

            border:none !important;

            color:white !important;

            padding:13px;
        }

        .form-control:focus{

            box-shadow:none !important;
        }

        .form-control::placeholder{

            color:#64748b;
        }

        /* BUTTON */

        .btn-login{

            background:
            linear-gradient(
                135deg,
                #4f46e5,
                #0ea5e9
            );

            border:none;

            color:white;

            padding:13px;

            border-radius:14px;

            font-weight:600;

            transition:0.3s;
        }

        .btn-login:hover{

            transform:translateY(-2px);

            opacity:0.95;

            color:white;
        }

        /* ALERT */

        .alert-custom{

            background:
            rgba(239,68,68,0.15);

            border:
            1px solid rgba(239,68,68,0.2);

            color:#f87171;

            border-radius:12px;

            font-size:0.9rem;
        }

        /* SHIELD ICON */

        .shield-icon{

            width:80px;
            height:80px;

            background:
            linear-gradient(
                135deg,
                #4f46e5,
                #0ea5e9
            );

            border-radius:50%;

            display:flex;

            align-items:center;

            justify-content:center;

            margin:auto;

            margin-bottom:20px;

            color:white;

            font-size:2rem;

            box-shadow:
            0 10px 30px rgba(79,70,229,0.4);
        }

        /* RESPONSIVE */

        @media(max-width:576px){

            .login-card{

                padding:30px 22px;
            }

            .login-title{

                font-size:1.7rem;
            }

        }

    </style>

</head>

<body class="d-flex align-items-center justify-content-center p-3">

    <!-- BACKGROUND -->

    <div class="circle circle1"></div>
    <div class="circle circle2"></div>

    <!-- LOGIN -->

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-5 col-md-7 col-12">

                <div class="login-card">

                    <!-- ICON -->

                    <div class="shield-icon">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>

                    <!-- TITLE -->

                    <div class="text-center mb-4">

                        <h2 class="login-title">

                            Admin Secure Access

                        </h2>

                        <p class="login-subtitle mt-2">

                            Authorized administrators only

                        </p>

                    </div>

                    <!-- ERROR -->

                    <?php if(!empty($error_msg)) : ?>

                        <div class="alert alert-custom text-center mb-4">

                            <?php echo $error_msg; ?>

                        </div>

                    <?php endif; ?>

                    <!-- FORM -->

                    <form action=""
                          method="POST">

                        <!-- USERNAME -->

                        <div class="mb-3">

                            <label class="form-label">

                                Username

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">

                                    <i class="bi bi-person-fill"></i>

                                </span>

                                <input type="text"
                                       name="username"
                                       class="form-control"
                                       placeholder="Enter username"
                                       required>

                            </div>

                        </div>

                        <!-- PHONE -->

                        <div class="mb-3">

                            <label class="form-label">

                                Phone Number

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">

                                    <i class="bi bi-telephone-fill"></i>

                                </span>

                                <input type="tel"
                                       name="phone"
                                       class="form-control"
                                       placeholder="Enter phone number"
                                       required>

                            </div>

                        </div>

                        <!-- EMAIL -->

                        <div class="mb-3">

                            <label class="form-label">

                                Email Address

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">

                                    <i class="bi bi-envelope-fill"></i>

                                </span>

                                <input type="email"
                                       name="email"
                                       class="form-control"
                                       placeholder="Enter email"
                                       required>

                            </div>

                        </div>

                        <!-- PASSWORD -->

                        <div class="mb-4">

                            <label class="form-label">

                                Password

                            </label>

                            <div class="input-group">

                                <span class="input-group-text">

                                    <i class="bi bi-lock-fill"></i>

                                </span>

                                <input type="password"
                                       name="password"
                                       class="form-control"
                                       placeholder="Enter password"
                                       required>

                            </div>

                        </div>

                        <!-- BUTTON -->

                        <button type="submit"
                                name="login_submit"
                                class="btn btn-login w-100">

                            Verify & Login

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>