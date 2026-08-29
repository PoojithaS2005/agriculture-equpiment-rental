<?php
session_start();
require_once 'includes/config.php'; // Adjust path if your config file is located elsewhere
require_once 'includes/lang.php'; // Multi-language file support // Multi-language file support

// 1. Session and Renter Validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

$renter_id = $_SESSION['user_id'];
$equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

if ($equipment_id <= 0) {
    header("Location: categories.php");
    exit();
}

// 2. Fetch Item Details from the `items` table
$sql = "
    SELECT 
        i.*, 
        u.full_name AS lender_name, 
        u.phone AS lender_phone,
        COALESCE(i.security_deposit, 0.00) AS fetched_security_deposit,
        COALESCE(i.min_rental_days, 1) AS fetched_min_days,
        COALESCE(i.max_rental_days, 30) AS fetched_max_days
    FROM items i 
    JOIN users u ON i.lender_id = u.user_id 
    WHERE i.item_id = ? AND i.status = 'Available'
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $equipment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$equipment = mysqli_fetch_assoc($result);

if (!$equipment) {
    echo "<script>alert('" . __('eq_not_found') . "'); window.location.href='categories.php';</script>";
    exit();
}

// Assign Dynamic Values from DB
$security_deposit = floatval($equipment['fetched_security_deposit']);
$price_per_day = floatval($equipment['price_per_day']);
$min_booking_days = max(1, intval($equipment['fetched_min_days']));
$max_booking_days = max($min_booking_days, intval($equipment['fetched_max_days']));

// Dynamic Image Resolution
$equipment_image_path = 'assets/img/default.png';
if (!empty($equipment['image'])) {
    if (file_exists('uploads/' . $equipment['image'])) {
        $equipment_image_path = 'uploads/' . $equipment['image'];
    } elseif (file_exists($equipment['image'])) {
        $equipment_image_path = $equipment['image'];
    }
}

// Fetch Renter Details
$stmt_user = mysqli_prepare($conn, "SELECT full_name, email, phone, address FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt_user, "i", $renter_id);
mysqli_stmt_execute($stmt_user);
$renter_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_user));
$renter_display_name = !empty($renter_user['full_name']) ? $renter_user['full_name'] : 'Renter';

$error_msg = "";
$success_msg = "";

// 3. Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    
    // Address handling
    $use_registered_address = isset($_POST['use_registered_address']) ? true : false;
    if ($use_registered_address) {
        $delivery_address = $renter_user['address'] ?? '';
    } else {
        $state = trim($_POST['address_state'] ?? '');
        $district = trim($_POST['address_district'] ?? '');
        $taluk = trim($_POST['address_taluk'] ?? '');
        $street = trim($_POST['address_full'] ?? '');
        $pincode = trim($_POST['address_pincode'] ?? '');
        
        $delivery_address = "State: $state, District: $district, Area/Taluk: $taluk, Address: $street, PIN: $pincode";
    }

    // Server-Side Validations
    $today = date('Y-m-d');
    if (empty($start_date) || empty($end_date) || empty($phone_number) || empty($id_number)) {
        $error_msg = __('err_all_fields');
    } elseif ($start_date < $today) {
        $error_msg = __('err_past_date');
    } elseif ($end_date < $start_date) {
        $error_msg = __('err_end_before_start');
    } elseif ($quantity < 1) {
        $error_msg = __('err_qty');
    } else {
        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($end_date);
        $interval = $datetime1->diff($datetime2);
        $total_days = $interval->days + 1;

        if ($total_days < $min_booking_days) {
            $error_msg = "Rental duration must be at least {$min_booking_days} days.";
        } elseif ($total_days > $max_booking_days) {
            $error_msg = "You cannot rent more than {$max_booking_days} days. The maximum limit is {$max_booking_days} days that lender specified.";
        } else {
            // Check Availability
            $check_sql = "
                SELECT COUNT(*) as count FROM bookings 
                WHERE equipment_id = ? AND status IN ('Pending', 'Accepted', 'Delivered') 
                AND NOT (end_date < ? OR start_date > ?)
            ";
            $check_avail = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_avail, "iss", $equipment_id, $start_date, $end_date);
            mysqli_stmt_execute($check_avail);
            $check_res = mysqli_fetch_assoc(mysqli_stmt_get_result($check_avail));

            if ($check_res['count'] > 0) {
                $error_msg = __('err_already_booked');
            } else {
                if (!isset($_FILES['id_proof_doc']) || $_FILES['id_proof_doc']['error'] === UPLOAD_ERR_NO_FILE) {
                    $error_msg = __('err_id_proof');
                } else {
                    $file = $_FILES['id_proof_doc'];
                    $max_size = 5 * 1024 * 1024; // 5 MB
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if ($file['size'] > $max_size) {
                        $error_msg = __('err_file_size');
                    } elseif (!in_array($mime_type, $allowed_types)) {
                        $error_msg = __('err_file_format');
                    } else {
                        $upload_dir = 'uploads/id_proofs/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $secure_filename = 'ID_' . $renter_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                        $destination = $upload_dir . $secure_filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $total_rent = $price_per_day * $total_days * $quantity;
                            $advance_amount = $security_deposit; 
                            $remaining_cod = max(0, $total_rent - $advance_amount);
                            $request_code = 'REQ-' . strtoupper(substr(uniqid(), -6));

                            $insert_sql = "
                                INSERT INTO bookings (request_code, renter_id, equipment_id, start_date, end_date, total_days, quantity, total_amount, advance_amount, remaining_cod, phone_number, id_number, id_proof_doc, delivery_address, status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
                            ";
                            $insert_stmt = mysqli_prepare($conn, $insert_sql);
                            mysqli_stmt_bind_param($insert_stmt, "siissiiiddssss", 
                                $request_code, $renter_id, $equipment_id, $start_date, $end_date, 
                                $total_days, $quantity, $total_rent, $advance_amount, $remaining_cod, 
                                $phone_number, $id_number, $secure_filename, $delivery_address
                            );
                            
                            if (mysqli_stmt_execute($insert_stmt)) {
                                echo "<script>alert('Booking request submitted successfully! Request Code: {$request_code}'); window.location.href='my_bookings.php';</script>";
                                exit();
                            } else {
                                $error_msg = __('err_db');
                            }
                        } else {
                            $error_msg = __('err_upload');
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><= __('book_equipment_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .top-navbar { height: 70px; background: #ffffff; border-bottom: 1px solid #dee2e6; position: fixed; top: 0; left: 0; right: 0; z-index: 1030; display: flex; align-items: center; justify-content: space-between; padding: 0 25px; }
        .brand-logo { color: #2e7d32; font-weight: 700; font-size: 1.2rem; text-decoration: none; display: flex; align-items: center; }
        .brand-logo small { font-size: 10px; color: #6c757d; letter-spacing: 0.5px; display: block; font-weight: 500; }
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #dee2e6; position: fixed; top: 70px; bottom: 0; left: 0; overflow-y: auto; padding: 20px 15px; z-index: 1020; }
        .sidebar a { padding: 10px 15px; display: flex; align-items: center; color: #333; text-decoration: none; font-weight: 500; border-radius: 6px; margin-bottom: 4px; transition: 0.2s; font-size: 14px; }
        .sidebar a i { width: 24px; color: #555; }
        .sidebar a:hover, .sidebar a.active { background-color: #e8f5e9; color: #2e7d32; }
        .sidebar a:hover i, .sidebar a.active i { color: #2e7d32; }
        .main-content { margin-left: 260px; margin-top: 70px; padding: 30px; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); background: #ffffff; }
        .upload-box { border: 2px dashed #ced4da; border-radius: 10px; padding: 20px; text-align: center; background: #fafafa; cursor: pointer; transition: 0.2s; }
        .upload-box:hover { border-color: #2e7d32; background: #f4fbf4; }
        .btn-agro { background-color: #2e7d32; color: #fff; border: none; font-weight: 600; padding: 12px; border-radius: 8px; }
        .btn-agro:hover { background-color: #1b5e20; color: #fff; }
        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>

    <!-- TOP NAVBAR (Search bar & Notifications removed, profile icon redirects to profile.php) -->
    <nav class="top-navbar">
        <a href="renter_dashboard.php" class="brand-logo">
            <i class="fa-solid fa-tractor fa-lg me-2 text-success"></i>
            <div>
                AGRICULTURE
                <small><= __('eq_rental_system'); ?></small>
            </div>
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-light btn-sm rounded-pill border dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-globe me-1 text-muted"></i> <?= ($_SESSION['lang'] ?? 'en') == 'kn' ? 'Kannada (ಕನ್ನಡ)' : 'English'; ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?lang=en">English</a></li>
                    <li><a class="dropdown-item" href="?lang=kn">Kannada (ಕನ್ನಡ)</a></li>
                </ul>
            </div>
            <a href="profile.php" class="d-flex align-items-center text-decoration-none border-start ps-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; font-weight: bold;">
                    <?= strtoupper(substr($renter_display_name, 0, 1)); ?>
                </div>
                <div>
                    <span class="fw-bold d-block lh-1 text-dark" style="font-size: 13px;"><?= htmlspecialchars($renter_display_name); ?></span>
                    <small class="text-muted" style="font-size: 11px;"><?= __('renter_role'); ?></small>
                </div>
            </a>
        </div>
    </nav>

    <!-- SIDEBAR ("Need Help" box removed) -->
    <div class="sidebar">
        <a href="renter_dashboard.php"><i class="fa-solid fa-gauge"></i> <?= __('nav_dashboard'); ?></a>
        <a href="categories.php"><i class="fa-solid fa-magnifying-glass"></i> <?= __('nav_search'); ?></a>
        <a href="categories.php" class="active"><i class="fa-solid fa-list"></i> <?= __('nav_categories'); ?></a>
        <a href="featured.php"><i class="fa-regular fa-star"></i> <?= __('nav_featured'); ?></a>
        <a href="recommended.php"><i class="fa-regular fa-thumbs-up"></i> <?= __('nav_recommended'); ?></a>
        <a href="my_bookings.php"><i class="fa-regular fa-calendar-check"></i> <?= __('nav_bookings'); ?></a>
        <a href="rental_history.php"><i class="fa-solid fa-clock-rotate-left"></i> <?= __('nav_history'); ?></a>
        <a href="profile.php"><i class="fa-regular fa-user"></i> <?= __('nav_profile'); ?></a>
        <a href="support.php"><i class="fa-regular fa-circle-question"></i> <?= __('nav_support'); ?></a>
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket text-danger"></i> <?= __('nav_logout'); ?></a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="text-muted small mb-2">
                <a href="renter_dashboard.php" class="text-decoration-none text-muted"><?= __('nav_dashboard'); ?></a> 
                <i class="fa-solid fa-chevron-right mx-1" style="font-size: 10px;"></i> 
                <span class="text-dark fw-semibold"><?= __('book_equipment_title'); ?></span>
            </div>

            <h2 class="mb-1 text-success fw-bold"><?= __('book_equipment_title'); ?></h2>
            <p class="text-muted mb-4" style="font-size: 14px;"><?= __('book_subtitle'); ?></p>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="bookingForm">
                <div class="row">
                    <!-- LEFT COLUMN -->
                    <div class="col-lg-8">
                        
                        <!-- Selected Equipment Preview Card ("Featured" badge removed) -->
                        <div class="card card-custom p-4 mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <img src="<?= htmlspecialchars($equipment_image_path); ?>" alt="<?= htmlspecialchars($equipment['title']); ?>" class="img-fluid rounded border" style="height: 140px; width: 100%; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($equipment['title']); ?></h5>
                                    </div>
                                    <p class="text-muted mb-2 small">
                                        <i class="fa-solid fa-tractor me-1 text-secondary"></i> <?= __('lbl_category'); ?>: <?= htmlspecialchars($equipment['category'] ?? 'Agriculture'); ?> &nbsp;|&nbsp; 
                                        <i class="fa-solid fa-user me-1 text-secondary"></i> <?= __('lbl_owner'); ?>: <?= htmlspecialchars($equipment['lender_name']); ?>
                                    </p>
                                    <p class="text-muted mb-2 small"><i class="fa-solid fa-location-dot me-1 text-secondary"></i> <?= htmlspecialchars($equipment['service_areas'] ?? 'Location Available'); ?></p>
                                    
                                    <h4 class="text-success fw-bold mb-0">₹<?= number_format($price_per_day, 2); ?> <span class="text-muted fw-normal" style="font-size: 13px;">/ <?= __('lbl_day'); ?></span></h4>
                                </div>
                            </div>
                        </div>

                        <!-- 1. RENTAL DETAILS -->
                        <div class="card card-custom p-4 mb-4">
                            <h5 class="fw-bold mb-3 text-success"><?= __('sec_rental_details'); ?></h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_start_date'); ?></label>
                                    <input type="date" class="form-control shadow-none" id="start_date" name="start_date" min="<?= date('Y-m-d'); ?>" required value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_end_date'); ?></label>
                                    <input type="date" class="form-control shadow-none" id="end_date" name="end_date" min="<?= date('Y-m-d'); ?>" required value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+' . ($min_booking_days - 1) . ' days'))); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_quantity'); ?></label>
                                    <select class="form-select shadow-none" id="quantity" name="quantity" onchange="calculateSummary()" oninput="calculateSummary()">
                                      
                                        <option value="1" selected>1 <?= __('lbl_unit'); ?></option>
                                        <option value="2">2 <?= __('lbl_units'); ?></option>
                                        <option value="3">3 <?= __('lbl_units'); ?></option>
                                        <option value="4">4 <?= __('lbl_units'); ?></option>
                                        <option value="5">5 <?= __('lbl_units'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_total_days'); ?></label>
                                    <input type="text" class="form-control bg-light fw-semibold text-dark shadow-none" id="display_total_days_input" value="1 Day" readonly>
                                </div>
                            </div>

                            <div id="duration_feedback" class="small mt-2 text-success">
                                <i class="fa-regular fa-circle-check me-1"></i> Total Rental Duration: <strong id="display_total_days_text">1 Day</strong> (Minimum required: <strong><?= $min_booking_days; ?> Days</strong>, Maximum allowed: <strong><?= $max_booking_days; ?> Days</strong>)
                            </div>
                        </div>

                        <!-- 2. DELIVERY ADDRESS -->
                        <div class="card card-custom p-4 mb-4">
                            <h5 class="fw-bold mb-3 text-success"><?= __('sec_delivery_address'); ?></h5>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="use_registered_address" name="use_registered_address" checked onchange="toggleAddressFields()">
                                <label class="form-check-label fw-semibold" for="use_registered_address" style="font-size: 14px;">
                                    <?= __('lbl_use_reg_address'); ?>
                                </label>
                            </div>

                            <div id="manual_address_wrapper" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_state'); ?></label>
                                        <input type="text" class="form-control shadow-none" name="address_state" placeholder="Karnataka" value="<?= htmlspecialchars($_POST['address_state'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_district'); ?></label>
                                        <input type="text" class="form-control shadow-none" name="address_district" placeholder="Bengaluru Rural" value="<?= htmlspecialchars($_POST['address_district'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_taluk'); ?></label>
                                        <input type="text" class="form-control shadow-none" name="address_taluk" placeholder="Devanahalli" value="<?= htmlspecialchars($_POST['address_taluk'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_pincode'); ?></label>
                                        <input type="text" class="form-control shadow-none" name="address_pincode" placeholder="562110" value="<?= htmlspecialchars($_POST['address_pincode'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_full_address'); ?></label>
                                    <textarea class="form-control shadow-none" name="address_full" rows="2" placeholder="Door No, Street name, Landmark"><?= htmlspecialchars($_POST['address_full'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div id="registered_address_display">
                                <div class="mb-2">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_full_address'); ?></label>
                                    <input type="text" class="form-control bg-light" value="<?= !empty($renter_user['address']) ? htmlspecialchars($renter_user['address']) : 'Registered address not set in profile'; ?>" readonly>
                                </div>
                            </div>

                            <div class="mt-2">
                                <button type="button" class="btn btn-link text-success p-0 text-decoration-none fw-semibold shadow-none" onclick="toggleAddressFieldsBtn()" id="toggle_address_btn" style="font-size: 13px;">
                                    + <?= __('btn_diff_address'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- 3. IDENTITY VERIFICATION -->
                        <div class="card card-custom p-4 mb-4">
                            <h5 class="fw-bold mb-3 text-success"><?= __('sec_identity_verification'); ?></h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_gov_id'); ?></label>
                                    <div class="upload-box" onclick="document.getElementById('id_proof_doc').click()">
                                        <i class="fa-solid fa-cloud-arrow-up text-success fa-2x mb-1"></i>
                                        <p class="mb-0 fw-semibold text-dark" style="font-size: 13px;"><?= __('lbl_click_upload'); ?></p>
                                        <small class="text-muted" style="font-size: 11px;"><?= __('lbl_file_specs'); ?></small>
                                        <input type="file" id="id_proof_doc" name="id_proof_doc" class="d-none" accept=".jpg,.jpeg,.png,.pdf" required onchange="updateFileName(this)">
                                        <div id="file_name_display" class="text-success small mt-1 fw-bold"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="mb-3">
                                        <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_id_number'); ?></label>
                                        <input type="text" class="form-control shadow-none" name="id_number" placeholder="Enter ID Number" value="<?= htmlspecialchars($_POST['id_number'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label text-muted" style="font-size: 12px;"><?= __('lbl_phone_number'); ?></label>
                                        <input type="text" class="form-control shadow-none" name="phone_number" placeholder="+91 98765 43210" value="<?= htmlspecialchars($_POST['phone_number'] ?? $renter_user['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. PAYMENT INFORMATION (Added explicit "Only cash payments" label) -->
                        <div class="card card-custom p-4 mb-4">
                            <h5 class="fw-bold mb-3 text-success"><?= __('sec_payment_info'); ?></h5>
                            <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                                <i class="fa-solid fa-circle-info me-1"></i> <strong><?= __('lbl_only_cash'); ?></strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa-solid fa-wallet text-success fa-xl me-2"></i>
                                            <span class="fw-bold text-dark" style="font-size: 13px;"><?= __('lbl_advance_payment'); ?></span>
                                        </div>
                                        <p class="text-muted mb-1" style="font-size: 12px;"><?= __('lbl_sec_deposit_lender'); ?></p>
                                        <h6 class="text-success fw-bold mb-1">₹<span id="payment_sec_dep_val"><?= number_format($security_deposit, 2); ?></span></h6>
                                        <span class="badge bg-success" style="font-size: 10px;"><?= __('lbl_cash_at_delivery'); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="p-3 bg-light rounded border h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa-solid fa-money-bill-transfer text-primary fa-xl me-2"></i>
                                            <span class="fw-bold text-dark" style="font-size: 13px;"><?= __('lbl_remaining_payment'); ?></span>
                                        </div>
                                        <p class="text-muted mb-1" style="font-size: 12px;"><?= __('lbl_total_minus_adv'); ?></p>
                                        <h6 class="text-primary fw-bold mb-1">₹<span id="payment_remaining_val">0.00</span></h6>
                                        <span class="badge bg-primary" style="font-size: 10px;"><?= __('lbl_cod'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms & Conditions Checkbox -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" checked required>
                            <label class="form-check-label text-muted" for="terms" style="font-size: 13px;">
                                <?= __('lbl_agree_terms'); ?>
                            </label>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Order Summary -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4 sticky-top" style="top: 90px;">
                            <h5 class="fw-bold mb-3 text-dark border-bottom pb-2"><?= __('lbl_order_summary'); ?></h5>
                            
                            <ul class="list-unstyled mb-3" style="font-size: 14px;">
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?= __('lbl_price_per_day'); ?></span>
                                    <span class="fw-semibold">₹<?= number_format($price_per_day, 2); ?></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?= __('lbl_total_days'); ?></span>
                                    <span class="fw-semibold" id="summary_rental_days">1 Day</span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?= __('lbl_quantity'); ?></span>
                                    <span class="fw-semibold" id="summary_quantity">1 Unit</span>
                                </li>
                                <hr class="my-2">
                                <li class="d-flex justify-content-between mb-2 fs-6">
                                    <span class="fw-bold text-dark"><?= __('lbl_total_rent'); ?></span>
                                    <span class="fw-bold text-success">₹<span id="summary_total_rent">0.00</span></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2 text-secondary" style="font-size: 13px;">
                                    <span><?= __('lbl_advance_dep'); ?></span>
                                    <span class="fw-semibold">₹<span id="summary_advance"><?= number_format($security_deposit, 2); ?></span></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3 bg-light p-2 rounded">
                                    <span class="fw-bold text-dark"><?= __('lbl_remaining_cod'); ?></span>
                                    <span class="fw-bold text-primary">₹<span id="summary_remaining">0.00</span></span>
                                </li>
                            </ul>

                            <hr class="my-2">

                            <button type="submit" id="submitBtn" class="btn btn-agro w-100 mb-2 shadow-none d-flex align-items-center justify-content-center gap-2">
                                <span><?= __('btn_confirm_booking'); ?></span> <i class="fa-solid fa-arrow-right"></i>
                            </button>
                            
                            <a href="categories.php" class="btn btn-outline-secondary w-100 shadow-none" style="font-size: 13px;">
                                <?= __('btn_cancel'); ?>
                            </a>

                            <div class="text-center text-muted mt-2" style="font-size: 12px;">
                                <i class="fa-solid fa-lock me-1"></i> <?= __('lbl_wont_be_charged'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Real-Time Calculations with fixed max-limit error message -->
    <!-- JavaScript Real-Time Calculations with fixed max-limit error message -->
    <!-- JavaScript Real-Time Calculations with fixed max-limit error message -->
<!-- JavaScript Real-Time Calculations -->
<script>
    // Ensure PHP variables load correctly into JS
    const pricePerDay = Number(<?= $price_per_day; ?>);
    const baseSecurityDeposit = Number(<?= $security_deposit; ?>);
    const minBookingDays = Number(<?= $min_booking_days; ?>);
    const maxBookingDays = Number(<?= $max_booking_days; ?>);

    function toggleAddressFieldsBtn() {
        const checkbox = document.getElementById('use_registered_address');
        checkbox.checked = !checkbox.checked;
        toggleAddressFields();
    }

    function toggleAddressFields() {
        const checkbox = document.getElementById('use_registered_address');
        const displayDiv = document.getElementById('registered_address_display');
        const manualDiv = document.getElementById('manual_address_wrapper');
        const toggleBtn = document.getElementById('toggle_address_btn');
        
        if (checkbox.checked) {
            displayDiv.style.display = 'block';
            manualDiv.style.display = 'none';
            toggleBtn.innerText = '+ <?= __('btn_diff_address'); ?>';
        } else {
            displayDiv.style.display = 'none';
            manualDiv.style.display = 'block';
            toggleBtn.innerText = '<?= __('lbl_use_reg_address'); ?>';
        }
    }

    function updateFileName(input) {
        const display = document.getElementById('file_name_display');
        if (input.files && input.files[0]) {
            display.innerText = "Selected: " + input.files[0].name;
        } else {
            display.innerText = "";
        }
    }

    function calculateSummary() {
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const qtySelect = document.getElementById('quantity');
        const submitBtn = document.getElementById('submitBtn');
        const feedbackDiv = document.getElementById('duration_feedback');

        if (!startInput || !endInput || !qtySelect) return;

        const startDateVal = startInput.value;
        const endDateVal = endInput.value;
        const qtyVal = parseInt(qtySelect.value) || 1;

        if (!startDateVal || !endDateVal) return;

        // Parse YYYY-MM-DD safely without timezone shifts
        const partsStart = startDateVal.split('-');
        const partsEnd = endDateVal.split('-');
        
        if (partsStart.length !== 3 || partsEnd.length !== 3) return;

        const start = new Date(partsStart[0], partsStart[1] - 1, partsStart[2]);
        const end = new Date(partsEnd[0], partsEnd[1] - 1, partsEnd[2]);
        
        const diffTime = end - start;
        let diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;

        if (isNaN(diffDays) || diffDays < 1) diffDays = 1;

        // 1. Update text displays for total days and quantity
        const dayText = diffDays + (diffDays === 1 ? ' Day' : ' Days');
        const unitText = qtyVal + (qtyVal === 1 ? ' Unit' : ' Units');

        document.getElementById('summary_rental_days').innerText = dayText;
        document.getElementById('display_total_days_input').value = dayText;
        document.getElementById('display_total_days_text').innerText = dayText;
        document.getElementById('summary_quantity').innerText = unitText;

        // 2. Validate Booking Limits
        if (diffDays < minBookingDays) {
            feedbackDiv.className = 'small mt-2 text-danger';
            feedbackDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i> Rental duration (${diffDays} days) is less than the minimum required limit of ${minBookingDays} days.`;
            if (submitBtn) submitBtn.disabled = true;
            return;
        } else if (diffDays > maxBookingDays) {
            feedbackDiv.className = 'small mt-2 text-danger';
            feedbackDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i> You cannot rent more than ${maxBookingDays} days.`;
            if (submitBtn) submitBtn.disabled = true;
            return;
        } else {
            feedbackDiv.className = 'small mt-2 text-success';
            feedbackDiv.innerHTML = `<i class="fa-regular fa-circle-check me-1"></i> Total Rental Duration: <strong>${diffDays} Days</strong> (Minimum: ${minBookingDays}, Maximum: ${maxBookingDays})`;
            if (submitBtn) submitBtn.disabled = false;
        }

        // 3. Perform the calculation: Price Per Day × Total Days × Quantity
        const totalRent = pricePerDay * diffDays * qtyVal;
        const totalSecurityDeposit = baseSecurityDeposit * qtyVal;
        
        let remainingAmount = totalRent - totalSecurityDeposit;
        if (remainingAmount < 0) remainingAmount = 0;

        // Format numbers cleanly for Indian currency locale
        const fmt = (num) => num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Update Order Summary Fields
        document.getElementById('summary_total_rent').innerText = fmt(totalRent);
        document.getElementById('summary_advance').innerText = fmt(totalSecurityDeposit);
        document.getElementById('summary_remaining').innerText = fmt(remainingAmount);
        
        // Update Payment Section Fields below
        const secDepValEl = document.getElementById('payment_sec_dep_val');
        const remainingValEl = document.getElementById('payment_remaining_val');
        
        if (secDepValEl) secDepValEl.innerText = fmt(totalSecurityDeposit);
        if (remainingValEl) remainingValEl.innerText = fmt(remainingAmount);
    }

    // Attach immediate listeners to inputs and dropdowns
    document.addEventListener("DOMContentLoaded", function() {
        const startEl = document.getElementById('start_date');
        const endEl = document.getElementById('end_date');
        const qtyEl = document.getElementById('quantity');

        if (startEl) {
            startEl.addEventListener('change', function() {
                endEl.min = this.value;
                calculateSummary();
            });
            startEl.addEventListener('input', calculateSummary);
        }
        if (endEl) {
            endEl.addEventListener('change', calculateSummary);
            endEl.addEventListener('input', calculateSummary);
        }
        if (qtyEl) {
            qtyEl.addEventListener('change', calculateSummary);
            qtyEl.addEventListener('input', calculateSummary);
        }

        // Run calculation immediately upon load
        calculateSummary();
    });

    // Fallback trigger
    window.onload = calculateSummary;
</script>
            </body>
</html>