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

// Get the category from the URL safely
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';

if (empty($selected_category)) {
    header("Location: categories.php");
    exit();
}

// Translate category name for display heading if needed
$display_cat_name = $selected_category;
if ($current_lang == 'kn') {
    if (strcasecmp($selected_category, 'Tractors') == 0) $display_cat_name = 'ಟ್ರಾಕ್ಟರ್‌ಗಳು';
    elseif (strcasecmp($selected_category, 'Harvesting Equipment') == 0 || strcasecmp($selected_category, 'Harvesting') == 0) $display_cat_name = 'ಕೊಯ್ಲು ಉಪಕರಣಗಳು';
    elseif (strcasecmp($selected_category, 'Irrigation Equipment') == 0) $display_cat_name = 'ನೀರಾವರಿ ಉಪಕರಣಗಳು';
    elseif (strcasecmp($selected_category, 'Tillage Equipment') == 0) $display_cat_name = 'ಉಳುಮೆ ಉಪಕರಣಗಳು';
    elseif (strcasecmp($selected_category, 'Seeding Equipment') == 0) $display_cat_name = 'ಬಿತ್ತನೆ ಉಪಕರಣಗಳು';
} elseif ($current_lang == 'hi') {
    if (strcasecmp($selected_category, 'Tractors') == 0) $display_cat_name = 'ट्रैक्टर';
    elseif (strcasecmp($selected_category, 'Harvesting Equipment') == 0 || strcasecmp($selected_category, 'Harvesting') == 0) $display_cat_name = 'कटाई उपकरण';
    elseif (strcasecmp($selected_category, 'Irrigation Equipment') == 0) $display_cat_name = 'सिंचाई उपकरण';
    elseif (strcasecmp($selected_category, 'Tillage Equipment') == 0) $display_cat_name = 'जुताई उपकरण';
    elseif (strcasecmp($selected_category, 'Seeding Equipment') == 0) $display_cat_name = 'बुआई उपकरण';
}

// Fetch items belonging to this category securely using prepared statements
$stmt = $conn->prepare("SELECT * FROM items WHERE category = ? ORDER BY item_id DESC");
$stmt->bind_param("s", $selected_category);
$stmt->execute();
$items_res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($display_cat_name); ?> - 
        <?php 
            if($current_lang == 'kn') echo 'ಕೃಷಿ ಉಪಕರಣಗಳ ಬಾಡಿಗೆ';
            elseif($current_lang == 'hi') echo 'कृषि उपकरण किराया';
            else echo 'Agriculture Equipment Rental';
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

        .item-card {
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

        .item-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .item-img-container {
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .item-img-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div>
            <div class="brand-logo">
                <img src="images/logo.png" alt="Logo" onerror="this.src='images/tractor.png'">
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

        <div class="text-center pb-2">
            <img src="images/tractor3.jpg" alt="Tractor" class="img-fluid rounded" onerror="this.src='images/tractor.png'" style="height: 100px; object-fit: cover; width: 100%;">
        </div>
    </div>


    <!-- MAIN CONTAINER -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR -->
        <div class="top-navbar">
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?php 
                        if($current_lang == 'kn') echo 'ಕನ್ನಡ';
                        elseif($current_lang == 'hi') echo 'हिन्दी';
                        else echo 'English';
                    ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="category_items.php?category=<?= urlencode($selected_category); ?>&lang=en">English</a></li>
                    <li><a class="dropdown-item" href="category_items.php?category=<?= urlencode($selected_category); ?>&lang=kn">ಕನ್ನಡ</a></li>
                    <li><a class="dropdown-item" href="category_items.php?category=<?= urlencode($selected_category); ?>&lang=hi">हिन्दी</a></li>
                </ul>
            </div>
        </div>

        <!-- HEADER & BREADCRUMB -->
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                    <li class="breadcrumb-item">
                        <a href="categories.php" class="text-success text-decoration-none">
                            <?php 
                                if($current_lang == 'kn') echo 'ವರ್ಗಗಳು';
                                elseif($current_lang == 'hi') echo 'श्रेणियाँ';
                                else echo 'Categories';
                            ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-muted fw-semibold">
                        <?= htmlspecialchars($display_cat_name); ?>
                    </li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-1"><?= htmlspecialchars($display_cat_name); ?></h3>
            <p class="text-muted small mb-0">
                <?php 
                    if($current_lang == 'kn') echo 'ಈ ವರ್ಗದಡಿಯಲ್ಲಿ ಲಭ್ಯವಿರುವ ಎಲ್ಲಾ ಉಪಕರಣಗಳು.';
                    elseif($current_lang == 'hi') echo 'इस श्रेणी के अंतर्गत उपलब्ध सभी उपकरण।';
                    else echo 'All equipment available under this category.';
                ?>
            </p>
        </div>


        <!-- ITEMS GRID -->
        <div class="row g-4 mb-4">
            <?php if ($items_res && mysqli_num_rows($items_res) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($items_res)): 
                    $i_name  = $item['title'];
                    $i_desc  = $item['description'];
                    $i_img   = $item['image'];
                    $i_price = $item['price_per_day'];
                    $i_id    = $item['item_id'];
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="item-card">
                            <div>
                                <div class="item-img-container">
                                    <img src="<?= !empty($i_img) ? htmlspecialchars($i_img) : 'images/tractor.png'; ?>" 
                                         alt="<?= htmlspecialchars($i_name); ?>"
                                         onerror="this.src='images/tractor.png'">
                                </div>

                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($i_name); ?></h5>
                                <p class="text-muted small mb-3" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($i_desc); ?>
                                </p>
                            </div>

                            <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-2">
                                <span class="text-success fw-bold">
                                    ₹<?= htmlspecialchars($i_price); ?> / 
                                    <?php 
                                        if($current_lang == 'kn') echo 'ದಿನ';
                                        elseif($current_lang == 'hi') echo 'दिन';
                                        else echo 'day';
                                    ?>
                                </span>

                                <a href="book_equipment.php?id=<?= $i_id; ?>"
                                   class="btn btn-sm btn-success fw-semibold">
                                    <?php 
                                        if($current_lang == 'kn') echo 'ಈಗ ಬಾಡಿಗೆಗೆ ಪಡೆಯಿರಿ';
                                        elseif($current_lang == 'hi') echo 'अभी किराए पर लें';
                                        else echo 'Rent Now';
                                    ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-light text-center border py-5">
                        <i class="fa-solid fa-box-open fs-2 text-muted mb-2"></i>
                        <p class="mb-0 text-muted">
                            <?php 
                                if($current_lang == 'kn') echo 'ಈ ವರ್ಗದಲ್ಲಿ ಯಾವುದೇ ಉಪಕರಣಗಳು ಲಭ್ಯವಿಲ್ಲ.';
                                elseif($current_lang == 'hi') echo 'इस श्रेणी में कोई उपकरण उपलब्ध नहीं है।';
                                else echo 'No equipment found under this category.';
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>