<?php
session_start();

require_once 'includes/lang.php';
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'lender') {
    header('Location: login.php');
    exit();
}

$lender_id = (int)$_SESSION['user_id'];
$lender_name = $_SESSION['full_name'] ?? 'Lender';

$current_lang = $current_lang ?? ($_SESSION['lang'] ?? 'en');
if (!in_array($current_lang, ['en', 'hi', 'kn'], true)) {
    $current_lang = 'en';
}
$lang_param = '?lang=' . urlencode($current_lang);

/* ---------------------------------------------------------
   Small page-specific translations.
   Existing project translations are still used everywhere
   they already exist.
--------------------------------------------------------- */
$earnings_text = [
    'en' => [
        'page_title' => 'Total Earnings',
        'subtitle' => 'Track your completed rental earnings across all equipment.',
        'total_earnings' => 'Total Earnings',
        'completed_rentals' => 'Completed Rentals',
        'this_month' => 'This Month',
        'earning_equipment' => 'Earning Equipment',
        'earnings_overview' => 'Earnings Overview',
        'monthly_earnings' => 'Monthly Earnings',
        'equipment_earnings' => 'Earnings by Equipment',
        'all_equipment' => 'All Equipment',
        'completed_rentals_title' => 'Completed Rentals',
        'completed_on' => 'Completed On',
        'rental_period' => 'Rental Period',
        'earned_amount' => 'Earned Amount',
        'no_completed' => 'No completed rentals yet.',
        'no_completed_msg' => 'Earnings will appear here after the renter confirms the return.',
        'rentals' => 'rentals',
        'rental' => 'rental',
        'total' => 'Total',
        'view_booking' => 'View Details',
        'all_time' => 'All Time',
        'month' => 'Month',
        'filter' => 'Equipment Filter',
        'select_equipment' => 'Select equipment',
    ],
    'hi' => [
        'page_title' => 'कुल कमाई',
        'subtitle' => 'सभी उपकरणों से पूरी हुई बुकिंग की कमाई देखें।',
        'total_earnings' => 'कुल कमाई',
        'completed_rentals' => 'पूरी हुई बुकिंग',
        'this_month' => 'इस महीने',
        'earning_equipment' => 'कमाई वाले उपकरण',
        'earnings_overview' => 'कमाई का अवलोकन',
        'monthly_earnings' => 'मासिक कमाई',
        'equipment_earnings' => 'उपकरण के अनुसार कमाई',
        'all_equipment' => 'सभी उपकरण',
        'completed_rentals_title' => 'पूरी हुई बुकिंग',
        'completed_on' => 'पूरा हुआ',
        'rental_period' => 'बाडा़ अवधि',
        'earned_amount' => 'कमाई',
        'no_completed' => 'अभी कोई बुकिंग पूरी नहीं हुई है।',
        'no_completed_msg' => 'रेंटर द्वारा वापसी की पुष्टि करने के बाद कमाई यहां दिखाई देगी।',
        'rentals' => 'बुकिंग',
        'rental' => 'बुकिंग',
        'total' => 'कुल',
        'view_booking' => 'विवरण देखें',
        'all_time' => 'अब तक',
        'month' => 'महीना',
        'filter' => 'उपकरण फ़िल्टर',
        'select_equipment' => 'उपकरण चुनें',
    ],
    'kn' => [
        'page_title' => 'ಒಟ್ಟು ಗಳಿಕೆ',
        'subtitle' => 'ಎಲ್ಲಾ ಉಪಕರಣಗಳ ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆಗಳಿಂದ ನಿಮ್ಮ ಗಳಿಕೆಯನ್ನು ವೀಕ್ಷಿಸಿ.',
        'total_earnings' => 'ಒಟ್ಟು ಗಳಿಕೆ',
        'completed_rentals' => 'ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆಗಳು',
        'this_month' => 'ಈ ತಿಂಗಳು',
        'earning_equipment' => 'ಗಳಿಕೆ ನೀಡಿದ ಉಪಕರಣಗಳು',
        'earnings_overview' => 'ಗಳಿಕೆ ಅವಲೋಕನ',
        'monthly_earnings' => 'ತಿಂಗಳ ಗಳಿಕೆ',
        'equipment_earnings' => 'ಉಪಕರಣದ ಪ್ರಕಾರ ಗಳಿಕೆ',
        'all_equipment' => 'ಎಲ್ಲಾ ಉಪಕರಣಗಳು',
        'completed_rentals_title' => 'ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆಗಳು',
        'completed_on' => 'ಪೂರ್ಣಗೊಂಡ ದಿನಾಂಕ',
        'rental_period' => 'ಬಾಡಿಗೆ ಅವಧಿ',
        'earned_amount' => 'ಗಳಿಸಿದ ಮೊತ್ತ',
        'no_completed' => 'ಇನ್ನೂ ಯಾವುದೇ ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿಲ್ಲ.',
        'no_completed_msg' => 'ಬಾಡಿಗೆದಾರರು ಹಿಂತಿರುಗಿದುದನ್ನು ದೃಢೀಕರಿಸಿದ ನಂತರ ಗಳಿಕೆ ಇಲ್ಲಿ ಕಾಣಿಸುತ್ತದೆ.',
        'rentals' => 'ಬಾಡಿಗೆಗಳು',
        'rental' => 'ಬಾಡಿಗೆ',
        'total' => 'ಒಟ್ಟು',
        'view_booking' => 'ವಿವರ ವೀಕ್ಷಿಸಿ',
        'all_time' => 'ಇಲ್ಲಿಯವರೆಗೆ',
        'month' => 'ತಿಂಗಳು',
        'filter' => 'ಉಪಕರಣ ಫಿಲ್ಟರ್',
        'select_equipment' => 'ಉಪಕರಣ ಆಯ್ಕೆಮಾಡಿ',
    ],
];
$t = $earnings_text[$current_lang];

/* ---------------------------------------------------------
   Profile + notification count
--------------------------------------------------------- */
$profile_pic = 'default_avatar.png';
$stmt = $conn->prepare('SELECT profile_pic FROM users WHERE user_id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $lender_id);
    $stmt->execute();
    $profile_result = $stmt->get_result();
    if ($profile_result && ($profile = $profile_result->fetch_assoc())) {
        if (!empty($profile['profile_pic'])) {
            $profile_pic = $profile['profile_pic'];
        }
    }
    $stmt->close();
}

$unread_notifications = 0;
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0');
if ($stmt) {
    $stmt->bind_param('i', $lender_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $unread_notifications = (int)($r['total'] ?? 0);
    $stmt->close();
}

/* ---------------------------------------------------------
   Completed earnings data
   IMPORTANT: Only Completed bookings are counted. This means
   the renter has confirmed the return in the current workflow.
--------------------------------------------------------- */
$completed_rows = [];
$completed_count = 0;
$total_earnings = 0.0;
$this_month_earnings = 0.0;
$equipment_count = 0;

$sql = "
    SELECT
        b.booking_id,
        b.request_code,
        b.start_date,
        b.end_date,
        b.total_days,
        b.quantity,
        b.total_amount,
        b.created_at,
        b.return_confirmed_at,
        e.equipment_id,
        e.title AS equipment_title,
        e.image AS equipment_image,
        u.full_name AS renter_name
    FROM bookings b
    INNER JOIN equipment e ON b.equipment_id = e.equipment_id
    INNER JOIN users u ON b.renter_id = u.user_id
    WHERE e.lender_id = ?
      AND b.status = 'Completed'
    ORDER BY COALESCE(b.return_confirmed_at, b.created_at) DESC, b.booking_id DESC
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $lender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['total_amount'] = (float)$row['total_amount'];
        $row['quantity'] = max(1, (int)($row['quantity'] ?? 1));
        $completed_rows[] = $row;
        $total_earnings += $row['total_amount'];
        $completed_count++;
    }
    $stmt->close();
}

/* ---------------------------------------------------------
   Monthly earnings: last 6 months, using completion/return
   confirmation date because that is when the rental becomes
   fully completed.
--------------------------------------------------------- */
$monthly_labels = [];
$monthly_values = [];
$monthly_sql = "
    SELECT
        DATE_FORMAT(COALESCE(b.return_confirmed_at, b.created_at), '%Y-%m') AS month_key,
        SUM(b.total_amount) AS total
    FROM bookings b
    INNER JOIN equipment e ON b.equipment_id = e.equipment_id
    WHERE e.lender_id = ?
      AND b.status = 'Completed'
      AND COALESCE(b.return_confirmed_at, b.created_at) >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY DATE_FORMAT(COALESCE(b.return_confirmed_at, b.created_at), '%Y-%m')
    ORDER BY month_key ASC
";
$monthly_map = [];
$stmt = $conn->prepare($monthly_sql);
if ($stmt) {
    $stmt->bind_param('i', $lender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $monthly_map[$row['month_key']] = (float)$row['total'];
    }
    $stmt->close();
}

for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("first day of -{$i} month");
    $key = date('Y-m', $ts);
    $monthly_labels[] = date('M Y', $ts);
    $monthly_values[] = round($monthly_map[$key] ?? 0, 2);
}
$this_month_key = date('Y-m');
$this_month_earnings = (float)($monthly_map[$this_month_key] ?? 0);

/* ---------------------------------------------------------
   Earnings by equipment
--------------------------------------------------------- */
$equipment_earnings = [];
$equipment_sql = "
    SELECT
        e.equipment_id,
        e.title AS equipment_title,
        e.image AS equipment_image,
        COUNT(b.booking_id) AS rental_count,
        COALESCE(SUM(b.total_amount), 0) AS total_earned
    FROM equipment e
    LEFT JOIN bookings b
        ON b.equipment_id = e.equipment_id
       AND b.status = 'Completed'
    WHERE e.lender_id = ?
    GROUP BY e.equipment_id, e.title, e.image
    HAVING rental_count > 0
    ORDER BY total_earned DESC, e.title ASC
";
$stmt = $conn->prepare($equipment_sql);
if ($stmt) {
    $stmt->bind_param('i', $lender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['rental_count'] = (int)$row['rental_count'];
        $row['total_earned'] = (float)$row['total_earned'];
        $equipment_earnings[] = $row;
    }
    $stmt->close();
}
$equipment_count = count($equipment_earnings);

/* ---------------------------------------------------------
   Equipment filter for detailed table
--------------------------------------------------------- */
$selected_equipment = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;
$filtered_rows = $completed_rows;
if ($selected_equipment > 0) {
    $filtered_rows = array_values(array_filter($completed_rows, static function ($row) use ($selected_equipment) {
        return (int)$row['equipment_id'] === $selected_equipment;
    }));
}

$filtered_total = 0.0;
foreach ($filtered_rows as $row) {
    $filtered_total += (float)$row['total_amount'];
}

/* Equipment dropdown should include all lender equipment. */
$all_equipment = [];
$stmt = $conn->prepare('SELECT equipment_id, title FROM equipment WHERE lender_id = ? ORDER BY title ASC');
if ($stmt) {
    $stmt->bind_param('i', $lender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_equipment[] = $row;
    }
    $stmt->close();
}

/* ---------------------------------------------------------
   Helpers
--------------------------------------------------------- */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function money(float $value): string {
    return '₹ ' . number_format($value, 2);
}
function format_date_value(?string $date): string {
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : '—';
}

$profile_url = 'uploads/' . e($profile_pic);
$avatar_fallback = 'https://ui-avatars.com/api/?name=' . urlencode($lender_name) . '&background=0f4c5c&color=fff';
?>
<!DOCTYPE html>
<html lang="<?= e($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($t['page_title']); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: #f4f6f9;
            color: #1e293b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        a { color: inherit; }

        /* ================= SIDEBAR ================= */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 250px;
            background: #fff;
            border-right: 1px solid #e0e0e0;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #1e3a8a;
            font-size: 15px;
            margin-bottom: 30px;
            line-height: 1.15;
        }
        .logo i { font-size: 24px; color: #0f4c5c; }
        .nav-list { list-style: none; margin: 0; padding: 0; }
        .nav-item { margin-bottom: 8px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: .2s ease;
        }
        .nav-link:hover, .nav-link.active { background: #0f4c5c; color: #fff; }
        .nav-link i { width: 18px; text-align: center; }

        /* ================= MAIN / TOP BAR ================= */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px 30px 40px;
        }
        .top-banner {
            background: #0f4c5c;
            color: #fff;
            text-align: center;
            padding: 9px 12px;
            font-weight: 700;
            border-radius: 6px;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
        }
        .search-box {
            position: relative;
            width: 350px;
            max-width: 100%;
        }
        .search-box input {
            width: 100%;
            height: 38px;
            padding: 8px 14px 8px 36px;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            outline: none;
            background: #fff;
        }
        .search-box i {
            position: absolute;
            left: 13px;
            top: 11px;
            color: #94a3b8;
        }
        .top-actions { display: flex; align-items: center; gap: 14px; }
        .lang-select {
            height: 38px;
            padding: 6px 34px 6px 12px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            font-size: 13px;
            outline: none;
            cursor: pointer;
        }
        .notification-link {
            position: relative;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #0f4c5c;
            text-decoration: none;
            border-radius: 50%;
            transition: .2s ease;
        }
        .notification-link:hover { background: #e0f2fe; color: #0284c7; }
        .notification-link i { font-size: 19px; }
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -1px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }
        .user-name { font-size: 14px; font-weight: 700; color: #1e293b; white-space: nowrap; }
        .user-profile-img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #0f4c5c;
            display: block;
        }

        /* ================= PAGE HEADER ================= */
        .page-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }
        .page-heading h1 { margin: 0 0 5px; font-size: 25px; color: #0f172a; }
        .page-heading p { margin: 0; color: #64748b; font-size: 14px; }
        .filter-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 9px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .03);
        }
        .filter-box label { font-size: 12px; color: #64748b; font-weight: 700; }
        .filter-box select { border: 0; outline: 0; font-weight: 600; color: #0f172a; background: transparent; max-width: 220px; }

        /* ================= KPI CARDS ================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            min-height: 125px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
        }
        .stat-top { display: flex; align-items: center; gap: 10px; color: #64748b; font-size: 13px; font-weight: 700; }
        .stat-icon { width: 38px; height: 38px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: 17px; }
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-green { background: #dcfce7; color: #16a34a; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-orange { background: #fef3c7; color: #d97706; }
        .stat-value { margin-top: 13px; font-size: 24px; line-height: 1; font-weight: 800; color: #0f172a; }
        .stat-note { margin-top: 8px; color: #94a3b8; font-size: 11px; }

        /* ================= CARDS / CHARTS ================= */
        .content-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(310px, 1fr); gap: 20px; margin-bottom: 20px; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
        }
        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 15px; }
        .card-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
        .card-subtitle { margin: 4px 0 0; color: #94a3b8; font-size: 12px; }
        .chart-wrap { position: relative; height: 300px; }
        .chart-wrap.small { height: 300px; }

        /* ================= EQUIPMENT PERFORMANCE ================= */
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .equipment-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .equipment-top { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .equipment-image { width: 52px; height: 52px; border-radius: 9px; object-fit: cover; background: #f1f5f9; flex-shrink: 0; }
        .equipment-name { font-size: 14px; font-weight: 800; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .equipment-count { color: #94a3b8; font-size: 11px; margin-top: 2px; }
        .equipment-money { font-size: 19px; font-weight: 800; color: #0f4c5c; }
        .equipment-footer { display: flex; justify-content: space-between; align-items: center; color: #64748b; font-size: 11px; }
        .mini-bar { height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
        .mini-bar span { display: block; height: 100%; background: #0f4c5c; border-radius: 99px; }

        /* ================= TABLE ================= */
        .table-card { margin-top: 0; }
        .table-wrap { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 820px; }
        th { text-align: left; padding: 11px 10px; color: #64748b; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; white-space: nowrap; }
        td { padding: 13px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; vertical-align: middle; }
        tr:last-child td { border-bottom: 0; }
        .equipment-cell { display: flex; align-items: center; gap: 10px; min-width: 180px; }
        .table-img { width: 42px; height: 42px; border-radius: 7px; object-fit: cover; background: #f1f5f9; flex-shrink: 0; }
        .equipment-cell strong { color: #0f172a; font-size: 12px; }
        .equipment-cell small { display: block; color: #94a3b8; margin-top: 3px; font-size: 10px; }
        .amount-cell { font-weight: 800; color: #0f4c5c; white-space: nowrap; }
        .completed-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 9px; border-radius: 999px; background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 800; }
        .details-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 7px; background: #e0f2fe; color: #0284c7; text-decoration: none; font-weight: 700; font-size: 10px; white-space: nowrap; }
        .details-btn:hover { background: #bae6fd; }

        /* ================= EMPTY STATE ================= */
        .empty-state { text-align: center; padding: 45px 20px; color: #94a3b8; }
        .empty-state .empty-icon { width: 55px; height: 55px; margin: 0 auto 12px; border-radius: 50%; background: #e0f2fe; color: #0f4c5c; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .empty-state h3 { margin: 0 0 6px; color: #475569; font-size: 16px; }
        .empty-state p { margin: 0; font-size: 12px; }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1150px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .equipment-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 800px) {
            .sidebar { position: static; width: 100%; min-height: auto; border-right: 0; border-bottom: 1px solid #e0e0e0; padding: 12px; }
            .logo { margin-bottom: 12px; }
            .nav-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px; }
            .nav-item { margin-bottom: 0; }
            .nav-link { padding: 10px 11px; font-size: 12px; }
            .main-content { margin-left: 0; padding: 15px; }
            .top-bar { align-items: stretch; flex-direction: column; }
            .search-box { width: 100%; }
            .top-actions { justify-content: flex-end; }
            .page-heading { flex-direction: column; align-items: stretch; }
            .filter-box { justify-content: space-between; }
        }
        @media (max-width: 560px) {
            .stats-grid, .equipment-grid { grid-template-columns: 1fr; }
            .top-actions { gap: 7px; }
            .user-name { display: none; }
            .chart-wrap, .chart-wrap.small { height: 250px; }
            .card { padding: 15px; }
            .page-heading h1 { font-size: 21px; }
        }
    </style>
</head>
<body>

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-tractor"></i>
        <span>AGRICULTURE<br><small style="font-size:9px;color:#64748b;font-weight:600;">EQUIPMENT RENTAL SYSTEM</small></span>
    </div>

    <ul class="nav-list">
        <li class="nav-item"><a href="lender_dashboard.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-chart-line"></i><?= e(__('dashboard')); ?></a></li>
        <li class="nav-item"><a href="add_equipment.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-circle-plus"></i><?= e(__('add_equipment')); ?></a></li>
        <li class="nav-item"><a href="my_equipment.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-list"></i><?= e(__('my_equipment')); ?></a></li>
        <li class="nav-item"><a href="rental_requests.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i><?= e(__('rental_requests')); ?></a></li>
        <li class="nav-item"><a href="active_rentals.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-truck-ramp-box"></i><?= e(__('active_rentals')); ?></a></li>
        <li class="nav-item"><a href="my_bookings.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-calendar-check"></i><?= e(__('my_bookings')); ?></a></li>
        <li class="nav-item"><a href="rental_history.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-history"></i><?= e(__('rental_history')); ?></a></li>
        <li class="nav-item"><a href="reviews.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-star"></i><?= e(__('reviews')); ?></a></li>
        <li class="nav-item"><a href="total_earnings.php<?= $lang_param; ?>" class="nav-link active"><i class="fa-solid fa-wallet"></i><?= e(__('total_earnings')); ?></a></li>
        <li class="nav-item"><a href="profile.php<?= $lang_param; ?>" class="nav-link"><i class="fa-solid fa-user"></i><?= e(__('my_profile')); ?></a></li>
        <li class="nav-item" style="margin-top:20px;"><a href="logout.php" class="nav-link" style="color:#ef4444;"><i class="fa-solid fa-right-from-bracket"></i><?= e(__('logout')); ?></a></li>
    </ul>
</aside>

<!-- ================= MAIN ================= -->
<main class="main-content">
    <div class="top-banner"><?= e(__('lender_dashboard')); ?></div>

    <div class="top-bar">
        <form action="search_equipment.php" method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" placeholder="<?= e(__('search_placeholder')); ?>">
        </form>

        <div class="top-actions">
            <select class="lang-select" onchange="window.location.href='total_earnings.php?lang=' + this.value;">
                <option value="en" <?= $current_lang === 'en' ? 'selected' : ''; ?>>🌐 English</option>
                <option value="hi" <?= $current_lang === 'hi' ? 'selected' : ''; ?>>🌐 हिन्दी (Hindi)</option>
                <option value="kn" <?= $current_lang === 'kn' ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
            </select>

            <a href="lender_notifications.php<?= $lang_param; ?>" class="notification-link" title="<?= e(__('notifications')); ?>" aria-label="<?= e(__('notifications')); ?>">
                <i class="fa-regular fa-bell"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?= $unread_notifications > 99 ? '99+' : $unread_notifications; ?></span>
                <?php endif; ?>
            </a>

            <span class="user-name"><?= e($lender_name); ?></span>
            <a href="profile.php<?= $lang_param; ?>">
                <img src="<?= $profile_url; ?>" onerror="this.src='<?= e($avatar_fallback); ?>'" class="user-profile-img" alt="Profile">
            </a>
        </div>
    </div>

    <div class="page-heading">
        <div>
            <h1><?= e($t['page_title']); ?></h1>
            <p><?= e($t['subtitle']); ?></p>
        </div>

        <form method="GET" action="total_earnings.php" class="filter-box">
            <input type="hidden" name="lang" value="<?= e($current_lang); ?>">
            <label for="equipmentFilter"><i class="fa-solid fa-filter"></i> <?= e($t['filter']); ?></label>
            <select id="equipmentFilter" name="equipment_id" onchange="this.form.submit()">
                <option value="0"><?= e($t['all_equipment']); ?></option>
                <?php foreach ($all_equipment as $eq): ?>
                    <option value="<?= (int)$eq['equipment_id']; ?>" <?= $selected_equipment === (int)$eq['equipment_id'] ? 'selected' : ''; ?>>
                        <?= e($eq['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- KPI CARDS -->
    <section class="stats-grid">
        <div class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-purple"><i class="fa-solid fa-indian-rupee-sign"></i></span><?= e($t['total_earnings']); ?></div>
            <div class="stat-value"><?= money($total_earnings); ?></div>
            <div class="stat-note"><?= e($t['all_time']); ?> • <?= $completed_count; ?> <?= e($completed_count === 1 ? $t['rental'] : $t['rentals']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-green"><i class="fa-solid fa-circle-check"></i></span><?= e($t['completed_rentals']); ?></div>
            <div class="stat-value"><?= $completed_count; ?></div>
            <div class="stat-note"><?= e($t['completed_rentals_title']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-blue"><i class="fa-solid fa-calendar-days"></i></span><?= e($t['this_month']); ?></div>
            <div class="stat-value"><?= money($this_month_earnings); ?></div>
            <div class="stat-note"><?= e(date('F Y')); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-top"><span class="stat-icon icon-orange"><i class="fa-solid fa-tractor"></i></span><?= e($t['earning_equipment']); ?></div>
            <div class="stat-value"><?= $equipment_count; ?></div>
            <div class="stat-note"><?= e($t['all_equipment']); ?></div>
        </div>
    </section>

    <!-- CHARTS -->
    <section class="content-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><?= e($t['monthly_earnings']); ?></h2>
                    <p class="card-subtitle"><?= e($t['earnings_overview']); ?> • <?= e($t['all_time']); ?></p>
                </div>
            </div>
            <div class="chart-wrap"><canvas id="monthlyChart"></canvas></div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><?= e($t['equipment_earnings']); ?></h2>
                    <p class="card-subtitle"><?= e($t['total_earnings']); ?> • <?= e($t['all_equipment']); ?></p>
                </div>
            </div>
            <div class="chart-wrap small"><canvas id="equipmentChart"></canvas></div>
        </div>
    </section>

    <!-- EQUIPMENT BREAKDOWN -->
    <?php if (!empty($equipment_earnings)): ?>
        <section class="equipment-grid">
            <?php $max_equipment_earnings = max(1, max(array_column($equipment_earnings, 'total_earned'))); ?>
            <?php foreach ($equipment_earnings as $eq): ?>
                <?php
                    $percent = min(100, max(0, ($eq['total_earned'] / $max_equipment_earnings) * 100));
                    $eq_img = !empty($eq['equipment_image']) ? 'uploads/' . $eq['equipment_image'] : '';
                ?>
                <div class="equipment-card">
                    <div class="equipment-top">
                        <?php if ($eq_img): ?>
                            <img src="<?= e($eq_img); ?>" class="equipment-image" alt="<?= e($eq['equipment_title']); ?>" onerror="this.style.display='none';">
                        <?php else: ?>
                            <div class="equipment-image" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class="fa-solid fa-tractor"></i></div>
                        <?php endif; ?>
                        <div style="min-width:0;">
                            <div class="equipment-name" title="<?= e($eq['equipment_title']); ?>"><?= e($eq['equipment_title']); ?></div>
                            <div class="equipment-count"><?= (int)$eq['rental_count']; ?> <?= e($eq['rental_count'] === 1 ? $t['rental'] : $t['rentals']); ?></div>
                        </div>
                    </div>
                    <div class="equipment-money"><?= money((float)$eq['total_earned']); ?></div>
                    <div class="mini-bar"><span style="width:<?= $percent; ?>%;"></span></div>
                    <div class="equipment-footer">
                        <span><?= e($t['total']); ?></span>
                        <strong><?= number_format($percent, 0); ?>%</strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <!-- COMPLETED RENTAL DETAILS -->
    <section class="card table-card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= e($t['completed_rentals_title']); ?></h2>
                <p class="card-subtitle">
                    <?= e($selected_equipment > 0 ? $t['select_equipment'] : $t['all_equipment']); ?>
                    <?php if ($selected_equipment > 0): ?> • <?= money($filtered_total); ?> <?= e($t['total']); ?><?php endif; ?>
                </p>
            </div>
            <span class="completed-pill"><i class="fa-solid fa-check"></i> <?= $completed_count; ?></span>
        </div>

        <?php if (empty($filtered_rows)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-wallet"></i></div>
                <h3><?= e($t['no_completed']); ?></h3>
                <p><?= e($t['no_completed_msg']); ?></p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= e(__('equipment')); ?></th>
                            <th><?= e(__('renter')); ?></th>
                            <th><?= e($t['rental_period']); ?></th>
                            <th><?= e($t['completed_on']); ?></th>
                            <th><?= e($t['earned_amount']); ?></th>
                            <th><?= e(__('action')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filtered_rows as $row): ?>
                        <?php
                            $row_img = !empty($row['equipment_image']) ? 'uploads/' . $row['equipment_image'] : '';
                            $completion_date = $row['return_confirmed_at'] ?? $row['created_at'];
                        ?>
                        <tr>
                            <td>
                                <div class="equipment-cell">
                                    <?php if ($row_img): ?>
                                        <img src="<?= e($row_img); ?>" class="table-img" alt="<?= e($row['equipment_title']); ?>" onerror="this.style.display='none';">
                                    <?php else: ?>
                                        <div class="table-img" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class="fa-solid fa-tractor"></i></div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($row['equipment_title']); ?></strong>
                                        <small><?= e($row['request_code'] ?: ('#' . $row['booking_id'])); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($row['renter_name']); ?></td>
                            <td><?= e(format_date_value($row['start_date'])); ?> – <?= e(format_date_value($row['end_date'])); ?></td>
                            <td><?= e(format_date_value($completion_date)); ?></td>
                            <td class="amount-cell"><?= money((float)$row['total_amount']); ?></td>
                            <td><a class="details-btn" href="lender_booking_details.php?booking_id=<?= (int)$row['booking_id']; ?>&lang=<?= urlencode($current_lang); ?>"><i class="fa-solid fa-eye"></i><?= e($t['view_booking']); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
const monthlyLabels = <?= json_encode($monthly_labels, JSON_UNESCAPED_UNICODE); ?>;
const monthlyValues = <?= json_encode($monthly_values); ?>;
const equipmentLabels = <?= json_encode(array_column($equipment_earnings, 'equipment_title'), JSON_UNESCAPED_UNICODE); ?>;
const equipmentValues = <?= json_encode(array_map(static fn($x) => round((float)$x['total_earned'], 2), $equipment_earnings)); ?>;

Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.color = '#64748b';

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: '<?= e($t['total_earnings']); ?>',
            data: monthlyValues,
            borderColor: '#0f4c5c',
            backgroundColor: 'rgba(15, 76, 92, 0.10)',
            pointBackgroundColor: '#0f4c5c',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 3,
            fill: true,
            tension: 0.35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (ctx) => ' ₹ ' + Number(ctx.raw || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#eef2f7' },
                ticks: {
                    callback: (value) => '₹' + Number(value).toLocaleString('en-IN')
                }
            },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('equipmentChart'), {
    type: 'doughnut',
    data: {
        labels: equipmentLabels,
        datasets: [{
            data: equipmentValues,
            backgroundColor: ['#0f4c5c','#0284c7','#16a34a','#d97706','#9333ea','#e11d48','#475569','#0891b2'],
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 11, boxHeight: 11, padding: 12, font: { size: 10 } }
            },
            tooltip: {
                callbacks: {
                    label: (ctx) => ' ' + ctx.label + ': ₹' + Number(ctx.raw || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                }
            }
        }
    }
});
</script>
</body>
</html>
