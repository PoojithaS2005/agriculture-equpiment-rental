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

// Mark notifications as read.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (hash_equals($_SESSION['csrf_token'], $csrf)) {

        if ($action === 'mark_read') {
            $notification_id = (int) ($_POST['notification_id'] ?? 0);

            if ($notification_id > 0) {
                $stmt = $conn->prepare(
                    "UPDATE notifications
                     SET is_read = 1
                     WHERE notification_id = ? AND user_id = ?"
                );

                if ($stmt) {
                    $stmt->bind_param('ii', $notification_id, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

        } elseif ($action === 'mark_all_read') {
            $stmt = $conn->prepare(
                "UPDATE notifications
                 SET is_read = 1
                 WHERE user_id = ?
                 AND (is_read = 0 OR is_read IS NULL)"
            );

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

$user_stmt = $conn->prepare(
    "SELECT full_name
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

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

    $count_stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt
         FROM notifications
         WHERE user_id = ?
         AND (is_read = 0 OR is_read IS NULL)"
    );

    if ($count_stmt) {
        $count_stmt->bind_param('i', $user_id);
        $count_stmt->execute();

        $count_data = $count_stmt->get_result()->fetch_assoc();

        $notif_count = (int) ($count_data['cnt'] ?? 0);

        $count_stmt->close();
    }

    $stmt = $conn->prepare(
        "SELECT notification_id, title, message, is_read, created_at
         FROM notifications
         WHERE user_id = ?
         ORDER BY created_at DESC, notification_id DESC"
    );

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

    if (strpos($t, 'delivered') !== false) {
        return 'equipment_delivered';
    }

    if (strpos($t, 'returned') !== false || strpos($t, 'return') !== false) {
        return 'equipment_returned';
    }

    if (strpos($t, 'accepted') !== false || strpos($t, 'approved') !== false) {
        return 'rental_accepted';
    }

    if (strpos($t, 'rejected') !== false || strpos($t, 'declined') !== false) {
        return 'rental_rejected';
    }

    if (strpos($t, 'completed') !== false) {
        return 'rental_completed';
    }

    if (strpos($t, 'overdue') !== false) {
        return 'rental_overdue';
    }

    if (strpos($t, 'request') !== false) {
        return 'new_rental_request';
    }

    return 'notifications_title';
}

/*
 * Notification-specific translations.
 * These are kept here because the current includes/lang.php does not
 * contain all of the notification keys used by this page.
 */
function notification_text(string $key, string $lang): string
{
    $translations = [
        'en' => [
            'notifications_title' => 'Notifications',
            'equipment_delivered' => 'Equipment Delivered',
            'equipment_returned' => 'Equipment Returned',
            'rental_accepted' => 'Rental Request Accepted',
            'rental_rejected' => 'Rental Request Rejected',
            'rental_completed' => 'Rental Completed',
            'rental_overdue' => 'Rental Overdue',
            'new_rental_request' => 'New Rental Request',
            'delivery_confirmation_required' => 'Please confirm that you received the equipment.',
            'return_confirmation_required' => 'Please confirm that the equipment was collected.',
            'booking_request_accepted' => 'Your rental request has been accepted.',
            'booking_request_rejected' => 'Your rental request has been rejected.',
            'booking_completed_message' => 'Your rental has been completed.',
            'notification_default' => 'You have a new notification.',
            'mark_all_read' => 'Mark All as Read',
            'mark_read' => 'Mark as Read',
            'no_notifications' => 'No notifications found.',
        ],
        'hi' => [
            'notifications_title' => 'सूचनाएं',
            'equipment_delivered' => 'उपकरण वितरित किया गया',
            'equipment_returned' => 'उपकरण वापस किया गया',
            'rental_accepted' => 'किराये का अनुरोध स्वीकार किया गया',
            'rental_rejected' => 'किराये का अनुरोध अस्वीकार किया गया',
            'rental_completed' => 'किराया पूरा हुआ',
            'rental_overdue' => 'किराया अतिदेय है',
            'new_rental_request' => 'नया किराये का अनुरोध',
            'delivery_confirmation_required' => 'कृपया पुष्टि करें कि आपको उपकरण प्राप्त हो गया है।',
            'return_confirmation_required' => 'कृपया पुष्टि करें कि उपकरण एकत्र कर लिया गया है।',
            'booking_request_accepted' => 'आपका किराये का अनुरोध स्वीकार कर लिया गया है।',
            'booking_request_rejected' => 'आपका किराये का अनुरोध अस्वीकार कर दिया गया है।',
            'booking_completed_message' => 'आपका किराया पूरा हो गया है।',
            'notification_default' => 'आपके पास एक नई सूचना है।',
            'mark_all_read' => 'सभी को पढ़ा हुआ चिन्हित करें',
            'mark_read' => 'पढ़ा हुआ चिन्हित करें',
            'no_notifications' => 'कोई सूचना नहीं मिली।',
        ],
        'kn' => [
            'notifications_title' => 'ಅಧಿಸೂಚನೆಗಳು',
            'equipment_delivered' => 'ಉಪಕರಣ ವಿತರಿಸಲಾಗಿದೆ',
            'equipment_returned' => 'ಉಪಕರಣ ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ',
            'rental_accepted' => 'ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ಸ್ವೀಕರಿಸಲಾಗಿದೆ',
            'rental_rejected' => 'ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ತಿರಸ್ಕರಿಸಲಾಗಿದೆ',
            'rental_completed' => 'ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿದೆ',
            'rental_overdue' => 'ಬಾಡಿಗೆ ಅವಧಿ ಮೀರಿದೆ',
            'new_rental_request' => 'ಹೊಸ ಬಾಡಿಗೆ ವಿನಂತಿ',
            'delivery_confirmation_required' => 'ನೀವು ಉಪಕರಣವನ್ನು ಸ್ವೀಕರಿಸಿದ್ದೀರಿ ಎಂಬುದನ್ನು ದಯವಿಟ್ಟು ದೃಢೀಕರಿಸಿ.',
            'return_confirmation_required' => 'ಉಪಕರಣವನ್ನು ಸಂಗ್ರಹಿಸಲಾಗಿದೆ ಎಂಬುದನ್ನು ದಯವಿಟ್ಟು ದೃಢೀಕರಿಸಿ.',
            'booking_request_accepted' => 'ನಿಮ್ಮ ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ಸ್ವೀಕರಿಸಲಾಗಿದೆ.',
            'booking_request_rejected' => 'ನಿಮ್ಮ ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ತಿರಸ್ಕರಿಸಲಾಗಿದೆ.',
            'booking_completed_message' => 'ನಿಮ್ಮ ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿದೆ.',
            'notification_default' => 'ನಿಮಗೆ ಹೊಸ ಅಧಿಸೂಚನೆ ಇದೆ.',
            'mark_all_read' => 'ಎಲ್ಲವನ್ನೂ ಓದಲಾಗಿದೆ ಎಂದು ಗುರುತಿಸಿ',
            'mark_read' => 'ಓದಲಾಗಿದೆ ಎಂದು ಗುರುತಿಸಿ',
            'no_notifications' => 'ಯಾವುದೇ ಅಧಿಸೂಚನೆಗಳು ಕಂಡುಬಂದಿಲ್ಲ.',
        ],
    ];

    if (!isset($translations[$lang])) {
        $lang = 'en';
    }

    return $translations[$lang][$key] ?? $translations['en'][$key] ?? $key;
}

function notification_message(string $title, string $message, string $lang): string
{
    $t = strtolower(trim($title));
    $key = null;

    if (strpos($t, 'delivered') !== false) {
        $key = 'delivery_confirmation_required';
    } elseif (
        strpos($t, 'returned') !== false ||
        strpos($t, 'return') !== false
    ) {
        $key = 'return_confirmation_required';
    } elseif (
        strpos($t, 'accepted') !== false ||
        strpos($t, 'approved') !== false
    ) {
        $key = 'booking_request_accepted';
    } elseif (
        strpos($t, 'rejected') !== false ||
        strpos($t, 'declined') !== false
    ) {
        $key = 'booking_request_rejected';
    } elseif (strpos($t, 'completed') !== false) {
        $key = 'booking_completed_message';
    }

    if ($key === null) {
        return $message !== ''
            ? $message
            : notification_text('notification_default', $lang);
    }

    $translated = notification_text($key, $lang);

    // Keep the booking/request code from the database notification.
    if (preg_match('/\b(?:REQ|BK)[-]?[A-Z0-9-]+\b/i', $message, $match)) {
        $translated .= ' (' . htmlspecialchars($match[0], ENT_QUOTES, 'UTF-8') . ')';
    }

    return $translated;
}

function notification_icon(string $title): string
{
    $t = strtolower(trim($title));

    if (strpos($t, 'delivered') !== false) {
        return 'fa-truck';
    }

    if (
        strpos($t, 'returned') !== false ||
        strpos($t, 'return') !== false
    ) {
        return 'fa-rotate-left';
    }

    if (
        strpos($t, 'rejected') !== false ||
        strpos($t, 'overdue') !== false ||
        strpos($t, 'declined') !== false
    ) {
        return 'fa-circle-exclamation';
    }

    if (
        strpos($t, 'accepted') !== false ||
        strpos($t, 'approved') !== false ||
        strpos($t, 'completed') !== false
    ) {
        return 'fa-circle-check';
    }

    return 'fa-bell';
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars(notification_text('notifications_title', $current_lang), ENT_QUOTES, 'UTF-8'); ?>
        - Agriculture Equipment Rental System
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="css/glassmorphism.css?v=2">

    <style>
        /* =========================================================
           MAIN INTERFACE COLOURS (matched to Renter Dashboard)
           Pale cream-green background + frosted white cards
           + dark green buttons. Sidebar is not touched.
           ========================================================= */

        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
            --brand-green-pill: #1f5c43;
            --page-bg-1: #f3f2ea;
            --page-bg-2: #eef2e8;
            --page-bg-3: #f5f4ec;
            --glass-bg: rgba(255, 255, 255, .55);
            --glass-border: rgba(255, 255, 255, .85);
            --text-dark: #2a2f29;
            --text-muted: #6a7566;
            --line: rgba(45, 106, 79, .12);
            --shadow-soft: 0 8px 26px rgba(70, 90, 60, .09);
        }

        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            margin: 0;
            min-height: 100vh;
            font-weight: 500;
            color: var(--text-dark);
            background-color: var(--page-bg-1);
        }

        /* ---------- Main area background ---------- */

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 28px 30px 40px;
            min-width: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 12% 8%, rgba(255, 255, 255, .70), transparent 32%),
                radial-gradient(circle at 88% 20%, rgba(220, 232, 205, .55), transparent 34%),
                radial-gradient(circle at 70% 92%, rgba(255, 255, 255, .50), transparent 32%),
                linear-gradient(135deg, var(--page-bg-1) 0%, var(--page-bg-2) 50%, var(--page-bg-3) 100%);
            background-attachment: fixed;
        }

        /* ---------- Top bar ---------- */

        .top-nav-bar {
            background: var(--glass-bg);
            padding: 12px 25px;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .top-nav-bar .form-select {
            background-color: rgba(255, 255, 255, .78);
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--brand-green);
            box-shadow: none;
        }

        .top-nav-bar .form-select:focus {
            border-color: #6bbd91;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, .12);
        }

        .profile-name {
            font-size: 13px;
            font-weight: 800;
            line-height: 1.15;
            color: var(--text-dark);
        }

        .profile-role {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 700;
        }

        .profile-avatar-btn {
            width: 38px;
            height: 38px;
            background: var(--brand-green-pill);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            border: 2px solid rgba(255, 255, 255, .85);
            box-shadow: 0 4px 12px rgba(31, 92, 67, .18);
            transition: .2s;
        }

        .profile-avatar-btn:hover {
            background: var(--brand-green-hover);
            color: #fff;
            border-color: rgba(255, 255, 255, .95);
        }

        /* ---------- Page header ---------- */

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .page-header {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -.4px;
            margin: 0;
        }

        .btn-read-all {
            border: 1px solid rgba(45, 106, 79, .40);
            color: var(--brand-green);
            background: rgba(255, 255, 255, .75);
            border-radius: 12px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 4px 14px rgba(70, 90, 60, .08);
            transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
        }

        .btn-read-all:hover {
            background: var(--brand-green-pill);
            border-color: var(--brand-green-pill);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ---------- Notification cards ---------- */

        .notification-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 18px 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(60, 90, 55, .14), inset 0 1px 0 rgba(255, 255, 255, .95);
        }

        .notification-card.unread {
            background: rgba(226, 240, 220, .75);
            border-color: rgba(120, 180, 135, .55);
        }

        .notification-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e3efdc, #cfe3c9);
            color: var(--brand-green);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            border: 1px solid rgba(255, 255, 255, .9);
            box-shadow: 0 4px 12px rgba(60, 90, 55, .08);
        }

        .notification-body {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .notification-message {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 7px;
        }

        .notification-time {
            font-size: 11px;
            color: #8a9686;
            font-weight: 600;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand-green);
            display: inline-block;
            margin-right: 6px;
        }

        .read-form {
            margin: 0;
        }

        .btn-mark-read {
            border: 1px solid rgba(45, 106, 79, .35);
            background: rgba(255, 255, 255, .80);
            color: var(--brand-green);
            border-radius: 10px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 800;
            transition: background .2s ease, color .2s ease;
        }

        .btn-mark-read:hover {
            background: var(--brand-green-pill);
            border-color: var(--brand-green-pill);
            color: #fff;
        }

        /* ---------- Empty state ---------- */

        .empty-state {
            text-align: center;
            padding: 65px 30px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .empty-state i {
            font-size: 48px;
            color: #b4c8ae;
            margin-bottom: 15px;
        }

        .empty-state h5 {
            font-weight: 800;
            color: var(--text-dark);
        }

        /* ---------- Responsive ---------- */

        @media (max-width: 900px) {
            .main-content {
                margin-left: 250px;
                padding: 18px;
            }

            .top-nav-bar {
                padding: 10px 15px;
            }
        }

        @media (max-width: 600px) {
            .page-header-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .top-nav-bar {
                border-radius: 16px;
                gap: 10px;
            }

            .notification-card {
                padding: 15px;
                border-radius: 17px;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
                min-width: 40px;
            }

            .notification-action {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .notification-card,
            .btn-read-all,
            .btn-mark-read {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <?php include 'renter_sidebar.php'; ?>

    <div class="main-content">

        <div class="top-nav-bar">

            <form
                action="notifications.php"
                method="GET"
                class="d-flex align-items-center mb-0"
            >
                <select
                    name="lang"
                    class="form-select form-select-sm fw-bold w-auto"
                    onchange="this.form.submit()"
                    aria-label="Language"
                >
                    <option
                        value="en"
                        <?php echo $current_lang === 'en' ? 'selected' : ''; ?>
                    >
                        English
                    </option>

                    <option
                        value="hi"
                        <?php echo $current_lang === 'hi' ? 'selected' : ''; ?>
                    >
                        हिंदी (Hindi)
                    </option>

                    <option
                        value="kn"
                        <?php echo $current_lang === 'kn' ? 'selected' : ''; ?>
                    >
                        ಕನ್ನಡ (Kannada)
                    </option>
                </select>
            </form>

            <a
                href="notifications.php<?php echo htmlspecialchars($lang_param, ENT_QUOTES, 'UTF-8'); ?>"
                class="position-relative text-dark text-decoration-none p-1"
                title="<?php echo htmlspecialchars(__('notifications'), ENT_QUOTES, 'UTF-8'); ?>"
            >
                <i class="fa-solid fa-bell fa-lg"></i>

                <?php if ($notif_count > 0): ?>
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="font-size:10px;font-weight:900;"
                    >
                        <?php echo $notif_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="d-flex align-items-center gap-2">

                <a
                    href="profile.php<?php echo htmlspecialchars($lang_param, ENT_QUOTES, 'UTF-8'); ?>"
                    class="profile-avatar-btn"
                    title="<?php echo htmlspecialchars(__('my_profile'), ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <i class="fa-solid fa-user"></i>
                </a>

                <div>
                    <div class="profile-name">
                        <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                    <div class="profile-role">
                        <?php echo htmlspecialchars(__('renter_role'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="page-header-row">

            <h1 class="page-header">
                <?php echo htmlspecialchars(notification_text('notifications_title', $current_lang), ENT_QUOTES, 'UTF-8'); ?>
            </h1>

            <?php if ($notif_count > 0): ?>

                <form method="POST" class="m-0">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="mark_all_read"
                    >

                    <button type="submit" class="btn-read-all">
                        <i class="fa-solid fa-check-double me-1"></i>
                        <?php echo htmlspecialchars(notification_text('mark_all_read', $current_lang), ENT_QUOTES, 'UTF-8'); ?>
                    </button>

                </form>

            <?php endif; ?>

        </div>

        <?php if (empty($notifications)): ?>

            <div class="empty-state">

                <i class="fa-regular fa-bell"></i>

                <h5>
                    <?php echo htmlspecialchars(notification_text('no_notifications', $current_lang), ENT_QUOTES, 'UTF-8'); ?>
                </h5>

            </div>

        <?php else: ?>

            <?php foreach ($notifications as $notification): ?>

                <?php
                $is_unread = empty($notification['is_read']);

                $title_key = notification_title_key(
                    $notification['title'] ?? ''
                );

                $display_title = notification_text($title_key, $current_lang);

                $display_message = notification_message(
                    $notification['title'] ?? '',
                    $notification['message'] ?? '',
                    $current_lang
                );

                $icon = notification_icon(
                    $notification['title'] ?? ''
                );
                ?>

                <div class="notification-card <?php echo $is_unread ? 'unread' : ''; ?>">

                    <div class="notification-icon">
                        <i class="fa-solid <?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                    </div>

                    <div class="notification-body">

                        <div class="notification-title">

                            <?php if ($is_unread): ?>
                                <span class="unread-dot"></span>
                            <?php endif; ?>

                            <?php echo htmlspecialchars($display_title, ENT_QUOTES, 'UTF-8'); ?>

                        </div>

                        <div class="notification-message">
                            <?php echo htmlspecialchars($display_message, ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <div class="notification-time">
                            <i class="fa-regular fa-clock me-1"></i>
                            <?php
                            echo htmlspecialchars(
                                date(
                                    'd M Y, h:i A',
                                    strtotime($notification['created_at'])
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </div>

                    </div>

                    <?php if ($is_unread): ?>

                        <div class="notification-action">

                            <form method="POST" class="read-form">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="mark_read"
                                >

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?php echo (int) $notification['notification_id']; ?>"
                                >

                                <button type="submit" class="btn-mark-read">
                                    <?php echo htmlspecialchars(notification_text('mark_read', $current_lang), ENT_QUOTES, 'UTF-8'); ?>
                                </button>

                            </form>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</body>

</html>