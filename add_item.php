
<?php
session_start();
require_once 'includes/lang.php';

if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$lender_id = (int)$_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

$categories_list = [
    'Tractor',
    'Harvesting',
    'Irrigation',
    'Tillage',
    'Seeding',
    'Spraying'
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (isset($_POST['submit_equipment']) || isset($_POST['save_equipment']))
) {
    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name'] ?? '');
    $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
    $model = mysqli_real_escape_string($conn, $_POST['model'] ?? '');
    $brand_model = trim($brand . ' ' . $model);

    $year_of_purchase = (int)($_POST['year_of_purchase'] ?? date('Y'));

    $item_condition = mysqli_real_escape_string($conn, $_POST['item_condition'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

    $price_per_day = (float)($_POST['price_per_day'] ?? 0.00);
    $security_deposit = (float)($_POST['security_deposit'] ?? 0.00);
    $total_quantity = max(1, (int)($_POST['total_quantity'] ?? 1));
    $min_rental_days = max(1, (int)($_POST['min_rental_days'] ?? 1));

    $max_rental_days = !empty($_POST['max_rental_days'])
        ? max($min_rental_days, (int)$_POST['max_rental_days'])
        : 30;

    /*
     * FORM STATUS:
     * available / unavailable
     *
     * EQUIPMENT TABLE:
     * Available / Inactive
     */

    $status_input = $_POST['status'] ?? 'available';

    if ($status_input === 'available') {
        $equipment_status = 'Available';
    } else {
        $equipment_status = 'Inactive';
    }

    $equipment_status = mysqli_real_escape_string($conn, $equipment_status);

    /*
     * Convert condition values:
     * Excellent -> New
     * Good      -> Good
     * Fair      -> Used
     */

    if ($item_condition === 'Excellent') {
        $equipment_condition = 'New';
    } elseif ($item_condition === 'Fair') {
        $equipment_condition = 'Used';
    } else {
        $equipment_condition = 'Good';
    }

    $equipment_condition = mysqli_real_escape_string($conn, $equipment_condition);

    $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type'] ?? 'Diesel');
    $power_hp = !empty($_POST['power_hp']) ? (int)$_POST['power_hp'] : 0;
    $working_hours = !empty($_POST['working_hours']) ? (int)$_POST['working_hours'] : 0;
    $is_recommended = isset($_POST['is_recommended']) ? 1 : 0;

    $drive_type = '4WD';
    $working_width = '';

    $service_areas_arr = isset($_POST['service_areas'])
        ? $_POST['service_areas']
        : [];

    $service_areas = mysqli_real_escape_string(
        $conn,
        implode(', ', $service_areas_arr)
    );

    /*
     * FIND CATEGORY ID
     */

    $cat_name_q = mysqli_query(
        $conn,
        "SELECT category_id
         FROM categories
         WHERE category_name = '$category_name'
         LIMIT 1"
    );

    $category_id = 1;

    if ($cat_name_q && mysqli_num_rows($cat_name_q) > 0) {
        $cat_data = mysqli_fetch_assoc($cat_name_q);
        $category_id = (int)$cat_data['category_id'];
    }

    /*
     * IMAGE UPLOAD
     */

    $image_name = '';

    if (!empty($_FILES['image']['name'])) {
        $file_ext = pathinfo(
            $_FILES['image']['name'],
            PATHINFO_EXTENSION
        );

        $image_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
        $target_path = 'uploads/' . $image_name;

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $target_path
        );
    }

    /*
     * RECOMMENDATION BADGE
     */

    $badge = $is_recommended ? 'RECOMMENDED' : 'NONE';

    /*
     * INSERT INTO EQUIPMENT TABLE
     */

    $equipment_sql = "INSERT INTO equipment (
        category_id,
        lender_id,
        title,
        category,
        brand_model,
        power_hp,
        drive_type,
        model_year,
        fuel_type,
        working_width,
        equipment_condition,
        price_per_day,
        security_deposit,
        total_quantity,
        min_booking_days,
        max_booking_days,
        service_location,
        distance_km,
        description,
        image,
        badge,
        status,
        rating,
        rating_count,
        created_at,
        is_featured
    ) VALUES (
        $category_id,
        $lender_id,
        '$title',
        '$category_name',
        '$brand_model',
        $power_hp,
        '$drive_type',
        $year_of_purchase,
        '$fuel_type',
        '$working_width',
        '$equipment_condition',
        $price_per_day,
        $security_deposit,
        $total_quantity,
        $min_rental_days,
        $max_rental_days,
        '$service_areas',
        25,
        '$description',
        '$image_name',
        '$badge',
        '$equipment_status',
        0.0,
        0,
        NOW(),
        $is_recommended
    )";

    if (mysqli_query($conn, $equipment_sql)) {
        $equipment_id = mysqli_insert_id($conn);
        $success_msg = "Equipment added successfully!";
    } else {
        $error_msg = "Equipment table error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'] ?? 'en'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo __('add_equipment'); ?> - Agriculture Rental System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

    <style>
        :root {
            --navy-primary: #0B3478;
            --navy-dark: #082B65;
            --blue-hover: #164C9A;
            --sidebar-bg: #C7DDF7;
            --sidebar-active: #A6C8F5;
            --text-dark: #10194A;
            --text-muted: #425D80;
            --bg-body: #EAF3FF;
            --card-bg: #F8FBFF;
            --border-color: #C8DDF5;
            --blue-accent: #8DB8F0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            margin: 0;
        }

        /* Main content */
        .main-wrapper {
            margin-left: 250px;
            padding: 15px 30px 40px;
            background-color: var(--bg-body);
            min-height: calc(100vh - 50px);
        }

        /* Top navigation */
        .top-navbar {
            background-color: var(--navy-primary);
            color: #FFFFFF;
            border-bottom: 1px solid #244D91;
            padding: 10px 30px;
            margin-left: 250px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
            z-index: 99;
            box-shadow: 0 2px 8px rgba(11, 52, 120, 0.12);
        }

        .top-navbar .text-muted {
            color: #D4E5FF !important;
        }

        /* Lender profile avatar: initials derived from the signed-in user's name */
        .lender-avatar {
            width: 34px;
            height: 34px;
            min-width: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFFFFF;
            color: var(--navy-primary);
            border: 1px solid #D7E6FA;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.3px;
        }

        .lender-profile-name,
        .lender-profile-role {
            color: #FFFFFF !important;
        }

        .lender-profile-role {
            display: inline-block;
            margin-top: 3px;
            font-size: 0.65rem;
            font-weight: 600;
            opacity: 0.9;
        }

        .top-navbar .form-select {
            background-color: #EAF3FF;
            color: var(--text-dark);
            border-color: #9DBDE9;
        }

        /* Page heading */
        .page-header-bar {
            background-color: var(--navy-primary);
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(11, 52, 120, 0.15);
        }

        /* Cards */
        .card-custom {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 14px rgba(44, 92, 155, 0.08);
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--navy-primary);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form elements */
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .form-control,
        .form-select {
            font-size: 0.85rem;
            padding: 7px 12px;
            color: var(--text-dark);
            background-color: #FFFFFF;
            border: 1px solid #B9D0EF;
            border-radius: 7px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4D85D6;
            box-shadow: 0 0 0 0.2rem rgba(77, 133, 214, 0.18);
        }

        /* Primary button */
        .btn-teal {
            background-color: var(--navy-primary);
            color: #FFFFFF;
            border: 1px solid var(--navy-primary);
            padding: 8px 24px;
            border-radius: 7px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background-color 0.2s ease;
        }

        .btn-teal:hover,
        .btn-teal:focus {
            background-color: var(--blue-hover);
            color: #FFFFFF;
            border-color: var(--blue-hover);
        }

        /* Image upload */
        .image-dropzone {
            border: 2px dashed #A8C5EC;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            background-color: #EFF6FF;
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s;
        }

        .image-dropzone:hover {
            border-color: var(--navy-primary);
            background-color: #E2EEFF;
        }

        /* Links */
        .text-teal {
            color: var(--navy-primary) !important;
        }

        .btn-link:hover {
            color: var(--blue-hover) !important;
        }

        .btn-outline-secondary {
            color: var(--navy-primary);
            border-color: #9CBCE8;
        }

        .btn-outline-secondary:hover {
            background-color: var(--navy-primary);
            color: #FFFFFF;
            border-color: var(--navy-primary);
        }

        /* Checkbox and radio colors */
        .form-check-input {
            border-color: #91B2E0;
        }

        .form-check-input:checked {
            background-color: var(--navy-primary);
            border-color: var(--navy-primary);
        }

        .form-check-input:focus {
            border-color: #4D85D6;
            box-shadow: 0 0 0 0.2rem rgba(77, 133, 214, 0.18);
        }

        /* Success and error messages */
        .alert-success {
            background-color: #E5F6EC;
            border-color: #A8DDBB;
            color: #17613B;
        }

        .alert-danger {
            background-color: #FFF0F0;
            border-color: #F2B8B8;
            color: #9B2222;
        }

        /* Sidebar color overrides */
        .sidebar,
        #sidebar,
        .lender-sidebar,
        .lender_sidebar,
        .sidebar-wrapper,
        .lender-sidebar-wrapper {
            background-color: var(--sidebar-bg) !important;
            color: var(--text-dark);
            border-right: 1px solid var(--border-color);
        }

        .sidebar a,
        #sidebar a,
        .lender-sidebar a,
        .lender_sidebar a,
        .sidebar-wrapper a,
        .lender-sidebar-wrapper a {
            color: var(--text-dark);
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar a:hover,
        #sidebar a:hover,
        .lender-sidebar a:hover,
        .lender_sidebar a:hover,
        .sidebar-wrapper a:hover,
        .lender-sidebar-wrapper a:hover {
            background-color: var(--sidebar-active) !important;
            color: var(--navy-primary) !important;
        }

        .sidebar .active,
        #sidebar .active,
        .lender-sidebar .active,
        .lender_sidebar .active,
        .sidebar-wrapper .active,
        .lender-sidebar-wrapper .active {
            background-color: var(--sidebar-active) !important;
            color: var(--navy-primary) !important;
            border-radius: 8px;
        }

        .sidebar i,
        #sidebar i,
        .lender-sidebar i,
        .lender_sidebar i,
        .sidebar-wrapper i,
        .lender-sidebar-wrapper i {
            color: var(--navy-primary);
        }

        /* Responsive layout */
        @media (max-width: 991px) {
            .main-wrapper {
                margin-left: 0;
                padding: 15px;
            }

            .top-navbar {
                margin-left: 0;
                padding: 10px 15px;
            }
        }
    </style>
</head>

<body>

    <!-- TOP NAVBAR -->
    <div class="top-navbar fixed-top">
        <form method="GET" action="" class="m-0">
            <select
                name="lang"
                onchange="this.form.submit()"
                class="form-select form-select-sm"
            >
                <option
                    value="en"
                    <?= (($_SESSION['lang'] ?? 'en') == 'en') ? 'selected' : ''; ?>
                >
                    English
                </option>

                <option
                    value="kn"
                    <?= (($_SESSION['lang'] ?? 'en') == 'kn') ? 'selected' : ''; ?>
                >
                    ಕನ್ನಡ (Kannada)
                </option>

                <option
                    value="hi"
                    <?= (($_SESSION['lang'] ?? 'en') == 'hi') ? 'selected' : ''; ?>
                >
                    हिन्दी (Hindi)
                </option>
            </select>
        </form>

        <?php
            // Build profile initials from the logged-in lender's actual name.
            $profileName = trim((string)($_SESSION['full_name'] ?? 'Lender'));
            $nameParts = preg_split('/\s+/u', $profileName, -1, PREG_SPLIT_NO_EMPTY);
            $profileInitials = '';
            if (!empty($nameParts)) {
                $profileInitials = mb_strtoupper(mb_substr($nameParts[0], 0, 1, 'UTF-8'), 'UTF-8');
                if (count($nameParts) > 1) {
                    $profileInitials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8'), 'UTF-8');
                }
            }
        ?>
        <div class="d-flex align-items-center gap-2 lender-profile">
            <div class="lender-avatar" aria-label="<?= htmlspecialchars($profileInitials); ?>">
                <?= htmlspecialchars($profileInitials); ?>
            </div>

            <div class="lh-1 lender-profile-info">
                <div class="fw-bold small lender-profile-name">
                    <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <span class="lender-profile-role">
                    <?php echo __('lender'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- SHARED LENDER SIDEBAR -->
    <?php include 'lender_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper" style="margin-top: 50px;">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0">
                    <?php echo __('add_equipment'); ?>
                </h4>

                <div class="text-muted small">
                    <?php echo __('home'); ?> /
                    <?php echo __('add_equipment'); ?>
                </div>
            </div>

            <a
                href="lender_dashboard.php"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3"
            >
                <?php echo __('back_dashboard'); ?>
            </a>
        </div>

        <div class="page-header-bar d-flex justify-content-between align-items-center">
            <span
                class="fw-bold"
                style="font-size: 0.85rem; letter-spacing: 0.5px;"
            >
                <?php echo __('header_badge'); ?>
            </span>

            <small style="font-size: 0.75rem; opacity: 0.85;">
                <?php echo __('header_subtitle'); ?>
            </small>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div
                class="alert alert-success alert-dismissible fade show"
                role="alert"
            >
                <i class="fa-solid fa-circle-check me-2"></i>
                <?= $success_msg; ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div
                class="alert alert-danger alert-dismissible fade show"
                role="alert"
            >
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <?= $error_msg; ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row g-3">

                <div class="col-lg-8">

                    <!-- 1. EQUIPMENT DETAILS -->
                    <div class="card-custom">
                        <div class="section-title">
                            <i class="fa-regular fa-clipboard"></i>
                            <?php echo __('sec_details'); ?>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('eq_name'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    placeholder="<?php echo __('eq_name_ph'); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('category'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="category_name" class="form-select" required>
                                    <option value="" selected disabled>
                                        <?php echo __('select_category'); ?>
                                    </option>

                                    <?php foreach ($categories_list as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat); ?>">
                                            <?= htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('brand'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="brand"
                                    class="form-control"
                                    placeholder="<?php echo __('brand_ph'); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('model'); ?>
                                </label>

                                <input
                                    type="text"
                                    name="model"
                                    class="form-control"
                                    placeholder="<?php echo __('model_ph'); ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('year_purchase'); ?>
                                </label>

                                <input
                                    type="number"
                                    name="year_of_purchase"
                                    class="form-control"
                                    value="<?= date('Y'); ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('condition'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="item_condition" class="form-select" required>
                                    <option value="" selected disabled>
                                        <?php echo __('select_condition'); ?>
                                    </option>

                                    <option value="Excellent">Excellent</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">
                                    <?php echo __('description'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="3"
                                    placeholder="<?php echo __('description_ph'); ?>"
                                    required
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 2. PRICING & AVAILABILITY -->
                    <div class="card-custom">
                        <div class="section-title">
                            <i class="fa-solid fa-indian-rupee-sign"></i>
                            <?php echo __('sec_pricing'); ?>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('price_per_day'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="price_per_day"
                                    step="0.01"
                                    class="form-control"
                                    placeholder="<?php echo __('price_per_day_ph'); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('security_deposit'); ?>
                                </label>

                                <input
                                    type="number"
                                    name="security_deposit"
                                    step="0.01"
                                    class="form-control"
                                    placeholder="<?php echo __('security_deposit_ph'); ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Quantity <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="total_quantity"
                                    min="1"
                                    value="1"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('min_rental'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="min_rental_days"
                                    class="form-control"
                                    value="1"
                                    required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <?php echo __('max_rental'); ?>
                                </label>

                                <input
                                    type="number"
                                    name="max_rental_days"
                                    class="form-control"
                                    placeholder="<?php echo __('max_rental_ph'); ?>"
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label d-block">
                                    <?php echo __('avail_status'); ?>
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="form-check form-check-inline">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="status"
                                        id="statusAvail"
                                        value="available"
                                        checked
                                    >

                                    <label class="form-check-label small" for="statusAvail">
                                        <?php echo __('available'); ?>
                                    </label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="status"
                                        id="statusNotAvail"
                                        value="unavailable"
                                    >

                                    <label class="form-check-label small" for="statusNotAvail">
                                        <?php echo __('not_available'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. ADDITIONAL INFORMATION -->
                    <div class="card-custom">
                        <div class="section-title">
                            <i class="fa-solid fa-circle-info"></i>
                            <?php echo __('sec_additional'); ?>
                        </div>

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label">
                                    <?php echo __('fuel_type'); ?>
                                </label>

                                <select name="fuel_type" class="form-select">
                                    <option value="" disabled selected>
                                        <?php echo __('select_fuel'); ?>
                                    </option>

                                    <option value="Diesel">Diesel</option>
                                    <option value="Petrol">Petrol</option>
                                    <option value="Electric">Electric</option>
                                    <option value="Manual">Manual / None</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    <?php echo __('power_hp'); ?>
                                </label>

                                <input
                                    type="number"
                                    name="power_hp"
                                    class="form-control"
                                    placeholder="<?php echo __('power_hp_ph'); ?>"
                                >
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    <?php echo __('working_hours'); ?>
                                </label>

                                <input
                                    type="number"
                                    name="working_hours"
                                    class="form-control"
                                    placeholder="<?php echo __('working_hours_ph'); ?>"
                                >
                            </div>

                            <div class="col-12 mt-3">
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_recommended"
                                        id="markRecommended"
                                    >

                                    <label
                                        class="form-check-label fw-semibold small"
                                        for="markRecommended"
                                    >
                                        <?php echo __('mark_recommended'); ?>
                                    </label>

                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?php echo __('recommended_desc'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">

                    <!-- 3. EQUIPMENT IMAGES -->
                    <div class="card-custom">
                        <div class="section-title">
                            <i class="fa-regular fa-image"></i>
                            <?php echo __('sec_images'); ?>
                        </div>

                        <div
                            class="image-dropzone"
                            onclick="document.getElementById('fileInput').click();"
                        >
                            <i class="fa-solid fa-cloud-arrow-up fa-2x text-secondary mb-2"></i>

                            <div class="fw-bold small">
                                <?php echo __('drag_drop'); ?>
                            </div>

                            <div class="text-muted small mb-2">
                                <?php echo __('or'); ?>
                            </div>

                            <button
                                type="button"
                                class="btn btn-teal btn-sm px-3 mb-1"
                            >
                                <?php echo __('choose_files'); ?>
                            </button>

                            <input
                                type="file"
                                name="image"
                                id="fileInput"
                                class="d-none"
                                accept="image/*"
                                onchange="previewFile(this)"
                            >

                            <div class="text-muted" style="font-size: 0.7rem;">
                                <?php echo __('image_specs'); ?>
                            </div>

                            <div
                                id="filePreview"
                                class="mt-2 text-success fw-bold small"
                            ></div>
                        </div>
                    </div>

                    <!-- 4. SERVICE AREAS -->
                    <div class="card-custom">
                        <div class="section-title">
                            <i class="fa-solid fa-location-dot"></i>
                            <?php echo __('sec_service_areas'); ?>
                        </div>

                        <label class="form-label">
                            <?php echo __('select_service_areas'); ?>
                            <span class="text-danger">*</span>
                        </label>

                        <div
                            class="d-flex flex-column gap-2 mt-1"
                            id="serviceAreasContainer"
                        >
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="service_areas[]"
                                    value="Bengaluru Rural"
                                    id="area1"
                                    checked
                                >

                                <label class="form-check-label small" for="area1">
                                    Bengaluru Rural
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="service_areas[]"
                                    value="Bengaluru Urban"
                                    id="area2"
                                >

                                <label class="form-check-label small" for="area2">
                                    Bengaluru Urban
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="service_areas[]"
                                    value="Kolar"
                                    id="area3"
                                >

                                <label class="form-check-label small" for="area3">
                                    Kolar
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="service_areas[]"
                                    value="Chikkaballapura"
                                    id="area4"
                                >

                                <label class="form-check-label small" for="area4">
                                    Chikkaballapura
                                </label>
                            </div>

                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="service_areas[]"
                                    value="Tumakuru"
                                    id="area5"
                                >

                                <label class="form-check-label small" for="area5">
                                    Tumakuru
                                </label>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button
                                type="button"
                                class="btn btn-link text-teal text-decoration-none p-0 small fw-bold"
                                onclick="addCustomArea()"
                                style="color: var(--navy-primary);"
                            >
                                <?php echo __('add_custom_area'); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FORM ACTIONS -->
            <div class="d-flex justify-content-end gap-2 mt-2">
                <button
                    type="reset"
                    class="btn btn-light border px-4"
                >
                    <i class="fa-solid fa-rotate-left me-1"></i>
                    <?php echo __('reset'); ?>
                </button>

                <button
                    type="submit"
                    name="submit_equipment"
                    class="btn btn-teal px-4"
                >
                    <i class="fa-regular fa-floppy-disk me-1"></i>
                    <?php echo __('save_equipment'); ?>
                </button>
            </div>

        </form>
    </div>

    <script>
        function previewFile(input) {
            const preview = document.getElementById('filePreview');

            if (input.files && input.files[0]) {
                preview.textContent = "Selected: " + input.files[0].name;
            }
        }

        function addCustomArea() {
            const areaName = prompt(
                "<?php echo __('prompt_custom_area'); ?>"
            );

            if (areaName && areaName.trim() !== "") {
                const container = document.getElementById('serviceAreasContainer');
                const newId = 'area_' + Date.now();
                const div = document.createElement('div');

                div.className = 'form-check';

                div.innerHTML =
                    `<input class="form-check-input"
                            type="checkbox"
                            name="service_areas[]"
                            value="${areaName.trim()}"
                            id="${newId}"
                            checked>
                     <label class="form-check-label small"
                            for="${newId}">
                        ${areaName.trim()}
                     </label>`;

                container.appendChild(div);
            }
        }
    </script>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
    ></script>

</body>
</html>
