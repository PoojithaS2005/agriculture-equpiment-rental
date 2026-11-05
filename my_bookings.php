<?php
session_start();

require_once __DIR__ . '/includes/config.php';

if (isset($_GET['lang']) && !empty($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once __DIR__ . '/includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$renter_id = $_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';

if (!in_array($current_lang, ['en', 'hi', 'kn'], true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

$lang_param = '?lang=' . urlencode($current_lang);

/*
|--------------------------------------------------------------------------
| Unread Notification Count
|--------------------------------------------------------------------------
*/
$notif_count = 0;

$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($notif_check && $notif_check->num_rows > 0) {
    $n_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ?
        AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($n_stmt) {
        $n_stmt->bind_param("i", $renter_id);
        $n_stmt->execute();

        $n_res = $n_stmt->get_result()->fetch_assoc();
        $notif_count = (int)($n_res['cnt'] ?? 0);

        $n_stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| Booking Filters
|--------------------------------------------------------------------------
*/
$active_tab = $_GET['tab'] ?? 'all';

$allowed_tabs = [
    'all',
    'upcoming',
    'ongoing',
    'completed',
    'cancelled'
];

if (!in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'all';
}

$sort_by = $_GET['sort'] ?? 'latest';

$allowed_sorts = [
    'latest',
    'oldest',
    'start_date',
    'amount'
];

if (!in_array($sort_by, $allowed_sorts, true)) {
    $sort_by = 'latest';
}

$order_sql = "b.created_at DESC";

if ($sort_by === 'oldest') {
    $order_sql = "b.created_at ASC";
} elseif ($sort_by === 'start_date') {
    $order_sql = "b.start_date ASC";
} elseif ($sort_by === 'amount') {
    $order_sql = "b.total_amount DESC";
}

/*
|--------------------------------------------------------------------------
| Location Translation Helper
|--------------------------------------------------------------------------
*/
function translate_location($location_string) {
    if (empty($location_string)) {
        return '';
    }
    
    // Split multiple locations separated by commas
    $parts = explode(',', $location_string);
    $translated_parts = [];

    foreach ($parts as $part) {
        $clean = trim($part);
        $slug = strtolower(str_replace(' ', '_', $clean));
        
        // Check district key first (e.g. district_bengaluru_rural)
        $key = 'district_' . $slug;
        $translated = __($key);

        if ($translated === $key) {
            // Check city key (e.g. city_mysuru)
            $city_key = 'city_' . $slug;
            $translated = __($city_key);
            
            if ($translated === $city_key) {
                $translated = $clean;
            }
        }
        $translated_parts[] = $translated;
    }

    return implode(', ', $translated_parts);
}

/*
|--------------------------------------------------------------------------
| Fetch Bookings
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        b.*,
        e.title AS equipment_title,
        e.category AS equipment_category,
        e.service_location,
        e.image AS equipment_image,
        CASE
            WHEN EXISTS (
                SELECT 1
                FROM reviews rv
                WHERE rv.booking_id = b.booking_id
            )
            THEN 1
            ELSE 0
        END AS has_review,
        u.full_name AS lender_name,
        u.phone AS lender_phone
    FROM bookings b
    JOIN equipment e
        ON b.equipment_id = e.equipment_id
    JOIN users u
        ON e.lender_id = u.user_id
    WHERE b.renter_id = ?
";

$today = date('Y-m-d');

if ($active_tab === 'upcoming') {
    $sql .= "
        AND (b.status = 'Accepted' OR b.status = 'Pending')
        AND b.start_date >= ?
    ";
} elseif ($active_tab === 'ongoing') {
    $sql .= "
        AND (
            b.status IN ('Delivered', 'Overdue')
            OR (
                b.status = 'Accepted'
                AND ? BETWEEN b.start_date AND b.end_date
            )
        )
    ";
} elseif ($active_tab === 'completed') {
    $sql .= "
        AND (b.status = 'Returned' OR b.status = 'Completed')
    ";
} elseif ($active_tab === 'cancelled') {
    $sql .= "
        AND (
            b.status = 'Cancelled'
            OR b.status = 'Canceled'
            OR b.status = 'Rejected'
        )
    ";
}

$sql .= " ORDER BY " . $order_sql;

$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($active_tab === 'upcoming') {
        $stmt->bind_param("is", $renter_id, $today);
    } elseif ($active_tab === 'ongoing') {
        $stmt->bind_param("is", $renter_id, $today);
    } else {
        $stmt->bind_param("i", $renter_id);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $bookings = [];

    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    $stmt->close();
} else {
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars(__('page_title')); ?>
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
            --line: rgba(45, 106, 79, .14);
            --shadow-soft: 0 8px 26px rgba(70, 90, 60, .09);
        }

        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--page-bg-1);
            color: var(--text-dark);
            margin: 0;
            font-weight: 500;
        }

        /* ---------- Main area background ---------- */

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 28px 30px 40px;
            background:
                radial-gradient(circle at 12% 8%, rgba(255, 255, 255, .70), transparent 32%),
                radial-gradient(circle at 88% 20%, rgba(220, 232, 205, .55), transparent 34%),
                radial-gradient(circle at 70% 92%, rgba(255, 255, 255, .50), transparent 32%),
                linear-gradient(135deg, var(--page-bg-1) 0%, var(--page-bg-2) 50%, var(--page-bg-3) 100%);
            background-attachment: fixed;
        }

        /* ---------- Top navigation bar ---------- */

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

        .top-nav-bar .form-select,
        .filter-bar .form-select {
            background-color: rgba(255, 255, 255, .78);
            border: 1px solid var(--line);
            border-radius: 12px;
            color: var(--brand-green);
            box-shadow: none;
        }

        .top-nav-bar .form-select:focus,
        .filter-bar .form-select:focus {
            border-color: #6bbd91;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, .12);
        }

        .page-header {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -.4px;
            margin-bottom: 20px;
        }

        /* ---------- Filter tabs & sorting bar ---------- */

        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
            background: var(--glass-bg);
            padding: 15px;
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .filter-bar .text-muted {
            color: var(--text-muted) !important;
        }

        .nav-tabs-custom {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 9px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid var(--line);
            color: var(--brand-green);
            background: rgba(255, 255, 255, .78);
            transition: .2s;
        }

        .tab-btn.active,
        .tab-btn:hover {
            background-color: var(--brand-green-pill);
            color: #fff;
            border-color: var(--brand-green-pill);
            box-shadow: 0 5px 14px rgba(31, 92, 67, .18);
        }

        /* ---------- Booking card ---------- */

        .booking-card {
            background: var(--glass-bg);
            border-radius: 22px;
            border: 1px solid var(--glass-border);
            padding: 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 240px 1fr 220px 180px;
            gap: 20px;
            align-items: center;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 34px rgba(60, 90, 55, .15), inset 0 1px 0 rgba(255, 255, 255, .95);
        }

        .equipment-img-box {
            height: 130px;
            width: 100%;
            border-radius: 14px;
            overflow: hidden;
            background: linear-gradient(145deg, #e3efdc, #f7faf2);
            border: 1px solid rgba(45, 106, 79, .08);
        }

        .equipment-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .equipment-info h5 {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 6px;
        }

        .equipment-info p {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .equipment-info .text-muted {
            color: var(--text-muted) !important;
        }

        .delivery-notice {
            margin-top: 10px;
            padding: 9px 12px;
            border-radius: 12px;
            background: rgba(226, 240, 220, .75);
            border: 1px solid rgba(120, 180, 135, .45);
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            line-height: 1.45;
        }

        .delivery-notice-title {
            color: var(--brand-green);
            font-weight: 800;
            margin-bottom: 2px;
        }

        .booking-meta div {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-meta i {
            color: var(--brand-green);
            width: 14px;
        }

        .booking-meta .border-top {
            border-color: var(--line) !important;
        }

        .payment-info div {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .payment-amount {
            font-weight: 900;
            color: var(--brand-green);
            font-size: 16px;
        }

        .card-right-col {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            height: 100%;
            text-align: right;
        }

        /* ---------- Status badges ---------- */

        .badge-status {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .status-upcoming {
            background: #e6f0e1;
            color: #2d6a4f;
            border: 1px solid #c4dbbb;
        }

        .status-ongoing {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .status-completed {
            background: #dcefd6;
            color: #1f5c43;
            border: 1px solid #b4d6aa;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-pending {
            background: #eef0e6;
            color: #6a7566;
            border: 1px solid #d5dac9;
        }

        .booked-on {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .btn-view-details {
            background: rgba(255, 255, 255, .80);
            border: 1.5px solid rgba(45, 106, 79, .40);
            color: var(--brand-green);
            padding: 7px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-details:hover {
            background: var(--brand-green-pill);
            border-color: var(--brand-green-pill);
            color: #fff;
        }

        .btn-submit-review {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            background: #f59e0b;
            color: #fff;
        }

        .btn-submit-review:hover {
            background: #d97706;
            color: #fff;
        }

        .text-success {
            color: var(--brand-green) !important;
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

        /* ---------- Empty state ---------- */

        .empty-state {
            text-align: center;
            padding: 50px;
            background: var(--glass-bg);
            border-radius: 22px;
            border: 1px solid var(--glass-border);
            margin-top: 20px;
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
            color: var(--text-dark);
        }

        .empty-state .btn-success {
            background: var(--brand-green-pill);
            border-color: var(--brand-green-pill);
            border-radius: 12px;
        }

        .empty-state .btn-success:hover {
            background: var(--brand-green-hover);
            border-color: var(--brand-green-hover);
        }

        /* ---------- Responsive ---------- */

        @media (max-width: 1200px) {
            .booking-card {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .card-right-col {
                align-items: flex-start;
                text-align: left;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 250px;
                padding: 15px;
            }

            .filter-bar {
                align-items: flex-start;
            }

            .booking-card {
                padding: 15px;
                border-radius: 18px;
            }

            .equipment-img-box {
                height: 180px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .booking-card,
            .tab-btn,
            .btn-view-details {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<!-- SHARED RENTER SIDEBAR -->
<?php include __DIR__ . '/renter_sidebar.php'; ?>

<!-- Main Content Area -->
<div class="main-content">

    <!-- Top Navigation Bar -->
    <div class="top-nav-bar">

        <!-- Language Selector -->
        <form
            action="my_bookings.php"
            method="GET"
            class="d-flex align-items-center mb-0"
        >
            <input
                type="hidden"
                name="tab"
                value="<?php echo htmlspecialchars($active_tab); ?>"
            >

            <input
                type="hidden"
                name="sort"
                value="<?php echo htmlspecialchars($sort_by); ?>"
            >

            <select
                name="lang"
                class="form-select form-select-sm fw-bold w-auto"
                onchange="this.form.submit()"
            >
                <option
                    value="en"
                    <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>
                >
                    English
                </option>

                <option
                    value="hi"
                    <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>
                >
                    हिंदी (Hindi)
                </option>

                <option
                    value="kn"
                    <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>
                >
                    ಕನ್ನಡ (Kannada)
                </option>
            </select>
        </form>

        <!-- Notification -->
        <a
            href="notifications.php<?php echo $lang_param; ?>"
            class="position-relative text-dark text-decoration-none p-1"
            title="<?php echo htmlspecialchars(__('notifications')); ?>"
        >
            <i class="fa-solid fa-bell fa-lg"></i>

            <?php if ($notif_count > 0): ?>
                <span
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size: 10px; font-weight: 900;"
                >
                    <?php echo $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <!-- Profile -->
        <a
            href="profile.php<?php echo $lang_param; ?>"
            class="profile-avatar-btn"
            title="<?php echo htmlspecialchars(__('my_profile')); ?>"
        >
            <i class="fa-solid fa-user"></i>
        </a>

    </div>

    <div class="page-header">
        <?php echo htmlspecialchars(__('page_title')); ?>
    </div>

    <!-- Filter Tabs & Sorting Bar -->
    <div class="filter-bar">

        <div class="nav-tabs-custom">

            <a
                href="my_bookings.php?tab=all&lang=<?php echo urlencode($current_lang); ?>&sort=<?php echo urlencode($sort_by); ?>"
                class="tab-btn <?php echo ($active_tab === 'all') ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars(__('all_bookings')); ?>
            </a>

            <a
                href="my_bookings.php?tab=upcoming&lang=<?php echo urlencode($current_lang); ?>&sort=<?php echo urlencode($sort_by); ?>"
                class="tab-btn <?php echo ($active_tab === 'upcoming') ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars(__('upcoming')); ?>
            </a>

            <a
                href="my_bookings.php?tab=ongoing&lang=<?php echo urlencode($current_lang); ?>&sort=<?php echo urlencode($sort_by); ?>"
                class="tab-btn <?php echo ($active_tab === 'ongoing') ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars(__('ongoing')); ?>
            </a>

            <a
                href="my_bookings.php?tab=completed&lang=<?php echo urlencode($current_lang); ?>&sort=<?php echo urlencode($sort_by); ?>"
                class="tab-btn <?php echo ($active_tab === 'completed') ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars(__('completed')); ?>
            </a>

            <a
                href="my_bookings.php?tab=cancelled&lang=<?php echo urlencode($current_lang); ?>&sort=<?php echo urlencode($sort_by); ?>"
                class="tab-btn <?php echo ($active_tab === 'cancelled') ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars(__('cancelled')); ?>
            </a>

        </div>

        <!-- Sorting -->
        <form
            method="GET"
            action="my_bookings.php"
            class="d-flex align-items-center gap-2 mb-0"
        >
            <input
                type="hidden"
                name="tab"
                value="<?php echo htmlspecialchars($active_tab); ?>"
            >

            <input
                type="hidden"
                name="lang"
                value="<?php echo htmlspecialchars($current_lang); ?>"
            >

            <span
                class="text-muted fw-bold"
                style="font-size: 13px; white-space: nowrap;"
            >
                <?php echo htmlspecialchars(__('sort_by')); ?>
            </span>

            <select
                name="sort"
                class="form-select form-select-sm fw-bold"
                onchange="this.form.submit()"
            >
                <option
                    value="latest"
                    <?php echo ($sort_by === 'latest') ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars(__('latest')); ?>
                </option>

                <option
                    value="oldest"
                    <?php echo ($sort_by === 'oldest') ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars(__('oldest')); ?>
                </option>

                <option
                    value="start_date"
                    <?php echo ($sort_by === 'start_date') ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars(__('start_date')); ?>
                </option>

                <option
                    value="amount"
                    <?php echo ($sort_by === 'amount') ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars(__('amount')); ?>
                </option>
            </select>
        </form>

    </div>

    <!-- Bookings List -->
    <?php if (empty($bookings)): ?>

        <div class="empty-state">

            <i class="fa-solid fa-box-open"></i>

            <h5 class="fw-bold">
                <?php echo htmlspecialchars(__('no_bookings')); ?>
            </h5>

            <p class="text-muted fw-semibold">
                <?php echo htmlspecialchars(__('no_bookings_desc')); ?>
            </p>

            <a
                href="categories.php<?php echo $lang_param; ?>"
                class="btn btn-success fw-bold mt-2"
            >
                <?php echo htmlspecialchars(__('browse_equipment')); ?>
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($bookings as $b): ?>

            <?php
            $img_path = !empty($b['equipment_image'])
                ? 'uploads/' . $b['equipment_image']
                : '';

            $has_img = !empty($b['equipment_image'])
                && file_exists(__DIR__ . '/' . $img_path);

            $st = $b['status'];

            $badge_class = 'status-pending';
            $status_icon = 'fa-clock';

            if ($st === 'Accepted') {
                $badge_class = 'status-upcoming';
                $status_icon = 'fa-calendar-check';
            } elseif ($st === 'Delivered' || $st === 'Overdue') {
                $badge_class = 'status-ongoing';
                $status_icon = 'fa-spinner fa-spin';
            } elseif ($st === 'Returned' || $st === 'Completed') {
                $badge_class = 'status-completed';
                $status_icon = 'fa-circle-check';
            } elseif (
                $st === 'Rejected' ||
                $st === 'Cancelled' ||
                $st === 'Canceled'
            ) {
                $badge_class = 'status-cancelled';
                $status_icon = 'fa-circle-xmark';
            }

            // Translate Status properly across variations/keys
            $status_key = strtolower($st);
            $translated_status = __($status_key);
            if ($translated_status === $status_key) {
                $translated_status = __('status_' . $status_key);
                if ($translated_status === 'status_' . $status_key) {
                    $translated_status = $st;
                }
            }

            // Translate Category properly across variations/keys
            $raw_cat = trim($b['equipment_category']);
            $cat_key = 'category_' . strtolower($raw_cat);
            $translated_category = __($cat_key);
            if ($translated_category === $cat_key) {
                $translated_category = __(strtolower($raw_cat));
                if ($translated_category === strtolower($raw_cat)) {
                    $translated_category = $raw_cat;
                }
            }

            // Translate Service Locations / Districts
            $translated_location = translate_location($b['service_location']);
            ?>

            <div class="booking-card">

                <!-- Equipment Image -->
                <div class="equipment-img-box">

                    <?php if ($has_img): ?>

                        <img
                            src="<?php echo htmlspecialchars($img_path); ?>"
                            alt="<?php echo htmlspecialchars($b['equipment_title']); ?>"
                        >

                    <?php else: ?>

                        <div class="d-flex align-items-center justify-content-center h-100 text-muted bg-light">
                            <i class="fa-solid fa-tractor fa-2x"></i>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- Equipment Information -->
                <div class="equipment-info">

                    <h5>
                        <?php echo htmlspecialchars($b['equipment_title']); ?>
                    </h5>

                    <p class="text-muted mb-1">
                        <?php echo htmlspecialchars(__('category')); ?>:
                        <strong>
                            <?php echo htmlspecialchars($translated_category); ?>
                        </strong>
                    </p>

                    <p class="mb-1">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i>
                        <?php echo htmlspecialchars($translated_location); ?>
                    </p>

                    <p class="mb-0">
                        <i class="fa-solid fa-user text-secondary me-1"></i>
                        <?php echo htmlspecialchars(__('lender')); ?>:
                        <strong>
                            <?php echo htmlspecialchars($b['lender_name']); ?>
                        </strong>
                    </p>

                    <?php if ($st === 'Pending' || $st === 'Accepted'): ?>
                        <div class="delivery-notice">
                            <div class="delivery-notice-title">
                                <i class="fa-solid fa-truck"></i>
                                Delivery Update
                            </div>
                            Your equipment will be delivered on the date decided by the lender.
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Booking Details -->
                <div class="booking-meta">

                    <div>
                        <i class="fa-solid fa-barcode"></i>
                        <span>
                            <?php echo htmlspecialchars(__('id')); ?>:
                            <strong>
                                <?php echo htmlspecialchars($b['request_code']); ?>
                            </strong>
                        </span>
                    </div>

                    <div>
                        <i class="fa-solid fa-calendar-days"></i>
                        <span>
                            <?php echo htmlspecialchars(__('start_date')); ?>:
                            <strong>
                                <?php echo date('d M Y', strtotime($b['start_date'])); ?>
                            </strong>
                        </span>
                    </div>

                    <div>
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>
                            <?php echo htmlspecialchars(__('end')); ?>:
                            <strong>
                                <?php echo date('d M Y', strtotime($b['end_date'])); ?>
                            </strong>
                        </span>
                    </div>

                    <div class="payment-info mt-2 pt-2 border-top">

                        <div>
                            <?php echo htmlspecialchars(__('total')); ?>:
                            <span class="payment-amount">
                                ₹<?php echo number_format((float)$b['total_amount'], 2); ?>
                            </span>
                        </div>

                        <div>
                            <?php echo htmlspecialchars(__('advance')); ?>:
                            ₹<?php echo number_format((float)$b['advance_amount'], 2); ?>
                        </div>

                        <div>
                            <?php echo htmlspecialchars(__('payment')); ?>:
                            <span class="badge bg-light text-dark border fw-bold">
                                COD
                            </span>
                        </div>

                    </div>

                </div>

                <!-- Status & Actions -->
                <div class="card-right-col">

                    <span class="badge-status <?php echo htmlspecialchars($badge_class); ?>">
                        <i class="fa-solid <?php echo htmlspecialchars($status_icon); ?>"></i>
                        <?php echo htmlspecialchars($translated_status); ?>
                    </span>

                    <div class="booked-on">
                        <?php echo htmlspecialchars(__('booked_on')); ?>:
                        <?php echo date('d M Y', strtotime($b['created_at'])); ?>
                    </div>

                    <a
                        href="booking_details.php?booking_id=<?php echo urlencode($b['booking_id']); ?>&lang=<?php echo urlencode($current_lang); ?>"
                        class="btn-view-details"
                    >
                        <i class="fa-solid fa-eye"></i>
                        <?php echo htmlspecialchars(__('view_details')); ?>
                    </a>

                    <?php if ($st === 'Completed'): ?>

                        <?php if (!empty($b['has_review'])): ?>

                            <span
                                class="text-success fw-bold mt-2"
                                style="font-size:13px;"
                            >
                                <i class="fa-solid fa-circle-check"></i>
                                <?php echo htmlspecialchars(__('review_submitted')); ?>
                            </span>

                        <?php else: ?>

                            <a
                                href="review_submit.php?booking_id=<?php echo urlencode($b['booking_id']); ?>&lang=<?php echo urlencode($current_lang); ?>"
                                class="btn-submit-review"
                            >
                                <i class="fa-solid fa-star"></i>
                                <?php echo htmlspecialchars(__('submit_review')); ?>
                            </a>

                        <?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>