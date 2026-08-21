<?php
session_start();

if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name         = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email             = mysqli_real_escape_string($conn, $_POST['email']);
    $phone             = mysqli_real_escape_string($conn, $_POST['phone']);
    $address           = mysqli_real_escape_string($conn, $_POST['address']);
    $user_type         = mysqli_real_escape_string($conn, $_POST['user_type']);
    $password          = $_POST['password'];
    $confirm_password  = $_POST['confirm_password'];
    $security_question = mysqli_real_escape_string($conn, $_POST['security_question']);
    $security_answer   = mysqli_real_escape_string($conn, $_POST['security_answer']);

    // Check Terms & Conditions
    if (!isset($_POST['terms'])) {
        $error = "Please agree to the Terms and Conditions and Privacy Policy.";
    }

    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {

        // Check if email or phone already exists
        $check_sql = "SELECT * FROM users WHERE email='$email' OR phone='$phone'";
        $check_res = mysqli_query($conn, $check_sql);

        if ($check_res && mysqli_num_rows($check_res) > 0) {
            $error = "An account with this Email or Phone number already exists!";
        } else {

            // Securely hash the password before saving
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into database
            $insert_sql = "INSERT INTO users 
                           (full_name, email, phone, address, role, password, security_question, security_answer) 
                           VALUES 
                           ('$full_name', '$email', '$phone', '$address', '$user_type', '$hashed_password', '$security_question', '$security_answer')";
            
            if (mysqli_query($conn, $insert_sql)) {

                // Get newly created user ID
                $user_id = mysqli_insert_id($conn);

                // Set session variables (Auto-login)
                $_SESSION['user_id']   = $user_id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email']     = $email;
                $_SESSION['role']      = $user_type;

                // Select dashboard based on user_type selection
                $target_page = ($user_type === 'lender') 
                    ? 'lender_dashboard.php' 
                    : 'renter_dashboard.php';

                $success = "Registration successful! Redirecting to your dashboard...";

                echo "<script>
                        setTimeout(function(){ 
                            window.location.href = '$target_page'; 
                        }, 1500);
                      </script>";

            } else {
                $error = "Database Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AgriRent</title>

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
            background-color: #f8faf9;
            color: #333;
        }

        /* --- Navbar --- */
        .brand-logo-icon {
            width: 42px;
            height: 42px;
            background-color: var(--brand-green);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .brand-text-main {
            font-weight: 800;
            color: var(--brand-green);
            font-size: 1.25rem;
            line-height: 1;
        }

        .brand-text-sub {
            font-size: 0.65rem;
            font-weight: 700;
            color: #555;
            letter-spacing: 0.5px;
        }

        .nav-link {
            font-weight: 500;
            color: #444 !important;
            margin: 0 8px;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            color: var(--brand-green) !important;
        }

        .btn-outline-login {
            border: 1.5px solid var(--brand-green);
            color: var(--brand-green);
            border-radius: 10px;
            padding: 6px 20px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline-login:hover {
            background-color: var(--brand-green);
            color: #fff;
        }

        /* --- Outer Card Layout --- */
        .register-outer-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid #eef2f0;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        /* --- Left Banner Section --- */
        .left-banner-panel {
            background-color: var(--brand-light-bg);
            padding: 40px 30px 20px 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            height: 100%;
        }

        .banner-heading {
            font-weight: 800;
            color: #1b4332;
            font-size: 2rem;
            line-height: 1.2;
        }

        .banner-line {
            width: 40px;
            height: 4px;
            background-color: var(--brand-green);
            margin: 15px 0;
            border-radius: 2px;
        }

        .banner-subtext {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .banner-image-wrapper {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            margin-top: 10px;
            height: 380px;
        }

        .banner-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* --- Right Form Section --- */
        .right-form-panel {
            padding: 40px;
        }

        .form-header-title {
            font-weight: 800;
            font-size: 1.8rem;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-header-title i {
            color: var(--brand-green);
        }

        .form-header-sub {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #222;
            margin-bottom: 6px;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom .form-control,
        .input-group-custom .form-select {
            border-radius: 10px;
            padding: 10px 12px 10px 40px;
            font-size: 0.9rem;
            border: 1px solid #dce1e5;
            background-color: #fff;
        }

        .input-group-custom .form-control:focus,
        .input-group-custom .form-select:focus {
            border-color: var(--brand-green);
            box-shadow: 0 0 0 0.2rem rgba(45, 106, 79, 0.15);
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            font-size: 0.9rem;
            z-index: 5;
        }

        .input-icon-right {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            cursor: pointer;
            font-size: 0.9rem;
            z-index: 5;
        }

        /* Security Divider */
        .security-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0 20px 0;
            color: var(--brand-green);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .security-divider::before,
        .security-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1.5px solid #81c784;
        }

        .security-divider span {
            padding: 0 15px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Submit Button */
        .btn-register-submit {
            background-color: var(--brand-green);
            color: #ffffff;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(45, 106, 79, 0.2);
            transition: all 0.2s ease-in-out;
        }

        .btn-register-submit:hover {
            background-color: var(--brand-green-hover);
            color: #ffffff;
        }

        .login-link-footer {
            text-align: center;
            font-size: 0.9rem;
            color: #555;
            margin-top: 15px;
        }

        .login-link-footer a {
            color: var(--brand-green);
            font-weight: 700;
            text-decoration: none;
        }

        .login-link-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <!-- NAVBAR HEADER -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">

            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">

                <div class="brand-logo-icon">
                    <!-- Icon here -->
                </div>

                <div>
                    <div class="brand-text-main">AgriRent</div>
                    <div class="brand-text-sub">
                        Agriculture Equipment Rental System
                    </div>
                </div>

            </a>

            <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav">

                <span class="navbar-toggler-icon"></span>

            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">

                <ul class="navbar-nav align-items-center me-3">

                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">Browse Equipment</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">Categories</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">How It Works</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">About Us</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact Us</a>
                    </li>

                </ul>

                <a href="login.php" class="btn-outline-login">
                    <i class="fa-regular fa-user"></i> Login
                </a>

            </div>
        </div>
    </nav>

    <!-- MAIN REGISTRATION CARD CONTAINER -->
    <div class="container">

        <div class="register-outer-card">

            <div class="row g-0">

                <!-- LEFT PANEL: Hero Banner with Image -->
                <div class="col-lg-5 d-none d-lg-block">

                    <div class="left-banner-panel">

                        <div>

                            <h2 class="banner-heading">
                                Create Your Account<br>
                                and Get Started!
                            </h2>

                            <div class="banner-line"></div>

                            <p class="banner-subtext">
                                Join AgriRent today and access a wide range of agricultural equipment for your farming needs.
                            </p>

                        </div>

                        <!-- Tractor Image Container -->
                        <div class="banner-image-wrapper">

                            <img src="images/tractor.jpg"
                                 alt="AgriRent Tractor">

                        </div>

                    </div>

                </div>

                <!-- RIGHT PANEL: Registration Form -->
                <div class="col-lg-7">

                    <div class="right-form-panel">

                        <div class="form-header-title">
                            <i class="fa-solid fa-user-plus"></i>
                            Register
                        </div>

                        <p class="form-header-sub">
                            Fill in the details to create your account
                        </p>

                        <?php if($error != ""): ?>

                            <div class="alert alert-danger py-2 text-center small">
                                <?php echo $error; ?>
                            </div>

                        <?php endif; ?>

                        <?php if($success != ""): ?>

                            <div class="alert alert-success py-2 text-center small">
                                <?php echo $success; ?>
                            </div>

                        <?php endif; ?>

                        <form method="POST" action="">

                            <div class="row g-3">

                                <!-- Full Name -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Full Name
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-regular fa-user input-icon-left"></i>

                                        <input type="text"
                                               name="full_name"
                                               class="form-control"
                                               placeholder="Enter your full name"
                                               required>

                                    </div>

                                </div>

                                <!-- Email Address -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Email Address
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-regular fa-envelope input-icon-left"></i>

                                        <input type="email"
                                               name="email"
                                               class="form-control"
                                               placeholder="Enter your email address"
                                               required>

                                    </div>

                                </div>

                                <!-- Phone Number -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Phone Number
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-solid fa-phone input-icon-left"></i>

                                        <input type="text"
                                               name="phone"
                                               class="form-control"
                                               placeholder="Enter your phone number"
                                               required>

                                    </div>

                                </div>

                                <!-- Address -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Address
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-solid fa-location-dot input-icon-left"></i>

                                        <input type="text"
                                               name="address"
                                               class="form-control"
                                               placeholder="Enter your full address"
                                               required>

                                    </div>

                                </div>

                                <!-- Select User Type -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Select User Type
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-solid fa-users input-icon-left"></i>

                                        <select name="user_type"
                                                class="form-select"
                                                required>

                                            <option value=""
                                                    selected
                                                    disabled>
                                                -- Select User Type --
                                            </option>

                                            <option value="renter">
                                                Farmer / Renter
                                            </option>

                                            <option value="lender">
                                                Equipment Owner / Lender
                                            </option>

                                        </select>

                                    </div>

                                </div>

                                <!-- Password -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Password
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-solid fa-lock input-icon-left"></i>

                                        <input type="password"
                                               name="password"
                                               id="passInput"
                                               class="form-control"
                                               placeholder="Enter your password"
                                               required>

                                        <i class="fa-regular fa-eye-slash input-icon-right"
                                           id="togglePass"></i>

                                    </div>

                                </div>

                                <!-- Confirm Password -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Confirm Password
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-solid fa-lock input-icon-left"></i>

                                        <input type="password"
                                               name="confirm_password"
                                               id="confirmPassInput"
                                               class="form-control"
                                               placeholder="Confirm your password"
                                               required>

                                        <i class="fa-regular fa-eye-slash input-icon-right"
                                           id="toggleConfirmPass"></i>

                                    </div>

                                </div>

                            </div>

                            <!-- SECURITY QUESTION DIVIDER -->
                            <div class="security-divider">

                                <span>
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Security Question
                                </span>

                            </div>

                            <div class="row g-3 mb-3">

                                <!-- Security Question Dropdown -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Security Question
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-regular fa-circle-question input-icon-left"></i>

                                        <select name="security_question"
                                                class="form-select"
                                                required>

                                            <option value=""
                                                    selected
                                                    disabled>
                                                -- Select a Security Question --
                                            </option>

                                            <option value="first_pet">
                                                What was the name of your first pet?
                                            </option>

                                            <option value="birth_city">
                                                In what city were you born?
                                            </option>

                                            <option value="mother_maiden">
                                                What is your mother's maiden name?
                                            </option>

                                            <option value="first_school">
                                                What was the name of your first school?
                                            </option>

                                        </select>

                                    </div>

                                </div>

                                <!-- Security Answer -->
                                <div class="col-md-6">

                                    <label class="form-label">
                                        Security Answer
                                    </label>

                                    <div class="input-group-custom">

                                        <i class="fa-solid fa-lock input-icon-left"></i>

                                        <input type="text"
                                               name="security_answer"
                                               class="form-control"
                                               placeholder="Enter your answer"
                                               required>

                                    </div>

                                </div>

                            </div>

                            <!-- Terms & Conditions Checkbox -->
                            <div class="form-check mb-4 mt-2">

                                <input class="form-check-input"
                                       type="checkbox"
                                       name="terms"
                                       id="termsCheck"
                                       required>

                                <label class="form-check-label small text-secondary"
                                       for="termsCheck">

                                    I agree to the
                                    <a href="#"
                                       class="text-success fw-semibold text-decoration-none">
                                        Terms & Conditions
                                    </a>
                                    and
                                    <a href="#"
                                       class="text-success fw-semibold text-decoration-none">
                                        Privacy Policy
                                    </a>

                                </label>

                            </div>

                            <!-- Submit Register Button -->
                            <button type="submit"
                                    class="btn-register-submit">

                                <i class="fa-solid fa-user-plus"></i>
                                Register

                            </button>

                            <!-- Footer Login Link -->
                            <div class="login-link-footer">

                                Already have an account?
                                <a href="login.php">
                                    Login here
                                </a>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap 5 JS & Toggle Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Password Visibility Toggle
        const togglePass = document.querySelector('#togglePass');
        const passInput = document.querySelector('#passInput');

        togglePass.addEventListener('click', function () {

            const type = passInput.getAttribute('type') === 'password'
                ? 'text'
                : 'password';

            passInput.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');

        });


        // Confirm Password Visibility Toggle
        const toggleConfirmPass = document.querySelector('#toggleConfirmPass');
        const confirmPassInput = document.querySelector('#confirmPassInput');

        toggleConfirmPass.addEventListener('click', function () {

            const type = confirmPassInput.getAttribute('type') === 'password'
                ? 'text'
                : 'password';

            confirmPassInput.setAttribute('type', type);

            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');

        });

    </script>

</body>
</html>