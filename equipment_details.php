
<?php
session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','hi','kn'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';
require_once 'includes/config.php';

$current_lang = $_SESSION['lang'] ?? 'en';

// Translate common equipment values stored in the database.
// Custom titles, brands, models and descriptions are left unchanged unless a known translation exists.
function translate_equipment_detail_value($value, $type = 'general') {
    $value = trim((string)$value);
    if ($value === '' || $value === 'N/A') {
        return $value;
    }

    $key = strtolower($value);
    $maps = [
        'category' => [
            'seeding' => ['seeding', 'ಬಿತ್ತನೆ', 'बुवाई'],
            'tractor' => ['tractor', 'ಟ್ರಾಕ್ಟರ್', 'ट्रैक्टर'],
            'harvesting' => ['harvesting', 'ಕೊಯ್ಲು', 'कटाई'],
            'irrigation' => ['irrigation', 'ನೀರಾವರಿ', 'सिंचाई'],
            'tillage' => ['tillage', 'ಉಳುಮೆ', 'जुताई'],
            'spraying' => ['spraying', 'ಸಿಂಪಡಣೆ', 'छिड़काव'],
        ],
        'condition' => [
            'good' => ['condition_good_value', 'condition_good_value', 'condition_good_value'],
            'excellent' => ['condition_excellent_value', 'condition_excellent_value', 'condition_excellent_value'],
            'fair' => ['condition_fair_value', 'condition_fair_value', 'condition_fair_value'],
            'poor' => ['condition_poor_value', 'condition_poor_value', 'condition_poor_value'],
        ],
        'status' => [
            'available' => ['available_value', 'available_value', 'available_value'],
            'rented' => ['rented_value', 'rented_value', 'rented_value'],
            'not available' => ['not_available_value', 'not_available_value', 'not_available_value'],
        ],
        'drive' => [
            'manual' => ['Manual', 'ಕೈಚಾಲಿತ', 'मैनुअल'],
            'automatic' => ['Automatic', 'ಸ್ವಯಂಚಾಲಿತ', 'स्वचालित'],
            'four wheel drive' => ['Four Wheel Drive', 'ನಾಲ್ಕು ಚಕ್ರ ಚಾಲನೆ', 'चार पहिया ड्राइव'],
            '2wd' => ['2WD', '2WD', '2WD'],
            '4wd' => ['4WD', '4WD', '4WD'],
        ],
        
          'fuel' => [
    'diesel' => ['Diesel', 'ಡೀಸೆಲ್', 'डीजल'],
    'petrol' => ['Petrol', 'ಪೆಟ್ರೋಲ್', 'पेट्रोल'],
    'electric' => ['Electric', 'ವಿದ್ಯುತ್', 'इलेक्ट्रिक'],
    'manual' => ['Manual', 'ಕೈಚಾಲಿತ', 'मैनुअल']

        ],
        'location' => [
            'bagalkot' => 'district_bagalkot',
            'bagalkote' => 'district_bagalkot',
            'ballari' => 'district_ballari',
            'bellary' => 'district_ballari',
            'belagavi' => 'district_belagavi',
            'belgaum' => 'district_belagavi',
            'bengaluru rural' => 'district_bengaluru_rural',
            'bangalore rural' => 'district_bengaluru_rural',
            'bengaluru urban' => 'district_bengaluru_urban',
            'bangalore urban' => 'district_bengaluru_urban',
            'bengaluru' => 'district_bengaluru_urban',
            'bangalore' => 'district_bengaluru_urban',
            'bidar' => 'district_bidar',
            'chamarajanagar' => 'district_chamarajanagar',
            'chamrajnagar' => 'district_chamarajanagar',
            'chikkaballapur' => 'district_chikkaballapur',
            'chikballapur' => 'district_chikkaballapur',
            'chikkamagaluru' => 'district_chikkamagaluru',
            'chikmagalur' => 'district_chikkamagaluru',
            'chitradurga' => 'district_chitradurga',
            'dakshina kannada' => 'district_dakshina_kannada',
            'south kanara' => 'district_dakshina_kannada',
            'davanagere' => 'district_davanagere',
            'davangere' => 'district_davanagere',
            'dharwad' => 'district_dharwad',
            'gadag' => 'district_gadag',
            'hassan' => 'district_hassan',
            'haveri' => 'district_haveri',
            'kalaburagi' => 'district_kalaburagi',
            'gulbarga' => 'district_kalaburagi',
            'kodagu' => 'district_kodagu',
            'coorg' => 'district_kodagu',
            'kolar' => 'district_kolar',
            'koppal' => 'district_koppal',
            'mandya' => 'district_mandya',
            'mysuru' => 'district_mysuru',
            'mysore' => 'district_mysuru',
            'raichur' => 'district_raichur',
            'ramanagara' => 'district_ramanagara',
            'ramnagaram' => 'district_ramanagara',
            'shivamogga' => 'district_shivamogga',
            'shimoga' => 'district_shivamogga',
            'tumakuru' => 'district_tumakuru',
            'tumkur' => 'district_tumakuru',
            'udupi' => 'district_udupi',
            'uttara kannada' => 'district_uttara_kannada',
            'north kanara' => 'district_uttara_kannada',
            'vijayapura' => 'district_vijayapura',
            'bijapur' => 'district_vijayapura',
            'yadgir' => 'district_yadgir',
            'yadagiri' => 'district_yadgir',
            'vijayanagara' => 'district_vijayanagara',
            'vijayanagaram' => 'district_vijayanagara',
        ],
        'brand_model' => [
            'manual agricultural seed drill' => ['Manual Agricultural Seed Drill', 'ಕೈಚಾಲಿತ ಕೃಷಿ ಬೀಜ ಬಿತ್ತನೆ ಯಂತ್ರ', 'मैनुअल कृषि बीज ड्रिल'],
        ],
        'title' => [
            'manual seed drill' => ['Manual Seed Drill', 'ಮ್ಯಾನುಯಲ್ ಸೀಡ್ ಡ್ರಿಲ್', 'मैनुअल सीड ड्रिल'],
            'maize seed planter' => ['Maize Seed Planter', 'ಮೆಕ್ಕೆಜೋಳ ಬೀಜ ಬಿತ್ತನೆ ಯಂತ್ರ', 'मक्का बीज बोने की मशीन'],
            'multi crop planter' => ['Multi Crop Planter', 'ಬಹು ಬೆಳೆ ಪ್ಲಾಂಟರ್', 'बहु-फसल प्लांटर'],
            'seed drill machine' => ['Seed Drill Machine', 'ಬೀಜ ಬಿತ್ತನೆ ಯಂತ್ರ', 'बीज ड्रिल मशीन'],
        ],
        'description' => [
            'manually operated seed drill for sowing seeds in small agricultural plots.' =>
                ['Manually operated seed drill for sowing seeds in small agricultural plots.', 'ಸಣ್ಣ ಕೃಷಿ ಜಮೀನುಗಳಲ್ಲಿ ಬೀಜ ಬಿತ್ತಲು ಕೈಯಿಂದ ಕಾರ್ಯನಿರ್ವಹಿಸುವ ಬೀಜ ಬಿತ್ತನೆ ಯಂತ್ರ.', 'छोटे कृषि खेतों में बीज बोने के लिए हाथ से चलने वाली बीज ड्रिल मशीन।'],
        ],
    ];

    $lang_index = ['en' => 0, 'kn' => 1, 'hi' => 2];
    $lang = $GLOBALS['current_lang'] ?? 'en';

    if ($type === 'location') {
        $parts = preg_split('/\s*,\s*/u', $value);
        $translated = [];
        foreach ($parts as $part) {
            $part_key = strtolower(trim($part));
            if (isset($maps['location'][$part_key])) {
                $translated[] = __($maps['location'][$part_key]);
            } else {
                $translated[] = trim($part);
            }
        }
        return implode(', ', $translated);
    }

    if (isset($maps[$type][$key])) {
        $entry = $maps[$type][$key];
        if ($type === 'condition' || $type === 'status') {
            return __($entry[$lang_index[$lang] ?? 0]);
        }
        return $entry[$lang_index[$lang] ?? 0];
    }

    return $value;
}


// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0);

// Fetch Equipment Details using exact columns
$stmt = $conn->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'renter') {
        header("Location: renter_dashboard.php");
    } else {
        header("Location: my_equipment.php");
    }
    exit();
}

$eq = $result->fetch_assoc();
$is_owner = ($eq['lender_id'] == $user_id);

// Use live review data
$review_avg = 0.0;
$review_count = 0;

$rating_stmt = $conn->prepare("
    SELECT COALESCE(AVG(rating),0) AS avg_rating,
           COUNT(*) AS review_count
    FROM reviews
    WHERE equipment_id = ?
");

if ($rating_stmt) {
    $rating_stmt->bind_param('i', $equipment_id);
    $rating_stmt->execute();

    $rating_data = $rating_stmt->get_result()->fetch_assoc();

    $review_avg = (float)($rating_data['avg_rating'] ?? 0);
    $review_count = (int)($rating_data['review_count'] ?? 0);

    $rating_stmt->close();
}

$eq['rating'] = $review_avg;
$eq['rating_count'] = $review_count;

$customer_reviews = [];

$reviews_stmt = $conn->prepare("
    SELECT
        rv.rating,
        rv.review_text,
        rv.created_at,
        u.full_name AS renter_name,
        b.request_code
    FROM reviews rv
    LEFT JOIN users u ON rv.renter_id = u.user_id
    LEFT JOIN bookings b ON rv.booking_id = b.booking_id
    WHERE rv.equipment_id = ?
    ORDER BY rv.created_at DESC
");

if ($reviews_stmt) {
    $reviews_stmt->bind_param('i', $equipment_id);
    $reviews_stmt->execute();

    $rr = $reviews_stmt->get_result();

    while ($r = $rr->fetch_assoc()) {
        $customer_reviews[] = $r;
    }

    $reviews_stmt->close();
}

$from = $_GET['from'] ?? '';

if ($from === 'recommended') {

    $back_page = 'recommended.php?lang=' . urlencode($current_lang);

    $back_text = ($current_lang === 'kn')
        ? 'ಶಿಫಾರಸುಗಳಿಗೆ ಹಿಂತಿರುಗಿ'
        : (($current_lang === 'hi')
            ? 'अनुशंसित पर वापस जाएं'
            : 'Back to Recommended');

} elseif ($from === 'search_equipment') {

    $search_q = $_GET['q'] ?? '';

    $back_page = 'search_equipment.php?q=' . urlencode($search_q) . '&lang=' . urlencode($current_lang);

    $back_text = ($current_lang === 'kn')
        ? 'ಉಪಕರಣ ಹುಡುಕಾಟಕ್ಕೆ ಹಿಂತಿರುಗಿ'
        : (($current_lang === 'hi')
            ? 'उपकरण खोज पर वापस जाएं'
            : 'Back to Search Equipment');

} elseif ($from === 'category_items') {

    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    $back_page = 'category_items.php?category_id=' . $category_id . '&lang=' . urlencode($current_lang);

    $back_text = ($current_lang === 'kn')
        ? 'ವರ್ಗಕ್ಕೆ ಹಿಂತಿರುಗಿ'
        : (($current_lang === 'hi')
            ? 'श्रेणी पर वापस जाएं'
            : 'Back to Category');

} elseif ($from === 'my_equipment') {

    $back_page = 'my_equipment.php?lang=' . urlencode($current_lang);

    $back_text = ($current_lang === 'kn')
        ? 'ನನ್ನ ಉಪಕರಣಗಳಿಗೆ ಹಿಂತಿರುಗಿ'
        : (($current_lang === 'hi')
            ? 'मेरे उपकरण पर वापस जाएं'
            : 'Back to My Equipment');

} else {

    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'renter') {

        $back_page = 'renter_dashboard.php?lang=' . urlencode($current_lang);

        $back_text = ($current_lang === 'kn')
            ? 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ಗೆ ಹಿಂತಿರುಗಿ'
            : (($current_lang === 'hi')
                ? 'डैशबोर्ड पर वापस जाएं'
                : 'Back to Dashboard');

    } else {

        $back_page = 'my_equipment.php?lang=' . urlencode($current_lang);

        $back_text = ($current_lang === 'kn')
            ? 'ನನ್ನ ಉಪಕರಣಗಳಿಗೆ ಹಿಂತಿರುಗಿ'
            : (($current_lang === 'hi')
                ? 'मेरे उपकरण पर वापस जाएं'
                : 'Back to My Equipment');
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($eq['title'] ?? __('equipment_details_title')); ?>
        - Agriculture Equipment Rental
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

        /* =========================================
           BLUEFIELD LENDING - BLUE GLASSMORPHISM
        ========================================= */

        :root {
            --navy-primary: #0B3478;
            --navy-dark: #082B65;
            --blue-hover: #164C9A;

            --blue-light: #D9E9FC;
            --blue-soft: #EAF3FF;
            --blue-accent: #8DB8F0;

            --text-dark: #10194A;
            --text-muted: #425D80;

            --glass-white: rgba(255,255,255,0.68);
            --glass-light: rgba(255,255,255,0.78);

            --glass-border: rgba(255,255,255,0.85);

            --glass-shadow:
                0 12px 30px rgba(11,52,120,0.12),
                inset 0 1px 0 rgba(255,255,255,0.9);
        }

        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html {
            min-height: 100%;
        }

        body {
            background: linear-gradient(
                135deg,
                #DCEBFC 0%,
                #EAF3FF 45%,
                #C8DDF7 100%
            ) !important;

            color: var(--text-dark) !important;

            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        /* MAIN DETAILS CONTAINER */

        .details-container {
            max-width: 900px;
            margin: 0 auto;

            background: var(--glass-white) !important;

            border: 1px solid var(--glass-border) !important;

            border-radius: 20px !important;

            overflow: hidden;

            box-shadow: var(--glass-shadow) !important;

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* TOP BLUE BANNER */

        .top-banner {
            background: linear-gradient(
                115deg,
                #0B3478,
                #082B65
            ) !important;

            color: #ffffff !important;

            padding: 18px 22px;

            font-weight: bold;
            font-size: 18px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 12px;
            flex-wrap: wrap;
        }

        .top-banner i {
            color: #A9CBFF;
        }

        /* LANGUAGE SELECT */

        .lang-select {
            padding: 7px 11px;

            border-radius: 9px;

            border: 1px solid rgba(255,255,255,0.45);

            font-size: 13px;

            outline: none;

            background: rgba(255,255,255,0.90);

            color: #082B65;

            cursor: pointer;
        }

        /* IMAGE PREVIEW */

        .img-preview-container {
            height: 350px;
            width: 100%;

            background: rgba(217,233,252,0.65);

            position: relative;

            border-bottom: 1px solid rgba(255,255,255,0.85);

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }

        .img-preview-container img {
            width: 95%;
            height: 100%;
            object-fit: cover;

            border-radius: 12px;
        }

        /* CONTENT */

        .content-body {
            padding: 30px;
        }

        .equipment-title {
            font-size: 26px;
            font-weight: bold;

            color: #10194A !important;

            margin-bottom: 20px;
        }

        /* SPECIFICATION GRID */

        .specs-grid {
            display: grid;

            grid-template-columns: repeat(
                auto-fit,
                minmax(250px, 1fr)
            );

            gap: 15px;
            margin-bottom: 25px;
        }

        /* GLASS SPECIFICATION CARDS */

        .spec-box {
            background: rgba(255,255,255,0.66) !important;

            border: 1px solid rgba(255,255,255,0.9) !important;

            padding: 14px 15px;

            border-radius: 14px !important;

            box-shadow:
                0 6px 18px rgba(11,52,120,0.07),
                inset 0 1px 0 rgba(255,255,255,0.9);

            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);

            transition: all 0.25s ease;
        }

        .spec-box:hover {
            transform: translateY(-3px);

            background: rgba(255,255,255,0.85) !important;

            box-shadow:
                0 10px 24px rgba(11,52,120,0.12);
        }

        .spec-label {
            font-size: 11px;

            text-transform: uppercase;

            color: #52749E !important;

            font-weight: bold;

            letter-spacing: 0.5px;

            margin-bottom: 5px;
        }

        .spec-value {
            font-size: 15px;

            font-weight: 600;

            color: #10194A !important;

            overflow-wrap: anywhere;
        }

        /* PRICE */

        .price-highlight {
            color: #0B3478 !important;

            font-size: 18px;

            font-weight: bold;
        }

        /* STATUS BADGES */

        .badge-status {
            padding: 5px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            display: inline-block;
        }

        .status-available {
            background: #DCFCE7;
            color: #166534;
        }

        .status-rented {
            background: #DBEAFE;
            color: #1E40AF;
        }

        /* REVIEWS SECTION */

        .reviews-section {
            margin-top: 25px;

            padding-top: 25px;

            border-top: 1px solid rgba(11,52,120,0.14);
        }

        .reviews-section h3 {
            color: #10194A !important;
        }

        .review-item {
            background: rgba(255,255,255,0.72) !important;

            border: 1px solid rgba(255,255,255,0.9) !important;

            border-radius: 14px !important;

            padding: 16px;

            margin-top: 12px;

            box-shadow:
                0 6px 18px rgba(11,52,120,0.07);

            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);

            transition: all 0.25s ease;
        }

        .review-item:hover {
            background: rgba(255,255,255,0.9) !important;

            transform: translateY(-2px);
        }

        .review-stars {
            color: #F59E0B;

            letter-spacing: 1px;

            margin-top: 4px;
        }

        .reviewer-name {
            font-weight: 800;

            color: #10194A !important;
        }

        .review-date {
            font-size: 12px;

            color: #52749E !important;
        }

        .review-text {
            margin-top: 7px;

            color: #334F75 !important;

            line-height: 1.5;

            white-space: pre-wrap;
        }

        /* FOOTER */

        .action-footer {
            padding: 16px 30px;

            background: rgba(217,233,252,0.65) !important;

            border-top: 1px solid rgba(255,255,255,0.85);

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 12px;
            flex-wrap: wrap;
        }

        /* BUTTONS */

        .btn-primary,
        .btn-success {
            background: linear-gradient(
                135deg,
                #164C9A,
                #0B3478
            ) !important;

            border: none !important;

            color: #ffffff !important;

            border-radius: 10px !important;

            box-shadow: 0 5px 14px rgba(11,52,120,0.18);
        }

        .btn-primary:hover,
        .btn-success:hover {
            background: #082B65 !important;

            color: #ffffff !important;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.75) !important;

            color: #0B3478 !important;

            border: 1px solid rgba(11,52,120,0.25) !important;

            border-radius: 10px !important;

            font-weight: 600;
        }

        .btn-secondary:hover {
            background: #0B3478 !important;

            color: #ffffff !important;
        }

        .btn-warning {
            background: linear-gradient(
                135deg,
                #FBBF24,
                #F59E0B
            ) !important;

            color: #422006 !important;

            border: none !important;

            border-radius: 10px !important;
        }

        .btn-warning:hover {
            background: #D97706 !important;

            color: #ffffff !important;
        }

        .top-banner .btn-light {
            background: rgba(255,255,255,0.92) !important;

            color: #082B65 !important;

            border: 1px solid rgba(255,255,255,0.7);

            border-radius: 9px;
        }

        .top-banner .btn-light:hover {
            background: #DCEBFC !important;
        }

        /* TEXT COLORS */

        .text-muted {
            color: #52749E !important;
        }

        .text-warning {
            color: #D99A12 !important;
        }

        /* DESCRIPTION */

        .content-body > .spec-box {
            margin-top: 5px;
        }

        /* RESPONSIVE DESIGN */

        @media (max-width: 768px) {

            body {
                padding: 12px;
            }

            .details-container {
                border-radius: 16px !important;
            }

            .top-banner {
                padding: 15px;

                font-size: 16px;

                align-items: flex-start;
            }

            .content-body {
                padding: 20px;
            }

            .img-preview-container {
                height: 260px;
            }

            .equipment-title {
                font-size: 23px;
            }

            .specs-grid {
                grid-template-columns: repeat(
                    auto-fit,
                    minmax(200px, 1fr)
                );
            }

            .action-footer {
                padding: 15px 20px;
            }
        }

        @media (max-width: 480px) {

            body {
                padding: 8px;
            }

            .top-banner {
                flex-direction: column;
            }

            .top-banner .d-flex {
                width: 100%;
                flex-wrap: wrap;
            }

            .content-body {
                padding: 15px;
            }

            .img-preview-container {
                height: 220px;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .equipment-title {
                font-size: 21px;
            }

            .action-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .action-footer .btn {
                width: 100%;
            }

            .review-item {
                padding: 12px;
            }
        }

    </style>

</head>

<body>

    <div class="details-container">

        <div class="top-banner">

            <span>
                <i class="fa-solid fa-circle-info me-2"></i>
                <?php echo __('equipment_details_title'); ?>
            </span>

            <div class="d-flex align-items-center gap-2">

                <select
                    class="lang-select text-dark"
                    onchange="location = this.value;"
                >

                    <option
                        value="equipment_details.php?id=<?php echo $equipment_id; ?>&from=<?php echo urlencode($from); ?>&q=<?php echo urlencode($_GET['q'] ?? ''); ?>&category_id=<?php echo intval($_GET['category_id'] ?? 0); ?>&lang=en"
                        <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>
                    >
                        🌐 English
                    </option>

                    <option
                        value="equipment_details.php?id=<?php echo $equipment_id; ?>&from=<?php echo urlencode($from); ?>&q=<?php echo urlencode($_GET['q'] ?? ''); ?>&category_id=<?php echo intval($_GET['category_id'] ?? 0); ?>&lang=hi"
                        <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>
                    >
                        🌐 हिन्दी
                    </option>

                    <option
                        value="equipment_details.php?id=<?php echo $equipment_id; ?>&from=<?php echo urlencode($from); ?>&q=<?php echo urlencode($_GET['q'] ?? ''); ?>&category_id=<?php echo intval($_GET['category_id'] ?? 0); ?>&lang=kn"
                        <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>
                    >
                        🌐 ಕನ್ನಡ
                    </option>

                </select>

                <a
                    href="<?php echo htmlspecialchars($back_page); ?>"
                    class="btn btn-sm btn-light fw-bold text-dark"
                >
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    <?php echo htmlspecialchars($back_text); ?>
                </a>

            </div>

        </div>

        <div class="img-preview-container">

            <?php

                $raw_img = trim($eq['image'] ?? '');

                $img_path = '';

                $has_valid_img = false;

                if (!empty($raw_img)) {

                    if (file_exists(__DIR__ . '/uploads/' . $raw_img)) {

                        $img_path = 'uploads/' . $raw_img;
                        $has_valid_img = true;

                    } elseif (file_exists(__DIR__ . '/' . $raw_img)) {

                        $img_path = $raw_img;
                        $has_valid_img = true;

                    } elseif (file_exists(__DIR__ . '/uploads/equipment/' . $raw_img)) {

                        $img_path = 'uploads/equipment/' . $raw_img;
                        $has_valid_img = true;

                    } elseif (preg_match('/^https?:\/\//i', $raw_img)) {

                        $img_path = $raw_img;
                        $has_valid_img = true;
                    }
                }

            ?>

            <?php if ($has_valid_img): ?>

                <img
                    src="<?php echo htmlspecialchars($img_path); ?>"
                    alt="<?php echo htmlspecialchars($eq['title'] ?? 'Equipment Image'); ?>"
                >

            <?php else: ?>

                <div class="d-flex align-items-center justify-content-center h-100 text-muted">

                    <i class="fa-solid fa-image fa-3x text-secondary opacity-50"></i>

                </div>

            <?php endif; ?>

        </div>

        <div class="content-body">

            <h1 class="equipment-title">
                <?php echo htmlspecialchars(translate_equipment_detail_value($eq['title'] ?? __('unnamed_equipment'), 'title'), ENT_QUOTES, 'UTF-8'); ?>
            </h1>

            <div class="specs-grid">

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('category_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars(translate_equipment_detail_value($eq['category'] ?? 'N/A', 'category'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('brand_model_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars(translate_equipment_detail_value($eq['brand_model'] ?? 'N/A', 'brand_model'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('power_hp_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['power_hp'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('drive_type_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars(translate_equipment_detail_value($eq['drive_type'] ?? 'N/A', 'drive'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('model_year_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['model_year'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('fuel_type_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars(translate_equipment_detail_value($eq['fuel_type'] ?? 'N/A', 'fuel'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('working_hours'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['working_hours'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('equipment_condition_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars(translate_equipment_detail_value($eq['equipment_condition'] ?? 'N/A', 'condition'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('price_per_day_spec'); ?></div>
                    <div class="spec-value price-highlight">
                        ₹<?php echo number_format($eq['price_per_day'] ?? 0, 2); ?>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('quantity_available_label'); ?></div>
                    <div class="spec-value">
                        <?php $detail_quantity = (int)($eq['total_quantity'] ?? 1); echo $detail_quantity; ?> <?php echo ($detail_quantity === 1) ? __('unit_label_detail') : __('units_label_detail'); ?>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('min_booking_days_spec'); ?></div>
                    <div class="spec-value">
                        <?php echo htmlspecialchars($eq['min_booking_days'] ?? 1); ?>
                        <?php echo __('days_label'); ?>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('security_deposit_label'); ?></div>
                    <div class="spec-value">
                        ₹<?php echo number_format((float)($eq['security_deposit'] ?? 0), 2); ?>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('maximum_booking_days_label'); ?></div>
                    <div class="spec-value">
                        <?php echo htmlspecialchars((string)($eq['max_booking_days'] ?? 'N/A')); ?>
                        <?php echo isset($eq['max_booking_days']) && $eq['max_booking_days'] !== '' ? __('days_label') : ''; ?>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('service_location_spec'); ?></div>
                    <div class="spec-value">

                        <?php echo htmlspecialchars(translate_equipment_detail_value($eq['service_location'] ?? 'N/A', 'location'), ENT_QUOTES, 'UTF-8'); ?>

                        <?php if (!empty($eq['distance_km'])): ?>

                            <small class="text-muted">
                                (<?php echo htmlspecialchars($eq['distance_km']); ?> <?php echo __('distance_unit'); ?>)
                            </small>

                        <?php endif; ?>

                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('status_featured_spec'); ?></div>
                    <div class="spec-value">

                        <span class="badge-status <?php echo (strtolower($eq['status'] ?? '') === 'rented') ? 'status-rented' : 'status-available'; ?>">

                            <?php echo htmlspecialchars(translate_equipment_detail_value($eq['status'] ?? 'Available', 'status'), ENT_QUOTES, 'UTF-8'); ?>

                        </span>

                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('rating_spec'); ?></div>

                    <div class="spec-value text-warning">

                        <i class="fa-solid fa-star"></i>

                        <?php echo number_format($eq['rating'] ?? 0, 1); ?> / 5

                        <span class="text-muted fs-6 fw-normal">
                            (<?php echo intval($eq['rating_count'] ?? 0); ?>
                            <?php echo __('reviews_label'); ?>)
                        </span>

                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('created_date_spec'); ?></div>
                    <div class="spec-value fs-6">
                        <?php echo htmlspecialchars($eq['created_at'] ?? 'N/A'); ?>
                    </div>
                </div>

            </div>

            <div class="spec-box w-100">

                <div class="spec-label">
                    <?php echo __('description_spec'); ?>
                </div>

                <div class="spec-value fw-normal text-muted mt-1">
                    <?php echo nl2br(htmlspecialchars(translate_equipment_detail_value($eq['description'] ?? __('no_description_provided'), 'description'), ENT_QUOTES, 'UTF-8')); ?>
                </div>

            </div>

            <!-- CUSTOMER REVIEWS -->

            <div class="reviews-section">

                <h3 class="h5 fw-bold mb-1">

                    <i class="fa-solid fa-star text-warning me-2"></i>

                    <?php echo __('customer_reviews'); ?>

                </h3>

                <div class="text-muted fw-semibold mb-3">

                    <?php echo number_format($eq['rating'],1); ?>/5
                    ·
                    <?php echo $review_count; ?>
                    <?php echo __('reviews_label'); ?>

                </div>

                <?php if (empty($customer_reviews)): ?>

                    <div class="text-muted fw-semibold">
                        <?php echo __('no_reviews_yet'); ?>
                    </div>

                <?php else: ?>

                    <?php foreach ($customer_reviews as $cr): ?>

                        <div class="review-item">

                            <div class="d-flex justify-content-between align-items-start gap-3">

                                <div>

                                    <div class="reviewer-name">
                                        <?php echo htmlspecialchars($cr['renter_name'] ?? 'Customer'); ?>
                                    </div>

                                    <div class="review-stars">

                                        <?php echo str_repeat('★',(int)$cr['rating']); ?>

                                        <span style="color:#B8CBE3">
                                            <?php echo str_repeat('★',5-(int)$cr['rating']); ?>
                                        </span>

                                    </div>

                                </div>

                                <div class="review-date">

                                    <?php echo htmlspecialchars(date('d M Y', strtotime($cr['created_at']))); ?>

                                </div>

                            </div>

                            <div class="review-text">

                                <?php echo nl2br(htmlspecialchars($cr['review_text'])); ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

        <!-- ACTION FOOTER -->

        <div class="action-footer">

            <a
                href="<?php echo htmlspecialchars($back_page); ?>"
                class="btn btn-secondary btn-sm px-4"
            >

                <i class="fa-solid fa-arrow-left me-1"></i>

                <?php echo htmlspecialchars($back_text); ?>

            </a>

            <?php if ($is_owner): ?>

                <a
                    href="edit_equipment.php?id=<?php echo (int)$eq['equipment_id']; ?>"
                    class="btn btn-warning btn-sm px-4 fw-bold"
                >

                    <i class="fa-solid fa-pen-to-square me-1"></i>

                    <?php echo __('edit_equipment_btn'); ?>

                </a>

            <?php endif; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
