<?php

session_start();


/* =========================================================
   DATABASE + LANGUAGE
   ========================================================= */

require_once __DIR__ . '/includes/lang.php';

require_once __DIR__ . '/includes/config.php';


/* =========================================================
   LOGIN CHECK
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: /agriculture-equpiment-rental/login.php");

    exit();

}

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'lender') {

    header("Location: /agriculture-equpiment-rental/login.php");

    exit();

}


$lender_id = (int)$_SESSION['user_id'];

$lender_name = $_SESSION['full_name'] ?? 'Lender';


/* =========================================================
   IMPORTANT:
   Your XAMPP project folder is:
   agriculture-equipment-rental
   ========================================================= */

$base_url = '/agriculture-equpiment-rental';


/* =========================================================
   CURRENT PAGE
   ========================================================= */

$current_page = basename($_SERVER['PHP_SELF']);


/* =========================================================
   ACTIVE MENU FUNCTION
   ========================================================= */

function activeMenu($page)

{

    global $current_page;

    if ($current_page === $page) {

        return 'active';

    }

    return '';

}


/* =========================================================
   PROFILE IMAGE
   ========================================================= */

$profile_pic = 'default_avatar.png';

$user_sql = "

    SELECT profile_pic

    FROM users

    WHERE user_id = $lender_id

    LIMIT 1

";

$user_result = mysqli_query($conn, $user_sql);

if ($user_result && mysqli_num_rows($user_result) > 0) {

    $user_data = mysqli_fetch_assoc($user_result);

    if (!empty($user_data['profile_pic'])) {

        $profile_pic = $user_data['profile_pic'];

    }

}


/* =========================================================
   NOTIFICATIONS
   ========================================================= */

$unread_notifications = 0;

$notification_sql = "

    SELECT COUNT(*) AS total

    FROM notifications

    WHERE user_id = $lender_id

    AND is_read = 0

";

$notification_result = mysqli_query(

    $conn,

    $notification_sql

);

if ($notification_result) {

    $notification_data =

        mysqli_fetch_assoc($notification_result);

    $unread_notifications =

        (int)($notification_data['total'] ?? 0);

}


/* =========================================================
   TOTAL EQUIPMENT
   ========================================================= */

$total_equipment = 0;

$sql = "

    SELECT COUNT(*) AS total

    FROM equipment

    WHERE lender_id = $lender_id

";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $total_equipment =

        (int)($data['total'] ?? 0);

}


/* =========================================================
   ACTIVE RENTALS
   ========================================================= */

$active_rentals = 0;

$sql = "

    SELECT COUNT(*) AS total

    FROM bookings b

    INNER JOIN equipment i

        ON b.equipment_id = i.equipment_id

    WHERE i.lender_id = $lender_id

    AND b.status IN ('Accepted', 'Delivered', 'Overdue')

";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $active_rentals =

        (int)($data['total'] ?? 0);

}


/* =========================================================
   RENTAL REQUESTS
   ========================================================= */

$pending_requests = 0;

$sql = "

    SELECT COUNT(*) AS total

    FROM bookings b

    INNER JOIN equipment i

        ON b.equipment_id = i.equipment_id

    WHERE i.lender_id = $lender_id

    AND b.status = 'Pending'

";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $pending_requests =

        (int)($data['total'] ?? 0);

}


/* =========================================================
   TOTAL EARNINGS
   ========================================================= */

$total_earnings = 0;

$sql = "

    SELECT COALESCE(SUM(b.total_amount), 0) AS total

    FROM bookings b

    INNER JOIN equipment i

        ON b.equipment_id = i.equipment_id

    WHERE i.lender_id = $lender_id

    AND b.status = 'Completed'

";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $total_earnings =

        (float)($data['total'] ?? 0);

}


/* =========================================================
   RENTAL REQUEST LIST
   ========================================================= */

$requests_sql = "

    SELECT

        b.booking_id,

        b.start_date,

        b.end_date,

        b.total_amount,

        i.title AS item_title,

        i.image,

        u.full_name AS renter_name,

        u.phone AS renter_phone


    FROM bookings b


    INNER JOIN equipment i

        ON b.equipment_id = i.equipment_id


    INNER JOIN users u

        ON b.renter_id = u.user_id


    WHERE i.lender_id = $lender_id

    AND b.status = 'Pending'


    ORDER BY b.booking_id DESC


    LIMIT 5

";

$requests_result =

    mysqli_query($conn, $requests_sql);


/* =========================================================
   ACTIVE RENTALS LIST
   ========================================================= */

$active_sql = "

    SELECT

        b.booking_id,

        b.start_date,

        b.end_date,

        b.total_amount,

        i.title AS item_title,

        i.image,

        u.full_name AS renter_name


    FROM bookings b


    INNER JOIN equipment i

        ON b.equipment_id = i.equipment_id


    INNER JOIN users u

        ON b.renter_id = u.user_id


    WHERE i.lender_id = $lender_id

    AND b.status IN ('Accepted', 'Delivered', 'Overdue')


    ORDER BY b.booking_id DESC


    LIMIT 5

";

$active_result =

    mysqli_query($conn, $active_sql);

?>

<!DOCTYPE html>

<html lang="<?php echo htmlspecialchars($current_lang ?? 'en'); ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title><?php
if (($current_lang ?? 'en') === 'kn') {
    echo 'ಸಾಲದಾತರ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್ - ಕೃಷಿ ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ';
} elseif (($current_lang ?? 'en') === 'hi') {
    echo 'लेंडर डैशबोर्ड - कृषि उपकरण किराया प्रणाली';
} else {
    echo 'Lender Dashboard - Agriculture Equipment Rental System';
}
?></title>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
>

<style>

/* =========================================================
   COLOUR THEME (matched to the blue dashboard image)
   Navy header bar, light-blue gradient background,
   frosted-white cards, blue sidebar, blue accents.
   ========================================================= */

:root {
    --navy: #0b3a7e;
    --navy-deep: #0a2f6b;
    --blue: #1d4ed8;
    --blue-soft: #dbe8fb;
    --blue-pill: #a9c8f4;
    --text-navy: #0b1f5c;
    --text-mid: #31508d;
    --text-muted: #5f7bb0;
    --glass: rgba(255, 255, 255, .68);
    --glass-border: rgba(255, 255, 255, .90);
    --line: rgba(61, 105, 180, .16);
    --shadow: 0 10px 30px rgba(36, 80, 160, .12);
}


/* =========================================================
   RESET
   ========================================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(180deg, #dce9fa 0%, #c3d8f4 50%, #a9c6ee 100%);
    background-attachment: fixed;
    color: var(--text-navy);
    display: flex;
    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {
    width: 250px;
    min-height: 100vh;
    background: linear-gradient(180deg, rgba(214, 229, 250, .95), rgba(190, 212, 244, .95));
    border-right: 1px solid var(--line);
    padding: 20px;
    flex-shrink: 0;
    box-shadow: 4px 0 24px rgba(36, 80, 160, .08);
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 30px;
    font-weight: bold;
    color: var(--navy);
}

.logo i {
    color: var(--navy);
    font-size: 25px;
}

.logo small {
    color: var(--text-muted) !important;
}

.nav-list {
    list-style: none;
}

.nav-item {
    margin-bottom: 7px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    padding: 12px 15px;
    border-radius: 14px;
    text-decoration: none;
    color: var(--text-navy);
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
}

.nav-link i {
    width: 20px;
    text-align: center;
    color: var(--text-mid);
}

.nav-link:hover {
    background: rgba(255, 255, 255, .55);
    color: var(--navy);
}


/* =========================================================
   ACTIVE SIDEBAR
   ========================================================= */

.nav-link.active {
    background: var(--blue-pill);
    color: var(--navy) !important;
    box-shadow: 0 4px 14px rgba(36, 80, 160, .12);
}

.nav-link.active i {
    color: var(--navy);
}

.nav-link.active:hover {
    background: #98bdef;
    color: var(--navy) !important;
}


/* =========================================================
   BADGES
   ========================================================= */

.badge {
    margin-left: auto;
    min-width: 22px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: #fff;
    border-radius: 20px;
    font-size: 11px;
}

.badge-blue {
    background: #2563eb;
}


/* =========================================================
   MAIN
   ========================================================= */

.main-content {
    flex: 1;
    padding: 20px 30px;
    min-width: 0;
}


/* =========================================================
   TOP BANNER
   ========================================================= */

.top-banner {
    background: var(--glass);
    color: var(--navy);
    text-align: center;
    padding: 10px;
    border-radius: 14px;
    border: 1px solid var(--glass-border);
    margin-bottom: 20px;
    font-weight: bold;
    letter-spacing: 1px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}


/* =========================================================
   TOP BAR (navy header like the image)
   ========================================================= */

.top-bar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-bottom: 25px;
    gap: 20px;
    padding: 14px 22px;
    background: var(--navy);
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(11, 58, 126, .28);
}




.topbar-welcome {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-right: auto;
    min-width: 0;
    color: #fff;
}

.topbar-welcome-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    color: #fff;
    font-size: 19px;
    background: linear-gradient(135deg, rgba(255,255,255,.25), rgba(147,197,253,.18));
    border: 1px solid rgba(255,255,255,.25);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
}

.topbar-welcome-copy {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.topbar-welcome-copy strong {
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: .2px;
}

.topbar-welcome-copy small {
    color: rgba(231,241,255,.78);
    font-size: 11px;
}


/* =========================================================
   USER
   ========================================================= */

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-menu > span {
    color: #fff;
}

.lang-select {
    padding: 8px 10px;
    border: 1px solid rgba(255, 255, 255, .30);
    border-radius: 12px;
    background: rgba(255, 255, 255, .16);
    color: #fff;
    cursor: pointer;
}

.lang-select option {
    color: var(--text-navy);
    background: #fff;
}

.notification-link {
    position: relative;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    text-decoration: none;
    border-radius: 50%;
}

.notification-link:hover {
    background: rgba(255, 255, 255, .18);
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: white;
    border: 2px solid var(--navy);
    border-radius: 50%;
    font-size: 9px;
}

.user-profile-img {
    width: 36px;
    height: 36px;
    object-fit: cover;
    display: block;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, .85);
}

.profile-initials {
    align-items: center;
    justify-content: center;
    background-color: #6e9be8;
    color: #fff;
    font-size: 13px;
    font-weight: bold;
    box-sizing: border-box;
}


/* =========================================================
   WELCOME
   ========================================================= */

.welcome-card {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    min-height: 130px;
    padding: 25px;
    margin-bottom: 25px;
    border-radius: 22px;
    display: flex;
    align-items: center;
    background-color: #dbe8fb;
    background-image: url('uploads/welcome-bg.png');
    background-repeat: no-repeat;
    background-position: center center;
    background-size: cover;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow);
}

/* Light overlay keeps welcome text readable over the full-width photo. */
.welcome-card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(
        90deg,
        rgba(235, 243, 253, 0.92) 0%,
        rgba(220, 233, 250, 0.72) 42%,
        rgba(190, 212, 244, 0.30) 100%
    );
}

.welcome-text h2 {
    color: var(--text-navy);
    font-size: 23px;
    margin-bottom: 7px;
}

.welcome-text p {
    color: var(--text-mid);
    font-size: 14px;
}


/* =========================================================
   STATS
   ========================================================= */

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: var(--glass);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: var(--shadow);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-mid);
    font-size: 13px;
    font-weight: 600;
}

.stat-icon {
    padding: 8px;
    border-radius: 10px;
    background: #d4e3fa !important;
    color: #1d4ed8 !important;
}

.stat-value {
    font-size: 22px;
    font-weight: bold;
    color: var(--text-navy);
}

.stat-link {
    color: #0b3d91;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.stat-link:hover {
    text-decoration: underline;
}


/* =========================================================
   LOWER GRID
   ========================================================= */

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.card {
    background: var(--glass);
    border: 1px solid var(--glass-border);
    border-radius: 22px;
    padding: 20px;
    min-width: 0;
    box-shadow: var(--shadow);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.card-title {
    font-size: 16px;
    font-weight: bold;
    color: var(--text-navy);
}


/* =========================================================
   TABLE
   ========================================================= */

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

th {
    text-align: left;
    padding: 10px;
    color: var(--text-mid);
    border-bottom: 1px solid var(--line);
}

td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(61, 105, 180, .09);
    vertical-align: middle;
    color: var(--text-navy);
}

td small {
    color: var(--text-muted) !important;
}

.item-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.item-img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 12px;
    background: var(--blue-soft);
}


/* =========================================================
   BUTTONS
   ========================================================= */

.btn-act {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
}

.btn-accept {
    background: #bbf0c8;
    color: #166534;
}

.btn-reject {
    background: #fca5a5;
    color: #991b1b;
}

.btn-accept:hover {
    background: #86e0a0;
}

.btn-reject:hover {
    background: #f87171;
}


/* =========================================================
   STATUS
   ========================================================= */

.badge-status {
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: bold;
}

.status-inuse {
    background: #7ea8f2;
    color: #0b2f78;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width:1100px) {

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

}

@media(max-width:800px) {

    body {
        display: block;
    }

    .sidebar {
        width: 100%;
        min-height: auto;
    }

    .main-content {
        padding: 15px;
    }

    .top-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .topbar-welcome {
        margin-right: 0;
    }

    .user-menu {
        justify-content: flex-end;
        flex-wrap: wrap;
    }

}

@media(max-width:600px) {

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .welcome-card {
        background-size: cover;
        background-position: center center;
    }

    .welcome-card::before {
        background: rgba(225, 236, 252, 0.78);
    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-tractor"></i>

        <span>

            <?php
            if (($current_lang ?? 'en') === 'kn') {
                echo 'ಕೃಷಿ';
            } elseif (($current_lang ?? 'en') === 'hi') {
                echo 'कृषि';
            } else {
                echo 'AGRICULTURE';
            }
            ?><br>
            <small style="font-size:9px;color:#64748b;">
                <?php
                if (($current_lang ?? 'en') === 'kn') {
                    echo 'ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ';
                } elseif (($current_lang ?? 'en') === 'hi') {
                    echo 'उपकरण किराया प्रणाली';
                } else {
                    echo 'EQUIPMENT RENTAL SYSTEM';
                }
                ?>
            </small>

        </span>

    </div>


    <ul class="nav-list">


        <!-- DASHBOARD -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/lender_dashboard.php"

                class="nav-link <?php echo activeMenu('lender_dashboard.php'); ?>"

            >

                <i class="fa-solid fa-chart-line"></i>

                <span>

                    <?php echo __('dashboard'); ?>

                </span>

            </a>

        </li>


        <!-- ADD EQUIPMENT -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/add_item.php"

                class="nav-link <?php echo activeMenu('add_item.php'); ?>"

            >

                <i class="fa-solid fa-circle-plus"></i>

                <span>

                    <?php echo __('add_equipment'); ?>

                </span>

            </a>

        </li>


        <!-- MY EQUIPMENT -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/my_equipment.php"

                class="nav-link <?php echo activeMenu('my_equipment.php'); ?>"

            >

                <i class="fa-solid fa-list"></i>

                <span>

                    <?php echo __('my_equipment'); ?>

                </span>

            </a>

        </li>


        <!-- =================================================
             RENTAL REQUESTS
             ================================================= -->

        <li class="nav-item">

            <a href="rental_request.php"

                class="nav-link <?php echo activeMenu('rental_request.php'); ?>"

            >

                <i class="fa-solid fa-clock-rotate-left"></i>

                <span>

                    <?php echo __('rental_requests'); ?>

                </span>

                <span class="badge">

                    <?php echo $pending_requests; ?>

                </span>

            </a>

        </li>


        <!-- =================================================
             ACTIVE RENTALS
             ================================================= -->

        <li class="nav-item">

           <a href="active_rentals.php"

                class="nav-link <?php echo activeMenu('active_rentals.php'); ?>"

            >

                <i class="fa-solid fa-truck-ramp-box"></i>

                <span>

                    <?php echo __('active_rentals'); ?>

                </span>

                <span class="badge badge-blue">

                    <?php echo $active_rentals; ?>

                </span>

            </a>

        </li>


        <!-- MY BOOKINGS -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/lender_my_bookings.php"

                class="nav-link <?php echo activeMenu('lender_my_bookings.php'); ?>"

            >

                <i class="fa-solid fa-calendar-check"></i>

                <span>

                    <?php echo __('my_bookings'); ?>

                </span>

            </a>

        </li>


        <!-- RENTAL HISTORY -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/lender_rental_history.php"

                class="nav-link <?php echo activeMenu('lender_rental_history.php'); ?>"

            >

                <i class="fa-solid fa-history"></i>

                <span>

                    <?php echo __('rental_history'); ?>

                </span>

            </a>

        </li>


        <!-- REVIEWS -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/reviews.php"

                class="nav-link <?php echo activeMenu('reviews.php'); ?>"

            >

                <i class="fa-solid fa-star"></i>

                <span>

                    <?php echo __('reviews'); ?>

                </span>

            </a>

        </li>


        <!-- TOTAL EARNINGS -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/total_earnings.php"

                class="nav-link <?php echo activeMenu('total_earnings.php'); ?>"

            >

                <i class="fa-solid fa-wallet"></i>

                <span>

                    <?php echo __('total_earnings'); ?>

                </span>

            </a>

        </li>


                <!-- AI INSIGHTS -->

        <li class="nav-item">

            <a
                href="<?php echo $base_url; ?>/lender_ai_insights.php?lang=<?php echo urlencode($current_lang ?? 'en'); ?>"
                class="nav-link <?php echo in_array($current_page, ['lender_ai_insights.php', 'lender_ai_equipment_recommendations.php', 'lender_ai_maintenance.php', 'lender_ai_review_analysis.php', 'lender_ai_revenue_prediction.php'], true) ? 'active' : ''; ?>"
            >

                <i class="fa-solid fa-wand-magic-sparkles"></i>

                <span><?php
                    if (($current_lang ?? 'en') === 'kn') {
                        echo 'AI ಒಳನೋಟಗಳು';
                    } elseif (($current_lang ?? 'en') === 'hi') {
                        echo 'AI जानकारी';
                    } else {
                        echo 'AI Insights';
                    }
                 ?></span>

            </a>

        </li>


        <!-- PROFILE -->

        <li class="nav-item">

            <a

                href="<?php echo $base_url; ?>/profile.php"

                class="nav-link <?php echo activeMenu('profile.php'); ?>"

            >

                <i class="fa-regular fa-user"></i>

                <span>

                    <?php
                    if (($current_lang ?? 'en') === 'kn') {
                        echo 'ನನ್ನ ಪ್ರೊಫೈಲ್';
                    } elseif (($current_lang ?? 'en') === 'hi') {
                        echo 'मेरी प्रोफ़ाइल';
                    } else {
                        echo 'My Profile';
                    }
                    ?>

                </span>

            </a>

        </li>


        <!-- LOGOUT -->

        <li

            class="nav-item"

            style="margin-top:20px;"

        >

            <a

                href="<?php echo $base_url; ?>/logout.php"

                class="nav-link"

                style="color:#ef4444;"

            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>

                    <?php echo __('logout'); ?>

                </span>

            </a>

        </li>


    </ul>

</aside>


<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->

<main class="main-content">


    <!-- TITLE -->

    <div class="top-banner">

        <?php echo __('lender_dashboard'); ?>

    </div>


    <!-- =====================================================
         TOP BAR
         ===================================================== -->

    <div class="top-bar">

        <div class="topbar-welcome">
            <div class="topbar-welcome-icon" aria-hidden="true">
                <i class="fa-solid fa-tractor"></i>
            </div>
            <div class="topbar-welcome-copy">
                <strong><?php
                    if (($current_lang ?? 'en') === 'kn') {
                        echo 'ಲೇಂದ್ರ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ಗೆ ಸ್ವಾಗತ';
                    } elseif (($current_lang ?? 'en') === 'hi') {
                        echo 'लेंडर डैशबोर्ड में आपका स्वागत है';
                    } else {
                        echo 'Welcome to your dashboard';
                    }
                ?></strong>
                <small><?php
                    if (($current_lang ?? 'en') === 'kn') {
                        echo 'ನಿಮ್ಮ ಉಪಕರಣಗಳು ಮತ್ತು ಬಾಡಿಗೆಗಳನ್ನು ನಿರ್ವಹಿಸಿ';
                    } elseif (($current_lang ?? 'en') === 'hi') {
                        echo 'अपने उपकरण और किराये आसानी से प्रबंधित करें';
                    } else {
                        echo 'Manage your equipment and rentals with ease';
                    }
                ?></small>
            </div>
        </div>

        <!-- USER -->
        <div class="user-menu">


            <select

                class="lang-select"

                onchange="changeLanguage(this.value)"

            >

                <option

                    value="en"

                    <?php

                    echo (($current_lang ?? 'en') === 'en')

                        ? 'selected'

                        : '';

                    ?>

                >

                    🌐 English

                </option>

                <option

                    value="hi"

                    <?php

                    echo (($current_lang ?? 'en') === 'hi')

                        ? 'selected'

                        : '';

                    ?>

                >

                    🌐 हिन्दी

                </option>

                <option

                    value="kn"

                    <?php

                    echo (($current_lang ?? 'en') === 'kn')

                        ? 'selected'

                        : '';

                    ?>

                >

                    🌐 ಕನ್ನಡ

                </option>

            </select>


            <!-- NOTIFICATION -->

            <a

                href="<?php echo $base_url; ?>/lender_notifications.php?lang=<?php echo urlencode($current_lang ?? 'en'); ?>"

                class="notification-link"

            >

                <i class="fa-regular fa-bell"></i>

                <?php if ($unread_notifications > 0): ?>

                    <span class="notification-badge">

                        <?php

                        echo $unread_notifications > 99

                            ? '99+'

                            : $unread_notifications;

                        ?>

                    </span>

                <?php endif; ?>

            </a>


            <span style="font-size:14px;font-weight:bold;">

                <?php echo htmlspecialchars($lender_name); ?>

            </span>


            <a

                href="<?php echo $base_url; ?>/profile.php"

            >


<?php
$nameParts = preg_split('/\s+/', trim($lender_name));
$initials = strtoupper(substr($nameParts[0] ?? 'L', 0, 1));

if (count($nameParts) > 1) {
    $initials .= strtoupper(substr(end($nameParts), 0, 1));
}
?>

<img
    src="<?php echo $base_url; ?>/uploads/<?php echo htmlspecialchars($profile_pic); ?>"
    class="user-profile-img"
    alt="Profile"
    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
>

<span
    class="user-profile-img profile-initials"
    style="display:none;"
>
    <?php echo htmlspecialchars($initials); ?>
</span>


            </a>


        </div>

    </div>


    <!-- =====================================================
         WELCOME
         ===================================================== -->

    <section class="welcome-card">

        <div class="welcome-text">

            <h2>

                <?php echo __('welcome'); ?>,

                <?php echo htmlspecialchars($lender_name); ?>

                👋

            </h2>

            <p>

                <?php echo __('banner_subtitle'); ?>

            </p>

        </div>

    </section>


    <!-- =====================================================
         STATISTICS
         ===================================================== -->

    <section class="stats-grid">


        <!-- TOTAL EQUIPMENT -->

        <div class="stat-card">

            <div class="stat-header">

                <i

                    class="fa-solid fa-clipboard-list stat-icon"

                    style="background:#e0f2fe;color:#0284c7;"

                ></i>

                <?php echo __('total_equipment'); ?>

            </div>

            <div class="stat-value">

                <?php echo $total_equipment; ?>

            </div>

            <a

                href="<?php echo $base_url; ?>/my_equipment.php"

                class="stat-link"

            >

                <?php echo __('view_details'); ?>

            </a>

        </div>


        <!-- ACTIVE RENTALS -->

        <div class="stat-card">

            <div class="stat-header">

                <i

                    class="fa-solid fa-calendar-check stat-icon"

                    style="background:#e0f2fe;color:#0284c7;"

                ></i>

                <?php echo __('active_rentals'); ?>

            </div>

            <div class="stat-value">

                <?php echo $active_rentals; ?>

            </div>

            <a

                href="<?php echo $base_url; ?>/active_rentals.php"

                class="stat-link"

            >

                <?php echo __('view_details'); ?>

            </a>

        </div>


        <!-- RENTAL REQUESTS -->

        <div class="stat-card">

            <div class="stat-header">

                <i

                    class="fa-solid fa-hourglass-half stat-icon"

                    style="background:#fef3c7;color:#d97706;"

                ></i>

                <?php echo __('rental_requests'); ?>

            </div>

            <div class="stat-value">

                <?php echo $pending_requests; ?>

            </div>

            <a

                href="<?php echo $base_url; ?>/rental_request.php"

                class="stat-link"

            >

                <?php echo __('view_details'); ?>

            </a>

        </div>


        <!-- EARNINGS -->

        <div class="stat-card">

            <div class="stat-header">

                <i

                    class="fa-solid fa-indian-rupee-sign stat-icon"

                    style="background:#f3e8ff;color:#9333ea;"

                ></i>

                <?php echo __('total_earnings'); ?>

            </div>

            <div class="stat-value">

                ₹ <?php echo number_format($total_earnings, 2); ?>

            </div>

            <a

                href="<?php echo $base_url; ?>/total_earnings.php"

                class="stat-link"

            >

                <?php echo __('view_details'); ?>

            </a>

        </div>


    </section>


    <!-- =====================================================
         LOWER CONTENT
         ===================================================== -->

    <section class="dashboard-grid">


        <!-- =================================================
             REQUESTS
             ================================================= -->

        <div class="card">

            <div class="card-header">

                <span class="card-title">

                    <?php echo __('rental_requests'); ?>

                </span>

                <a

                    href="<?php echo $base_url; ?>/rental_request.php"

                    class="stat-link"

                >

                    <?php echo __('view_all'); ?> →

                </a>

            </div>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>

                                <?php echo __('renter'); ?>

                            </th>

                            <th>

                                <?php echo __('equipment'); ?>

                            </th>

                            <th>

                                <?php echo __('from_to'); ?>

                            </th>

                            <th>

                                <?php echo __('amount'); ?>

                            </th>

                            <th>

                                <?php echo __('action'); ?>

                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (

                        $requests_result &&

                        mysqli_num_rows($requests_result) > 0

                    ): ?>

                        <?php while (

                            $row = mysqli_fetch_assoc($requests_result)

                        ): ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(

                                        $row['renter_name']

                                    );
                                    ?>

                                </strong>

                                <br>

                                <small style="color:#64748b;">

                                    <?php
                                    echo htmlspecialchars(

                                        $row['renter_phone'] ?? 'N/A'

                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <div class="item-info">

                                    <img

                                        src="<?php

                                        echo !empty($row['image'])

                                            ? $base_url . '/uploads/' . htmlspecialchars($row['image'])

                                            : $base_url . '/uploads/default.png';

                                        ?>"

                                        class="item-img"

                                        alt="Equipment"

                                    >

                                    <span>

                                        <?php
                                        echo htmlspecialchars(

                                            $row['item_title']

                                        );
                                        ?>

                                    </span>

                                </div>

                            </td>


                            <td>

                                <?php
                                echo date(

                                    'd M',

                                    strtotime($row['start_date'])

                                );
                                ?>

                                -

                                <?php
                                echo date(

                                    'd M Y',

                                    strtotime($row['end_date'])

                                );
                                ?>

                            </td>


                            <td>

                                <strong>

                                    ₹<?php
                                    echo number_format(

                                        (float)$row['total_amount'],

                                        2

                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>
                                <td>
    <a
        href="<?php echo $base_url; ?>/rental_request.php"
        class="btn-act btn-view"
    >
        <i class="fa-solid fa-eye"></i>
        View Equipment
    </a>
</td>

                                
                            </td>

                        </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td

                                colspan="5"

                                style="

                                    text-align:center;

                                    color:#94a3b8;

                                    padding:25px;

                                "

                            >

                                <?php echo __('no_pending_requests'); ?>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- =================================================
             ACTIVE RENTALS
             ================================================= -->

        <div class="card">

            <div class="card-header">

                <span class="card-title">

                    <?php echo __('active_rentals'); ?>

                </span>

                <a

                    href="<?php echo $base_url; ?>/active_rentals.php"

                    class="stat-link"

                >

                    <?php echo __('view_all'); ?> →

                </a>

            </div>


            <?php if (

                $active_result &&

                mysqli_num_rows($active_result) > 0

            ): ?>

                <?php while (

                    $active = mysqli_fetch_assoc($active_result)

                ): ?>

                    <div

                        style="

                            display:flex;

                            justify-content:space-between;

                            align-items:center;

                            gap:10px;

                            margin-bottom:15px;

                            padding-bottom:10px;

                            border-bottom:1px solid #f1f5f9;

                        "

                    >

                        <div class="item-info">

                            <img

                                src="<?php

                                echo !empty($active['image'])

                                    ? $base_url . '/uploads/' . htmlspecialchars($active['image'])

                                    : $base_url . '/uploads/default.png';

                                ?>"

                                class="item-img"

                                alt="Equipment"

                            >

                            <div>

                                <strong style="font-size:13px;">

                                    <?php
                                    echo htmlspecialchars(

                                        $active['item_title']

                                    );
                                    ?>

                                </strong>

                                <br>

                                <small style="color:#64748b;">

                                    <?php echo __('renter'); ?>:

                                    <?php
                                    echo htmlspecialchars(

                                        $active['renter_name']

                                    );
                                    ?>

                                </small>

                            </div>

                        </div>


                        <span

                            class="badge-status status-inuse"

                        >

                            <?php echo __('in_use'); ?>

                        </span>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p

                    style="

                        text-align:center;

                        color:#94a3b8;

                        padding:30px 10px;

                        font-size:13px;

                    "

                >

                    <?php echo __('no_active_rentals'); ?>

                </p>

            <?php endif; ?>

        </div>


    </section>


</main>


<script>

/* =========================================================
   LANGUAGE
   ========================================================= */

function changeLanguage(lang) {

    window.location.href =

        "<?php echo $base_url; ?>/lender_dashboard.php?lang="

        + encodeURIComponent(lang);

}

</script>


</body>

</html>