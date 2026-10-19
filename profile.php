<?php
session_start();

// Load persistent language system & DB config
require_once 'includes/lang.php';
require_once 'includes/config.php';
// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$location_labels = [
    'en' => ['city' => 'City', 'district' => 'District', 'state' => 'State'],
    'kn' => ['city' => 'ನಗರ', 'district' => 'ಜಿಲ್ಲೆ', 'state' => 'ರಾಜ್ಯ'],
    'hi' => ['city' => 'शहर', 'district' => 'जिला', 'state' => 'राज्य']
];
$city_label = $location_labels[$current_lang]['city'] ?? 'City';
$district_label = $location_labels[$current_lang]['district'] ?? 'District';
$state_label = $location_labels[$current_lang]['state'] ?? 'State';

// Handle Profile Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $district  = trim($_POST['district'] ?? '');
    $state     = trim($_POST['state'] ?? '');

    // Fetch current profile pic to keep it if no new upload
    $stmt_curr = mysqli_prepare($conn, "SELECT profile_pic, role FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_curr, "i", $user_id);
    mysqli_stmt_execute($stmt_curr);
    $res_curr = mysqli_stmt_get_result($stmt_curr);
    $curr_data = mysqli_fetch_assoc($res_curr);
    $profile_pic = $curr_data['profile_pic'] ?? '';
    mysqli_stmt_close($stmt_curr);

    // Handle Profile Picture Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['profile_pic']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['profile_pic']['name']);
        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $profile_pic = $file_name;
        }
    }

    // Secure Update
    $update_stmt = mysqli_prepare($conn, "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, city = ?, district = ?, state = ?, profile_pic = ? WHERE user_id = ?");
    mysqli_stmt_bind_param($update_stmt, "ssssssssi", $full_name, $email, $phone, $address, $city, $district, $state, $profile_pic, $user_id);

    if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['full_name'] = $full_name;
        $success = __('profile_updated_success');
    } else {
        $error = "Error updating profile: " . mysqli_error($conn);
    }
    mysqli_stmt_close($update_stmt);
}

// Fetch Latest User Information
$stmt = mysqli_prepare($conn, "SELECT user_id, full_name, email, phone, role, security_question, address, city, district, state, profile_pic FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Determine Dashboard Link based on Role
$role_lower = strtolower($user['role'] ?? 'renter');
$dashboard_link = ($role_lower === 'lender') ? 'lender_dashboard.php' : 'renter_dashboard.php';
$user_name = $user['full_name'] ?? 'User';
$profile_img = !empty($user['profile_pic']) ? $user['profile_pic'] : 'default_avatar.png';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('my_profile'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* =========================================================
           INTERFACE COLOURS (matched to Renter Dashboard)
           Pale cream-green background + frosted white cards
           + dark green banner and buttons.
           ========================================================= */

        :root {
            color-scheme: light;
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
            --brand-green-pill: #1f5c43;
            --page-bg-1: #f3f2ea;
            --page-bg-2: #eef2e8;
            --page-bg-3: #f5f4ec;
            --glass-bg: rgba(255, 255, 255, .55);
            --glass-border: rgba(255, 255, 255, .85);
            --text-dark: #2a2f29;
            --text-muted: #6a7566;
            --line: rgba(45, 106, 79, .14);
            --shadow-soft: 0 8px 26px rgba(70, 90, 60, .09);
        }

        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body {
            margin: 0;
            padding: clamp(12px, 3vw, 32px);
            min-height: 100vh;
            color: var(--text-dark);
            background-color: var(--page-bg-1);
            background:
                radial-gradient(circle at 12% 8%, rgba(255, 255, 255, .70), transparent 32%),
                radial-gradient(circle at 88% 20%, rgba(220, 232, 205, .55), transparent 34%),
                radial-gradient(circle at 70% 92%, rgba(255, 255, 255, .50), transparent 32%),
                linear-gradient(135deg, var(--page-bg-1) 0%, var(--page-bg-2) 50%, var(--page-bg-3) 100%);
            background-attachment: fixed;
        }

        /* ---------- Main card ---------- */

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            overflow: hidden;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /* ---------- Top banner ---------- */

        .top-banner {
            background: var(--brand-green-pill);
            color: #fff;
            padding: 18px clamp(16px, 3vw, 28px);
            font-weight: bold;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 8px 24px rgba(31, 92, 67, .14);
        }

        .top-banner > span { letter-spacing: .1px; }

        .top-banner .btn-light {
            border: 1px solid rgba(255, 255, 255, .55);
            border-radius: 12px;
            background: rgba(255, 255, 255, .90);
            color: var(--brand-green) !important;
            box-shadow: 0 4px 12px rgba(12, 60, 37, .10);
            transition: transform .2s ease, background .2s ease;
        }

        .top-banner .btn-light:hover { background: #fff; transform: translateY(-1px); }

        .lang-select {
            padding: 8px 11px;
            border: 1px solid rgba(255, 255, 255, .55);
            border-radius: 12px;
            font-size: 13px;
            outline: none;
            background: rgba(255, 255, 255, .90);
            color: var(--brand-green) !important;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(12, 60, 37, .08);
        }

        /* ---------- Profile header ---------- */

        .profile-header {
            background: rgba(226, 240, 220, .55);
            border-bottom: 1px solid var(--line);
            padding: clamp(22px, 4vw, 34px);
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .avatar-box {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, .90);
            outline: 2px solid var(--brand-green);
            background: #e3efdc;
            box-shadow: 0 8px 24px rgba(31, 92, 67, .18);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .avatar-box img { width: 100%; height: 100%; object-fit: cover; }

        .profile-header h2 { color: var(--text-dark) !important; overflow-wrap: anywhere; }
        .profile-header .text-muted { color: var(--text-muted) !important; overflow-wrap: anywhere; }

        .badge-role {
            background: #dcefd6;
            color: var(--brand-green-pill);
            border: 1px solid #b4d6aa;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* ---------- Info cards ---------- */

        .profile-body { padding: clamp(18px, 4vw, 32px); }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 260px), 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .info-card {
            background: rgba(255, 255, 255, .62);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 18px 20px;
            box-shadow: 0 6px 18px rgba(70, 90, 60, .07), inset 0 1px 0 rgba(255, 255, 255, .95);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            border-color: rgba(92, 163, 121, .40);
            box-shadow: 0 12px 26px rgba(60, 90, 55, .13);
        }

        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: bold;
            letter-spacing: .8px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            overflow-wrap: anywhere;
        }

        #viewMode .border-top,
        #editMode .border-top { border-color: var(--line) !important; }

        #viewMode .text-danger { color: #b45353 !important; }

        /* ---------- Buttons ---------- */

        #viewMode button.btn,
        #editMode button[type="submit"] {
            background: var(--brand-green-pill) !important;
            border: 1px solid rgba(255, 255, 255, .70);
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(31, 92, 67, .20);
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }

        #viewMode button.btn:hover,
        #editMode button[type="submit"]:hover {
            background: var(--brand-green-hover) !important;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(31, 92, 67, .26);
        }

        #editMode .btn-secondary {
            border-radius: 12px;
            background: rgba(255, 255, 255, .80);
            border: 1px solid var(--line);
            color: var(--brand-green);
            font-weight: 600;
        }

        #editMode .btn-secondary:hover {
            background: #e9f3e6;
            color: var(--brand-green);
            border-color: rgba(45, 106, 79, .40);
        }

        /* ---------- Edit form ---------- */

        #editMode .form-control {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255, 255, 255, .80);
            color: var(--text-dark);
            padding: 10px 12px;
        }

        #editMode .form-control:focus,
        .lang-select:focus {
            border-color: #6bbd91;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, .12);
        }

        #editMode .form-label { color: var(--text-dark); }
        #editMode .form-text { color: var(--text-muted); }
        #editMode h4 { color: var(--text-dark) !important; }
        #editMode h4 .text-info { color: var(--brand-green) !important; }

        /* ---------- Alerts ---------- */

        .alert { border-radius: 14px; border: 1px solid transparent; }
        .alert-success { background: rgba(226, 240, 220, .85); border-color: #b4d6aa; color: var(--brand-green-pill); }
        .alert-danger { background: rgba(254, 226, 226, .85); border-color: #fecaca; color: #a83232; }

        /* ---------- Responsive ---------- */

        @media (max-width: 600px) {
            body { padding: 10px; }
            .profile-container { border-radius: 18px; }
            .top-banner { align-items: flex-start; }
            .top-banner > .d-flex { width: 100%; flex-wrap: wrap; gap: 10px !important; }
            .profile-header { align-items: flex-start; gap: 18px; }
            .avatar-box { width: 82px; height: 82px; }
            .profile-header h2 { font-size: 1.35rem; }
            .profile-body { padding: 18px; }
            .info-card { padding: 15px; }
            #viewMode .d-flex.justify-content-between,
            #editMode .d-flex.justify-content-between { align-items: flex-start !important; gap: 16px; flex-wrap: wrap; }
        }

        @media (max-width: 400px) {
            .profile-header { flex-direction: column; }
            .top-banner { font-size: 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>

    <div class="profile-container">
        <!-- Top Banner with Language Selector matching dashboard -->
        <div class="top-banner">
            <span><i class="fa-regular fa-user me-2"></i> <?php echo __('my_profile'); ?></span>
            <div class="d-flex align-items-center gap-3">
                <select class="lang-select text-dark" onchange="location = this.value;">
                    <option value="profile.php?lang=en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>🌐 English</option>
                    <option value="profile.php?lang=hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>🌐 हिन्दी (Hindi)</option>
                    <option value="profile.php?lang=kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
                </select>
                <a href="<?php echo $dashboard_link; ?>" class="btn btn-sm btn-light fw-bold text-dark"><i class="fa-solid fa-arrow-left me-1"></i> <?php echo __('back_to_dashboard'); ?></a>
            </div>
        </div>

        <!-- Profile Header Section -->
        <div class="profile-header">
            <div class="avatar-box">
                <img src="uploads/<?php echo htmlspecialchars($profile_img); ?>"
                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=1f5c43&color=fff';"
                     alt="Profile Picture">
            </div>
            <div>
                <h2 class="mb-1 text-dark fw-bold"><?php echo htmlspecialchars($user_name); ?></h2>
                <p class="text-muted mb-2"><i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge-role"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>
        </div>

        <div class="profile-body">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- View Mode -->
            <div id="viewMode">
                <div class="info-grid">
                    <div class="info-card"><div class="info-label"><?php echo __('full_name'); ?></div><div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo __('email_address'); ?></div><div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo __('phone_number'); ?></div><div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not specified'); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo __('address'); ?></div><div class="info-value"><?php echo htmlspecialchars($user['address'] ?? 'Not specified'); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo htmlspecialchars($city_label); ?></div><div class="info-value"><?php echo htmlspecialchars($user['city'] ?? 'Not specified'); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo htmlspecialchars($district_label); ?></div><div class="info-value"><?php echo htmlspecialchars($user['district'] ?? 'Not specified'); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo htmlspecialchars($state_label); ?></div><div class="info-value"><?php echo htmlspecialchars($user['state'] ?? 'Not specified'); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo __('security_question'); ?></div><div class="info-value"><?php echo htmlspecialchars($user['security_question'] ?? 'Not specified'); ?></div></div>
                    <div class="info-card"><div class="info-label"><?php echo __('account_user_id'); ?></div><div class="info-value">#<?php echo intval($user['user_id']); ?></div></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <a href="forgot_password.php" class="text-danger text-decoration-none fw-bold"><i class="fa-solid fa-key me-1"></i> <?php echo __('change_password'); ?></a>
                    <button type="button" class="btn text-white px-4 fw-bold" style="background-color: #0f4c5c;" onclick="toggleEditMode(true)"><i class="fa-solid fa-pen-to-square me-1"></i> <?php echo __('edit_profile'); ?></button>
                </div>
            </div>

            <!-- Edit Form Mode -->
            <div id="editMode" style="display: none;">
                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <h4 class="mb-3 text-dark fw-bold" style="font-size: 18px;"><i class="fa-solid fa-pen-to-square me-2 text-info"></i> <?php echo __('update_profile_info'); ?></h4>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo __('full_name'); ?></label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo __('email_address'); ?></label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo __('phone_number'); ?></label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo __('update_profile_pic'); ?></label><input type="file" name="profile_pic" class="form-control"><div class="form-text"><?php echo __('leave_blank_pic'); ?></div></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold"><?php echo __('address'); ?></label><textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo htmlspecialchars($city_label); ?></label><input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo htmlspecialchars($district_label); ?></label><input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold"><?php echo htmlspecialchars($state_label); ?></label><input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" required></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" onclick="toggleEditMode(false)"><?php echo __('cancel'); ?></button>
                        <button type="submit" class="btn text-white px-4 fw-bold" style="background-color: #0f4c5c;"><i class="fa-solid fa-floppy-disk me-1"></i> <?php echo __('save_changes'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleEditMode(enable) {
            if (enable) {
                document.getElementById('viewMode').style.display = 'none';
                document.getElementById('editMode').style.display = 'block';
            } else {
                document.getElementById('editMode').style.display = 'none';
                document.getElementById('viewMode').style.display = 'block';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>