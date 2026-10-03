<?php
session_start();
require_once 'includes/lang.php';

// Fix the Hindi Active Rentals translations without changing the shared language file.
// The shared lang.php contains two Hindi dictionaries, so the later one overrides
// the earlier Active Rentals entries. Restore only the Active Rentals keys needed here.
$translations['hi'] = array_merge($translations['hi'], [
    'lender_role' => 'ऋणदाता',
    'menu_dashboard' => 'डैशबोर्ड',
    'menu_add_equipment' => 'उपकरण जोड़ें',
    'menu_my_equipment' => 'मेरे उपकरण',
    'menu_rental_requests' => 'किराये के अनुरोध',
    'menu_active_rentals' => 'सक्रिय किराए',
    'menu_rental_history' => 'किराए का इतिहास',
    'menu_profile' => 'प्रोफ़ाइल',
    'menu_logout' => 'लॉग आउट',
    'sidebar_help_title' => 'सहायता चाहिए?',
    'sidebar_help_desc' => 'हम किसी भी प्रश्न के लिए आपकी सहायता के लिए यहाँ हैं।',
    'sidebar_contact_btn' => 'सहायता से संपर्क करें',
    'page_title_active_rentals' => 'सक्रिय किराए',
    'page_subtitle_active_rentals' => 'अपने सभी चल रहे उपकरणों के किराए को प्रबंधित और ट्रैक करें।',
    'stat_total_active' => 'कुल सक्रिय किराए',
    'stat_ongoing_rentals' => 'चल रहे किराए',
    'stat_total_equipment' => 'कुल उपकरण',
    'stat_rented_out' => 'पंजीकृत इकाइयाँ',
    'stat_total_days' => 'किराए पर दिए गए कुल दिन',
    'stat_across_rentals' => 'सभी किरायों में',
    'stat_total_earned' => 'कुल कमाई (अब तक)',
    'stat_from_active' => 'सक्रिय किरायों से',
    'table_col_equipment' => 'उपकरण',
    'table_col_renter' => 'किराएदार विवरण',
    'table_col_period' => 'किराए की अवधि',
    'table_col_address' => 'वितरण पता',
    'table_col_days_left' => 'शेष दिन',
    'table_col_amount' => 'राशि (कुल)',
    'table_col_status' => 'स्थिति',
    'table_col_action' => 'कार्रवाई',
    'btn_view_details' => 'विवरण देखें',
    'btn_track_equipment' => 'टरैक करें',
    'no_active_rentals' => 'आपके खाते में कोई सक्रिय किराया नहीं मिला.'
]);

// Active Rentals Hindi translations are defined here because lang.php currently
// contains two top-level Hindi dictionaries, and the later one overrides the first one.
$active_rentals_hi = [
    'lender_role' => 'ऋणदाता',
    'page_title_active_rentals' => 'सक्रिय किराए',
    'page_subtitle_active_rentals' => 'अपने सभी चल रहे उपकरणों के किराए को प्रबंधित और ट्रैक करें।',
    'stat_total_active' => 'कुल सक्रिय किराए',
    'stat_ongoing_rentals' => 'चल रहे किराए',
    'stat_total_equipment' => 'कुल उपकरण',
    'stat_rented_out' => 'पंजीकृत इकाइयाँ',
    'stat_total_days' => 'किराए पर दिए गए कुल दिन',
    'stat_across_rentals' => 'सभी किरायों में',
    'stat_total_earned' => 'कुल कमाई (अब तक)',
    'stat_from_active' => 'सक्रिय किरायों से',
    'table_col_equipment' => 'उपकरण',
    'table_col_renter' => 'किराएदार विवरण',
    'table_col_period' => 'किराए की अवधि',
    'table_col_address' => 'वितरण पता',
    'table_col_days_left' => 'शेष दिन',
    'table_col_amount' => 'राशि (कुल)',
    'table_col_status' => 'स्थिति',
    'table_col_action' => 'कार्रवाई',
    'btn_view_details' => 'विवरण देखें',
    'btn_track_equipment' => 'उपकरण ट्रैक करें',
    'no_active_rentals' => 'कोई सक्रिय किराया नहीं है',
    'days' => 'दिन',
    'days_left' => 'दिन शेष',
    'due_today' => 'आज देय',
    'return_today' => 'आज वापस करें',
    'overdue' => 'अतिदेय',
    'return_by' => 'वापसी की तारीख',
    'returned_on' => 'वापस किया गया',
    'active' => 'सक्रिय'
];

function active_rental__(string $key): string {
    global $current_lang, $active_rentals_hi;
    if ($current_lang === 'hi' && isset($active_rentals_hi[$key])) {
        return $active_rentals_hi[$key];
    }
    return __($key);
}

// Database connection configuration
$host = 'localhost';
$db   = 'agri_rental_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Authentication Check: Ensure lender is logged in
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'lender') {
    // Defaulting to lender ID 7 from your seed data for testing if session is absent
    $_SESSION['user_id'] = 7;
}
$lender_id = $_SESSION['user_id'];

$lender_name = $_SESSION['full_name'] ?? 'Tejomurthy';
$name_parts = preg_split('/\s+/', trim($lender_name));
if (count($name_parts) >= 2) {
    $lender_initials = strtoupper(
        substr($name_parts[0], 0, 1) .
        substr($name_parts[count($name_parts) - 1], 0, 1)
    );
} else {
    $lender_initials = strtoupper(substr($name_parts[0] ?? 'U', 0, 2));
}

// Fetch Dynamic Summary Statistics for this Lender
$stats_query = "SELECT 
                    COUNT(b.booking_id) as total_active,
                    COALESCE(SUM(b.total_amount), 0) as total_earned,
                    COALESCE(SUM(b.total_days), 0) as total_days
                FROM bookings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                WHERE e.lender_id = ? AND b.status IN ('Accepted', 'Delivered', 'Overdue')";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch Equipment count registered by this lender
$eq_count_query = "SELECT COUNT(*) as total_eq FROM equipment WHERE lender_id = ?";
$stmt_eq = $conn->prepare($eq_count_query);
$stmt_eq->bind_param("i", $lender_id);
$stmt_eq->execute();
$eq_stats = $stmt_eq->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(active_rental__('page_title_active_rentals')); ?> - Agriculture Equipment Rental System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
        }

        /* Header */
        .main-header {
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 25px;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-left: 250px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2e7d32;
            font-weight: bold;
            font-size: 18px;
        }

        .logo-icon {
            font-size: 24px;
        }

        .logo-title small {
            display: block;
            font-size: 9px;
            color: #666;
            letter-spacing: 1px;
        }

        .header-search-bar {
            display: flex;
            align-items: center;
            background: #f1f3f4;
            padding: 8px 15px;
            border-radius: 20px;
            width: 350px;
            gap: 10px;
        }

        .header-search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 14px;
        }

        .header-right-controls {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }

        .language-selector select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #fff;
            font-size: 13px;
            color: #333;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }

        .language-selector select:focus {
            border-color: #2e7d32;
        }

        .notification-icon {
            position: relative;
            font-size: 18px;
            cursor: pointer;
        }

        .notification-icon .badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #2e7d32;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
        }

        .user-profile-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #0f4c5c;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border: 2px solid #0f4c5c;
        }

        .user-info .user-name {
            display: block;
            font-size: 13px;
            font-weight: bold;
        }

        .user-info .user-role {
            font-size: 11px;
            color: #666;
        }

        /* Main Layout */
        .dashboard-container {
            display: block;
            min-height: calc(100vh - 65px);
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 25px;
            background: #f8f9fa;
            overflow-x: auto;
            min-height: calc(100vh - 65px);
        }

        .content-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .content-header-row h1 {
            font-size: 22px;
            color: #222;
        }

        .content-header-row p {
            font-size: 13px;
            color: #666;
        }

        .btn-download-report {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stats Grid */
        .stats-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #eee;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .bg-green {
            background: #2e7d32;
        }

        .bg-blue {
            background: #1976d2;
        }

        .bg-orange {
            background: #f57c00;
        }

        .bg-purple {
            background: #7b1fa2;
        }

        .stat-title {
            font-size: 12px;
            color: #666;
            display: block;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #222;
            margin: 3px 0;
        }

        .stat-desc {
            font-size: 11px;
            color: #888;
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #eee;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .data-table th {
            background: #fafafa;
            padding: 15px;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #eee;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
        }

        .table-equipment-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .eq-thumb {
            width: 45px;
            height: 45px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        /* Badges & Actions */
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-due {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-overdue {
            background: #ffebee;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
            border: 1px solid #ddd;
            background: white;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            color: #1976d2;
            border-color: #bbdefb;
            background: #e3f2fd;
        }

        .btn-track {
            color: #2e7d32;
            border-color: #c8e6c9;
            background: #e8f5e9;
        }

        @media (max-width: 1200px) {
            .stats-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 991px) {
            .main-header {
                margin-left: 220px;
            }

            .main-content {
                margin-left: 220px;
            }
        }

        @media (max-width: 768px) {
            .main-header {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .header-search-bar {
                display: none;
            }

            .stats-cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- SHARED LENDER SIDEBAR -->
    <?php include 'lender_sidebar.php'; ?>

    <!-- Top Navigation Header -->
    <header class="main-header">
        <div class="header-right-controls">

            <div class="language-selector">
                <select id="langSelect" onchange="window.location.href='active_rentals.php?lang=' + this.value;">
                    <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                    <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी</option>
                </select>
            </div>

            <a href="lender_notifications.php?lang=<?php echo urlencode($current_lang); ?>" class="notification-icon" style="color: #333; text-decoration: none;" title="Notifications">
                <i class="fa-regular fa-bell"></i>
                <span class="badge">3</span>
            </a>

            <div class="user-profile-menu">
                <div class="avatar"><?php echo htmlspecialchars($lender_initials); ?></div>

                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($lender_name); ?></span>
                    <span class="user-role">
                                                <?php echo active_rental__('lender_role'); ?>
                                            </span>
                </div>
            </div>

        </div>
    </header>

    <div class="dashboard-container">

        <!-- Main Content Area -->
        <main class="main-content">

            <div class="content-header-row">
                <div>
                    <h1>
                                                <?php echo active_rental__('page_title_active_rentals'); ?>
                                            </h1>

                    <p>
                                                <?php echo active_rental__('page_subtitle_active_rentals'); ?>
                                            </p>
                </div>

            </div>

            <!-- Dynamic Summary Cards -->
            <div class="stats-cards-grid">

                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_active'); ?>
                                            </span>

                        <h2 class="stat-value">
                            <?php echo $stats['total_active']; ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_ongoing_rentals'); ?>
                                            </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fa-solid fa-tractor"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_equipment'); ?>
                                            </span>

                        <h2 class="stat-value">
                            <?php echo $eq_stats['total_eq']; ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_rented_out'); ?>
                                            </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fa-solid fa-clock"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_days'); ?>
                                            </span>

                        <h2 class="stat-value">
                            <?php echo $stats['total_days']; ?> <?php echo active_rental__('days'); ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_across_rentals'); ?>
                                            </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_earned'); ?>
                                            </span>

                        <h2 class="stat-value">
                            ₹<?php echo number_format($stats['total_earned'], 2); ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_from_active'); ?>
                                            </span>
                    </div>
                </div>

            </div>

            <!-- Active Rentals Table Section -->
            <div class="table-card">

                <div class="table-responsive">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>
                                                <?php echo active_rental__('table_col_equipment'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_renter'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_period'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_address'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_days_left'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_amount'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_status'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_action'); ?>
                                            </th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            // Fetch active bookings matching this lender's equipment
                            $sql = "SELECT b.*, 
                                           e.title as eq_title,
                                           e.category as eq_cat,
                                           e.brand_model,
                                           e.image as eq_image,
                                           u.full_name as renter_name,
                                           u.phone as renter_phone
                                    FROM bookings b
                                    JOIN equipment e 
                                        ON b.equipment_id = e.equipment_id
                                    JOIN users u 
                                        ON b.renter_id = u.user_id
                                    WHERE e.lender_id = ?
                                    AND b.status IN ('Accepted', 'Delivered', 'Overdue')";

                            $stmt_bookings = $conn->prepare($sql);
                            $stmt_bookings->bind_param("i", $lender_id);
                            $stmt_bookings->execute();

                            $result = $stmt_bookings->get_result();

                            if ($result->num_rows > 0) {

                                while ($row = $result->fetch_assoc()) {

                                    // Automatic Days Left & Status Calculation
                                    $current_date = new DateTime();
                                    $end_date = new DateTime($row['end_date']);

                                    $interval = $current_date->diff($end_date);
                                    $days_diff = (int)$interval->format('%r%d');

                                    if ($days_diff > 0) {

                                        $days_left_text = $days_diff . " " . active_rental__('days_left');
                                        $sub_text = active_rental__('return_by') . " " . date(
                                            'd M Y',
                                            strtotime($row['end_date'])
                                        );

                                        $status_class = 'status-active';
                                        $status_text = active_rental__('active');

                                    } elseif ($days_diff === 0) {

                                        $days_left_text = active_rental__('due_today');
                                        $sub_text = active_rental__('return_today');

                                        $status_class = 'status-due';
                                        $status_text = active_rental__('due_today');

                                    } else {

                                        $days_left_text = active_rental__('overdue');
                                        $sub_text = active_rental__('returned_on') . " " . date(
                                            'd M Y',
                                            strtotime($row['end_date'])
                                        );

                                        $status_class = 'status-overdue';
                                        $status_text = active_rental__('overdue');
                                    }

                                    $per_day_price =
                                        ($row['total_days'] > 0)
                                        ? ($row['total_amount'] / $row['total_days'])
                                        : $row['total_amount'];
                            ?>

                            <tr>

                                <td>
                                    <div class="table-equipment-info">

                                        <img
                                            src="uploads/<?php echo htmlspecialchars($row['eq_image']); ?>"
                                            alt="Equipment"
                                            class="eq-thumb"
                                            onerror="this.src='assets/images/default.png'"
                                        >

                                        <div>
                                            <strong>
                                                <?php echo htmlspecialchars($row['eq_title']); ?>
                                            </strong>

                                            <br>

                                            <span>
                                                <?php echo htmlspecialchars($row['eq_cat']); ?>
                                            </span>

                                            <br>

                                            <small>
                                                <?php echo htmlspecialchars($row['brand_model']); ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <div class="renter-info">

                                        <span>
                                            <i class="fa-regular fa-user"></i>
                                            <?php echo htmlspecialchars($row['renter_name']); ?>
                                        </span>

                                        <br>

                                        <small>
                                            <i class="fa-solid fa-phone"></i>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['phone_number'] ?? $row['renter_phone']
                                            );
                                            ?>
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <div class="rental-period">

                                        <span>
                                            <i class="fa-regular fa-calendar"></i>
                                            <?php
                                            echo date(
                                                'd M Y',
                                                strtotime($row['start_date'])
                                            );
                                            ?>
                                        </span>

                                        <br>

                                        <small>
                                            to
                                            <?php
                                            echo date(
                                                'd M Y',
                                                strtotime($row['end_date'])
                                            );
                                            ?>

                                            (<?php echo $row['total_days']; ?>d)
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <div class="delivery-address">

                                        <span>
                                            <i class="fa-solid fa-location-dot"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $row['delivery_address']
                                            );
                                            ?>
                                        </span>

                                    </div>
                                </td>

                                <td>
                                    <div class="days-left-cell">

                                        <strong>
                                            <?php echo $days_left_text; ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?php echo $sub_text; ?>
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <div class="amount-cell">

                                        <strong>
                                            ₹<?php
                                            echo number_format(
                                                $row['total_amount'],
                                                2
                                            );
                                            ?>
                                        </strong>

                                        <br>

                                        <small>
                                            (₹<?php
                                            echo number_format(
                                                $per_day_price,
                                                0
                                            );
                                            ?>/day)
                                        </small>

                                    </div>
                                </td>

                                <td>

                                    <span class="badge-status <?php echo $status_class; ?>">

                                        <i class="fa-solid fa-circle"></i>

                                        <?php echo $status_text; ?>

                                    </span>

                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <button
                                            class="btn-action btn-view"
                                            onclick="viewDetails(<?php echo $row['booking_id']; ?>)"
                                        >
                                            <i class="fa-regular fa-eye"></i>

                                            <span>
                                                <?php echo active_rental__('btn_view_details'); ?>
                                            </span>
                                        </button>

                                        <button
                                            class="btn-action btn-track"
                                            onclick="trackEquipment(<?php echo $row['booking_id']; ?>)"
                                        >
                                            <i class="fa-solid fa-location-crosshairs"></i>

                                            <span>
                                                <?php echo active_rental__('btn_track_equipment'); ?>
                                            </span>
                                        </button>

                                    </div>

                                </td>

                            </tr>

                            <?php
                                }

                            } else {

                                echo '<tr>
                                    <td
                                        colspan="8"
                                        style="text-align:center; padding:25px;"
                                    >' . htmlspecialchars(active_rental__('no_active_rentals')) . '</td>
                                </tr>';

                            }
                            ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

    </div>

    <script>
        function viewDetails(bookingId) {
            window.location.href =
                `lender_booking_details.php?booking_id=${bookingId}`;
        }

        function trackEquipment(bookingId) {
            alert(
                "Tracking feature for booking session #" +
                bookingId +
                " is initialized through map services."
            );
        }
    </script>

</body>
</html>