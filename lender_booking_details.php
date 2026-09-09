<?php
session_start();

require_once 'includes/config.php';

/* =========================================================
   LANGUAGE
   ========================================================= */
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';

$current_lang = $_SESSION['lang'] ?? 'en';
$lang_param = '?lang=' . urlencode($current_lang);


/* =========================================================
   LOGIN + LENDER CHECK
   ========================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php" . $lang_param);
    exit();
}

$lender_id = intval($_SESSION['user_id']);


/* =========================================================
   BOOKING ID
   ========================================================= */
$booking_id = isset($_GET['booking_id'])
    ? intval($_GET['booking_id'])
    : 0;

if ($booking_id <= 0) {
    header("Location: active_rentals.php" . $lang_param);
    exit();
}


/* =========================================================
   CSRF TOKEN
   ========================================================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =========================================================
   HANDLE STATUS UPDATE
   ========================================================= */
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = __('lbd_invalid_request');
    } else {

        /*
         * Only these status changes are allowed:
         *
         * Pending  -> Accepted
         * Pending  -> Rejected
         * Accepted -> Delivered
         * Delivered -> Returned
         */

        $new_status = '';

        if ($action === 'accept') {
            $new_status = 'Accepted';
        } elseif ($action === 'reject') {
            $new_status = 'Rejected';
        } elseif ($action === 'delivered') {
            $new_status = 'Delivered';
        } elseif ($action === 'returned') {
            $new_status = 'Returned';
        }

        if ($new_status === '') {

            $error = __('lbd_invalid_action');

        } else {

            /*
             * IMPORTANT:
             * We verify that this equipment actually belongs
             * to the logged-in lender.
             */

            $update_sql = "
                UPDATE bookings b
                INNER JOIN equipment e
                    ON b.equipment_id = e.equipment_id
                SET b.status = ?
                WHERE b.booking_id = ?
                  AND e.lender_id = ?
            ";

            $update_stmt = $conn->prepare($update_sql);

            if ($update_stmt) {

                $update_stmt->bind_param(
                    "sii",
                    $new_status,
                    $booking_id,
                    $lender_id
                );

                if ($update_stmt->execute()) {

                    if ($update_stmt->affected_rows > 0) {

                        /*
                         * Update equipment availability
                         * when rental starts/ends.
                         */

                        if ($new_status === 'Accepted') {

                            $eq_stmt = $conn->prepare("
                                UPDATE equipment
                                SET status = 'Rented Out'
                                WHERE equipment_id = (
                                    SELECT equipment_id
                                    FROM bookings
                                    WHERE booking_id = ?
                                )
                                AND lender_id = ?
                            ");

                            if ($eq_stmt) {
                                $eq_stmt->bind_param(
                                    "ii",
                                    $booking_id,
                                    $lender_id
                                );
                                $eq_stmt->execute();
                                $eq_stmt->close();
                            }

                        } elseif ($new_status === 'Returned') {

                            $eq_stmt = $conn->prepare("
                                UPDATE equipment
                                SET status = 'Available'
                                WHERE equipment_id = (
                                    SELECT equipment_id
                                    FROM bookings
                                    WHERE booking_id = ?
                                )
                                AND lender_id = ?
                            ");

                            if ($eq_stmt) {
                                $eq_stmt->bind_param(
                                    "ii",
                                    $booking_id,
                                    $lender_id
                                );
                                $eq_stmt->execute();
                                $eq_stmt->close();
                            }
                        }

                        $message = __('lbd_status_updated');

                    } else {

                        $error = __('lbd_update_failed');
                    }
                } else {

                    $error = __('lbd_update_failed');
                }

                $update_stmt->close();

            } else {

                $error = __('lbd_database_error');
            }
        }
    }
}


/* =========================================================
   NOTIFICATION COUNT
   ========================================================= */
$notif_count = 0;

$notif_check = $conn->query(
    "SHOW TABLES LIKE 'notifications'"
);

if ($notif_check && $notif_check->num_rows > 0) {

    $n_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ?
          AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($n_stmt) {

        $n_stmt->bind_param("i", $lender_id);
        $n_stmt->execute();

        $notif_result = $n_stmt->get_result();

        if ($notif_result) {
            $notif_count = intval(
                $notif_result->fetch_assoc()['cnt'] ?? 0
            );
        }

        $n_stmt->close();
    }
}


/* =========================================================
   FETCH BOOKING
   =========================================================
   b.* is used so this page works with your current booking
   table even if you have extra columns such as:
   quantity, phone_number, id_number, delivery_address.
   ========================================================= */

$sql = "
    SELECT
        b.*,

        e.title AS equipment_title,
        e.category AS equipment_category,
        e.service_location,
        e.image AS equipment_image,
        e.price_per_day,
        e.brand_model,
        e.power_hp,
        e.drive_type,
        e.model_year,
        e.fuel_type,
        e.equipment_condition,

        u.user_id AS renter_user_id,
        u.full_name AS renter_name,
        u.phone AS renter_phone,
        u.email AS renter_email,
        u.address AS renter_address,
        u.profile_pic AS renter_profile_pic

    FROM bookings b

    INNER JOIN equipment e
        ON b.equipment_id = e.equipment_id

    INNER JOIN users u
        ON b.renter_id = u.user_id

    WHERE b.booking_id = ?
      AND e.lender_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param(
    "ii",
    $booking_id,
    $lender_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header(
        "Location: active_rentals.php" . $lang_param
    );

    exit();
}

$booking = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   VALUES
   ========================================================= */

$status = $booking['status'] ?? 'Pending';

$quantity = isset($booking['quantity'])
    ? intval($booking['quantity'])
    : 1;

$delivery_address = $booking['delivery_address']
    ?? $booking['renter_address']
    ?? 'N/A';

$phone_number = $booking['phone_number']
    ?? $booking['renter_phone']
    ?? 'N/A';

$id_number = $booking['id_number'] ?? '';

$total_amount = floatval(
    $booking['total_amount'] ?? 0
);

$advance_amount = floatval(
    $booking['advance_amount'] ?? 0
);

$remaining_cod = isset($booking['remaining_cod'])
    ? floatval($booking['remaining_cod'])
    : max(
        0,
        $total_amount - $advance_amount
    );


/* =========================================================
   STATUS STYLE
   ========================================================= */

$status_class = 'status-pending';
$status_icon = 'fa-clock';

if ($status === 'Accepted') {

    $status_class = 'status-accepted';
    $status_icon = 'fa-calendar-check';

} elseif ($status === 'Delivered') {

    $status_class = 'status-delivered';
    $status_icon = 'fa-truck';

} elseif ($status === 'Returned') {

    $status_class = 'status-returned';
    $status_icon = 'fa-circle-check';

} elseif ($status === 'Rejected') {

    $status_class = 'status-rejected';
    $status_icon = 'fa-circle-xmark';

} elseif ($status === 'Overdue') {

    $status_class = 'status-overdue';
    $status_icon = 'fa-triangle-exclamation';
}


/* =========================================================
   IMAGE
   ========================================================= */

$img_path = '';

if (!empty($booking['equipment_image'])) {

    if (
        file_exists(
            __DIR__ . '/uploads/' .
            $booking['equipment_image']
        )
    ) {

        $img_path =
            'uploads/' .
            $booking['equipment_image'];

    } elseif (
        file_exists(
            __DIR__ . '/' .
            $booking['equipment_image']
        )
    ) {

        $img_path =
            $booking['equipment_image'];
    }
}


/* =========================================================
   BACK PAGE
   ========================================================= */

if ($status === 'Pending') {

    $back_page = 'rental_request.php';

} elseif (
    $status === 'Accepted' ||
    $status === 'Delivered'
) {

    $back_page = 'active_rentals.php';

} else {

    $back_page = 'rental_history.php';
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo __('lbd_page_title'); ?>
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
            font-family:
                'Segoe UI',
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }

        body {
            background: #f4f6f9;
            color: #1e293b;
            margin: 0;
            font-weight: 500;
        }

        /* ================= SIDEBAR ================= */

        .sidebar {
            width: 260px;
            background: #fff;
            min-height: 100vh;
            padding: 20px;
            border-right: 1px solid #e0e0e0;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 28px;
            color: #198754;
        }

        .logo-text-main {
            display: block;
            font-size: 15px;
            font-weight: 900;
            color: #198754;
            letter-spacing: .3px;
        }

        .logo-text-sub {
            display: block;
            font-size: 10px;
            font-weight: 800;
            color: #198754;
            margin-top: 2px;
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 7px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            color: #334155;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            transition: .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #198754;
            color: #fff;
        }

        .nav-link-content {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .nav-link-content i {
            width: 20px;
            text-align: center;
        }

        .badge-count {
            background: #dc3545;
            color: #fff;
            border-radius: 50px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 900;
        }

        /* ================= MAIN ================= */

        .main-content {
            margin-left: 260px;
            padding: 20px 30px;
        }

        .top-bar {
            background: #fff;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 18px;
            margin-bottom: 20px;
        }

        .profile-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #334155;
            text-decoration: none;
        }

        .profile-btn:hover {
            background: #198754;
            color: #fff;
        }

        /* ================= BREADCRUMB ================= */

        .breadcrumb-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #64748b;
        }

        .breadcrumb-custom a {
            color: #198754;
            text-decoration: none;
            font-weight: 700;
        }

        /* ================= HEADER ================= */

        .page-header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-header {
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 4px;
            color: #0f172a;
        }

        .page-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
        }

        /* ================= GRID ================= */

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 370px;
            gap: 25px;
            align-items: start;
        }

        .content-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,.02);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .card-title i {
            color: #198754;
        }

        /* ================= EQUIPMENT ================= */

        .equipment-row {
            display: flex;
            gap: 20px;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .equipment-image {
            width: 145px;
            height: 105px;
            border-radius: 10px;
            overflow: hidden;
            background: #f1f5f9;
            flex-shrink: 0;
        }

        .equipment-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .equipment-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 30px;
        }

        .equipment-info h4 {
            font-size: 19px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .equipment-info p {
            margin: 3px 0;
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        /* ================= SPECIFICATIONS ================= */

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2,1fr);
            gap: 12px;
        }

        .spec-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 13px;
        }

        .spec-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .spec-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            word-break: break-word;
        }

        /* ================= RENTER ================= */

        .renter-box {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f8fafc;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        .renter-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #d1fae5;
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .renter-info h4 {
            margin: 0 0 3px;
            font-size: 17px;
            font-weight: 900;
        }

        .renter-info p {
            margin: 2px 0;
            color: #64748b;
            font-size: 13px;
        }

        .contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 8px;
            background: #198754;
            color: #fff;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
        }

        .contact-btn:hover {
            background: #157347;
            color: #fff;
        }

        /* ================= ADDRESS ================= */

        .address-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
        }

        .address-title {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .address-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        /* ================= STATUS ================= */

        .status-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .status-pending {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-accepted {
            background: #e0f2fe;
            color: #0284c7;
        }

        .status-delivered {
            background: #fef3c7;
            color: #d97706;
        }

        .status-returned {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-rejected,
        .status-overdue {
            background: #fee2e2;
            color: #dc2626;
        }

        /* ================= TIMELINE ================= */

        .progress-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0 10px;
        }

        .progress-timeline::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 30px;
            right: 30px;
            height: 3px;
            background: #e2e8f0;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto auto 8px;
            border: 3px solid #fff;
            font-size: 12px;
            font-weight: 900;
        }

        .timeline-step.completed .step-icon {
            background: #198754;
            color: #fff;
        }

        .timeline-step.current .step-icon {
            background: #0284c7;
            color: #fff;
        }

        .step-title {
            font-size: 11px;
            font-weight: 800;
        }

        /* ================= ACTIONS ================= */

        .action-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-accept {
            background: #198754;
            color: #fff;
        }

        .btn-reject {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delivered {
            background: #0284c7;
            color: #fff;
        }

        .btn-returned {
            background: #198754;
            color: #fff;
        }

        .btn-accept:hover,
        .btn-delivered:hover,
        .btn-returned:hover {
            opacity: .9;
        }

        .btn-reject:hover {
            background: #fecaca;
        }

        .completed-message {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 13px;
            border-radius: 10px;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
        }

        .alert-custom {
            border-radius: 10px;
            font-weight: 700;
        }

        /* ================= SUMMARY ================= */

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 13px;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
        }

        .summary-row .amount {
            color: #198754;
            font-weight: 900;
        }

        .summary-total {
            border-top: 1px dashed #cbd5e1;
            padding-top: 13px;
            margin-top: 13px;
            font-weight: 900;
            color: #0f172a;
        }

        /* ================= RESPONSIVE ================= */

        @media(max-width: 1100px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width: 768px) {

            .sidebar {
                width: 75px;
                padding: 15px 10px;
            }

            .logo-text-main,
            .logo-text-sub,
            .nav-link span {
                display: none;
            }

            .nav-link {
                justify-content: center;
            }

            .nav-link-content {
                justify-content: center;
            }

            .main-content {
                margin-left: 75px;
                padding: 15px;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .equipment-row {
                align-items: flex-start;
            }

        }

        @media(max-width: 500px) {

            .equipment-row {
                flex-direction: column;
            }

            .equipment-image {
                width: 100%;
                height: 160px;
            }

            .progress-timeline {
                overflow-x: auto;
            }

            .step-title {
                font-size: 9px;
            }
        }

    </style>

</head>

<body>


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<div class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-tractor"></i>

        <div>

            <span class="logo-text-main">
                AGRICULTURE
            </span>

            <span class="logo-text-sub">
                EQUIPMENT RENTAL
            </span>

        </div>

    </div>


    <ul class="nav-list">

        <li class="nav-item">

            <a
                href="lender_dashboard.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-chart-line"></i>

                    <span>
                        <?php echo __('dashboard'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="add_equipment.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-circle-plus"></i>

                    <span>
                        <?php echo __('add_equipment'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="my_equipment.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-list"></i>

                    <span>
                        <?php echo __('my_equipment'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="rental_request.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                    <span>
                        <?php echo __('rental_requests'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="active_rentals.php<?php echo $lang_param; ?>"
                class="nav-link active"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-truck-ramp-box"></i>

                    <span>
                        <?php echo __('active_rentals'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="rental_history.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                    <span>
                        <?php echo __('rental_history'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="reviews.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-star"></i>

                    <span>
                        <?php echo __('reviews'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="total_earnings.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-wallet"></i>

                    <span>
                        <?php echo __('total_earnings'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li class="nav-item">

            <a
                href="profile.php<?php echo $lang_param; ?>"
                class="nav-link"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-user"></i>

                    <span>
                        <?php echo __('my_profile'); ?>
                    </span>

                </span>

            </a>

        </li>


        <li
            class="nav-item"
            style="margin-top:25px;"
        >

            <a
                href="logout.php"
                class="nav-link"
                style="color:#dc2626;"
            >

                <span class="nav-link-content">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>
                        <?php echo __('logout'); ?>
                    </span>

                </span>

            </a>

        </li>

    </ul>

</div>


<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<div class="main-content">


    <!-- TOP BAR -->

    <div class="top-bar">

        <form
            action="lender_booking_details.php"
            method="GET"
            class="mb-0"
        >

            <input
                type="hidden"
                name="booking_id"
                value="<?php echo $booking_id; ?>"
            >

            <select
                name="lang"
                class="form-select form-select-sm fw-bold"
                onchange="this.form.submit()"
            >

                <option
                    value="en"
                    <?php
                    echo ($current_lang === 'en')
                        ? 'selected'
                        : '';
                    ?>
                >
                    English
                </option>

                <option
                    value="kn"
                    <?php
                    echo ($current_lang === 'kn')
                        ? 'selected'
                        : '';
                    ?>
                >
                    ಕನ್ನಡ (Kannada)
                </option>

                <option
                    value="hi"
                    <?php
                    echo ($current_lang === 'hi')
                        ? 'selected'
                        : '';
                    ?>
                >
                    हिंदी (Hindi)
                </option>

            </select>

        </form>


        <a
            href="notifications.php<?php echo $lang_param; ?>"
            class="position-relative text-dark"
        >

            <i class="fa-solid fa-bell fa-lg"></i>

            <?php if ($notif_count > 0): ?>

                <span
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                >
                    <?php echo $notif_count; ?>
                </span>

            <?php endif; ?>

        </a>


        <a
            href="profile.php<?php echo $lang_param; ?>"
            class="profile-btn"
        >

            <i class="fa-solid fa-user"></i>

        </a>

    </div>


    <!-- BREADCRUMB -->

    <div class="breadcrumb-custom">

        <a
            href="lender_dashboard.php<?php echo $lang_param; ?>"
        >
            <?php echo __('dashboard'); ?>
        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <a
            href="<?php echo $back_page . $lang_param; ?>"
        >
            <?php echo __('lbd_rentals'); ?>
        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <span>
            <?php echo __('lbd_booking_details'); ?>
        </span>

    </div>


    <!-- PAGE HEADER -->

    <div class="page-header-box">

        <div>

            <h1 class="page-header">

                <?php echo __('lbd_page_title'); ?>

            </h1>

            <p class="page-subtitle">

                <?php echo __('lbd_page_subtitle'); ?>

            </p>

        </div>


        <a
            href="<?php echo $back_page . $lang_param; ?>"
            class="btn btn-outline-success fw-bold"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            <?php echo __('lbd_back'); ?>

        </a>

    </div>


    <!-- ALERTS -->

    <?php if ($message): ?>

        <div class="alert alert-success alert-custom">

            <i class="fa-solid fa-circle-check me-2"></i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger alert-custom">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="details-grid">


        <!-- =================================================
             LEFT COLUMN
             ================================================= -->

        <div>


            <!-- EQUIPMENT + BOOKING INFORMATION -->

            <div class="content-card">

                <div class="card-title">

                    <i class="fa-solid fa-circle-info"></i>

                    <?php echo __('lbd_booking_information'); ?>

                </div>


                <div class="equipment-row">

                    <div class="equipment-image">

                        <?php if ($img_path): ?>

                            <img
                                src="<?php echo htmlspecialchars($img_path); ?>"
                                alt="Equipment"
                            >

                        <?php else: ?>

                            <div class="equipment-image-placeholder">

                                <i class="fa-solid fa-tractor"></i>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="equipment-info">

                        <h4>

                            <?php
                            echo htmlspecialchars(
                                $booking['equipment_title']
                            );
                            ?>

                        </h4>

                        <p>

                            <i class="fa-solid fa-layer-group me-1"></i>

                            <?php echo __('lbd_category'); ?>:

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $booking['equipment_category']
                                );
                                ?>

                            </strong>

                        </p>

                        <p>

                            <i class="fa-solid fa-location-dot me-1 text-danger"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['service_location']
                            );
                            ?>

                        </p>

                    </div>

                </div>


                <div class="specs-grid">


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_booking_id'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-barcode text-muted me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['request_code']
                                ?? ('#' . $booking['booking_id'])
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_booking_date'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-calendar-days text-muted me-1"></i>

                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime(
                                    $booking['created_at']
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_renter_name'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-user text-muted me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['renter_name']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_phone'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-phone text-muted me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $phone_number
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_quantity'); ?>

                        </div>

                        <div class="spec-value">

                            <?php echo $quantity; ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_status'); ?>

                        </div>

                        <div class="spec-value">

                            <span
                                class="status-badge <?php echo $status_class; ?>"
                            >

                                <i
                                    class="fa-solid <?php echo $status_icon; ?>"
                                ></i>

                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- RENTAL PERIOD -->

                <div
                    class="address-box"
                    style="margin-top:15px;"
                >

                    <div class="address-title">

                        <i class="fa-solid fa-calendar-week me-1"></i>

                        <?php echo __('lbd_rental_period'); ?>

                    </div>

                    <div class="address-value">

                        <?php
                        echo date(
                            'd M Y',
                            strtotime(
                                $booking['start_date']
                            )
                        );
                        ?>

                        &nbsp; – &nbsp;

                        <?php
                        echo date(
                            'd M Y',
                            strtotime(
                                $booking['end_date']
                            )
                        );
                        ?>

                        <span
                            class="badge bg-success ms-2"
                        >

                            <?php
                            echo intval(
                                $booking['total_days']
                            );
                            ?>

                            <?php echo __('lbd_days'); ?>

                        </span>

                    </div>

                </div>


                <!-- DELIVERY ADDRESS -->

                <div
                    class="address-box"
                    style="margin-top:12px;"
                >

                    <div class="address-title">

                        <i class="fa-solid fa-location-crosshairs me-1 text-danger"></i>

                        <?php echo __('lbd_delivery_address'); ?>

                    </div>

                    <div class="address-value">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $delivery_address
                            )
                        );
                        ?>

                    </div>

                </div>

            </div>


            <!-- RENTER DETAILS -->

            <div class="content-card">

                <div class="card-title">

                    <i class="fa-solid fa-user"></i>

                    <?php echo __('lbd_renter_information'); ?>

                </div>


                <div class="renter-box">

                    <div class="renter-avatar">

                        <i class="fa-solid fa-user"></i>

                    </div>


                    <div class="renter-info">

                        <h4>

                            <?php
                            echo htmlspecialchars(
                                $booking['renter_name']
                            );
                            ?>

                        </h4>

                        <p>

                            <i class="fa-solid fa-envelope me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['renter_email']
                            );
                            ?>

                        </p>

                        <p>

                            <i class="fa-solid fa-phone me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $phone_number
                            );
                            ?>

                        </p>


                        <a
                            href="tel:<?php echo htmlspecialchars($phone_number); ?>"
                            class="contact-btn"
                        >

                            <i class="fa-solid fa-phone"></i>

                            <?php echo __('lbd_contact_renter'); ?>

                        </a>

                    </div>

                </div>


                <?php if ($id_number): ?>

                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_id_number'); ?>

                        </div>

                        <div class="spec-value">

                            <?php
                            echo htmlspecialchars(
                                $id_number
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>

            </div>


            <!-- BOOKING STATUS -->

            <div class="content-card">

                <div class="status-card-header">

                    <div class="card-title mb-0">

                        <i class="fa-solid fa-bars-progress"></i>

                        <?php echo __('lbd_booking_status'); ?>

                    </div>


                    <span
                        class="status-badge <?php echo $status_class; ?>"
                    >

                        <i
                            class="fa-solid <?php echo $status_icon; ?>"
                        ></i>

                        <?php echo htmlspecialchars($status); ?>

                    </span>

                </div>


                <?php

                $pending =
                    ($status === 'Pending');

                $accepted =
                    ($status === 'Accepted');

                $delivered =
                    ($status === 'Delivered');

                $returned =
                    ($status === 'Returned');

                ?>


                <div class="progress-timeline">


                    <div
                        class="timeline-step completed"
                    >

                        <div class="step-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_submitted'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo (!$pending)
                            ? 'completed'
                            : 'current';
                        ?>"
                    >

                        <div class="step-icon">

                            <?php if (!$pending): ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                2

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_pending_approval'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo (
                            $delivered ||
                            $returned
                        )
                            ? 'completed'
                            : (
                                $accepted
                                    ? 'current'
                                    : ''
                            );
                        ?>"
                    >

                        <div class="step-icon">

                            <?php
                            if (
                                $delivered ||
                                $returned
                            ):
                            ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                3

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_accepted'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo $returned
                            ? 'completed'
                            : (
                                $delivered
                                    ? 'current'
                                    : ''
                            );
                        ?>"
                    >

                        <div class="step-icon">

                            <?php if ($returned): ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                4

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_delivered'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo $returned
                            ? 'completed'
                            : '';
                        ?>"
                    >

                        <div class="step-icon">

                            <?php if ($returned): ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                5

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_returned'); ?>

                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             RIGHT COLUMN
             ================================================= -->

        <div>


            <!-- ORDER SUMMARY -->

            <div
                class="content-card"
                style="position:sticky;top:20px;"
            >

                <div class="card-title">

                    <i class="fa-solid fa-receipt"></i>

                    <?php echo __('lbd_order_summary'); ?>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_price_per_day'); ?>
                    </span>

                    <strong>
                        ₹<?php
                        echo number_format(
                            $booking['price_per_day'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_total_days'); ?>
                    </span>

                    <strong>
                        <?php
                        echo intval(
                            $booking['total_days']
                        );
                        ?>
                        <?php echo __('lbd_days'); ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_quantity'); ?>
                    </span>

                    <strong>
                        <?php echo $quantity; ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_total_rent'); ?>
                    </span>

                    <span class="amount">

                        ₹<?php
                        echo number_format(
                            $total_amount,
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_advance'); ?>
                    </span>

                    <strong>

                        ₹<?php
                        echo number_format(
                            $advance_amount,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_remaining'); ?>
                    </span>

                    <strong class="text-danger">

                        ₹<?php
                        echo number_format(
                            $remaining_cod,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <div class="summary-row summary-total">

                    <span>
                        <?php echo __('lbd_payment_method'); ?>
                    </span>

                    <span class="badge bg-light text-dark border">

                        <?php echo __('lbd_cash'); ?>

                    </span>

                </div>


                <!-- =========================================
                     LENDER ACTIONS
                     ========================================= -->

                <div
                    style="
                    margin-top:22px;
                    border-top:1px solid #e2e8f0;
                    padding-top:20px;
                    "
                >

                    <div class="card-title">

                        <i class="fa-solid fa-hand-pointer"></i>

                        <?php echo __('lbd_actions'); ?>

                    </div>


                    <div class="action-box">


                        <!-- PENDING -->

                        <?php if ($status === 'Pending'): ?>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_accept')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="accept"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-accept"
                                >

                                    <i class="fa-solid fa-check"></i>

                                    <?php echo __('lbd_accept'); ?>

                                </button>

                            </form>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_reject')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="reject"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-reject"
                                >

                                    <i class="fa-solid fa-xmark"></i>

                                    <?php echo __('lbd_reject'); ?>

                                </button>

                            </form>


                        <!-- ACCEPTED -->

                        <?php elseif ($status === 'Accepted'): ?>


                            <div
                                class="alert alert-info mb-0"
                                style="font-size:13px;"
                            >

                                <i class="fa-solid fa-truck me-1"></i>

                                <?php
                                echo __('lbd_delivery_instruction');
                                ?>

                            </div>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_delivered')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delivered"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-delivered"
                                >

                                    <i class="fa-solid fa-truck"></i>

                                    <?php echo __('lbd_mark_delivered'); ?>

                                </button>

                            </form>


                        <!-- DELIVERED -->

                        <?php elseif ($status === 'Delivered'): ?>


                            <div
                                class="alert alert-warning mb-0"
                                style="font-size:13px;"
                            >

                                <i class="fa-solid fa-rotate-left me-1"></i>

                                <?php
                                echo __('lbd_return_instruction');
                                ?>

                            </div>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_returned')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="returned"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-returned"
                                >

                                    <i class="fa-solid fa-rotate-left"></i>

                                    <?php echo __('lbd_mark_returned'); ?>

                                </button>

                            </form>


                        <!-- RETURNED -->

                        <?php elseif ($status === 'Returned'): ?>


                            <div class="completed-message">

                                <i
                                    class="fa-solid fa-circle-check me-1"
                                ></i>

                                <?php
                                echo __('lbd_completed');
                                ?>

                            </div>


                        <!-- REJECTED -->

                        <?php elseif ($status === 'Rejected'): ?>


                            <div
                                class="completed-message"
                                style="
                                background:#fee2e2;
                                color:#991b1b;
                                border-color:#fecaca;
                                "
                            >

                                <i
                                    class="fa-solid fa-circle-xmark me-1"
                                ></i>

                                <?php
                                echo __('lbd_rejected_message');
                                ?>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>