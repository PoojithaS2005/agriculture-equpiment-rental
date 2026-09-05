<?php
session_start();

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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    // Defaulting to lender ID 7 from your seed data for testing if session is absent
    $_SESSION['user_id'] = 7;
}
$lender_id = $_SESSION['user_id'];

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Rentals - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Core Reset & Dashboard Layout */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8f9fa; color: #333; }
        
        /* Header */
        .main-header { background: #fff; display: flex; justify-content: space-between; align-items: center; padding: 12px 25px; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; z-index: 1000; }
        .logo-container { display: flex; align-items: center; gap: 10px; color: #2e7d32; font-weight: bold; font-size: 18px; }
        .logo-icon { font-size: 24px; }
        .logo-title small { display: block; font-size: 9px; color: #666; letter-spacing: 1px; }
        .header-search-bar { display: flex; align-items: center; background: #f1f3f4; padding: 8px 15px; border-radius: 20px; width: 350px; gap: 10px; }
        .header-search-bar input { border: none; background: transparent; outline: none; width: 100%; font-size: 14px; }
        .header-right-controls { display: flex; align-items: center; gap: 20px; }
        
        /* Updated Language Dropdown Styling */
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

        .notification-icon { position: relative; font-size: 18px; cursor: pointer; }
        .notification-icon .badge { position: absolute; top: -5px; right: -8px; background: #2e7d32; color: white; font-size: 10px; padding: 2px 5px; border-radius: 10px; }
        .user-profile-menu { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .user-info .user-name { display: block; font-size: 13px; font-weight: bold; }
        .user-info .user-role { font-size: 11px; color: #666; }

        /* Dashboard Container & Sidebar */
        .dashboard-container { display: flex; min-height: calc(100vh - 65px); }
        .sidebar { width: 240px; background: #fff; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #333; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .sidebar-menu li:hover a, .sidebar-menu li.active a { background: #e8f5e9; color: #2e7d32; font-weight: 500; border-left: 4px solid #2e7d32; }
        .sidebar-menu li.logout-item { margin-top: 20px; border-top: 1px solid #eee; }
        .sidebar-help-box { background: #e8f5e9; margin: 15px; padding: 15px; border-radius: 10px; text-align: center; }
        .sidebar-help-box h4 { font-size: 13px; color: #1b5e20; margin-bottom: 5px; }
        .sidebar-help-box p { font-size: 11px; color: #555; margin-bottom: 10px; }
        .btn-contact-support { background: #2e7d32; color: white; border: none; padding: 6px 12px; border-radius: 5px; font-size: 11px; cursor: pointer; }

        /* Main Content */
        .main-content { flex: 1; padding: 25px; background: #f8f9fa; overflow-x: auto; }
        .content-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .content-header-row h1 { font-size: 22px; color: #222; }
        .content-header-row p { font-size: 13px; color: #666; }
        .btn-download-report { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 8px 15px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }

        /* Stats Grid */
        .stats-cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #eee; }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .bg-green { background: #2e7d32; }
        .bg-blue { background: #1976d2; }
        .bg-orange { background: #f57c00; }
        .bg-purple { background: #7b1fa2; }
        .stat-title { font-size: 12px; color: #666; display: block; }
        .stat-value { font-size: 18px; font-weight: bold; color: #222; margin: 3px 0; }
        .stat-desc { font-size: 11px; color: #888; }

        /* Table Card */
        .table-card { background: white; border-radius: 10px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        .data-table th { background: #fafafa; padding: 15px; font-weight: 600; color: #555; border-bottom: 1px solid #eee; }
        .data-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .table-equipment-info { display: flex; align-items: center; gap: 12px; }
        .eq-thumb { width: 45px; height: 45px; border-radius: 6px; object-fit: cover; border: 1px solid #eee; }
        
        /* Badges & Actions */
        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-due { background: #fff3e0; color: #f57c00; }
        .status-overdue { background: #ffebee; color: #c62828; }
        .action-buttons { display: flex; gap: 8px; }
        .btn-action { padding: 6px 10px; border-radius: 5px; font-size: 11px; cursor: pointer; border: 1px solid #ddd; background: white; display: flex; align-items: center; gap: 5px; }
        .btn-view { color: #1976d2; border-color: #bbdefb; background: #e3f2fd; }
        .btn-track { color: #2e7d32; border-color: #c8e6c9; background: #e8f5e9; }
    </style>
</head>
<body>
    <!-- Top Navigation Header -->
    <header class="main-header">
        <div class="logo-container">
            <i class="fa-solid fa-tractor logo-icon"></i>
            <span class="logo-title">AGRICULTURE <small>EQUIPMENT RENTAL SYSTEM</small></span>
        </div>
        <form action="search_equipment.php" method="GET" class="header-search-bar">
            <i class="fa-solid fa-bars menu-toggle-icon"></i>
            <i class="fa-solid fa-search search-icon"></i>
            <input type="text" name="query" id="searchInput" placeholder="Search equipment, renters, bookings..." data-i18n-placeholder="search_placeholder">
            <i class="fa-solid fa-microphone mic-icon"></i>
        </form>
        <div class="header-right-controls">
            <!-- Language Dropdown Menu -->
            <div class="language-selector">
                <select id="langSelect" onchange="changeLanguage(this.value)">
                    <option value="en" selected>English</option>
                    <option value="kn">ಕನ್ನಡ</option>
                    <option value="hi">हिंदी</option>
                </select>
            </div>
            <div class="notification-icon">
                <i class="fa-regular fa-bell"></i>
                <span class="badge">3</span>
            </div>
            <div class="user-profile-menu">
                <img src="assets/images/default_avatar.png" alt="Profile" class="avatar">
                <div class="user-info">
                    <span class="user-name">Tejomurthy</span>
                    <span class="user-role" data-i18n="lender_role">Lender</span>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Left Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="lender_dashboard.php"><i class="fa-solid fa-chart-pie"></i> <span data-i18n="menu_dashboard">Dashboard</span></a></li>
                <li><a href="add_item.php"><i class="fa-solid fa-plus"></i> <span data-i18n="menu_add_equipment">Add Equipment</span></a></li>
                <li><a href="my_equipment.php"><i class="fa-solid fa-tractor"></i> <span data-i18n="menu_my_equipment">My Equipment</span></a></li>
                <li><a href="rental_requests.php"><i class="fa-solid fa-star"></i> <span data-i18n="menu_rental_requests">Rental Requests</span></a></li>
                <li class="active"><a href="active_rentals.php"><i class="fa-solid fa-calendar-check"></i> <span data-i18n="menu_active_rentals">Active Rentals</span></a></li>
                <li><a href="rental_history.php"><i class="fa-solid fa-clock-rotate-left"></i> <span data-i18n="menu_rental_history">Rental History</span></a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> <span data-i18n="menu_profile">Profile</span></a></li>
                <li class="logout-item"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="menu_logout">Logout</span></a></li>
            </ul>

            <!-- Need Help Box -->
            <div class="sidebar-help-box">
                <div class="help-content">
                    <h4 data-i18n="sidebar_help_title">Need Help?</h4>
                    <p data-i18n="sidebar_help_desc">We are here to help you for any queries.</p>
                    <button class="btn-contact-support" data-i18n="sidebar_contact_btn">Contact Support</button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="content-header-row">
                <div>
                    <h1 data-i18n="page_title_active_rentals">Active Rentals</h1>
                    <p data-i18n="page_subtitle_active_rentals">Manage and track all your ongoing equipment rentals.</p>
                </div>
                <button class="btn-download-report"><i class="fa-solid fa-download"></i> <span data-i18n="btn_download_report">Download Report</span></button>
            </div>

            <!-- Dynamic Summary Cards -->
            <div class="stats-cards-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-calendar-days"></i></div>
                    <div class="stat-details">
                        <span class="stat-title" data-i18n="stat_total_active">Total Active Rentals</span>
                        <h2 class="stat-value"><?php echo $stats['total_active']; ?></h2>
                        <span class="stat-desc" data-i18n="stat_ongoing_rentals">Ongoing Rentals</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-tractor"></i></div>
                    <div class="stat-details">
                        <span class="stat-title" data-i18n="stat_total_equipment">Total Equipment</span>
                        <h2 class="stat-value"><?php echo $eq_stats['total_eq']; ?></h2>
                        <span class="stat-desc" data-i18n="stat_rented_out">Registered Units</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-details">
                        <span class="stat-title" data-i18n="stat_total_days">Total Days Rented</span>
                        <h2 class="stat-value"><?php echo $stats['total_days']; ?> Days</h2>
                        <span class="stat-desc" data-i18n="stat_across_rentals">Across all rentals</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="stat-details">
                        <span class="stat-title" data-i18n="stat_total_earned">Total Earned (So Far)</span>
                        <h2 class="stat-value">₹<?php echo number_format($stats['total_earned'], 2); ?></h2>
                        <span class="stat-desc" data-i18n="stat_from_active">From active rentals</span>
                    </div>
                </div>
            </div>

            <!-- Active Rentals Table Section -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th data-i18n="table_col_equipment">Equipment</th>
                                <th data-i18n="table_col_renter">Renter Details</th>
                                <th data-i18n="table_col_period">Rental Period</th>
                                <th data-i18n="table_col_address">Delivery Address</th>
                                <th data-i18n="table_col_days_left">Days Left</th>
                                <th data-i18n="table_col_amount">Amount (Total)</th>
                                <th data-i18n="table_col_status">Status</th>
                                <th data-i18n="table_col_action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch active bookings matching this lender's equipment using existing tables
                            $sql = "SELECT b.*, e.title as eq_title, e.category as eq_cat, e.brand_model, e.image as eq_image, 
                                           u.full_name as renter_name, u.phone as renter_phone 
                                    FROM bookings b 
                                    JOIN equipment e ON b.equipment_id = e.equipment_id 
                                    JOIN users u ON b.renter_id = u.user_id 
                                    WHERE e.lender_id = ? AND b.status IN ('Accepted', 'Delivered', 'Overdue')";
                            $stmt_bookings = $conn->prepare($sql);
                            $stmt_bookings->bind_param("i", $lender_id);
                            $stmt_bookings->execute();
                            $result = $stmt_bookings->get_result();

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // Automatic Days Left & Status Calculation
                                    $current_date = new DateTime();
                                    $end_date = new DateTime($row['end_date']);
                                    $interval = $current_date->diff($end_date);
                                    $days_diff = (int)$interval->format('%r%d');

                                    if ($days_diff > 0) {
                                        $days_left_text = $days_diff . " Days Left";
                                        $sub_text = "Return by " . date('d M Y', strtotime($row['end_date']));
                                        $status_class = 'status-active';
                                        $status_text = 'Active';
                                    } elseif ($days_diff === 0) {
                                        $days_left_text = "Due Today";
                                        $sub_text = "Return Today";
                                        $status_class = 'status-due';
                                        $status_text = 'Due Today';
                                    } else {
                                        $days_left_text = "Overdue";
                                        $sub_text = "Returned on " . date('d M Y', strtotime($row['end_date']));
                                        $status_class = 'status-overdue';
                                        $status_text = 'Overdue';
                                    }

                                    $per_day_price = ($row['total_days'] > 0) ? ($row['total_amount'] / $row['total_days']) : $row['total_amount'];
                            ?>
                            <tr>
                                <td>
                                    <div class="table-equipment-info">
                                        <img src="uploads/<?php echo htmlspecialchars($row['eq_image']); ?>" alt="Equipment" class="eq-thumb" onerror="this.src='assets/images/default.png'">
                                        <div>
                                            <strong><?php echo htmlspecialchars($row['eq_title']); ?></strong><br>
                                            <span><?php echo htmlspecialchars($row['eq_cat']); ?></span><br>
                                            <small><?php echo htmlspecialchars($row['brand_model']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="renter-info">
                                        <span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($row['renter_name']); ?></span><br>
                                        <small><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($row['phone_number'] ?? $row['renter_phone']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="rental-period">
                                        <span><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y', strtotime($row['start_date'])); ?></span><br>
                                        <small>to <?php echo date('d M Y', strtotime($row['end_date'])); ?> (<?php echo $row['total_days']; ?>d)</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="delivery-address">
                                        <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($row['delivery_address']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="days-left-cell">
                                        <strong><?php echo $days_left_text; ?></strong><br>
                                        <small><?php echo $sub_text; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-cell">
                                        <strong>₹<?php echo number_format($row['total_amount'], 2); ?></strong><br>
                                        <small>(₹<?php echo number_format($per_day_price, 0); ?>/day)</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $status_class; ?>">
                                        <i class="fa-solid fa-circle"></i> <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewDetails(<?php echo $row['booking_id']; ?>)">
                                            <i class="fa-regular fa-eye"></i> <span data-i18n="btn_view_details">View Details</span>
                                        </button>
                                        <button class="btn-action btn-track" onclick="trackEquipment(<?php echo $row['booking_id']; ?>)">
                                            <i class="fa-solid fa-location-crosshairs"></i> <span data-i18n="btn_track_equipment">Track</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="8" style="text-align:center; padding: 25px;" data-i18n="no_active_rentals">No active rentals found in your account.</td></tr>';
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
            window.location.href = `booking_details.php?booking_id=${bookingId}`;
        }
        function trackEquipment(bookingId) {
            alert("Tracking feature for booking session #" + bookingId + " is initialized through map services.");
        }
    </script>
</body>
</html>