<?php
session_start();

require_once 'includes/config.php';
require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = trim($_GET['q'] ?? '');

$current_lang = $current_lang ?? 'en';

$lang_param = '&lang=' . urlencode($current_lang);

/*
|--------------------------------------------------------------------------
| FETCH RENTER LOCATION
|--------------------------------------------------------------------------
*/
$user_stmt = $conn->prepare("
    SELECT
        full_name,
        email,
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

$user_address  = trim($user_data['address'] ?? 'Not Specified');
$user_city     = trim($user_data['city'] ?? '');
$user_district = trim($user_data['district'] ?? '');
$user_state    = trim($user_data['state'] ?? '');

$user_stmt->close();

$user_location_display = trim(
    $user_city .
    ($user_district !== '' ? ', ' . $user_district : '') .
    ($user_state !== '' ? ', ' . $user_state : '')
);

if ($user_location_display === '') {
    $user_location_display = $user_address;
}


/*
|--------------------------------------------------------------------------
| SECTION TITLES
|--------------------------------------------------------------------------
*/
$location_section_labels = [
    'en' => [
        'nearby' => 'Near Your Registered Location – Equipment Serviceable to You',
        'note' => '⭐ Equipment that is not serviceable to your location is not displayed.'
    ],
    'kn' => [
        'nearby' => 'ನಿಮ್ಮ ನೋಂದಾಯಿತ ಸ್ಥಳದ ಸಮೀಪ – ನಿಮಗೆ ಸೇವೆ ಲಭ್ಯವಿರುವ ಉಪಕರಣಗಳು',
        'note' => '⭐ ನಿಮ್ಮ ಸ್ಥಳಕ್ಕೆ ಸೇವೆ ಲಭ್ಯವಿಲ್ಲದ ಉಪಕರಣಗಳನ್ನು ಪ್ರದರ್ಶಿಸಲಾಗುವುದಿಲ್ಲ.'
    ],
    'hi' => [
        'nearby' => 'आपके पंजीकृत स्थान के पास – आपके लिए सेवा योग्य उपकरण',
        'note' => '⭐ जो उपकरण आपके स्थान के लिए सेवा योग्य नहीं हैं, वे प्रदर्शित नहीं किए जाते हैं।'
    ]
];

if (!isset($location_section_labels[$current_lang])) {
    $current_lang = 'en';
}


/*
|--------------------------------------------------------------------------
| CATEGORY FALLBACK
|--------------------------------------------------------------------------
*/
$valid_categories = [
    'Harvesting',
    'Tillage',
    'Seeding',
    'Spraying',
    'Irrigation',
    'Tractor'
];

$detected_fallback_category = '';
$search_mode = 'specific';


/*
|--------------------------------------------------------------------------
| ARRAYS
|--------------------------------------------------------------------------
*/
$raw_collected_items = [];

$same_city_equipment     = [];
$same_district_equipment = [];
$same_state_equipment    = [];
$other_equipment         = [];

$nearby_equipment = [];


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

/*
 * Normalize a location string.
 */
function normalize_location($value)
{
    $value = trim((string)$value);
    $value = preg_replace('/\s+/', ' ', $value);

    return mb_strtolower($value, 'UTF-8');
}


/*
 * Convert comma-separated service areas into clean individual values.
 *
 * Example:
 * "Mysuru, Hassan, Mandya"
 *
 * becomes:
 * ["Mysuru", "Hassan", "Mandya"]
 */
function get_service_area_list($service_location)
{
    if ($service_location === null || trim($service_location) === '') {
        return [];
    }

    $parts = preg_split('/[,;|]+/', $service_location);

    $areas = [];

    foreach ($parts as $part) {
        $part = trim($part);

        if ($part !== '') {
            $areas[] = normalize_location($part);
        }
    }

    return array_values(array_unique($areas));
}


/*
 * Check whether the lender's service area covers the renter.
 *
 * Priority:
 * 1. Renter city
 * 2. Renter district
 * 3. Renter state
 *
 * The lender's service_location is expected to contain
 * comma-separated service areas.
 */
function is_equipment_serviceable($row, $user_city, $user_district, $user_state)
{
    $service_location = trim($row['service_location'] ?? '');

    if ($service_location === '') {
        return false;
    }

    $service_areas = get_service_area_list($service_location);

    if (empty($service_areas)) {
        return false;
    }

    $user_city_normalized     = normalize_location($user_city);
    $user_district_normalized = normalize_location($user_district);
    $user_state_normalized    = normalize_location($user_state);

    foreach ($service_areas as $area) {

        /*
         * Exact city match.
         */
        if ($user_city_normalized !== '' &&
            $area === $user_city_normalized) {
            return true;
        }

        /*
         * Exact district match.
         */
        if ($user_district_normalized !== '' &&
            $area === $user_district_normalized) {
            return true;
        }

        /*
         * Exact state match.
         */
        if ($user_state_normalized !== '' &&
            $area === $user_state_normalized) {
            return true;
        }
    }

    return false;
}


/*
|--------------------------------------------------------------------------
| FETCH EQUIPMENT
|--------------------------------------------------------------------------
|
| We first fetch matching available equipment.
| Serviceability is checked in PHP because service_location is
| stored as a comma-separated value.
|
|--------------------------------------------------------------------------
*/

if (!empty($search_query)) {

    $like_term = "%" . $search_query . "%";


    /*
    |--------------------------------------------------------------------------
    | STEP A - FULL PHRASE SEARCH
    |--------------------------------------------------------------------------
    */

    $eq_sql = "
        SELECT
            e.*,

            COALESCE(r.avg_rating, 0) AS rating,
            COALESCE(r.review_count, 0) AS rating_count,

            COALESCE(l.city, '') AS lender_city,
            COALESCE(l.district, '') AS lender_district,
            COALESCE(l.state, '') AS lender_state

        FROM equipment e

        LEFT JOIN users l
            ON l.user_id = e.lender_id

        LEFT JOIN (
            SELECT
                equipment_id,
                AVG(rating) AS avg_rating,
                COUNT(*) AS review_count
            FROM reviews
            GROUP BY equipment_id
        ) r
            ON r.equipment_id = e.equipment_id

        WHERE e.status = 'Available'

        AND (
            e.title LIKE ?
            OR e.category LIKE ?
            OR e.brand_model LIKE ?
            OR e.description LIKE ?
        )

        ORDER BY e.equipment_id DESC
    ";

    $eq_stmt = $conn->prepare($eq_sql);

    if ($eq_stmt) {

        $eq_stmt->bind_param(
            "ssss",
            $like_term,
            $like_term,
            $like_term,
            $like_term
        );

        $eq_stmt->execute();

        $eq_result = $eq_stmt->get_result();

        while ($row = $eq_result->fetch_assoc()) {

            /*
             * Only include equipment that is serviceable
             * to the renter.
             */
            if (
                is_equipment_serviceable(
                    $row,
                    $user_city,
                    $user_district,
                    $user_state
                )
            ) {
                $raw_collected_items[] = $row;
            }
        }

        $eq_stmt->close();
    }


    /*
    |--------------------------------------------------------------------------
    | STEP B - WORD SEARCH
    |--------------------------------------------------------------------------
    */

    if (empty($raw_collected_items)) {

        $words = preg_split('/\s+/', $search_query);

        if (count($words) > 1) {

            $conditions = [];
            $types = '';
            $params = [];

            foreach ($words as $word) {

                $word = trim($word);

                if (mb_strlen($word) <= 2) {
                    continue;
                }

                $w_term = "%" . $word . "%";

                $conditions[] = "
                    (
                        e.title LIKE ?
                        OR e.category LIKE ?
                        OR e.brand_model LIKE ?
                        OR e.description LIKE ?
                    )
                ";

                $types .= "ssss";

                $params[] = $w_term;
                $params[] = $w_term;
                $params[] = $w_term;
                $params[] = $w_term;
            }

            if (!empty($conditions)) {

                $multi_sql = "
                    SELECT
                        e.*,

                        COALESCE(r.avg_rating, 0) AS rating,
                        COALESCE(r.review_count, 0) AS rating_count,

                        COALESCE(l.city, '') AS lender_city,
                        COALESCE(l.district, '') AS lender_district,
                        COALESCE(l.state, '') AS lender_state

                    FROM equipment e

                    LEFT JOIN users l
                        ON l.user_id = e.lender_id

                    LEFT JOIN (
                        SELECT
                            equipment_id,
                            AVG(rating) AS avg_rating,
                            COUNT(*) AS review_count
                        FROM reviews
                        GROUP BY equipment_id
                    ) r
                        ON r.equipment_id = e.equipment_id

                    WHERE e.status = 'Available'

                    AND (
                        " . implode(" OR ", $conditions) . "
                    )

                    ORDER BY e.equipment_id DESC
                ";

                $multi_stmt = $conn->prepare($multi_sql);

                if ($multi_stmt) {

                    $multi_stmt->bind_param($types, ...$params);

                    $multi_stmt->execute();

                    $multi_res = $multi_stmt->get_result();

                    while ($row = $multi_res->fetch_assoc()) {

                        if (
                            is_equipment_serviceable(
                                $row,
                                $user_city,
                                $user_district,
                                $user_state
                            )
                        ) {
                            $raw_collected_items[] = $row;
                        }
                    }

                    $multi_stmt->close();
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STEP C - CATEGORY FALLBACK
    |--------------------------------------------------------------------------
    */

    if (empty($raw_collected_items)) {

        $search_mode = 'fallback';

        $query_lower = mb_strtolower($search_query, 'UTF-8');

        foreach ($valid_categories as $cat) {

            if (
                stripos(
                    $query_lower,
                    mb_strtolower($cat, 'UTF-8')
                ) !== false
            ) {
                $detected_fallback_category = $cat;
                break;
            }
        }


        if (!empty($detected_fallback_category)) {

            $cat_sql = "
                SELECT
                    e.*,

                    COALESCE(r.avg_rating, 0) AS rating,
                    COALESCE(r.review_count, 0) AS rating_count,

                    COALESCE(l.city, '') AS lender_city,
                    COALESCE(l.district, '') AS lender_district,
                    COALESCE(l.state, '') AS lender_state

                FROM equipment e

                LEFT JOIN users l
                    ON l.user_id = e.lender_id

                LEFT JOIN (
                    SELECT
                        equipment_id,
                        AVG(rating) AS avg_rating,
                        COUNT(*) AS review_count
                    FROM reviews
                    GROUP BY equipment_id
                ) r
                    ON r.equipment_id = e.equipment_id

                WHERE e.status = 'Available'
                  AND e.category = ?

                ORDER BY e.equipment_id DESC
            ";

            $cat_stmt = $conn->prepare($cat_sql);

            if ($cat_stmt) {

                $cat_stmt->bind_param(
                    "s",
                    $detected_fallback_category
                );

                $cat_stmt->execute();

                $cat_result = $cat_stmt->get_result();

                while ($row = $cat_result->fetch_assoc()) {

                    if (
                        is_equipment_serviceable(
                            $row,
                            $user_city,
                            $user_district,
                            $user_state
                        )
                    ) {
                        $raw_collected_items[] = $row;
                    }
                }

                $cat_stmt->close();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STEP D - LOCATION PRIORITY
    |--------------------------------------------------------------------------
    |
    | 1 = Same City + District + State
    | 2 = Same District + State
    | 3 = Same State
    | 4 = Other serviceable location
    |
    */

    foreach ($raw_collected_items as $row) {

        $lender_city = trim($row['lender_city'] ?? '');
        $lender_district = trim($row['lender_district'] ?? '');
        $lender_state = trim($row['lender_state'] ?? '');

        $city_match =
            $user_city !== '' &&
            strcasecmp($lender_city, $user_city) === 0;

        $district_match =
            $user_district !== '' &&
            strcasecmp($lender_district, $user_district) === 0;

        $state_match =
            $user_state !== '' &&
            strcasecmp($lender_state, $user_state) === 0;


        /*
         * Same city + district + state
         */
        if (
            $city_match &&
            $district_match &&
            $state_match
        ) {

            $row['location_priority'] = 1;

            $same_city_equipment[] = $row;
        }

        /*
         * Same district + state
         */
        elseif (
            $district_match &&
            $state_match
        ) {

            $row['location_priority'] = 2;

            $same_district_equipment[] = $row;
        }

        /*
         * Same state
         */
        elseif ($state_match) {

            $row['location_priority'] = 3;

            $same_state_equipment[] = $row;
        }

        /*
         * Other serviceable location
         */
        else {

            $row['location_priority'] = 4;

            $other_equipment[] = $row;
        }
    }


    /*
     * Compatibility variable.
     */
    $nearby_equipment = array_merge(
        $same_city_equipment,
        $same_district_equipment,
        $same_state_equipment
    );
}


/*
|--------------------------------------------------------------------------
| EQUIPMENT CARD HELPER
|--------------------------------------------------------------------------
*/

function display_equipment_card($eq, $current_lang, $lang_param)
{
    $img_path = !empty($eq['image'])
        ? 'uploads/' . $eq['image']
        : '';

    $has_valid_img =
        !empty($eq['image']) &&
        file_exists(__DIR__ . '/' . $img_path);

    $equipment_location = trim(
        ($eq['lender_city'] ?? '') .
        (!empty($eq['lender_district'])
            ? ', ' . $eq['lender_district']
            : '') .
        (!empty($eq['lender_state'])
            ? ', ' . $eq['lender_state']
            : '')
    );

    if ($equipment_location === '') {
        $equipment_location = $eq['service_location'] ?? '';
    }
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
                if (!empty($eq['brand_model'])) {
                    echo ' | ' .
                        htmlspecialchars($eq['brand_model']);
                }
                ?>

            </div>


            <div class="meta-text text-danger fw-semibold">

                <i class="fa-solid fa-location-dot me-1"></i>

                <?php echo htmlspecialchars($equipment_location); ?>

            </div>


            <div class="meta-text text-warning">

                <i class="fa-solid fa-star"></i>

                <?php echo number_format(
                    $eq['rating'] ?? 0,
                    1
                ); ?>

                (<?php echo intval(
                    $eq['rating_count'] ?? 0
                ); ?>)

            </div>


            <div class="price-tag">

                ₹<?php echo number_format(
                    $eq['price_per_day'],
                    2
                ); ?>

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
                href="equipment_details.php?id=<?php
                    echo $eq['equipment_id'];
                ?><?php
                    echo $lang_param;
                ?>"
                class="btn-view"
            >
                <?php echo __('view_equipment'); ?>
            </a>


            <a
                href="rent_now.php?id=<?php
                    echo $eq['equipment_id'];
                ?><?php
                    echo $lang_param;
                ?>"
                class="btn-rent-now"
            >

                <i class="fa-solid fa-calendar-check me-1"></i>

                <?php
                echo !empty($current_lang) && $current_lang === 'kn'
                    ? 'ಈಗ ಬಾಡಿಗೆಗೆ'
                    : (
                        $current_lang === 'hi'
                            ? 'अभी किराए पर लें'
                            : 'Rent Now'
                    );
                ?>

            </a>

        </div>

    </div>

    <?php
}
?>

<!DOCTYPE html>

<html lang="<?php echo htmlspecialchars($current_lang); ?>">

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

    
.serviceability-note {
    margin: 8px 0 18px;
    color: #6c757d;
    font-size: 0.9rem;
}
</style>

</head>


<body>


<?php include 'renter_sidebar.php'; ?>


<div class="main-content">


    <!-- SEARCH BAR -->

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


            <select
                name="lang"
                class="form-select w-auto"
                onchange="this.form.submit()"
            >

                <option
                    value="en"
                    <?php echo $current_lang === 'en'
                        ? 'selected'
                        : ''; ?>
                >
                    English
                </option>

                <option
                    value="hi"
                    <?php echo $current_lang === 'hi'
                        ? 'selected'
                        : ''; ?>
                >
                    हिंदी (Hindi)
                </option>

                <option
                    value="kn"
                    <?php echo $current_lang === 'kn'
                        ? 'selected'
                        : ''; ?>
                >
                    ಕನ್ನಡ (Kannada)
                </option>

            </select>


            <button
                type="submit"
                class="btn text-white px-4"
                style="background-color:#198754;"
            >
                <?php echo __('search'); ?>
            </button>

        </form>

    </div>


    <!-- HEADER -->

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
                <?php echo htmlspecialchars(
                    $user_location_display
                ); ?>
            </strong>

        </p>


        <?php if (
            $search_mode === 'fallback' &&
            !empty($detected_fallback_category)
        ): ?>

            <div
                class="alert alert-warning py-2 px-3 mt-2 mb-0 d-inline-flex align-items-center gap-2"
                style="font-size:14px;"
            >

                <i class="fa-solid fa-circle-info text-warning"></i>

                <span>

                    <?php echo __('specific_item_not_available'); ?>

                    <strong>
                        <?php echo htmlspecialchars(
                            $detected_fallback_category
                        ); ?>
                    </strong>.

                </span>

            </div>

        <?php endif; ?>

    </div>


    <!-- EMPTY QUERY -->

    <?php if (empty($search_query)): ?>

        <div class="empty-state">

            <i class="fa-solid fa-keyboard"></i>

            <h5>
                <?php echo __('enter_keyword_prompt'); ?>
            </h5>

        </div>


    <!-- NOTHING FOUND -->

    <?php elseif (
        empty($same_city_equipment) &&
        empty($same_district_equipment) &&
        empty($same_state_equipment) &&
        empty($other_equipment)
    ): ?>

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
                href="renter_dashboard.php?lang=<?php echo urlencode($current_lang); ?>"
                class="btn btn-outline-secondary mt-2"
            >

                <?php echo __('back_to_dashboard'); ?>

            </a>

        </div>


    <?php else: ?>


        <!-- NEAR YOUR REGISTERED LOCATION -->

        <?php if (!empty($nearby_equipment)): ?>

            <div class="section-title">

                <i class="fa-solid fa-location-dot text-danger"></i>

                <?php echo htmlspecialchars(
                    $location_section_labels[$current_lang]['nearby']
                ); ?>

            </div>

            <div class="serviceability-note">

                <?php echo htmlspecialchars(
                    $location_section_labels[$current_lang]['note']
                ); ?>

            </div>

            <div class="equipment-grid">

                <?php foreach ($nearby_equipment as $eq): ?>

                    <?php
                    display_equipment_card(
                        $eq,
                        $current_lang,
                        $lang_param
                    );
                    ?>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- OTHER EQUIPMENT -->

        <?php if (!empty($other_equipment)): ?>

            <div class="section-title">

                <i class="fa-solid fa-globe text-secondary"></i>

                <?php echo __('other_equipment'); ?>

            </div>

            <div class="equipment-grid">

                <?php foreach ($other_equipment as $eq): ?>

                    <?php
                    display_equipment_card(
                        $eq,
                        $current_lang,
                        $lang_param
                    );
                    ?>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


    <?php endif; ?>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>