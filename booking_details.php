<?php
session_start();

require_once __DIR__ . '/includes/config.php';

$allowed_languages = ['en', 'kn', 'hi'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_languages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    $_SESSION['language'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? $_SESSION['language'] ?? 'en';

if (!in_array($current_lang, $allowed_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
    $_SESSION['language'] = 'en';
}

require_once __DIR__ . '/includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$renter_id = (int)$_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    header("Location: my_bookings.php?lang=" . urlencode($current_lang));
    exit();
}

/*
|--------------------------------------------------------------------------
| Handle Booking Cancellation
|--------------------------------------------------------------------------
*/
if (isset($_POST['cancel_booking'])) {
    // Cancellation is allowed only before delivery: Pending or Accepted.
    // Once the lender marks the booking as Delivered, cancellation is not allowed.
    $equipment_title_for_notification = 'Equipment';
    $lender_id_for_notification = 0;

    // Get the appropriate lender and equipment before changing the status.
    $cancel_info_stmt = $conn->prepare("
        SELECT e.lender_id, e.title
        FROM bookings b
        INNER JOIN equipment e ON b.equipment_id = e.equipment_id
        WHERE b.booking_id = ?
          AND b.renter_id = ?
          AND b.status IN ('Pending', 'Accepted')
        LIMIT 1
    ");

    if ($cancel_info_stmt) {
        $cancel_info_stmt->bind_param("ii", $booking_id, $renter_id);
        $cancel_info_stmt->execute();
        $cancel_info = $cancel_info_stmt->get_result()->fetch_assoc();
        if ($cancel_info) {
            $lender_id_for_notification = (int)$cancel_info['lender_id'];
            $equipment_title_for_notification = $cancel_info['title'] ?: 'Equipment';
        }
        $cancel_info_stmt->close();
    }

    // Update only if the booking is still Pending or Accepted.
    $cancel_stmt = $conn->prepare("
        UPDATE bookings
        SET status = 'Cancelled'
        WHERE booking_id = ?
          AND renter_id = ?
          AND status IN ('Pending', 'Accepted')
    ");

    $cancelled_successfully = false;
    if ($cancel_stmt) {
        $cancel_stmt->bind_param("ii", $booking_id, $renter_id);
        $cancel_stmt->execute();
        $cancelled_successfully = ($cancel_stmt->affected_rows > 0);
        $cancel_stmt->close();
    }

    // Notify the appropriate lender only after a successful cancellation.
    if ($cancelled_successfully && $lender_id_for_notification > 0) {
        $notification_title = 'Booking Cancelled by Renter';
        $notification_message = 'The renter has cancelled the booking for ' . $equipment_title_for_notification . '.';

        $notification_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, is_read)
            VALUES (?, ?, ?, 0)
        ");

        if ($notification_stmt) {
            $notification_stmt->bind_param(
                "iss",
                $lender_id_for_notification,
                $notification_title,
                $notification_message
            );
            $notification_stmt->execute();
            $notification_stmt->close();
        }
    }

    header("Location: booking_details.php?booking_id=" . $booking_id . "&lang=" . urlencode($current_lang) . "&cancelled=1");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Booking & Equipment Details
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        b.*,
        e.title AS equipment_title,
        e.category AS equipment_category,
        e.service_location,
        e.image AS equipment_image,
        e.price_per_day,
        u.full_name AS lender_name,
        u.phone AS lender_phone,
        u.email AS lender_email
    FROM bookings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    JOIN users u ON e.lender_id = u.user_id
    WHERE b.booking_id = ? AND b.renter_id = ?
";

$stmt = $conn->prepare($sql);
$booking = null;

if ($stmt) {
    $stmt->bind_param("ii", $booking_id, $renter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
}

if (!$booking) {
    header("Location: my_bookings.php?lang=" . urlencode($current_lang));
    exit();
}

/*
|--------------------------------------------------------------------------
| Unread Notification Count
|--------------------------------------------------------------------------
*/
$notif_count = 0;
$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($notif_check && $notif_check->num_rows > 0) {
    $n_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");
    if ($n_stmt) {
        $n_stmt->bind_param("i", $renter_id);
        $n_stmt->execute();
        $notif_count = (int)($n_stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $n_stmt->close();
    }
}

$lang_param = '?lang=' . urlencode($current_lang);

// Calculations
$start_ts = strtotime($booking['start_date']);
$end_ts = strtotime($booking['end_date']);
$total_days = max(1, ceil(($end_ts - $start_ts) / 86400) + 1);
$status = $booking['status'];
$just_cancelled = isset($_GET['cancelled']) && $_GET['cancelled'] === '1' && $status === 'Cancelled';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Agriculture Equipment Rental System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
            color: #1e293b;
            margin: 0;
            font-weight: 500;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px 30px;
        }

        .top-nav-bar {
            background: #fff;
            padding: 12px 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .breadcrumb-nav {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
        }

        .breadcrumb-nav a {
            color: #64748b;
            text-decoration: none;
        }

        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-header {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .page-subtitle {
            font-size: 13px;
            color: #64748b;
        }

        .btn-back {
            border: 1px solid #198754;
            color: #198754;
            background: #fff;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #198754;
            color: #fff;
        }

        .content-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }

        .card-title-custom {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .equipment-box {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .equipment-img {
            width: 100px;
            height: 75px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .meta-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .meta-item label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 800;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .meta-item span {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }

        .info-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Stepper */
        .stepper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 25px 0 10px 0;
        }

        .stepper::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 5%;
            right: 5%;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background: #fff;
            padding: 0 8px;
            text-align: center;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px auto;
            font-size: 13px;
            font-weight: 800;
        }

        .step.active .step-circle {
            background: #0284c7;
            color: #fff;
        }

        .step.completed .step-circle {
            background: #16a34a;
            color: #fff;
        }

        .step-label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
        }

        /* Timeline list */
        .timeline-list {
            list-style: none;
            padding-left: 15px;
            position: relative;
            margin: 0;
        }

        .timeline-list::before {
            content: '';
            position: absolute;
            top: 5px;
            bottom: 5px;
            left: 4px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            padding-left: 20px;
            margin-bottom: 15px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .timeline-item.active::before {
            background: #16a34a;
        }

        .timeline-item h6 {
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .timeline-item p {
            font-size: 11px;
            color: #64748b;
            margin: 0;
        }

        .profile-avatar-btn {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            text-decoration: none;
            font-size: 16px;
            border: 2px solid #cbd5e1;
        }

        .btn-cancel-booking {
            background: #dc3545;
            color: #fff;
            border: none;
            width: 100%;
            padding: 9px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
            margin-top: 10px;
        }

        .btn-cancel-booking:hover {
            background: #bb2d3b;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/renter_sidebar.php'; ?>

<div class="main-content">

    <!-- Top Navigation Bar -->
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
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                    <?php echo $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="profile.php<?php echo $lang_param; ?>" class="profile-avatar-btn">
            <i class="fa-solid fa-user"></i>
        </a>
    </div>

    <!-- Header Section -->
    <div class="breadcrumb-nav">
        <a href="dashboard.php<?php echo $lang_param; ?>">Dashboard</a> &gt; 
        <a href="my_bookings.php<?php echo $lang_param; ?>">My Bookings</a> &gt; 
        <span>Booking Details</span>
    </div>

    <div class="page-header-container">
        <div>
            <h1 class="page-header"><?php echo htmlspecialchars(__('booking_details') === 'booking_details' ? 'Booking Details & Status' : __('booking_details')); ?></h1>
            <div class="page-subtitle">Track your equipment rental status and lender details.</div>
        </div>
        <a href="my_bookings.php<?php echo $lang_param; ?>" class="btn-back">
            &larr; Back to My Bookings
        </a>
    </div>

    <?php if ($status === 'Cancelled'): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert" style="border-radius: 10px; font-size: 13px; font-weight: 600;">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            You have cancelled this equipment booking.
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            
            <!-- Booking Information Card -->
            <div class="content-card">
                <div class="card-title-custom">
                    <i class="fa-solid fa-circle-info text-success"></i> Booking Information
                </div>

                <div class="equipment-box">
                    <?php 
                    $img_path = !empty($booking['equipment_image']) ? 'uploads/' . $booking['equipment_image'] : '';
                    if (!empty($booking['equipment_image']) && file_exists(__DIR__ . '/' . $img_path)): 
                    ?>
                        <img src="<?php echo htmlspecialchars($img_path); ?>" class="equipment-img" alt="Equipment">
                    <?php else: ?>
                        <div class="equipment-img d-flex align-items-center justify-content-center text-muted">
                            <i class="fa-solid fa-tractor fa-2x"></i>
                        </div>
                    <?php endif; ?>

                    <div>
                        <h5 class="fw-bold mb-1" style="font-size: 16px;"><?php echo htmlspecialchars($booking['equipment_title']); ?></h5>
                        <p class="text-muted mb-1" style="font-size: 12px;">
                            Category: <strong><?php echo htmlspecialchars($booking['equipment_category']); ?></strong>
                        </p>
                        <p class="text-muted mb-0" style="font-size: 12px;">
                            <i class="fa-solid fa-location-dot text-danger me-1"></i>
                            <?php echo htmlspecialchars($booking['service_location']); ?>
                        </p>
                    </div>
                </div>

                <div class="meta-grid">
                    <div class="meta-item">
                        <label>Booking ID</label>
                        <span><i class="fa-solid fa-barcode me-1 text-muted"></i> <?php echo htmlspecialchars($booking['request_code']); ?></span>
                    </div>
                    <div class="meta-item">
                        <label>Booking Date</label>
                        <span><i class="fa-solid fa-calendar me-1 text-muted"></i> <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <label>Lender Name</label>
                        <span><i class="fa-solid fa-user me-1 text-muted"></i> <?php echo htmlspecialchars($booking['lender_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <label>Phone Number</label>
                        <span><i class="fa-solid fa-phone me-1 text-muted"></i> <?php echo htmlspecialchars($booking['lender_phone']); ?></span>
                    </div>
                </div>

                <div class="info-pill d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-calendar-days me-1 text-muted"></i> <strong>Rental Period</strong><br>
                        <span class="text-muted"><?php echo date('d M Y', strtotime($booking['start_date'])); ?> – <?php echo date('d M Y', strtotime($booking['end_date'])); ?></span>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold"><?php echo $total_days; ?> Days</span>
                </div>

                <div class="info-pill">
                    <i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong>Delivery Address</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($booking['service_location']); ?></span>
                </div>
            </div>

            <!-- Booking Status Stepper -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title-custom mb-0">
                        <i class="fa-solid fa-bars-progress text-success"></i> Booking Status
                    </div>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 fw-bold">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>

                <?php if ($status === 'Cancelled'): ?>
                    <div class="stepper">
                        <div class="step completed">
                            <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                            <div class="step-label">Submitted</div>
                        </div>
                        <div class="step completed">
                            <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                            <div class="step-label">Pending Approval</div>
                        </div>
                        <div class="step active">
                            <div class="step-circle"><i class="fa-solid fa-xmark"></i></div>
                            <div class="step-label">Cancelled</div>
                        </div>
                    </div>
                <?php else: ?>
                <div class="stepper">
                    <div class="step <?php echo in_array($status, ['Pending', 'Accepted', 'Delivered', 'Returned', 'Completed']) ? 'completed' : ''; ?>">
                        <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                        <div class="step-label">Submitted</div>
                    </div>
                    <div class="step <?php echo in_array($status, ['Accepted', 'Delivered', 'Returned', 'Completed']) ? 'completed' : ''; ?>">
                        <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                        <div class="step-label">Pending Approval</div>
                    </div>
                    <div class="step <?php echo ($status === 'Accepted') ? 'active' : (in_array($status, ['Delivered', 'Returned', 'Completed']) ? 'completed' : ''); ?>">
                        <div class="step-circle">3</div>
                        <div class="step-label">Accepted</div>
                    </div>
                    <div class="step <?php echo ($status === 'Delivered') ? 'active' : (in_array($status, ['Returned', 'Completed']) ? 'completed' : ''); ?>">
                        <div class="step-circle">4</div>
                        <div class="step-label">Delivered</div>
                    </div>
                    <div class="step <?php echo ($status === 'Returned') ? 'active' : ($status === 'Completed' ? 'completed' : ''); ?>">
                        <div class="step-circle">5</div>
                        <div class="step-label">Returned</div>
                    </div>
                    <div class="step <?php echo ($status === 'Completed') ? 'completed' : ''; ?>">
                        <div class="step-circle">6</div>
                        <div class="step-label">Completed</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Rental Timeline -->
            <div class="content-card">
                <div class="card-title-custom">
                    <i class="fa-solid fa-timeline text-success"></i> Rental Timeline
                </div>

                <ul class="timeline-list">
                    <?php if ($status === 'Cancelled'): ?>
                        <li class="timeline-item active">
                            <h6>Request Submitted</h6>
                            <p>You requested to book this equipment. (<?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>)</p>
                        </li>
                        <li class="timeline-item active">
                            <h6>Booking Cancelled</h6>
                            <p>You cancelled this equipment booking before delivery.</p>
                        </li>
                    <?php else: ?>
                    <li class="timeline-item active">
                        <h6>Request Submitted</h6>
                        <p>You have requested to book this equipment. (<?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>)</p>
                    </li>
                    <li class="timeline-item <?php echo in_array($status, ['Accepted', 'Delivered', 'Returned', 'Completed']) ? 'active' : ''; ?>">
                        <h6>Lender Review & Approval</h6>
                        <p><?php echo in_array($status, ['Accepted', 'Delivered', 'Returned', 'Completed']) ? 'Lender has reviewed and accepted your request.' : 'Waiting for lender approval.'; ?></p>
                    </li>
                    <li class="timeline-item <?php echo in_array($status, ['Delivered', 'Returned', 'Completed']) ? 'active' : ''; ?>">
                        <h6>Equipment Delivery</h6>
                        <p><?php echo in_array($status, ['Delivered', 'Returned', 'Completed']) ? 'Equipment has been delivered.' : 'Pending delivery execution by the lender.'; ?></p>
                    </li>
                    <li class="timeline-item <?php echo in_array($status, ['Returned', 'Completed']) ? 'active' : ''; ?>">
                        <h6>Equipment Return</h6>
                        <p><?php echo in_array($status, ['Returned', 'Completed']) ? 'Equipment returned to lender.' : 'Return will be recorded after the lender collects the equipment.'; ?></p>
                    </li>
                    <li class="timeline-item <?php echo ($status === 'Completed') ? 'active' : ''; ?>">
                        <h6>Rental Completed</h6>
                        <p><?php echo ($status === 'Completed') ? 'Rental process successfully finished.' : 'Waiting for return confirmation.'; ?></p>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <!-- Right Column: Order Summary & Actions -->
        <div class="col-lg-4">
            <div class="content-card">
                <div class="card-title-custom">
                    <i class="fa-solid fa-receipt text-success"></i> Order Summary
                </div>

                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted">Price per Day</span>
                    <span class="fw-bold">₹<?php echo number_format((float)($booking['price_per_day'] ?? 0), 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted">Total Days</span>
                    <span class="fw-bold"><?php echo $total_days; ?> Days</span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted">Total Rent</span>
                    <span class="fw-bold text-success">₹<?php echo number_format((float)$booking['total_amount'], 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted">Advance Paid</span>
                    <span class="fw-bold">₹<?php echo number_format((float)$booking['advance_amount'], 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3" style="font-size: 14px;">
                    <span class="fw-bold">Remaining Amount (COD)</span>
                    <span class="fw-bold text-danger">₹<?php echo number_format((float)($booking['total_amount'] - $booking['advance_amount']), 2); ?></span>
                </div>

                <div class="mb-3">
                    <span class="text-muted" style="font-size: 12px;">Payment Method</span>
                    <div><span class="badge bg-light text-dark border">Cash on Delivery</span></div>
                </div>

                <button class="btn btn-light border w-100 fw-bold mb-2" style="font-size: 13px;">
                    <i class="fa-solid fa-user me-1"></i> View Lender Details
                </button>

                <a href="tel:<?php echo htmlspecialchars($booking['lender_phone']); ?>" class="btn btn-success w-100 fw-bold mb-2" style="font-size: 13px;">
                    <i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($booking['lender_phone']); ?>
                </a>

                <!-- Cancel Button Section: Only available BEFORE equipment delivery -->
                <?php if ($status === 'Pending' || $status === 'Accepted'): ?>
                    <form method="POST" action="booking_details.php?booking_id=<?php echo $booking_id; ?>&lang=<?php echo urlencode($current_lang); ?>" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <button type="submit" name="cancel_booking" value="1" class="btn-cancel-booking">
                            <i class="fa-solid fa-xmark me-1"></i> Cancel Booking
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>