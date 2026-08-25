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
            if($current_lang == 'kn') echo 'ವರ್ಗಗಳು - ಕೃಷಿ ಉಪಕರಣಗಳ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ';
            elseif($current_lang == 'hi') echo 'श्रेणियाँ - कृषि उपकरण किराया प्रणाली';
            else echo 'Categories - Agriculture Equipment Rental System';
        ?>
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

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
        }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid var(--card-border);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar .brand-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 0 10px 20px 10px;
            border-bottom: 1px solid var(--card-border);
            margin-bottom: 15px;
        }

        .sidebar .brand-logo img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .sidebar .brand-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--brand-green);
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar .nav-link {
            color: #4a5568;
            padding: 10px 15px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 10px;
            margin-bottom: 4px;
            text-decoration: none;
            transition: 0.2s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--brand-green);
            background-color: var(--brand-green-light);
            font-weight: 600;
        }

        .main-wrapper {
            margin-left: 260px;
            padding: 25px 35px;
        }

        .top-navbar {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
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

        .support-banner {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <div>
            <!-- LOGO & TITLE -->
            <div class="brand-logo">
                <img src="images/logo.png" alt="Agriculture Logo" onerror="this.src='images/tractor.png'">
                <span class="brand-title">
                    <?php 
                        if ($current_lang == 'kn') echo 'ಕೃಷಿ ಉಪಕರಣಗಳ ಬಾಡಿಗೆ';
                        elseif ($current_lang == 'hi') echo 'कृषि उपकरण किराया';
                        else echo 'Agriculture Equipment Rental';
                    ?>
                </span>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="renter_dashboard.php" class="nav-link">
                        <i class="fa-solid fa-border-all"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್';
                            elseif($current_lang == 'hi') echo 'डैशबोर्ड';
                            else echo 'Dashboard';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="search_equipment.php" class="nav-link">
                        <i class="fa-solid fa-magnifying-glass"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ಉಪಕರಣಗಳನ್ನು ಹುಡುಕಿ';
                            elseif($current_lang == 'hi') echo 'उपकरण खोजें';
                            else echo 'Search Equipment';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="categories.php" class="nav-link active">
                        <i class="fa-solid fa-layer-group"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ವರ್ಗಗಳು';
                            elseif($current_lang == 'hi') echo 'श्रेणियाँ';
                            else echo 'Categories';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="featured_equipment.php" class="nav-link">
                        <i class="fa-regular fa-star"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ವಿಶೇಷ ಉಪಕರಣಗಳು';
                            elseif($current_lang == 'hi') echo 'विशेष उपकरण';
                            else echo 'Featured Equipment';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="recommended.php" class="nav-link">
                        <i class="fa-regular fa-thumbs-up"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ಶಿಫಾರಸು ಮಾಡಲಾಗಿದೆ';
                            elseif($current_lang == 'hi') echo 'अनुशंसित';
                            else echo 'Recommended';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="my_bookings.php" class="nav-link">
                        <i class="fa-regular fa-calendar-check"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ನನ್ನ ಬುಕಿಂಗ್‌ಗಳು';
                            elseif($current_lang == 'hi') echo 'मेरी बुकिंग';
                            else echo 'My Bookings';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="rental_history.php" class="nav-link">
                        <i class="fa-solid fa-clock-rotate-left"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ಬಾಡಿಗೆ ಇತಿಹಾಸ';
                            elseif($current_lang == 'hi') echo 'किराए का इतिहास';
                            else echo 'Rental History';
                        ?>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fa-regular fa-user"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ನನ್ನ ಪ್ರೊಫೈಲ್';
                            elseif($current_lang == 'hi') echo 'मेरी प्रोफाइल';
                            else echo 'My Profile';
                        ?>
                    </a>
                </li>

                <li class="nav-item mt-2">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fa-solid fa-right-from-bracket"></i> 
                        <?php 
                            if($current_lang == 'kn') echo 'ಲಾಗ್ ಔಟ್';
                            elseif($current_lang == 'hi') echo 'लॉग आउट';
                            else echo 'Logout';
                        ?>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Bottom Sidebar Illustration -->
        <div class="text-center pb-2">
            <img src="images/tractor3.jpg"
                 alt="Tractor Illustration"
                 class="img-fluid rounded"
                 onerror="this.src='images/tractor.png'"
                 style="height: 100px; object-fit: cover; width: 100%;">
        </div>

    </div>

    <!-- MAIN CONTAINER -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR -->
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown">
                        <?php 
                            if($current_lang == 'kn') echo 'ಕನ್ನಡ';
                            elseif($current_lang == 'hi') echo 'हिन्दी';
                            else echo 'English';
                        ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="categories.php?lang=en">English</a></li>
                        <li><a class="dropdown-item" href="categories.php?lang=kn">ಕನ್ನಡ</a></li>
                        <li><a class="dropdown-item" href="categories.php?lang=hi">हिन्दी</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- HEADER & PROMO BANNER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                        <li class="breadcrumb-item">
                            <a href="renter_dashboard.php" class="text-muted text-decoration-none">
                                <?php 
                                    if($current_lang == 'kn') echo 'ಮುಖಪುಟ';
                                    elseif($current_lang == 'hi') echo 'होम';
                                    else echo 'Home';
                                ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active text-success fw-semibold">
                            <?php 
                                if($current_lang == 'kn') echo 'ವರ್ಗಗಳು';
                                elseif($current_lang == 'hi') echo 'श्रेणियाँ';
                                else echo 'Categories';
                            ?>
                        </li>
                    </ol>
                </nav>

                <h3 class="fw-bold mb-1">
                    <?php 
                        if($current_lang == 'kn') echo 'ಉಪಕರಣಗಳ ವರ್ಗಗಳು';
                        elseif($current_lang == 'hi') echo 'उपकरण श्रेणियाँ';
                        else echo 'Equipment Categories';
                    ?>
                </h3>
                <p class="text-muted small mb-0">
                    <?php 
                        if($current_lang == 'kn') echo 'ವರ್ಗಗಳ ಪ್ರಕಾರ ಉಪಕರಣಗಳನ್ನು ಬ್ರೌಸ್ ಮಾಡಿ ಮತ್ತು ನಿಮಗೆ ಬೇಕಾದುದನ್ನು ಹುಡುಕಿ.';
                        elseif($current_lang == 'hi') echo 'श्रेणियों के अनुसार उपकरण ब्राउज़ करें और अपनी आवश्यकतानुसार खोजें।';
                        else echo 'Browse equipment by categories and find what you need.';
                    ?>
                </p>
            </div>

            <div class="support-banner bg-white shadow-sm" style="max-width: 300px;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-tractor text-success fs-4"></i>
                    <div>
                        <span class="fw-bold d-block" style="font-size: 0.8rem;">
                            <?php 
                                if($current_lang == 'kn') echo 'ನಿಮಗೆ ಬೇಕಾಗಿರುವುದು ಸಿಗುತ್ತಿಲ್ಲವೇ?';
                                elseif($current_lang == 'hi') echo 'जो आप ढूंढ रहे हैं वह नहीं मिला?';
                                else echo "Can't find what you're looking for?";
                            ?>
                        </span>
                        <a href="search_equipment.php" class="text-success text-decoration-none fw-semibold" style="font-size: 0.75rem;">
                            <?php 
                                if($current_lang == 'kn') echo 'ಉಪಕರಣಗಳನ್ನು ಹುಡುಕಲು ಪ್ರಯತ್ನಿಸಿ';
                                elseif($current_lang == 'hi') echo 'उपकरण खोजने का प्रयास करें';
                                else echo 'Try searching equipment';
                            ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- CATEGORIES GRID -->
        <div class="row g-4 mb-4">
            <?php if ($cat_res && mysqli_num_rows($cat_res) > 0): ?>
                <?php while ($cat = mysqli_fetch_assoc($cat_res)):
                    $original_name = trim($cat['category_name']);
                    $original_desc = trim($cat['description']);

                    $display_name = $original_name;
                    $display_desc = $original_desc;

                    if ($current_lang == 'kn') {
                        if (strcasecmp($original_name, 'Tractors') == 0) {
                            $display_name = 'ಟ್ರಾಕ್ಟರ್‌ಗಳು';
                            $display_desc = 'ಭೂಮಿ ಉಳುಮೆ ಮತ್ತು ಸಾಗಾಣಿಕೆಗಾಗಿ ವಿಶ್ವಾಸಾರ್ಹ ಟ್ರಾಕ್ಟರ್‌ಗಳು.';
                        } elseif (strcasecmp($original_name, 'Harvesting Equipment') == 0) {
                            $display_name = 'ಕೊಯ್ಲು ಉಪಕರಣಗಳು';
                            $display_desc = 'ಸುಲಭವಾಗಿ ಬೆಳೆ ಕೀಳಲು ಮತ್ತು ಕೊಯ್ಲು ಮಾಡಲು ಆಧುನಿಕ ಯಂತ್ರಗಳು.';
                        } elseif (strcasecmp($original_name, 'Irrigation Equipment') == 0) {
                            $display_name = 'ನೀರಾವರಿ ಉಪಕರಣಗಳು';
                            $display_desc = 'ಪರಿಣಾಮಕಾರಿ ನೀರು ನಿರ್ವಹಣೆಗಾಗಿ ಪಂಪ್‌ಗಳು ಮತ್ತು ನೀರಾವರಿ ವ್ಯವಸ್ಥೆಗಳು.';
                        } elseif (strcasecmp($original_name, 'Tillage Equipment') == 0) {
                            $display_name = 'ಉಳುಮೆ ಉಪಕರಣಗಳು';
                            $display_desc = 'ಬಿತ್ತನೆಗೆ ಮುಂಚಿತವಾಗಿ ಮಣ್ಣನ್ನು ಹದಗೊಳಿಸಲು ಮತ್ತು ಉಳುಮೆ ಮಾಡಲು ಉಪಕರಣಗಳು.';
                        } elseif (strcasecmp($original_name, 'Seeding Equipment') == 0) {
                            $display_name = 'ಬಿತ್ತನೆ ಉಪಕರಣಗಳು';
                            $display_desc = 'ಸರಿಯಾದ ಆಳದಲ್ಲಿ ನಿಖರವಾಗಿ ಬೀಜಗಳನ್ನು ಬಿತ್ತನೆ ಮಾಡುವ ಯಂತ್ರಗಳು.';
                        }
                    } elseif ($current_lang == 'hi') {
                        if (strcasecmp($original_name, 'Tractors') == 0) {
                            $display_name = 'ट्रैक्टर';
                            $display_desc = 'खेती और ढुलाई के काम के लिए शक्तिशाली और विश्वसनीय ट्रैक्टर।';
                        } elseif (strcasecmp($original_name, 'Harvesting Equipment') == 0) {
                            $display_name = 'कटाई उपकरण';
                            $display_desc = 'फसलों की आसान और कुशल कटाई के लिए आधुनिक मशीनें।';
                        } elseif (strcasecmp($original_name, 'Irrigation Equipment') == 0) {
                            $display_name = 'सिंचाई उपकरण';
                            $display_desc = 'प्रभावी जल प्रबंधन के लिए पंप और सिंचाई प्रणाली।';
                        } elseif (strcasecmp($original_name, 'Tillage Equipment') == 0) {
                            $display_name = 'जुताई उपकरण';
                            $display_desc = 'बुआई से पहले मिट्टी को तैयार करने और जोतने के लिए उपकरण।';
                        } elseif (strcasecmp($original_name, 'Seeding Equipment') == 0) {
                            $display_name = 'बुआई उपकरण';
                            $display_desc = 'उचित गहराई पर सटीक बीज बुआई के लिए मशीनें।';
                        }
                    }

                    // Flexible counting logic supporting both items and equipment tables + plural/singular case matching
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

                    $lower_name = strtolower($original_name);

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
                                    <div class="cat-circle-icon" style="background-color: <?= $circle_bg; ?>; color: <?= $circle_color; ?>;">
                                        <i class="<?= $circle_icon; ?>"></i>
                                    </div>
                                </div>

                                <div class="cat-img-container">
                                    <img src="<?= $cat_img; ?>" 
                                         alt="<?= htmlspecialchars($display_name); ?>"
                                         onerror="this.src='images/tractor.png'">
                                </div>

                                <h5 class="fw-bold mb-1">
                                    <?= htmlspecialchars($display_name); ?>
                                </h5>

                                <p class="text-muted small mb-3" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($display_desc); ?>
                                </p>
                            </div>

                            <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-2">
                                <span class="text-dark fw-bold small">
                                    <?= (int)$eq_count; ?> 
                                    <?php 
                                        if($current_lang == 'kn') echo 'ಉಪಕರಣಗಳು';
                                        elseif($current_lang == 'hi') echo 'उपकरण';
                                        else echo 'Equipment';
                                    ?>
                                </span>

                                <a href="category_items.php?category=<?= urlencode($original_name); ?>"
                                   class="text-success fw-bold text-decoration-none small d-flex align-items-center gap-1">
                                    <?php 
                                        if($current_lang == 'kn') echo 'ಉಪಕರಣಗಳನ್ನು ವೀಕ್ಷಿಸಿ';
                                        elseif($current_lang == 'hi') echo 'उपकरण देखें';
                                        else echo 'View Equipment';
                                    ?> 
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
                            if($current_lang == 'kn') echo 'ಡಾಟಾಬೇಸ್‌ನಲ್ಲಿ ಯಾವುದೇ ವರ್ಗಗಳನ್ನು ಕಾನ್ಫಿಗರ್ ಮಾಡಲಾಗಿಲ್ಲ.';
                            elseif($current_lang == 'hi') echo 'डेटाबेस में कोई श्रेणी कॉन्फ़िगर नहीं की गई है।';
                            else echo 'No categories configured in database.';
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- FOOTER TRUST BANNER -->
        <div class="bg-white border rounded-4 p-3 d-flex align-items-center justify-content-around text-muted small shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-shield-halved text-success fs-5"></i>
                <div>
                    <strong class="text-dark d-block" style="font-size: 0.8rem;">
                        <?php 
                            if($current_lang == 'kn') echo 'ಸುರಕ್ಷಿತ ಮತ್ತು ವಿಶ್ವಾಸಾರ್ಹ ವೇದಿಕೆ';
                            elseif($current_lang == 'hi') echo 'सुरक्षित और विश्वसनीय प्लेटफ़ॉर्म';
                            else echo 'Secure & Trusted Platform';
                        ?>
                    </strong>
                    <span style="font-size: 0.7rem;">
                        <?php 
                            if($current_lang == 'kn') echo '100% ಸುರಕ್ಷಿತ ಬುಕಿಂಗ್ • ಪರಿಶೀಲಿಸಿದ ಮಾಲೀಕರು';
                            elseif($current_lang == 'hi') echo '100% सुरक्षित बुकिंग • सत्यापित मालिक';
                            else echo '100% secure bookings • Verified owners';
                        ?>
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check text-success fs-5"></i>
                <span style="font-size: 0.8rem;" class="fw-semibold text-dark">
                    <?php 
                        if($current_lang == 'kn') echo 'ಪರಿಶೀಲಿಸಿದ ಉಪಕರಣಗಳು';
                        elseif($current_lang == 'hi') echo 'सत्यापित उपकरण';
                        else echo 'Verified Equipment';
                    ?>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-lock text-success fs-5"></i>
                <span style="font-size: 0.8rem;" class="fw-semibold text-dark">
                    <?php 
                        if($current_lang == 'kn') echo 'ಸುರಕ್ಷಿತ ಬುಕಿಂಗ್';
                        elseif($current_lang == 'hi') echo 'सुरक्षित बुकिंग';
                        else echo 'Secure Booking';
                    ?>
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-rotate-left text-success fs-5"></i>
                <span style="font-size: 0.8rem;" class="fw-semibold text-dark">
                    <?php 
                        if($current_lang == 'kn') echo 'ಸುಲಭ ರದ್ದತಿ';
                        elseif($current_lang == 'hi') echo 'आसान रद्दीकरण';
                        else echo 'Easy Cancellation';
                    ?>
                </span>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>