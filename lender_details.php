<?php
session_start();
require_once 'includes/config.php';

// Handle Language Switch instantly via session
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_param = '?lang=' . urlencode($current_lang);

// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$renter_id = $_SESSION['user_id'];
$lender_id = isset($_GET['lender_id']) ? intval($_GET['lender_id']) : 0;

if ($lender_id <= 0) {
    header("Location: renter_dashboard.php" . $lang_param);
    exit();
}

// Fetch unread notification count for sidebar
$notif_count = 0;
$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($notif_check && $notif_check->num_rows > 0) {
    $n_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)");
    if ($n_stmt) {
        $n_stmt->bind_param("i", $renter_id);
        $n_stmt->execute();
        $notif_count = intval($n_stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $n_stmt->close();
    }
}

// Fetch exact lender details from the users table
$stmt = $conn->prepare("SELECT full_name, phone, address, profile_pic FROM users WHERE user_id = ? AND role = 'lender'");
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: renter_dashboard.php" . $lang_param);
    exit();
}
$lender = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title>Lender Details - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; color: #1e293b; margin: 0; font-weight: 500; }
        
        /* Sidebar Styles */
        .sidebar { width: 260px; background: #fff; min-height: 100vh; padding: 20px; border-right: 1px solid #e0e0e0; position: fixed; }
        .logo { display: flex; align-items: center; gap: 12px; font-weight: 900; color: #198754; font-size: 16px; margin-bottom: 35px; line-height: 1.2; }
        .logo i { font-size: 28px; color: #198754; }
        .logo-text-main { font-size: 15px; font-weight: 900; letter-spacing: 0.3px; color: #198754; display: block; }
        .logo-text-sub { font-size: 10px; font-weight: 800; color: #198754; letter-spacing: 0.5px; display: block; margin-top: 2px; }

        .nav-list { list-style: none; padding-left: 0; }
        .nav-item { margin-bottom: 10px; }
        .nav-link { display: flex; align-items: center; justify-content: space-between; padding: 13px 16px; color: #334155; text-decoration: none; border-radius: 10px; font-weight: 700; font-size: 15px; transition: 0.2s; }
        .nav-link-content { display: flex; align-items: center; gap: 14px; }
        .nav-link i { font-size: 17px; width: 20px; text-align: center; }
        .nav-link:hover, .nav-link.active { background-color: #198754; color: #fff; }
        .nav-link:hover .badge-count, .nav-link.active .badge-count { background: #fff; color: #198754; }

        /* Main Content Layout */
        .main-content { margin-left: 260px; flex: 1; padding: 20px 30px; }
        
        /* Top Navigation Bar */
        .top-nav-bar { background: #fff; padding: 12px 25px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; gap: 20px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        /* Breadcrumb */
        .breadcrumb-custom { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .breadcrumb-custom a { color: #198754; text-decoration: none; }
        .breadcrumb-custom a:hover { text-decoration: underline; }

        .page-header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-header { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .page-subtitle { font-size: 14px; color: #64748b; font-weight: 600; margin: 0; }

        /* Card Styles */
        .content-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); max-width: 700px; }
        .card-title-custom { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
        .card-title-custom i { color: #198754; }

        /* Lender Profile Banner */
        .lender-profile-header { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; }
        .lender-avatar-box { width: 75px; height: 75px; border-radius: 50%; overflow: hidden; background: #e2e8f0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: #198754; border: 2px solid #cbd5e1; }
        .lender-avatar-box img { width: 100%; height: 100%; object-fit: cover; }
        .lender-title-info h3 { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .lender-title-info p { font-size: 13px; font-weight: 600; color: #64748b; margin: 0; }

        /* Info Display Row */
        .info-row-item { display: flex; justify-content: space-between; align-items: flex-start; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .info-row-item:last-child { border-bottom: none; }
        .info-label-title { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; display: flex; align-items: center; gap: 10px; }
        .info-value-text { font-size: 15px; font-weight: 800; color: #0f172a; text-align: right; }

        .badge-count { background: #dc2626; color: #fff; border-radius: 50px; padding: 2px 8px; font-size: 11px; font-weight: 900; }
        .profile-avatar-btn { width: 38px; height: 38px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #334155; text-decoration: none; font-size: 16px; border: 2px solid #cbd5e1; transition: 0.2s; }
        .profile-avatar-btn:hover { background: #198754; color: #fff; border-color: #198754; }

        @media(max-width: 768px) {
            .sidebar { width: 75px; padding: 15px 10px; }
            .sidebar .logo span, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 75px; }
            .lender-profile-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-tractor"></i>
            <div>
                <span class="logo-text-main">AGRI-RENT</span>
                <span class="logo-text-sub">EQUIPMENT SYSTEM</span>
            </div>
        </div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="renter_dashboard.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-grid-2"></i> <span>Categories</span></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="my_bookings.php<?php echo $lang_param; ?>" class="nav-link active">
                    <span class="nav-link-content"><i class="fa-solid fa-clock-rotate-left"></i> <span>My Bookings</span></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="notifications.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-bell"></i> <span>Notifications</span></span>
                    <span class="badge-count"><?php echo $notif_count; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-user"></i> <span>My Profile</span></span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: 35px;">
                <a href="logout.php" class="nav-link" style="color: #ef4444;">
                    <span class="nav-link-content"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Page Content -->
    <div class="main-content">
        
        <!-- Top Navigation Bar -->
        <div class="top-nav-bar">
            <form action="lender_details.php" method="GET" class="d-flex align-items-center mb-0">
                <input type="hidden" name="lender_id" value="<?php echo $lender_id; ?>">
                <select name="lang" class="form-select form-select-sm fw-bold w-auto" onchange="this.form.submit()">
                    <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                    <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                </select>
            </form>
            <a href="notifications.php<?php echo $lang_param; ?>" class="position-relative text-dark text-decoration-none p-1">
                <i class="fa-solid fa-bell fa-lg"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px; font-weight: 900;"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php<?php echo $lang_param; ?>" class="profile-avatar-btn">
                <i class="fa-solid fa-user"></i>
            </a>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb-custom">
            <a href="renter_dashboard.php<?php echo $lang_param; ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
            <a href="my_bookings.php<?php echo $lang_param; ?>">My Bookings</a>
            <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
            <span class="text-dark fw-bold">Lender Details</span>
        </div>

        <!-- Page Header & Back Button -->
        <div class="page-header-box">
            <div>
                <h1 class="page-header">Lender Information</h1>
                <p class="page-subtitle">View verified contact details and address provided by the lender.</p>
            </div>
            <a href="javascript:history.back();" class="btn btn-outline-success fw-bold btn-sm px-3 py-2">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <!-- Lender Details Card -->
        <div class="content-card">
            <div class="card-title-custom">
                <i class="fa-solid fa-user-tie"></i> Lender Profile Overview
            </div>

            <div class="lender-profile-header">
                <div class="lender-avatar-box">
                    <?php 
                        $profile_pic = $lender['profile_pic'] ?? 'default_avatar.png';
                        $has_pic = ($profile_pic !== 'default_avatar.png' && file_exists(__DIR__ . '/uploads/' . $profile_pic));
                    ?>
                    <?php if ($has_pic): ?>
                        <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="Lender">
                    <?php else: ?>
                        <?php echo strtoupper(substr($lender['full_name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="lender-title-info">
                    <h3><?php echo htmlspecialchars($lender['full_name']); ?></h3>
                    <p><i class="fa-solid fa-circle-check text-success me-1"></i> Registered Equipment Lender</p>
                </div>
            </div>

            <!-- Lender Exact Fields from Database -->
            <div class="info-row-item">
                <div class="info-label-title"><i class="fa-solid fa-user text-success"></i> Full Name</div>
                <div class="info-value-text"><?php echo htmlspecialchars($lender['full_name']); ?></div>
            </div>

            <div class="info-row-item">
                <div class="info-label-title"><i class="fa-solid fa-phone text-success"></i> Phone Number</div>
                <div class="info-value-text">
                    <a href="tel:<?php echo htmlspecialchars($lender['phone']); ?>" class="text-success text-decoration-none">
                        <?php echo htmlspecialchars($lender['phone']); ?>
                    </a>
                </div>
            </div>

            <div class="info-row-item">
                <div class="info-label-title"><i class="fa-solid fa-location-dot text-danger"></i> Address</div>
                <div class="info-value-text text-end" style="max-width: 320px; font-weight: 600; color: #334155;">
                    <?php echo !empty($lender['address']) ? nl2br(htmlspecialchars($lender['address'])) : '<span class="text-muted">Not Provided</span>'; ?>
                </div>
            </div>

            <!-- Action Button Back -->
            <div class="mt-4 pt-3 border-top text-end">
                <a href="javascript:history.back();" class="btn btn-success fw-bold px-4 py-2">
                    <i class="fa-solid fa-arrow-left me-2"></i> Return to Booking
                </a>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>