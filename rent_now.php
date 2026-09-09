<?php
session_start();

/* =========================================================
   LANGUAGE PERSISTENCE
   Keep the language selected in the renter/lender dashboard
   across all pages, including rent_now.php.
   Supported languages: English, Kannada, Hindi.
   ========================================================= */
$supported_languages = ['en', 'kn', 'hi'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_languages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], $supported_languages, true)) {
    $_SESSION['lang'] = 'en';
}

require_once 'includes/config.php';
require_once 'includes/lang.php';

/* =========================================================
   1. RENTER VALIDATION
   ========================================================= */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'renter') {
    header("Location: login.php");
    exit();
}

$renter_id = intval($_SESSION['user_id']);
$equipment_id = 0;
if (isset($_GET['equipment_id'])) {
    $equipment_id = intval($_GET['equipment_id']);
} elseif (isset($_GET['id'])) {
    $equipment_id = intval($_GET['id']);
}

if ($equipment_id <= 0) {
    header("Location: categories.php");
    exit();
}
/* =========================================================
   2. FETCH EQUIPMENT DETAILS
   =========================================================
   IMPORTANT:
   - Security deposit comes from database.
   - Minimum and maximum rental days come from database.
   - Image comes from database.
   ========================================================= */

$sql = "
    SELECT 
        i.*,
        u.full_name AS lender_name,
        u.phone AS lender_phone
    FROM items i
    INNER JOIN users u ON i.lender_id = u.user_id
    WHERE i.item_id = ?
      AND i.status = 'Available'
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $equipment_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$equipment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$equipment) {
    echo "<script>
        alert('Equipment not found or is not available.');
        window.location.href='categories.php';
    </script>";
    exit();
}

/* =========================================================
   3. GET DATABASE VALUES
   ========================================================= */

$price_per_day = isset($equipment['price_per_day'])
    ? floatval($equipment['price_per_day'])
    : 0;

/*
 * Security deposit MUST come from database.
 * No hard-coded amount.
 */
$security_deposit = isset($equipment['security_deposit'])
    ? floatval($equipment['security_deposit'])
    : 0;

/*
 * Support the existing column names used in your database.
 */
if (isset($equipment['min_rental_days'])) {
    $min_booking_days = intval($equipment['min_rental_days']);
} elseif (isset($equipment['min_booking_days'])) {
    $min_booking_days = intval($equipment['min_booking_days']);
} else {
    $min_booking_days = 1;
}

if (isset($equipment['max_rental_days'])) {
    $max_booking_days = intval($equipment['max_rental_days']);
} elseif (isset($equipment['max_booking_days'])) {
    $max_booking_days = intval($equipment['max_booking_days']);
} elseif (isset($equipment['max_booking_days'])) {
    $max_booking_days = intval($equipment['max_booking_days']);
} else {
    $max_booking_days = 30;
}

$min_booking_days = max(1, $min_booking_days);
$max_booking_days = max($min_booking_days, $max_booking_days);

/* =========================================================
   4. EQUIPMENT IMAGE
   =========================================================
   Image is dynamically taken from database.
   No hard-coded equipment image.
   ========================================================= */

$equipment_image_path = 'assets/img/default.png';

if (!empty($equipment['image'])) {

    $database_image = trim($equipment['image']);

    if (file_exists('uploads/' . $database_image)) {
        $equipment_image_path = 'uploads/' . $database_image;
    } elseif (file_exists($database_image)) {
        $equipment_image_path = $database_image;
    } elseif (file_exists('assets/img/' . $database_image)) {
        $equipment_image_path = 'assets/img/' . $database_image;
    }
}

/* =========================================================
   5. FETCH RENTER DETAILS
   ========================================================= */

$user_sql = "
    SELECT full_name, email, phone, address
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$stmt_user = mysqli_prepare($conn, $user_sql);

mysqli_stmt_bind_param($stmt_user, "i", $renter_id);
mysqli_stmt_execute($stmt_user);

$user_result = mysqli_stmt_get_result($stmt_user);
$renter_user = mysqli_fetch_assoc($user_result);

mysqli_stmt_close($stmt_user);

$renter_display_name = !empty($renter_user['full_name'])
    ? $renter_user['full_name']
    : 'Renter';

$error_msg = "";

/* =========================================================
   6. FORM SUBMISSION
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    $quantity = intval($_POST['quantity'] ?? 1);

    $phone_number = trim($_POST['phone_number'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');

    /* -----------------------------------------------------
       ADDRESS
       ----------------------------------------------------- */

    $use_registered_address =
        isset($_POST['use_registered_address']);

    if ($use_registered_address) {

        $delivery_address = trim(
            $renter_user['address'] ?? ''
        );

        if ($delivery_address === '') {
            $error_msg = "Your registered address is not available. Please enter a delivery address.";
        }

    } else {

        $state = trim($_POST['address_state'] ?? '');
        $district = trim($_POST['address_district'] ?? '');
        $taluk = trim($_POST['address_taluk'] ?? '');
        $street = trim($_POST['address_full'] ?? '');
        $pincode = trim($_POST['address_pincode'] ?? '');

        if (
            $state === '' ||
            $district === '' ||
            $taluk === '' ||
            $street === '' ||
            $pincode === ''
        ) {
            $error_msg = "Please complete all delivery address fields.";
        } else {

            $delivery_address =
                "State: " . $state .
                ", District: " . $district .
                ", Area/Taluk: " . $taluk .
                ", Address: " . $street .
                ", PIN: " . $pincode;
        }
    }

    /* -----------------------------------------------------
       BASIC VALIDATION
       ----------------------------------------------------- */

    $today = date('Y-m-d');

    if ($error_msg === '') {

        if (
            empty($start_date) ||
            empty($end_date) ||
            empty($phone_number) ||
            empty($id_number)
        ) {

            $error_msg = "Please fill all required fields.";

        } elseif ($start_date < $today) {

            $error_msg = "Rental start date cannot be in the past.";

        } elseif ($end_date < $start_date) {

            $error_msg = "End date cannot be before start date.";

        } elseif ($quantity < 1) {

            $error_msg = "Quantity must be at least 1.";

        } else {

            /* -------------------------------------------------
               CALCULATE TOTAL DAYS
               ------------------------------------------------- */

            try {

                $datetime1 = new DateTime($start_date);
                $datetime2 = new DateTime($end_date);

                $interval = $datetime1->diff($datetime2);

                /*
                 * Inclusive rental calculation.
                 *
                 * Example:
                 * Aug 30 → Aug 30 = 1 day
                 * Aug 30 → Aug 31 = 2 days
                 */
                $total_days = $interval->days + 1;

            } catch (Exception $e) {

                $total_days = 0;
            }

            /* -------------------------------------------------
               MIN/MAX VALIDATION
               ------------------------------------------------- */

            if ($total_days < $min_booking_days) {

                $error_msg =
                    "Rental duration must be at least " .
                    $min_booking_days .
                    " days.";

            } elseif ($total_days > $max_booking_days) {

                $error_msg =
                    "Rental duration cannot exceed " .
                    $max_booking_days .
                    " days.";

            } else {

                /* ---------------------------------------------
                   CHECK EQUIPMENT AVAILABILITY
                   --------------------------------------------- */

                $check_sql = "
                    SELECT COUNT(*) AS booking_count
                    FROM bookings
                    WHERE equipment_id = ?
                      AND status IN ('Pending', 'Accepted', 'Delivered')
                      AND NOT (
                          end_date < ?
                          OR start_date > ?
                      )
                ";

                $check_avail = mysqli_prepare($conn, $check_sql);

                mysqli_stmt_bind_param(
                    $check_avail,
                    "iss",
                    $equipment_id,
                    $start_date,
                    $end_date
                );

                mysqli_stmt_execute($check_avail);

                $check_result =
                    mysqli_stmt_get_result($check_avail);

                $check_data =
                    mysqli_fetch_assoc($check_result);

                mysqli_stmt_close($check_avail);

                if (
                    intval($check_data['booking_count'] ?? 0) > 0
                ) {

                    $error_msg =
                        "This equipment is already booked for the selected dates.";

                } else {

                    /* -----------------------------------------
                       ID DOCUMENT VALIDATION
                       ----------------------------------------- */

                    if (
                        !isset($_FILES['id_proof_doc']) ||
                        $_FILES['id_proof_doc']['error'] === UPLOAD_ERR_NO_FILE
                    ) {

                        $error_msg =
                            "Please upload your identity proof.";

                    } else {

                        $file = $_FILES['id_proof_doc'];

                        $max_size = 5 * 1024 * 1024;

                        $allowed_types = [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'application/pdf'
                        ];

                        $finfo =
                            finfo_open(FILEINFO_MIME_TYPE);

                        $mime_type =
                            finfo_file(
                                $finfo,
                                $file['tmp_name']
                            );

                        finfo_close($finfo);

                        if ($file['size'] > $max_size) {

                            $error_msg =
                                "Identity document must be 5 MB or smaller.";

                        } elseif (
                            !in_array(
                                $mime_type,
                                $allowed_types,
                                true
                            )
                        ) {

                            $error_msg =
                                "Invalid identity document format.";

                        } else {

                            /* ---------------------------------
                               SECURE FILE UPLOAD
                               --------------------------------- */

                            $upload_dir =
                                'uploads/id_proofs/';

                            if (!is_dir($upload_dir)) {

                                mkdir(
                                    $upload_dir,
                                    0755,
                                    true
                                );
                            }

                            $file_ext =
                                strtolower(
                                    pathinfo(
                                        $file['name'],
                                        PATHINFO_EXTENSION
                                    )
                                );

                            $secure_filename =
                                'ID_' .
                                $renter_id .
                                '_' .
                                time() .
                                '_' .
                                bin2hex(random_bytes(4)) .
                                '.' .
                                $file_ext;

                            $destination =
                                $upload_dir .
                                $secure_filename;

                            if (
                                move_uploaded_file(
                                    $file['tmp_name'],
                                    $destination
                                )
                            ) {

                                /* ---------------------------------
                                   FINAL SERVER-SIDE CALCULATION
                                   --------------------------------- */

                                /*
                                 * Total Rent:
                                 *
                                 * Price × Days × Quantity
                                 */
                                $total_rent =
                                    $price_per_day *
                                    $total_days *
                                    $quantity;

                                /*
                                 * IMPORTANT:
                                 *
                                 * Security deposit is the amount
                                 * entered by the lender.
                                 *
                                 * DO NOT multiply it by quantity.
                                 */
                                $advance_amount =
                                    $security_deposit;

                                /*
                                 * Remaining amount:
                                 *
                                 * Total Rent - Security Deposit
                                 *
                                 * Never allow negative value.
                                 */
                                $remaining_cod =
                                    max(
                                        0,
                                        $total_rent -
                                        $advance_amount
                                    );

                                $request_code =
                                    'REQ-' .
                                    strtoupper(
                                        substr(
                                            uniqid(),
                                            -6
                                        )
                                    );

                                /* ---------------------------------
                                   INSERT BOOKING
                                   --------------------------------- */

                                $insert_sql = "
                                    INSERT INTO bookings
                                    (
                                        request_code,
                                        renter_id,
                                        equipment_id,
                                        start_date,
                                        end_date,
                                        total_days,
                                        quantity,
                                        total_amount,
                                        advance_amount,
                                        remaining_cod,
                                        phone_number,
                                        id_number,
                                        id_proof_doc,
                                        delivery_address,
                                        status,
                                        created_at
                                    )
                                    VALUES
                                    (
                                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                        ?, ?, ?, ?, 'Pending', NOW()
                                    )
                                ";

                                $insert_stmt =
                                    mysqli_prepare(
                                        $conn,
                                        $insert_sql
                                    );

                                if (!$insert_stmt) {

                                    $error_msg =
                                        "Database error while preparing booking.";

                                } else {

                                    /*
                                     * Correct parameter types:
                                     *
                                     * s = request code
                                     * i = renter ID
                                     * i = equipment ID
                                     * s = start date
                                     * s = end date
                                     * i = total days
                                     * i = quantity
                                     * d = total rent
                                     * d = advance
                                     * d = remaining
                                     * s = phone
                                     * s = ID number
                                     * s = document
                                     * s = address
                                     */

                                    mysqli_stmt_bind_param(
                                          $insert_stmt,
                                          "siissiidddssss",
                                           $request_code,
                                           $renter_id,
                                           $equipment_id,
                                           $start_date,
                                           $end_date,
                                           $total_days,
                                           $quantity,
                                           $total_rent,
                                           $advance_amount,
                                           $remaining_cod,
                                           $phone_number,
                                           $id_number,
                                           $secure_filename,
                                           $delivery_address
                                             );

                                    /*
                                     * Remove accidental space from
                                     * bind string before execution.
                                     */
                                    mysqli_stmt_close(
                                        $insert_stmt
                                    );

                                    /*
                                     * Prepare again with exact
                                     * parameter type string.
                                     */
                                    $insert_stmt =
                                        mysqli_prepare(
                                            $conn,
                                            $insert_sql
                                        );

                                    mysqli_stmt_bind_param(
                                        $insert_stmt,
                                        "siissiidddssss",
                                        $request_code,
                                        $renter_id,
                                        $equipment_id,
                                        $start_date,
                                        $end_date,
                                        $total_days,
                                        $quantity,
                                        $total_rent,
                                        $advance_amount,
                                        $remaining_cod,
                                        $phone_number,
                                        $id_number,
                                        $secure_filename,
                                        $delivery_address
                                    );

                                    /*
                                     * mysqli accepts no spaces in
                                     * type string, therefore use the
                                     * corrected exact string below.
                                     */
                                    mysqli_stmt_close(
                                        $insert_stmt
                                    );

                                    $insert_stmt =
                                        mysqli_prepare(
                                            $conn,
                                            $insert_sql
                                        );

                                    mysqli_stmt_bind_param(
                                        $insert_stmt,
                                        "siissiidddssss",
                                        $request_code,
                                        $renter_id,
                                        $equipment_id,
                                        $start_date,
                                        $end_date,
                                        $total_days,
                                        $quantity,
                                        $total_rent,
                                        $advance_amount,
                                        $remaining_cod,
                                        $phone_number,
                                        $id_number,
                                        $secure_filename,
                                        $delivery_address
                                    );

                                    if (
                                        mysqli_stmt_execute(
                                            $insert_stmt
                                        )
                                    ) {

                                        mysqli_stmt_close(
                                            $insert_stmt
                                        );

                                        echo "<script>
                                            alert('Booking request submitted successfully! Request Code: " .
                                            htmlspecialchars(
                                                $request_code,
                                                ENT_QUOTES
                                            ) .
                                            "');
                                            window.location.href='my_bookings.php';
                                        </script>";

                                        exit();

                                    } else {

                                        $error_msg =
                                            "Booking could not be saved: " .
                                            mysqli_stmt_error(
                                                $insert_stmt
                                            );

                                        mysqli_stmt_close(
                                            $insert_stmt
                                        );
                                    }
                                }

                            } else {

                                $error_msg =
                                    "Failed to upload identity document.";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= __('book_equipment_title'); ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .top-navbar {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid #dee2e6;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
        }

        .brand-logo {
            color: #2e7d32;
            font-weight: 700;
            font-size: 1.2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .brand-logo small {
            font-size: 10px;
            color: #6c757d;
            letter-spacing: 0.5px;
            display: block;
            font-weight: 500;
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #dee2e6;
            position: fixed;
            top: 70px;
            bottom: 0;
            left: 0;
            overflow-y: auto;
            padding: 20px 15px;
            z-index: 1020;
        }

        .sidebar a {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            border-radius: 6px;
            margin-bottom: 4px;
            transition: 0.2s;
            font-size: 14px;
        }

        .sidebar a i {
            width: 24px;
            color: #555;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .sidebar a:hover i,
        .sidebar a.active i {
            color: #2e7d32;
        }

        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            padding: 30px;
        }

        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            background: #ffffff;
        }

        .upload-box {
            border: 2px dashed #ced4da;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: 0.2s;
        }

        .upload-box:hover {
            border-color: #2e7d32;
            background: #f4fbf4;
        }

        .btn-agro {
            background-color: #2e7d32;
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
        }

        .btn-agro:hover {
            background-color: #1b5e20;
            color: #fff;
        }

        .btn-agro:disabled {
            background-color: #9e9e9e;
            cursor: not-allowed;
        }

        .payment-box {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            background: #f8f9fa;
            padding: 15px;
        }

        .security-note {
            font-size: 12px;
            color: #6c757d;
        }

        @media (max-width: 992px) {

            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }

    </style>

</head>

<body>

<!-- =====================================================
     TOP NAVBAR
     ===================================================== -->

<nav class="top-navbar">

    <a
        href="renter_dashboard.php"
        class="brand-logo"
    >

        <i class="fa-solid fa-tractor fa-lg me-2 text-success"></i>

        <div>
            AGRICULTURE

            <small>
                <?= __('eq_rental_system'); ?>
            </small>
        </div>

    </a>

    <div class="d-flex align-items-center gap-3">

        <div class="dropdown">

            <button
                class="btn btn-light btn-sm rounded-pill border dropdown-toggle px-3"
                type="button"
                data-bs-toggle="dropdown"
            >

                <i class="fa-solid fa-globe me-1 text-muted"></i>

                <?= ($_SESSION['lang'] ?? 'en') == 'kn'
                    ? 'Kannada (ಕನ್ನಡ)'
                    : 'English'; ?>

            </button>

            <ul class="dropdown-menu">
                <li>
                    <a
                        class="dropdown-item <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'active' : ''; ?>"
                        href="rent_now.php?equipment_id=<?= (int)$equipment_id; ?>&lang=en"
                    >
                        English
                    </a>
                </li>

                <li>
                    <a
                        class="dropdown-item <?= ($_SESSION['lang'] ?? 'en') === 'kn' ? 'active' : ''; ?>"
                        href="rent_now.php?equipment_id=<?= (int)$equipment_id; ?>&lang=kn"
                    >
                        Kannada (ಕನ್ನಡ)
                    </a>
                </li>

                <li>
                    <a
                        class="dropdown-item <?= ($_SESSION['lang'] ?? 'en') === 'hi' ? 'active' : ''; ?>"
                        href="rent_now.php?equipment_id=<?= (int)$equipment_id; ?>&lang=hi"
                    >
                        Hindi (हिन्दी)
                    </a>
                </li>
            </ul>

        </div>

        <a
            href="profile.php"
            class="d-flex align-items-center text-decoration-none border-start ps-3"
        >

            <div
                class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                style="width:36px;height:36px;font-weight:bold;"
            >
                <?= strtoupper(
                    substr(
                        $renter_display_name,
                        0,
                        1
                    )
                ); ?>
            </div>

            <div>

                <span
                    class="fw-bold d-block lh-1 text-dark"
                    style="font-size:13px;"
                >
                    <?= htmlspecialchars(
                        $renter_display_name
                    ); ?>
                </span>

                <small
                    class="text-muted"
                    style="font-size:11px;"
                >
                    <?= __('renter_role'); ?>
                </small>

            </div>

        </a>

    </div>

</nav>


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<div class="sidebar">

    <a href="renter_dashboard.php">
        <i class="fa-solid fa-gauge"></i>
        <?= __('nav_dashboard'); ?>
    </a>

    <a href="my_bookings.php">
        <i class="fa-regular fa-calendar-check"></i>
        <?= __('nav_bookings'); ?>
    </a>

    <a
        href="categories.php"
        class="active"
    >
        <i class="fa-solid fa-list"></i>
        <?= __('nav_categories'); ?>
    </a>

    <a href="profile.php">
        <i class="fa-regular fa-user"></i>
        <?= __('nav_profile'); ?>
    </a>

    <a
        href="logout.php"
        class="text-danger"
    >
        <i class="fa-solid fa-right-from-bracket text-danger"></i>
        <?= __('nav_logout'); ?>
    </a>

</div>


<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<div class="main-content">

    <div class="container-fluid">

        <div class="text-muted small mb-2">

            <a
                href="renter_dashboard.php"
                class="text-decoration-none text-muted"
            >
                <?= __('nav_dashboard'); ?>
            </a>

            <i
                class="fa-solid fa-chevron-right mx-1"
                style="font-size:10px;"
            ></i>

            <span class="text-dark fw-semibold">
                <?= __('book_equipment_title'); ?>
            </span>

        </div>


        <h2 class="mb-1 text-success fw-bold">
            <?= __('book_equipment_title'); ?>
        </h2>

        <p
            class="text-muted mb-4"
            style="font-size:14px;"
        >
            <?= __('book_subtitle'); ?>
        </p>


        <?php if (!empty($error_msg)): ?>

            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >

                <?= htmlspecialchars($error_msg); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <form
            action=""
            method="POST"
            enctype="multipart/form-data"
            id="bookingForm"
        >

            <div class="row">


                <!-- =================================================
                     LEFT COLUMN
                     ================================================= -->

                <div class="col-lg-8">


                    <!-- EQUIPMENT -->

                    <div class="card card-custom p-4 mb-4">

                        <div class="row align-items-center">

                            <div class="col-md-4 mb-3 mb-md-0">

                                <img
                                    src="<?= htmlspecialchars(
                                        $equipment_image_path
                                    ); ?>"
                                    alt="<?= htmlspecialchars(
                                        $equipment['title']
                                    ); ?>"
                                    class="img-fluid rounded border"
                                    style="
                                        height:140px;
                                        width:100%;
                                        object-fit:cover;
                                    "
                                >

                            </div>

                            <div class="col-md-8">

                                <h5 class="fw-bold mb-2">
                                    <?= htmlspecialchars(
                                        $equipment['title']
                                    ); ?>
                                </h5>

                                <p
                                    class="text-muted mb-2 small"
                                >

                                    <i class="fa-solid fa-tractor me-1"></i>

                                    <?= __('lbl_category'); ?>:

                                    <?= htmlspecialchars(
                                        $equipment['category'] ??
                                        'Agriculture'
                                    ); ?>

                                    &nbsp; | &nbsp;

                                    <i class="fa-solid fa-user me-1"></i>

                                    <?= __('lbl_owner'); ?>:

                                    <?= htmlspecialchars(
                                        $equipment['lender_name']
                                    ); ?>

                                </p>

                                <p
                                    class="text-muted mb-2 small"
                                >

                                    <i class="fa-solid fa-location-dot me-1"></i>

                                    <?= htmlspecialchars(
                                        $equipment['service_areas'] ??
                                        $equipment['service_location'] ??
                                        'Location Available'
                                    ); ?>

                                </p>

                                <h4 class="text-success fw-bold mb-0">

                                    ₹<?= number_format(
                                        $price_per_day,
                                        2
                                    ); ?>

                                    <span
                                        class="text-muted fw-normal"
                                        style="font-size:13px;"
                                    >
                                        / day
                                    </span>

                                </h4>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         RENTAL DETAILS
                         ================================================= -->

                    <div class="card card-custom p-4 mb-4">

                        <h5 class="fw-bold mb-3 text-success">
                            <?= __('sec_rental_details'); ?>
                        </h5>


                        <div class="row">


                            <!-- START DATE -->

                            <div class="col-md-4 mb-3">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_start_date'); ?>
                                </label>

                                <input
                                    type="date"
                                    class="form-control shadow-none"
                                    id="start_date"
                                    name="start_date"
                                    min="<?= date('Y-m-d'); ?>"
                                    required
                                    value="<?= htmlspecialchars(
                                        $_POST['start_date'] ??
                                        date('Y-m-d')
                                    ); ?>"
                                >

                            </div>


                            <!-- END DATE -->

                            <div class="col-md-4 mb-3">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_end_date'); ?>
                                </label>

                                <input
                                    type="date"
                                    class="form-control shadow-none"
                                    id="end_date"
                                    name="end_date"
                                    min="<?= date('Y-m-d'); ?>"
                                    required
                                >

                            </div>


                            <!-- QUANTITY -->

                            <div class="col-md-4 mb-3">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_quantity'); ?>
                                </label>

                                <select
                                    class="form-select shadow-none"
                                    id="quantity"
                                    name="quantity"
                                >

                                    <option value="1">1 Unit</option>
                                    <option value="2">2 Units</option>
                                    <option value="3">3 Units</option>
                                    <option value="4">4 Units</option>
                                    <option value="5">5 Units</option>

                                </select>

                            </div>

                        </div>


                        <!-- TOTAL DAYS -->

                        <div class="row">

                            <div class="col-md-12">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_total_days'); ?>
                                </label>

                                <input
                                    type="text"
                                    class="form-control bg-light fw-semibold shadow-none"
                                    id="display_total_days_input"
                                    value="1 Day"
                                    readonly
                                >

                            </div>

                        </div>


                        <!-- RENTAL LIMIT -->

                        <div
                            id="duration_feedback"
                            class="small mt-2 text-success"
                        >

                            <i class="fa-regular fa-circle-check me-1"></i>

                            Total Rental Duration:

                            <strong id="display_total_days_text">
                                1 Day
                            </strong>

                            (Minimum required:

                            <strong>
                                <?= $min_booking_days; ?> Days
                            </strong>

                            , Maximum allowed:

                            <strong>
                                <?= $max_booking_days; ?> Days
                            </strong>)

                        </div>

                    </div>


                    <!-- =================================================
                         DELIVERY ADDRESS
                         ================================================= -->

                    <div class="card card-custom p-4 mb-4">

                        <h5 class="fw-bold mb-3 text-success">
                            <?= __('sec_delivery_address'); ?>
                        </h5>


                        <div class="form-check mb-3">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="use_registered_address"
                                name="use_registered_address"
                                checked
                            >

                            <label
                                class="form-check-label fw-semibold"
                                for="use_registered_address"
                                style="font-size:14px;"
                            >
                                <?= __('lbl_use_reg_address'); ?>
                            </label>

                        </div>


                        <!-- REGISTERED ADDRESS -->

                        <div id="registered_address_display">

                            <label
                                class="form-label text-muted"
                                style="font-size:12px;"
                            >
                                <?= __('lbl_full_address'); ?>
                            </label>

                            <input
                                type="text"
                                class="form-control bg-light"
                                value="<?= !empty(
                                    $renter_user['address']
                                )
                                    ? htmlspecialchars(
                                        $renter_user['address']
                                    )
                                    : 'Registered address not set in profile'; ?>"
                                readonly
                            >

                        </div>


                        <!-- MANUAL ADDRESS -->

                        <div
                            id="manual_address_wrapper"
                            style="display:none;"
                        >

                            <div class="row">

                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label text-muted"
                                        style="font-size:12px;"
                                    >
                                        State
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        name="address_state"
                                        placeholder="Karnataka"
                                    >

                                </div>


                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label text-muted"
                                        style="font-size:12px;"
                                    >
                                        District
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        name="address_district"
                                        placeholder="Bengaluru Rural"
                                    >

                                </div>

                            </div>


                            <div class="row">

                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label text-muted"
                                        style="font-size:12px;"
                                    >
                                        Area / Taluk
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        name="address_taluk"
                                        placeholder="Devanahalli"
                                    >

                                </div>


                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label text-muted"
                                        style="font-size:12px;"
                                    >
                                        PIN Code
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        name="address_pincode"
                                        maxlength="6"
                                        placeholder="562110"
                                    >

                                </div>

                            </div>


                            <div class="mb-3">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    Full Address
                                </label>

                                <textarea
                                    class="form-control"
                                    name="address_full"
                                    rows="2"
                                    placeholder="Door No, Street name, Landmark"
                                ></textarea>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         IDENTITY VERIFICATION
                         ================================================= -->

                    <div class="card card-custom p-4 mb-4">

                        <h5 class="fw-bold mb-3 text-success">
                            <?= __('sec_identity_verification'); ?>
                        </h5>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_gov_id'); ?>
                                </label>

                                <div
                                    class="upload-box"
                                    onclick="document.getElementById('id_proof_doc').click()"
                                >

                                    <i
                                        class="fa-solid fa-cloud-arrow-up text-success fa-2x mb-1"
                                    ></i>

                                    <p
                                        class="mb-0 fw-semibold"
                                        style="font-size:13px;"
                                    >
                                        <?= __('lbl_click_upload'); ?>
                                    </p>

                                    <small
                                        class="text-muted"
                                        style="font-size:11px;"
                                    >
                                        <?= __('lbl_file_specs'); ?>
                                    </small>

                                    <input
                                        type="file"
                                        id="id_proof_doc"
                                        name="id_proof_doc"
                                        class="d-none"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        required
                                    >

                                    <div
                                        id="file_name_display"
                                        class="text-success small mt-1 fw-bold"
                                    ></div>

                                </div>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label
                                    class="form-label text-muted"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_id_number'); ?>
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="id_number"
                                    required
                                    value="<?= htmlspecialchars(
                                        $_POST['id_number'] ?? ''
                                    ); ?>"
                                >


                                <label
                                    class="form-label text-muted mt-3"
                                    style="font-size:12px;"
                                >
                                    <?= __('lbl_phone_number'); ?>
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="phone_number"
                                    required
                                    value="<?= htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         PAYMENT INFORMATION
                         ================================================= -->

                    <div class="card card-custom p-4 mb-4">

                        <h5 class="fw-bold mb-3 text-success">
                            <?= __('sec_payment_info'); ?>
                        </h5>


                        <div class="alert alert-info py-2">

                            <i class="fa-solid fa-circle-info me-1"></i>

                            <strong>
                                Only cash / COD payment is available.
                            </strong>

                        </div>


                        <div class="row">


                            <!-- ADVANCE -->

                            <div class="col-md-6 mb-3">

                                <div class="payment-box h-100">

                                    <div class="d-flex align-items-center mb-2">

                                        <i
                                            class="fa-solid fa-wallet text-success fa-lg me-2"
                                        ></i>

                                        <span class="fw-bold">
                                            Advance Payment
                                        </span>

                                    </div>

                                    <p
                                        class="text-muted mb-1"
                                        style="font-size:12px;"
                                    >
                                        Security deposit specified by lender
                                    </p>

                                    <h5
                                        class="text-success fw-bold"
                                    >

                                        ₹<span id="payment_sec_dep_val">
                                            <?= number_format(
                                                $security_deposit,
                                                2
                                            ); ?>
                                        </span>

                                    </h5>

                                </div>

                            </div>


                            <!-- REMAINING -->

                            <div class="col-md-6 mb-3">

                                <div class="payment-box h-100">

                                    <div class="d-flex align-items-center mb-2">

                                        <i
                                            class="fa-solid fa-money-bill-transfer text-primary fa-lg me-2"
                                        ></i>

                                        <span class="fw-bold">
                                            Remaining Payment
                                        </span>

                                    </div>

                                    <p
                                        class="text-muted mb-1"
                                        style="font-size:12px;"
                                    >
                                        Total rent minus advance/security deposit
                                    </p>

                                    <h5
                                        class="text-primary fw-bold"
                                    >

                                        ₹<span id="payment_remaining_val">
                                            0.00
                                        </span>

                                    </h5>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- TERMS -->

                    <div class="form-check mb-4">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="terms"
                            required
                        >

                        <label
                            class="form-check-label text-muted"
                            for="terms"
                            style="font-size:13px;"
                        >
                            <?= __('lbl_agree_terms'); ?>
                            <a
                                href="terms_conditions.php?lang=<?= urlencode($_SESSION['lang'] ?? 'en'); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-success fw-semibold text-decoration-none"
                                onclick="event.stopPropagation();"
                            >
                                Terms and Conditions
                            </a>
                        </label>

                    </div>

                </div>


                <!-- =================================================
                     RIGHT COLUMN - ORDER SUMMARY
                     ================================================= -->

                <div class="col-lg-4">

                    <div
                        class="card card-custom p-4 sticky-top"
                        style="top:90px;"
                    >

                        <h5
                            class="fw-bold mb-3 text-dark border-bottom pb-2"
                        >
                            Order Summary
                        </h5>


                        <ul
                            class="list-unstyled mb-3"
                            style="font-size:14px;"
                        >


                            <!-- PRICE -->

                            <li class="d-flex justify-content-between mb-2">

                                <span class="text-muted">
                                    Price Per Day
                                </span>

                                <span class="fw-semibold">

                                    ₹<?= number_format(
                                        $price_per_day,
                                        2
                                    ); ?>

                                </span>

                            </li>


                            <!-- DAYS -->

                            <li class="d-flex justify-content-between mb-2">

                                <span class="text-muted">
                                    Total Days
                                </span>

                                <span
                                    class="fw-semibold"
                                    id="summary_rental_days"
                                >
                                    1 Day
                                </span>

                            </li>


                            <!-- QUANTITY -->

                            <li class="d-flex justify-content-between mb-2">

                                <span class="text-muted">
                                    Quantity
                                </span>

                                <span
                                    class="fw-semibold"
                                    id="summary_quantity"
                                >
                                    1 Unit
                                </span>

                            </li>


                            <hr class="my-2">


                            <!-- TOTAL RENT -->

                            <li
                                class="d-flex justify-content-between mb-2"
                            >

                                <span class="fw-bold">
                                    Total Rent
                                </span>

                                <span
                                    class="fw-bold text-success"
                                >
                                    ₹<span id="summary_total_rent">
                                        0.00
                                    </span>
                                </span>

                            </li>


                            <!-- ADVANCE -->

                            <li
                                class="d-flex justify-content-between mb-2 text-secondary"
                                style="font-size:13px;"
                            >

                                <span>
                                    Advance / Security Deposit
                                </span>

                                <span class="fw-semibold">

                                    ₹<span id="summary_advance">
                                        <?= number_format(
                                            $security_deposit,
                                            2
                                        ); ?>
                                    </span>

                                </span>

                            </li>


                            <!-- REMAINING -->

                            <li
                                class="d-flex justify-content-between mb-3 bg-light p-2 rounded"
                            >

                                <span class="fw-bold">
                                    Remaining COD
                                </span>

                                <span
                                    class="fw-bold text-primary"
                                >
                                    ₹<span id="summary_remaining">
                                        0.00
                                    </span>
                                </span>

                            </li>

                        </ul>


                        <!-- SECURITY MESSAGE -->

                        <div
                            class="text-center text-muted mb-3"
                            style="font-size:12px;"
                        >

                            <i class="fa-solid fa-lock me-1"></i>

                            You won't be charged now

                        </div>


                        <!-- CONFIRM -->

                        <button
                            type="submit"
                            id="submitBtn"
                            class="btn btn-agro w-100 mb-2 d-flex align-items-center justify-content-center gap-2"
                        >

                            <span>
                                Confirm Booking
                            </span>

                            <i class="fa-solid fa-arrow-right"></i>

                        </button>


                        <!-- CANCEL -->

                        <a
                            href="categories.php"
                            class="btn btn-outline-secondary w-100"
                            style="font-size:13px;"
                        >
                            Cancel
                        </a>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>
/* =========================================================
   RENT NOW - REAL-TIME CALCULATIONS
   All price, security deposit, minimum days and maximum days
   values come from the PHP/database. Nothing is hard-coded.
   ========================================================= */

const pricePerDay = Number(<?php echo json_encode($price_per_day); ?>);
const securityDeposit = Number(<?php echo json_encode($security_deposit); ?>);
const minBookingDays = Number(<?php echo json_encode($min_booking_days); ?>);
const maxBookingDays = Number(<?php echo json_encode($max_booking_days); ?>);

function formatCurrency(value) {
    return (Number(value) || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function parseLocalDate(value) {
    if (!value) return null;
    const parts = value.split('-').map(Number);
    if (parts.length !== 3 || parts.some(Number.isNaN)) return null;

    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return Number.isNaN(date.getTime()) ? null : date;
}

function formatDate(date) {
    return date.getFullYear() + '-' +
        String(date.getMonth() + 1).padStart(2, '0') + '-' +
        String(date.getDate()).padStart(2, '0');
}

/* Inclusive calculation:
   Aug 30 -> Aug 30 = 1 day
   Aug 30 -> Aug 31 = 2 days */
function getRentalDays(startValue, endValue) {
    const start = parseLocalDate(startValue);
    const end = parseLocalDate(endValue);

    if (!start || !end || end < start) return 0;

    return Math.round((end - start) / (24 * 60 * 60 * 1000)) + 1;
}

function updateDateLimits() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    if (!startInput || !endInput || !startInput.value) return;

    const start = parseLocalDate(startInput.value);
    if (!start) return;

    const minimumEnd = new Date(start);
    minimumEnd.setDate(minimumEnd.getDate() + minBookingDays - 1);

    const maximumEnd = new Date(start);
    maximumEnd.setDate(maximumEnd.getDate() + maxBookingDays - 1);

    endInput.min = formatDate(minimumEnd);
    endInput.max = formatDate(maximumEnd);

    /* If the current end date is invalid/empty, select the
       minimum valid end date automatically. */
    if (
        !endInput.value ||
        endInput.value < endInput.min ||
        endInput.value > endInput.max
    ) {
        endInput.value = endInput.min;
    }
}

function calculateSummary() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const quantityInput = document.getElementById('quantity');

    const displayTotalDays = document.getElementById('display_total_days_input');
    const displayTotalDaysText = document.getElementById('display_total_days_text');
    const summaryRentalDays = document.getElementById('summary_rental_days');
    const summaryQuantity = document.getElementById('summary_quantity');

    const summaryTotalRent = document.getElementById('summary_total_rent');
    const summaryAdvance = document.getElementById('summary_advance');
    const summaryRemaining = document.getElementById('summary_remaining');

    const paymentSecurityDeposit = document.getElementById('payment_sec_dep_val');
    const paymentRemaining = document.getElementById('payment_remaining_val');

    const feedback = document.getElementById('duration_feedback');
    const submitButton = document.getElementById('submitBtn');

    if (!startInput || !endInput || !quantityInput) return;

    const totalDays = getRentalDays(startInput.value, endInput.value);

    let quantity = parseInt(quantityInput.value, 10);
    if (!Number.isFinite(quantity) || quantity < 1) {
        quantity = 1;
        quantityInput.value = '1';
    }

    const dayText = totalDays > 0
        ? `${totalDays} ${totalDays === 1 ? 'Day' : 'Days'}`
        : 'Select dates';

    const quantityText =
        `${quantity} ${quantity === 1 ? 'Unit' : 'Units'}`;

    if (displayTotalDays) displayTotalDays.value = dayText;
    if (displayTotalDaysText) displayTotalDaysText.innerText = dayText;
    if (summaryRentalDays) summaryRentalDays.innerText = dayText;
    if (summaryQuantity) summaryQuantity.innerText = quantityText;

    /* Invalid dates: clear calculated rent and disable booking. */
    if (totalDays <= 0) {
        if (summaryTotalRent) summaryTotalRent.innerText = '0.00';
        if (summaryAdvance) summaryAdvance.innerText = formatCurrency(securityDeposit);
        if (summaryRemaining) summaryRemaining.innerText = '0.00';
        if (paymentSecurityDeposit) paymentSecurityDeposit.innerText = formatCurrency(securityDeposit);
        if (paymentRemaining) paymentRemaining.innerText = '0.00';

        if (feedback) {
            feedback.className = 'small mt-2 text-danger';
            feedback.innerHTML =
                '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
                'Please select valid rental dates.';
        }

        if (submitButton) submitButton.disabled = true;
        return;
    }

    /* Enforce lender's minimum/maximum rental days. */
    const validDuration =
        totalDays >= minBookingDays && totalDays <= maxBookingDays;

    if (feedback) {
        if (totalDays < minBookingDays) {
            feedback.className = 'small mt-2 text-danger';
            feedback.innerHTML =
                '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
                `Rental duration is less than the minimum required ${minBookingDays} days.`;
        } else if (totalDays > maxBookingDays) {
            feedback.className = 'small mt-2 text-danger';
            feedback.innerHTML =
                '<i class="fa-solid fa-circle-exclamation me-1"></i>' +
                `Rental duration cannot exceed ${maxBookingDays} days.`;
        } else {
            feedback.className = 'small mt-2 text-success';
            feedback.innerHTML =
                '<i class="fa-regular fa-circle-check me-1"></i>' +
                `Total Rental Duration: <strong>${dayText}</strong> ` +
                `(Minimum required: <strong>${minBookingDays} Days</strong>, ` +
                `Maximum allowed: <strong>${maxBookingDays} Days</strong>)`;
        }
    }

    if (submitButton) submitButton.disabled = !validDuration;

    /*
     * REQUIRED CALCULATION
     * Total Rent = Price Per Day × Total Days × Quantity
     * Advance = EXACT security deposit entered by lender
     * Remaining COD = Total Rent - Advance
     *
     * Security deposit is NOT multiplied by quantity.
     */
    const totalRent = pricePerDay * totalDays * quantity;
    const advancePayment = Math.max(0, securityDeposit);
    const remainingPayment = Math.max(0, totalRent - advancePayment);

    /* Update Order Summary immediately. */
    if (summaryTotalRent) {
        summaryTotalRent.innerText = formatCurrency(totalRent);
    }

    if (summaryAdvance) {
        summaryAdvance.innerText = formatCurrency(advancePayment);
    }

    if (summaryRemaining) {
        summaryRemaining.innerText = formatCurrency(remainingPayment);
    }

    /* Update Payment Information immediately. */
    if (paymentSecurityDeposit) {
        paymentSecurityDeposit.innerText = formatCurrency(advancePayment);
    }

    if (paymentRemaining) {
        paymentRemaining.innerText = formatCurrency(remainingPayment);
    }
}

function toggleAddressFields() {
    const checkbox = document.getElementById('use_registered_address');
    const registered = document.getElementById('registered_address_display');
    const manual = document.getElementById('manual_address_wrapper');

    if (!checkbox || !registered || !manual) return;

    registered.style.display = checkbox.checked ? 'block' : 'none';
    manual.style.display = checkbox.checked ? 'none' : 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const quantityInput = document.getElementById('quantity');
    const addressCheckbox = document.getElementById('use_registered_address');
    const idProofInput = document.getElementById('id_proof_doc');

    if (addressCheckbox) {
        addressCheckbox.addEventListener('change', toggleAddressFields);
        toggleAddressFields();
    }

    /* Start date works with calendar selection AND typing. */
    if (startInput) {
        startInput.addEventListener('change', function() {
            updateDateLimits();
            calculateSummary();
        });

        startInput.addEventListener('input', function() {
            updateDateLimits();
            calculateSummary();
        });
    }

    /* End date works with calendar selection AND typing. */
    if (endInput) {
        endInput.addEventListener('change', calculateSummary);
        endInput.addEventListener('input', calculateSummary);
    }

    /* Quantity changes recalculate all amounts immediately. */
    if (quantityInput) {
        quantityInput.addEventListener('change', calculateSummary);
        quantityInput.addEventListener('input', calculateSummary);
    }

    if (idProofInput) {
        idProofInput.addEventListener('change', function() {
            const display = document.getElementById('file_name_display');
            if (!display) return;

            display.innerText =
                this.files && this.files.length
                    ? 'Selected: ' + this.files[0].name
                    : '';
        });
    }

    /*
     * On first load, set the end date to the minimum valid date,
     * then calculate the complete summary.
     */
    if (startInput && startInput.value) {
        updateDateLimits();
    }

    calculateSummary();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>