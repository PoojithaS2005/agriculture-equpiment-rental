<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$allowed_languages = ['en', 'kn', 'hi'];

if (isset($_GET['lang'])) {

    $selected_lang = $_GET['lang'];

    if (in_array($selected_lang, $allowed_languages, true)) {
        $_SESSION['lang'] = $selected_lang;
    }

    header("Location: rental_history.php");
    exit;
}

$current_lang = $_SESSION['lang'] ?? 'en';

if (!in_array($current_lang, $allowed_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

function tr($key, $default = '')
{
    global $translations, $current_lang;

    if (isset($translations[$current_lang][$key])) {
        return $translations[$current_lang][$key];
    }

    if (isset($translations['en'][$key])) {
        return $translations['en'][$key];
    }

    return $default !== '' ? $default : $key;
}

$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Renter';
$user_location = $_SESSION['location'] ?? '';

$user_sql = "SELECT * FROM users WHERE user_id = ?";

$user_stmt = mysqli_prepare($conn, $user_sql);

if ($user_stmt) {

    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);

    $user_result = mysqli_stmt_get_result($user_stmt);

    if ($user = mysqli_fetch_assoc($user_result)) {

        if (!empty($user['full_name'])) {
            $user_name = $user['full_name'];
        }

        if (isset($user['location']) && !empty($user['location'])) {
            $user_location = $user['location'];
        } elseif (isset($user['address']) && !empty($user['address'])) {
            $user_location = $user['address'];
        } elseif (isset($user['city']) && !empty($user['city'])) {
            $user_location = $user['city'];
        }
    }

    mysqli_stmt_close($user_stmt);
}

if (empty($user_location)) {
    $user_location = tr('location_not_set', 'Location not set');
}

$user_initial = 'R';

if (!empty($user_name)) {
    $user_initial = mb_strtoupper(
        mb_substr(trim($user_name), 0, 1, 'UTF-8'),
        'UTF-8'
    );
}

$bookings = [];

$booking_sql = "
    SELECT
        b.*,
        e.title AS equipment_title,
        e.category AS equipment_category,
        e.image AS equipment_image
    FROM bookings b
    LEFT JOIN equipment e
        ON b.equipment_id = e.equipment_id
    WHERE b.renter_id = ?
    ORDER BY b.booking_id DESC
";

$booking_stmt = mysqli_prepare($conn, $booking_sql);

if ($booking_stmt) {

    mysqli_stmt_bind_param($booking_stmt, "i", $user_id);
    mysqli_stmt_execute($booking_stmt);

    $booking_result = mysqli_stmt_get_result($booking_stmt);

    while ($booking = mysqli_fetch_assoc($booking_result)) {
        $bookings[] = $booking;
    }

    mysqli_stmt_close($booking_stmt);
}

function getBookingValue($row, $keys, $default = '')
{
    foreach ($keys as $key) {

        if (isset($row[$key]) && $row[$key] !== '') {
            return $row[$key];
        }
    }

    return $default;
}

function getEquipmentImage($image)
{
    if (empty($image)) {
        return '';
    }

    $image = str_replace('\\', '/', trim($image));

    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }

    $filename = basename($image);

    $possible_files = [
        __DIR__ . '/' . $image,
        __DIR__ . '/images/' . $filename,
        __DIR__ . '/uploads/' . $filename,
        __DIR__ . '/uploads/equipment/' . $filename
    ];

    foreach ($possible_files as $file) {

        if (file_exists($file)) {

            $file = str_replace('\\', '/', $file);

            if (strpos($file, '/uploads/equipment/') !== false) {
                return 'uploads/equipment/' . $filename;
            }

            if (strpos($file, '/uploads/') !== false) {
                return 'uploads/' . $filename;
            }

            if (strpos($file, '/images/') !== false) {
                return 'images/' . $filename;
            }

            return $image;
        }
    }

    return '';
}

function formatBookingDate($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d M Y', $timestamp);
}

function formatBookingTime($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '';
    }

    return date('h:i A', $timestamp);
}

function calculateDays($start_date, $end_date)
{
    if (empty($start_date) || empty($end_date)) {
        return 0;
    }

    $start = strtotime($start_date);
    $end = strtotime($end_date);

    if ($start === false || $end === false) {
        return 0;
    }

    $days = floor(($end - $start) / 86400) + 1;

    return max(1, $days);
}

function getStatusClass($status)
{
    $status = strtolower(trim($status));

    if (
        $status === 'completed' ||
        $status === 'complete'
    ) {
        return 'status-completed';
    }

    if (
        $status === 'cancelled' ||
        $status === 'canceled'
    ) {
        return 'status-cancelled';
    }

    if (
        $status === 'pending'
    ) {
        return 'status-pending';
    }

    if (
        $status === 'confirmed' ||
        $status === 'approved'
    ) {
        return 'status-confirmed';
    }

    if (
        $status === 'ongoing' ||
        $status === 'active'
    ) {
        return 'status-ongoing';
    }

    return 'status-pending';
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
<?= htmlspecialchars(tr('rental_history', 'Rental History')) ?>
</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f8fafb;
    color: #172033;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 270px;
    height: 100vh;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    padding: 24px 18px;
    z-index: 100;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 40px;
    padding-left: 5px;
}

.logo-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
}

.logo-content {
    display: flex;
    flex-direction: column;
}

.logo-text {
    color: #168b45;
    font-size: 18px;
    font-weight: bold;
    line-height: 20px;
}

.logo-sub {
    color: #555;
    font-size: 8px;
    margin-top: 3px;
    letter-spacing: 0.2px;
}

.nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.nav-item {
    width: 100%;
    height: 43px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 14px;
    border-radius: 8px;
    text-decoration: none;
    color: #172033;
    font-size: 14px;
    transition: 0.2s;
}

.nav-item:hover {
    background: #eef8f1;
    color: #168b45;
}

.nav-item.active {
    background: #3d9d3f;
    color: white;
}

.nav-icon {
    width: 21px;
    min-width: 21px;
    height: 21px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: 17px;
    line-height: 1;
}

.nav-text {
    flex: 1;
    white-space: nowrap;
}

.notification-count {
    width: 21px;
    height: 21px;
    min-width: 21px;
    border-radius: 50%;
    background: #46a447;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    margin-left: auto;
}

.nav-item.active .notification-count {
    background: white;
    color: #3d9d3f;
}

.main {
    margin-left: 270px;
    min-height: 100vh;
}

.topbar {
    height: 80px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 25px;
    padding: 0 35px;
}

.location {
    color: #555;
    font-size: 13px;
}

.language {
    position: relative;
}

.language-button {
    border: 1px solid #ddd;
    background: white;
    border-radius: 8px;
    padding: 9px 13px;
    cursor: pointer;
    font-size: 13px;
}

.language-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 42px;
    width: 150px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    overflow: hidden;
    z-index: 1000;
}

.language:hover .language-menu {
    display: block;
}

.language-menu a {
    display: block;
    padding: 12px 14px;
    text-decoration: none;
    color: #333;
    font-size: 13px;
}

.language-menu a:hover {
    background: #eef8f1;
    color: #168b45;
}

.profile-link {
    text-decoration: none;
    color: #172033;
}

.profile {
    display: flex;
    align-items: center;
    gap: 9px;
}

.profile-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: #168b45;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: bold;
}

.profile-name {
    font-size: 13px;
    font-weight: bold;
}

.profile-role {
    color: #777;
    font-size: 11px;
    margin-top: 3px;
}

.content {
    padding: 30px 35px 40px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    font-size: 13px;
}

.breadcrumb a {
    color: #168b45;
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb-arrow {
    color: #999;
}

.breadcrumb-current {
    color: #555;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.page-title h1 {
    font-size: 27px;
    margin-bottom: 7px;
}

.page-title p {
    color: #666;
    font-size: 14px;
}

.status-filter {
    position: relative;
}

.status-filter select {
    min-width: 135px;
    padding: 10px 13px;
    border: 1px solid #dfe3e8;
    border-radius: 7px;
    background: white;
    color: #333;
    font-size: 13px;
    outline: none;
}

.history-box {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th {
    text-align: left;
    padding: 13px 10px;
    background: #ffffff;
    color: #333;
    font-size: 11px;
    font-weight: bold;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}

.history-table td {
    padding: 13px 10px;
    border-bottom: 1px solid #edf0f2;
    vertical-align: middle;
    font-size: 11px;
}

.history-table tr:last-child td {
    border-bottom: none;
}

.equipment-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 180px;
}

.equipment-image {
    width: 48px;
    height: 42px;
    border-radius: 6px;
    object-fit: cover;
    background: #f1f4f1;
    border: 1px solid #e4e7e4;
}

.equipment-placeholder {
    width: 48px;
    height: 42px;
    border-radius: 6px;
    background: #f1f4f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.equipment-info {
    min-width: 0;
}

.equipment-name {
    font-weight: bold;
    color: #172033;
    margin-bottom: 5px;
    font-size: 11px;
}

.equipment-category {
    color: #777;
    font-size: 9px;
}

.booking-id {
    color: #333;
    font-weight: 500;
    white-space: nowrap;
}

.rental-period {
    line-height: 17px;
    white-space: nowrap;
}

.rental-period strong {
    font-weight: bold;
}

.rental-days {
    color: #666;
    font-size: 9px;
}

.total-amount {
    font-weight: bold;
    color: #172033;
    white-space: nowrap;
}

.advance {
    color: #777;
    font-size: 9px;
    margin-top: 4px;
}

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 8px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: bold;
    white-space: nowrap;
}

.status-completed {
    background: #eaf6e9;
    color: #338b35;
}

.status-cancelled {
    background: #fdeaea;
    color: #c84b4b;
}

.status-pending {
    background: #fff5d9;
    color: #b78300;
}

.status-confirmed {
    background: #e9f3ff;
    color: #3275b7;
}

.status-ongoing {
    background: #e9f8ef;
    color: #168b45;
}

.booked-on {
    white-space: nowrap;
    line-height: 16px;
}

.booked-time {
    color: #777;
    font-size: 9px;
}

.view-details {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    min-width: 82px;
    padding: 7px 9px;
    border: 1px solid #b9d4b9;
    border-radius: 6px;
    background: white;
    color: #438643;
    text-decoration: none;
    font-size: 10px;
    font-weight: bold;
    white-space: nowrap;
}

.view-details:hover {
    background: #eef8f1;
    border-color: #8dbb8d;
}

.empty {
    padding: 65px 20px;
    text-align: center;
}

.empty-icon {
    font-size: 45px;
    margin-bottom: 15px;
}

.empty h2 {
    font-size: 20px;
    margin-bottom: 8px;
}

.empty p {
    color: #777;
    font-size: 13px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 16px;
    border-top: 1px solid #edf0f2;
}

.page-button {
    width: 30px;
    height: 30px;
    border: 1px solid #dfe3e8;
    border-radius: 5px;
    background: white;
    color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 12px;
}

.page-button:hover {
    background: #eef8f1;
    color: #168b45;
}

.page-button.active {
    background: #46a447;
    border-color: #46a447;
    color: white;
}

.page-arrow {
    font-size: 16px;
}

@media (max-width: 1100px) {

    .sidebar {
        width: 230px;
    }

    .main {
        margin-left: 230px;
    }

    .content {
        padding: 25px;
    }

    .history-box {
        overflow-x: auto;
    }

    .history-table {
        min-width: 900px;
    }

}

@media (max-width: 750px) {

    .sidebar {
        display: none;
    }

    .main {
        margin-left: 0;
    }

    .topbar {
        padding: 0 15px;
        gap: 10px;
    }

    .location {
        display: none;
    }

    .content {
        padding: 20px 15px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .status-filter {
        width: 100%;
    }

    .status-filter select {
        width: 100%;
    }

}

</style>

</head>

<body>

<div class="sidebar">

    <div class="logo">

        <div class="logo-icon">
            🚜
        </div>

        <div class="logo-content">

            <div class="logo-text">
                AGRICULTURE
            </div>

            <div class="logo-sub">
                EQUIPMENT RENTAL SYSTEM
            </div>

        </div>

    </div>

    <div class="nav">

        <a href="renter_dashboard.php" class="nav-item">

            <span class="nav-icon">
                🏠
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('dashboard', 'Dashboard')) ?>
            </span>

        </a>

        <a href="categories.php" class="nav-item">

            <span class="nav-icon">
                ▦
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('categories', 'Categories')) ?>
            </span>

        </a>

        <a href="my_bookings.php" class="nav-item">

            <span class="nav-icon">
                ▣
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('my_bookings', 'My Bookings')) ?>
            </span>

        </a>

        <a href="notifications.php" class="nav-item">

            <span class="nav-icon">
                🔔
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('notifications', 'Notifications')) ?>
            </span>

            <span class="notification-count">
                2
            </span>

        </a>

        <a href="profile.php" class="nav-item">

            <span class="nav-icon">
                👤
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('my_profile', 'My Profile')) ?>
            </span>

        </a>

        <a href="rental_history.php" class="nav-item active">

            <span class="nav-icon">
                ↶
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('rental_history', 'Rental History')) ?>
            </span>

        </a>

        <a href="logout.php" class="nav-item">

            <span class="nav-icon">
                ⇥
            </span>

            <span class="nav-text">
                <?= htmlspecialchars(tr('logout', 'Logout')) ?>
            </span>

        </a>

    </div>

</div>

<div class="main">

    <div class="topbar">

        <div class="location">

            📍 <?= htmlspecialchars($user_location) ?>

        </div>

        <div class="language">

            <button class="language-button">

                🌐

                <?php

                if ($current_lang === 'kn') {
                    echo 'ಕನ್ನಡ';
                } elseif ($current_lang === 'hi') {
                    echo 'हिन्दी';
                } else {
                    echo 'English';
                }

                ?>

                ▾

            </button>

            <div class="language-menu">

                <a href="rental_history.php?lang=en">
                     English
                </a>

                <a href="rental_history.php?lang=kn">
                     ಕನ್ನಡ
                </a>

                <a href="rental_history.php?lang=hi">
                     हिन्दी
                </a>

            </div>

        </div>

        <a href="profile.php" class="profile-link">

            <div class="profile">

                <div class="profile-icon">
                    <?= htmlspecialchars($user_initial) ?>
                </div>

                <div>

                    <div class="profile-name">
                        <?= htmlspecialchars($user_name) ?>
                    </div>

                    <div class="profile-role">
                        <?= htmlspecialchars(tr('renter', 'Renter')) ?>
                    </div>

                </div>

            </div>

        </a>

    </div>

    <div class="content">

        <div class="breadcrumb">

            <a href="renter_dashboard.php">
                <?= htmlspecialchars(tr('home', 'Home')) ?>
            </a>

            <span class="breadcrumb-arrow">
                ›
            </span>

            <span class="breadcrumb-current">
                <?= htmlspecialchars(tr('rental_history', 'Rental History')) ?>
            </span>

        </div>

        <div class="page-header">

            <div class="page-title">

                <h1>
                    <?= htmlspecialchars(tr('rental_history', 'Rental History')) ?>
                </h1>

                <p>
                    <?= htmlspecialchars(
                        tr(
                            'rental_history_description',
                            'View your past bookings and rental activities.'
                        )
                    ) ?>
                </p>

            </div>

            <div class="status-filter">

                <select id="statusFilter">

                    <option value="all">
                        <?= htmlspecialchars(tr('all_status', 'All Status')) ?>
                    </option>

                    <option value="completed">
                        <?= htmlspecialchars(tr('completed', 'Completed')) ?>
                    </option>

                    <option value="confirmed">
                        <?= htmlspecialchars(tr('confirmed', 'Confirmed')) ?>
                    </option>

                    <option value="pending">
                        <?= htmlspecialchars(tr('pending', 'Pending')) ?>
                    </option>

                    <option value="cancelled">
                        <?= htmlspecialchars(tr('cancelled', 'Cancelled')) ?>
                    </option>

                    <option value="ongoing">
                        <?= htmlspecialchars(tr('ongoing', 'Ongoing')) ?>
                    </option>

                </select>

            </div>

        </div>

        <div class="history-box">

            <?php if (count($bookings) > 0): ?>

                <table class="history-table">

                    <thead>

                        <tr>

                            <th>
                                <?= htmlspecialchars(tr('equipment', 'Equipment')) ?>
                            </th>

                            <th>
                                <?= htmlspecialchars(tr('booking_id', 'Booking ID')) ?>
                            </th>

                            <th>
                                <?= htmlspecialchars(tr('rental_period', 'Rental Period')) ?>
                            </th>

                            <th>
                                <?= htmlspecialchars(tr('total_amount', 'Total Amount')) ?>
                            </th>

                            <th>
                                <?= htmlspecialchars(tr('status', 'Status')) ?>
                            </th>

                            <th>
                                <?= htmlspecialchars(tr('booked_on', 'Booked On')) ?>
                            </th>

                            <th>
                                <?= htmlspecialchars(tr('action', 'Action')) ?>
                            </th>

                        </tr>

                    </thead>

                    <tbody id="bookingTableBody">

                    <?php foreach ($bookings as $row): ?>

                        <?php

                        $booking_id = getBookingValue(
                            $row,
                            ['booking_id', 'id'],
                            0
                        );

                        $equipment_id = getBookingValue(
                            $row,
                            ['equipment_id'],
                            0
                        );

                        $equipment_name = getBookingValue(
                            $row,
                            [
                                'equipment_title',
                                'title',
                                'equipment_name'
                            ],
                            'Agricultural Equipment'
                        );

                        $equipment_category = getBookingValue(
                            $row,
                            [
                                'equipment_category',
                                'category'
                            ],
                            'Agricultural Equipment'
                        );

                        $equipment_image = getEquipmentImage(
                            getBookingValue(
                                $row,
                                ['equipment_image', 'image'],
                                ''
                            )
                        );

                        $start_date = getBookingValue(
                            $row,
                            [
                                'start_date',
                                'rental_start',
                                'from_date',
                                'booking_start',
                                'rent_from'
                            ],
                            ''
                        );

                        $end_date = getBookingValue(
                            $row,
                            [
                                'end_date',
                                'rental_end',
                                'to_date',
                                'booking_end',
                                'rent_to'
                            ],
                            ''
                        );

                        $total_amount = getBookingValue(
                            $row,
                            [
                                'total_amount',
                                'total_price',
                                'amount',
                                'total'
                            ],
                            0
                        );

                        $advance = getBookingValue(
                            $row,
                            [
                                'advance_amount',
                                'advance',
                                'deposit'
                            ],
                            0
                        );

                        $status = getBookingValue(
                            $row,
                            ['status', 'booking_status'],
                            'Pending'
                        );

                        $booked_on = getBookingValue(
                            $row,
                            [
                                'created_at',
                                'booked_on',
                                'booking_date',
                                'created_date'
                            ],
                            ''
                        );

                        $days = calculateDays(
                            $start_date,
                            $end_date
                        );

                        $status_class = getStatusClass($status);

                        ?>

                        <tr
                            class="booking-row"
                            data-status="<?= htmlspecialchars(strtolower($status)) ?>"
                        >

                            <td>

                                <div class="equipment-cell">

                                    <?php if (!empty($equipment_image)): ?>

                                        <img
                                            src="<?= htmlspecialchars($equipment_image) ?>"
                                            class="equipment-image"
                                            alt="<?= htmlspecialchars($equipment_name) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="equipment-placeholder">
                                            🚜
                                        </div>

                                    <?php endif; ?>

                                    <div class="equipment-info">

                                        <div class="equipment-name">
                                            <?= htmlspecialchars($equipment_name) ?>
                                        </div>

                                        <div class="equipment-category">

                                            <?= htmlspecialchars(
                                                tr('category', 'Category')
                                            ) ?>:

                                            <?= htmlspecialchars($equipment_category) ?>

                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="booking-id">

                                    <?php
                                    echo 'REQ-' . strtoupper(
                                        substr(
                                            md5((string)$booking_id),
                                            0,
                                            6
                                        )
                                    );
                                    ?>

                                </div>

                            </td>

                            <td>

                                <div class="rental-period">

                                    <?php if (!empty($start_date)): ?>

                                        <strong>
                                            <?= htmlspecialchars(
                                                formatBookingDate($start_date)
                                            ) ?>
                                        </strong>

                                    <?php endif; ?>

                                    <?php if (!empty($end_date)): ?>

                                        <br>

                                        <strong>
                                            <?= htmlspecialchars(
                                                formatBookingDate($end_date)
                                            ) ?>
                                        </strong>

                                    <?php endif; ?>

                                    <?php if ($days > 0): ?>

                                        <div class="rental-days">

                                            (
                                            <?= htmlspecialchars($days) ?>

                                            <?= htmlspecialchars(
                                                tr('days', 'Days')
                                            ) ?>

                                            )

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </td>

                            <td>

                                <div class="total-amount">

                                    ₹<?= number_format(
                                        (float)$total_amount,
                                        0
                                    ) ?>

                                </div>

                                <?php if ((float)$advance > 0): ?>

                                    <div class="advance">

                                        <?= htmlspecialchars(
                                            tr('advance', 'Advance')
                                        ) ?>:

                                        ₹<?= number_format(
                                            (float)$advance,
                                            0
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>

                                <span class="status <?= htmlspecialchars($status_class) ?>">

                                    <?= htmlspecialchars(
                                        ucfirst($status)
                                    ) ?>

                                </span>

                            </td>

                            <td>

                                <div class="booked-on">

                                    <?php if (!empty($booked_on)): ?>

                                        <?= htmlspecialchars(
                                            formatBookingDate($booked_on)
                                        ) ?>

                                        <br>

                                        <span class="booked-time">

                                            <?= htmlspecialchars(
                                                formatBookingTime($booked_on)
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </div>

                            </td>

                            <td>

                                <a
                                    href="booking_details.php?booking_id=<?= urlencode($booking_id) ?><?= !empty($current_lang) ? '&lang=' . urlencode($current_lang) : '' ?>"
                                    class="view-details"
                                >

                                    <span>
                                        👁
                                    </span>

                                    <?= htmlspecialchars(
                                        tr('view_details', 'View Details')
                                    ) ?>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

                <div class="pagination">

                    <a href="#" class="page-button page-arrow">
                        «
                    </a>

                    <a href="#" class="page-button page-arrow">
                        ‹
                    </a>

                    <a href="#" class="page-button active">
                        1
                    </a>

                    <a href="#" class="page-button">
                        2
                    </a>

                    <a href="#" class="page-button">
                        3
                    </a>

                    <a href="#" class="page-button page-arrow">
                        ›
                    </a>

                    <a href="#" class="page-button page-arrow">
                        »
                    </a>

                </div>

            <?php else: ?>

                <div class="empty">

                    <div class="empty-icon">
                        📋
                    </div>

                    <h2>
                        <?= htmlspecialchars(
                            tr(
                                'no_rental_history',
                                'No Rental History'
                            )
                        ) ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars(
                            tr(
                                'no_rental_history_description',
                                'You have not made any equipment bookings yet.'
                            )
                        ) ?>
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>

const statusFilter = document.getElementById('statusFilter');

if (statusFilter) {

    statusFilter.addEventListener('change', function () {

        const selectedStatus = this.value;

        const rows = document.querySelectorAll('.booking-row');

        rows.forEach(function (row) {

            const rowStatus = row.getAttribute('data-status');

            if (
                selectedStatus === 'all' ||
                rowStatus === selectedStatus
            ) {

                row.style.display = '';

            } else {

                row.style.display = 'none';

            }

        });

    });

}

</script>

</body>

</html>
