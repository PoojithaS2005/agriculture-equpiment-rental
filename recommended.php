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
 * Logged-in user information
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

        if (!empty($user['address'])) {
            $user_location = $user['address'];
        } elseif (!empty($user['city'])) {
            $user_location = $user['city'];
        } elseif (!empty($user['district'])) {
            $user_location = $user['district'];
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
 * Query recommended equipment joined with lender address from users table
 */
$sql = "
    SELECT
        e.*,
        re.recommendation_id,
        COALESCE(
            NULLIF(TRIM(u.address), ''),
            CONCAT_WS(', ', NULLIF(u.city, ''), NULLIF(u.district, ''), NULLIF(u.state, ''))
        ) AS equipment_location
    FROM equipment e
    LEFT JOIN recommended_equipment re
        ON re.equipment_id = e.equipment_id
    INNER JOIN users u
        ON e.lender_id = u.user_id
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

    <link rel="stylesheet" href="css/glassmorphism.css?v=2">

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
            --line: rgba(45, 106, 79, .12);
            --shadow-soft: 0 8px 26px rgba(70, 90, 60, .09);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--page-bg-1);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* ---------- Main area background ---------- */

        .main-content {
            margin-left: 250px;
            padding: 28px 30px 40px;
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
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 0 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .location {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .language {
            position: relative;
        }

        .language-button {
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, .78);
            border-radius: 12px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: var(--brand-green);
        }

        .language-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 42px;
            width: 150px;
            background: rgba(255, 255, 255, .95);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            box-shadow: 0 16px 35px rgba(50, 70, 45, .14);
            z-index: 1000;
            overflow: hidden;
        }

        .language:hover .language-menu {
            display: block;
        }

        .language-menu a {
            display: block;
            padding: 11px 14px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 13px;
        }

        .language-menu a:hover {
            background: #e9f3e6;
            color: var(--brand-green);
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
            background: var(--brand-green-pill);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            border: 2px solid rgba(255, 255, 255, .85);
            box-shadow: 0 4px 12px rgba(31, 92, 67, .18);
        }

        .profile-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-dark);
            line-height: 1.15;
        }

        .profile-role {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 700;
        }

        /* ---------- Content / headings ---------- */

        .content {
            padding: 5px 5px 30px;
        }

        .breadcrumb {
            font-size: 14px;
            margin-bottom: 18px;
        }

        .breadcrumb a {
            color: var(--brand-green);
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb .current {
            color: var(--text-muted);
        }

        .breadcrumb span {
            color: var(--text-muted);
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
            color: var(--text-dark);
            letter-spacing: -.5px;
        }

        .heading p {
            color: var(--text-muted);
            font-size: 15px;
            margin: 0;
        }

        .browse {
            padding: 11px 18px;
            background: var(--brand-green-pill);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 13px;
            white-space: nowrap;
            box-shadow: 0 5px 14px rgba(31, 92, 67, .20);
            transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
        }

        .browse:hover {
            background: var(--brand-green-hover);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ---------- Equipment cards ---------- */

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform .28s ease, box-shadow .28s ease;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 38px rgba(60, 90, 55, .15), inset 0 1px 0 rgba(255, 255, 255, .95);
        }

        .card-image {
            width: 100%;
            height: 230px;
            object-fit: cover;
            background: linear-gradient(135deg, #e3efdc, #f7faf2);
            display: block;
        }

        .favorite {
            position: absolute;
            right: 15px;
            top: 15px;
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, .85);
            border: 1px solid rgba(255, 255, 255, .95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            color: #7a8576;
            cursor: pointer;
            box-shadow: 0 5px 18px rgba(50, 70, 45, .14);
            z-index: 10;
            transition: .2s;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .favorite:hover {
            transform: scale(1.08);
        }

        .favorite.active {
            color: #e53955;
        }

        .wishlist-form {
            margin: 0;
            padding: 0;
        }

        .card-body {
            padding: 20px;
        }

        .equipment-name {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .category {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 13px;
        }

        .price {
            font-size: 19px;
            font-weight: 800;
            margin-bottom: 13px;
            color: var(--brand-green);
        }

        .price span {
            font-size: 13px;
            font-weight: normal;
            color: var(--text-muted);
        }

        .location-text {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .rating {
            color: #d9a51b;
            font-size: 14px;
        }

        .rating span {
            color: var(--text-muted);
            margin-left: 5px;
        }

        .view-btn {
            display: block;
            width: 100%;
            text-align: center;
            background: var(--brand-green-pill);
            color: #fff;
            text-decoration: none;
            padding: 12px;
            border-radius: 12px;
            margin-top: 16px;
            font-weight: 800;
            font-size: 13px;
            box-shadow: 0 5px 14px rgba(31, 92, 67, .18);
            transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
        }

        .view-btn:hover {
            background: var(--brand-green-hover);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ---------- Empty state ---------- */

        .empty {
            background: var(--glass-bg);
            padding: 60px;
            text-align: center;
            border-radius: 22px;
            border: 1px solid var(--glass-border);
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .empty h2 {
            font-size: 20px;
            color: var(--text-dark);
            font-weight: 800;
        }

        /* ---------- Responsive ---------- */

        @media(max-width: 1100px) {

            .equipment-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media(max-width: 900px) {

            .main-content {
                margin-left: 250px;
                padding: 18px;
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
                gap: 18px;
            }

            .card {
                border-radius: 18px;
            }

            .heading-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .heading h1 {
                font-size: 26px;
            }

            .topbar {
                padding: 10px 15px;
                gap: 10px;
                border-radius: 16px;
            }

            .content {
                padding: 5px 0 30px;
            }

        }

        @media (prefers-reduced-motion: reduce) {
            .card,
            .browse,
            .view-btn,
            .favorite {
                transition: none !important;
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
                        echo 'ಹಿन्दी';
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
                        ಹಿन्दी
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
                        Equipments that are recommended to you by the lender
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

                        $price =
                            $row['price_per_day']
                            ?? 0;

                        $location =
                            $row['equipment_location']
                            ?? '';

                        if (empty($location)) {
                            $location =
                                __('location_not_available');
                        }

                        $rating =
                            $row['rating']
                            ?? '4.5';

                        $reviews =
                            $row['rating_count']
                            ?? 0;

                        $equipment_id =
                            (int) ($row['equipment_id'] ?? 0);

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
                                        href="equipment_details.php?id=<?= urlencode($equipment_id) ?>&from=recommended&lang=<?= urlencode($current_lang) ?>"
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