<?php
session_start();
require_once 'includes/lang.php';

if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

// Session security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

// Get category_id securely from GET parameters
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if ($category_id <= 0) {
    header("Location: categories.php");
    exit();
}

// Fetch details for the selected category using Prepared Statements
$cat_stmt = mysqli_prepare($conn, "SELECT category_name, description, image, icon_class FROM categories WHERE category_id = ?");
mysqli_stmt_bind_param($cat_stmt, "i", $category_id);
mysqli_stmt_execute($cat_stmt);
$cat_res = mysqli_stmt_get_result($cat_stmt);
$category = mysqli_fetch_assoc($cat_res);

if (!$category) {
    header("Location: categories.php");
    exit();
}

// Fetch all equipment belonging to this category_id
$eq_stmt = mysqli_prepare($conn, "SELECT * FROM equipment WHERE category_id = ?");
mysqli_stmt_bind_param($eq_stmt, "i", $category_id);
mysqli_stmt_execute($eq_stmt);
$equipment_res = mysqli_stmt_get_result($eq_stmt);
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category['category_name']); ?> - Equipment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-green-light: #e8f5e9;
            --bg-gray: #f8fafc;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-gray); color: #2d3748; }
        .sidebar { width: 250px; min-height: 100vh; background-color: #ffffff; border-right: 1px solid #e2e8f0; position: fixed; top: 0; left: 0; z-index: 100; padding-top: 20px; }
        .sidebar .brand-logo { padding: 0 20px 20px 20px; display: flex; align-items: center; gap: 10px; }
        .sidebar .nav-link { color: #4a5568; padding: 10px 20px; font-size: 0.9rem; font-weight: 500; display: flex; align-items: center; gap: 12px; border-radius: 0 20px 20px 0; margin-bottom: 2px; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: var(--brand-green); background-color: var(--brand-green-light); font-weight: 600; }
        .main-wrapper { margin-left: 250px; padding: 20px 35px; }
        .eq-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: all 0.2s ease; height: 100%; }
        .eq-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand-logo">
            <i class="fa-solid fa-tractor text-success fa-xl"></i>
            <div>
                <strong style="color: var(--brand-green); display: block; line-height: 1;">AGRICULTURE</strong>
                <span style="font-size: 0.65rem; color: #64748b; font-weight: 700;">EQUIPMENT RENTAL SYSTEM</span>
            </div>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="renter_dashboard.php" class="nav-link"><i class="fa-solid fa-border-all"></i> <?= __('dashboard'); ?></a></li>
            <li class="nav-item"><a href="search_equipment.php" class="nav-link"><i class="fa-solid fa-magnifying-glass"></i> <?= __('search_equipment'); ?></a></li>
            <li class="nav-item"><a href="categories.php" class="nav-link active"><i class="fa-solid fa-layer-group"></i> <?= __('categories'); ?></a></li>
            <li class="nav-item"><a href="my_bookings.php" class="nav-link"><i class="fa-regular fa-calendar-check"></i> <?= __('my_bookings'); ?></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fa-regular fa-user"></i> <?= __('my_profile'); ?></a></li>
            <li class="nav-item mt-3"><a href="logout.php" class="nav-link text-danger"><i class="fa-solid fa-right-from-bracket"></i> <?= __('logout'); ?></a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="renter_dashboard.php" class="text-success text-decoration-none"><?= __('home'); ?></a></li>
                <li class="breadcrumb-item"><a href="categories.php" class="text-success text-decoration-none"><?= __('categories'); ?></a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($category['category_name']); ?></li>
            </ol>
        </nav>

        <div class="bg-white p-4 rounded-4 border mb-4 d-flex align-items-center justify-content-between">
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="<?= !empty($category['icon_class']) ? $category['icon_class'] : 'fa-solid fa-tractor'; ?> text-success me-2"></i>
                    <?= htmlspecialchars($category['category_name']); ?>
                </h3>
                <p class="text-muted mb-0 small"><?= htmlspecialchars($category['description'] ?? 'Browse available equipment in this category.'); ?></p>
            </div>
            <a href="categories.php" class="btn btn-outline-success btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Categories
            </a>
        </div>

        <!-- EQUIPMENT GRID -->
        <div class="row g-4">
            <?php if ($equipment_res && mysqli_num_rows($equipment_res) > 0): ?>
                <?php while ($item = mysqli_fetch_assoc($equipment_res)): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="eq-card p-3 d-flex flex-column justify-content-between">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($item['title'] ?? $item['equipment_name'] ?? 'Equipment Item'); ?></h5>
                                <p class="text-muted small mb-2"><?= htmlspecialchars(substr($item['description'] ?? '', 0, 90)) . '...'; ?></p>
                            </div>
                            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-success">
                                    ₹<?= number_format($item['price_per_day'] ?? $item['rental_rate'] ?? 0); ?> <small class="text-muted fw-normal">/ day</small>
                                </span>
                                <a href="equipment_details.php?id=<?= $item['equipment_id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="bg-white p-5 text-center rounded border">
                        <i class="fa-solid fa-tractor text-muted fa-3x mb-3 opacity-50"></i>
                        <h5>No Equipment Found</h5>
                        <p class="text-muted small">There is currently no equipment listed under the "<?= htmlspecialchars($category['category_name']); ?>" category.</p>
                        <a href="search_equipment.php" class="btn btn-success btn-sm rounded-pill">Browse All Equipment</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>