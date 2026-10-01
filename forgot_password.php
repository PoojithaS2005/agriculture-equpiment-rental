<?php
session_start();

// Include translation and database connection
require_once 'includes/lang.php';
if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

// Optional SMTP configuration.
// This file is not required for the Security Question recovery method.
// If OTP recovery is used, configure these variables in smtp_config.php.
$smtp_username = '';
$smtp_app_password = '';

$smtp_config_file = __DIR__ . '/includes/smtp_config.php';
if (file_exists($smtp_config_file)) {
    require_once $smtp_config_file;
}

/*
 * PHPMailer - manual installation
 * Keep your Gmail App Password private.
 */
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$step = 1;
$error = '';

/*
 * Gmail SMTP settings
 * Replace only these two values:
 * 1. YOUR_PROJECT_GMAIL@gmail.com -> the new Gmail account you created.
 * 2. YOUR_16_CHARACTER_APP_PASSWORD -> the Google App Password.
 *
 * Do NOT use your normal Gmail password here.
 */

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

            // Send the OTP through Gmail SMTP using PHPMailer.
            if (!empty($user['email'])) {
                if (empty($smtp_username) || empty($smtp_app_password)) {
                    $error = 'OTP email is not configured. Please use Security Question or configure includes/smtp_config.php.';
                    $step = 1;
                } else {
                    $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_username;
                    $mail->Password = $smtp_app_password;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom($smtp_username, 'Agriculture Equipment Rental System');
                    $mail->addAddress($user['email']);

                    $mail->isHTML(false);
                    $mail->Subject = 'Your Password Reset OTP';
                    $mail->Body =
                        "Hello,\n\n" .
                        "Your OTP for password recovery is: " . $otp . "\n\n" .
                        "This code is valid for 10 minutes.\n\n" .
                        "If you did not request a password reset, please ignore this email.";

                    $mail->send();
                    $step = 2;
                    } catch (Exception $e) {
                        $error = 'Unable to send the OTP email. Please check the Gmail/PHPMailer settings.';
                        $step = 1;
                    }
                }
            } else {
                $error = 'No email address is registered for this account.';
                $step = 1;
            }
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
    $stmt->bind_param("i", $user_id);
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

    $password_is_valid =
        strlen($new_password) >= 8 &&
        preg_match('/[A-Z]/', $new_password) &&
        preg_match('/[0-9]/', $new_password) &&
        preg_match('/[^A-Za-z0-9]/', $new_password);

    if ($new_password !== $confirm_password) {
        $error = __('err_pwd_mismatch');
        $step = 4;
    } elseif (!$password_is_valid) {
        $error = 'Password must contain at least 8 characters, 1 capital letter, 1 number, and 1 special character.';
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

        /* Password policy */
        .password-rules {
            margin-top: 8px;
            padding: 10px 12px;
            background: #f8faf9;
            border: 1px solid #dce1e5;
            border-radius: 10px;
            font-size: 0.84rem;
        }

        .password-rule {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            color: #dc3545;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .password-rule::before {
            content: "•";
            font-size: 1.25rem;
            line-height: 0.8;
            color: #dc3545;
        }

        .password-rule.valid {
            color: #198754;
        }

        .password-rule.valid::before {
            color: #198754;
        }


        /* Password show/hide button */
        .password-input-group {
            position: relative;
        }

        .password-input-group .password-field {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            z-index: 5;
        }

        .password-toggle:hover {
            color: #2d6a4f;
        }

        .password-toggle:focus {
            outline: none;
            box-shadow: none;
        }

        /* Bright password warning popup */
        .password-warning-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .password-warning-overlay.show {
            display: flex;
        }

        .password-warning-box {
            width: 100%;
            max-width: 430px;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
            animation: passwordPopupIn 0.2s ease-out;
        }

        .password-warning-header {
            background: #dc3545;
            color: #ffffff;
            padding: 18px 20px;
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .password-warning-body {
            padding: 20px;
            color: #333;
        }

        .password-warning-body ul {
            margin: 10px 0 0;
            padding-left: 22px;
        }

        .password-warning-body li {
            margin: 7px 0;
            color: #dc3545;
            font-weight: 600;
        }

        .password-warning-footer {
            padding: 0 20px 20px;
        }

        .password-warning-footer button {
            width: 100%;
            border: none;
            border-radius: 10px;
            background: #dc3545;
            color: #ffffff;
            padding: 11px;
            font-weight: 700;
        }

        .password-warning-footer button:hover {
            background: #b02a37;
        }

        @keyframes passwordPopupIn {
            from {
                opacity: 0;
                transform: scale(0.92);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR HEADER -->
    <nav class="navbar navbar-expand-lg navbar-light py-3 border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <div class="brand-logo-icon"><i class="fa-solid fa-tractor"></i></div>
                <div>
                   <div class="brand-text-main"><?= __('brand_main'); ?></div>
<div class="brand-text-sub"><?= __('brand_sub'); ?></div>
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
                        <input type="text" name="otp_code" class="form-control text-center fw-bold fs-4" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required style="letter-spacing: 6px;">
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

                        <div class="password-input-group">
                            <input type="password"
                                   id="newPassword"
                                   name="new_password"
                                   class="form-control password-field"
                                   minlength="8"
                                   required
                                   autocomplete="new-password">

                            <button type="button"
                                    class="password-toggle"
                                    id="toggleNewPassword"
                                    aria-label="Show password"
                                    title="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>

                        <div class="password-rules" id="passwordRules">
                            <div class="password-rule" id="lengthRule">At least 8 characters</div>
                            <div class="password-rule" id="capitalRule">At least 1 capital letter (A-Z)</div>
                            <div class="password-rule" id="numberRule">At least 1 number (0-9)</div>
                            <div class="password-rule" id="specialRule">At least 1 special character</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary"><?= __('confirm_password'); ?></label>

                        <div class="password-input-group">
                            <input type="password"
                                   id="confirmPassword"
                                   name="confirm_password"
                                   class="form-control password-field"
                                   minlength="8"
                                   required
                                   autocomplete="new-password">

                            <button type="button"
                                    class="password-toggle"
                                    id="toggleConfirmPassword"
                                    aria-label="Show password"
                                    title="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                            name="action_reset_password"
                            id="resetPasswordButton"
                            class="btn btn-brand-green w-100">
                        <?= __('reset_btn'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" class="text-success text-decoration-none fw-semibold small">
                    <i class="fa-solid fa-arrow-left me-1"></i> <?= __('back_login'); ?>
                </a>
            </div>

        </div>
    </div>


    <!-- Bright password warning popup -->
    <div class="password-warning-overlay" id="passwordWarningOverlay">
        <div class="password-warning-box" role="alertdialog" aria-modal="true" aria-labelledby="passwordWarningTitle">
            <div class="password-warning-header">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span id="passwordWarningTitle">Password Requirements</span>
            </div>

            <div class="password-warning-body">
                <strong>Please correct the following password requirements:</strong>
                <ul id="missingPasswordRules"></ul>
            </div>

            <div class="password-warning-footer">
                <button type="button" id="closePasswordWarning">OK</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('newPassword');
            const confirmInput = document.getElementById('confirmPassword');
            const form = passwordInput ? passwordInput.closest('form') : null;

            if (!passwordInput || !form) {
                return;
            }

            const lengthRule = document.getElementById('lengthRule');
            const capitalRule = document.getElementById('capitalRule');
            const numberRule = document.getElementById('numberRule');
            const specialRule = document.getElementById('specialRule');

            const overlay = document.getElementById('passwordWarningOverlay');
            const missingList = document.getElementById('missingPasswordRules');
            const closeButton = document.getElementById('closePasswordWarning');

            function setupPasswordToggle(inputId, buttonId) {
                const input = document.getElementById(inputId);
                const button = document.getElementById(buttonId);

                if (!input || !button) {
                    return;
                }

                button.addEventListener('click', function () {
                    const icon = button.querySelector('i');

                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                        button.setAttribute('aria-label', 'Hide password');
                        button.setAttribute('title', 'Hide password');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                        button.setAttribute('aria-label', 'Show password');
                        button.setAttribute('title', 'Show password');
                    }
                });
            }

            setupPasswordToggle('newPassword', 'toggleNewPassword');
            setupPasswordToggle('confirmPassword', 'toggleConfirmPassword');

            function checkPassword(password) {
                return {
                    length: password.length >= 8,
                    capital: /[A-Z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
            }

            function updateRule(element, valid) {
                element.classList.toggle('valid', valid);
            }

            function updatePasswordRules() {
                const result = checkPassword(passwordInput.value);

                updateRule(lengthRule, result.length);
                updateRule(capitalRule, result.capital);
                updateRule(numberRule, result.number);
                updateRule(specialRule, result.special);

                return result;
            }

            function showPasswordWarning(result) {
                missingList.innerHTML = '';

                const missingRules = [];

                if (!result.length) {
                    missingRules.push('At least 8 characters');
                }
                if (!result.capital) {
                    missingRules.push('At least 1 capital letter (A-Z)');
                }
                if (!result.number) {
                    missingRules.push('At least 1 number (0-9)');
                }
                if (!result.special) {
                    missingRules.push('At least 1 special character');
                }

                missingRules.forEach(function (rule) {
                    const li = document.createElement('li');
                    li.textContent = rule;
                    missingList.appendChild(li);
                });

                overlay.classList.add('show');
                closeButton.focus();
            }

            function closePasswordWarning() {
                overlay.classList.remove('show');
                passwordInput.focus();
            }

            passwordInput.addEventListener('input', updatePasswordRules);

            closeButton.addEventListener('click', closePasswordWarning);

            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    closePasswordWarning();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && overlay.classList.contains('show')) {
                    closePasswordWarning();
                }
            });

            form.addEventListener('submit', function (event) {
                const result = updatePasswordRules();

                if (!result.length || !result.capital || !result.number || !result.special) {
                    event.preventDefault();
                    showPasswordWarning(result);
                    return;
                }

                if (confirmInput && passwordInput.value !== confirmInput.value) {
                    event.preventDefault();
                    alert('Passwords do not match.');
                    confirmInput.focus();
                }
            });

            updatePasswordRules();
        });
    </script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>