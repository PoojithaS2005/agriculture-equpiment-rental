
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

$lender_id = (int) $_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';

if (!in_array($current_lang, ['en', 'hi', 'kn'], true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

$lang_param = '?lang=' . urlencode($current_lang);

/* Mark one notification as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = (int) ($_POST['notification_id'] ?? 0);

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

/* Fetch notifications */
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
        $notif_count = (int) ($count_result['cnt'] ?? 0);
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
function notification_icon($title, $message)
{
    $text = strtolower($title . ' ' . $message);

    if (strpos($text, 'request') !== false ||
        strpos($text, 'booking') !== false) {
        return ['fa-clipboard-list', 'request'];
    }

    if (strpos($text, 'accepted') !== false ||
        strpos($text, 'confirmed') !== false) {
        return ['fa-circle-check', 'success'];
    }

    if (strpos($text, 'delivered') !== false ||
        strpos($text, 'delivery') !== false) {
        return ['fa-truck', 'delivery'];
    }

    if (strpos($text, 'returned') !== false ||
        strpos($text, 'return') !== false) {
        return ['fa-rotate-left', 'return'];
    }

    if (strpos($text, 'rejected') !== false ||
        strpos($text, 'overdue') !== false ||
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

    <title>
        <?php echo htmlspecialchars(__('notifications')); ?> -
        <?php echo htmlspecialchars(__('brand_main') . ' ' . __('brand_sub')); ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
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

            --teal: #0B3478;
            --teal-dark: #082B65;
            --teal-light: #DCEBFF;
            --blue: #164C9A;
            --page-bg: #EAF3FF;
            --border: #C8DDF5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg-body);
            color: var(--text-dark);
            font-family: Arial, Helvetica, sans-serif;
        }

        /* Main content starts beside the shared sidebar */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            background: var(--bg-body);
        }

        /* Top navigation */
        .top-nav {
            min-height: 72px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 12px 32px;
        }

        .language-select {
            border: 1px solid var(--border-color);
            border-radius: 7px;
            padding: 8px 30px 8px 10px;
            font-size: 13px;
            font-weight: 600;
            background: var(--card-bg);
            color: var(--text-dark);
            cursor: pointer;
        }

        .language-select:focus {
            outline: none;
            border-color: var(--blue-accent);
            box-shadow: 0 0 0 3px rgba(141, 184, 240, 0.2);
        }

        .notification-bell {
            position: relative;
            color: var(--navy-primary);
            text-decoration: none;
            font-size: 19px;
        }

        .notification-bell:hover {
            color: var(--blue-hover);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            min-width: 17px;
            height: 17px;
            padding: 0 4px;
            border-radius: 20px;
            background: #DC2626;
            color: #fff;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--navy-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .profile-btn:hover {
            background: var(--blue-hover);
            color: white;
        }

        /* Page container */
        .page-wrap {
            width: 100%;
            max-width: 1400px;
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
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 6px;
            line-height: 1.5;
        }

        /* Mark all button */
        .mark-all-btn {
            border: 1px solid var(--blue-accent);
            background: var(--card-bg);
            color: var(--navy-primary);
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .mark-all-btn:hover {
            background: var(--navy-primary);
            border-color: var(--navy-primary);
            color: #fff;
        }

        /* Notification cards */
        .notification-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 3px 10px rgba(11, 52, 120, 0.04);
            transition: box-shadow 0.2s ease;
        }

        .notification-card:hover {
            box-shadow: 0 5px 14px rgba(11, 52, 120, 0.08);
        }

        .notification-card.unread {
            border-left: 4px solid var(--blue-accent);
            background: #F0F6FF;
        }

        .notification-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .notification-icon.request {
            background: #DCEBFF;
            color: var(--navy-primary);
        }

        .notification-icon.success {
            background: #DCFCE7;
            color: #15803D;
        }

        .notification-icon.delivery {
            background: #DCEBFF;
            color: var(--blue-hover);
        }

        .notification-icon.return {
            background: #FEF3C7;
            color: #B45309;
        }

        .notification-icon.danger {
            background: #FEE2E2;
            color: #DC2626;
        }

        .notification-icon.default {
            background: #DCEBFF;
            color: var(--navy-primary);
        }

        .notification-body {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 6px;
        }

        .notification-message {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .notification-time {
            color: #657B9A;
            font-size: 12px;
            margin-top: 10px;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: var(--navy-primary);
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
            vertical-align: middle;
        }

        /* Individual mark-as-read button */
        .read-btn {
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--navy-primary);
            border-radius: 7px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .read-btn:hover {
            background: #DCEBFF;
            border-color: var(--blue-accent);
            color: var(--navy-dark);
        }

        /* Empty notification state */
        .empty-state {
            background: var(--card-bg);
            border: 1px dashed var(--blue-accent);
            border-radius: 14px;
            padding: 70px 25px;
            text-align: center;
        }

        .empty-state i {
            font-size: 42px;
            color: var(--blue-accent);
            margin-bottom: 16px;
        }

        .empty-state h5 {
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin: 0;
            font-size: 14px;
        }

        /* Responsive layout */
        @media (max-width: 1000px) {
            .page-wrap {
                padding: 25px 20px;
            }

            .top-nav {
                padding: 12px 20px;
            }
        }

        @media (max-width: 650px) {
            .page-wrap {
                padding: 20px 14px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .notification-card {
                flex-wrap: wrap;
                padding: 16px;
            }

            .notification-body {
                flex-basis: calc(100% - 65px);
            }

            .notification-card form {
                margin-left: 62px;
            }

            .top-nav {
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<?php include 'lender_sidebar.php'; ?>

<div class="main-content">

    <!-- Top Navigation -->
    <div class="top-nav">

        <form
            action="lender_notifications.php"
            method="GET"
            class="mb-0"
        >
            <select
                name="lang"
                class="language-select"
                onchange="this.form.submit()"
                aria-label="Select language"
            >
                <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>
                    English
                </option>

                <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>
                    हिंदी (Hindi)
                </option>

                <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>
                    ಕನ್ನಡ (Kannada)
                </option>
            </select>
        </form>

        <a
            href="lender_notifications.php<?php echo $lang_param; ?>"
            class="notification-bell"
            title="<?php echo htmlspecialchars(__('notifications')); ?>"
            aria-label="<?php echo htmlspecialchars(__('notifications')); ?>"
        >
            <i class="fa-solid fa-bell"></i>

            <?php if ($notif_count > 0): ?>
                <span class="notification-badge">
                    <?php echo $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a
            href="profile.php<?php echo $lang_param; ?>"
            class="profile-btn"
            title="<?php echo htmlspecialchars(__('my_profile')); ?>"
            aria-label="<?php echo htmlspecialchars(__('my_profile')); ?>"
        >
            <i class="fa-solid fa-user"></i>
        </a>

    </div>

    <div class="page-wrap">

        <!-- Page Heading -->
        <div class="page-header">

            <div>
                <h1 class="page-title">
                    <?php echo htmlspecialchars(__('notifications')); ?>
                </h1>

                <div class="page-subtitle">
                    <?php echo htmlspecialchars(__('lender_notifications_subtitle')); ?>
                </div>
            </div>

            <?php if ($notif_count > 0): ?>
                <form method="POST" class="mb-0">
                    <button
                        type="submit"
                        name="mark_all_read"
                        class="mark-all-btn"
                    >
                        <i class="fa-solid fa-check-double me-1"></i>
                        <?php echo htmlspecialchars(__('mark_all_read')); ?>
                    </button>
                </form>
            <?php endif; ?>

        </div>

        <!-- Notifications -->
        <?php if (empty($notifications)): ?>

            <div class="empty-state">
                <i class="fa-regular fa-bell-slash"></i>

                <h5>
                    <?php echo htmlspecialchars(__('no_notifications')); ?>
                </h5>

                <p>
                    <?php echo htmlspecialchars(__('no_notifications_message')); ?>
                </p>
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
                            <?php
                                echo htmlspecialchars(
                                    $notification['title'] ?? __('notifications')
                                );
                            ?>

                            <?php if ($is_unread): ?>
                                <span
                                    class="unread-dot"
                                    title="<?php echo htmlspecialchars(__('unread')); ?>"
                                ></span>
                            <?php endif; ?>
                        </h5>

                        <p class="notification-message">
                            <?php
                                echo nl2br(
                                    htmlspecialchars($notification['message'] ?? '')
                                );
                            ?>
                        </p>

                        <div class="notification-time">
                            <i class="fa-regular fa-clock me-1"></i>

                            <?php
                                $created_at = $notification['created_at'] ?? '';
                                $timestamp = $created_at ? strtotime($created_at) : false;

                                echo htmlspecialchars(
                                    $timestamp
                                        ? date('d M Y, h:i A', $timestamp)
                                        : ''
                                );
                            ?>
                        </div>

                    </div>

                    <?php if ($is_unread): ?>

                        <form method="POST" class="mb-0">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?php echo (int) $notification['notification_id']; ?>"
                            >

                            <button
                                type="submit"
                                name="mark_read"
                                class="read-btn"
                            >
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
