<?php
session_start();

// 1. Redirect if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'renter') {
        header("Location: renter_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'lender') {
        header("Location: lender_dashboard.php");
        exit();
    }
}

// Include language translation system
require_once 'includes/lang.php';

// Include database connection
if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login_input = mysqli_real_escape_string($conn, trim($_POST['login_input']));
    $password = trim($_POST['password']);

    // Find user by email OR phone
    $sql = "SELECT * FROM users 
            WHERE email='$login_input' OR phone='$login_input'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        // Check password
        if (password_verify($password, $user['password'])) {

            // 2. Prevent Session Fixation attacks
            session_regenerate_id(true);

            // Create login session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Remember Me
            if (isset($_POST['remember_me']) && $_POST['remember_me'] === '1') {

                // Generate a secure random token
                $remember_token = bin2hex(random_bytes(32));

                // Store only the SHA-256 hash in the database
                $remember_token_hash = hash('sha256', $remember_token);

                $user_id = (int)$user['user_id'];

                $update_token = mysqli_prepare(
                    $conn,
                    "UPDATE users SET remember_token = ? WHERE user_id = ?"
                );

                mysqli_stmt_bind_param(
                    $update_token,
                    "si",
                    $remember_token_hash,
                    $user_id
                );

                mysqli_stmt_execute($update_token);
                mysqli_stmt_close($update_token);

                // Store the original token in a secure browser cookie
                setcookie(
                    'remember_token',
                    $remember_token,
                    [
                        'expires' => time() + (60 * 60 * 24 * 30),
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );

            } else {

                // If Remember Me is not selected, remove any old token
                $user_id = (int)$user['user_id'];

                $clear_token = mysqli_prepare(
                    $conn,
                    "UPDATE users SET remember_token = NULL WHERE user_id = ?"
                );

                mysqli_stmt_bind_param(
                    $clear_token,
                    "i",
                    $user_id
                );

                mysqli_stmt_execute($clear_token);
                mysqli_stmt_close($clear_token);

                // Remove old browser cookie
                setcookie(
                    'remember_token',
                    '',
                    [
                        'expires' => time() - 3600,
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            }

            // Redirect according to role
            if ($user['role'] === 'renter') {
                header("Location: renter_dashboard.php");
                exit();
            } 
            elseif ($user['role'] === 'lender') {
                header("Location: lender_dashboard.php");
                exit();
            }

        } else {
            $error = "Incorrect password!";
        }

    } else {
        $error = "Email or Phone number not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login'); ?> - <?= __('title'); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --brand-green: #08785f;
            --brand-green-hover: #075b49;
            --brand-light-bg: #f4f9f5;
            --glass-border: rgba(255, 255, 255, 0.62);
            --glass-fill: rgba(255, 255, 255, 0.28);
            --glass-shadow: 0 18px 45px rgba(16, 66, 66, 0.16);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #173b3b;
            min-height: 100vh;
            position: relative;
            isolation: isolate;
            overflow-x: hidden;
            background: transparent;
        }

        /* Softly blurred countryside background behind the existing glass panels. */
        body::before {
            content: "";
            position: fixed;
            inset: -5px;
            pointer-events: none;
            z-index: -1;
            background:
                linear-gradient(
                    180deg,
                    rgba(220, 241, 255, .08) 0%,
                    rgba(235, 247, 220, .08) 100%
                ),
                url('images/countryside-background.png')
                center center / cover no-repeat;
            /* The image is already softly blurred, so keep the blur very light. */
            filter: blur(1px);
            transform: scale(1.01);
        }

        /* Header Brand Logo */
        .navbar {
            background: rgba(232, 246, 252, .48) !important;
            border-bottom: 1px solid rgba(255,255,255,.38) !important;
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .brand-logo-icon {
            width: 46px; height: 46px;
            background: linear-gradient(145deg, #0b8a6d, #075b49);
            color: #fff; border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 7px 18px rgba(7, 91, 73, .25);
        }
        .brand-text-main { font-weight: 800; color: #075b49; font-size: 1.25rem; line-height: 1.1; letter-spacing: .5px; }
        .brand-text-sub { font-size: .68rem; font-weight: 700; color: #355c58; letter-spacing: 1px; }
        .nav-link { font-weight: 600; color: #244b4b !important; margin: 0 10px; }
        .nav-link:hover { color: var(--brand-green) !important; }
        .lang-dropdown {
            border: 1px solid rgba(255,255,255,.8); border-radius: 12px;
            padding: 8px 16px; font-size: .9rem;
            background: rgba(255,255,255,.58); color: #075b49;
            cursor: pointer; backdrop-filter: blur(12px);
        }

        /* Left welcome glass panel */
        .welcome-glass-card {
            height: 100%;
            padding: 34px 30px 28px;
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            background: rgba(255,255,255,.30);
            box-shadow: var(--glass-shadow), inset 0 1px 0 rgba(255,255,255,.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .welcome-title { color: #086b5b; font-weight: 800; font-size: clamp(2rem, 3vw, 2.7rem); }
        .welcome-sub { color: #294b4b; font-size: 1.02rem; line-height: 1.55; }
        .tractor-hero-card {
            width: 100%; margin: 10px 0 14px;
            padding: 0; background: transparent; border-radius: 18px;
            overflow: hidden; box-shadow: none;
        }
        .tractor-hero-card img {
            display: block; width: 100%; height: 255px;
            max-height: 34vh; object-fit: contain; object-position: center;
        }
        .feature-icon-box {
            width: 58px; height: 58px; border-radius: 50%;
            border: 1px solid rgba(255,255,255,.9); color: #078267;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; margin-bottom: 8px;
            background: rgba(255,255,255,.68);
            box-shadow: 0 7px 18px rgba(18,76,67,.10);
        }
        .feature-title { font-size: .95rem; font-weight: 800; color: #173b36; margin-bottom: 3px; }
        .feature-sub { font-size: .8rem; color: #365b53; line-height: 1.35; }

        /* Login glass panel */
        .login-card {
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            box-shadow: var(--glass-shadow), inset 0 1px 0 rgba(255,255,255,.65);
            padding: 35px 30px;
            background: rgba(255,255,255,.38);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
        }
        .avatar-circle {
            width: 68px; height: 68px; border-radius: 50%;
            border: 2px solid #078267; color: #078267;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; margin: 0 auto 10px;
            background: rgba(255,255,255,.42);
        }
        .login-heading { font-weight: 800; color: #102f2d; margin-bottom: 25px; }
        .form-control {
            border-radius: 12px; padding: 12px 15px 12px 42px;
            font-size: .95rem; border: 1px solid rgba(255,255,255,.9);
            background: rgba(255,255,255,.52); color: #183b38;
            box-shadow: inset 0 1px 3px rgba(30,70,65,.04);
        }
        .form-control::placeholder { color: #58736e; }
        .form-control:focus {
            border-color: #078267;
            background: rgba(255,255,255,.76);
            box-shadow: 0 0 0 .2rem rgba(7,130,103,.14);
        }
        .input-icon-wrapper { position: relative; }
        .input-icon-left { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #55716b; }
        .input-icon-right { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #55716b; cursor: pointer; }
        .btn-brand-green {
            background: linear-gradient(100deg, #087e65, #075b50);
            color: #fff; border-radius: 12px; padding: 13px;
            font-weight: 700; border: 1px solid rgba(255,255,255,.25);
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 8px 18px rgba(7,91,73,.18);
        }
        .btn-brand-green:hover { background: linear-gradient(100deg, #076b56, #064b42); color: #fff; }
        .btn-brand-green .arrow-icon {
            width: 24px; height: 24px; border-radius: 50%; border: 1px solid #fff;
            display: flex; align-items: center; justify-content: center; font-size: .75rem;
        }
        .btn-outline-register {
            border: 1px solid rgba(255,255,255,.85); border-radius: 12px;
            padding: 12px; font-weight: 600; color: #244b45;
            text-decoration: none; display: block; text-align: center;
            background: rgba(255,255,255,.48);
        }
        .btn-outline-register:hover { background: rgba(255,255,255,.75); color: var(--brand-green); }
        .terms-box {
            background: rgba(255,255,255,.38); border: 1px solid rgba(255,255,255,.55);
            border-radius: 14px; padding: 12px 15px; font-size: .85rem; color: #294b45;
        }
        .terms-box a { color: #08745d; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
        .terms-box a:hover { color: var(--brand-green-hover); }

        /* Footer */
        footer > .container { padding-bottom: 18px; }
        footer .container.pt-3 { background: rgba(238, 248, 226, .62); border: 1px solid rgba(255,255,255,.60); border-radius: 20px 20px 0 0; backdrop-filter: blur(14px); }
        .footer-bottom {
            background: rgba(5, 91, 70, .88); color: #fff;
            padding: 15px 0; text-align: center; font-size: .9rem; margin-top: 20px;
            backdrop-filter: blur(12px);
        }

        @media (max-width: 991.98px) {
            .welcome-glass-card { padding: 28px 24px; }
            .tractor-hero-card img { height: 230px; max-height: none; }
            .login-card { padding: 30px 24px; }
        }
        @media (max-width: 575.98px) {
            .welcome-glass-card, .login-card { border-radius: 22px; padding: 25px 20px; }
            .welcome-title { font-size: 2rem; }
            .tractor-hero-card img { height: 190px; }
            .feature-icon-box { width: 48px; height: 48px; font-size: 1.1rem; }
            .feature-title { font-size: .82rem; }
            .feature-sub { font-size: .7rem; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR HEADER -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
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
                        <a class="nav-link" href="index.php">
                            <i class="fa-solid fa-house me-1"></i> <?= __('home'); ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="how-it-works.php">
                            <i class="fa-solid fa-info-circle me-1"></i> <?= __('how_it_works'); ?>
                        </a>
                    </li>
                </ul>

                <!-- DYNAMIC LANGUAGE DROPDOWN -->
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-globe text-secondary"></i>

                    <select class="lang-dropdown fw-bold text-success" onchange="location = this.value;">
                        <option value="?lang=en" <?= ($current_lang === 'en') ? 'selected' : ''; ?>>
                            English
                        </option>

                        <option value="?lang=kn" <?= ($current_lang === 'kn') ? 'selected' : ''; ?>>
                            ಕನ್ನಡ (Kannada)
                        </option>

                        <option value="?lang=hi" <?= ($current_lang === 'hi') ? 'selected' : ''; ?>>
                            हिंदी (Hindi)
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN SIDE-BY-SIDE CONTENT -->
    <div class="container my-4">
        <div class="row align-items-center g-4">

            <!-- LEFT COLUMN: Image & Text -->
            <div class="col-lg-6">
                <div class="welcome-glass-card">
                <h1 class="welcome-title mb-2"><?= __('welcome_back'); ?></h1>
                <p class="welcome-sub mb-3"><?= __('login_sub'); ?></p>

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
                            <span class="feature-title"><?= __('trusted'); ?></span>
                            <span class="feature-sub"><?= __('trusted_desc'); ?></span>
                        </div>
                    </div>

                    <div class="col-4 border-start border-end">
                        <div class="d-flex flex-column align-items-center">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <span class="feature-title"><?= __('quality'); ?></span>
                            <span class="feature-sub"><?= __('quality_desc'); ?></span>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="feature-icon-box">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <span class="feature-title"><?= __('near'); ?></span>
                            <span class="feature-sub"><?= __('near_desc'); ?></span>
                        </div>
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

                    <h3 class="text-center login-heading"><?= __('login'); ?></h3>

                    <?php if($error != ""): ?>
                        <div class="alert alert-danger py-2 text-center small">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" autocomplete="on">

                        <!-- Hidden inputs trick Chrome away from real inputs -->
                        <input type="text" style="display:none" name="fake_username_autofill"/>
                        <input type="password" style="display:none" name="fake_password_autofill"/>

                        <!-- Email / Phone -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <?= __('email_or_phone'); ?>
                            </label>

                            <div class="input-icon-wrapper">
                                <i class="fa-regular fa-user input-icon-left"></i>

                                <input type="text"
                                       name="login_input"
                                       class="form-control"
                                       placeholder="<?= __('enter_email_phone'); ?>"
                                       autocomplete="username"
                                       required>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">
                                <?= __('password'); ?>
                            </label>

                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-lock input-icon-left"></i>

                                <input type="password"
                                       name="password"
                                       id="passwordInput"
                                       class="form-control"
                                       placeholder="<?= __('enter_password'); ?>"
                                       autocomplete="current-password"
                                       required>

                                <i class="fa-regular fa-eye input-icon-right"
                                   id="togglePassword"
                                   title="Show password"
                                   aria-label="Show password"></i>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4 small">
                            <div class="form-check">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="rememberMe"
                                       name="remember_me"
                                       value="1">

                                <label class="form-check-label text-secondary"
                                       for="rememberMe">
                                    <?= __('remember_me'); ?>
                                </label>
                            </div>

                            <a href="forgot_password.php?lang=<?= urlencode($current_lang); ?>"
                               class="text-success text-decoration-none fw-semibold">
                                <?= __('forgot_password'); ?>
                            </a>
                        </div>

                        <!-- Login Button -->
                        <button type="submit" class="btn btn-brand-green w-100 mb-3">
                            <?= __('login'); ?>

                            <span class="arrow-icon">
                                <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </button>
                    </form>

                    <!-- OR Divider -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1 my-0 text-muted">

                        <span class="px-3 small text-muted fw-semibold">
                            OR
                        </span>

                        <hr class="flex-grow-1 my-0 text-muted">
                    </div>

                    <!-- Register Link -->
                    <a href="register.php?lang=<?= urlencode($current_lang); ?>"
                       class="btn-outline-register mb-4">

                        <i class="fa-solid fa-user-plus me-2 text-success"></i>

                        <?= __('dont_have_account'); ?>

                        <strong class="text-success">
                            <?= __('register'); ?>
                        </strong>
                    </a>

                    <!-- TERMS & PRIVACY LINKS -->
                    <div class="terms-box d-flex align-items-center gap-3">
                        <i class="fa-solid fa-shield-halved text-success fa-lg"></i>

                        <div>
                            <?= __('terms_notice'); ?>

                            <div class="mt-1">
                                <a href="terms_privacy.php?lang=<?= urlencode($current_lang); ?>&from=login#terms"
                                   target="_blank">
                                    <?= ($current_lang === 'kn') ? 'ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳು' : (($current_lang === 'hi') ? 'नियम और शर्तें' : 'Terms &amp; Conditions'); ?>
                                </a>

                                <span class="mx-1"><?= ($current_lang === 'kn') ? 'ಮತ್ತು' : (($current_lang === 'hi') ? 'और' : 'and'); ?></span>

                                <a href="terms_privacy.php?lang=<?= urlencode($current_lang); ?>&from=login#privacy"
                                   target="_blank">
                                    <?= ($current_lang === 'kn') ? 'ಗೌಪ್ಯತಾ ನೀತಿ' : (($current_lang === 'hi') ? 'गोपनीयता नीति' : 'Privacy Policy'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container pt-3">
            <hr class="my-4 text-muted opacity-25">

            <div class="row g-4 justify-content-center">
                <div class="col-md-8 text-center">

                    <h6 class="fw-bold mb-3 text-success">
                        <?= __('popular_categories'); ?>
                    </h6>

                    <div class="d-flex flex-wrap justify-content-center gap-4 small text-secondary">

                        <span>
                            <i class="fa-solid fa-tractor me-1 text-success"></i>
                            <?= __('tractor'); ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-wheat-awn me-1 text-success"></i>
                            <?= __('harvesting'); ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-droplet me-1 text-success"></i>
                            <?= __('irrigation'); ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-gears me-1 text-success"></i>
                            <?= __('tillage'); ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-seedling me-1 text-success"></i>
                            <?= __('seeding'); ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-spray-can me-1 text-success"></i>
                            <?= __('spraying'); ?>
                        </span>

                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                © 2026 <?= __('title'); ?>.
                <?= __('all_rights_reserved'); ?>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Password Show / Hide -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#passwordInput');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {

                const type =
                    password.getAttribute('type') === 'password'
                        ? 'text'
                        : 'password';

                password.setAttribute('type', type);

                if (type === 'text') {
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                    this.setAttribute('title', 'Hide password');
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                    this.setAttribute('title', 'Show password');
                    this.setAttribute('aria-label', 'Show password');
                }
            });
        }
    </script>

</body>
</html>
