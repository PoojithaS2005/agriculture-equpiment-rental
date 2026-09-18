<?php
session_start();
require_once 'includes/config.php';

if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$renter_id = $_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';

// CSRF protection for renter confirmation actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$lang_param = '?lang=' . urlencode($current_lang);

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if ($booking_id <= 0) {
    header("Location: my_bookings.php" . $lang_param);
    exit();
}

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

$sql = "SELECT b.*, 
               e.title AS equipment_title, e.category AS equipment_category, e.service_location, e.image AS equipment_image, e.price_per_day,
               u.user_id AS lender_id, u.full_name AS lender_name, u.phone AS lender_phone, u.email AS lender_email, u.address AS lender_address
        FROM bookings b
        JOIN equipment e ON b.equipment_id = e.equipment_id
        JOIN users u ON e.lender_id = u.user_id
        WHERE b.booking_id = ? AND b.renter_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $renter_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: my_bookings.php" . $lang_param);
    exit();
}
$booking = $result->fetch_assoc();
$stmt->close();

// Fetch the renter's review for this completed booking.
$review = null;
$review_stmt = $conn->prepare("SELECT review_id, rating, review_text, created_at, updated_at FROM reviews WHERE booking_id = ? AND renter_id = ? LIMIT 1");
if ($review_stmt) {
    $review_stmt->bind_param('ii', $booking_id, $renter_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    $review = $review_result->fetch_assoc() ?: null;
    $review_stmt->close();
}

/* =========================================================
   RENTER CONFIRMATION ACTIONS
   ========================================================= */
$action_message = '';
$action_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $action_error = 'Invalid request. Please try again.';
    } elseif ($action === 'confirm_delivery') {
        if (($booking['status'] ?? '') !== 'Delivered') {
            $action_error = 'Delivery can be confirmed only after the lender marks it as delivered.';
        } elseif (!empty($booking['delivery_confirmed'])) {
            $action_error = 'Delivery is already confirmed.';
        } else {
            $confirm_stmt = $conn->prepare("
                UPDATE bookings
                SET delivery_confirmed = 1, delivery_confirmed_at = NOW()
                WHERE booking_id = ? AND renter_id = ? AND status = 'Delivered'
            ");
            if ($confirm_stmt) {
                $confirm_stmt->bind_param('ii', $booking_id, $renter_id);
                if ($confirm_stmt->execute() && $confirm_stmt->affected_rows > 0) {
                    $action_message = 'Delivery confirmed successfully.';
                    $booking['delivery_confirmed'] = 1;
                    $booking['delivery_confirmed_at'] = date('Y-m-d H:i:s');
                } else {
                    $action_error = 'Unable to confirm delivery. Please try again.';
                }
                $confirm_stmt->close();
            } else {
                $action_error = 'Unable to process delivery confirmation.';
            }
        }
    } elseif ($action === 'confirm_return') {
        if (($booking['status'] ?? '') !== 'Returned') {
            $action_error = 'Return can be confirmed only after the lender marks it as returned.';
        } elseif (!empty($booking['return_confirmed'])) {
            $action_error = 'Return is already confirmed.';
        } else {
            $confirm_stmt = $conn->prepare("
                UPDATE bookings
                SET return_confirmed = 1, return_confirmed_at = NOW(), status = 'Completed'
                WHERE booking_id = ? AND renter_id = ? AND status = 'Returned'
            ");
            if ($confirm_stmt) {
                $confirm_stmt->bind_param('ii', $booking_id, $renter_id);
                if ($confirm_stmt->execute() && $confirm_stmt->affected_rows > 0) {
                    $action_message = 'Return confirmed successfully. Rental completed.';
                    $booking['return_confirmed'] = 1;
                    $booking['return_confirmed_at'] = date('Y-m-d H:i:s');
                    $booking['status'] = 'Completed';
                } else {
                    $action_error = 'Unable to confirm return. Please try again.';
                }
                $confirm_stmt->close();
            } else {
                $action_error = 'Unable to process return confirmation.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title>Booking Details - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; color: #1e293b; margin: 0; font-weight: 500; }
        
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

        .main-content { margin-left: 260px; flex: 1; padding: 20px 30px; }
        .top-nav-bar { background: #fff; padding: 12px 25px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; gap: 20px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .breadcrumb-custom { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .breadcrumb-custom a { color: #198754; text-decoration: none; }
        .breadcrumb-custom a:hover { text-decoration: underline; }

        .page-header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-header { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .page-subtitle { font-size: 14px; color: #64748b; font-weight: 600; margin: 0; }

        .details-grid { display: grid; grid-template-columns: 1fr 380px; gap: 25px; align-items: start; }
        @media(max-width: 1024px) {
            .details-grid { grid-template-columns: 1fr; }
            .sidebar { width: 75px; padding: 15px 10px; }
            .sidebar .logo span, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 75px; }
        }

        .review-card { background:#fffdf5; border:1px solid #fde68a; border-radius:14px; padding:22px; margin-bottom:20px; }
        .review-stars { color:#f59e0b; font-size:18px; letter-spacing:2px; }
        .review-text { color:#334155; font-size:14px; line-height:1.6; white-space:pre-wrap; }
        .btn-review { display:inline-flex; align-items:center; gap:7px; background:#f59e0b; color:#fff; text-decoration:none; border-radius:8px; padding:8px 14px; font-weight:800; font-size:13px; }
        .btn-review:hover { background:#d97706; color:#fff; }
        .content-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .card-title-custom { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .card-title-custom i { color: #198754; }

        .booking-main-row { display: flex; gap: 20px; align-items: center; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .equipment-img-box { width: 140px; height: 100px; border-radius: 10px; overflow: hidden; background: #f1f5f9; flex-shrink: 0; }
        .equipment-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .equipment-detail-info h4 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .equipment-detail-info p { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 4px; }

        .specs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .spec-item { background: #f8fafc; padding: 12px 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .spec-label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
        .spec-value { font-size: 14px; font-weight: 800; color: #0f172a; }

        .badge-status { padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; }
        .status-upcoming { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .status-ongoing { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .status-completed { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-cancelled { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .status-pending { background: #f1f5f9; color: #737373; border: 1px solid #cbd5e1; }

        .progress-timeline { display: flex; justify-content: space-between; position: relative; margin: 30px 0 10px 0; padding: 0 10px; }
        .progress-timeline::before { content: ''; position: absolute; top: 15px; left: 30px; right: 30px; height: 3px; background: #e2e8f0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .step-icon { width: 34px; height: 34px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-size: 13px; font-weight: 800; border: 3px solid #fff; box-shadow: 0 0 0 1px #cbd5e1; }
        .timeline-step.completed .step-icon { background: #198754; color: #fff; box-shadow: 0 0 0 1px #198754; }
        .timeline-step.current .step-icon { background: #0284c7; color: #fff; box-shadow: 0 0 0 1px #0284c7; }
        .step-title { font-size: 12px; font-weight: 700; color: #334155; }

        .timeline-list { position: relative; padding-left: 25px; margin-top: 15px; }
        .timeline-list::before { content: ''; position: absolute; left: 7px; top: 5px; bottom: 5px; width: 2px; background: #e2e8f0; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-dot { position: absolute; left: -25px; top: 3px; width: 16px; height: 16px; border-radius: 50%; background: #cbd5e1; border: 3px solid #fff; box-shadow: 0 0 0 1px #94a3b8; }
        .timeline-item.completed .timeline-dot { background: #198754; box-shadow: 0 0 0 1px #198754; }
        .timeline-content h6 { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
        .timeline-content p { font-size: 12px; font-weight: 600; color: #64748b; margin: 0; }

        .summary-row { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 12px; }
        .summary-row.total { font-size: 16px; font-weight: 900; color: #0f172a; border-top: 1px dashed #cbd5e1; padding-top: 12px; margin-top: 12px; }
        .summary-row .amount { font-weight: 800; color: #198754; }

        .action-btns-box { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .confirmation-box { margin-top: 18px; padding: 16px; border-radius: 12px; border: 1px solid #dbeafe; background: #eff6ff; }
        .confirmation-box.confirmed { border-color: #bbf7d0; background: #f0fdf4; }
        .confirmation-title { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .confirmation-text { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 12px; }
        .btn-confirm-rental { width: 100%; border: 0; border-radius: 9px; padding: 10px 12px; background: #198754; color: #fff; font-size: 13px; font-weight: 800; }
        .btn-confirm-rental:hover { background: #157347; }
        .alert-action { margin-bottom: 18px; font-size: 13px; font-weight: 700; }
        .btn-action-custom { width: 100%; padding: 11px; border-radius: 10px; font-size: 14px; font-weight: 700; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
        .btn-lender-details { background: #f8fafc; color: #334155; border: 1.5px solid #cbd5e1; }
        .btn-lender-details:hover { background: #e2e8f0; color: #0f172a; }
        .btn-contact-lender { background: #198754; color: #fff; border: 1.5px solid #198754; }
        .btn-contact-lender:hover { background: #157347; color: #fff; }

        .notes-list { padding-left: 18px; margin: 0; font-size: 13px; font-weight: 600; color: #475569; line-height: 1.6; }
        .notes-list li { margin-bottom: 6px; }

        .badge-count { background: #dc2626; color: #fff; border-radius: 50px; padding: 2px 8px; font-size: 11px; font-weight: 900; }
        .profile-avatar-btn { width: 38px; height: 38px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #334155; text-decoration: none; font-size: 16px; border: 2px solid #cbd5e1; transition: 0.2s; }
        .profile-avatar-btn:hover { background: #198754; color: #fff; border-color: #198754; }
    </style>
</head>
<body>

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

    <div class="main-content">
        
        <div class="top-nav-bar">
            <form action="booking_details.php" method="GET" class="d-flex align-items-center mb-0">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
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

        <div class="breadcrumb-custom">
            <a href="renter_dashboard.php<?php echo $lang_param; ?>">Dashboard</a>
            <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
            <a href="my_bookings.php<?php echo $lang_param; ?>">My Bookings</a>
            <i class="fa-solid fa-chevron-right" style="font-size: 10px;"></i>
            <span class="text-dark fw-bold">Booking Details</span>
        </div>

        <div class="page-header-box">
            <div>
                <h1 class="page-header">Booking Details & Status</h1>
                <p class="page-subtitle">Track your equipment rental status and lender details.</p>
            </div>
            <a href="my_bookings.php<?php echo $lang_param; ?>" class="btn btn-outline-success fw-bold btn-sm px-3 py-2">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to My Bookings
            </a>
        </div>

        <?php 
            $img_path = !empty($booking['equipment_image']) ? 'uploads/' . $booking['equipment_image'] : '';
            $has_img = !empty($booking['equipment_image']) && file_exists(__DIR__ . '/' . $img_path);

            $st = $booking['status'];
            $badge_class = 'status-pending';
            $status_icon = 'fa-clock';
            if ($st === 'Accepted') { $badge_class = 'status-upcoming'; $status_icon = 'fa-calendar-check'; }
            elseif ($st === 'Delivered') { $badge_class = 'status-ongoing'; $status_icon = 'fa-spinner fa-spin'; }
            elseif ($st === 'Returned') { $badge_class = 'status-completed'; $status_icon = 'fa-circle-check'; }
            elseif ($st === 'Completed') { $badge_class = 'status-completed'; $status_icon = 'fa-circle-check'; }
            elseif ($st === 'Rejected' || $st === 'Overdue') { $badge_class = 'status-cancelled'; $status_icon = 'fa-circle-xmark'; }
        ?>

        <?php if ($action_message): ?>
            <div class="alert alert-success alert-action"><i class="fa-solid fa-circle-check me-1"></i><?php echo htmlspecialchars($action_message); ?></div>
        <?php endif; ?>
        <?php if ($action_error): ?>
            <div class="alert alert-danger alert-action"><i class="fa-solid fa-circle-exclamation me-1"></i><?php echo htmlspecialchars($action_error); ?></div>
        <?php endif; ?>

        <div class="details-grid">
            
            <div>
                <!-- Booking Information Card -->
                <div class="content-card">
                    <div class="card-title-custom">
                        <i class="fa-solid fa-circle-info"></i> Booking Information
                    </div>

                    <div class="booking-main-row">
                        <div class="equipment-img-box">
                            <?php if ($has_img): ?>
                                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Equipment">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted bg-light">
                                    <i class="fa-solid fa-tractor fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="equipment-detail-info">
                            <h4><?php echo htmlspecialchars($booking['equipment_title']); ?></h4>
                            <p class="mb-1">Category: <strong><?php echo htmlspecialchars($booking['equipment_category']); ?></strong></p>
                            <p class="mb-0"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?php echo htmlspecialchars($booking['service_location']); ?></p>
                        </div>
                    </div>

                    <div class="specs-grid">
                        <div class="spec-item">
                            <div class="spec-label">Booking ID</div>
                            <div class="spec-value"><i class="fa-solid fa-barcode text-muted me-1"></i> <?php echo htmlspecialchars($booking['request_code'] ?? '#' . $booking['booking_id']); ?></div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Booking Date</div>
                            <div class="spec-value"><i class="fa-solid fa-calendar-days text-muted me-1"></i> <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Lender Name</div>
                            <div class="spec-value"><i class="fa-solid fa-user text-muted me-1"></i> <?php echo htmlspecialchars($booking['lender_name']); ?></div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Phone Number</div>
                            <div class="spec-value"><i class="fa-solid fa-phone text-muted me-1"></i> <?php echo htmlspecialchars($booking['lender_phone']); ?></div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted fw-bold" style="font-size: 13px;"><i class="fa-solid fa-calendar-week me-1"></i> Rental Period</span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-bold px-2 py-1"><?php echo intval($booking['total_days']); ?> Days</span>
                        </div>
                        <div class="fw-bold text-dark" style="font-size: 15px;">
                            <?php echo date('d M Y', strtotime($booking['start_date'])); ?> – <?php echo date('d M Y', strtotime($booking['end_date'])); ?>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted fw-bold mb-1" style="font-size: 13px;"><i class="fa-solid fa-location-crosshairs me-1 text-danger"></i> Delivery Address</div>
                        <div class="fw-semibold text-dark" style="font-size: 14px;"><?php echo nl2br(htmlspecialchars($booking['delivery_address'] ?? 'N/A')); ?></div>
                    </div>
                </div>

                <!-- Booking Status Progress Stepper -->
                <div class="content-card">
                    <div class="card-title-custom">
                        <i class="fa-solid fa-bars-progress"></i> Booking Status
                        <div class="ms-auto">
                            <span class="badge-status <?php echo $badge_class; ?>">
                                <i class="fa-solid <?php echo $status_icon; ?>"></i> <?php echo htmlspecialchars($st); ?>
                            </span>
                        </div>
                    </div>

                    <?php 
                        $is_pending = ($st === 'Pending');
                        $is_accepted = ($st === 'Accepted');
                        $is_delivered = ($st === 'Delivered');
                        $is_returned = ($st === 'Returned');
                    ?>

                    <div class="progress-timeline">
                        <div class="timeline-step completed">
                            <div class="step-icon"><i class="fa-solid fa-check"></i></div>
                            <div class="step-title">Submitted</div>
                        </div>
                        <div class="timeline-step <?php echo (!$is_pending) ? 'completed' : 'current'; ?>">
                            <div class="step-icon"><?php echo (!$is_pending) ? '<i class="fa-solid fa-check"></i>' : '2'; ?></div>
                            <div class="step-title">Pending Approval</div>
                        </div>
                        <div class="timeline-step <?php echo ($is_delivered || $is_returned) ? 'completed' : ($is_accepted ? 'current' : ''); ?>">
                            <div class="step-icon"><?php echo ($is_delivered || $is_returned) ? '<i class="fa-solid fa-check"></i>' : '3'; ?></div>
                            <div class="step-title">Accepted</div>
                        </div>
                        <div class="timeline-step <?php echo $is_returned ? 'completed' : ($is_delivered ? 'current' : ''); ?>">
                            <div class="step-icon"><?php echo $is_returned ? '<i class="fa-solid fa-check"></i>' : '4'; ?></div>
                            <div class="step-title">Delivered</div>
                        </div>
                        <div class="timeline-step <?php echo $is_returned ? 'completed' : ''; ?>">
                            <div class="step-icon"><?php echo $is_returned ? '<i class="fa-solid fa-check"></i>' : '5'; ?></div>
                            <div class="step-title">Returned</div>
                        </div>
                        <div class="timeline-step <?php echo ($st === 'Completed') ? 'completed' : ''; ?>">
                            <div class="step-icon"><?php echo ($st === 'Completed') ? '<i class="fa-solid fa-check"></i>' : '6'; ?></div>
                            <div class="step-title">Completed</div>
                        </div>
                    </div>
                </div>

                <!-- Your Review -->
                <?php if ($st === 'Completed'): ?>
                    <div class="content-card review-card">
                        <div class="card-title-custom">
                            <i class="fa-solid fa-star"></i> <?php echo __('your_review'); ?>
                        </div>

                        <?php if ($review): ?>
                            <div class="review-stars" aria-label="<?php echo intval($review['rating']); ?> out of 5 stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php echo ($i <= intval($review['rating'])) ? '★' : '☆'; ?>
                                <?php endfor; ?>
                                <span style="color:#475569;font-size:13px;letter-spacing:0;margin-left:8px;">
                                    <?php echo intval($review['rating']); ?>/5
                                </span>
                            </div>
                            <div class="review-text mt-3"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                            <div class="text-muted mt-3" style="font-size:12px;font-weight:600;">
                                <?php echo __('reviewed_on'); ?>:
                                <?php echo date('d M Y, h:i A', strtotime($review['created_at'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted fw-semibold mb-3"><?php echo __('no_review_yet'); ?></p>
                            <a href="review_submit.php?booking_id=<?php echo urlencode($booking_id); ?><?php echo $lang_param; ?>" class="btn-review">
                                <i class="fa-solid fa-star"></i> <?php echo __('submit_review'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Rental Timeline Section -->
                <div class="content-card">
                    <div class="card-title-custom">
                        <i class="fa-solid fa-timeline"></i> Rental Timeline
                    </div>

                    <div class="timeline-list">
                        <div class="timeline-item completed">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h6>Request Submitted</h6>
                                <p>You have requested to book this equipment. (<?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>)</p>
                            </div>
                        </div>

                        <div class="timeline-item <?php echo (!$is_pending) ? 'completed' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h6>Lender Review & Approval</h6>
                                <p><?php echo (!$is_pending) ? 'Lender has reviewed and accepted your request.' : 'Awaiting lender review and confirmation.'; ?></p>
                            </div>
                        </div>

                        <div class="timeline-item <?php echo ($is_delivered || $is_returned || $st === 'Completed') ? 'completed' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h6>Equipment Delivery</h6>
                                <p><?php echo ($is_delivered || $is_returned || $st === 'Completed') ? 'Equipment has been delivered successfully.' : 'Pending delivery execution by the lender.'; ?></p>
                            </div>
                        </div>

                        <div class="timeline-item <?php echo ($is_returned || $st === 'Completed') ? 'completed' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h6>Equipment Return</h6>
                                <p><?php echo ($is_returned || $st === 'Completed') ? 'The lender has collected the equipment.' : 'Return will be recorded after the lender collects the equipment.'; ?></p>
                            </div>
                        </div>

                        <div class="timeline-item <?php echo ($st === 'Completed') ? 'completed' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h6>Rental Completed</h6>
                                <p><?php echo ($st === 'Completed') ? 'You confirmed the return. This rental is completed.' : 'Waiting for return confirmation.'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Notes -->
                <div class="content-card">
                    <div class="card-title-custom">
                        <i class="fa-solid fa-triangle-exclamation"></i> Important Notes
                    </div>
                    <ul class="notes-list">
                        <li>Ensure the equipment is operated carefully and used only for intended agricultural purposes.</li>
                        <li>Return the equipment on or before the agreed end date to avoid late penalty charges.</li>
                        <li>Inspect the equipment upon delivery and report any mechanical issues immediately.</li>
                        <li>Keep the equipment clean and securely stored when not in use during your rental period.</li>
                        <li>Contact the lender directly if you need any assistance or have questions regarding operation.</li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Order Summary & Action Buttons -->
            <div>
                <div class="content-card sticky-top" style="top: 20px;">
                    <div class="card-title-custom">
                        <i class="fa-solid fa-receipt"></i> Order Summary
                    </div>

                    <div class="summary-row">
                        <span>Price per Day</span>
                        <span class="fw-bold text-dark">₹<?php echo number_format($booking['price_per_day'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Total Days</span>
                        <span class="fw-bold text-dark"><?php echo intval($booking['total_days']); ?> Days</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Rent</span>
                        <span class="amount">₹<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Advance Paid</span>
                        <span class="fw-bold text-dark">₹<?php echo number_format($booking['advance_amount'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Remaining Amount (COD)</span>
                        <span class="fw-bold text-danger">₹<?php echo number_format($booking['remaining_cod'] ?? ($booking['total_amount'] - $booking['advance_amount']), 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Payment Method</span>
                        <span class="badge bg-light text-dark border px-2 py-1 fw-bold">Cash on Delivery</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-btns-box">
                        <a href="lender_details.php?lender_id=<?php echo $booking['lender_id']; ?><?php echo $lang_param; ?>" class="btn-action-custom btn-lender-details">
                            <span class="mb-0"><i class="fa-solid fa-user-tie"></i> View Lender Details</span>
                        </a>
                        <a href="tel:<?php echo htmlspecialchars($booking['lender_phone']); ?>" class="btn-action-custom btn-contact-lender">
                            <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($booking['lender_phone']); ?>
                        </a>

                    <?php if ($st === 'Delivered'): ?>
                        <div class="confirmation-box <?php echo !empty($booking['delivery_confirmed']) ? 'confirmed' : ''; ?>">
                            <div class="confirmation-title"><i class="fa-solid fa-truck me-1"></i> Delivery Confirmation</div>
                            <?php if (!empty($booking['delivery_confirmed'])): ?>
                                <div class="confirmation-text mb-0"><i class="fa-solid fa-circle-check text-success me-1"></i>You have confirmed that the equipment was delivered.</div>
                            <?php else: ?>
                                <div class="confirmation-text">The lender has marked this equipment as delivered. Please confirm that you received it.</div>
                                <form method="POST" onsubmit="return confirm('Confirm that you received the equipment?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="confirm_delivery">
                                    <button type="submit" class="btn-confirm-rental"><i class="fa-solid fa-circle-check me-1"></i> Confirm Delivery</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($st === 'Returned'): ?>
                        <div class="confirmation-box <?php echo !empty($booking['return_confirmed']) ? 'confirmed' : ''; ?>">
                            <div class="confirmation-title"><i class="fa-solid fa-rotate-left me-1"></i> Return Confirmation</div>
                            <?php if (!empty($booking['return_confirmed'])): ?>
                                <div class="confirmation-text mb-0"><i class="fa-solid fa-circle-check text-success me-1"></i>You have confirmed that the equipment was returned.</div>
                            <?php else: ?>
                                <div class="confirmation-text">The lender has marked this equipment as returned. Please confirm that the equipment was collected.</div>
                                <form method="POST" onsubmit="return confirm('Confirm that the equipment was collected by the lender?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="confirm_return">
                                    <button type="submit" class="btn-confirm-rental"><i class="fa-solid fa-circle-check me-1"></i> Confirm Return</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($st === 'Completed'): ?>
                        <div class="confirmation-box confirmed">
                            <div class="confirmation-title"><i class="fa-solid fa-circle-check me-1 text-success"></i> Rental Completed</div>
                            <div class="confirmation-text mb-0">Delivery and return have both been confirmed. This rental is completed.</div>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>