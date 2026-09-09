<?php
session_start();

// Include existing configuration and language files
require_once 'includes/config.php';
require_once 'includes/lang.php';

// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search_query = trim($_GET['q'] ?? '');

// Preserve active language query parameter if present
$lang_param = isset($_GET['lang']) ? '&lang=' . urlencode($_GET['lang']) : '';

// 1. Fetch Renter's Registered Address
$user_stmt = $conn->prepare("SELECT full_name, email, COALESCE(address, 'Not Specified') AS address FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_address = $user_data['address'] ?? 'Not Specified';
$user_stmt->close();

// Extract primary city/keyword from user address for flexible comparison
$primary_user_location = '';
if ($user_address !== 'Not Specified') {
    $parts = explode(',', $user_address);
    $primary_user_location = trim($parts[0]);
}

// 2. Define Supported Categories for Intelligent Fallback Matching
$valid_categories = ['Harvesting', 'Tillage', 'Seeding', 'Spraying', 'Irrigation', 'Tractor'];
$detected_fallback_category = '';

$raw_collected_items = [];
$search_mode = 'specific'; 

if (!empty($search_query)) {
    // Step A: Attempt a specific search using the full query phrase first
    $like_term = "%" . $search_query . "%";
    $eq_stmt = $conn->prepare("SELECT * FROM equipment WHERE status = 'Available' AND (title LIKE ? OR category LIKE ? OR brand_model LIKE ? OR description LIKE ?) ORDER BY distance_km ASC, equipment_id DESC");
    $eq_stmt->bind_param("ssss", $like_term, $like_term, $like_term, $like_term);
    $eq_stmt->execute();
    $eq_result = $eq_stmt->get_result();

    while ($row = $eq_result->fetch_assoc()) {
        $raw_collected_items[] = $row;
    }
    $eq_stmt->close();

    // Step B: If full-phrase search fails, try breaking into words
    if (empty($raw_collected_items)) {
        $words = explode(' ', $search_query);
        if (count($words) > 1) {
            $conditions = [];
            $types = '';
            $params = [];
            
            foreach ($words as $word) {
                if (strlen(trim($word)) > 2) { 
                    $w_term = "%" . trim($word) . "%";
                    $conditions[] = "(title LIKE ? OR category LIKE ? OR brand_model LIKE ? OR description LIKE ?)";
                    $types .= 'ssss';
                    array_push($params, $w_term, $w_term, $w_term, $w_term);
                }
            }

            if (!empty($conditions)) {
                $multi_sql = "SELECT * FROM equipment WHERE status = 'Available' AND (" . implode(' OR ', $conditions) . ") ORDER BY distance_km ASC, equipment_id DESC";
                $multi_stmt = $conn->prepare($multi_sql);
                $multi_stmt->bind_param($types, ...$params);
                $multi_stmt->execute();
                $multi_res = $multi_stmt->get_result();
                while ($row = $multi_res->fetch_assoc()) {
                    $raw_collected_items[] = $row;
                }
                $multi_stmt->close();
            }
        }
    }

    // Step C: Fallback to Category match if still empty
    if (empty($raw_collected_items)) {
        $search_mode = 'fallback';
        $query_lower = mb_strtolower($search_query);

        foreach ($valid_categories as $cat) {
            if (stripos($query_lower, mb_strtolower($cat)) !== false) {
                $detected_fallback_category = $cat;
                break;
            }
        }

        if (!empty($detected_fallback_category)) {
            $cat_stmt = $conn->prepare("SELECT * FROM equipment WHERE status = 'Available' AND category = ? ORDER BY distance_km ASC, equipment_id DESC");
            $cat_stmt->bind_param("s", $detected_fallback_category);
            $cat_stmt->execute();
            $cat_result = $cat_stmt->get_result();

            while ($row = $cat_result->fetch_assoc()) {
                $raw_collected_items[] = $row;
            }
            $cat_stmt->close();
        }
    }

    // Step D: Process collected items into Location Priority sections
    $nearby_equipment = [];
    $other_equipment = [];

    foreach ($raw_collected_items as $row) {
        $service_loc = trim($row['service_location'] ?? '');
        $is_nearby = false;
        if (!empty($primary_user_location) && !empty($service_loc)) {
            if (stripos($service_loc, $primary_user_location) !== false) {
                $is_nearby = true;
            }
        }

        if ($is_nearby) {
            $nearby_equipment[] = $row;
        } else {
            $other_equipment[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('search_results_for'); ?>: <?php echo htmlspecialchars($search_query); ?> - Agriculture Equipment Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; color: #333; margin: 0; }
        
        .sidebar { width: 250px; background: #fff; min-height: 100vh; padding: 20px; border-right: 1px solid #e0e0e0; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: bold; color: #198754; font-size: 15px; margin-bottom: 30px; }
        .logo i { font-size: 24px; color: #198754; }
        .nav-list { list-style: none; padding-left: 0; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #64748b; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 14px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #198754; color: #fff; }

        .main-content { flex: 1; padding: 20px 30px; }
        .top-search-bar { background: #fff; padding: 15px 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        
        .section-title { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 30px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        .equipment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .equipment-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s; }
        .equipment-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        
        .card-img-container { height: 180px; width: 100%; background: #f1f5f9; position: relative; }
        .card-img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        .card-body-content { padding: 15px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .equipment-title { font-size: 16px; font-weight: bold; color: #0f172a; margin-bottom: 0; }
        .meta-text { font-size: 13px; color: #64748b; }
        .price-tag { font-size: 16px; font-weight: bold; color: #198754; margin-top: auto; }
        
        .card-footer-actions { padding: 12px 15px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; }
        .btn-view { background: #6c757d; color: #fff; flex: 1; font-weight: 600; font-size: 12px; border-radius: 6px; padding: 8px 4px; text-align: center; text-decoration: none; display: inline-block; }
        .btn-view:hover { background: #5c636a; color: #fff; }
        
        .btn-rent-now { background: #198754; color: #fff; flex: 1; font-weight: 600; font-size: 12px; border-radius: 6px; padding: 8px 4px; text-align: center; text-decoration: none; display: inline-block; }
        .btn-rent-now:hover { background: #157347; color: #fff; }

        .empty-state { text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 15px; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-tractor"></i>
            <span>AGRICULTURE<br><small style="font-size: 9px; color: #64748b;"><?php echo __('equipment_rental'); ?></small></span>
        </div>
        <ul class="nav-list">
            <li class="nav-item"><a href="renter_dashboard.php<?php echo !empty($lang_param)?'?lang='.urlencode($current_lang):''; ?>" class="nav-link"><i class="fa-solid fa-chart-line"></i> <?php echo __('dashboard'); ?></a></li>
            <li class="nav-item"><a href="my_rentals.php<?php echo !empty($lang_param)?'?lang='.urlencode($current_lang):''; ?>" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> <?php echo __('my_bookings'); ?></a></li>
            <li class="nav-item"><a href="profile.php<?php echo !empty($lang_param)?'?lang='.urlencode($current_lang):''; ?>" class="nav-link"><i class="regular fa-user"></i> <?php echo __('profile'); ?></a></li>
            <li class="nav-item" style="margin-top: 20px;"><a href="logout.php" class="nav-link" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <?php echo __('logout'); ?></a></li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        
        <!-- Search Bar with Language Selector -->
        <div class="top-search-bar">
            <form action="search_equipment.php" method="GET" class="d-flex w-100 gap-2 align-items-center">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="q" id="searchInput" class="form-control border-start-0" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="<?php echo __('search_placeholder'); ?>" required>
                </div>
                
                <!-- Language Dropdown Menu -->
                <select name="lang" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                    <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                    <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                </select>

                <button type="submit" class="btn text-white px-4" style="background-color: #198754;"><?php echo __('search'); ?></button>
            </form>
        </div>

        <!-- Search Header & Fallback Notice -->
        <div class="mb-4">
            <h4><?php echo __('search_results_for'); ?>: <span class="text-success">"<?php echo htmlspecialchars($search_query); ?>"</span></h4>
            <p class="text-muted mb-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo __('registered_location'); ?>: <strong><?php echo htmlspecialchars($user_address); ?></strong></p>
            
            <?php if ($search_mode === 'fallback' && !empty($detected_fallback_category)): ?>
                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 d-inline-flex align-items-center gap-2" style="font-size: 14px;">
                    <i class="fa-solid fa-circle-info text-warning"></i>
                    <span><?php echo __('specific_item_not_available'); ?> <strong><?php echo htmlspecialchars($detected_fallback_category); ?></strong>.</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($search_query)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-keyboard"></i>
                <h5><?php echo __('enter_keyword_prompt'); ?></h5>
            </div>
        <?php elseif (empty($nearby_equipment) && empty($other_equipment)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h5><?php echo __('no_equipment_found'); ?> "<?php echo htmlspecialchars($search_query); ?>"</h5>
                <p class="text-muted"><?php echo __('try_different_keyword'); ?></p>
                <a href="renter_dashboard.php<?php echo !empty($lang_param)?'?lang='.urlencode($current_lang):''; ?>" class="btn btn-outline-secondary mt-2"><?php echo __('back_to_dashboard'); ?></a>
            </div>
        <?php else: ?>

            <!-- SECTION 1: Equipment Near You -->
            <?php if (!empty($nearby_equipment)): ?>
                <div class="section-title">
                    <i class="fa-solid fa-map-pin text-danger"></i> <?php echo __('equipment_near_you'); ?> (<?php echo htmlspecialchars($primary_user_location); ?>)
                </div>
                <div class="equipment-grid">
                    <?php foreach ($nearby_equipment as $eq): ?>
                        <?php 
                            $img_path = !empty($eq['image']) ? 'uploads/' . $eq['image'] : '';
                            $has_valid_img = !empty($eq['image']) && file_exists(__DIR__ . '/' . $img_path);
                        ?>
                        <div class="equipment-card">
                            <div class="card-img-container">
                                <?php if ($has_valid_img): ?>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Equipment Image">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                        <i class="fa-solid fa-tractor fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="equipment-title"><?php echo htmlspecialchars($eq['title']); ?></h5>
                                    <span class="badge bg-success" style="font-size: 10px;"><?php echo __('available_status_label'); ?></span>
                                </div>
                                <div class="meta-text"><i class="fa-solid fa-tag me-1"></i> <?php echo htmlspecialchars($eq['category']); ?> <?php echo !empty($eq['brand_model']) ? ' | ' . htmlspecialchars($eq['brand_model']) : ''; ?></div>
                                <div class="meta-text text-danger fw-semibold"><i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($eq['service_location']); ?> <?php echo !empty($eq['distance_km']) ? '(' . htmlspecialchars($eq['distance_km']) . ' km)' : ''; ?></div>
                                <div class="meta-text text-warning"><i class="fa-solid fa-star"></i> <?php echo number_format($eq['rating'] ?? 0, 1); ?> (<?php echo intval($eq['rating_count'] ?? 0); ?>)</div>
                                <div class="price-tag">₹<?php echo number_format($eq['price_per_day'], 2); ?> <small class="text-muted fw-normal" style="font-size: 11px;"><?php echo __('per_day'); ?></small></div>
                            </div>
                            <div class="card-footer-actions">
                                <a href="equipment_details.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param)?'&lang='.urlencode($current_lang):''; ?>" class="btn-view"><?php echo __('view_equipment'); ?></a>
                                <a href="rent_now.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param)?'&lang='.urlencode($current_lang):''; ?>" class="btn-rent-now"><i class="fa-solid fa-calendar-check me-1"></i> Rent Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- SECTION 2: Other Equipment -->
            <?php if (!empty($other_equipment)): ?>
                <div class="section-title">
                    <i class="fa-solid fa-globe text-secondary"></i> <?php echo __('other_equipment'); ?>
                </div>
                <div class="equipment-grid">
                    <?php foreach ($other_equipment as $eq): ?>
                        <?php 
                            $img_path = !empty($eq['image']) ? 'uploads/' . $eq['image'] : '';
                            $has_valid_img = !empty($eq['image']) && file_exists(__DIR__ . '/' . $img_path);
                        ?>
                        <div class="equipment-card">
                            <div class="card-img-container">
                                <?php if ($has_valid_img): ?>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Equipment Image">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                        <i class="fa-solid fa-tractor fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body-content">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="equipment-title"><?php echo htmlspecialchars($eq['title']); ?></h5>
                                    <span class="badge bg-success" style="font-size: 10px;"><?php echo __('available_status_label'); ?></span>
                                </div>
                                <div class="meta-text"><i class="fa-solid fa-tag me-1"></i> <?php echo htmlspecialchars($eq['category']); ?> <?php echo !empty($eq['brand_model']) ? ' | ' . htmlspecialchars($eq['brand_model']) : ''; ?></div>
                                <div class="meta-text"><i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($eq['service_location']); ?> <?php echo !empty($eq['distance_km']) ? '(' . htmlspecialchars($eq['distance_km']) . ' km)' : ''; ?></div>
                                <div class="meta-text text-warning"><i class="fa-solid fa-star"></i> <?php echo number_format($eq['rating'] ?? 0, 1); ?> (<?php echo intval($eq['rating_count'] ?? 0); ?>)</div>
                                <div class="price-tag">₹<?php echo number_format($eq['price_per_day'], 2); ?> <small class="text-muted fw-normal" style="font-size: 11px;"><?php echo __('per_day'); ?></small></div>
                            </div>
                            <div class="card-footer-actions">
                                <a href="equipment_details.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param)?'&lang='.urlencode($current_lang):''; ?>" class="btn-view"><?php echo __('view_equipment'); ?></a>
                                <a href="rent_now.php?id=<?php echo $eq['equipment_id']; ?><?php echo !empty($lang_param)?'&lang='.urlencode($current_lang):''; ?>" class="btn-rent-now"><i class="fa-solid fa-calendar-check me-1"></i> Rent Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>