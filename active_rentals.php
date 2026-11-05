<?php
session_start();
require_once 'includes/lang.php';

// Fix the Hindi Active Rentals translations without changing the shared language file.
// The shared lang.php contains two Hindi dictionaries, so the later one overrides
// the earlier Active Rentals entries. Restore only the Active Rentals keys needed here.
$translations['hi'] = array_merge($translations['hi'], [
    'lender_role' => 'ऋणदाता',
    'menu_dashboard' => 'डैशबोर्ड',
    'menu_add_equipment' => 'उपकरण जोड़ें',
    'menu_my_equipment' => 'मेरे उपकरण',
    'menu_rental_requests' => 'किराये के अनुरोध',
    'menu_active_rentals' => 'सक्रिय किराए',
    'menu_rental_history' => 'किराए का इतिहास',
    'menu_profile' => 'प्रोफ़ाइल',
    'menu_logout' => 'लॉग आउट',
    'sidebar_help_title' => 'सहायता चाहिए?',
    'sidebar_help_desc' => 'हम किसी भी प्रश्न के लिए आपकी सहायता के लिए यहाँ हैं।',
    'sidebar_contact_btn' => 'सहायता से संपर्क करें',
    'page_title_active_rentals' => 'सक्रिय किराए',
    'page_subtitle_active_rentals' => 'अपने सभी चल रहे उपकरणों के किराए को प्रबंधित और ट्रैक करें।',
    'stat_total_active' => 'कुल सक्रिय किराए',
    'stat_ongoing_rentals' => 'चल रहे किराए',
    'stat_total_equipment' => 'कुल उपकरण',
    'stat_rented_out' => 'पंजीकृत इकाइयाँ',
    'stat_total_days' => 'किराए पर दिए गए कुल दिन',
    'stat_across_rentals' => 'सभी किरायों में',
    'stat_total_earned' => 'कुल कमाई (अब तक)',
    'stat_from_active' => 'सक्रिय किरायों से',
    'table_col_equipment' => 'उपकरण',
    'table_col_renter' => 'किराएदार विवरण',
    'table_col_period' => 'किराए की अवधि',
    'table_col_address' => 'वितरण पता',
    'table_col_days_left' => 'शेष दिन',
    'table_col_amount' => 'राशि (कुल)',
    'table_col_status' => 'स्थिति',
    'table_col_action' => 'कार्रवाई',
    'btn_view_details' => 'विवरण देखें',
    'btn_track_equipment' => 'टरैक करें',
    'no_active_rentals' => 'आपके खाते में कोई सक्रिय किराया नहीं मिला.'
]);

// Active Rentals Hindi translations are defined here because lang.php currently
// contains two top-level Hindi dictionaries, and the later one overrides the first one.
$active_rentals_hi = [
    'lender_role' => 'ऋणदाता',
    'page_title_active_rentals' => 'सक्रिय किराए',
    'page_subtitle_active_rentals' => 'अपने सभी चल रहे उपकरणों के किराए को प्रबंधित और ट्रैक करें।',
    'stat_total_active' => 'कुल सक्रिय किराए',
    'stat_ongoing_rentals' => 'चल रहे किराए',
    'stat_total_equipment' => 'कुल उपकरण',
    'stat_rented_out' => 'पंजीकृत इकाइयाँ',
    'stat_total_days' => 'किराए पर दिए गए कुल दिन',
    'stat_across_rentals' => 'सभी किरायों में',
    'stat_total_earned' => 'कुल कमाई (अब तक)',
    'stat_from_active' => 'सक्रिय किरायों से',
    'table_col_equipment' => 'उपकरण',
    'table_col_renter' => 'किराएदार विवरण',
    'table_col_period' => 'किराए की अवधि',
    'table_col_address' => 'वितरण पता',
    'table_col_days_left' => 'शेष दिन',
    'table_col_amount' => 'राशि (कुल)',
    'table_col_status' => 'स्थिति',
    'table_col_action' => 'कार्रवाई',
    'btn_view_details' => 'विवरण देखें',
    'btn_track_equipment' => 'उपकरण ट्रैक करें',
    'no_active_rentals' => 'कोई सक्रिय किराया नहीं है',
    'days' => 'दिन',
    'days_left' => 'दिन शेष',
    'due_today' => 'आज देय',
    'return_today' => 'आज वापस करें',
    'overdue' => 'अतिदेय',
    'return_by' => 'वापसी की तारीख',
    'returned_on' => 'वापस किया गया',
    'active' => 'सक्रिय'
];

function active_rental__(string $key): string {
    global $current_lang, $active_rentals_hi;
    if ($current_lang === 'hi' && isset($active_rentals_hi[$key])) {
        return $active_rentals_hi[$key];
    }
    return __($key);
}

function active_rental_category_label($category_name) {
    $key = strtolower(trim($category_name));
    $category_keys = [
        'tractor' => 'tractor',
        'harvesting' => 'harvesting',
        'irrigation' => 'irrigation',
        'tillage' => 'tillage',
        'seeding' => 'seeding',
        'spraying' => 'spraying'
    ];
    if (isset($category_keys[$key])) {
        $translated = active_rental__($category_keys[$key]);
        if ($translated !== $category_keys[$key]) {
            return $translated;
        }
        $prefixed = active_rental__('category_' . $category_keys[$key]);
        if ($prefixed !== 'category_' . $category_keys[$key]) {
            return $prefixed;
        }
    }
    $direct_prefixed = active_rental__('category_' . $key);
    if ($direct_prefixed !== 'category_' . $key) {
        return $direct_prefixed;
    }
    $direct = active_rental__($key);
    return ($direct !== $key) ? $direct : $category_name;
}

$active_rental_status_text = [
    'en' => [
        'return_marked_lender'=>'Return marked by lender','waiting_return_confirm'=>'Waiting for renter to confirm returned equipment',
        'awaiting_return_confirm'=>'Awaiting Return Confirmation','rental_completed'=>'Rental completed',
        'renter_confirmed_return'=>'Renter confirmed the equipment return','completed'=>'Completed',
        'not_delivered'=>'Not delivered yet','delivered_confirmation_pending'=>'Delivered - confirmation pending',
        'waiting_lender_delivery'=>'Waiting for lender to deliver equipment','waiting_renter_delivery'=>'Waiting for renter to confirm delivery',
        'awaiting_delivery'=>'Awaiting Delivery','waiting_delivery_confirm'=>'Waiting for Delivery Confirmation',
        'in_use'=>'In Use','awaiting_return'=>'Awaiting return','equipment_alt'=>'Equipment','to'=>'to','notifications'=>'Notifications',
        'overdue' => 'Overdue', 'due_today' => 'Due Today','return_by' => 'Return by'
    ],
    'kn' => [
        'return_marked_lender'=>'ಸಾಲದಾತರು ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ ಎಂದು ಗುರುತಿಸಿದ್ದಾರೆ','waiting_return_confirm'=>'ಹಿಂತಿರುಗಿಸಿದ ಉಪಕರಣವನ್ನು ಬಾಡಿಗೆದಾರರು ದೃಢೀಕರಿಸಲು ಕಾಯಲಾಗುತ್ತಿದೆ',
        'awaiting_return_confirm'=>'ಹಿಂತಿರುಗಿಸುವ ದೃಢೀಕರಣಕ್ಕಾಗಿ ಕಾಯಲಾಗುತ್ತಿದೆ','rental_completed'=>'ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿದೆ',
        'renter_confirmed_return'=>'ಬಾಡಿಗೆದಾರರು ಉಪಕರಣ ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ','completed'=>'ಪೂರ್ಣಗೊಂಡಿದೆ',
        'not_delivered'=>'ಇನ್ನೂ ತಲುಪಿಸಲಾಗಿಲ್ಲ','delivered_confirmation_pending'=>'ತಲುಪಿಸಲಾಗಿದೆ - ದೃಢೀಕರಣ ಬಾಕಿಯಿದೆ',
        'waiting_lender_delivery'=>'ಸಾಲದಾತರು ಉಪಕರಣ ತಲುಪಿಸಲು ಕಾಯಲಾಗುತ್ತಿದೆ','waiting_renter_delivery'=>'ಉಪಕರಣ ಸ್ವೀಕರಿಸಿರುವುದನ್ನು ಬಾಡಿಗೆದಾರರು ದೃಢೀಕರಿಸಲು ಕಾಯಲಾಗುತ್ತಿದೆ',
        'awaiting_delivery'=>'ತಲುಪಿಸುವಿಕೆ ಬಾಕಿಯಿದೆ','waiting_delivery_confirm'=>'ತಲುಪಿಸುವಿಕೆಯ ದೃಢೀಕರಣಕ್ಕಾಗಿ ಕಾಯಲಾಗುತ್ತಿದೆ',
        'in_use'=>'ಬಳಕೆಯಲ್ಲಿದೆ','awaiting_return'=>'ಹಿಂತಿರುಗಿಸುವಿಕೆ ಬಾಕಿಯಿದೆ','equipment_alt'=>'ಉಪಕರಣ','to'=>'ಇಂದ','notifications'=>'ಅಧಿಸೂಚನೆಗಳು',
        'overdue' => 'ಅವಧಿ ಮೀರಿದೆ', 'due_today' => 'ಇಂದು ಹಿಂತಿರುಗಿಸಬೇಕು','return_by' => 'ಹಿಂತಿರುಗಿಸಬೇಕಾದ ದಿನಾಂಕ:'
    ],
    'hi' => [
        'return_marked_lender'=>'ऋणदाता ने वापसी दर्ज की है','waiting_return_confirm'=>'किरायेदार द्वारा उपकरण की वापसी की पुष्टि की प्रतीक्षा है',
        'awaiting_return_confirm'=>'वापसी की पुष्टि की प्रतीक्षा है','rental_completed'=>'किराया पूरा हुआ',
        'renter_confirmed_return'=>'किरायेदार ने उपकरण की वापसी की पुष्टि की','completed'=>'पूरा हुआ',
        'not_delivered'=>'अभी तक वितरित नहीं हुआ','delivered_confirmation_pending'=>'वितरित - पुष्टि बाकी है',
        'waiting_lender_delivery'=>'ऋणदाता द्वारा उपकरण वितरित करने की प्रतीक्षा है','waiting_renter_delivery'=>'किरायेदार द्वारा डिलीवरी की पुष्टि की प्रतीक्षा है',
        'awaiting_delivery'=>'डिलीवरी की प्रतीक्षा है','waiting_delivery_confirm'=>'डिलीवरी की पुष्टि की प्रतीक्षा है',
        'in_use'=>'उपयोग में','awaiting_return'=>'वापसी की प्रतीक्षा है','equipment_alt'=>'उपकरण','to'=>'से','notifications'=>'सूचनाएँ',
        'overdue' => 'किराये की अवधि समाप्त', 'due_today' => 'आज वापस करें','return_by' => 'वापसी की अंतिम तारीख:'
    ],
];

function active_rental_status__(string $key): string {
    global $current_lang, $active_rental_status_text;
    $l = isset($active_rental_status_text[$current_lang]) ? $current_lang : 'en';
    if (isset($active_rental_status_text[$l][$key])) {
        return $active_rental_status_text[$l][$key];
    }
    return $active_rental_status_text['en'][$key] ?? $key;
}

function active_rental_image_src($stored_image): string {
    $image = trim((string)$stored_image);
    if ($image === '') return '';
    if (preg_match('~^(https?://|data:)~i', $image)) return $image;
    $image = str_replace('\\\\', '/', $image);
    $image = preg_replace('~^(\\./)+~', '', $image);
    if (strpos($image, '/') === false) return 'uploads/' . $image;
    return ltrim($image, '/');
}

// Database connection configuration
$host = 'localhost';
$db   = 'agri_rental_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Authentication Check: Ensure lender is logged in
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'lender') {
    // Defaulting to lender ID 7 from your seed data for testing if session is absent
    $_SESSION['user_id'] = 7;
}
$lender_id = $_SESSION['user_id'];

$lender_name = $_SESSION['full_name'] ?? 'Tejomurthy';
$name_parts = preg_split('/\s+/', trim($lender_name));
if (count($name_parts) >= 2) {
    $lender_initials = strtoupper(
        substr($name_parts[0], 0, 1) .
        substr($name_parts[count($name_parts) - 1], 0, 1)
    );
} else {
    $lender_initials = strtoupper(substr($name_parts[0] ?? 'U', 0, 2));
}

// Fetch Dynamic Summary Statistics for this Lender
$stats_query = "SELECT 
                    COUNT(b.booking_id) as total_active,
                    COALESCE(SUM(b.total_amount), 0) as total_earned,
                    COALESCE(SUM(b.total_days), 0) as total_days
                FROM bookings b
                JOIN equipment e ON b.equipment_id = e.equipment_id
                WHERE e.lender_id = ? AND b.status IN ('Accepted', 'Delivered', 'Overdue', 'Returned')";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch Equipment count registered by this lender
$eq_count_query = "SELECT COUNT(*) as total_eq FROM equipment WHERE lender_id = ?";
$stmt_eq = $conn->prepare($eq_count_query);
$stmt_eq->bind_param("i", $lender_id);
$stmt_eq->execute();
$eq_stats = $stmt_eq->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(active_rental__('page_title_active_rentals')); ?> - Agriculture Equipment Rental System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
:root{--navy-primary:#0B3478;--navy-dark:#082B65;--blue-hover:#164C9A;--sidebar-bg:#C7DDF7;--sidebar-active:#A6C8F5;--text-dark:#10194A;--text-muted:#425D80;--bg-body:#EAF3FF;--card-bg:#F8FBFF;--border-color:#C8DDF5;--blue-accent:#8DB8F0;}

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #EAF3FF;
            color: #10194A;
        }

        /* Header */
        .main-header {
            background: #F8FBFF;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 25px;
            border-bottom: 1px solid #C8DDF5;
            position: sticky;
            top: 0;
            z-index: 1000;
            margin-left: 250px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0B3478;
            font-weight: bold;
            font-size: 18px;
        }

        .logo-icon {
            font-size: 24px;
        }

        .logo-title small {
            display: block;
            font-size: 9px;
            color: #425D80;
            letter-spacing: 1px;
        }

        .header-search-bar {
            display: flex;
            align-items: center;
            background: #EAF3FF;
            padding: 8px 15px;
            border-radius: 20px;
            width: 350px;
            gap: 10px;
        }

        .header-search-bar input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
            font-size: 14px;
        }

        .header-right-controls {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }

        .language-selector select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #F8FBFF;
            font-size: 13px;
            color: #10194A;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }

        .language-selector select:focus {
            border-color: #0B3478;
        }

        .notification-icon {
            position: relative;
            font-size: 18px;
            cursor: pointer;
        }

        .notification-icon .badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #0B3478;
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
        }

        .user-profile-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #0B3478;
            color: #F8FBFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border: 2px solid #0B3478;
        }

        .user-info .user-name {
            display: block;
            font-size: 13px;
            font-weight: bold;
        }

        .user-info .user-role {
            font-size: 11px;
            color: #FFFFFF;
            opacity: 0.88;
            font-weight: 600;
        }

        .user-info .user-name { color: #FFFFFF; }
        .user-profile-menu { color: #FFFFFF; }
        .avatar { background: #FFFFFF; color: #0B3478; border-color: #FFFFFF; }

        /* Main Layout */
        .dashboard-container {
            display: block;
            min-height: calc(100vh - 65px);
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 25px;
            background: #EAF3FF;
            overflow-x: auto;
            min-height: calc(100vh - 65px);
        }

        .content-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .content-header-row h1 {
            font-size: 22px;
            color: #222;
        }

        .content-header-row p {
            font-size: 13px;
            color: #425D80;
        }

        .btn-download-report {
            background: #D9E9FC;
            color: #0B3478;
            border: 1px solid #A6C8F5;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stats Grid */
        .stats-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            border: 1px solid #C8DDF5;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .bg-green {
            background: #0B3478;
        }

        .bg-blue {
            background: #164C9A;
        }

        .bg-orange {
            background: #f57c00;
        }

        .bg-purple {
            background: #7b1fa2;
        }

        .stat-title {
            font-size: 12px;
            color: #425D80;
            display: block;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #222;
            margin: 3px 0;
        }

        .stat-desc {
            font-size: 11px;
            color: #888;
        }

        /* Table Card */
        .table-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #C8DDF5;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .data-table th {
            background: #F8FBFF;
            padding: 15px;
            font-weight: 600;
            color: #555;
            border-bottom: 1px solid #C8DDF5;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #EAF3FF;
            vertical-align: middle;
        }

        .table-equipment-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .eq-thumb {
            width: 45px;
            height: 45px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #C8DDF5;
        }

        .eq-thumb-wrap { width:45px; height:45px; flex:0 0 45px; position:relative; }
        .eq-thumb-wrap .eq-thumb { display:block; width:100%; height:100%; }
        .eq-thumb-placeholder { width:45px; height:45px; border-radius:6px; border:1px solid #C8DDF5; background:#EAF3FF; color:#0B3478; display:flex; align-items:center; justify-content:center; font-size:20px; }

        /* Badges & Actions */
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-active {
            background: #D9E9FC;
            color: #0B3478;
        }

        .status-due {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-overdue {
            background: #ffebee;
            color: #c62828;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
            border: 1px solid #ddd;
            background: white;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            color: #164C9A;
            border-color: #bbdefb;
            background: #e3f2fd;
        }

@media (max-width: 1200px) {
            .stats-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 991px) {
            .main-header {
                margin-left: 220px;
            }

            .main-content {
                margin-left: 220px;
            }
        }

        @media (max-width: 768px) {
            .main-header {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .header-search-bar {
                display: none;
            }

            .stats-cards-grid {
                grid-template-columns: 1fr;
            }
        }
    

/* ===== CONSISTENT MAIN INTERFACE THEME (reference palette) ===== */
:root {
  --navy-primary: #0B3478 !important;
  --navy-dark: #082B65 !important;
  --blue-hover: #164C9A !important;
  --sidebar-bg: #C7DDF7 !important;
  --sidebar-active: #A6C8F5 !important;
  --text-dark: #10194A !important;
  --text-muted: #425D80 !important;
  --bg-body: #EAF3FF !important;
  --card-bg: #F8FBFF !important;
  --border-color: #C8DDF5 !important;
  --blue-accent: #8DB8F0 !important;
}
html, body { background-color: #EAF3FF !important; color: #10194A; }
/* Main headers and top navigation use the reference's deep navy */
.main-header, .top-nav, .topbar, .top-bar-header, header.main-header,
.header, .navbar, .topbar-header {
  background: #0B3478 !important;
  color: #F8FBFF !important;
  border-color: #0B3478 !important;
}
.main-header a, .top-nav a, .topbar a, .top-bar-header a,
.header a, .navbar a, .topbar-header a,
.main-header .logo-container, .main-header .logo-title,
.main-header .logo-title small { color: #F8FBFF !important; }
/* Search and controls */
.header-search-bar, .search-box input, .header-search input,
.search-bar, .search-container input, .language-selector select,
.language-select, .lang-select, select, input:not([type=checkbox]):not([type=radio]),
textarea {
  border-color: #C8DDF5;
}
/* Reusable content surfaces */
.panel, .card, .summary-card, .stat-card, .stat, .metric-card,
.equipment-card, .feature-card, .review, .notification-card,
.booking-card, .request-card, .earnings-card, .table-container,
.table-responsive, .content-card, .section-card, .empty,
.table-wrapper, .data-card, .chart-card {
  background-color: #F8FBFF;
  border-color: #C8DDF5;
  box-shadow: 0 5px 18px rgba(11, 52, 120, 0.07);
}
/* Main titles, labels and supporting text */
h1, h2, h3, h4, h5, .page-title, .section-title, .card-title {
  color: #10194A;
}
.subtitle, .muted, .text-muted, .description, .page-subtitle,
.stat-label, .card-subtitle { color: #425D80; }
/* Primary actions and buttons */
button.primary, .btn-primary, .primary-btn, .search-btn,
button[type=submit], input[type=submit], .action-primary {
  background: #0B3478 !important;
  border-color: #0B3478 !important;
  color: #FFFFFF !important;
}
button.primary:hover, .btn-primary:hover, .primary-btn:hover,
.search-btn:hover, button[type=submit]:hover, input[type=submit]:hover,
.action-primary:hover { background: #164C9A !important; border-color: #164C9A !important; }
/* Keep status badges (success, pending, rejected, overdue) semantically colored. */

</style>
</head>

<body>

    <!-- SHARED LENDER SIDEBAR -->
    <?php include 'lender_sidebar.php'; ?>

    <!-- Top Navigation Header -->
    <header class="main-header">
        <div class="header-right-controls">

            <div class="language-selector">
                <select id="langSelect" onchange="window.location.href='active_rentals.php?lang=' + this.value;">
                    <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                    <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी</option>
                </select>
            </div>

            <a href="lender_notifications.php?lang=<?php echo urlencode($current_lang); ?>" class="notification-icon" style="color: #333; text-decoration: none;" title="<?php echo htmlspecialchars(active_rental_status__('notifications'), ENT_QUOTES, 'UTF-8'); ?>" >
                <i class="fa-regular fa-bell"></i>
                <span class="badge">3</span>
            </a>

            <div class="user-profile-menu">
                <div class="avatar"><?php echo htmlspecialchars($lender_initials); ?></div>

                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($lender_name); ?></span>
                    <span class="user-role">
                                                <?php echo active_rental__('lender_role'); ?>
                                            </span>
                </div>
            </div>

        </div>
    </header>

    <div class="dashboard-container">

        <!-- Main Content Area -->
        <main class="main-content">

            <div class="content-header-row">
                <div>
                    <h1>
                                                <?php echo active_rental__('page_title_active_rentals'); ?>
                                            </h1>

                    <p>
                                                <?php echo active_rental__('page_subtitle_active_rentals'); ?>
                                            </p>
                </div>

            </div>

            <!-- Dynamic Summary Cards -->
            <div class="stats-cards-grid">

                <div class="stat-card">
                    <div class="stat-icon bg-green">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_active'); ?>
                                            </span>

                        <h2 class="stat-value">
                            <?php echo $stats['total_active']; ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_ongoing_rentals'); ?>
                                            </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-blue">
                        <i class="fa-solid fa-tractor"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_equipment'); ?>
                                            </span>

                        <h2 class="stat-value">
                            <?php echo $eq_stats['total_eq']; ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_rented_out'); ?>
                                            </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-orange">
                        <i class="fa-solid fa-clock"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_days'); ?>
                                            </span>

                        <h2 class="stat-value">
                            <?php echo $stats['total_days']; ?> <?php echo active_rental__('days'); ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_across_rentals'); ?>
                                            </span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon bg-purple">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>

                    <div class="stat-details">
                        <span class="stat-title">
                                                <?php echo active_rental__('stat_total_earned'); ?>
                                            </span>

                        <h2 class="stat-value">
                            ₹<?php echo number_format($stats['total_earned'], 2); ?>
                        </h2>

                        <span class="stat-desc">
                                                <?php echo active_rental__('stat_from_active'); ?>
                                            </span>
                    </div>
                </div>

            </div>

            <!-- Active Rentals Table Section -->
            <div class="table-card">

                <div class="table-responsive">

                    <table class="data-table">

                        <thead>
                            <tr>
                                <th>
                                                <?php echo active_rental__('table_col_equipment'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_renter'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_period'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_address'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_days_left'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_amount'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_status'); ?>
                                            </th>

                                <th>
                                                <?php echo active_rental__('table_col_action'); ?>
                                            </th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php

                            // Fetch active bookings matching this lender's equipment
                            $sql = "SELECT b.*, 
                                           e.title as eq_title,
                                           e.category as eq_cat,
                                           e.brand_model,
                                           e.image as eq_image,
                                           u.full_name as renter_name,
                                           u.phone as renter_phone
                                    FROM bookings b
                                    JOIN equipment e 
                                        ON b.equipment_id = e.equipment_id
                                    JOIN users u 
                                        ON b.renter_id = u.user_id
                                    WHERE e.lender_id = ?
                                    AND b.status IN ('Accepted', 'Delivered', 'Overdue', 'Returned')";

                            $stmt_bookings = $conn->prepare($sql);
                            $stmt_bookings->bind_param("i", $lender_id);
                            $stmt_bookings->execute();

                            $result = $stmt_bookings->get_result();

                            if ($result->num_rows > 0) {

                                while ($row = $result->fetch_assoc()) {

                                    // Delivery/return only become effective after renter confirmation.
                                    $current_date = new DateTime(date('Y-m-d'));
                                    $end_date = new DateTime($row['end_date']);
                                    $interval = $current_date->diff($end_date);
                                    $days_diff = (int)$interval->format('%r%d');
                                    $booking_status = $row['status'] ?? '';
                                    $delivery_confirmed = (int)($row['delivery_confirmed'] ?? 0) === 1;
                                    $return_confirmed = (int)($row['return_confirmed'] ?? 0) === 1;

                                    if ($booking_status === 'Returned' && !$return_confirmed) {
                                        $days_left_text = active_rental_status__('return_marked_lender');
                                        $sub_text = active_rental_status__('waiting_return_confirm');
                                        $status_class = 'status-due';
                                        $status_text = active_rental_status__('awaiting_return_confirm');
                                    } elseif ($booking_status === 'Returned' && $return_confirmed) {
                                        $days_left_text = active_rental_status__('rental_completed');
                                        $sub_text = active_rental_status__('renter_confirmed_return');
                                        $status_class = 'status-active';
                                        $status_text = active_rental_status__('completed');
                                    } elseif (!$delivery_confirmed && in_array($booking_status, ['Accepted', 'Delivered', 'Overdue'], true)) {
                                        $days_left_text = ($booking_status === 'Accepted')
                                            ? active_rental_status__('not_delivered')
                                            : active_rental_status__('delivered_confirmation_pending');
                                        $sub_text = ($booking_status === 'Accepted')
                                            ? active_rental_status__('waiting_lender_delivery')
                                            : active_rental_status__('waiting_renter_delivery');
                                        $status_class = 'status-active';
                                        $status_text = ($booking_status === 'Accepted')
                                            ? active_rental_status__('awaiting_delivery')
                                            : active_rental_status__('waiting_delivery_confirm');
                                    } elseif ($booking_status === 'Accepted') {
                                        $days_left_text = active_rental_status__('not_delivered');
                                        $sub_text = active_rental_status__('waiting_lender_delivery');
                                        $status_class = 'status-active';
                                        $status_text = active_rental_status__('awaiting_delivery');
                                    } elseif (in_array($booking_status, ['Delivered', 'Overdue'], true) && $delivery_confirmed && $days_diff < 0) {
                                        $days_left_text = active_rental_status__('overdue');
                                        $sub_text = active_rental_status__('return_by') . ' ' . date('d M Y', strtotime($row['end_date']));
                                        $status_class = 'status-overdue';
                                        $status_text = active_rental_status__('overdue');
                                    } elseif ($days_diff > 0) {
                                        $days_left_text = $days_diff . ' ' . active_rental__('days_left');
                                        $sub_text = active_rental_status__('return_by') . ' ' . date('d M Y', strtotime($row['end_date']));
                                        $status_class = 'status-active';
                                        $status_text = active_rental_status__('in_use');
                                    } elseif ($days_diff === 0) {
                                        $days_left_text = active_rental_status__('due_today');
                                        $sub_text = active_rental__('return_today');
                                        $status_class = 'status-due';
                                        $status_text = active_rental_status__('due_today');
                                    } else {
                                        // A past end date is not overdue unless delivery was confirmed.
                                        $days_left_text = active_rental_status__('awaiting_return');
                                        $sub_text = active_rental_status__('return_by') . ' ' . date('d M Y', strtotime($row['end_date']));
                                        $status_class = 'status-active';
                                        $status_text = active_rental_status__('in_use');
                                    }

                                    $per_day_price =
                                        ($row['total_days'] > 0)
                                        ? ($row['total_amount'] / $row['total_days'])
                                        : $row['total_amount'];
                            ?>

                            <tr>

                                <td>
                                    <div class="table-equipment-info">

                                        <?php $equipment_image_src = active_rental_image_src($row['eq_image'] ?? ''); ?>
                                        <div class="eq-thumb-wrap">
                                            <?php if ($equipment_image_src !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($equipment_image_src, ENT_QUOTES, 'UTF-8'); ?>"
                                                     alt="<?php echo htmlspecialchars(active_rental_status__('equipment_alt'), ENT_QUOTES, 'UTF-8'); ?>"
                                                     class="eq-thumb"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <?php endif; ?>
                                            <div class="eq-thumb-placeholder" <?php echo ($equipment_image_src !== '') ? 'style="display:none;"' : ''; ?>>
                                                <i class="fa-solid fa-tractor" aria-hidden="true"></i>
                                            </div>
                                        </div>

                                        <div>
                                            <strong>
                                                <?php echo htmlspecialchars($row['eq_title']); ?>
                                            </strong>

                                            <br>

                                            <span>
                                                <?php echo htmlspecialchars(active_rental_category_label($row['eq_cat'])); ?>
                                            </span>

                                            <br>

                                            <small>
                                                <?php echo htmlspecialchars($row['brand_model']); ?>
                                            </small>
                                        </div>

                                    </div>
                                </td>

                                <td>
                                    <div class="renter-info">

                                        <span>
                                            <i class="fa-regular fa-user"></i>
                                            <?php echo htmlspecialchars($row['renter_name']); ?>
                                        </span>

                                        <br>

                                        <small>
                                            <i class="fa-solid fa-phone"></i>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['phone_number'] ?? $row['renter_phone']
                                            );
                                            ?>
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <div class="rental-period">

                                        <span>
                                            <i class="fa-regular fa-calendar"></i>
                                            <?php
                                            echo date(
                                                'd M Y',
                                                strtotime($row['start_date'])
                                            );
                                            ?>
                                        </span>

                                        <br>

                                        <small>
                                            <?php echo active_rental_status__('to'); ?>
                                            <?php
                                            echo date(
                                                'd M Y',
                                                strtotime($row['end_date'])
                                            );
                                            ?>

                                            (<?php echo $row['total_days']; ?> <?php echo active_rental__('days'); ?>)
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <div class="delivery-address">

                                        <span>
                                            <i class="fa-solid fa-location-dot"></i>

                                            <?php
                                            echo htmlspecialchars(
                                                $row['delivery_address']
                                            );
                                            ?>
                                        </span>

                                    </div>
                                </td>

                                <td>
                                    <div class="days-left-cell">

                                        <strong>
                                            <?php echo $days_left_text; ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?php echo $sub_text; ?>
                                        </small>

                                    </div>
                                </td>

                                <td>
                                    <div class="amount-cell">

                                        <strong>
                                            ₹<?php
                                            echo number_format(
                                                $row['total_amount'],
                                                2
                                            );
                                            ?>
                                        </strong>

                                        <br>

                                        <small>
                                            (₹<?php
                                            echo number_format(
                                                $per_day_price,
                                                0
                                            );
                                            ?>/day)
                                        </small>

                                    </div>
                                </td>

                                <td>

                                    <span class="badge-status <?php echo $status_class; ?>">

                                        <i class="fa-solid fa-circle"></i>

                                        <?php echo $status_text; ?>

                                    </span>

                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <button
                                            class="btn-action btn-view"
                                            onclick="viewDetails(<?php echo $row['booking_id']; ?>)"
                                        >
                                            <i class="fa-regular fa-eye"></i>

                                            <span>
                                                <?php echo active_rental__('btn_view_details'); ?>
                                            </span>
                                        </button>


                                    </div>

                                </td>

                            </tr>

                            <?php
                                }

                            } else {

                                echo '<tr>
                                    <td
                                        colspan="8"
                                        style="text-align:center; padding:25px;"
                                    >' . htmlspecialchars(active_rental__('no_active_rentals')) . '</td>
                                </tr>';

                            }
                            ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

    </div>

    <script>
        function viewDetails(bookingId) {
            window.location.href =
                `lender_booking_details.php?booking_id=${bookingId}`;
        }


    </script>

</body>
</html>