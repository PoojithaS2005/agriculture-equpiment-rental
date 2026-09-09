<?php
session_start();

require_once 'includes/lang.php';
require_once 'includes/config.php';

// Protect Page: Ensure user is logged in as Lender
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'lender') {
    header("Location: login.php");
    exit();
}

$lender_id = $_SESSION['user_id'];
$lender_name = $_SESSION['full_name'] ?? 'Lender';

// Handle Deletion via GET request securely with Prepared Statements
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $equipment_id_to_delete = intval($_GET['id']);
    
    $del_stmt = $conn->prepare("DELETE FROM equipment WHERE equipment_id = ? AND lender_id = ?");
    $del_stmt->bind_param("ii", $equipment_id_to_delete, $lender_id);
    if ($del_stmt->execute()) {
        header("Location: my_equipment.php?msg=deleted");
        exit();
    }
    $del_stmt->close();
}

// Fetch Real Equipment Belonging ONLY to Logged-in Lender
$equip_stmt = $conn->prepare("SELECT * FROM equipment WHERE lender_id = ? ORDER BY equipment_id DESC");
$equip_stmt->bind_param("i", $lender_id);
$equip_stmt->execute();
$equip_result = $equip_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('my_equipment_title'); ?> - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; color: #333; margin: 0; }
        
        .sidebar { width: 250px; background: #fff; min-height: 100vh; padding: 20px; border-right: 1px solid #e0e0e0; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: bold; color: #1e3a8a; font-size: 15px; margin-bottom: 30px; }
        .logo i { font-size: 24px; color: #0e7490; }
        
        .nav-list { list-style: none; padding-left: 0; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #64748b; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 14px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #0e7490; color: #fff; }

        .main-content { flex: 1; padding: 20px 30px; }
        .top-banner { background: #0e7490; color: white; padding: 12px 20px; font-weight: bold; border-radius: 8px; margin-bottom: 25px; letter-spacing: 1px; font-size: 18px; display: flex; justify-content: space-between; align-items: center; }
        
        .equipment-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .equipment-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s; }
        .equipment-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        
        .card-img-container { height: 180px; width: 100%; background: #f1f5f9; position: relative; }
        .card-img-container img { width: 100%; height: 100%; object-fit: cover; }
        
        .card-body-content { padding: 15px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .equipment-title { font-size: 16px; font-weight: bold; color: #0f172a; margin-bottom: 0; }
        .meta-text { font-size: 13px; color: #64748b; }
        .price-tag { font-size: 16px; font-weight: bold; color: #0e7490; margin-top: auto; }
        
        .card-footer-actions { padding: 12px 15px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; justify-content: space-between; }
        .btn-action { padding: 6px 10px; font-size: 12px; font-weight: 600; border-radius: 6px; text-decoration: none; text-align: center; flex: 1; }
        .btn-view { background: #e0f2fe; color: #0284c7; }
        .btn-view:hover { background: #bae6fd; color: #0369a1; }
        .btn-edit { background: #fef3c7; color: #d97706; }
        .btn-edit:hover { background: #fde68a; color: #b45309; }
        .btn-remove { background: #fee2e2; color: #dc2626; }
        .btn-remove:hover { background: #fecaca; color: #b91c1c; }

        .empty-state { text-align: center; padding: 50px 20px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 20px; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 15px; }
        .badge-status { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
        .status-available { background: #dcfce7; color: #166534; }
        .status-rented { background: #dbeafe; color: #1e40af; }
        .lang-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: #fff; cursor: pointer; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-tractor"></i>
            <span>AGRICULTURE<br><small style="font-size: 9px; color: #64748b;">EQUIPMENT RENTAL</small></span>
        </div>
        <ul class="nav-list">
            <li class="nav-item"><a href="lender_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> <?php echo __('dashboard'); ?></a></li>
            <li class="nav-item"><a href="add_item.php" class="nav-link"><i class="fa-solid fa-circle-plus"></i> <?php echo __('add_equipment'); ?></a></li>
            <li class="nav-item"><a href="my_equipment.php" class="nav-link active"><i class="fa-solid fa-list"></i> <?php echo __('my_equipment_title'); ?></a></li>
            <li class="nav-item"><a href="rental_requests.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left"></i> <?php echo __('rental_requests'); ?></a></li>
            <li class="nav-item"><a href="active_rentals.php" class="nav-link"><i class="fa-solid fa-truck-ramp-box"></i> <?php echo __('active_rentals'); ?></a></li>
            <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fa-regular fa-user"></i> <?php echo __('my_profile'); ?></a></li>
            <li class="nav-item" style="margin-top: 20px;"><a href="logout.php" class="nav-link" style="color: #ef4444;"><i class="fa-solid fa-right-from-bracket"></i> <?php echo __('logout'); ?></a></li>
        </ul>
    </div>

    <div class="main-content">
        <!-- Top banner with Language Selector dropdown included -->
        <div class="top-banner">
            <span><i class="fa-solid fa-list me-2"></i> <?php echo __('my_equipment_inventory'); ?></span>
            <select class="lang-select text-dark" onchange="location = this.value;">
                <option value="my_equipment.php?lang=en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>🌐 English</option>
                <option value="my_equipment.php?lang=hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>🌐 हिन्दी (Hindi)</option>
                <option value="my_equipment.php?lang=kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
            </select>
        </div>

        <?php if ($equip_result && $equip_result->num_rows > 0): ?>
            <div class="equipment-grid">
                <?php while ($row = $equip_result->fetch_assoc()): ?>
                    <?php 
                        $img_path = !empty($row['image']) ? 'uploads/' . htmlspecialchars($row['image']) : '';
                        $has_valid_img = !empty($row['image']) && file_exists($img_path);
                    ?>
                    <div class="equipment-card">
                        <div class="card-img-container">
                            <?php if ($has_valid_img): ?>
                                <img src="<?php echo $img_path; ?>" alt="Equipment Image">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                    <i class="fa-solid fa-tractor fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <h4 class="equipment-title"><?php echo htmlspecialchars($row['title'] ?? __('unnamed_equipment')); ?></h4>
                                <span class="badge-status <?php echo (isset($row['status']) && strtolower($row['status']) === 'rented') ? 'status-rented' : 'status-available'; ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['status'] ?? 'Available')); ?>
                                </span>
                            </div>
                            
                            <!-- USING brand_model COLUMN -->
                            <div class="meta-text">
                                <i class="fa-solid fa-tag me-1"></i> 
                                <?php echo htmlspecialchars($row['category'] ?? __('uncategorized')); ?> 
                                <?php echo !empty($row['brand_model']) ? ' | ' . htmlspecialchars($row['brand_model']) : ''; ?>
                            </div>
                            
                            <div class="meta-text">
                                <i class="fa-solid fa-location-dot me-1"></i> 
                                <?php echo htmlspecialchars($row['service_location'] ?? __('location_not_specified')); ?>
                            </div>
                            
                            <div class="meta-text">
                                <i class="fa-solid fa-circle-info me-1"></i> <?php echo __('condition'); ?>: 
                                <?php echo htmlspecialchars($row['equipment_condition'] ?? 'N/A'); ?>
                            </div>
                            
                            <div class="price-tag">
                                ₹<?php echo number_format($row['price_per_day'] ?? 0, 2); ?> <small class="text-muted fw-normal"><?php echo __('per_day'); ?></small>
                            </div>
                        </div>
                        <div class="card-footer-actions">
                            <a href="equipment_details.php?id=<?php echo $row['equipment_id']; ?>" class="btn-action btn-view"><i class="fa-solid fa-eye"></i> <?php echo __('view'); ?></a>
                            <a href="edit_equipment.php?id=<?php echo $row['equipment_id']; ?>" class="btn-action btn-edit"><i class="fa-solid fa-pen-to-square"></i> <?php echo __('edit'); ?></a>
                            <a href="my_equipment.php?action=delete&id=<?php echo $row['equipment_id']; ?>" class="btn-action btn-remove" onclick="return confirm('<?php echo __('confirm_remove_equipment'); ?>');"><i class="fa-solid fa-trash"></i> <?php echo __('remove'); ?></a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3><?php echo __('no_equipment_added'); ?></h3>
                <a href="add_item.php" class="btn px-4 py-2 text-white mt-2" style="background-color: #0e7490;"><i class="fa-solid fa-circle-plus me-2"></i> <?php echo __('add_equipment'); ?></a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>