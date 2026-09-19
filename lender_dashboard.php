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
    header("Location: /agriculture-equipment-rental/login.php");
    exit();
}

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'lender') {
    header("Location: /agriculture-equipment-rental/login.php");
    exit();
}


$lender_id = (int)$_SESSION['user_id'];

$lender_name = $_SESSION['full_name'] ?? 'Lender';


/* =========================================================
   IMPORTANT:
   Your XAMPP project folder is:
   agriculture-equipment-rental
   ========================================================= */
$base_url = '/agriculture-equipment-rental';


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
    FROM items
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
    INNER JOIN items i
        ON b.equipment_id = i.item_id
    WHERE i.lender_id = $lender_id
    AND LOWER(b.status) = 'approved'
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
    INNER JOIN items i
        ON b.equipment_id = i.item_id
    WHERE i.lender_id = $lender_id
    AND LOWER(b.status) = 'pending'
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
    INNER JOIN items i
        ON b.equipment_id = i.item_id
    WHERE i.lender_id = $lender_id
    AND LOWER(b.status) IN ('approved', 'completed')
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

    INNER JOIN items i
        ON b.equipment_id = i.item_id

    INNER JOIN users u
        ON b.renter_id = u.user_id

    WHERE i.lender_id = $lender_id
    AND LOWER(b.status) = 'pending'

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

    INNER JOIN items i
        ON b.equipment_id = i.item_id

    INNER JOIN users u
        ON b.renter_id = u.user_id

    WHERE i.lender_id = $lender_id
    AND LOWER(b.status) = 'approved'

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

<title>
Lender Dashboard - Agriculture Equipment Rental System
</title>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
>

<style>

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
    background: #f4f6f9;
    color: #333;
    display: flex;
    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {
    width: 250px;
    min-height: 100vh;
    background: #fff;
    border-right: 1px solid #e0e0e0;
    padding: 20px;
    flex-shrink: 0;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 30px;
    font-weight: bold;
    color: #1e3a8a;
}

.logo i {
    color: #0f4c5c;
    font-size: 25px;
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

    border-radius: 8px;

    text-decoration: none;

    color: #64748b;

    font-size: 14px;
    font-weight: 500;

    transition: all 0.2s ease;

    cursor: pointer;
}

.nav-link i {
    width: 20px;
    text-align: center;
}

.nav-link:hover {
    background: #e0f2fe;
    color: #0f4c5c;
}


/* =========================================================
   ACTIVE SIDEBAR
   ========================================================= */

.nav-link.active {
    background: #0f4c5c;
    color: #fff !important;
}

.nav-link.active:hover {
    background: #0b3844;
    color: #fff !important;
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
    background: #3b82f6;
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
    background: #0f4c5c;
    color: #fff;

    text-align: center;

    padding: 9px;

    border-radius: 6px;

    margin-bottom: 20px;

    font-weight: bold;

    letter-spacing: 1px;
}


/* =========================================================
   TOP BAR
   ========================================================= */

.top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 25px;

    gap: 20px;
}

.search-box {
    position: relative;
    width: 350px;
}

.search-box input {
    width: 100%;

    padding: 10px 45px 10px 38px;

    border: 1px solid #cbd5e1;

    border-radius: 25px;

    outline: none;
}

.search-box > i {
    position: absolute;
    left: 13px;
    top: 11px;

    color: #94a3b8;
}

.mic-btn {
    position: absolute;

    right: 5px;
    top: 4px;

    width: 32px;
    height: 32px;

    border: none;
    border-radius: 50%;

    background: #0f4c5c;

    color: #fff;

    cursor: pointer;
}

.mic-btn:hover {
    background: #0b3844;
}

.mic-btn.recording {
    background: #ef4444;
}


/* =========================================================
   USER
   ========================================================= */

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lang-select {
    padding: 8px 10px;

    border: 1px solid #cbd5e1;

    border-radius: 6px;

    background: #fff;

    cursor: pointer;
}

.notification-link {
    position: relative;

    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #0f4c5c;

    text-decoration: none;

    border-radius: 50%;
}

.notification-link:hover {
    background: #e0f2fe;
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

    border: 2px solid white;

    border-radius: 50%;

    font-size: 9px;
}

.user-profile-img {
    width: 36px;
    height: 36px;

    object-fit: cover;

    border-radius: 50%;

    border: 2px solid #0f4c5c;
}


/* =========================================================
   WELCOME
   ========================================================= */

.welcome-card {
    min-height: 130px;

    padding: 25px;

    margin-bottom: 25px;

    border-radius: 12px;

    display: flex;
    align-items: center;

    background:
        #e0f2fe
        url('<?php echo $base_url; ?>/uploads/welcome-bg.jpg')
        no-repeat right center;

    background-size: contain;
}

.welcome-text h2 {
    color: #0f172a;
    font-size: 23px;
    margin-bottom: 7px;
}

.welcome-text p {
    color: #334155;
    font-size: 14px;
}


/* =========================================================
   STATS
   ========================================================= */

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}

.stat-card {
    background: #fff;

    border: 1px solid #e2e8f0;

    border-radius: 10px;

    padding: 20px;

    display: flex;
    flex-direction: column;

    gap: 10px;
}

.stat-header {
    display: flex;
    align-items: center;
    gap: 10px;

    color: #64748b;

    font-size: 13px;
    font-weight: 600;
}

.stat-icon {
    padding: 8px;
    border-radius: 6px;
}

.stat-value {
    font-size: 22px;
    font-weight: bold;
    color: #0f172a;
}

.stat-link {
    color: #0284c7;

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
    background: #fff;

    border: 1px solid #e2e8f0;

    border-radius: 10px;

    padding: 20px;

    min-width: 0;
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
    color: #0f172a;
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

    color: #64748b;

    border-bottom: 1px solid #e2e8f0;
}

td {
    padding: 12px 10px;

    border-bottom: 1px solid #f1f5f9;

    vertical-align: middle;
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

    border-radius: 6px;

    background: #f1f5f9;
}


/* =========================================================
   BUTTONS
   ========================================================= */

.btn-act {
    display: inline-block;

    padding: 6px 11px;

    border-radius: 6px;

    text-decoration: none;

    font-size: 12px;
    font-weight: bold;
}

.btn-accept {
    background: #22c55e;
    color: white;
}

.btn-reject {
    background: #ef4444;
    color: white;
}

.btn-accept:hover {
    background: #16a34a;
}

.btn-reject:hover {
    background: #dc2626;
}


/* =========================================================
   STATUS
   ========================================================= */

.badge-status {
    padding: 4px 8px;

    border-radius: 12px;

    font-size: 11px;

    font-weight: bold;
}

.status-inuse {
    background: #dbeafe;
    color: #1d4ed8;
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

    .search-box {
        width: 100%;
    }

    .user-menu {
        justify-content: flex-end;
    }
}

@media(max-width:600px) {

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .welcome-card {
        background-image: none;
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
            AGRICULTURE<br>

            <small style="font-size:9px;color:#64748b;">
                EQUIPMENT RENTAL SYSTEM
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
                href="<?php echo $base_url; ?>/my_bookings.php"
                class="nav-link <?php echo activeMenu('my_bookings.php'); ?>"
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
                href="<?php echo $base_url; ?>/rental_history.php"
                class="nav-link <?php echo activeMenu('rental_history.php'); ?>"
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
                href="<?php echo $base_url; ?>/Reviews.php"
                class="nav-link <?php echo activeMenu('Reviews.php'); ?>"
            >

                <i class="fa-solid fa-star"></i>

                <span>
                    <?php echo __('Reviews'); ?>
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


        <!-- PROFILE -->

        <li class="nav-item">

            <a
                href="<?php echo $base_url; ?>/profile.php"
                class="nav-link <?php echo activeMenu('profile.php'); ?>"
            >

                <i class="fa-regular fa-user"></i>

                <span>
                    My Profile
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


        <!-- SEARCH -->

        <form
            action="<?php echo $base_url; ?>/search_equipment.php"
            method="GET"
            id="searchForm"
            class="search-box"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                name="q"
                id="searchInput"
                placeholder="<?php echo __('search_placeholder'); ?>"
            >

            <button
                type="button"
                id="micBtn"
                class="mic-btn"
                onclick="startVoiceSearch()"
            >

                <i
                    class="fa-solid fa-microphone"
                    id="micIcon"
                ></i>

            </button>

        </form>


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

                <img
                    src="<?php echo $base_url; ?>/uploads/<?php echo htmlspecialchars($profile_pic); ?>"
                    class="user-profile-img"
                    alt="Profile"
                    onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($lender_name); ?>&background=0f4c5c&color=fff';"
                >

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

                                <a
                                    href="<?php echo $base_url; ?>/update_booking.php?id=<?php echo (int)$row['booking_id']; ?>&action=approve"
                                    class="btn-act btn-accept"
                                    onclick="return confirm('Approve this rental request?');"
                                >
                                    <?php echo __('accept'); ?>
                                </a>

                                <a
                                    href="<?php echo $base_url; ?>/update_booking.php?id=<?php echo (int)$row['booking_id']; ?>&action=reject"
                                    class="btn-act btn-reject"
                                    onclick="return confirm('Reject this rental request?');"
                                >
                                    <?php echo __('reject'); ?>
                                </a>

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


/* =========================================================
   VOICE SEARCH
   ========================================================= */

function startVoiceSearch() {

    const SpeechRecognition =
        window.SpeechRecognition ||
        window.webkitSpeechRecognition;

    if (!SpeechRecognition) {

        alert(
            "Voice search is not supported. " +
            "Please use Google Chrome or Microsoft Edge."
        );

        return;
    }


    const recognition =
        new SpeechRecognition();


    const input =
        document.getElementById('searchInput');

    const form =
        document.getElementById('searchForm');

    const mic =
        document.getElementById('micBtn');

    const icon =
        document.getElementById('micIcon');


    const lang =
        "<?php echo htmlspecialchars($current_lang ?? 'en'); ?>";


    if (lang === 'hi') {

        recognition.lang = 'hi-IN';

    } else if (lang === 'kn') {

        recognition.lang = 'kn-IN';

    } else {

        recognition.lang = 'en-US';

    }


    recognition.interimResults = false;

    recognition.maxAlternatives = 1;


    mic.classList.add('recording');

    icon.className =
        'fa-solid fa-spinner fa-spin';


    recognition.start();


    recognition.onresult = function(event) {

        input.value =
            event.results[0][0].transcript;

        mic.classList.remove('recording');

        icon.className =
            'fa-solid fa-microphone';

        form.submit();

    };


    recognition.onerror = function() {

        mic.classList.remove('recording');

        icon.className =
            'fa-solid fa-microphone';

        alert(
            "Could not recognize your voice. Please try again."
        );

    };


    recognition.onend = function() {

        mic.classList.remove('recording');

        icon.className =
            'fa-solid fa-microphone';

    };

}

</script>


</body>
</html>