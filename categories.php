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
        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
            --brand-green-light: #e8f5e9;
            --bg-gray: #f8fafc;
            --card-border: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-gray);
            color: #2d3748;
            margin: 0;
        }

        /* Main content starts after the common renter sidebar */
        .main-wrapper {
            margin-left: 250px;
            padding: 25px 35px;
        }

        .top-navbar {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .cat-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.2s ease-in-out;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cat-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .cat-img-container {
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .cat-img-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
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

                    // Flexible counting logic
                    $base_name = rtrim(strtolower($original_name), 's');
                    $searchTerm = '%' . $base_name . '%';

                    $q_items = "SELECT COUNT(*) AS total FROM items WHERE LOWER(category) LIKE '$searchTerm'";
                    $res_items = mysqli_query($conn, $q_items);
                    $row_items = mysqli_fetch_assoc($res_items);
                    $count_items = $row_items['total'] ?? 0;

                    $q_equip = "SELECT COUNT(*) AS total FROM equipment WHERE LOWER(category) LIKE '$searchTerm'";
                    $res_equip = mysqli_query($conn, $q_equip);
                    $row_equip = mysqli_fetch_assoc($res_equip);
                    $count_equip = $row_equip['total'] ?? 0;

                    $eq_count = max($count_items, $count_equip);

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