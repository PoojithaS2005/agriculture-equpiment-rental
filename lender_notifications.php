<?php
session_start();

require_once 'includes/config.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'hi', 'kn'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$lender_id = (int)$_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';
if (!in_array($current_lang, ['en', 'hi', 'kn'], true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

$lang_param = '?lang=' . urlencode($current_lang);

/* Mark one notification as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = (int)$_POST['notification_id'];

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ? AND user_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("ii", $notification_id, $lender_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: lender_notifications.php" . $lang_param);
    exit();
}

/* Mark all notifications as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($stmt) {
        $stmt->bind_param("i", $lender_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: lender_notifications.php" . $lang_param);
    exit();
}

/* Fetch lender notifications */
$notifications = [];
$notif_count = 0;

$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($table_check && $table_check->num_rows > 0) {
    $count_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($count_stmt) {
        $count_stmt->bind_param("i", $lender_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $notif_count = (int)($count_result['cnt'] ?? 0);
        $count_stmt->close();
    }

    $stmt = $conn->prepare("
        SELECT notification_id, title, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, notification_id DESC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $lender_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();
    }
}

/* Notification icon based on notification type */
function notification_icon($title, $message) {
    $text = strtolower($title . ' ' . $message);

    if (strpos($text, 'request') !== false || strpos($text, 'booking') !== false) {
        return ['fa-clipboard-list', 'request'];
    }

    if (strpos($text, 'accepted') !== false || strpos($text, 'confirmed') !== false) {
        return ['fa-circle-check', 'success'];
    }

    if (strpos($text, 'delivered') !== false || strpos($text, 'delivery') !== false) {
        return ['fa-truck', 'delivery'];
    }

    if (strpos($text, 'returned') !== false || strpos($text, 'return') !== false) {
        return ['fa-rotate-left', 'return'];
    }

    if (strpos($text, 'rejected') !== false || strpos($text, 'overdue') !== false ||
        strpos($text, 'cancel') !== false) {
        return ['fa-circle-exclamation', 'danger'];
    }

    return ['fa-bell', 'default'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(__('notifications')); ?> - <?php echo htmlspecialchars(__('brand_main') . ' ' . __('brand_sub')); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --teal: #0f766e;
            --teal-dark: #115e59;
            --teal-light: #e6fffb;
            --blue: #2563eb;
            --page-bg: #f5f8fa;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text-dark);
            font-family: Arial, Helvetica, sans-serif;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 255px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid var(--border);
            padding: 24px 16px;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 10px 24px;
            font-weight: 800;
            font-size: 19px;
            color: var(--teal-dark);
        }

        .brand i {
            color: var(--teal);
            font-size: 23px;
        }

        .nav-menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-item {
            margin-bottom: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 11px 13px;
            border-radius: 9px;
            text-decoration: none;
            color: #4b5563;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .nav-link:hover {
            background: #ecfdf5;
            color: var(--teal-dark);
        }

        .nav-link.active {
            background: #dff7f4;
            color: var(--teal-dark);
        }

        .nav-link i {
            width: 25px;
            font-size: 15px;
            margin-right: 8px;
        }

        .main-content {
            margin-left: 255px;
            min-height: 100vh;
        }

        .top-nav {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 0 32px;
        }

        .language-select {
            border: 1px solid #d1d5db;
            border-radius: 7px;
            padding: 7px 30px 7px 10px;
            font-size: 13px;
            font-weight: 700;
            background: #fff;
        }

        .notification-bell {
            position: relative;
            color: #374151;
            text-decoration: none;
            font-size: 19px;
        }

        .notification-bell:hover {
            color: var(--teal);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            min-width: 17px;
            height: 17px;
            border-radius: 20px;
            background: #dc2626;
            color: #fff;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .profile-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--teal);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
        }

        .page-title {
            margin: 0;
            font-size: 27px;
            font-weight: 800;
            color: #123c3a;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 5px;
        }

        .mark-all-btn {
            border: 1px solid var(--teal);
            background: white;
            color: var(--teal-dark);
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 700;
        }

        .mark-all-btn:hover {
            background: var(--teal);
            color: white;
        }

        .notification-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 18px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 2px 7px rgba(0,0,0,.03);
        }

        .notification-card.unread {
            border-left: 4px solid var(--teal);
            background: #f0fdfa;
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .notification-icon.request {
            background: #dbeafe;
            color: #2563eb;
        }

        .notification-icon.success {
            background: #dcfce7;
            color: #15803d;
        }

        .notification-icon.delivery {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .notification-icon.return {
            background: #fef3c7;
            color: #b45309;
        }

        .notification-icon.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .notification-icon.default {
            background: var(--teal-light);
            color: var(--teal);
        }

        .notification-body {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 15px;
            font-weight: 800;
            margin: 0 0 5px;
        }

        .notification-message {
            margin: 0;
            color: #59636e;
            font-size: 14px;
            line-height: 1.55;
        }

        .notification-time {
            color: #9ca3af;
            font-size: 12px;
            margin-top: 8px;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: var(--teal);
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
        }

        .read-btn {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #475569;
            border-radius: 7px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .read-btn:hover {
            border-color: var(--teal);
            color: var(--teal-dark);
        }

        .empty-state {
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 70px 25px;
            text-align: center;
        }

        .empty-state i {
            font-size: 42px;
            color: #94a3b8;
            margin-bottom: 15px;
        }

        .empty-state h5 {
            font-weight: 800;
            margin-bottom: 7px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin: 0;
            font-size: 14px;
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 210px;
            }

            .main-content {
                margin-left: 210px;
            }

            .top-nav {
                padding: 0 18px;
            }

            .page-wrap {
                padding: 22px 16px;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<!-- Lender Sidebar -->
<div class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-tractor"></i>
        <span><?php echo htmlspecialchars(__('brand_main') . ' ' . __('brand_sub')); ?></span>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="lender_dashboard.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-gauge"></i>
                <span><?php echo htmlspecialchars(__('dashboard')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="add_equipment.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-plus-circle"></i>
                <span><?php echo htmlspecialchars(__('add_equipment')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="my_equipment.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-tractor"></i>
                <span><?php echo htmlspecialchars(__('my_equipment')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="active_rentals.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-clock"></i>
                <span><?php echo htmlspecialchars(__('active_rentals')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="rental_request.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-file-circle-check"></i>
                <span><?php echo htmlspecialchars(__('rental_requests')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="lender_bookings.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span><?php echo htmlspecialchars(__('my_bookings')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="rental_history.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span><?php echo htmlspecialchars(__('rental_history')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="reviews.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-star"></i>
                <span><?php echo htmlspecialchars(__('reviews')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="total_earnings.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-indian-rupee-sign"></i>
                <span><?php echo htmlspecialchars(__('total_earnings')); ?></span>
            </a>
        </li>

        <li class="nav-item">
            <a href="profile.php<?php echo $lang_param; ?>" class="nav-link">
                <i class="fa-solid fa-user"></i>
                <span><?php echo htmlspecialchars(__('my_profile')); ?></span>
            </a>
        </li>

        <li class="nav-item" style="margin-top: 25px;">
            <a href="logout.php" class="nav-link" style="color:#dc2626;">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span><?php echo htmlspecialchars(__('logout')); ?></span>
            </a>
        </li>
    </ul>
</div>

<div class="main-content">

    <!-- Top Bar -->
    <div class="top-nav">

        <form action="lender_notifications.php" method="GET" class="mb-0">
            <select name="lang" class="language-select" onchange="this.form.submit()">
                <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
            </select>
        </form>

        <a href="lender_notifications.php<?php echo $lang_param; ?>"
           class="notification-bell"
           title="<?php echo htmlspecialchars(__('notifications')); ?>">
            <i class="fa-solid fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span class="notification-badge"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </a>

        <a href="profile.php<?php echo $lang_param; ?>"
           class="profile-btn"
           title="<?php echo htmlspecialchars(__('my_profile')); ?>">
            <i class="fa-solid fa-user"></i>
        </a>
    </div>

    <div class="page-wrap">

        <div class="page-header">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars(__('notifications')); ?></h1>
                <div class="page-subtitle">
                    <?php echo htmlspecialchars(__('lender_notifications_subtitle')); ?>
                </div>
            </div>

            <?php if ($notif_count > 0): ?>
                <form method="POST" class="mb-0">
                    <button type="submit" name="mark_all_read" class="mark-all-btn">
                        <i class="fa-solid fa-check-double me-1"></i>
                        <?php echo htmlspecialchars(__('mark_all_read')); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>

            <div class="empty-state">
                <i class="fa-regular fa-bell-slash"></i>
                <h5><?php echo htmlspecialchars(__('no_notifications')); ?></h5>
                <p><?php echo htmlspecialchars(__('no_notifications_message')); ?></p>
            </div>

        <?php else: ?>

            <?php foreach ($notifications as $notification): ?>
                <?php
                    [$icon, $icon_type] = notification_icon(
                        $notification['title'] ?? '',
                        $notification['message'] ?? ''
                    );

                    $is_unread = empty($notification['is_read']);
                ?>

                <div class="notification-card <?php echo $is_unread ? 'unread' : ''; ?>">

                    <div class="notification-icon <?php echo htmlspecialchars($icon_type); ?>">
                        <i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i>
                    </div>

                    <div class="notification-body">
                        <h5 class="notification-title">
                            <?php echo htmlspecialchars($notification['title'] ?? __('notifications')); ?>
                            <?php if ($is_unread): ?>
                                <span class="unread-dot" title="<?php echo htmlspecialchars(__('unread')); ?>"></span>
                            <?php endif; ?>
                        </h5>

                        <p class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notification['message'] ?? '')); ?>
                        </p>

                        <div class="notification-time">
                            <i class="fa-regular fa-clock me-1"></i>
                            <?php
                                echo htmlspecialchars(
                                    date('d M Y, h:i A', strtotime($notification['created_at']))
                                );
                            ?>
                        </div>
                    </div>

                    <?php if ($is_unread): ?>
                        <form method="POST" class="mb-0">
                            <input type="hidden" name="notification_id"
                                   value="<?php echo (int)$notification['notification_id']; ?>">
                            <button type="submit" name="mark_read" class="read-btn">
                                <i class="fa-solid fa-check me-1"></i>
                                <?php echo htmlspecialchars(__('mark_read')); ?>
                            </button>
                        </form>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
