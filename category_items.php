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

$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Fetch equipment items belonging to this category
$query = "SELECT * FROM equipment WHERE category = ? ORDER BY equipment_id DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $category);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($category); ?> - <?php echo __('app_title', 'Agriculture Equipment Rental'); ?>
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
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .eq-card {
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

        .eq-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .eq-img-container {
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .eq-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div>
            <div class="brand-logo">
                <img src="images/logo.png" alt="Agriculture Logo" onerror="this.src='images/tractor.png'">
                <span class="brand-title">
                    <?php echo __('app_title', 'Agriculture Equipment Rental'); ?>
                </span>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="renter_dashboard.php" class="nav-link">
                        <i class="fa-solid fa-border-all"></i> <?php echo __('dashboard', 'Dashboard'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="search_equipment.php" class="nav-link">
                        <i class="fa-solid fa-magnifying-glass"></i> <?php echo __('search_equipment', 'Search Equipment'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link active">
                        <i class="fa-solid fa-layer-group"></i> <?php echo __('categories', 'Categories'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="featured_equipment.php" class="nav-link">
                        <i class="fa-regular fa-star"></i> <?php echo __('featured_equipment', 'Featured Equipment'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="recommended.php" class="nav-link">
                        <i class="fa-regular fa-thumbs-up"></i> <?php echo __('recommended', 'Recommended'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_bookings.php" class="nav-link">
                        <i class="fa-regular fa-calendar-check"></i> <?php echo __('my_bookings', 'My Bookings'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="rental_history.php" class="nav-link">
                        <i class="fa-solid fa-clock-rotate-left"></i> <?php echo __('rental_history', 'Rental History'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link">
                        <i class="fa-regular fa-user"></i> <?php echo __('my_profile', 'My Profile'); ?>
                    </a>
                </li>
                <li class="nav-item mt-2">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fa-solid fa-right-from-bracket"></i> <?php echo __('logout', 'Logout'); ?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="text-center pb-2">
            <img src="images/tractor3.jpg" alt="Tractor Illustration" class="img-fluid rounded" onerror="this.src='images/tractor.png'" style="height: 100px; object-fit: cover; width: 100%;">
        </div>
    </div>

    <!-- MAIN CONTAINER -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR -->
        <div class="top-navbar">
            <form action="search_equipment.php" method="GET" class="d-flex w-75 gap-2 align-items-center">
                <?php if (!empty($current_lang)): ?>
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($current_lang); ?>">
                <?php endif; ?>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="<?php echo __('search_placeholder', 'Search equipment...'); ?>" required>
                </div>
                <button type="submit" class="btn btn-sm text-white px-3" style="background-color: #2d6a4f;"><?php echo __('search', 'Search'); ?></button>
            </form>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?php 
                            if($current_lang == 'kn') echo 'ಕನ್ನಡ';
                            elseif($current_lang == 'hi') echo 'हिन्दी';
                            else echo 'English';
                        ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="category_items.php?category=<?= urlencode($category); ?>&lang=en">English</a></li>
                        <li><a class="dropdown-item" href="category_items.php?category=<?= urlencode($category); ?>&lang=kn">ಕನ್ನಡ</a></li>
                        <li><a class="dropdown-item" href="category_items.php?category=<?= urlencode($category); ?>&lang=hi">हिन्दी</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- BREADCRUMB & HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                        <li class="breadcrumb-item"><a href="renter_dashboard.php" class="text-muted text-decoration-none"><?php echo __('home', 'Home'); ?></a></li>
                        <li class="breadcrumb-item"><a href="categories.php" class="text-muted text-decoration-none"><?php echo __('categories', 'Categories'); ?></a></li>
                        <li class="breadcrumb-item active text-success fw-semibold"><?php echo htmlspecialchars($category); ?></li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($category); ?></h3>
                <p class="text-muted small mb-0"><?php echo __('category_items_desc', 'Explore available equipment in this category.'); ?></p>
            </div>
        </div>

        <!-- EQUIPMENT GRID -->
        <div class="row g-4 mb-4">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($eq = mysqli_fetch_assoc($result)): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="eq-card">
                            <div>
                                <!-- Equipment Image -->
                                <div class="eq-img-container">
                                    <?php 
                                        $imgSrc = !empty($eq['image']) ? $eq['image'] : (!empty($eq['equipment_image']) ? $eq['equipment_image'] : 'images/tractor.png');
                                    ?>
                                    <img src="<?= htmlspecialchars($imgSrc); ?>" alt="<?= htmlspecialchars($eq['title'] ?? $eq['equipment_title'] ?? 'Equipment'); ?>" onerror="this.src='images/tractor.png'">
                                </div>

                                <!-- Title -->
                                <h5 class="fw-bold mb-1">
                                    <?= htmlspecialchars($eq['title'] ?? $eq['equipment_title'] ?? 'Unnamed Equipment'); ?>
                                </h5>

                                <!-- Description -->
                                <p class="text-muted small mb-2" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($eq['description'] ?? ''); ?>
                                </p>

                                <!-- CHANGE 1: Display Location dynamically -->
                                <p class="text-secondary small mb-3 fw-semibold">
                                    📍 <?= htmlspecialchars($eq['service_location'] ?? 'Location not specified'); ?>
                                </p>
                            </div>

                            <div>
                                <!-- Price & Rent Now / Details Footer -->
                                <div class="d-flex align-items-center justify-content-between pt-3 border-top mb-2">
                                    <span class="text-dark fw-bold">
                                        ₹<?= number_format($eq['price'] ?? $eq['price_per_day'] ?? 0); ?> / day
                                    </span>
                                </div>

                                <div class="d-flex gap-2">
                                    <!-- CHANGE 2: Add View Equipment Details Button -->
                                    <a href="equipment_details.php?equipment_id=<?= $eq['equipment_id']; ?>" class="btn btn-outline-secondary btn-sm w-50">
                                        <?php echo __('view_details', 'View Details'); ?>
                                    </a>

                                    <!-- Existing Rent Now Button -->
                                    <a href="rent_equipment.php?equipment_id=<?= $eq['equipment_id']; ?>" class="btn btn-sm text-white w-50" style="background-color: #2d6a4f;">
                                        <?php echo __('rent_now', 'Rent Now'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-light text-center border py-4">
                        <?php echo __('no_equipment_found', 'No equipment found in this category.'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>