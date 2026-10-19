<?php
session_start();
require_once 'includes/lang.php';
require_once 'includes/config.php';

// Authentication Check: Ensure lender is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'lender') {
    header("Location: login.php");
    exit();
}
$lender_id = (int) $_SESSION['user_id'];

// Handle Status Updates (Accept / Reject)
if (isset($_GET['action'], $_GET['id']) && in_array($_GET['action'], ['accept', 'reject'], true)) {
    $action_id = (int) $_GET['id'];
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

$lender_name_parts = preg_split('/\s+/', trim($lender_name));
if (count($lender_name_parts) >= 2) {
    $lender_initials = strtoupper(
        substr($lender_name_parts[0], 0, 1) .
        substr($lender_name_parts[count($lender_name_parts) - 1], 0, 1)
    );
} else {
    $lender_initials = strtoupper(substr($lender_name_parts[0] ?? 'L', 0, 2));
}

// Fetch Summary Statistics dynamically
$stats_query = "SELECT
    COUNT(b.booking_id) as total_requests,
    SUM(CASE WHEN b.status = 'Pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN b.status = 'Accepted'
        AND MONTH(b.created_at) = MONTH(CURRENT_DATE())
        AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as accepted_month,
    SUM(CASE WHEN b.status = 'Rejected'
        AND MONTH(b.created_at) = MONTH(CURRENT_DATE())
        AND YEAR(b.created_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as rejected_month
    FROM bookings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    WHERE e.lender_id = ?
      AND LOWER(TRIM(b.status)) NOT IN ('completed', 'returned', 'cancelled', 'canceled')";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $lender_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$total_rows = 0;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang ?? 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('page_title_rental_requests'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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

        body {
            background-color: var(--bg-body);
            color: var(--text-dark);
        }

        .main-header {
            background: var(--card-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 25px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 65px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--navy-primary);
            font-weight: bold;
            font-size: 18px;
        }

        .logo-icon { font-size: 24px; }

        .logo-title small {
            display: block;
            font-size: 9px;
            color: var(--text-muted);
            letter-spacing: 1px;
        }

        .header-right-controls {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }

        .header-notification {
            color: var(--navy-primary);
            text-decoration: none;
            font-size: 18px;
        }

        .language-selector select {
            padding: 8px 12px;
            border-radius: 7px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            font-size: 14px;
            color: var(--text-dark);
            cursor: pointer;
            outline: none;
        }

        .user-profile-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--navy-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            border: 2px solid var(--navy-primary);
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .user-info .user-name {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: var(--text-dark);
        }

        .user-info .user-role {
            font-size: 12px;
            color: var(--text-muted);
        }

        .main-content {
            margin-left: 250px;
            min-height: calc(100vh - 65px);
            padding: 25px 30px;
            background: var(--bg-body);
            overflow-x: auto;
        }

        .content-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 25px;
        }

        .content-header-row h1 {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .content-header-row p {
            font-size: 15px;
            color: var(--text-muted);
        }

        .header-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-select, .search-box {
            padding: 11px 14px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 14px;
            outline: none;
            background: var(--card-bg);
            color: var(--text-dark);
        }

        .search-box { min-width: 205px; }
        .filter-select:focus, .search-box:focus {
            border-color: var(--blue-accent);
            box-shadow: 0 0 0 3px rgba(141, 184, 240, .22);
        }

        .stats-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 22px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(11, 52, 120, .045);
            min-width: 0;
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .bg-green { background: #2e7d32; }
        .bg-blue { background: var(--navy-primary); }
        .bg-orange { background: #f57c00; }
        .bg-red { background: #c62828; }

        .stat-title {
            font-size: 14px;
            color: var(--text-muted);
            display: block;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 5px 0;
        }

        .stat-desc {
            font-size: 13px;
            color: var(--text-muted);
        }

        .table-card {
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(11, 52, 120, .045);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .data-table th {
            background: #EEF5FF;
            padding: 16px 15px;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .data-table td {
            padding: 16px 15px;
            border-bottom: 1px solid #E1ECFA;
            vertical-align: middle;
            color: var(--text-dark);
        }

        .data-table tbody tr:hover { background: #F0F6FF; }

        .table-equipment-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 180px;
        }

        .eq-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        .table-equipment-info small,
        .data-table td small { color: var(--text-muted); }

        .badge-status {
            padding: 5px 11px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        /* Preserve semantic status colors. */
        .status-pending { background: #fff3e0; color: #f57c00; }
        .status-accepted { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }

        .action-buttons { display: flex; gap: 6px; }

        .btn-action {
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }

        .btn-action:hover { transform: translateY(-1px); }

        .btn-accept {
            color: #2e7d32;
            border-color: #c8e6c9;
            background: #e8f5e9;
        }

        .btn-reject {
            color: #c62828;
            border-color: #ffcdd2;
            background: #ffebee;
        }

        .btn-view {
            color: var(--navy-primary);
            border-color: #B7D1F2;
            background: #E5F0FF;
        }

        .btn-view:hover { background: #D5E6FF; }

        .pagination-footer {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text-muted);
            background: #EEF5FF;
            border-top: 1px solid var(--border-color);
        }

        @media (max-width: 1200px) {
            .stats-cards-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .content-header-row { align-items: flex-start; flex-wrap: wrap; }
            .header-filters { margin-left: auto; }
        }

        @media (max-width: 800px) {
            .main-header {
                margin-left: 0;
                width: 100%;
                padding: 12px 16px;
            }
            .main-content {
                margin-left: 0;
                padding: 18px 16px;
            }
            .header-right-controls { gap: 12px; }
            .user-info .user-name { max-width: 120px; overflow-wrap: anywhere; }
            .table-card { overflow-x: auto; }
            .data-table { min-width: 1000px; }
        }

        @media (max-width: 560px) {
            .stats-cards-grid { grid-template-columns: 1fr; }
            .content-header-row h1 { font-size: 24px; }
            .header-filters { width: 100%; flex-wrap: wrap; margin-left: 0; }
            .search-box { min-width: 0; flex: 1; width: 100%; }
            .filter-select { flex: 1; }
            .user-info { display: none; }
        }
    </style>
</head>

<body>
    <!-- Shared lender sidebar -->
    <?php include 'lender_sidebar.php'; ?>

    <header class="main-header">
        <div class="header-right-controls">
            <a class="header-notification"
               href="lender_notifications.php?lang=<?php echo urlencode($current_lang ?? 'en'); ?>"
               aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
            </a>

            <div class="language-selector">
                <select id="langSelect" onchange="window.location.href='?lang=' + this.value">
                    <option value="en" <?php echo (($current_lang ?? 'en') == 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="kn" <?php echo (($current_lang ?? 'en') == 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                    <option value="hi" <?php echo (($current_lang ?? 'en') == 'hi') ? 'selected' : ''; ?>>हिंदी</option>
                </select>
            </div>

            <div class="user-profile-menu">
                <div class="avatar"><?php echo htmlspecialchars($lender_initials); ?></div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($lender_name); ?></span>
                    <span class="user-role"><?php echo __('lender_role'); ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="content-header-row">
            <div>
                <h1><?php echo __('page_title_rental_requests'); ?></h1>
                <p><?php echo __('page_subtitle_rental_requests'); ?></p>
            </div>

            <div class="header-filters">
                <input type="text" id="tableSearch" class="search-box"
                       placeholder="Search request..." onkeyup="filterTable()">
                <select class="filter-select" aria-label="Filter requests">
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
                              AND LOWER(TRIM(b.status)) NOT IN ('completed', 'returned', 'cancelled', 'canceled')
                            ORDER BY b.booking_id DESC";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $lender_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $total_rows = $result->num_rows;

                    if ($total_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $status_class = 'status-pending';
                            if ($row['status'] === 'Accepted') {
                                $status_class = 'status-accepted';
                            } elseif ($row['status'] === 'Rejected') {
                                $status_class = 'status-rejected';
                            }
                    ?>
                    <tr>
                        <td><strong>REQ<?php echo (int) $row['booking_id']; ?></strong></td>
                        <td>
                            <div class="table-equipment-info">
                                <img src="uploads/<?php echo htmlspecialchars($row['eq_image'] ?? ''); ?>"
                                     alt="Equipment" class="eq-thumb"
                                     onerror="this.onerror=null; this.src='uploads/default.png';">
                                <div>
                                    <strong><?php echo htmlspecialchars($row['eq_title'] ?? ''); ?></strong><br>
                                    <small><?php echo htmlspecialchars($row['eq_cat'] ?? ''); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span><i class="fa-regular fa-user"></i>
                                    <?php echo htmlspecialchars($row['renter_name'] ?? ''); ?>
                                </span><br>
                                <small><i class="fa-solid fa-phone"></i>
                                    <?php echo htmlspecialchars($row['renter_phone'] ?? ''); ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <?php
                            echo date('M d', strtotime($row['start_date'])) . ' – ' .
                                 date('M d, Y', strtotime($row['end_date']));
                            ?>
                        </td>
                        <td><?php echo (int) $row['total_days']; ?></td>
                        <td>₹<?php echo number_format((float) $row['total_amount'], 2); ?></td>
                        <td>
                            <span class="badge-status <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y h:i A', strtotime($row['created_at'] ?? 'now')); ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <a href="rental_request.php?action=accept&amp;id=<?php echo (int) $row['booking_id']; ?>"
                                       class="btn-action btn-accept" aria-label="Accept request" title="Accept">
                                        <i class="fa-solid fa-check"></i>
                                    </a>
                                    <a href="rental_request.php?action=reject&amp;id=<?php echo (int) $row['booking_id']; ?>"
                                       class="btn-action btn-reject" aria-label="Reject request" title="Reject">
                                        <i class="fa-solid fa-xmark"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="lender_booking_details.php?booking_id=<?php echo (int) $row['booking_id']; ?>"
                                   class="btn-action btn-view" aria-label="View booking details" title="View">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="9" style="text-align:center; padding:30px; color:var(--text-muted);">' .
                             htmlspecialchars(__('no_rental_requests')) .
                             '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <div class="pagination-footer">
                <span><?php echo "Showing " . ($total_rows > 0 ? 1 : 0) . " to {$total_rows} of {$total_rows} requests"; ?></span>
            </div>
        </div>
    </main>

    <script>
        function filterTable() {
            const input = document.getElementById("tableSearch").value.toLowerCase();
            const table = document.getElementById("requestsTable");
            const rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                const rowText = rows[i].textContent || rows[i].innerText;
                rows[i].style.display = rowText.toLowerCase().includes(input) ? "" : "none";
            }
        }
    </script>
</body>
</html>
