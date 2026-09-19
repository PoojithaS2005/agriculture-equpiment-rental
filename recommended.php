<?php

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$allowed_languages = ['en', 'kn', 'hi'];

if (isset($_GET['lang'])) {
    $selected_language = $_GET['lang'];

    if (in_array($selected_language, $allowed_languages, true)) {
        $_SESSION['lang'] = $selected_language;
        $_SESSION['language'] = $selected_language;
    }

    header("Location: recommended.php");
    exit;
}

$current_lang = $_SESSION['lang'] ?? 'en';

if (!in_array($current_lang, $allowed_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

/*
 * Wishlist action
 */
if (
    isset($_POST['wishlist_action']) &&
    isset($_POST['equipment_id'])
) {
    $equipment_id = (int) $_POST['equipment_id'];
    $wishlist_action = $_POST['wishlist_action'];

    if ($equipment_id > 0) {

        if ($wishlist_action === 'add') {

            $wishlist_sql = "
                INSERT IGNORE INTO wishlist
                (
                    user_id,
                    equipment_id
                )
                VALUES
                (
                    ?,
                    ?
                )
            ";

            $wishlist_stmt = mysqli_prepare(
                $conn,
                $wishlist_sql
            );

            if ($wishlist_stmt) {
                mysqli_stmt_bind_param(
                    $wishlist_stmt,
                    "ii",
                    $user_id,
                    $equipment_id
                );

                mysqli_stmt_execute($wishlist_stmt);
                mysqli_stmt_close($wishlist_stmt);
            }

        } elseif ($wishlist_action === 'remove') {

            $wishlist_sql = "
                DELETE FROM wishlist
                WHERE user_id = ?
                AND equipment_id = ?
            ";

            $wishlist_stmt = mysqli_prepare(
                $conn,
                $wishlist_sql
            );

            if ($wishlist_stmt) {
                mysqli_stmt_bind_param(
                    $wishlist_stmt,
                    "ii",
                    $user_id,
                    $equipment_id
                );

                mysqli_stmt_execute($wishlist_stmt);
                mysqli_stmt_close($wishlist_stmt);
            }
        }
    }

    header("Location: recommended.php");
    exit;
}

/*
 * User information
 */
$user_name = $_SESSION['name']
    ?? $_SESSION['full_name']
    ?? 'Renter';

$user_location = $_SESSION['location']
    ?? 'Hassan, Karnataka';

$user_sql = "
    SELECT *
    FROM users
    WHERE user_id = ?
";

$user_stmt = mysqli_prepare(
    $conn,
    $user_sql
);

if ($user_stmt) {

    mysqli_stmt_bind_param(
        $user_stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($user_stmt);

    $user_result = mysqli_stmt_get_result($user_stmt);

    if ($user = mysqli_fetch_assoc($user_result)) {

        if (!empty($user['full_name'])) {
            $user_name = $user['full_name'];
        }

        if (
            isset($user['location']) &&
            !empty($user['location'])
        ) {
            $user_location = $user['location'];

        } elseif (
            isset($user['address']) &&
            !empty($user['address'])
        ) {
            $user_location = $user['address'];

        } elseif (
            isset($user['city']) &&
            !empty($user['city'])
        ) {
            $user_location = $user['city'];
        }
    }

    mysqli_stmt_close($user_stmt);
}

if (empty($user_location)) {
    $user_location = __('location_not_set');
}

$renter_initial = 'R';

if (!empty($user_name)) {
    $renter_initial = mb_strtoupper(
        mb_substr(
            trim($user_name),
            0,
            1,
            'UTF-8'
        ),
        'UTF-8'
    );
}

/*
 * Wishlist IDs
 */
$wishlist_ids = [];

$wishlist_check_sql = "
    SELECT equipment_id
    FROM wishlist
    WHERE user_id = ?
";

$wishlist_check_stmt = mysqli_prepare(
    $conn,
    $wishlist_check_sql
);

if ($wishlist_check_stmt) {

    mysqli_stmt_bind_param(
        $wishlist_check_stmt,
        "i",
        $user_id
    );

    mysqli_stmt_execute($wishlist_check_stmt);

    $wishlist_result = mysqli_stmt_get_result(
        $wishlist_check_stmt
    );

    while (
        $wishlist_row = mysqli_fetch_assoc(
            $wishlist_result
        )
    ) {
        $wishlist_ids[] = (int) $wishlist_row['equipment_id'];
    }

    mysqli_stmt_close($wishlist_check_stmt);
}

/*
 * Read recommended equipment from the real equipment table.
 */
$sql = "
    SELECT
        e.*,
        re.recommendation_id
    FROM equipment e
    LEFT JOIN recommended_equipment re
        ON re.equipment_id = e.equipment_id
    WHERE
        e.status = 'Available'
        AND (
            e.badge = 'RECOMMENDED'
            OR re.equipment_id IS NOT NULL
        )
    ORDER BY e.created_at DESC, e.equipment_id DESC
";

$result = mysqli_query(
    $conn,
    $sql
);

if (!$result) {
    die(
        "Database Error: " .
        mysqli_error($conn)
    );
}

function equipmentName($title)
{
    $title = trim($title);

    $map = [
        'Tractors' => 'equipment_tractors',
        'Tractor' => 'category_tractor',
        'Harvesting' => 'equipment_harvesting',
        'Irrigation' => 'equipment_irrigation',
        'Tillage' => 'equipment_tillage',
        'Seed Drill' => 'equipment_seed_drill',
        'Sprayer' => 'equipment_sprayer',
        'Rotavator' => 'equipment_rotavator',
        'Irrigation Pump' => 'equipment_irrigation_pump',
        'Combine Harvester' => 'equipment_combine_harvester'
    ];

    if (isset($map[$title])) {
        return __($map[$title]);
    }

    return $title;
}

function categoryName($category)
{
    $category = trim($category);

    $map = [
        'Tractor' => 'category_tractor',
        'Tractors' => 'equipment_tractors',
        'Harvesting' => 'category_harvesting',
        'Irrigation' => 'category_irrigation',
        'Tillage' => 'category_tillage',
        'Seeding' => 'category_seeding',
        'Spraying' => 'category_spraying'
    ];

    if (isset($map[$category])) {
        return __($map[$category]);
    }

    return $category;
}

function getImagePath($image)
{
    if (empty($image)) {
        return '';
    }

    $image = str_replace(
        '\\',
        '/',
        trim($image)
    );

    if (
        strpos($image, 'http://') === 0 ||
        strpos($image, 'https://') === 0
    ) {
        return $image;
    }

    $project_path =
        str_replace(
            '\\',
            '/',
            __DIR__
        ) . '/';

    if (
        strpos(
            $image,
            'C:/xampp/htdocs/'
        ) === 0
    ) {
        $image = substr(
            $image,
            strlen('C:/xampp/htdocs/')
        );
    }

    if (
        strpos(
            $image,
            $project_path
        ) === 0
    ) {
        $image = substr(
            $image,
            strlen($project_path)
        );
    }

    $image = ltrim(
        $image,
        '/'
    );

    $filename = basename($image);

    $possible_files = [
        __DIR__ . '/' . $image,
        __DIR__ . '/images/' . $filename,
        __DIR__ . '/uploads/' . $filename,
        __DIR__ . '/uploads/equipment/' . $filename
    ];

    foreach ($possible_files as $file) {

        if (file_exists($file)) {

            $file = str_replace(
                '\\',
                '/',
                $file
            );

            if (
                strpos(
                    $file,
                    '/uploads/equipment/'
                ) !== false
            ) {
                return 'uploads/equipment/' . $filename;
            }

            if (
                strpos(
                    $file,
                    '/uploads/'
                ) !== false
            ) {
                return 'uploads/' . $filename;
            }

            if (
                strpos(
                    $file,
                    '/images/'
                ) !== false
            ) {
                return 'images/' . $filename;
            }

            return $image;
        }
    }

    return '';
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8') ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars(
            __('recommended_equipment'),
            ENT_QUOTES,
            'UTF-8'
        ) ?>
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
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f8fafb;
            color: #172033;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 0 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .02);
        }

        .location {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        .language {
            position: relative;
        }

        .language-button {
            border: 1px solid #dbe2e8;
            background: white;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
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
            z-index: 1000;
            overflow: hidden;
        }

        .language:hover .language-menu {
            display: block;
        }

        .language-menu a {
            display: block;
            padding: 11px 14px;
            color: #333;
            text-decoration: none;
            font-size: 13px;
        }

        .language-menu a:hover {
            background: #eef8f1;
            color: #168b45;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .profile-icon {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            border: 2px solid #cbd5e1;
        }

        .profile-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
        }

        .profile-role {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
        }

        .content {
            padding: 5px 5px 30px;
        }

        .breadcrumb {
            font-size: 14px;
            margin-bottom: 18px;
        }

        .breadcrumb a {
            color: #168b45;
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb .current {
            color: #777;
        }

        .breadcrumb span {
            color: #777;
            margin: 0 8px;
        }

        .heading-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .heading h1 {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .heading p {
            color: #64748b;
            font-size: 15px;
            margin: 0;
        }

        .browse {
            padding: 11px 18px;
            background: #198754;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 800;
            font-size: 13px;
            white-space: nowrap;
        }

        .browse:hover {
            background: #157347;
            color: white;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            transition: 0.2s;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .02);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .card-image {
            width: 100%;
            height: 230px;
            object-fit: cover;
            background: #f1f1f1;
            display: block;
        }

        .favorite {
            position: absolute;
            right: 15px;
            top: 15px;
            width: 42px;
            height: 42px;
            background: white;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            color: #777;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.12);
            z-index: 10;
            transition: 0.2s;
        }

        .favorite:hover {
            transform: scale(1.08);
        }

        .favorite.active {
            color: #e53935;
        }

        .wishlist-form {
            margin: 0;
            padding: 0;
        }

        .card-body {
            padding: 18px;
        }

        .equipment-name {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .category {
            color: #777;
            font-size: 14px;
            margin-bottom: 13px;
        }

        .price {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 13px;
            color: #0f172a;
        }

        .price span {
            font-size: 13px;
            font-weight: normal;
            color: #666;
        }

        .location-text {
            color: #666;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .rating {
            color: #d99d00;
            font-size: 14px;
        }

        .rating span {
            color: #666;
            margin-left: 5px;
        }

        .view-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: #198754;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 7px;
            margin-top: 16px;
            font-weight: 800;
            font-size: 13px;
        }

        .view-btn:hover {
            background: #157347;
            color: white;
        }

        .empty {
            background: white;
            padding: 60px;
            text-align: center;
            border-radius: 15px;
            border: 1px solid #ddd;
        }

        .empty h2 {
            font-size: 20px;
            color: #475569;
            font-weight: 800;
        }

        @media(max-width: 1100px) {

            .equipment-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media(max-width: 900px) {

            .main-content {
                margin-left: 250px;
                padding: 15px;
            }

            .topbar {
                padding: 10px 15px;
            }

            .location {
                display: none;
            }

        }

        @media(max-width: 750px) {

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .equipment-grid {
                grid-template-columns: 1fr;
            }

            .heading-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .topbar {
                padding: 10px 15px;
                gap: 10px;
            }

            .content {
                padding: 5px 0 30px;
            }

        }

    </style>

</head>

<body>

    <?php include 'renter_sidebar.php'; ?>

    <div class="main-content">

        <div class="topbar">

            <div class="location">
                <i class="fa-solid fa-location-dot me-1"></i>
                <?= htmlspecialchars(
                    $user_location,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="language">

                <button class="language-button">

                    <i class="fa-solid fa-globe me-1"></i>

                    <?php
                    if ($current_lang === 'kn') {
                        echo 'ಕನ್ನಡ';
                    } elseif ($current_lang === 'hi') {
                        echo 'हिन्दी';
                    } else {
                        echo 'English';
                    }
                    ?>

                    <i class="fa-solid fa-chevron-down ms-1"></i>

                </button>

                <div class="language-menu">

                    <a href="?lang=en">
                        English
                    </a>

                    <a href="?lang=kn">
                        ಕನ್ನಡ
                    </a>

                    <a href="?lang=hi">
                        हिन्दी
                    </a>

                </div>

            </div>

            <div class="profile">

                <div class="profile-icon">
                    <?= htmlspecialchars(
                        $renter_initial,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <div>

                    <div class="profile-name">
                        <?= htmlspecialchars(
                            $user_name,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div class="profile-role">
                        <?= htmlspecialchars(
                            __('renter'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

            </div>

        </div>

        <div class="content">

            <div class="breadcrumb">

                <a href="renter_dashboard.php">

                    <?= htmlspecialchars(
                        __('home'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

                <span>›</span>

                <span class="current">

                    <?= htmlspecialchars(
                        __('recommended'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>

            <div class="heading-row">

                <div class="heading">

                    <h1>

                        <?= htmlspecialchars(
                            __('recommended_equipment'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h1>

                    <p>

                        <?= htmlspecialchars(
                            __('recommended_description'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </p>

                </div>

                <a
                    href="categories.php"
                    class="browse"
                >

                    <?= htmlspecialchars(
                        __('browse_all'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </a>

            </div>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <div class="equipment-grid">

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <?php

                        $title =
                            $row['title']
                            ?? 'Agricultural Equipment';

                        $category =
                            $row['category']
                            ?? '';

                        $display_title =
                            equipmentName($title);

                        $display_category =
                            categoryName($category);

                        $image =
                            getImagePath(
                                $row['image'] ?? ''
                            );

                        $price = 0;

                        if (
                            isset(
                                $row['price_per_day']
                            )
                        ) {
                            $price =
                                $row['price_per_day'];

                        } elseif (
                            isset(
                                $row['daily_rate']
                            )
                        ) {
                            $price =
                                $row['daily_rate'];

                        } elseif (
                            isset(
                                $row['rent_per_day']
                            )
                        ) {
                            $price =
                                $row['rent_per_day'];

                        } elseif (
                            isset(
                                $row['price']
                            )
                        ) {
                            $price =
                                $row['price'];
                        }

                        $location =
                            $row['location']
                            ?? '';

                        if (empty($location)) {
                            $location =
                                __('location_not_available');
                        }

                        $rating =
                            $row['rating']
                            ?? '4.5';

                        $reviews =
                            $row['review_count']
                            ?? $row['reviews']
                            ?? 0;

                        $equipment_id = 0;

                        if (
                            isset(
                                $row['equipment_id']
                            )
                        ) {
                            $equipment_id =
                                (int) $row['equipment_id'];
                        }

                        $is_wishlisted =
                            in_array(
                                $equipment_id,
                                $wishlist_ids,
                                true
                            );

                        ?>

                        <div class="card">

                            <?php if (!empty($image)): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $image,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    class="card-image"
                                    alt="<?= htmlspecialchars(
                                        $display_title,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >

                            <?php else: ?>

                                <div
                                    class="card-image"
                                    style="
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                        color:#888;
                                    "
                                >

                                    <?= htmlspecialchars(
                                        $display_title,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                            <?php endif; ?>

                            <?php if ($equipment_id > 0): ?>

                                <form
                                    method="POST"
                                    action="recommended.php"
                                    class="wishlist-form"
                                >

                                    <input
                                        type="hidden"
                                        name="equipment_id"
                                        value="<?= $equipment_id ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="wishlist_action"
                                        value="<?= $is_wishlisted ? 'remove' : 'add' ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="favorite <?= $is_wishlisted ? 'active' : '' ?>"
                                        title="<?= $is_wishlisted ? 'Remove from Wishlist' : 'Add to Wishlist' ?>"
                                    >

                                        <?= $is_wishlisted ? '♥' : '♡' ?>

                                    </button>

                                </form>

                            <?php endif; ?>

                            <div class="card-body">

                                <div class="equipment-name">

                                    <?= htmlspecialchars(
                                        $display_title,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                                <div class="category">

                                    <?= htmlspecialchars(
                                        __('category'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    :

                                    <?= htmlspecialchars(
                                        $display_category,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                                <div class="price">

                                    ₹<?= number_format(
                                        (float) $price,
                                        0
                                    ) ?>

                                    <span>

                                        /

                                        <?= htmlspecialchars(
                                            __('day'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                </div>

                                <div class="location-text">

                                    <i class="fa-solid fa-location-dot me-1"></i>

                                    <?= htmlspecialchars(
                                        $location,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                                <div class="rating">

                                    ★
                                    <?= htmlspecialchars(
                                        $rating,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                    <span>

                                        (
                                        <?= htmlspecialchars(
                                            $reviews,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        <?= htmlspecialchars(
                                            __('reviews'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                        )

                                    </span>

                                </div>

                                <?php if ($equipment_id > 0): ?>

                                    <a
                                        href="equipment_details.php?id=<?= urlencode($equipment_id) ?>"
                                        class="view-btn"
                                    >

                                        <?= htmlspecialchars(
                                            __('view_equipment'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </a>

                                <?php else: ?>

                                    <span
                                        class="view-btn"
                                        style="opacity:0.6; cursor:not-allowed;"
                                    >

                                        <?= htmlspecialchars(
                                            __('view_equipment'),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endwhile; ?>

                </div>

            <?php else: ?>

                <div class="empty">

                    <h2>

                        <?= htmlspecialchars(
                            __('no_recommended_equipment'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </h2>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>