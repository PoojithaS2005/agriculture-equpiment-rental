
<?php
session_start();

require_once 'includes/config.php';
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'lender') {
    header('Location: login.php');
    exit();
}

$lender_id = (int)$_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_param = '?lang=' . urlencode($current_lang);

// Lender name + profile picture
$lender_name = $_SESSION['full_name'] ?? 'Lender';
$profile_pic = '';
$has_profile_pic = false;
$profile_initials = strtoupper(mb_substr(trim($lender_name), 0, 2, 'UTF-8'));

if ($profile_initials === '') {
    $profile_initials = 'LE';
}

$user_stmt = $conn->prepare("SELECT full_name, profile_pic FROM users WHERE user_id = ? LIMIT 1");

if ($user_stmt) {
    $user_stmt->bind_param('i', $lender_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();

    if ($user_data) {
        $lender_name = $user_data['full_name'] ?: $lender_name;

        if (!empty($user_data['profile_pic']) && $user_data['profile_pic'] !== 'default_avatar.png') {
            $profile_pic = $user_data['profile_pic'];
            $has_profile_pic = true;
        }

        $profile_initials = strtoupper(mb_substr(trim($lender_name), 0, 2, 'UTF-8'));

        if ($profile_initials === '') {
            $profile_initials = 'LE';
        }
    }

    $user_stmt->close();
}

// Page-specific translations
$lender_bookings_text = [
    'en' => [
        'page_title' => 'My Bookings',
        'page_subtitle' => 'Manage your accepted and ongoing rental bookings.',
        'notifications' => 'Notifications',
        'current_bookings' => 'Current Bookings',
        'accepted_awaiting_delivery' => 'Accepted / Awaiting Delivery',
        'delivered_active' => 'Delivered / Active',
        'returned_awaiting_confirmation' => 'Returned / Awaiting Confirmation',
        'search_placeholder' => 'Search request, equipment or renter...',
        'all_current_bookings' => 'All Current Bookings',
        'current_booking_management' => 'Current Booking Management',
        'pending_requests_note' => 'Pending requests are handled under Rental Requests.',
        'no_current_bookings' => 'No current bookings',
        'no_current_bookings_desc' => 'Accepted, delivered, or returned bookings will appear here.',
        'search' => 'Search',
        'reset' => 'Reset',
    ],

    'kn' => [
        'page_title' => 'ನನ್ನ ಬುಕ್ಕಿಂಗ್‌ಗಳು',
        'page_subtitle' => 'ನಿಮ್ಮ ಸ್ವೀಕರಿಸಿದ ಮತ್ತು ಪ್ರಸ್ತುತ ಬಾಡಿಗೆ ಬುಕ್ಕಿಂಗ್‌ಗಳನ್ನು ನಿರ್ವಹಿಸಿ.',
        'notifications' => 'ಅಧಿಸೂಚನೆಗಳು',
        'current_bookings' => 'ಪ್ರಸ್ತುತ ಬುಕ್ಕಿಂಗ್‌ಗಳು',
        'accepted_awaiting_delivery' => 'ಸ್ವೀಕರಿಸಲಾಗಿದೆ / ವಿತರಣೆಗೆ ಕಾಯುತ್ತಿದೆ',
        'delivered_active' => 'ತಲುಪಿಸಲಾಗಿದೆ / ಸಕ್ರಿಯ',
        'returned_awaiting_confirmation' => 'ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ / ದೃಢೀಕರಣಕ್ಕೆ ಕಾಯುತ್ತಿದೆ',
        'search_placeholder' => 'ವಿನಂತಿ, ಉಪಕರಣ ಅಥವಾ ಬಾಡಿಗೆದಾರರನ್ನು ಹುಡುಕಿ...',
        'all_current_bookings' => 'ಎಲ್ಲಾ ಪ್ರಸ್ತುತ ಬುಕ್ಕಿಂಗ್‌ಗಳು',
        'current_booking_management' => 'ಪ್ರಸ್ತುತ ಬುಕ್ಕಿಂಗ್ ನಿರ್ವಹಣೆ',
        'pending_requests_note' => 'ಬಾಕಿ ಇರುವ ವಿನಂತಿಗಳನ್ನು ಬಾಡಿಗೆ ವಿನಂತಿಗಳ ಅಡಿಯಲ್ಲಿ ನಿರ್ವಹಿಸಲಾಗುತ್ತದೆ.',
        'no_current_bookings' => 'ಯಾವುದೇ ಪ್ರಸ್ತುತ ಬುಕ್ಕಿಂಗ್‌ಗಳಿಲ್ಲ',
        'no_current_bookings_desc' => 'ಸ್ವೀಕರಿಸಿದ, ತಲುಪಿಸಿದ ಅಥವಾ ಹಿಂತಿರುಗಿಸಿದ ಬುಕ್ಕಿಂಗ್‌ಗಳು ಇಲ್ಲಿ ಕಾಣಿಸುತ್ತವೆ.',
        'search' => 'ಹುಡುಕಿ',
        'reset' => 'ಮರುಹೊಂದಿಸಿ',
    ],

    'hi' => [
        'page_title' => 'मेरी बुकिंग',
        'page_subtitle' => 'अपनी स्वीकृत और चल रही किराये की बुकिंग प्रबंधित करें।',
        'notifications' => 'सूचनाएँ',
        'current_bookings' => 'वर्तमान बुकिंग',
        'accepted_awaiting_delivery' => 'स्वीकृत / डिलीवरी की प्रतीक्षा',
        'delivered_active' => 'डिलीवर / सक्रिय',
        'returned_awaiting_confirmation' => 'वापस किया गया / पुष्टि की प्रतीक्षा',
        'search_placeholder' => 'अनुरोध, उपकरण या किराएदार खोजें...',
        'all_current_bookings' => 'सभी वर्तमान बुकिंग',
        'current_booking_management' => 'वर्तमान बुकिंग प्रबंधन',
        'pending_requests_note' => 'लंबित अनुरोधों को किराया अनुरोध के अंतर्गत संभाला जाता है।',
        'no_current_bookings' => 'कोई वर्तमान बुकिंग नहीं है',
        'no_current_bookings_desc' => 'स्वीकृत, डिलीवर या वापस की गई बुकिंग यहाँ दिखाई देंगी।',
        'search' => 'खोजें',
        'reset' => 'रीसेट',
    ],
];

function lender_bookings_text(string $key): string {
    global $lender_bookings_text, $current_lang;

    return $lender_bookings_text[$current_lang][$key]
        ?? $lender_bookings_text['en'][$key]
        ?? $key;
}

// Notification count
$unread_notifications = 0;

$notif_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0"
);

if ($notif_stmt) {
    $notif_stmt->bind_param('i', $lender_id);
    $notif_stmt->execute();

    $notif_data = $notif_stmt->get_result()->fetch_assoc();

    $unread_notifications = (int)($notif_data['total'] ?? 0);

    $notif_stmt->close();
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$allowed_statuses = ['Accepted', 'Delivered', 'Returned'];

if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = '';
}

// Current lender bookings
$sql = "SELECT
            b.booking_id,
            b.request_code,
            b.start_date,
            b.end_date,
            b.total_days,
            b.quantity,
            b.total_amount,
            b.advance_amount,
            b.remaining_cod,
            b.status,
            b.created_at,
            e.equipment_id,
            e.title AS equipment_title,
            e.category AS equipment_category,
            e.image AS equipment_image,
            u.full_name AS renter_name,
            u.phone AS renter_phone
        FROM bookings b
        INNER JOIN equipment e ON b.equipment_id = e.equipment_id
        INNER JOIN users u ON b.renter_id = u.user_id
        WHERE e.lender_id = ?
          AND b.status IN ('Accepted', 'Delivered', 'Returned')";

$params = [$lender_id];
$types = 'i';

if ($status_filter !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($search !== '') {
    $sql .= " AND (
        b.request_code LIKE ?
        OR e.title LIKE ?
        OR u.full_name LIKE ?
        OR u.phone LIKE ?
    )";

    $term = '%' . $search . '%';

    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;

    $types .= 'ssss';
}

$sql .= " ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Unable to load bookings.');
}

$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();

// Summary counts
$count_sql = "SELECT
    SUM(b.status = 'Accepted') AS accepted_count,
    SUM(b.status = 'Delivered') AS delivered_count,
    SUM(b.status = 'Returned') AS returned_count
    FROM bookings b
    INNER JOIN equipment e ON b.equipment_id = e.equipment_id
    WHERE e.lender_id = ?
      AND b.status IN ('Accepted', 'Delivered', 'Returned')";

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $lender_id);
$count_stmt->execute();

$counts = $count_stmt->get_result()->fetch_assoc() ?: [];

$accepted_count = (int)($counts['accepted_count'] ?? 0);
$delivered_count = (int)($counts['delivered_count'] ?? 0);
$returned_count = (int)($counts['returned_count'] ?? 0);

$total_current = $accepted_count + $delivered_count + $returned_count;

$count_stmt->close();

function statusClass(string $status): string {
    return match ($status) {
        'Accepted' => 'status-accepted',
        'Delivered' => 'status-delivered',
        'Returned' => 'status-returned',
        default => 'status-other'
    };
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
<?= htmlspecialchars(lender_bookings_text('page_title')); ?>
- <?= htmlspecialchars(__('title')); ?>
</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
>

<style>

/* =========================================
   BLUEFIELD LENDING - BLUE GLASS THEME
========================================= */

:root {
    --navy-primary: #0B3478;
    --navy-dark: #082B65;
    --navy-light: #164C9A;

    --blue-accent: #3979D8;
    --blue-soft: #A6C8F5;

    --bg-body: #DCEBFC;
    --bg-light: #EAF3FF;

    --glass-bg: rgba(255, 255, 255, 0.48);
    --glass-bg-strong: rgba(255, 255, 255, 0.65);

    --border-color: rgba(255, 255, 255, 0.65);

    --text-dark: #10194A;
    --text-muted: #425D80;

    --shadow: 0 8px 25px rgba(11, 52, 120, 0.10);
    --glass-blur: blur(14px);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(
        135deg,
        #DCEBFC 0%,
        #EAF3FF 45%,
        #C9DFFA 100%
    );

    color: var(--text-dark);
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR - EXISTING STYLING */

.sidebar {
    width: 250px;
    background: #F8FBFF;
    min-height: 100vh;
    padding: 20px;
    border-right: 1px solid #C8DDF5;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    overflow-y: auto;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
    color: var(--navy-dark);
    font-size: 15px;
    margin-bottom: 30px;
}

.logo i {
    font-size: 24px;
    color: var(--navy-primary);
}

.nav-list {
    list-style: none;
}

.nav-item {
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    transition: .2s;
}

.nav-link:hover,
.nav-link.active {
    background: #A6C8F5;
    color: var(--text-dark);
}

.nav-link .badge {
    margin-left: auto;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 12px;
}

/* MAIN CONTENT */

.main-content {
    flex: 1;
    margin-left: 250px;
    padding: 25px 30px;
    min-width: 0;
}

/* TOP BAR */

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    gap: 20px;
    padding: 18px 22px;

    background: linear-gradient(
        135deg,
        #0B3478,
        #164C9A
    );

    border: 1px solid rgba(255,255,255,.25);
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(8,43,101,.20);
}

.page-title h1 {
    color: #FFFFFF;
    font-size: 25px;
    margin-bottom: 5px;
}

.page-title p {
    color: #D5E7FF;
    font-size: 14px;
}

.top-actions {
    display: flex;
    align-items: center;
    gap: 14px;
}

.notification-link {
    position: relative;
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    text-decoration: none;
    border-radius: 50%;
    transition: .2s;
}

.notification-link:hover {
    background: rgba(255,255,255,.18);
}

.notification-badge {
    position: absolute;
    top: -2px;
    right: -1px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #ef4444;
    color: #FFFFFF;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #0B3478;
}

.lang-select {
    padding: 8px 12px;
    border: 1px solid rgba(255,255,255,.35);
    border-radius: 8px;
    background: rgba(255,255,255,.15);
    color: #FFFFFF;
    outline: none;
}

.lang-select option {
    background: #FFFFFF;
    color: #10194A;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 9px;
}

.user-profile-img,
.user-profile-initials {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,.8);
}

.user-profile-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #A6C8F5;
    color: #082B65;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    flex-shrink: 0;
}

.user-text strong {
    display: block;
    font-size: 13px;
    color: #FFFFFF;
}

.user-text span {
    display: block;
    font-size: 11px;
    color: #D5E7FF;
}

/* SUMMARY CARDS */

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.summary-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);

    border: 1px solid var(--border-color);
    border-radius: 16px;

    padding: 20px;

    display: flex;
    align-items: center;
    gap: 14px;

    box-shadow: var(--shadow);

    transition: transform .25s, box-shadow .25s;
}

.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(11,52,120,.16);
}

.summary-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: var(--navy-primary);
    background: rgba(166,200,245,.55);

    border: 1px solid rgba(255,255,255,.8);

    font-size: 19px;
    flex-shrink: 0;
}

.summary-card h3 {
    font-size: 25px;
    color: var(--navy-dark);
    font-weight: 700;
}

.summary-card p {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 4px;
    line-height: 1.4;
}

/* SEARCH TOOLBAR */

.toolbar {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);

    border: 1px solid var(--border-color);
    border-radius: 15px;

    padding: 16px;

    display: flex;
    justify-content: space-between;
    gap: 12px;

    margin-bottom: 20px;

    box-shadow: var(--shadow);
}

.search-form {
    display: flex;
    gap: 9px;
    flex: 1;
    max-width: 650px;
}

.search-input,
.status-select {
    border: 1px solid rgba(75,130,200,.25);
    border-radius: 9px;
    padding: 11px 13px;
    outline: none;

    background: rgba(255,255,255,.75);
    color: var(--text-dark);

    transition: .2s;
}

.search-input {
    flex: 1;
}

.search-input:focus,
.status-select:focus {
    border-color: var(--blue-accent);
    box-shadow: 0 0 0 3px rgba(57,121,216,.13);
}

.search-input::placeholder {
    color: #7188A7;
}

.btn-search {
    border: none;
    border-radius: 9px;

    background: linear-gradient(
        135deg,
        var(--navy-primary),
        var(--navy-light)
    );

    color: #FFFFFF;
    padding: 0 20px;
    cursor: pointer;
    font-weight: 600;
    transition: .2s;
}

.btn-search:hover {
    background: #164C9A;
    transform: translateY(-1px);
}

.btn-reset {
    display: inline-flex;
    align-items: center;
    padding: 0 16px;

    border-radius: 9px;
    text-decoration: none;

    border: 1px solid #B7D0F1;
    color: var(--navy-primary);
    background: rgba(255,255,255,.65);

    font-size: 13px;
    transition: .2s;
}

.btn-reset:hover {
    background: #D5E7FF;
}

/* BOOKING TABLE CARD */

.table-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    -webkit-backdrop-filter: var(--glass-blur);

    border: 1px solid var(--border-color);
    border-radius: 16px;

    overflow: hidden;

    box-shadow: var(--shadow);
}

.table-header {
    padding: 20px 22px;

    border-bottom: 1px solid rgba(75,130,200,.18);

    display: flex;
    justify-content: space-between;
    align-items: center;

    background: rgba(255,255,255,.25);
}

.table-header h2 {
    font-size: 18px;
    color: var(--navy-dark);
}

.table-header span {
    font-size: 12px;
    color: var(--text-muted);
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 980px;
}

th {
    text-align: left;
    padding: 15px 14px;

    color: var(--navy-primary);
    background: rgba(166,200,245,.28);

    border-bottom: 1px solid rgba(75,130,200,.20);

    font-weight: 700;
    white-space: nowrap;
}

td {
    padding: 15px 14px;

    border-bottom: 1px solid rgba(75,130,200,.12);

    vertical-align: middle;
    color: var(--text-dark);
}

tbody tr {
    transition: background .2s;
}

tbody tr:hover {
    background: rgba(166,200,245,.20);
}

tr:last-child td {
    border-bottom: 0;
}

/* EQUIPMENT INFORMATION */

.equipment-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 190px;
}

.equipment-img {
    width: 45px;
    height: 45px;
    border-radius: 9px;
    object-fit: cover;

    background: #EAF3FF;
    border: 1px solid #B7D0F1;
}

.equipment-info strong {
    display: block;
    color: var(--navy-dark);
    font-size: 13px;
}

.equipment-info small {
    color: var(--text-muted);
}

.renter strong {
    display: block;
    color: var(--navy-dark);
}

.renter small {
    color: var(--text-muted);
}

/* STATUS LABELS */

.status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
}

.status-accepted {
    background: #FEF0C7;
    color: #946200;
}

.status-delivered {
    background: #D7E8FF;
    color: #1453A1;
}

.status-returned {
    background: #D5F5E2;
    color: #167044;
}

.status-other {
    background: #DCEBFC;
    color: #425D80;
}

/* VIEW BUTTON */

.btn-view {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 8px 12px;
    border-radius: 8px;

    background: linear-gradient(
        135deg,
        #0B3478,
        #164C9A
    );

    color: #FFFFFF;
    text-decoration: none;

    font-size: 12px;
    font-weight: 600;

    transition: .2s;
}

.btn-view:hover {
    background: #082B65;
    transform: translateY(-1px);
}

/* EMPTY STATE */

.empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty i {
    font-size: 42px;
    color: #719BD1;
    margin-bottom: 14px;
}

.empty h3 {
    color: var(--navy-dark);
    margin-bottom: 8px;
    font-size: 18px;
}

.empty p {
    color: var(--text-muted);
    font-size: 13px;
}

/* RESPONSIVE DESIGN */

@media(max-width:1100px) {
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width:800px) {

    .sidebar {
        position: relative;
        width: 100%;
        min-height: auto;
    }

    body {
        display: block;
    }

    .main-content {
        margin-left: 0;
        padding: 18px;
    }

    .top-bar {
        align-items: flex-start;
        flex-direction: column;
    }

    .top-actions {
        width: 100%;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .toolbar {
        flex-direction: column;
    }

    .search-form {
        max-width: none;
    }

    .table-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 8px;
    }
}

@media(max-width:550px) {

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .search-form {
        flex-wrap: wrap;
    }

    .search-input {
        min-width: 100%;
    }

    .status-select {
        flex: 1;
    }

    .btn-search,
    .btn-reset {
        height: 42px;
        padding: 0 15px;
    }

    .main-content {
        padding: 12px;
    }

    .top-bar {
        padding: 16px;
    }

    .page-title h1 {
        font-size: 21px;
    }
}

</style>

</head>

<body>

<?php include __DIR__ . '/lender_sidebar.php'; ?>

<main class="main-content">

    <div class="top-bar">

        <div class="page-title">

            <h1>
                <?= htmlspecialchars(lender_bookings_text('page_title')); ?>
            </h1>

            <p>
                <?= htmlspecialchars(lender_bookings_text('page_subtitle')); ?>
            </p>

        </div>

        <div class="top-actions">

            <a
                href="lender_notifications.php<?php echo $lang_param; ?>"
                class="notification-link"
                title="<?= htmlspecialchars(lender_bookings_text('notifications')); ?>"
                aria-label="<?= htmlspecialchars(lender_bookings_text('notifications')); ?>"
            >

                <i class="fa-regular fa-bell"></i>

                <?php if ($unread_notifications > 0): ?>

                    <span class="notification-badge">
                        <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
                    </span>

                <?php endif; ?>

            </a>

            <select
                class="lang-select"
                onchange="changeLanguage(this.value)"
            >

                <option value="en" <?php echo $current_lang === 'en' ? 'selected' : ''; ?>>
                    English
                </option>

                <option value="kn" <?php echo $current_lang === 'kn' ? 'selected' : ''; ?>>
                    ಕನ್ನಡ
                </option>

                <option value="hi" <?php echo $current_lang === 'hi' ? 'selected' : ''; ?>>
                    हिन्दी
                </option>

            </select>

            <div class="user-menu">

                <?php if ($has_profile_pic): ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($profile_pic); ?>"
                        class="user-profile-img"
                        alt="Profile"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >

                    <div class="user-profile-initials" style="display:none;">
                        <?php echo htmlspecialchars($profile_initials); ?>
                    </div>

                <?php else: ?>

                    <div class="user-profile-initials">
                        <?php echo htmlspecialchars($profile_initials); ?>
                    </div>

                <?php endif; ?>

                <div class="user-text">

                    <strong>
                        <?php echo htmlspecialchars($lender_name); ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(__('lender')); ?>
                    </span>

                </div>

            </div>

        </div>

    </div>

    <!-- SUMMARY CARDS -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <div>
                <h3><?php echo $total_current; ?></h3>

                <p>
                    <?= htmlspecialchars(lender_bookings_text('current_bookings')); ?>
                </p>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>

            <div>
                <h3><?php echo $accepted_count; ?></h3>

                <p>
                    <?= htmlspecialchars(lender_bookings_text('accepted_awaiting_delivery')); ?>
                </p>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-truck"></i>
            </div>

            <div>
                <h3><?php echo $delivered_count; ?></h3>

                <p>
                    <?= htmlspecialchars(lender_bookings_text('delivered_active')); ?>
                </p>
            </div>

        </div>

        <div class="summary-card">

            <div class="summary-icon">
                <i class="fa-solid fa-rotate-left"></i>
            </div>

            <div>
                <h3><?php echo $returned_count; ?></h3>

                <p>
                    <?= htmlspecialchars(lender_bookings_text('returned_awaiting_confirmation')); ?>
                </p>
            </div>

        </div>

    </div>

    <!-- SEARCH AND FILTER -->

    <div class="toolbar">

        <form method="get" class="search-form">

            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="<?= htmlspecialchars(lender_bookings_text('search_placeholder')); ?>"
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <select name="status" class="status-select">

                <option value="">
                    <?= htmlspecialchars(lender_bookings_text('all_current_bookings')); ?>
                </option>

                <option
                    value="Accepted"
                    <?php echo $status_filter === 'Accepted' ? 'selected' : ''; ?>
                >
                    <?= htmlspecialchars(__('accepted')); ?>
                </option>

                <option
                    value="Delivered"
                    <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>
                >
                    <?= htmlspecialchars(__('delivered')); ?>
                </option>

                <option
                    value="Returned"
                    <?php echo $status_filter === 'Returned' ? 'selected' : ''; ?>
                >
                    <?= htmlspecialchars(__('returned')); ?>
                </option>

            </select>

            <button type="submit" class="btn-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <?= htmlspecialchars(lender_bookings_text('search')); ?>

            </button>

            <?php if ($search !== '' || $status_filter !== ''): ?>

                <a
                    href="lender_my_bookings.php<?php echo $lang_param; ?>"
                    class="btn-reset"
                >
                    <?= htmlspecialchars(lender_bookings_text('reset')); ?>
                </a>

            <?php endif; ?>

        </form>

    </div>

    <!-- BOOKINGS TABLE -->

    <section class="table-card">

        <div class="table-header">

            <h2>
                <?= htmlspecialchars(lender_bookings_text('current_booking_management')); ?>
            </h2>

            <span>
                <?= htmlspecialchars(lender_bookings_text('pending_requests_note')); ?>
            </span>

        </div>

        <div class="table-wrap">

            <?php if ($result->num_rows > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th><?= htmlspecialchars(__('request')); ?></th>

                            <th><?= htmlspecialchars(__('equipment')); ?></th>

                            <th><?= htmlspecialchars(__('renter')); ?></th>

                            <th><?= htmlspecialchars(__('rental_period')); ?></th>

                            <th><?= htmlspecialchars(__('amount')); ?></th>

                            <th><?= htmlspecialchars(__('status')); ?></th>

                            <th><?= htmlspecialchars(__('action')); ?></th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $row['request_code']
                                        ?: ('REQ' . $row['booking_id'])
                                    );
                                    ?>
                                </strong>

                                <br>

                                <small style="color:#7188A7;">
                                    #<?php echo (int)$row['booking_id']; ?>
                                </small>

                            </td>

                            <td>

                                <div class="equipment-info">

                                    <?php

                                    $img = !empty($row['equipment_image'])
                                        ? 'uploads/' . $row['equipment_image']
                                        : 'assets/images/default.png';

                                    ?>

                                    <img
                                        src="<?php echo htmlspecialchars($img); ?>"
                                        class="equipment-img"
                                        alt="Equipment"
                                        onerror="this.onerror=null; this.src='uploads/default.png';"
                                    >

                                    <div>

                                        <strong>
                                            <?php echo htmlspecialchars($row['equipment_title']); ?>
                                        </strong>

                                        <small>
                                            <?php echo htmlspecialchars($row['equipment_category']); ?>
                                        </small>

                                    </div>

                                </div>

                            </td>

                            <td>

                                <div class="renter">

                                    <strong>
                                        <?php echo htmlspecialchars($row['renter_name']); ?>
                                    </strong>

                                    <small>
                                        <?php echo htmlspecialchars($row['renter_phone']); ?>
                                    </small>

                                </div>

                            </td>

                            <td>

                                <?php echo date('d M Y', strtotime($row['start_date'])); ?>

                                <br>

                                <small style="color:#7188A7;">

                                    to

                                    <?php echo date('d M Y', strtotime($row['end_date'])); ?>

                                </small>

                            </td>

                            <td>

                                <strong>
                                    ₹<?php echo number_format((float)$row['total_amount'], 2); ?>
                                </strong>

                            </td>

                            <td>

                                <span class="status <?php echo statusClass($row['status']); ?>">

                                    <?php

                                    $status_key = match ($row['status']) {
                                        'Accepted' => 'accepted',
                                        'Delivered' => 'delivered',
                                        'Returned' => 'returned',
                                        default => ''
                                    };

                                    echo htmlspecialchars(
                                        $status_key !== ''
                                            ? __($status_key)
                                            : $row['status']
                                    );

                                    ?>

                                </span>

                            </td>

                            <td>

                                <a
                                    class="btn-view"
                                    href="lender_booking_details.php?booking_id=<?php echo (int)$row['booking_id']; ?><?php echo '&lang=' . urlencode($current_lang); ?>"
                                >

                                    <i class="fa-regular fa-eye"></i>

                                    <?= htmlspecialchars(__('view_details')); ?>

                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    <i class="fa-regular fa-calendar-xmark"></i>

                    <h3>
                        <?= htmlspecialchars(lender_bookings_text('no_current_bookings')); ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(lender_bookings_text('no_current_bookings_desc')); ?>
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </section>

</main>

<script>

function changeLanguage(lang) {

    const url = new URL(window.location.href);

    url.searchParams.set('lang', lang);

    window.location.href = url.toString();

}

</script>

</body>
</html>
