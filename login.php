<?php
session_start();
if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = mysqli_real_escape_string($conn, $_POST['login_input']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM users WHERE (email='$login_input' OR phone='$login_input') AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'renter') {
            header("Location: renter_dashboard.php");
        } else {
            header("Location: lender_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid Email/Phone or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agriculture Equipment Rental System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
            --brand-light-bg: #f4f9f5;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: #333;
        }

        /* --- Header Brand Logo --- */
        .brand-logo-icon {
            width: 46px;
            height: 46px;
            background-color: var(--brand-green);
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 10px rgba(45, 106, 79, 0.25);
        }

        .brand-text-main {
            font-weight: 800;
            color: var(--brand-green);
            font-size: 1.25rem;
            line-height: 1.1;
            letter-spacing: 0.5px;
        }

        .brand-text-sub {
            font-size: 0.68rem;
            font-weight: 700;
            color: #555;
            letter-spacing: 1px;
        }

        .nav-link {
            font-weight: 500;
            color: #444 !important;
            margin: 0 10px;
        }

        .nav-link:hover {
            color: var(--brand-green) !important;
        }

        .lang-dropdown {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 6px 16px;
            font-size: 0.9rem;
        }

        /* --- Welcome & Tractor Image Styling --- */
        .welcome-title {
            color: var(--brand-green);
            font-weight: 800;
            font-size: 2.3rem;
        }

        .welcome-sub {
            color: #555;
            font-size: 1.05rem;
        }

        .tractor-hero-card {
            background: transparent;
            border-radius: 0;
            padding: 0;
            overflow: visible;
            box-shadow: none;
            width: 100%;
            margin-bottom: 15px;
        }

        .tractor-hero-card img {
            width: 100%;
            height: auto;
            max-height: 340px;
            object-fit: contain;
            display: block;
        }

        /* Feature Highlights */
        .feature-icon-box {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: 2.5px solid var(--brand-green);
            color: var(--brand-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 8px;
            background-color: #fff;
        }

        .feature-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111;
            margin-bottom: 2px;
        }

        .feature-sub {
            font-size: 0.8rem;
            color: #666;
            line-height: 1.2;
        }

        /* --- Login Card Styling --- */
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 35px 30px;
            background: #ffffff;
        }

        .avatar-circle {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            border: 2px solid var(--brand-green);
            color: var(--brand-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 10px auto;
        }

        .login-heading {
            font-weight: 800;
            color: #111;
            margin-bottom: 25px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px 12px 42px;
            font-size: 0.95rem;
            border: 1px solid #dce1e5;
        }

        .form-control:focus {
            border-color: var(--brand-green);
            box-shadow: 0 0 0 0.2rem rgba(45, 106, 79, 0.15);
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-left {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .input-icon-right {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
        }

        .btn-brand-green {
            background-color: var(--brand-green);
            color: #fff;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-brand-green:hover {
            background-color: var(--brand-green-hover);
            color: #fff;
        }

        .btn-brand-green .arrow-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .btn-outline-register {
            border: 1px solid #dce1e5;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            display: block;
            text-align: center;
            background: #fff;
        }

        .btn-outline-register:hover {
            background-color: #f8f9fa;
            color: var(--brand-green);
        }

        .terms-box {
            background-color: #f4f9f5;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 0.85rem;
            color: #444;
        }

        /* Footer */
        .footer-bottom {
            background-color: var(--brand-green);
            color: #ffffff;
            padding: 15px 0;
            text-align: center;
            font-size: 0.9rem;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <!-- NAVBAR HEADER -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="#">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-tractor"></i>
                </div>
                <div>
                    <div class="brand-text-main">AGRICULTURE</div>
                    <div class="brand-text-sub">EQUIPMENT RENTAL SYSTEM</div>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center me-4">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fa-solid fa-house me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">How It Works</a>
                    </li>
                </ul>

                <div class="dropdown">
                    <button class="btn lang-dropdown dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-globe text-secondary"></i> English
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#">English</a></li>
                        <li><a class="dropdown-item" href="#">ಕನ್ನಡ (Kannada)</a></li>
                        <li><a class="dropdown-item" href="#">हिंदी (Hindi)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN SIDE-BY-SIDE CONTENT -->
    <div class="container my-4">
        <div class="row align-items-center g-4">
            
            <!-- LEFT COLUMN: Image & Text -->
            <div class="col-lg-6">
                <h1 class="welcome-title mb-2">Welcome Back!</h1>
                <p class="welcome-sub mb-3">Login to your account and start renting the best agricultural equipment near you.</p>

                <!-- Tractor Image -->
                <div class="tractor-hero-card">
                    <img src="images/tractor.jpg" alt="Green Agriculture Tractor">
                </div>

                <!-- 3 Feature Icons -->
                <div class="row text-center mt-3">
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <span class="feature-title">Secure & Safe</span>
                            <span class="feature-sub">Your data is safe<br>with us.</span>
                        </div>
                    </div>
                    <div class="col-4 border-start border-end">
                        <div class="d-flex flex-column align-items-center">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <span class="feature-title">24x7 Access</span>
                            <span class="feature-sub">Access your account<br>anytime, anywhere.</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span class="feature-title">Near You</span>
                            <span class="feature-sub">Find equipment<br>available nearby.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Login Form Box -->
            <div class="col-lg-6">
                <div class="login-card">
                    
                    <div class="avatar-circle">
                        <i class="fa-regular fa-user"></i>
                    </div>

                    <h3 class="text-center login-heading">Login</h3>

                    <?php if($error != ""): ?>
                        <div class="alert alert-danger py-2 text-center small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Email / Phone -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Email / Phone Number</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-regular fa-user input-icon-left"></i>
                                <input type="text" name="login_input" class="form-control" placeholder="Enter your email or mobile number" required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-lock input-icon-left"></i>
                                <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                                <i class="fa-regular fa-eye input-icon-right" id="togglePassword"></i>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4 small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label text-secondary" for="rememberMe">Remember Me</label>
                            </div>
                            <a href="#" class="text-success text-decoration-none fw-semibold">Forgot Password?</a>
                        </div>

                        <!-- Login Button -->
                        <button type="submit" class="btn btn-brand-green w-100 mb-3">
                            Login <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>
                        </button>
                    </form>

                    <!-- OR Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 my-0 text-muted">
                        <span class="px-3 small text-muted fw-semibold">OR</span>
                        <hr class="flex-grow-1 my-0 text-muted">
                    </div>

                    <!-- Register Link -->
                    <a href="register.php" class="btn-outline-register mb-4">
                        <i class="fa-solid fa-user-plus me-2 text-success"></i> Don't have an account? <strong class="text-success">Register</strong>
                    </a>

                    <!-- Terms Box -->
                    <div class="terms-box d-flex align-items-center gap-3">
                        <i class="fa-solid fa-shield-check text-success fa-lg"></i>
                        <div>
                            By logging in, you agree to our <a href="#" class="text-success text-decoration-none fw-semibold">Terms & Conditions</a> and <a href="#" class="text-success text-decoration-none fw-semibold">Privacy Policy</a>.
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container pt-3">
            <!-- Light Horizontal Line Above Categories -->
            <hr class="my-4 text-muted opacity-25">

            <div class="row g-4 justify-content-center">
                <div class="col-md-8 text-center">
                    <h6 class="fw-bold mb-3 text-success">POPULAR CATEGORIES</h6>
                    <div class="d-flex flex-wrap justify-content-center gap-4 small text-secondary">
                        <span><i class="fa-solid fa-tractor me-1 text-success"></i> Tractor</span>
                        <span><i class="fa-solid fa-wheat-awn me-1 text-success"></i> Harvesting</span>
                        <span><i class="fa-solid fa-droplet me-1 text-success"></i> Irrigation</span>
                        <span><i class="fa-solid fa-gears me-1 text-success"></i> Tillage</span>
                        <span><i class="fa-solid fa-seedling me-1 text-success"></i> Seeding</span>
                        <span><i class="fa-solid fa-spray-can me-1 text-success"></i> Spraying</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                © 2026 Agriculture Equipment Rental System. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS & Eye Icon Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#passwordInput');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>