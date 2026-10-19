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

    mysqli_stmt_store_result($user_stmt);

    $user_metadata = mysqli_stmt_result_metadata($user_stmt);

    if ($user_metadata) {

        $user_fields = mysqli_fetch_fields($user_metadata);
        $user_row = [];
        $user_bind = [];

        foreach ($user_fields as $field) {
            $user_row[$field->name] = null;
            $user_bind[] = &$user_row[$field->name];
        }

        mysqli_stmt_bind_result($user_stmt, ...$user_bind);

        if (mysqli_stmt_fetch($user_stmt)) {

            if (!empty($user_row['full_name'])) {
                $user_name = $user_row['full_name'];
            }

            if (isset($user_row['location']) && !empty($user_row['location'])) {
                $user_location = $user_row['location'];
            } elseif (isset($user_row['address']) && !empty($user_row['address'])) {
                $user_location = $user_row['address'];
            } elseif (isset($user_row['city']) && !empty($user_row['city'])) {
                $user_location = $user_row['city'];
            }
        }

        mysqli_free_result($user_metadata);
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

    mysqli_stmt_store_result($booking_stmt);

    $booking_metadata = mysqli_stmt_result_metadata($booking_stmt);

    if ($booking_metadata) {

        $booking_fields = mysqli_fetch_fields($booking_metadata);
        $booking_row = [];
        $booking_bind = [];

        foreach ($booking_fields as $field) {
            $booking_row[$field->name] = null;
            $booking_bind[] = &$booking_row[$field->name];
        }

        mysqli_stmt_bind_result($booking_stmt, ...$booking_bind);

        while (mysqli_stmt_fetch($booking_stmt)) {
            $bookings[] = $booking_row;
            $booking_row = [];

            foreach ($booking_fields as $field) {
                $booking_row[$field->name] = null;
            }

            $booking_bind = [];

            foreach ($booking_fields as $field) {
                $booking_bind[] = &$booking_row[$field->name];
            }

            mysqli_stmt_bind_result($booking_stmt, ...$booking_bind);
        }

        mysqli_free_result($booking_metadata);
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
        $status === 'canceled' ||
        $status === 'rejected'
    ) {
        return 'status-cancelled';
    }

    if ($status === 'pending') {
        return 'status-pending';
    }

    if (
        $status === 'confirmed' ||
        $status === 'approved' ||
        $status === 'accepted'
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

<!-- FontAwesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
    background-color: var(--page-bg-1);
    color: var(--text-dark);
    -webkit-font-smoothing: antialiased;
}

/* ---------- Main area background ---------- */

.main {
    margin-left: 250px;
    min-height: 100vh;
    background:
        radial-gradient(circle at 12% 8%, rgba(255, 255, 255, .70), transparent 32%),
        radial-gradient(circle at 88% 20%, rgba(220, 232, 205, .55), transparent 34%),
        radial-gradient(circle at 70% 92%, rgba(255, 255, 255, .50), transparent 32%),
        linear-gradient(135deg, var(--page-bg-1) 0%, var(--page-bg-2) 50%, var(--page-bg-3) 100%);
    background-attachment: fixed;
}

/* ---------- Top bar ---------- */

.topbar {
    height: 70px;
    margin: 18px 24px 0;
    padding: 0 24px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 25px;
    box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.location {
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 600;
}

.language {
    position: relative;
}

.language-button {
    border: 1px solid var(--line);
    background: rgba(255, 255, 255, .78);
    border-radius: 12px;
    padding: 8px 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: var(--brand-green);
}

.language-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 42px;
    width: 150px;
    background: rgba(255, 255, 255, .96);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    box-shadow: 0 16px 35px rgba(50, 70, 45, .14);
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
    color: var(--text-dark);
    font-size: 13px;
    font-weight: 600;
}

.language-menu a:hover {
    background: #e9f3e6;
    color: var(--brand-green);
}

.profile-link {
    text-decoration: none;
    color: var(--text-dark);
}

.profile {
    display: flex;
    align-items: center;
    gap: 10px;
}

.profile-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: var(--brand-green-pill);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    border: 2px solid rgba(255, 255, 255, .85);
    box-shadow: 0 4px 12px rgba(31, 92, 67, .18);
}

.profile-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-dark);
}

.profile-role {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    margin-top: 2px;
}

/* ---------- Content / headings ---------- */

.content {
    padding: 30px 35px 40px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 600;
}

.breadcrumb a {
    color: var(--brand-green);
    text-decoration: none;
    font-weight: 700;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb-arrow {
    color: var(--text-muted);
}

.breadcrumb-current {
    color: var(--text-muted);
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.page-title h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 6px;
    letter-spacing: -0.5px;
}

.page-title p {
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 500;
}

.status-filter select {
    min-width: 140px;
    padding: 10px 14px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: rgba(255, 255, 255, .78);
    color: var(--brand-green);
    font-size: 13px;
    font-weight: 700;
    outline: none;
    cursor: pointer;
}

.status-filter select:focus {
    border-color: #6bbd91;
    box-shadow: 0 0 0 3px rgba(45, 106, 79, .12);
}

/* ---------- History table card ---------- */

.history-box {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.history-table {
    width: 100%;
    border-collapse: collapse;
}

.history-table th {
    text-align: left;
    padding: 14px 14px;
    background: rgba(226, 240, 220, .75);
    color: var(--brand-green);
    font-size: 13px;
    font-weight: 800;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
}

.history-table td {
    padding: 14px 14px;
    border-bottom: 1px solid rgba(45, 106, 79, .08);
    vertical-align: middle;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
}

.history-table tr:last-child td {
    border-bottom: none;
}

.equipment-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 200px;
}

.equipment-image {
    width: 52px;
    height: 46px;
    border-radius: 12px;
    object-fit: cover;
    background: #e3efdc;
    border: 1px solid rgba(255, 255, 255, .95);
}

.equipment-placeholder {
    width: 52px;
    height: 46px;
    border-radius: 12px;
    background: linear-gradient(145deg, #e3efdc, #f7faf2);
    border: 1px solid rgba(255, 255, 255, .95);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.equipment-info {
    min-width: 0;
}

.equipment-name {
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 4px;
    font-size: 14px;
}

.equipment-category {
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
}

.booking-id {
    color: var(--text-dark);
    font-weight: 700;
    font-size: 13px;
    white-space: nowrap;
}

.rental-period {
    line-height: 18px;
    white-space: nowrap;
}

.rental-period strong {
    font-weight: 800;
    color: var(--text-dark);
}

.rental-days {
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
    margin-top: 2px;
}

.total-amount {
    font-weight: 800;
    color: var(--brand-green);
    font-size: 14px;
    white-space: nowrap;
}

.advance {
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
    margin-top: 3px;
}

/* ---------- Status badges ---------- */

.status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
}

.status-completed,
.status-ongoing {
    background: #dcefd6;
    color: #1f5c43;
}

.status-cancelled {
    background: #fee2e2;
    color: #b91c1c;
}

.status-pending {
    background: #fef3c7;
    color: #b45309;
}

.status-confirmed {
    background: #e6f0e1;
    color: #2d6a4f;
}

.booked-on {
    white-space: nowrap;
    line-height: 18px;
    font-weight: 700;
    color: var(--text-dark);
}

.booked-time {
    color: var(--text-muted);
    font-size: 11px;
    font-weight: 600;
}

.view-details {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 90px;
    padding: 8px 12px;
    border: 1px solid rgba(45, 106, 79, .40);
    border-radius: 12px;
    background: rgba(255, 255, 255, .80);
    color: var(--brand-green);
    text-decoration: none;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.view-details:hover {
    background: var(--brand-green-pill);
    border-color: var(--brand-green-pill);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 7px 16px rgba(31, 92, 67, .18);
}

/* ---------- Empty state ---------- */

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
    font-weight: 800;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.empty p {
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 500;
}

/* ---------- Pagination ---------- */

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 16px;
    border-top: 1px solid rgba(45, 106, 79, .10);
}

.page-button {
    width: 32px;
    height: 32px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: rgba(255, 255, 255, .78);
    color: var(--brand-green);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.page-button:hover {
    background: #e9f3e6;
    color: var(--brand-green);
    border-color: rgba(45, 106, 79, .40);
}

.page-button.active {
    background: var(--brand-green-pill);
    border-color: var(--brand-green-pill);
    color: #fff;
}

.page-arrow {
    font-size: 16px;
}

/* ---------- Responsive ---------- */

@media (max-width: 1100px) {

    .main {
        margin-left: 250px;
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

    .main {
        margin-left: 0;
    }

    .topbar {
        margin: 10px 10px 0;
        height: auto;
        min-height: 64px;
        padding: 12px;
        gap: 10px;
        border-radius: 16px;
    }

    .location {
        display: none;
    }

    .content {
        padding: 20px 15px;
    }

    .history-box {
        border-radius: 17px;
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

@media (prefers-reduced-motion: reduce) {
    .view-details {
        transition: none !important;
    }
}

</style>

</head>

<body>

<?php include __DIR__ . '/renter_sidebar.php'; ?>

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

                                    <i class="fa-regular fa-eye"></i>

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