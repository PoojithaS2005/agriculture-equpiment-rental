<?php
session_start();

// Include existing configuration and language files
require_once 'includes/config.php';
require_once 'includes/lang.php';

// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = trim($_GET['q'] ?? '');

// Preserve active language query parameter if present
$lang_param = isset($_GET['lang']) ? '&lang=' . urlencode($_GET['lang']) : '';

// 1. Fetch Renter's registered city/district/state
$user_stmt = $conn->prepare("
    SELECT full_name, email,
           COALESCE(address, 'Not Specified') AS address,
           COALESCE(city, '') AS city,
           COALESCE(district, '') AS district,
           COALESCE(state, '') AS state
    FROM users
    WHERE user_id = ?
");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc() ?: [];
$user_address = $user_data['address'] ?? 'Not Specified';
$user_city = trim($user_data['city'] ?? '');
$user_district = trim($user_data['district'] ?? '');
$user_state = trim($user_data['state'] ?? '');
$user_stmt->close();

$user_location_display = trim($user_city . ($user_district !== '' ? ', ' . $user_district : '') . ($user_state !== '' ? ', ' . $user_state : ''));
$location_section_labels = [
    'en' => ['city' => 'Equipment in Your City', 'district' => 'Equipment in Your District', 'state' => 'Equipment in Your State'],
    'kn' => ['city' => 'ನಿಮ್ಮ ನಗರದ ಉಪಕರಣಗಳು', 'district' => 'ನಿಮ್ಮ ಜಿಲ್ಲೆಯ ಉಪಕರಣಗಳು', 'state' => 'ನಿಮ್ಮ ರಾಜ್ಯದ ಉಪಕರಣಗಳು'],
    'hi' => ['city' => 'आपके शहर के उपकरण', 'district' => 'आपके जिले के उपकरण', 'state' => 'आपके राज्य के उपकरण']
];

if ($user_location_display === '') {
    $user_location_display = $user_address;
}

$valid_categories = ['Harvesting', 'Tillage', 'Seeding', 'Spraying', 'Irrigation', 'Tractor'];
$detected_fallback_category = '';
$raw_collected_items = [];
$search_mode = 'specific';

if (!empty($search_query)) {

    $like_term = "%" . $search_query . "%";

    // Location priority:
    // 1 = same city + same state
    // 2 = same state, different city
    // 3 = other states / unknown
    $location_priority_sql = "
        CASE
            WHEN TRIM(LOWER(COALESCE(l.city, ''))) = TRIM(LOWER(?))
             AND TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
             AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 1
            WHEN TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
             AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 2
            WHEN TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 3
            ELSE 4
        END
    ";

    // Step A: Full phrase search
    $eq_sql = "
        SELECT e.*,
               COALESCE(r.avg_rating, 0) AS rating,
               COALESCE(r.review_count, 0) AS rating_count,
               COALESCE(l.city, '') AS lender_city,
               COALESCE(l.district, '') AS lender_district,
               COALESCE(l.state, '') AS lender_state,
               ($location_priority_sql) AS location_priority
        FROM equipment e
        LEFT JOIN users l ON l.user_id = e.lender_id
        LEFT JOIN (
            SELECT equipment_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
            FROM reviews
            GROUP BY equipment_id
        ) r ON r.equipment_id = e.equipment_id
        WHERE e.status = 'Available'
          AND (
              e.title LIKE ?
              OR e.category LIKE ?
              OR e.brand_model LIKE ?
              OR e.description LIKE ?
          )
        ORDER BY location_priority ASC, e.equipment_id DESC
    ";
    $eq_sql = "
        SELECT e.*,
               COALESCE(r.avg_rating, 0) AS rating,
               COALESCE(r.review_count, 0) AS rating_count,
               COALESCE(l.city, '') AS lender_city,
               COALESCE(l.district, '') AS lender_district,
               COALESCE(l.state, '') AS lender_state,
               CASE
                   WHEN TRIM(LOWER(COALESCE(l.city, ''))) = TRIM(LOWER(?))
                    AND TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
                    AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 1
                   WHEN TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
                    AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 2
                   WHEN TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 3
                   ELSE 4
               END AS location_priority
        FROM equipment e
        LEFT JOIN users l ON l.user_id = e.lender_id
        LEFT JOIN (
            SELECT equipment_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
            FROM reviews
            GROUP BY equipment_id
        ) r ON r.equipment_id = e.equipment_id
        WHERE e.status = 'Available'
          AND (e.title LIKE ? OR e.category LIKE ? OR e.brand_model LIKE ? OR e.description LIKE ?)
        ORDER BY location_priority ASC, e.equipment_id DESC
    ";
    $eq_stmt = $conn->prepare($eq_sql);
    $eq_stmt->bind_param(
        "ssssssssss",
        $user_city, $user_district, $user_state, $user_district, $user_state, $user_state, $like_term, $like_term, $like_term, $like_term
    );
    $eq_stmt->execute();
    $eq_result = $eq_stmt->get_result();
    while ($row = $eq_result->fetch_assoc()) {
        $raw_collected_items[] = $row;
    }
    $eq_stmt->close();

    // Step B: word search
    if (empty($raw_collected_items)) {
        $words = explode(' ', $search_query);
        if (count($words) > 1) {
            $conditions = [];
            $types = "ssssss";
            $params = [$user_city, $user_district, $user_state, $user_district, $user_state, $user_state];

            foreach ($words as $word) {
                if (strlen(trim($word)) > 2) {
                    $w_term = "%" . trim($word) . "%";
                    $conditions[] = "(e.title LIKE ? OR e.category LIKE ? OR e.brand_model LIKE ? OR e.description LIKE ?)";
                    $types .= "ssss";
                    array_push($params, $w_term, $w_term, $w_term, $w_term);
                }
            }

            if (!empty($conditions)) {
                $multi_sql = "
                    SELECT e.*,
                           COALESCE(r.avg_rating, 0) AS rating,
                           COALESCE(r.review_count, 0) AS rating_count,
                           COALESCE(l.city, '') AS lender_city,
                           COALESCE(l.district, '') AS lender_district,
                           COALESCE(l.state, '') AS lender_state,
                           CASE
                               WHEN TRIM(LOWER(COALESCE(l.city, ''))) = TRIM(LOWER(?))
                                AND TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
                                AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 1
                               WHEN TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
                                AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 2
                               WHEN TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 3
                               ELSE 4
                           END AS location_priority
                    FROM equipment e
                    LEFT JOIN users l ON l.user_id = e.lender_id
                    LEFT JOIN (
                        SELECT equipment_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                        FROM reviews
                        GROUP BY equipment_id
                    ) r ON r.equipment_id = e.equipment_id
                    WHERE e.status = 'Available'
                      AND (" . implode(' OR ', $conditions) . ")
                    ORDER BY location_priority ASC, e.equipment_id DESC
                ";
                $multi_stmt = $conn->prepare($multi_sql);
                $multi_stmt->bind_param($types, ...$params);
                $multi_stmt->execute();
                $multi_res = $multi_stmt->get_result();
                while ($row = $multi_res->fetch_assoc()) {
                    $raw_collected_items[] = $row;
                }
                $multi_stmt->close();
            }
        }
    }

    // Step C: category fallback
    if (empty($raw_collected_items)) {
        $search_mode = 'fallback';
        $query_lower = mb_strtolower($search_query);
        foreach ($valid_categories as $cat) {
            if (stripos($query_lower, mb_strtolower($cat)) !== false) {
                $detected_fallback_category = $cat;
                break;
            }
        }

        if (!empty($detected_fallback_category)) {
            $cat_sql = "
                SELECT e.*,
                       COALESCE(r.avg_rating, 0) AS rating,
                       COALESCE(r.review_count, 0) AS rating_count,
                       COALESCE(l.city, '') AS lender_city,
                       COALESCE(l.district, '') AS lender_district,
                       COALESCE(l.state, '') AS lender_state,
                       CASE
                           WHEN TRIM(LOWER(COALESCE(l.city, ''))) = TRIM(LOWER(?))
                            AND TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
                            AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 1
                           WHEN TRIM(LOWER(COALESCE(l.district, ''))) = TRIM(LOWER(?))
                            AND TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 2
                           WHEN TRIM(LOWER(COALESCE(l.state, ''))) = TRIM(LOWER(?)) THEN 3
                           ELSE 4
                       END AS location_priority
                FROM equipment e
                LEFT JOIN users l ON l.user_id = e.lender_id
                LEFT JOIN (
                    SELECT equipment_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                    FROM reviews
                    GROUP BY equipment_id
                ) r ON r.equipment_id = e.equipment_id
                WHERE e.status = 'Available' AND e.category = ?
                ORDER BY location_priority ASC, e.equipment_id DESC
            ";
            $cat_stmt = $conn->prepare($cat_sql);
            $cat_stmt->bind_param("sssssss",
                $user_city, $user_district, $user_state, $user_district, $user_state, $user_state,
                $detected_fallback_category
            );
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();
            while ($row = $cat_result->fetch_assoc()) {
                $raw_collected_items[] = $row;
            }
            $cat_stmt->close();
        }
    }

    // Step D: Split into same-city, same-district, same-state, and other locations
    $same_city_equipment = [];
    $same_district_equipment = [];
    $same_state_equipment = [];
    $other_equipment = [];

    foreach ($raw_collected_items as $row) {
        $lender_city = trim($row['lender_city'] ?? '');
        $lender_district = trim($row['lender_district'] ?? '');
        $lender_state = trim($row['lender_state'] ?? '');

        if ($user_city !== '' && $user_district !== '' && $user_state !== ''
            && strcasecmp($lender_city, $user_city) === 0
            && strcasecmp($lender_district, $user_district) === 0
            && strcasecmp($lender_state, $user_state) === 0) {
            $same_city_equipment[] = $row;
        } elseif ($user_district !== '' && $user_state !== ''
            && strcasecmp($lender_district, $user_district) === 0
            && strcasecmp($lender_state, $user_state) === 0) {
            $same_district_equipment[] = $row;
        } elseif ($user_state !== '' && strcasecmp($lender_state, $user_state) === 0) {
            $same_state_equipment[] = $row;
        } else {
            $other_equipment[] = $row;
        }
    }

    // Keep the old variable name for empty-state compatibility.
    $nearby_equipment = array_merge($same_city_equipment, $same_district_equipment, $same_state_equipment);
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">

<head>

    <meta charset="UTF-8">

    <title>
        <?php echo __('search_results_for'); ?>:
        <?php echo htmlspecialchars($search_query); ?>
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

        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
            display: flex;
            color: #333;
            margin: 0;
        }

        /* Main content starts after the common renter sidebar */
        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            flex: 1;
        }

        .top-search-bar {
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 30px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .equipment-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }

        .equipment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .card-img-container {
            height: 180px;
            width: 100%;
            background: #f1f5f9;
            position: relative;
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-body-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .equipment-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 0;
        }

        .meta-text {
            font-size: 13px;
            color: #64748b;
        }

        .price-tag {
            font-size: 16px;
            font-weight: bold;
            color: #198754;
            margin-top: auto;
        }

        .card-footer-actions {
            padding: 12px 15px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 8px;
        }

        .btn-view {
            background: #6c757d;
            color: #fff;
            flex: 1;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            padding: 8px 4px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view:hover {
            background: #5c636a;
            color: #fff;
        }

        .btn-rent-now {
            background: #198754;
            color: #fff;
            flex: 1;
            font-weight: 600;
            font-size: 12px;
            border-radius: 6px;
            padding: 8px 4px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-rent-now:hover {
            background: #157347;
            color: #fff;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
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

    </style>

</head>

<body>

    <!-- Common Renter Sidebar -->
    <?php include 'renter_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Search Bar with Language Selector -->
        <div class="top-search-bar">

            <form
                action="search_equipment.php"
                method="GET"
                class="d-flex w-100 gap-2 align-items-center"
            >

                <div class="input-group">

                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>

                    <input
                        type="text"
                        name="q"
                        id="searchInput"
                        class="form-control border-start-0"
                        value="<?php echo htmlspecialchars($search_query); ?>"
                        placeholder="<?php echo __('search_placeholder'); ?>"
                        required
                    >

                </div>

                <!-- Language Dropdown Menu -->
                <select
                    name="lang"
                    class="form-select w-auto"
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

                <button
                    type="submit"
                    class="btn text-white px-4"
                    style="background-color: #198754;"
                >
                    <?php echo __('search'); ?>
                </button>

            </form>

        </div>

        <!-- Search Header & Fallback Notice -->
        <div class="mb-4">

            <h4>
                <?php echo __('search_results_for'); ?>:
                <span class="text-success">
                    "<?php echo htmlspecialchars($search_query); ?>"
                </span>
            </h4>

            <p class="text-muted mb-1">
                <i class="fa-solid fa-location-dot me-1 text-danger"></i>
                <?php echo __('registered_location'); ?>:
                <strong>
                    <?php echo htmlspecialchars($user_location_display); ?>
                </strong>
            </p>

            <?php if ($search_mode === 'fallback' && !empty($detected_fallback_category)): ?>

                <div
                    class="alert alert-warning py-2 px-3 mt-2 mb-0 d-inline-flex align-items-center gap-2"
                    style="font-size: 14px;"
                >

                    <i class="fa-solid fa-circle-info text-warning"></i>

                    <span>
                        <?php echo __('specific_item_not_available'); ?>
                        <strong>
                            <?php echo htmlspecialchars($detected_fallback_category); ?>
                        </strong>.
                    </span>

                </div>

            <?php endif; ?>

        </div>

        <?php if (empty($search_query)): ?>

            <div class="empty-state">

                <i class="fa-solid fa-keyboard"></i>

                <h5>
                    <?php echo __('enter_keyword_prompt'); ?>
                </h5>

            </div>

        <?php elseif (empty($nearby_equipment) && empty($other_equipment)): ?>

            <div class="empty-state">

                <i class="fa-solid fa-box-open"></i>

                <h5>
                    <?php echo __('no_equipment_found'); ?>
                    "<?php echo htmlspecialchars($search_query); ?>"
                </h5>

                <p class="text-muted">
                    <?php echo __('try_different_keyword'); ?>
                </p>

                <a
                    href="renter_dashboard.php<?php echo !empty($lang_param) ? '?lang=' . urlencode($current_lang) : ''; ?>"
                    class="btn btn-outline-secondary mt-2"
                >
                    <?php echo __('back_to_dashboard'); ?>
                </a>

            </div>

        <?php else: ?>

            <!-- SECTION 1: Equipment Near You -->
            <?php if (!empty($same_city_equipment)): ?>

                <div class="section-title">

                    <i class="fa-solid fa-map-pin text-danger"></i>

                    <?php echo htmlspecialchars($location_section_labels[$current_lang]['city']); ?>

                    (<?php echo htmlspecialchars($user_city); ?>)

                </div>

                <div class="equipment-grid">

                    <?php foreach ($same_city_equipment as $eq): ?>

                        <?php
                        $img_path = !empty($eq['image'])
                            ? 'uploads/' . $eq['image']
                            : '';

                        $has_valid_img = !empty($eq['image'])
                            && file_exists(__DIR__ . '/' . $img_path);
                        ?>

                        <div class="equipment-card">

                            <div class="card-img-container">

                                <?php if ($has_valid_img): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($img_path); ?>"
                                        alt="Equipment Image"
                                    >

                                <?php else: ?>

                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">

                                        <i class="fa-solid fa-tractor fa-2x"></i>

                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="card-body-content">

                                <div class="d-flex justify-content-between align-items-start">

                                    <h5 class="equipment-title">
                                        <?php echo htmlspecialchars($eq['title']); ?>
                                    </h5>

                                    <span
                                        class="badge bg-success"
                                        style="font-size: 10px;"
                                    >
                                        <?php echo __('available_status_label'); ?>
                                    </span>

                                </div>

                                <div class="meta-text">

                                    <i class="fa-solid fa-tag me-1"></i>

                                    <?php echo htmlspecialchars($eq['category']); ?>

                                    <?php
                                    echo !empty($eq['brand_model'])
                                        ? ' | ' . htmlspecialchars($eq['brand_model'])
                                        : '';
                                    ?>

                                </div>

                                <div class="meta-text text-danger fw-semibold">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?php echo htmlspecialchars(trim(($eq['lender_city'] ?? '') . (!empty($eq['lender_district']) ? ', ' . $eq['lender_district'] : '') . (!empty($eq['lender_state']) ? ', ' . $eq['lender_state'] : '')) ?: ($eq['service_location'] ?? '')); ?>
                                </div>

                                <div class="meta-text text-warning">

                                    <i class="fa-solid fa-star"></i>

                                    <?php echo number_format($eq['rating'] ?? 0, 1); ?>

                                    (<?php echo intval($eq['rating_count'] ?? 0); ?>)

                                </div>

                                <div class="price-tag">

                                    ₹<?php echo number_format($eq['price_per_day'], 2); ?>

                                    <small
                                        class="text-muted fw-normal"
                                        style="font-size: 11px;"
                                    >
                                        <?php echo __('per_day'); ?>
                                    </small>

                                </div>

                            </div>

                            <div class="card-footer-actions">

                                <a
                                    href="equipment_details.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>"
                                    class="btn-view"
                                >
                                    <?php echo __('view_equipment'); ?>
                                </a>

                                <a
                                    href="rent_now.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>"
                                    class="btn-rent-now"
                                >
                                    <i class="fa-solid fa-calendar-check me-1"></i>
                                    Rent Now
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <!-- SECTION 2: Equipment in Your State -->
            <?php if (!empty($same_district_equipment)): ?>
                <div class="section-title">
                    <i class="fa-solid fa-map-location-dot text-primary"></i>
                    <?php echo htmlspecialchars($location_section_labels[$current_lang]['district']); ?> (<?php echo htmlspecialchars($user_district); ?>)
                </div>
                <div class="equipment-grid">
                    <?php foreach ($same_district_equipment as $eq): ?>
                        <?php
                        $img_path = !empty($eq['image']) ? 'uploads/' . $eq['image'] : '';
                        $has_valid_img = !empty($eq['image']) && file_exists(__DIR__ . '/' . $img_path);
                        ?>
                        <div class="equipment-card">
                            <div class="card-img-container">
                                <?php if ($has_valid_img): ?>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Equipment Image">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                        <i class="fa-solid fa-tractor fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="equipment-title"><?php echo htmlspecialchars($eq['title']); ?></h5>
                                    <span class="badge bg-success" style="font-size: 10px;"><?php echo __('available_status_label'); ?></span>
                                </div>
                                <div class="meta-text"><i class="fa-solid fa-tag me-1"></i><?php echo htmlspecialchars($eq['category']); ?><?php echo !empty($eq['brand_model']) ? ' | ' . htmlspecialchars($eq['brand_model']) : ''; ?></div>
                                <div class="meta-text text-danger fw-semibold">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?php echo htmlspecialchars(trim(($eq['lender_city'] ?? '') . (!empty($eq['lender_district']) ? ', ' . $eq['lender_district'] : '') . (!empty($eq['lender_state']) ? ', ' . $eq['lender_state'] : '')) ?: ($eq['service_location'] ?? '')); ?>
                                </div>
                                <div class="meta-text text-warning"><i class="fa-solid fa-star"></i> <?php echo number_format($eq['rating'] ?? 0, 1); ?> (<?php echo intval($eq['rating_count'] ?? 0); ?>)</div>
                                <div class="price-tag">₹<?php echo number_format($eq['price_per_day'], 2); ?> <small class="text-muted fw-normal" style="font-size: 11px;"><?php echo __('per_day'); ?></small></div>
                            </div>
                            <div class="card-footer-actions">
                                <a href="equipment_details.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>" class="btn-view"><?php echo __('view_equipment'); ?></a>
                                <a href="rent_now.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>" class="btn-rent-now"><i class="fa-solid fa-calendar-check me-1"></i>Rent Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 3: Other Equipment -->

            <?php if (!empty($same_state_equipment)): ?>
                <div class="section-title">
                    <i class="fa-solid fa-map-location-dot text-primary"></i>
                    <?php echo htmlspecialchars($location_section_labels[$current_lang]['state']); ?> (<?php echo htmlspecialchars($user_state); ?>)
                </div>
                <div class="equipment-grid">
                    <?php foreach ($same_state_equipment as $eq): ?>
                        <?php
                        $img_path = !empty($eq['image']) ? 'uploads/' . $eq['image'] : '';
                        $has_valid_img = !empty($eq['image']) && file_exists(__DIR__ . '/' . $img_path);
                        ?>
                        <div class="equipment-card">
                            <div class="card-img-container">
                                <?php if ($has_valid_img): ?>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Equipment Image">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                        <i class="fa-solid fa-tractor fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="equipment-title"><?php echo htmlspecialchars($eq['title']); ?></h5>
                                    <span class="badge bg-success" style="font-size: 10px;"><?php echo __('available_status_label'); ?></span>
                                </div>
                                <div class="meta-text"><i class="fa-solid fa-tag me-1"></i><?php echo htmlspecialchars($eq['category']); ?><?php echo !empty($eq['brand_model']) ? ' | ' . htmlspecialchars($eq['brand_model']) : ''; ?></div>
                                <div class="meta-text text-danger fw-semibold">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    <?php echo htmlspecialchars(trim(($eq['lender_city'] ?? '') . (!empty($eq['lender_district']) ? ', ' . $eq['lender_district'] : '') . (!empty($eq['lender_state']) ? ', ' . $eq['lender_state'] : '')) ?: ($eq['service_location'] ?? '')); ?>
                                </div>
                                <div class="meta-text text-warning"><i class="fa-solid fa-star"></i> <?php echo number_format($eq['rating'] ?? 0, 1); ?> (<?php echo intval($eq['rating_count'] ?? 0); ?>)</div>
                                <div class="price-tag">₹<?php echo number_format($eq['price_per_day'], 2); ?> <small class="text-muted fw-normal" style="font-size: 11px;"><?php echo __('per_day'); ?></small></div>
                            </div>
                            <div class="card-footer-actions">
                                <a href="equipment_details.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>" class="btn-view"><?php echo __('view_equipment'); ?></a>
                                <a href="rent_now.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>" class="btn-rent-now"><i class="fa-solid fa-calendar-check me-1"></i>Rent Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 3: Other Equipment -->
            <?php if (!empty($other_equipment)): ?>

                <div class="section-title">

                    <i class="fa-solid fa-globe text-secondary"></i>

                    <?php echo __('other_equipment'); ?>

                </div>

                <div class="equipment-grid">

                    <?php foreach ($other_equipment as $eq): ?>

                        <?php
                        $img_path = !empty($eq['image'])
                            ? 'uploads/' . $eq['image']
                            : '';

                        $has_valid_img = !empty($eq['image'])
                            && file_exists(__DIR__ . '/' . $img_path);
                        ?>

                        <div class="equipment-card">

                            <div class="card-img-container">

                                <?php if ($has_valid_img): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($img_path); ?>"
                                        alt="Equipment Image"
                                    >

                                <?php else: ?>

                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">

                                        <i class="fa-solid fa-tractor fa-2x"></i>

                                    </div>

                                <?php endif; ?>

                            </div>

                            <div class="card-body-content">

                                <div class="d-flex justify-content-between align-items-start">

                                    <h5 class="equipment-title">
                                        <?php echo htmlspecialchars($eq['title']); ?>
                                    </h5>

                                    <span
                                        class="badge bg-success"
                                        style="font-size: 10px;"
                                    >
                                        <?php echo __('available_status_label'); ?>
                                    </span>

                                </div>

                                <div class="meta-text">

                                    <i class="fa-solid fa-tag me-1"></i>

                                    <?php echo htmlspecialchars($eq['category']); ?>

                                    <?php
                                    echo !empty($eq['brand_model'])
                                        ? ' | ' . htmlspecialchars($eq['brand_model'])
                                        : '';
                                    ?>

                                </div>

                                <div class="meta-text">

                                    <i class="fa-solid fa-location-dot me-1"></i>

                                    <?php echo htmlspecialchars(trim(($eq['lender_city'] ?? '') . (!empty($eq['lender_district']) ? ', ' . $eq['lender_district'] : '') . (!empty($eq['lender_state']) ? ', ' . $eq['lender_state'] : '')) ?: ($eq['service_location'] ?? '')); ?>

                                </div>

                                <div class="meta-text text-warning">

                                    <i class="fa-solid fa-star"></i>

                                    <?php echo number_format($eq['rating'] ?? 0, 1); ?>

                                    (<?php echo intval($eq['rating_count'] ?? 0); ?>)

                                </div>

                                <div class="price-tag">

                                    ₹<?php echo number_format($eq['price_per_day'], 2); ?>

                                    <small
                                        class="text-muted fw-normal"
                                        style="font-size: 11px;"
                                    >
                                        <?php echo __('per_day'); ?>
                                    </small>

                                </div>

                            </div>

                            <div class="card-footer-actions">

                                <a
                                    href="equipment_details.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>"
                                    class="btn-view"
                                >
                                    <?php echo __('view_equipment'); ?>
                                </a>

                                <a
                                    href="rent_now.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param) ? '&lang=' . urlencode($current_lang) : ''; ?>"
                                    class="btn-rent-now"
                                >
                                    <i class="fa-solid fa-calendar-check me-1"></i>
                                    Rent Now
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>