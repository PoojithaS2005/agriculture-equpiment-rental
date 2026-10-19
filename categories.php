<?php
session_start();

require_once 'includes/lang.php';

if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$cat_query = "SELECT * FROM categories ORDER BY category_id ASC";
$cat_res = mysqli_query($conn, $cat_query);
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php
        echo isset($translations[$current_lang]['categories_title'])
            ? $translations[$current_lang]['categories_title']
            : 'Categories - Agriculture Equipment Rental System';
        ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >

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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--page-bg-1);
            color: var(--text-dark);
            margin: 0;
            min-height: 100vh;
        }

        /* Main content starts after the common renter sidebar */
        .main-wrapper {
            margin-left: 250px;
            padding: 28px 35px 40px;
            min-height: 100vh;
            color: var(--text-dark);
            background:
                radial-gradient(circle at 12% 8%, rgba(255, 255, 255, .70), transparent 32%),
                radial-gradient(circle at 88% 20%, rgba(220, 232, 205, .55), transparent 34%),
                radial-gradient(circle at 70% 92%, rgba(255, 255, 255, .50), transparent 32%),
                linear-gradient(135deg, var(--page-bg-1) 0%, var(--page-bg-2) 50%, var(--page-bg-3) 100%);
            background-attachment: fixed;
        }

        /* ---------- Top navbar ---------- */

        .top-navbar {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .top-navbar .input-group-text,
        .top-navbar .form-control,
        .top-navbar .btn,
        .top-navbar .dropdown-toggle {
            border-radius: 12px !important;
        }

        .top-navbar .input-group-text,
        .top-navbar .form-control {
            background: rgba(255, 255, 255, .80) !important;
            border: 1px solid var(--line) !important;
            box-shadow: none !important;
        }

        .top-navbar .form-control:focus {
            border-color: #6bbd91 !important;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, .12) !important;
        }

        /* Search button (overrides inline colour) */
        .top-navbar button[type="submit"] {
            background: var(--brand-green-pill) !important;
            border: 0 !important;
            color: #fff !important;
            font-weight: 600;
            box-shadow: 0 6px 14px rgba(31, 92, 67, .20);
        }

        .top-navbar button[type="submit"]:hover {
            background: var(--brand-green-hover) !important;
        }

        /* Language dropdown */
        .top-navbar .dropdown-toggle {
            background: rgba(255, 255, 255, .78) !important;
            border: 1px solid var(--line) !important;
            color: var(--brand-green) !important;
            font-weight: 600;
        }

        .top-navbar .dropdown-menu {
            background: rgba(255, 255, 255, .95);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            box-shadow: 0 16px 35px rgba(50, 70, 45, .14);
        }

        .top-navbar .dropdown-item {
            border-radius: 8px;
        }

        .top-navbar .dropdown-item:hover {
            background: #e9f3e6;
            color: var(--brand-green);
        }

        /* ---------- Heading area ---------- */

        .main-wrapper h3 {
            color: var(--text-dark);
            letter-spacing: -.3px;
        }

        .main-wrapper p.text-muted {
            color: var(--text-muted) !important;
        }

        .main-wrapper .breadcrumb-item a {
            color: var(--text-muted) !important;
        }

        .main-wrapper .breadcrumb-item.active {
            color: var(--brand-green) !important;
        }

        /* ---------- Category cards ---------- */

        .cat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 22px;
            padding: 22px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-soft), inset 0 1px 0 rgba(255, 255, 255, .95);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .cat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(92, 163, 121, .40);
            box-shadow: 0 18px 38px rgba(60, 90, 55, .15), inset 0 1px 0 rgba(255, 255, 255, .95);
        }

        .cat-img-container {
            height: 160px;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            background: linear-gradient(145deg, rgba(247, 249, 241, .95), rgba(255, 255, 255, .90));
            border: 1px solid rgba(45, 106, 79, .07);
        }

        .cat-img-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
            transition: transform .25s ease;
        }

        .cat-card:hover .cat-img-container img {
            transform: scale(1.035);
        }

        .cat-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .cat-circle-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            border: 1px solid rgba(255, 255, 255, .85);
            box-shadow: 0 5px 14px rgba(60, 90, 55, .08);
        }

        .cat-card h5 {
            color: var(--text-dark);
            letter-spacing: -.2px;
        }

        .cat-card p {
            color: var(--text-muted) !important;
            line-height: 1.6;
        }

        .cat-card .border-top {
            border-color: var(--line) !important;
        }

        .cat-card a.text-success {
            color: var(--brand-green) !important;
            transition: color .2s ease;
        }

        .cat-card a.text-success:hover {
            color: var(--brand-green-hover) !important;
        }

        .cat-card a.text-success i {
            transition: transform .2s ease;
        }

        .cat-card a.text-success:hover i {
            transform: translateX(3px);
        }

        /* ---------- Empty message ---------- */

        .main-wrapper .alert {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            color: #3c5a46;
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        /* ---------- Responsive ---------- */

        @media (max-width: 991.98px) {
            .main-wrapper {
                margin-left: 250px;
                padding: 22px;
            }
        }

        @media (max-width: 767.98px) {
            .main-wrapper {
                margin-left: 0 !important;
                padding: 16px;
            }

            .top-navbar {
                flex-wrap: wrap;
                gap: 12px;
                padding: 14px;
                margin-bottom: 22px;
            }

            .top-navbar form {
                width: 100% !important;
            }

            .top-navbar > .d-flex {
                width: 100%;
                justify-content: flex-end;
            }

            .cat-card {
                padding: 18px;
                border-radius: 18px;
            }

            .cat-img-container {
                height: 145px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .cat-card,
            .cat-img-container img,
            .cat-card a.text-success i {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Common Renter Sidebar -->
    <?php include 'renter_sidebar.php'; ?>

    <!-- MAIN CONTAINER -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR WITH SEARCH BAR -->
        <div class="top-navbar">

            <form
                action="search_equipment.php"
                method="GET"
                class="d-flex w-75 gap-2 align-items-center"
            >

                <?php if (!empty($current_lang)): ?>
                    <input
                        type="hidden"
                        name="lang"
                        value="<?php echo htmlspecialchars($current_lang); ?>"
                    >
                <?php endif; ?>

                <div class="input-group input-group-sm">

                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>

                    <input
                        type="text"
                        name="q"
                        class="form-control border-start-0"
                        placeholder="<?php echo __('search_placeholder', 'Search equipment...'); ?>"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-sm text-white px-3"
                    style="background-color: #2d6a4f;"
                >
                    <?php echo __('search', 'Search'); ?>
                </button>

            </form>

            <div class="d-flex align-items-center gap-3">

                <div class="dropdown">

                    <button
                        class="btn btn-sm btn-light border dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                    >
                        <?php
                        if ($current_lang == 'kn') {
                            echo 'ಕನ್ನಡ';
                        } elseif ($current_lang == 'hi') {
                            echo 'हिन्दी';
                        } else {
                            echo 'English';
                        }
                        ?>
                    </button>

                    <ul class="dropdown-menu">

                        <li>
                            <a
                                class="dropdown-item"
                                href="categories.php?lang=en"
                            >
                                English
                            </a>
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="categories.php?lang=kn"
                            >
                                ಕನ್ನಡ
                            </a>
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="categories.php?lang=hi"
                            >
                                हिन्दी
                            </a>
                        </li>

                    </ul>

                </div>

            </div>

        </div>

        <!-- HEADER & PROMO BANNER -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <nav aria-label="breadcrumb">

                    <ol
                        class="breadcrumb mb-1"
                        style="font-size: 0.8rem;"
                    >

                        <li class="breadcrumb-item">

                            <a
                                href="renter_dashboard.php"
                                class="text-muted text-decoration-none"
                            >
                                <?php echo __('home', 'Home'); ?>
                            </a>

                        </li>

                        <li class="breadcrumb-item active text-success fw-semibold">

                            <?php echo __('categories', 'Categories'); ?>

                        </li>

                    </ol>

                </nav>

                <h3 class="fw-bold mb-1">
                    <?php echo __('equipment_categories', 'Equipment Categories'); ?>
                </h3>

                <p class="text-muted small mb-0">
                    <?php
                    echo __('browse_categories_desc', 'Browse equipment by categories and find what you need.');
                    ?>
                </p>

            </div>

        </div>

        <!-- CATEGORIES GRID -->
        <div class="row g-4 mb-4">

            <?php if ($cat_res && mysqli_num_rows($cat_res) > 0): ?>

                <?php while ($cat = mysqli_fetch_assoc($cat_res)): ?>

                    <?php
                    $original_name = trim($cat['category_name']);
                    $original_desc = trim($cat['description']);

                    // Map database names directly to translation array keys in lang.php
                    $lower_name = strtolower($original_name);

                    $lang_key = '';
                    $desc_key = '';

                    if (str_contains($lower_name, 'tractor')) {

                        $lang_key = 'cat_tractors';
                        $desc_key = 'desc_tractors';

                    } elseif (str_contains($lower_name, 'harvest')) {

                        $lang_key = 'cat_harvesting';
                        $desc_key = 'desc_harvesting';

                    } elseif (str_contains($lower_name, 'irrigat')) {

                        $lang_key = 'cat_irrigation';
                        $desc_key = 'desc_irrigation';

                    } elseif (str_contains($lower_name, 'till')) {

                        $lang_key = 'cat_tillage';
                        $desc_key = 'desc_tillage';

                    } elseif (str_contains($lower_name, 'seed')) {

                        $lang_key = 'cat_seeding';
                        $desc_key = 'desc_seeding';

                    } elseif (str_contains($lower_name, 'spray')) {

                        $lang_key = 'cat_spraying';
                        $desc_key = 'desc_spraying';
                    }

                    // Fallback to database values if translation key isn't found
                    $display_name = ($lang_key && function_exists('__'))
                        ? __($lang_key, $original_name)
                        : $original_name;

                    $display_desc = ($desc_key && function_exists('__'))
                        ? __($desc_key, $original_desc)
                        : $original_desc;

                    // Equipment is the single source of truth.
                    $base_name = rtrim(strtolower($original_name), 's');
                    $searchTerm = '%' . $base_name . '%';

                    $q_equip = "SELECT COUNT(*) AS total FROM equipment WHERE LOWER(category) LIKE '$searchTerm'";
                    $res_equip = mysqli_query($conn, $q_equip);
                    $row_equip = mysqli_fetch_assoc($res_equip);
                    $eq_count = (int)($row_equip['total'] ?? 0);

                    if (str_contains($lower_name, 'tractor')) {

                        $cat_img = 'images/tractor.png';
                        $circle_bg = '#e8f5e9';
                        $circle_color = '#2d6a4f';
                        $circle_icon = 'fa-solid fa-tractor';

                    } elseif (str_contains($lower_name, 'harvest')) {

                        $cat_img = 'images/harvesting.png';
                        $circle_bg = '#fef3c7';
                        $circle_color = '#d97706';
                        $circle_icon = 'fa-solid fa-wheat-awn';

                    } elseif (str_contains($lower_name, 'irrigat')) {

                        $cat_img = 'images/irrigation.png';
                        $circle_bg = '#e0f2fe';
                        $circle_color = '#0284c7';
                        $circle_icon = 'fa-solid fa-droplet';

                    } elseif (str_contains($lower_name, 'till')) {

                        $cat_img = 'images/tillage.png';
                        $circle_bg = '#ffedd5';
                        $circle_color = '#ea580c';
                        $circle_icon = 'fa-solid fa-arrows-split-up-and-left';

                    } elseif (str_contains($lower_name, 'seed')) {

                        $cat_img = 'images/seeding.png';
                        $circle_bg = '#d1fae5';
                        $circle_color = '#059669';
                        $circle_icon = 'fa-solid fa-seedling';

                    } else {

                        $cat_img = 'images/spraying.png';
                        $circle_bg = '#f3e8ff';
                        $circle_color = '#9333ea';
                        $circle_icon = 'fa-solid fa-spray-can-sparkles';
                    }
                    ?>

                    <div class="col-lg-4 col-md-6">

                        <div class="cat-card">

                            <div>

                                <div class="cat-header-row">

                                    <div
                                        class="cat-circle-icon"
                                        style="background-color: <?= $circle_bg; ?>; color: <?= $circle_color; ?>;"
                                    >
                                        <i class="<?= $circle_icon; ?>"></i>
                                    </div>

                                </div>

                                <div class="cat-img-container">

                                    <img
                                        src="<?= $cat_img; ?>"
                                        alt="<?= htmlspecialchars($display_name); ?>"
                                        onerror="this.src='images/tractor.png'"
                                    >

                                </div>

                                <h5 class="fw-bold mb-1">
                                    <?= htmlspecialchars($display_name); ?>
                                </h5>

                                <p
                                    class="text-muted small mb-3"
                                    style="font-size: 0.85rem;"
                                >
                                    <?= htmlspecialchars($display_desc); ?>
                                </p>

                            </div>

                            <div
                                class="d-flex align-items-center justify-content-between pt-3 border-top mt-2"
                            >

                                <span class="text-dark fw-bold small">

                                    <?= (int)$eq_count; ?>

                                    <?php echo __('equipment_label', 'Equipment'); ?>

                                </span>

                                <a
                                    href="category_items.php?category=<?= urlencode($original_name); ?>"
                                    class="text-success fw-bold text-decoration-none small d-flex align-items-center gap-1"
                                >
                                    <?php echo __('view_equipment', 'View Equipment'); ?>

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">

                    <div class="alert alert-light text-center border py-4">

                        <?php
                        echo __('no_categories_found', 'No categories configured in database.');
                        ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>