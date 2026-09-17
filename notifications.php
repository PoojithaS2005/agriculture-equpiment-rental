<?php
session_start();

require_once 'includes/config.php';

// Keep the selected language across pages.
$supported_languages = ['en', 'hi', 'kn'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_languages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';
if (!in_array($current_lang, $supported_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

$lang_param = '?lang=' . urlencode($current_lang);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mark one notification as read.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (hash_equals($_SESSION['csrf_token'], $csrf)) {
        if ($action === 'mark_read') {
            $notification_id = (int) ($_POST['notification_id'] ?? 0);
            if ($notification_id > 0) {
                $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
                if ($stmt) {
                    $stmt->bind_param('ii', $notification_id, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } elseif ($action === 'mark_all_read') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)");
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header('Location: notifications.php' . $lang_param);
    exit();
}

// User name for the top bar.
$user_name = 'User';
$user_stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ? LIMIT 1");
if ($user_stmt) {
    $user_stmt->bind_param('i', $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    $user_name = $user_data['full_name'] ?? 'User';
    $user_stmt->close();
}

$notifications = [];
$notif_count = 0;

$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($table_check && $table_check->num_rows > 0) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)");
    if ($count_stmt) {
        $count_stmt->bind_param('i', $user_id);
        $count_stmt->execute();
        $count_data = $count_stmt->get_result()->fetch_assoc();
        $notif_count = (int) ($count_data['cnt'] ?? 0);
        $count_stmt->close();
    }

    $stmt = $conn->prepare("SELECT notification_id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, notification_id DESC");
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();
    }
}

function notification_title_key(string $title): string
{
    $t = strtolower(trim($title));
    if (strpos($t, 'delivered') !== false) return 'equipment_delivered';
    if (strpos($t, 'returned') !== false || strpos($t, 'return') !== false) return 'equipment_returned';
    if (strpos($t, 'accepted') !== false || strpos($t, 'approved') !== false) return 'rental_accepted';
    if (strpos($t, 'rejected') !== false || strpos($t, 'declined') !== false) return 'rental_rejected';
    if (strpos($t, 'completed') !== false) return 'rental_completed';
    if (strpos($t, 'overdue') !== false) return 'rental_overdue';
    if (strpos($t, 'request') !== false) return 'new_rental_request';
    return 'notifications_title';
}

function notification_message(string $title, string $message, string $lang): string
{
    $t = strtolower(trim($title));
    $key = null;

    if (strpos($t, 'delivered') !== false) {
        $key = 'delivery_confirmation_required';
    } elseif (strpos($t, 'returned') !== false || strpos($t, 'return') !== false) {
        $key = 'return_confirmation_required';
    } elseif (strpos($t, 'accepted') !== false || strpos($t, 'approved') !== false) {
        $key = 'booking_request_accepted';
    } elseif (strpos($t, 'rejected') !== false || strpos($t, 'declined') !== false) {
        $key = 'booking_request_rejected';
    } elseif (strpos($t, 'completed') !== false) {
        $key = 'booking_completed_message';
    }

    if ($key === null) {
        return $message !== '' ? $message : __('notification_default');
    }

    $translated = __($key);

    // Keep the booking/request code from the database notification.
    if (preg_match('/(?:booking|request)\s+([A-Z0-9-]+)/i', $message, $match)) {
        $translated .= ' (' . htmlspecialchars($match[1], ENT_QUOTES, 'UTF-8') . ')';
    }

    return $translated;
}

function notification_icon(string $title): string
{
    $t = strtolower(trim($title));
    if (strpos($t, 'delivered') !== false) return 'fa-truck';
    if (strpos($t, 'returned') !== false || strpos($t, 'return') !== false) return 'fa-rotate-left';
    if (strpos($t, 'rejected') !== false || strpos($t, 'overdue') !== false || strpos($t, 'declined') !== false) return 'fa-circle-exclamation';
    if (strpos($t, 'accepted') !== false || strpos($t, 'approved') !== false || strpos($t, 'completed') !== false) return 'fa-circle-check';
    return 'fa-bell';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('notifications_title'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background:#f4f6f9; display:flex; color:#1e293b; margin:0; font-weight:500; }
        .sidebar { width:260px; background:#fff; min-height:100vh; padding:20px; border-right:1px solid #e0e0e0; position:fixed; }
        .logo { display:flex; align-items:center; gap:12px; font-weight:900; color:#198754; font-size:16px; margin-bottom:35px; line-height:1.2; }
        .logo i { font-size:28px; color:#198754; }
        .logo-text-main { font-size:15px; font-weight:900; letter-spacing:.3px; color:#198754; display:block; }
        .logo-text-sub { font-size:10px; font-weight:800; color:#198754; letter-spacing:.5px; display:block; margin-top:2px; }
        .nav-list { list-style:none; padding-left:0; }
        .nav-item { margin-bottom:10px; }
        .nav-link { display:flex; align-items:center; justify-content:space-between; padding:13px 16px; color:#334155; text-decoration:none; border-radius:10px; font-weight:700; font-size:15px; transition:.2s; }
        .nav-link-content { display:flex; align-items:center; gap:14px; }
        .nav-link i { font-size:17px; width:20px; text-align:center; }
        .nav-link:hover, .nav-link.active { background:#198754; color:#fff; }
        .main-content { margin-left:260px; flex:1; padding:20px 30px; }
        .top-nav-bar { background:#fff; padding:12px 25px; border-radius:12px; border:1px solid #e2e8f0; display:flex; justify-content:flex-end; align-items:center; gap:20px; margin-bottom:25px; box-shadow:0 2px 4px rgba(0,0,0,.02); }
        .profile-name { font-size:13px; font-weight:800; line-height:1.15; color:#0f172a; }
        .profile-role { font-size:11px; color:#64748b; font-weight:700; }
        .profile-avatar-btn { width:38px; height:38px; background:#e2e8f0; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#334155; text-decoration:none; font-size:16px; border:2px solid #cbd5e1; transition:.2s; }
        .profile-avatar-btn:hover { background:#198754; color:#fff; border-color:#198754; }
        .page-header-row { display:flex; justify-content:space-between; align-items:center; gap:15px; margin-bottom:20px; }
        .page-header { font-size:24px; font-weight:800; color:#0f172a; margin:0; }
        .btn-read-all { border:1px solid #198754; color:#198754; background:#fff; border-radius:8px; padding:8px 13px; font-size:13px; font-weight:800; }
        .btn-read-all:hover { background:#198754; color:#fff; }
        .notification-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px 20px; margin-bottom:14px; display:flex; align-items:flex-start; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
        .notification-card.unread { background:#f0fdf4; border-color:#bbf7d0; }
        .notification-icon { width:46px; height:46px; min-width:46px; border-radius:50%; background:#e8f5ee; color:#198754; display:flex; align-items:center; justify-content:center; font-size:18px; }
        .notification-body { flex:1; min-width:0; }
        .notification-title { font-size:15px; font-weight:800; color:#0f172a; margin-bottom:5px; }
        .notification-message { font-size:13px; color:#475569; line-height:1.55; margin-bottom:7px; }
        .notification-time { font-size:11px; color:#94a3b8; font-weight:600; }
        .unread-dot { width:8px; height:8px; border-radius:50%; background:#16a34a; display:inline-block; margin-right:6px; }
        .read-form { margin:0; }
        .btn-mark-read { border:1px solid #cbd5e1; background:#fff; color:#475569; border-radius:7px; padding:6px 10px; font-size:11px; font-weight:800; }
        .btn-mark-read:hover { border-color:#198754; color:#198754; }
        .empty-state { text-align:center; padding:65px 30px; background:#fff; border-radius:14px; border:1px solid #e2e8f0; }
        .empty-state i { font-size:48px; color:#cbd5e1; margin-bottom:15px; }
        .empty-state h5 { font-weight:800; color:#334155; }
        @media(max-width:900px) { .sidebar{width:75px;padding:15px 10px;} .sidebar .logo-text-main,.sidebar .logo-text-sub,.sidebar .nav-link span{display:none;} .main-content{margin-left:75px;padding:15px;} .top-nav-bar{padding:10px 15px;} }
        @media(max-width:600px) { .page-header-row{align-items:flex-start;flex-direction:column;} .notification-card{padding:15px;} .notification-action{display:none;} }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-tractor"></i>
        <div>
            <span class="logo-text-main"><?php echo __('brand_main'); ?></span>
            <span class="logo-text-sub"><?php echo __('brand_sub'); ?></span>
        </div>
    </div>

    <ul class="nav-list">
        <li class="nav-item"><a href="renter_dashboard.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-chart-line"></i><span><?php echo __('dashboard'); ?></span></span></a></li>
        <li class="nav-item"><a href="search_equipment.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-magnifying-glass"></i><span><?php echo __('search_equipment'); ?></span></span></a></li>
        <li class="nav-item"><a href="categories.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-grid-2"></i><span><?php echo __('categories'); ?></span></span></a></li>
        <li class="nav-item"><a href="featured_equipment.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-star"></i><span><?php echo __('featured_equipment'); ?></span></span></a></li>
        <li class="nav-item"><a href="recommended.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-thumbs-up"></i><span><?php echo __('recommended'); ?></span></span></a></li>
        <li class="nav-item"><a href="my_bookings.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-calendar-check"></i><span><?php echo __('my_bookings'); ?></span></span></a></li>
        <li class="nav-item"><a href="rental_history.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-clock-rotate-left"></i><span><?php echo __('rental_history'); ?></span></span></a></li>
        <li class="nav-item"><a href="profile.php<?php echo $lang_param; ?>" class="nav-link"><span class="nav-link-content"><i class="fa-solid fa-user"></i><span><?php echo __('my_profile'); ?></span></span></a></li>
        <li class="nav-item" style="margin-top:35px;"><a href="logout.php" class="nav-link" style="color:#ef4444;"><span class="nav-link-content"><i class="fa-solid fa-right-from-bracket"></i><span><?php echo __('logout'); ?></span></span></a></li>
    </ul>
</div>

<div class="main-content">
    <div class="top-nav-bar">
        <form action="notifications.php" method="GET" class="d-flex align-items-center mb-0">
            <select name="lang" class="form-select form-select-sm fw-bold w-auto" onchange="this.form.submit()" aria-label="Language">
                <option value="en" <?php echo $current_lang === 'en' ? 'selected' : ''; ?>>English</option>
                <option value="hi" <?php echo $current_lang === 'hi' ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                <option value="kn" <?php echo $current_lang === 'kn' ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
            </select>
        </form>

        <a href="notifications.php<?php echo $lang_param; ?>" class="position-relative text-dark text-decoration-none p-1" title="<?php echo __('notifications'); ?>">
            <i class="fa-solid fa-bell fa-lg"></i>
            <?php if ($notif_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;font-weight:900;"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </a>

        <div class="d-flex align-items-center gap-2">
            <a href="profile.php<?php echo $lang_param; ?>" class="profile-avatar-btn" title="<?php echo __('my_profile'); ?>"><i class="fa-solid fa-user"></i></a>
            <div>
                <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="profile-role"><?php echo __('renter_role'); ?></div>
            </div>
        </div>
    </div>

    <div class="page-header-row">
        <h1 class="page-header"><?php echo __('notifications_title'); ?></h1>
        <?php if ($notif_count > 0): ?>
            <form method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn-read-all"><i class="fa-solid fa-check-double me-1"></i><?php echo __('mark_all_read'); ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-bell"></i>
            <h5><?php echo __('no_notifications'); ?></h5>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <?php
                $is_unread = empty($notification['is_read']);
                $title_key = notification_title_key($notification['title'] ?? '');
                $display_title = __($title_key);
                $display_message = notification_message($notification['title'] ?? '', $notification['message'] ?? '', $current_lang);
                $icon = notification_icon($notification['title'] ?? '');
            ?>
            <div class="notification-card <?php echo $is_unread ? 'unread' : ''; ?>">
                <div class="notification-icon"><i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i></div>
                <div class="notification-body">
                    <div class="notification-title">
                        <?php if ($is_unread): ?><span class="unread-dot"></span><?php endif; ?>
                        <?php echo htmlspecialchars($display_title); ?>
                    </div>
                    <div class="notification-message"><?php echo htmlspecialchars($display_message); ?></div>
                    <div class="notification-time"><i class="fa-regular fa-clock me-1"></i><?php echo date('d M Y, h:i A', strtotime($notification['created_at'])); ?></div>
                </div>
                <?php if ($is_unread): ?>
                    <div class="notification-action">
                        <form method="POST" class="read-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notification_id" value="<?php echo (int)$notification['notification_id']; ?>">
                            <button type="submit" class="btn-mark-read"><?php echo __('mark_read'); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
