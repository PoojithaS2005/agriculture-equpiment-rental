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
            b.status = 'Delivered'
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
        AND (b.status = 'Rejected' OR b.status = 'Overdue')
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
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
            color: #1e293b;
            margin: 0;
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px 30px;
        }

        /* Top Navigation Bar */
        .top-nav-bar {
            background: #fff;
            padding: 12px 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .page-header {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 20px;
        }

        /* Filter Tabs & Sorting Bar */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .nav-tabs-custom {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #334155;
            background: #f8fafc;
            transition: 0.2s;
        }

        .tab-btn.active,
        .tab-btn:hover {
            background-color: #198754;
            color: #fff;
            border-color: #198754;
        }

        /* Booking Card */
        .booking-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 240px 1fr 220px 180px;
            gap: 20px;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            transition: 0.2s;
        }

        .booking-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }

        .equipment-img-box {
            height: 130px;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .equipment-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .equipment-info h5 {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .equipment-info p {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
        }

        .booking-meta div {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .booking-meta i {
            color: #64748b;
            width: 14px;
        }

        .payment-info div {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 5px;
        }

        .payment-amount {
            font-weight: 900;
            color: #198754;
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

        /* Status Badges */
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
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .status-ongoing {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .status-completed {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-pending {
            background: #f1f5f9;
            color: #e30ebc;
            border: 1px solid #0f0f10;
        }

        .booked-on {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        .btn-view-details {
            background: #fff;
            border: 1.5px solid #198754;
            color: #198754;
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-details:hover {
            background: #198754;
            color: #fff;
        }

        .btn-submit-review {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
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

        .profile-avatar-btn {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            text-decoration: none;
            font-size: 16px;
            border: 2px solid #cbd5e1;
            transition: 0.2s;
        }

        .profile-avatar-btn:hover {
            background: #198754;
            color: #fff;
            border-color: #198754;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 15px;
        }

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
            }

            .equipment-img-box {
                height: 180px;
            }
        }
    </style>
</head>

<body>

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
            } elseif ($st === 'Delivered') {
                $badge_class = 'status-ongoing';
                $status_icon = 'fa-spinner fa-spin';
            } elseif ($st === 'Returned') {
                $badge_class = 'status-completed';
                $status_icon = 'fa-circle-check';
            } elseif ($st === 'Completed') {
                $badge_class = 'status-completed';
                $status_icon = 'fa-circle-check';
            } elseif ($st === 'Rejected' || $st === 'Overdue') {
                $badge_class = 'status-cancelled';
                $status_icon = 'fa-circle-xmark';
            }

            $status_key = strtolower($st);
            $translated_status = __($status_key);

            if ($translated_status === $status_key) {
                $translated_status = $st;
            }
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
                            <?php echo htmlspecialchars($b['equipment_category']); ?>
                        </strong>
                    </p>

                    <p class="mb-1">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i>
                        <?php echo htmlspecialchars($b['service_location']); ?>
                    </p>

                    <p class="mb-0">
                        <i class="fa-solid fa-user text-secondary me-1"></i>
                        <?php echo htmlspecialchars(__('lender')); ?>:
                        <strong>
                            <?php echo htmlspecialchars($b['lender_name']); ?>
                        </strong>
                    </p>

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