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

// Handle Add Category Logic (Only available if logged in user is NOT a renter)
if (isset($_POST['add_category']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'renter') {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $description   = mysqli_real_escape_string($conn, $_POST['description']);
    $icon_class    = mysqli_real_escape_string($conn, $_POST['icon_class']);
    
    $image_name = '';
    if (!empty($_FILES['category_image']['name'])) {
        $image_name = time() . '_' . $_FILES['category_image']['name'];
        move_uploaded_file($_FILES['category_image']['tmp_name'], 'uploads/' . $image_name);
    }

    $insert_sql = "INSERT INTO categories (category_name, description, image, icon_class) 
                   VALUES ('$category_name', '$description', '$image_name', '$icon_class')";
    mysqli_query($conn, $insert_sql);
    header("Location: categories.php");
    exit();
}

// Fetch categories and count linked equipment using category_id
$cat_query = "SELECT c.*, 
              (SELECT COUNT(*) FROM equipment e WHERE e.category_id = c.category_id) AS eq_count 
              FROM categories c ORDER BY c.category_id ASC";
$cat_res = mysqli_query($conn, $cat_query);
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('categories'); ?> - Agriculture Equipment Rental</title>
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

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #ffffff;
            border-right: 1px solid var(--card-border);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            padding-top: 20px;
        }

        .sidebar .brand-logo {
            padding: 0 20px 20px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar .brand-logo i {
            font-size: 1.8rem;
            color: var(--brand-green);
        }

        .sidebar .nav-link {
            color: #4a5568;
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 0 20px 20px 0;
            margin-bottom: 2px;
            text-decoration: none;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: var(--brand-green);
            background-color: var(--brand-green-light);
            font-weight: 600;
        }

        /* Main Content Layout */
        .main-wrapper {
            margin-left: 250px;
            padding: 20px 35px;
        }

        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
        }

        /* Category Card Layout */
        .cat-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.2s ease-in-out;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .cat-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .cat-badge-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .cat-img-box {
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 0;
        }

        .cat-img-box img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        .cat-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px dashed var(--card-border);
            margin-top: 15px;
        }

        .lang-select {
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand-logo">
            <i class="fa-solid fa-tractor"></i>
            <div>
                <strong style="color: var(--brand-green); display: block; line-height: 1;">AGRICULTURE</strong>
                <span style="font-size: 0.65rem; color: #64748b; font-weight: 700;">EQUIPMENT RENTAL SYSTEM</span>
            </div>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="renter_dashboard.php" class="nav-link">
                    <i class="fa-solid fa-border-all"></i> <?= __('dashboard'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="search_equipment.php" class="nav-link">
                    <i class="fa-solid fa-magnifying-glass"></i> <?= __('search_equipment'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link active">
                    <i class="fa-solid fa-layer-group"></i> <?= __('categories'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="featured_equipment.php" class="nav-link">
                    <i class="fa-regular fa-star"></i> <?= __('featured_equipment'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="recommended.php" class="nav-link">
                    <i class="fa-regular fa-thumbs-up"></i> <?= __('recommended'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="my_bookings.php" class="nav-link">
                    <i class="fa-regular fa-calendar-check"></i> <?= __('my_bookings'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="rental_history.php" class="nav-link">
                    <i class="fa-solid fa-clock-rotate-left"></i> <?= __('rental_history'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link">
                    <i class="fa-regular fa-user"></i> <?= __('my_profile'); ?>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> <?= __('logout'); ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper">

        <!-- TOP BAR -->
        <div class="top-bar">
            <div class="search-box position-relative w-50">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" class="form-control ps-5 rounded-pill bg-light border-0" placeholder="<?= __('search_placeholder'); ?>">
            </div>

            <div class="d-flex align-items-center gap-4">
                <!-- LANGUAGE SELECTOR -->
                <div class="d-flex align-items-center gap-1">
                    <i class="fa-solid fa-globe text-secondary"></i>
                    <select class="lang-select" onchange="window.location.href='categories.php?lang=' + this.value;">
                        <option value="en" <?= ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                        <option value="kn" <?= ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                        <option value="hi" <?= ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी</option>
                    </select>
                </div>

                <!-- ADD CATEGORY BUTTON (Hidden for Renters) -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'renter'): ?>
                    <button class="btn btn-sm btn-success fw-semibold rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fa-solid fa-plus me-1"></i> <?= __('add_category'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- BREADCRUMB & HEADER -->
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="renter_dashboard.php" class="text-success text-decoration-none"><?= __('home'); ?></a></li>
                <li class="breadcrumb-item active"><?= __('categories'); ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h3 class="fw-bold mb-1"><?= __('equipment_categories'); ?></h3>
                <p class="text-muted small mb-0"><?= __('categories_desc'); ?></p>
            </div>

            <!-- CAN'T FIND BOX -->
            <div class="bg-success-subtle p-3 rounded-4 d-flex align-items-center gap-3 border border-success-subtle">
                <div class="bg-success text-white rounded-circle p-2">
                    <i class="fa-solid fa-tractor"></i>
                </div>
                <div>
                    <div class="fw-bold small"><?= __('cant_find_title'); ?></div>
                    <a href="search_equipment.php" class="text-success text-decoration-none fw-bold small"><?= __('try_searching'); ?></a>
                </div>
            </div>
        </div>

        <!-- CATEGORIES GRID (UPDATED: View equipment via category_id) -->
        <div class="row g-4 mb-4">
            <?php if ($cat_res && mysqli_num_rows($cat_res) > 0): ?>
                <?php 
                $bg_colors = ['#e8f5e9', '#fff8e1', '#e0f7fa', '#fbe9e7', '#f3e5f5'];
                $text_colors = ['#2d6a4f', '#d97706', '#0284c7', '#ea580c', '#9333ea'];
                $i = 0;
                while ($cat = mysqli_fetch_assoc($cat_res)): 
                    $bg = $bg_colors[$i % count($bg_colors)];
                    $color = $text_colors[$i % count($text_colors)];
                    $i++;
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="cat-card">
                            <div>
                                <div class="cat-badge-icon mb-3" style="background-color: <?= $bg; ?>; color: <?= $color; ?>;">
                                    <i class="<?= !empty($cat['icon_class']) ? $cat['icon_class'] : 'fa-solid fa-tractor'; ?>"></i>
                                </div>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($cat['category_name']); ?></h5>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($cat['description'] ?? 'Equipment for farming activities.'); ?></p>
                                
                                <div class="cat-img-box">
                                    <?php if (!empty($cat['image']) && file_exists('uploads/' . $cat['image'])): ?>
                                        <img src="uploads/<?= $cat['image']; ?>" alt="<?= htmlspecialchars($cat['category_name']); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-tractor text-muted fa-4x opacity-25"></i>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="cat-card-footer">
                                <span class="fw-bold text-dark small"><?= $cat['eq_count']; ?> <?= __('equipment_count'); ?></span>
                                <a href="category_items.php?category_id=<?= (int)$cat['category_id']; ?>" class="text-success fw-bold text-decoration-none small d-flex align-items-center gap-1">
                                    <?= __('view_equipment'); ?> <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><div class="alert alert-light text-center border py-4"><?= __('no_categories'); ?></div></div>
            <?php endif; ?>
        </div>

        <!-- FOOTER TRUST BANNER -->
        <div class="bg-success-subtle rounded-4 p-3 border border-success-subtle d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white p-2 rounded-circle">
                    <i class="fa-solid fa-shield-halved fa-lg"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0"><?= __('secure_platform'); ?></h6>
                    <small class="text-muted"><?= __('secure_sub'); ?></small>
                </div>
            </div>
            
            <div class="d-flex gap-4 text-muted small fw-semibold">
                <div><i class="fa-solid fa-check text-success me-1"></i> <?= __('verified_eq'); ?></div>
                <div><i class="fa-solid fa-lock text-success me-1"></i> <?= __('secure_booking'); ?></div>
                <div><i class="fa-solid fa-rotate-left text-success me-1"></i> <?= __('easy_cancel'); ?></div>
                <div><i class="fa-solid fa-headset text-success me-1"></i> <?= __('support_247'); ?></div>
            </div>
        </div>

    </div>

    <!-- ADD CATEGORY MODAL (Only rendered if non-renter) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] !== 'renter'): ?>
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><?= __('add_category'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('category_name'); ?></label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Tractors, Tillage" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('category_desc'); ?></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief explanation of equipment type..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('category_image'); ?></label>
                        <input type="file" name="category_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('category_icon'); ?></label>
                        <input type="text" name="icon_class" class="form-control" placeholder="e.g. fa-solid fa-tractor">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_category" class="btn btn-success fw-semibold w-100"><?= __('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>