<?php
session_start();
require_once 'includes/lang.php';
require_once 'includes/config.php';// Reusing existing project database connection file

// Authentication Check: Ensure lender is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lender') {
    header("Location: login.php");
    exit();
}
$lender_id = $_SESSION['user_id'];

// Handle Status Updates (Accept / Reject)
if (isset($_GET['action'], $_GET['id']) && in_array($_GET['action'], ['accept', 'reject'])) {
    $action_id = intval($_GET['id']);
    $new_status = ($_GET['action'] === 'accept') ? 'Accepted' : 'Rejected';

    $update_sql = "UPDATE bookings b 
                   JOIN equipment e ON b.equipment_id = e.equipment_id 
                   SET b.status = ? 
                   WHERE b.booking_id = ? AND e.lender_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $new_status, $action_id, $lender_id);
    $update_stmt->execute();
    
    header("Location: rental_request.php");
    exit();
}

// Fetch Lender Details for Dynamic Header
$lender_query = "SELECT full_name FROM users WHERE user_id = ?";
$lender_stmt = $conn->prepare($lender_query);
$lender_stmt->bind_param("i", $lender_id);
$lender_stmt->execute();
$lender_res = $lender_stmt->get_result()->fetch_assoc();
$lender_name = $lender_res['full_name'] ?? 'Lender';

// Fetch Summary Statistics dynamically
$stats_query = "SELECT 
    COUNT(b.booking_id) as total_requests,
    SUM(CASE WHEN b.status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN b.status = 'Accepted' AND MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as accepted_month,
    SUM(CASE WHEN b.status = 'Rejected' AND MONTH(b.created_at) = MONTH(CURRENT_DATE()) AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as rejected_month
    FROM bookings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    WHERE e.lender_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $lender_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('page_title_rental_requests'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8f9fa; color: #333; }
        .main-header { background: #fff; display: flex; justify-content: space-between; align-items: center; padding: 12px 25px; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; z-index: 1000; }
        .logo-container { display: flex; align-items: center; gap: 10px; color: #2e7d32; font-weight: bold; font-size: 18px; }
        .logo-icon { font-size: 24px; }
        .logo-title small { display: block; font-size: 9px; color: #666; letter-spacing: 1px; }
        .header-right-controls { display: flex; align-items: center; gap: 20px; }
        .language-selector select { padding: 6px 12px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff; font-size: 13px; color: #333; cursor: pointer; outline: none; }
        .user-profile-menu { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .user-info .user-name { display: block; font-size: 13px; font-weight: bold; }
        .user-info .user-role { font-size: 11px; color: #666; }
        .dashboard-container { display: flex; min-height: calc(100vh - 65px); }
        .sidebar { width: 240px; background: #fff; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 20px; color: #333; text-decoration: none; font-size: 14px; transition: 0.2s; }
        .sidebar-menu li:hover a, .sidebar-menu li.active a { background: #e8f5e9; color: #2e7d32; font-weight: 500; border-left: 4px solid #2e7d32; }
        .sidebar-menu li.logout-item { margin-top: 20px; border-top: 1px solid #eee; }
        .main-content { flex: 1; padding: 25px; background: #f8f9fa; overflow-x: auto; }
        .content-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .content-header-row h1 { font-size: 22px; color: #222; }
        .content-header-row p { font-size: 13px; color: #666; }
        .header-filters { display: flex; gap: 10px; align-items: center; }
        .filter-select, .search-box { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-size: 13px; outline: none; background: white; }
        .stats-cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; display: flex; align-items: center; gap: 15px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .bg-green { background: #2e7d32; }
        .bg-blue { background: #1976d2; }
        .bg-orange { background: #f57c00; }
        .bg-red { background: #c62828; }
        .stat-title { font-size: 12px; color: #666; display: block; }
        .stat-value { font-size: 18px; font-weight: bold; color: #222; margin: 3px 0; }
        .stat-desc { font-size: 11px; color: #888; }
        .table-card { background: white; border-radius: 10px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        .data-table th { background: #fafafa; padding: 15px; font-weight: 600; color: #555; border-bottom: 1px solid #eee; }
        .data-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .table-equipment-info { display: flex; align-items: center; gap: 12px; }
        .eq-thumb { width: 45px; height: 45px; border-radius: 6px; object-fit: cover; border: 1px solid #eee; }
        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-accepted { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .action-buttons { display: flex; gap: 6px; }
        .btn-action { padding: 6px 10px; border-radius: 5px; font-size: 11px; cursor: pointer; border: 1px solid #ddd; background: white; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
        .btn-accept { color: #2e7d32; border-color: #c8e6c9; background: #e8f5e9; }
        .btn-reject { color: #c62828; border-color: #ffcdd2; background: #ffebee; }
        .btn-view { color: #1976d2; border-color: #bbdefb; background: #e3f2fd; }
        .pagination-footer { padding: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #666; background: #fafafa; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo-container">
            <i class="fa-solid fa-tractor logo-icon"></i>
            <span class="logo-title">AGRICULTURE <small>EQUIPMENT RENTAL SYSTEM</small></span>
        </div>
        <div class="header-right-controls">
            <div class="language-selector">
                <select id="langSelect" onchange="window.location.href='?lang=' + this.value">
                    <option value="en" <?php echo ($current_lang == 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="kn" <?php echo ($current_lang == 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                    <option value="hi" <?php echo ($current_lang == 'hi') ? 'selected' : ''; ?>>हिंदी</option>
                </select>
            </div>
            <div class="user-profile-menu">
                <img src="assets/images/default_avatar.png" alt="Profile" class="avatar">
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($lender_name); ?></span>
                    <span class="user-role"><?php echo __('lender_role'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="lender_dashboard.php"><i class="fa-solid fa-chart-pie"></i> <span><?php echo __('menu_dashboard'); ?></span></a></li>
                <li><a href="add_item.php"><i class="fa-solid fa-plus"></i> <span><?php echo __('menu_add_equipment'); ?></span></a></li>
                <li><a href="my_equipment.php"><i class="fa-solid fa-tractor"></i> <span><?php echo __('menu_my_equipment'); ?></span></a></li>
                <li class="active"><a href="rental_request.php"><i class="fa-solid fa-star"></i> <span><?php echo __('menu_rental_requests'); ?></span></a></li>
                <li><a href="active_rentals.php"><i class="fa-solid fa-calendar-check"></i> <span><?php echo __('menu_active_rentals'); ?></span></a></li>
                <li><a href="rental_history.php"><i class="fa-solid fa-clock-rotate-left"></i> <span><?php echo __('menu_rental_history'); ?></span></a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> <span><?php echo __('menu_profile'); ?></span></a></li>
                <li class="logout-item"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span><?php echo __('menu_logout'); ?></span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="content-header-row">
                <div>
                    <h1><?php echo __('page_title_rental_requests'); ?></h1>
                    <p><?php echo __('page_subtitle_rental_requests'); ?></p>
                </div>
                <div class="header-filters">
                    <input type="text" id="tableSearch" class="search-box" placeholder="Search request..." onkeyup="filterTable()">
                    <select class="filter-select">
                        <option><?php echo __('filter_all_requests'); ?></option>
                    </select>
                </div>
            </div>

            <div class="stats-cards-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="stat-details">
                        <span class="stat-title"><?php echo __('stat_total_requests'); ?></span>
                        <h2 class="stat-value"><?php echo $stats['total_requests'] ?? 0; ?></h2>
                        <span class="stat-desc"><?php echo __('stat_all_time'); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-orange"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-details">
                        <span class="stat-title"><?php echo __('stat_pending_requests'); ?></span>
                        <h2 class="stat-value"><?php echo $stats['pending_requests'] ?? 0; ?></h2>
                        <span class="stat-desc"><?php echo __('stat_awaiting_response'); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-check"></i></div>
                    <div class="stat-details">
                        <span class="stat-title"><?php echo __('stat_accepted_requests'); ?></span>
                        <h2 class="stat-value"><?php echo $stats['accepted_month'] ?? 0; ?></h2>
                        <span class="stat-desc"><?php echo __('stat_this_month'); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-red"><i class="fa-solid fa-xmark"></i></div>
                    <div class="stat-details">
                        <span class="stat-title"><?php echo __('stat_rejected_requests'); ?></span>
                        <h2 class="stat-value"><?php echo $stats['rejected_month'] ?? 0; ?></h2>
                        <span class="stat-desc"><?php echo __('stat_this_month'); ?></span>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th><?php echo __('table_col_request_id'); ?></th>
                            <th><?php echo __('table_col_equipment'); ?></th>
                            <th><?php echo __('table_col_renter'); ?></th>
                            <th><?php echo __('table_col_rental_period'); ?></th>
                            <th><?php echo __('table_col_days'); ?></th>
                            <th><?php echo __('table_col_total_amount'); ?></th>
                            <th><?php echo __('table_col_status'); ?></th>
                            <th><?php echo __('table_col_requested_on'); ?></th>
                            <th><?php echo __('table_col_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT b.*, e.title as eq_title, e.category as eq_cat, e.image as eq_image, 
                                       u.full_name as renter_name, u.phone as renter_phone 
                                FROM bookings b 
                                JOIN equipment e ON b.equipment_id = e.equipment_id 
                                JOIN users u ON b.renter_id = u.user_id 
                                WHERE e.lender_id = ? 
                                ORDER BY b.booking_id DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $lender_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $total_rows = $result->num_rows;

                        if ($total_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $status_class = 'status-pending';
                                if ($row['status'] === 'Accepted') $status_class = 'status-accepted';
                                if ($row['status'] === 'Rejected') $status_class = 'status-rejected';
                        ?>
                        <tr>
                            <td><strong>REQ<?php echo $row['booking_id']; ?></strong></td>
                            <td>
                                <div class="table-equipment-info">
                                    <img src="uploads/<?php echo htmlspecialchars($row['eq_image']); ?>" alt="Equipment" class="eq-thumb" onerror="this.src='assets/images/default.png'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($row['eq_title']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($row['eq_cat']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($row['renter_name']); ?></span><br>
                                    <small><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($row['renter_phone']); ?></small>
                                </div>
                            </td>
                            <td><?php echo date('M d', strtotime($row['start_date'])) . ' – ' . date('M d, Y', strtotime($row['end_date'])); ?></td>
                            <td><?php echo $row['total_days']; ?></td>
                            <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><span class="badge-status <?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['created_at'] ?? 'now')); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <a href="rental_request.php?action=accept&id=<?php echo $row['booking_id']; ?>" class="btn-action btn-accept"><i class="fa-solid fa-check"></i></a>
                                        <a href="rental_request.php?action=reject&id=<?php echo $row['booking_id']; ?>" class="btn-action btn-reject"><i class="fa-solid fa-xmark"></i></a>
                                    <?php endif; ?>
                                    <a href="booking_details.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-action btn-view"><i class="fa-regular fa-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="9" style="text-align:center; padding: 25px;">' . __('no_rental_requests') . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <div class="pagination-footer">
                    <span><?php echo "Showing 1 to {$total_rows} of {$total_rows} requests"; ?></span>
                </div>
            </div>
        </main>
    </div>

    <script>
        function filterTable() {
            let input = document.getElementById("tableSearch").value.toLowerCase();
            let table = document.getElementById("requestsTable");
            let tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let text = tr[i].textContent || tr[i].innerText;
                tr[i].style.display = text.toLowerCase().indexOf(input) > -1 ? "" : "none";
            }
        }
    </script>
</body>
</html>