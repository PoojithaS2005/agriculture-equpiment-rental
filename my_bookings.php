<?php
session_start();

// Include configuration and language files
require_once 'includes/config.php';

// Handle Language Switch instantly via session without self-redirect loops
if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';

// Protect Page: Ensure renter is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$renter_id = $_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';
// Fixed: Ensure $lang_param starts with '?' so links like dashboard.php?lang=kn work correctly
$lang_param = '?lang=' . urlencode($current_lang);

// Fetch unread notification count for this user
$notif_count = 0;
$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($notif_check && $notif_check->num_rows > 0) {
    $n_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)");
    if ($n_stmt) {
        $n_stmt->bind_param("i", $renter_id);
        $n_stmt->execute();
        $n_res = $n_stmt->get_result()->fetch_assoc();
        $notif_count = intval($n_res['cnt'] ?? 0);
        $n_stmt->close();
    }
}

// Active tab filter (all, upcoming, ongoing, completed, cancelled)
$active_tab = $_GET['tab'] ?? 'all';

// Sorting option
$sort_by = $_GET['sort'] ?? 'latest';
$order_sql = "b.created_at DESC"; 

if ($sort_by === 'oldest') {
    $order_sql = "b.created_at ASC";
} elseif ($sort_by === 'start_date') {
    $order_sql = "b.start_date ASC";
} elseif ($sort_by === 'amount') {
    $order_sql = "b.total_amount DESC";
}

// Base SQL query joining bookings, equipment, and lender details from users table
$sql = "SELECT b.*, 
               e.title AS equipment_title, e.category AS equipment_category, e.service_location, e.image AS equipment_image,
               u.full_name AS lender_name, u.phone AS lender_phone
        FROM bookings b
        JOIN equipment e ON b.equipment_id = e.equipment_id
        JOIN users u ON e.lender_id = u.user_id
        WHERE b.renter_id = ?";

// Apply Tab Filters based on actual database status and dates
$today = date('Y-m-d');
if ($active_tab === 'upcoming') {
    $sql .= " AND (b.status = 'Accepted' OR b.status = 'Pending') AND b.start_date >= '$today'";
} elseif ($active_tab === 'ongoing') {
    $sql .= " AND (b.status = 'Delivered' OR (b.status = 'Accepted' AND '$today' BETWEEN b.start_date AND b.end_date))";
} elseif ($active_tab === 'completed') {
    $sql .= " AND b.status = 'Returned'";
} elseif ($active_tab === 'cancelled') {
    $sql .= " AND (b.status = 'Rejected' OR b.status = 'Overdue')";
}

$sql .= " ORDER BY " . $order_sql;

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $renter_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('page_title'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; color: #1e293b; margin: 0; font-weight: 500; }
        
        /* Sidebar Styles */
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

        /* Main Content */
        .main-content { margin-left: 260px; flex: 1; padding: 20px 30px; }
        
        /* Top Navigation Bar */
        .top-nav-bar { background: #fff; padding: 12px 25px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: flex-end; align-items: center; gap: 20px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .page-header { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 20px; }
        
        /* Filter Tabs & Sorting bar */
        .filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .nav-tabs-custom { display: flex; gap: 8px; flex-wrap: wrap; }
        .tab-btn { padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; background: #f8fafc; transition: 0.2s; }
        .tab-btn.active, .tab-btn:hover { background-color: #198754; color: #fff; border-color: #198754; }

        /* Booking Card Layout */
        .booking-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; display: grid; grid-template-columns: 240px 1fr 220px 180px; gap: 20px; align-items: center; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: 0.2s; }
        .booking-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,0.06); }

        .equipment-img-box { height: 130px; width: 100%; border-radius: 10px; overflow: hidden; background: #f1f5f9; }
        .equipment-img-box img { width: 100%; height: 100%; object-fit: cover; }

        .equipment-info h5 { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        .equipment-info p { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 5px; }

        .booking-meta div { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
        .booking-meta i { color: #64748b; width: 14px; }

        .payment-info div { font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 5px; }
        .payment-amount { font-weight: 900; color: #198754; font-size: 16px; }

        .card-right-col { display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between; height: 100%; text-align: right; }
        
        /* Attractive Status Badges */
        .badge-status { padding: 7px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; letter-spacing: 0.3px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .status-upcoming { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .status-ongoing { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .status-completed { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-cancelled { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .status-pending { background: #f1f5f9; color: #e30ebc; border: 1px solid #0f0f10; }

        .booked-on { font-size: 11px; font-weight: 600; color: #64748b; }
        
        .btn-view-details { background: #fff; border: 1.5px solid #198754; color: #198754; padding: 7px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-view-details:hover { background: #198754; color: #fff; }

        .badge-count { background: #dc2626; color: #fff; border-radius: 50px; padding: 2px 8px; font-size: 11px; font-weight: 900; }
        .profile-avatar-btn { width: 38px; height: 38px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #334155; text-decoration: none; font-size: 16px; border: 2px solid #cbd5e1; transition: 0.2s; }
        .profile-avatar-btn:hover { background: #198754; color: #fff; border-color: #198754; }

        .empty-state { text-align: center; padding: 50px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 15px; }

        @media(max-width: 1200px) {
            .booking-card { grid-template-columns: 1fr; text-align: left; }
            .card-right-col { align-items: flex-start; text-align: left; gap: 10px; }
            .sidebar { width: 75px; padding: 15px 10px; }
            .sidebar .logo span, .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 75px; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-tractor"></i>
            <div>
                <span class="logo-text-main"><?php echo __('brand_main'); ?></span>
                <span class="logo-text-sub"><?php echo __('brand_sub'); ?></span>
            </div>
        </div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="renter_dashboard.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-chart-line"></i> <span><?php echo __('dashboard'); ?></span></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-grid-2"></i> <span><?php echo __('categories'); ?></span></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="my_bookings.php<?php echo $lang_param; ?>" class="nav-link active">
                    <span class="nav-link-content"><i class="fa-solid fa-clock-rotate-left"></i> <span><?php echo __('my_bookings'); ?></span></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="notifications.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-bell"></i> <span><?php echo __('notifications'); ?></span></span>
                    <span class="badge-count"><?php echo $notif_count; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php<?php echo $lang_param; ?>" class="nav-link">
                    <span class="nav-link-content"><i class="fa-solid fa-user"></i> <span><?php echo __('my_profile'); ?></span></span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: 35px;">
                <a href="logout.php" class="nav-link" style="color: #ef4444;">
                    <span class="nav-link-content"><i class="fa-solid fa-right-from-bracket"></i> <span><?php echo __('logout'); ?></span></span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        
        <!-- Top Navigation Bar -->
        <div class="top-nav-bar">
            <!-- Language Selector Form -->
            <form action="my_bookings.php" method="GET" class="d-flex align-items-center mb-0">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                <select name="lang" class="form-select form-select-sm fw-bold w-auto" onchange="this.form.submit()">
                    <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                    <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                </select>
            </form>

            <!-- Notification Icon link with badge -->
            <a href="notifications.php<?php echo $lang_param; ?>" class="position-relative text-dark text-decoration-none p-1" title="<?php echo __('notifications'); ?>">
                <i class="fa-solid fa-bell fa-lg"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px; font-weight: 900;">
                        <?php echo $notif_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Profile Icon link -->
            <a href="profile.php<?php echo $lang_param; ?>" class="profile-avatar-btn" title="<?php echo __('my_profile'); ?>">
                <i class="fa-solid fa-user"></i>
            </a>
        </div>

        <div class="page-header"><?php echo __('page_title'); ?></div>

        <!-- Filter Tabs & Sorting Bar -->
        <div class="filter-bar">
            <div class="nav-tabs-custom">
                <a href="my_bookings.php?tab=all<?php echo $lang_param; ?>" class="tab-btn <?php echo ($active_tab === 'all') ? 'active' : ''; ?>"><?php echo __('all_bookings'); ?></a>
                <a href="my_bookings.php?tab=upcoming<?php echo $lang_param; ?>" class="tab-btn <?php echo ($active_tab === 'upcoming') ? 'active' : ''; ?>"><?php echo __('upcoming'); ?></a>
                <a href="my_bookings.php?tab=ongoing<?php echo $lang_param; ?>" class="tab-btn <?php echo ($active_tab === 'ongoing') ? 'active' : ''; ?>"><?php echo __('ongoing'); ?></a>
                <a href="my_bookings.php?tab=completed<?php echo $lang_param; ?>" class="tab-btn <?php echo ($active_tab === 'completed') ? 'active' : ''; ?>"><?php echo __('completed'); ?></a>
                <a href="my_bookings.php?tab=cancelled<?php echo $lang_param; ?>" class="tab-btn <?php echo ($active_tab === 'cancelled') ? 'active' : ''; ?>"><?php echo __('cancelled'); ?></a>
            </div>

            <form method="GET" action="my_bookings.php" class="d-flex align-items-center gap-2 mb-0">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($current_lang); ?>">
                <span class="text-muted fw-bold" style="font-size: 13px; white-space: nowrap;"><?php echo __('sort_by'); ?></span>
                <select name="sort" class="form-select form-select-sm fw-bold" onchange="this.form.submit()">
                    <option value="latest" <?php echo ($sort_by === 'latest') ? 'selected' : ''; ?>><?php echo __('latest'); ?></option>
                    <option value="oldest" <?php echo ($sort_by === 'oldest') ? 'selected' : ''; ?>><?php echo __('oldest'); ?></option>
                    <option value="start_date" <?php echo ($sort_by === 'start_date') ? 'selected' : ''; ?>><?php echo __('start_date'); ?></option>
                    <option value="amount" <?php echo ($sort_by === 'amount') ? 'selected' : ''; ?>><?php echo __('amount'); ?></option>
                </select>
            </form>
        </div>

        <!-- Bookings List Section -->
        <?php if (empty($bookings)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h5 class="fw-bold"><?php echo __('no_bookings'); ?></h5>
                <p class="text-muted fw-semibold"><?php echo __('no_bookings_desc'); ?></p>
                <a href="categories.php<?php echo $lang_param; ?>" class="btn btn-success fw-bold mt-2"><?php echo __('browse_equipment'); ?></a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $b): ?>
                <?php 
                    // Image resolution path check
                    $img_path = !empty($b['equipment_image']) ? 'uploads/' . $b['equipment_image'] : '';
                    $has_img = !empty($b['equipment_image']) && file_exists(__DIR__ . '/' . $img_path);

                    // Determine status badge class and label
                    $st = $b['status'];
                    $badge_class = 'status-pending';
                    $status_icon = 'fa-clock';
                    if ($st === 'Accepted') { $badge_class = 'status-upcoming'; $status_icon = 'fa-calendar-check'; }
                    elseif ($st === 'Delivered') { $badge_class = 'status-ongoing'; $status_icon = 'fa-spinner fa-spin'; }
                    elseif ($st === 'Returned') { $badge_class = 'status-completed'; $status_icon = 'fa-circle-check'; }
                    elseif ($st === 'Rejected' || $st === 'Overdue') { $badge_class = 'status-cancelled'; $status_icon = 'fa-circle-xmark'; }

                    // Translate status if translation key exists, otherwise use database value
                    $status_key = strtolower($st);
                    $translated_status = (__($status_key) !== $status_key) ? __($status_key) : $st;
                ?>
                <div class="booking-card">
                    <!-- Left: Equipment Image & Info -->
                    <div class="equipment-img-box">
                        <?php if ($has_img): ?>
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Equipment">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted bg-light">
                                <i class="fa-solid fa-tractor fa-2x"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="equipment-info">
                        <h5><?php echo htmlspecialchars($b['equipment_title']); ?></h5>
                        <p class="text-muted mb-1"><?php echo __('category'); ?>: <strong><?php echo htmlspecialchars($b['equipment_category']); ?></strong></p>
                        <p class="mb-1"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?php echo htmlspecialchars($b['service_location']); ?></p>
                        <p class="mb-0"><i class="fa-solid fa-user text-secondary me-1"></i> <?php echo __('lender'); ?>: <strong><?php echo htmlspecialchars($b['lender_name']); ?></strong></p>
                    </div>

                    <!-- Middle: Dates & Payment details -->
                    <div class="booking-meta">
                        <div><i class="fa-solid fa-barcode"></i> <span><?php echo __('id'); ?>: <strong><?php echo htmlspecialchars($b['request_code']); ?></strong></span></div>
                        <div><i class="fa-solid fa-calendar-days"></i> <span><?php echo __('start_date'); ?>: <strong><?php echo date('d M Y', strtotime($b['start_date'])); ?></strong></span></div>
                        <div><i class="fa-solid fa-calendar-check"></i> <span><?php echo __('end'); ?>: <strong><?php echo date('d M Y', strtotime($b['end_date'])); ?></strong></span></div>
                        <div class="payment-info mt-2 pt-2 border-top">
                            <div><?php echo __('total'); ?>: <span class="payment-amount">₹<?php echo number_format($b['total_amount'], 2); ?></span></div>
                            <div><?php echo __('advance'); ?>: ₹<?php echo number_format($b['advance_amount'], 2); ?></div>
                            <div><?php echo __('payment'); ?>: <span class="badge bg-light text-dark border fw-bold">COD</span></div>
                        </div>
                    </div>

                    <!-- Right: Status Badge & Action -->
                    <div class="card-right-col">
                        <span class="badge-status <?php echo $badge_class; ?>">
                            <i class="fa-solid <?php echo $status_icon; ?>"></i> <?php echo htmlspecialchars($translated_status); ?>
                        </span>
                        <div class="booked-on"><?php echo __('booked_on'); ?>: <?php echo date('d M Y', strtotime($b['created_at'])); ?></div>
                        <a href="booking_details.php?booking_id=<?php echo $b['booking_id']; ?><?php echo $lang_param; ?>" class="btn-view-details">
                            <i class="fa-solid fa-eye"></i> <?php echo __('view_details'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>