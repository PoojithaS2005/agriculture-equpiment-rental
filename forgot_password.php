<?php
session_start();

// Include translation and database connection
require_once 'includes/lang.php';
if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

$step = 1;
$error = '';

// Determine current step based on form submission
if (isset($_POST['step'])) {
    $step = (int)$_POST['step'];
}

// STEP 1: Email or Phone Lookup & Method Selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_find_account'])) {
    $identity = mysqli_real_escape_string($conn, trim($_POST['identity']));
    $method = $_POST['reset_method'] ?? 'otp';

    // Check against either email or phone column in the users table
    $stmt = $conn->prepare("SELECT user_id, email, security_question FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $identity, $identity);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $_SESSION['reset_user_id'] = $user['user_id'];
        $_SESSION['reset_email'] = $user['email']; // Keep email saved for OTP sending if needed

        if ($method === 'otp') {
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $update_stmt = $conn->prepare("UPDATE users SET reset_otp = ?, otp_expires_at = ? WHERE user_id = ?");
            $update_stmt->bind_param("sss", $otp, $expires, $user['user_id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Send Real Email OTP (Only if user has a valid email address)
            if (!empty($user['email'])) {
                $to = $user['email'];
                $subject = "Your Password Reset OTP";
                $message = "Hello,\n\nYour OTP for password recovery is: " . $otp . "\n\nThis code is valid for 10 minutes.";
                $headers = "From: no-reply@" . $_SERVER['SERVER_NAME'];
                @mail($to, $subject, $message, $headers);
            }

            $step = 2;
        } else {
            if (!empty($user['security_question'])) {
                $_SESSION['reset_question'] = $user['security_question'];
                $step = 3;
            } else {
                $error = __('err_no_question');
                $step = 1;
            }
        }
    } else {
        $error = __('err_no_account');
        $step = 1;
    }
    $stmt->close();
}

// STEP 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_verify_otp'])) {
    $entered_otp = trim($_POST['otp_code']);
    $user_id = $_SESSION['reset_user_id'] ?? null;

    $stmt = $conn->prepare("SELECT reset_otp, otp_expires_at FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && $user['reset_otp'] === $entered_otp && strtotime($user['otp_expires_at']) > time()) {
        $_SESSION['verified_reset'] = true;
        $step = 4;
    } else {
        $error = __('err_invalid_otp');
        $step = 2;
    }
}

// STEP 3: Verify Security Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_verify_answer'])) {
    $answer_input = strtolower(trim($_POST['security_answer']));
    $user_id = $_SESSION['reset_user_id'] ?? null;

    $stmt = $conn->prepare("SELECT security_answer FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && !empty($user['security_answer'])) {
        $db_answer = strtolower(trim($user['security_answer']));
        
        // Flexible validation: handles plain text or hashed answers
        if (password_verify($answer_input, $user['security_answer']) || hash_equals($db_answer, $answer_input)) {
            $_SESSION['verified_reset'] = true;
            $step = 4;
        } else {
            $error = __('err_wrong_answer');
            $step = 3;
        }
    } else {
        $error = __('err_wrong_answer');
        $step = 3;
    }
}

// STEP 4: Reset Password & Redirect to Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reset_password'])) {
    if (!($_SESSION['verified_reset'] ?? false)) {
        header("Location: forgot_password.php");
        exit();
    }

    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['reset_user_id'];

    if ($new_password !== $confirm_password) {
        $error = __('err_pwd_mismatch');
        $step = 4;
    } elseif (strlen($new_password) < 6) {
        $error = __('err_pwd_length');
        $step = 4;
    } else {
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_otp = NULL, otp_expires_at = NULL WHERE user_id = ?");
        $stmt->bind_param("ss", $password_hash, $user_id);
        
        if ($stmt->execute()) {
            // Clear all recovery session variables
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_question'], $_SESSION['verified_reset']);

            // Redirect directly to login page with success status
            header("Location: login.php?reset=success");
            exit();
        } else {
            $error = __('err_update_failed');
            $step = 4;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('forgot_password_title'); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
        }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            /* Full Background Image with a dark gradient overlay */
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('images/tractor3.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            color: #333; 
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(5px);
        }
        .brand-logo-icon { width: 46px; height: 46px; background-color: var(--brand-green); color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .brand-text-main { font-weight: 800; color: var(--brand-green); font-size: 1.25rem; line-height: 1.1; letter-spacing: 0.5px; }
        .brand-text-sub { font-size: 0.68rem; font-weight: 700; color: #555; letter-spacing: 1px; }
        .lang-dropdown { border: 1px solid #ddd; border-radius: 8px; padding: 6px 16px; font-size: 0.9rem; background: #fff; cursor: pointer; }
        .login-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); padding: 35px 30px; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(10px); max-width: 450px; margin: 40px auto; }
        .avatar-circle { width: 65px; height: 65px; border-radius: 50%; border: 2px solid var(--brand-green); color: var(--brand-green); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 10px auto; background: #f4f9f5; }
        .btn-brand-green { background-color: var(--brand-green); color: #fff; border-radius: 10px; padding: 12px; font-weight: 600; border: none; }
        .btn-brand-green:hover { background-color: var(--brand-green-hover); color: #fff; }
        .question-box { background: #f4f9f5; border: 1px solid #dce1e5; border-radius: 10px; padding: 12px; font-weight: 600; color: var(--brand-green); }
    </style>
</head>
<body>

    <!-- NAVBAR HEADER -->
    <nav class="navbar navbar-expand-lg navbar-light py-3 border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <div class="brand-logo-icon"><i class="fa-solid fa-tractor"></i></div>
                <div>
                    <div class="brand-text-main">AGRICULTURE</div>
                    <div class="brand-text-sub">EQUIPMENT RENTAL SYSTEM</div>
                </div>
            </a>
            
            <div class="d-flex align-items-center gap-2 ms-auto">
                <i class="fa-solid fa-globe text-secondary"></i>
                <select class="lang-dropdown fw-bold text-success" onchange="location = this.value;">
                    <option value="?lang=en" <?= ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="?lang=kn" <?= ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                    <option value="?lang=hi" <?= ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                </select>
            </div>
        </div>
    </nav>

    <!-- CARD CONTAINER -->
    <div class="container">
        <div class="login-card">
            
            <div class="avatar-circle">
                <i class="fa-solid fa-key"></i>
            </div>

            <h3 class="text-center fw-bold mb-4"><?= __('forgot_password_title'); ?></h3>

            <?php if ($error != ""): ?>
                <div class="alert alert-danger py-2 text-center small"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- STEP 1: EMAIL OR PHONE & METHOD -->
            <?php if ($step === 1): ?>
                <form method="POST" action="forgot_password.php">
                    <input type="hidden" name="step" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Enter Registered Email or Mobile Number</label>
                        <input type="text" name="identity" class="form-control" required placeholder="Enter email or mobile number">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary"><?= __('choose_method'); ?></label>
                        <select name="reset_method" class="form-select">
                            <option value="otp"><?= __('method_otp'); ?></option>
                            <option value="question"><?= __('method_question'); ?></option>
                        </select>
                    </div>
                    <button type="submit" name="action_find_account" class="btn btn-brand-green w-100"><?= __('continue'); ?></button>
                </form>
            <?php endif; ?>

            <!-- STEP 2: OTP VERIFICATION -->
            <?php if ($step === 2): ?>
                <form method="POST" action="forgot_password.php">
                    <input type="hidden" name="step" value="2">
                    <div class="mb-4 text-center">
                        <label class="form-label small fw-semibold text-secondary"><?= __('enter_otp'); ?></label>
                        <input type="text" name="otp_code" class="form-control text-center fw-bold fs-4" maxlength="6" required style="letter-spacing: 6px;">
                    </div>
                    <button type="submit" name="action_verify_otp" class="btn btn-brand-green w-100"><?= __('verify_code'); ?></button>
                </form>
            <?php endif; ?>

            <!-- STEP 3: SECURITY QUESTION -->
            <?php if ($step === 3): ?>
                <form method="POST" action="forgot_password.php">
                    <input type="hidden" name="step" value="3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary"><?= __('security_question'); ?></label>
                        <div class="question-box mb-3"><?= htmlspecialchars($_SESSION['reset_question'] ?? ''); ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary"><?= __('your_answer'); ?></label>
                        <input type="text" name="security_answer" class="form-control" required autocomplete="off">
                    </div>
                    <button type="submit" name="action_verify_answer" class="btn btn-brand-green w-100"><?= __('verify_answer'); ?></button>
                </form>
            <?php endif; ?>

            <!-- STEP 4: RESET PASSWORD -->
            <?php if ($step === 4): ?>
                <form method="POST" action="forgot_password.php">
                    <input type="hidden" name="step" value="4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary"><?= __('new_password'); ?></label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary"><?= __('confirm_password'); ?></label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>
                    <button type="submit" name="action_reset_password" class="btn btn-brand-green w-100"><?= __('reset_btn'); ?></button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" class="text-success text-decoration-none fw-semibold small">
                    <i class="fa-solid fa-arrow-left me-1"></i> <?= __('back_login'); ?>
                </a>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>