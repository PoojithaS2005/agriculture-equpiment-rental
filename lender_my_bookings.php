<?php
session_start();

require_once 'includes/config.php';
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'lender') {
    header('Location: login.php');
    exit();
}

$lender_id = (int)$_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_param = '?lang=' . urlencode($current_lang);

// Lender name + profile picture
$lender_name = $_SESSION['full_name'] ?? 'Lender';
$profile_pic = 'assets/images/default_avatar.png';

$user_stmt = $conn->prepare("SELECT full_name, profile_pic FROM users WHERE user_id = ? LIMIT 1");
if ($user_stmt) {
    $user_stmt->bind_param('i', $lender_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    if ($user_data) {
        $lender_name = $user_data['full_name'] ?: $lender_name;
        if (!empty($user_data['profile_pic'])) {
            $profile_pic = $user_data['profile_pic'];
        }
    }
    $user_stmt->close();
}

// Notification count for the top bell
$unread_notifications = 0;
$notif_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
if ($notif_stmt) {
    $notif_stmt->bind_param('i', $lender_id);
    $notif_stmt->execute();
    $notif_data = $notif_stmt->get_result()->fetch_assoc();
    $unread_notifications = (int)($notif_data['total'] ?? 0);
    $notif_stmt->close();
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$allowed_statuses = ['Accepted', 'Delivered', 'Returned'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

// Current lender bookings only. Pending requests are handled in Rental Requests;
// Completed/Rejected bookings are handled in Rental History.
$sql = "SELECT
            b.booking_id,
            b.request_code,
            b.start_date,
            b.end_date,
            b.total_days,
            b.quantity,
            b.total_amount,
            b.advance_amount,
            b.remaining_cod,
            b.status,
            b.created_at,
            e.equipment_id,
            e.title AS equipment_title,
            e.category AS equipment_category,
            e.image AS equipment_image,
            u.full_name AS renter_name,
            u.phone AS renter_phone
        FROM bookings b
        INNER JOIN equipment e ON b.equipment_id = e.equipment_id
        INNER JOIN users u ON b.renter_id = u.user_id
        WHERE e.lender_id = ?
          AND b.status IN ('Accepted', 'Delivered', 'Returned')";

$params = [$lender_id];
$types = 'i';

if ($status_filter !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search !== '') {
    $sql .= " AND (b.request_code LIKE ? OR e.title LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)";
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'ssss';
}

$sql .= " ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Unable to load bookings.');
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Counts for summary cards
$count_sql = "SELECT
    SUM(b.status = 'Accepted') AS accepted_count,
    SUM(b.status = 'Delivered') AS delivered_count,
    SUM(b.status = 'Returned') AS returned_count
    FROM bookings b
    INNER JOIN equipment e ON b.equipment_id = e.equipment_id
    WHERE e.lender_id = ?
      AND b.status IN ('Accepted', 'Delivered', 'Returned')";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $lender_id);
$count_stmt->execute();
$counts = $count_stmt->get_result()->fetch_assoc() ?: [];
$accepted_count = (int)($counts['accepted_count'] ?? 0);
$delivered_count = (int)($counts['delivered_count'] ?? 0);
$returned_count = (int)($counts['returned_count'] ?? 0);
$total_current = $accepted_count + $delivered_count + $returned_count;
$count_stmt->close();

function statusClass(string $status): string {
    return match ($status) {
        'Accepted' => 'status-accepted',
        'Delivered' => 'status-delivered',
        'Returned' => 'status-returned',
        default => 'status-other'
    };
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background:#f4f6f9; color:#333; display:flex; min-height:100vh; }
        .sidebar { width:250px; background:#fff; min-height:100vh; padding:20px; border-right:1px solid #e0e0e0; position:fixed; left:0; top:0; bottom:0; overflow-y:auto; }
        .logo { display:flex; align-items:center; gap:10px; font-weight:bold; color:#1e3a8a; font-size:15px; margin-bottom:30px; }
        .logo i { font-size:24px; color:#0f4c5c; }
        .nav-list { list-style:none; }
        .nav-item { margin-bottom:8px; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:12px 15px; color:#64748b; text-decoration:none; border-radius:8px; font-weight:500; font-size:14px; transition:.2s; }
        .nav-link:hover,.nav-link.active { background:#0f4c5c; color:#fff; }
        .nav-link .badge { margin-left:auto; background:#ef4444; color:#fff; font-size:11px; padding:2px 7px; border-radius:12px; }
        .main-content { flex:1; margin-left:250px; padding:20px 30px; min-width:0; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; gap:20px; }
        .page-title h1 { color:#0f172a; font-size:25px; margin-bottom:5px; }
        .page-title p { color:#64748b; font-size:14px; }
        .top-actions { display:flex; align-items:center; gap:14px; }
        .notification-link { position:relative; width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center; color:#0f4c5c; text-decoration:none; border-radius:50%; }
        .notification-link:hover { background:#e0f2fe; }
        .notification-badge { position:absolute; top:-2px; right:-1px; min-width:18px; height:18px; padding:0 5px; border-radius:999px; background:#ef4444; color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; border:2px solid #fff; }
        .lang-select { padding:7px 10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; }
        .user-menu { display:flex; align-items:center; gap:9px; }
        .user-profile-img { width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid #0f4c5c; }
        .user-text strong { display:block; font-size:13px; color:#0f172a; }
        .user-text span { display:block; font-size:11px; color:#64748b; }
        .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:22px; }
        .summary-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px; display:flex; align-items:center; gap:14px; }
        .summary-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; background:#0f4c5c; }
        .summary-card h3 { font-size:21px; color:#0f172a; }
        .summary-card p { color:#64748b; font-size:12px; margin-top:2px; }
        .toolbar { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:15px; display:flex; justify-content:space-between; gap:12px; margin-bottom:18px; }
        .search-form { display:flex; gap:8px; flex:1; max-width:650px; }
        .search-input,.status-select { border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; outline:none; background:#fff; }
        .search-input { flex:1; }
        .btn-search { border:0; border-radius:8px; background:#0f4c5c; color:#fff; padding:0 18px; cursor:pointer; font-weight:600; }
        .btn-reset { display:inline-flex; align-items:center; padding:0 15px; border-radius:8px; text-decoration:none; border:1px solid #cbd5e1; color:#475569; background:#fff; font-size:13px; }
        .table-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
        .table-header { padding:18px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
        .table-header h2 { font-size:16px; color:#0f172a; }
        .table-header span { font-size:12px; color:#64748b; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:13px; min-width:980px; }
        th { text-align:left; padding:12px 14px; color:#64748b; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:600; white-space:nowrap; }
        td { padding:13px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        tr:last-child td { border-bottom:0; }
        .equipment-info { display:flex; align-items:center; gap:10px; min-width:190px; }
        .equipment-img { width:45px; height:45px; border-radius:7px; object-fit:cover; background:#f1f5f9; border:1px solid #e2e8f0; }
        .equipment-info strong { display:block; color:#0f172a; font-size:13px; }
        .equipment-info small { color:#64748b; }
        .renter strong { display:block; color:#0f172a; }
        .renter small { color:#64748b; }
        .status { display:inline-block; padding:5px 9px; border-radius:999px; font-size:11px; font-weight:700; }
        .status-accepted { background:#fef3c7; color:#92400e; }
        .status-delivered { background:#dbeafe; color:#1d4ed8; }
        .status-returned { background:#dcfce7; color:#166534; }
        .status-other { background:#e2e8f0; color:#475569; }
        .btn-view { display:inline-flex; align-items:center; gap:6px; padding:7px 11px; border-radius:7px; background:#0f4c5c; color:#fff; text-decoration:none; font-size:12px; font-weight:600; }
        .btn-view:hover { background:#0b3844; }
        .empty { text-align:center; padding:55px 20px; color:#64748b; }
        .empty i { font-size:38px; color:#94a3b8; margin-bottom:12px; }
        .empty h3 { color:#334155; margin-bottom:6px; }
        @media(max-width:1100px){ .summary-grid{grid-template-columns:repeat(2,1fr);} }
        @media(max-width:800px){ .sidebar{position:relative;width:100%;min-height:auto;} body{display:block;} .main-content{margin-left:0;padding:18px;} .top-bar{align-items:flex-start;flex-direction:column;} .top-actions{width:100%;justify-content:flex-end;} .toolbar{flex-direction:column;} .search-form{max-width:none;} }
        @media(max-width:550px){ .summary-grid{grid-template-columns:1fr;} .search-form{flex-wrap:wrap;} .search-input{min-width:100%;} .btn-search,.btn-reset{height:40px;padding:0 15px;} }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-tractor"></i>
        <span>AGRICULTURE<br><small style="font-size:9px;color:#64748b;">EQUIPMENT RENTAL SYSTEM</small></span>
    </div>
    <ul class="nav-list">
        <li class="nav-item"><a href="lender_dashboard.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-chart-line"></i> <?php echo __('dashboard'); ?></a></li>
        <li class="nav-item"><a href="add_item.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-circle-plus"></i> <?php echo __('add_equipment'); ?></a></li>
        <li class="nav-item"><a href="my_equipment.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-list"></i> <?php echo __('my_equipment'); ?></a></li>
        <li class="nav-item"><a href="active_rentals.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-truck-ramp-box"></i> <?php echo __('active_rentals'); ?></a></li>
        <li class="nav-item"><a href="rental_request.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-clock"></i> <?php echo __('rental_requests'); ?></a></li>
        <li class="nav-item"><a href="lender_my_bookings.php<?php echo $lang_param; ?>" class="nav-link active"><i class="fa-solid fa-calendar-check"></i> My Bookings</a></li>
        <li class="nav-item"><a href="lender_rental_history.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> <?php echo __('rental_history'); ?></a></li>
        <li class="nav-item"><a href="reviews.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-star"></i> <?php echo __('reviews'); ?></a></li>
        <li class="nav-item"><a href="total_earnings.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo __('total_earnings'); ?></a></li>
        <li class="nav-item"><a href="profile.php<?php echo $lang_param; ?>" class="nav-link"><i class="fa-solid fa-user"></i> <?php echo __('my_profile'); ?></a></li>
        <li class="nav-item"><a href="logout.php" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> <?php echo __('logout'); ?></a></li>
    </ul>
</aside>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <h1>My Bookings</h1>
            <p>Manage your accepted and ongoing rental bookings.</p>
        </div>
        <div class="top-actions">
            <a href="lender_notifications.php<?php echo $lang_param; ?>" class="notification-link" title="Notifications" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
                <?php if ($unread_notifications > 0): ?><span class="notification-badge"><?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?></span><?php endif; ?>
            </a>
            <select class="lang-select" onchange="changeLanguage(this.value)">
                <option value="en" <?php echo $current_lang === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="kn" <?php echo $current_lang === 'kn' ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                <option value="hi" <?php echo $current_lang === 'hi' ? 'selected' : ''; ?>>हिन्दी</option>
            </select>
            <div class="user-menu">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" class="user-profile-img" alt="Profile">
                <div class="user-text"><strong><?php echo htmlspecialchars($lender_name); ?></strong><span>Lender</span></div>
            </div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card"><div class="summary-icon"><i class="fa-solid fa-calendar-check"></i></div><div><h3><?php echo $total_current; ?></h3><p>Current Bookings</p></div></div>
        <div class="summary-card"><div class="summary-icon"><i class="fa-solid fa-hourglass-half"></i></div><div><h3><?php echo $accepted_count; ?></h3><p>Accepted / Awaiting Delivery</p></div></div>
        <div class="summary-card"><div class="summary-icon"><i class="fa-solid fa-truck"></i></div><div><h3><?php echo $delivered_count; ?></h3><p>Delivered / Active</p></div></div>
        <div class="summary-card"><div class="summary-icon"><i class="fa-solid fa-rotate-left"></i></div><div><h3><?php echo $returned_count; ?></h3><p>Returned / Awaiting Confirmation</p></div></div>
    </div>

    <div class="toolbar">
        <form method="get" class="search-form">
            <input type="text" name="search" class="search-input" placeholder="Search request, equipment or renter..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status" class="status-select">
                <option value="">All Current Bookings</option>
                <option value="Accepted" <?php echo $status_filter === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="Returned" <?php echo $status_filter === 'Returned' ? 'selected' : ''; ?>>Returned</option>
            </select>
            <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if ($search !== '' || $status_filter !== ''): ?><a href="lender_my_bookings.php<?php echo $lang_param; ?>" class="btn-reset">Reset</a><?php endif; ?>
        </form>
    </div>

    <section class="table-card">
        <div class="table-header">
            <h2>Current Booking Management</h2>
            <span>Pending requests are handled under Rental Requests.</span>
        </div>
        <div class="table-wrap">
            <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Equipment</th>
                        <th>Renter</th>
                        <th>Rental Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['request_code'] ?: ('REQ' . $row['booking_id'])); ?></strong><br><small style="color:#94a3b8;">#<?php echo (int)$row['booking_id']; ?></small></td>
                        <td>
                            <div class="equipment-info">
                                <?php $img = !empty($row['equipment_image']) ? 'uploads/' . $row['equipment_image'] : 'assets/images/default.png'; ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" class="equipment-img" alt="Equipment" onerror="this.src='assets/images/default.png'">
                                <div><strong><?php echo htmlspecialchars($row['equipment_title']); ?></strong><small><?php echo htmlspecialchars($row['equipment_category']); ?></small></div>
                            </div>
                        </td>
                        <td><div class="renter"><strong><?php echo htmlspecialchars($row['renter_name']); ?></strong><small><?php echo htmlspecialchars($row['renter_phone']); ?></small></div></td>
                        <td><?php echo date('d M Y', strtotime($row['start_date'])); ?><br><small style="color:#64748b;">to <?php echo date('d M Y', strtotime($row['end_date'])); ?></small></td>
                        <td><strong>₹<?php echo number_format((float)$row['total_amount'], 2); ?></strong></td>
                        <td><span class="status <?php echo statusClass($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td><a class="btn-view" href="lender_booking_details.php?booking_id=<?php echo (int)$row['booking_id']; ?><?php echo '&lang=' . urlencode($current_lang); ?>"><i class="fa-regular fa-eye"></i> View Details</a></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty"><i class="fa-regular fa-calendar-xmark"></i><h3>No current bookings</h3><p>Accepted, delivered, or returned bookings will appear here.</p></div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
function changeLanguage(lang) {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}
</script>
</body>
</html>
