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
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Existing Equipment Data
$stmt = $conn->prepare("SELECT * FROM equipment WHERE equipment_id = ? AND lender_id = ?");
$stmt->bind_param("ii", $equipment_id, $lender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: my_equipment.php");
    exit();
}

$eq = $result->fetch_assoc();
$error = '';
$success = '';

// Check if redirected with success message
if (isset($_GET['msg']) && $_GET['msg'] === 'updated') {
    $success = __('equipment_updated_success');
}

// Handle Form Submission for Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title             = trim($_POST['title'] ?? '');
    $category          = trim($_POST['category'] ?? '');
    $status            = trim($_POST['status'] ?? 'Available');
    $brand_model       = trim($_POST['brand_model'] ?? '');
    $power_hp          = trim($_POST['power_hp'] ?? '');
    $drive_type        = trim($_POST['drive_type'] ?? '');
    $model_year        = trim($_POST['model_year'] ?? '');
    $fuel_type         = trim($_POST['fuel_type'] ?? '');
    $wrorking_width    = trim($_POST['wrorking_width'] ?? '');
    $equipment_condition = trim($_POST['equipment_condition'] ?? '');
    $price_per_day     = floatval($_POST['price_per_day'] ?? 0);
    $min_booking_days  = intval($_POST['min_booking_days'] ?? 1);
    $service_location  = trim($_POST['service_location'] ?? '');
    $distance_km       = floatval($_POST['distance_km'] ?? 0);
    $description       = trim($_POST['description'] ?? '');
    $is_featured       = isset($_POST['is_featured']) ? 1 : 0;

    $image_name = $eq['image']; // Keep existing image by default

    // Handle Image Upload if a new one is selected
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_dir = 'uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $image_name = $file_name;
        }
    }

    $update_stmt = $conn->prepare("UPDATE equipment SET title=?, category=?, status=?, brand_model=?, power_hp=?, drive_type=?, model_year=?, fuel_type=?, working_width=?, equipment_condition=?, price_per_day=?, min_booking_days=?, service_location=?, distance_km=?, description=?, image=?, is_featured=? WHERE equipment_id=? AND lender_id=?");
    
    $update_stmt->bind_param(
        "ssssssssssdisdssiii",
        $title, $category, $status, $brand_model, $power_hp, $drive_type, $model_year, $fuel_type, $wrorking_width, $equipment_condition, $price_per_day, $min_booking_days, $service_location, $distance_km, $description, $image_name, $is_featured, $equipment_id, $lender_id
    );

    if ($update_stmt->execute()) {
        // Redirect back to edit page with success message
        header("Location: edit_equipment.php?id=" . $equipment_id . "&msg=updated");
        exit();
    } else {
        $error = "Error updating equipment: " . $conn->error;
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo __('edit_equipment_title'); ?> - Agriculture Equipment Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .form-container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .top-banner { background: #0e7490; color: white; padding: 15px 20px; font-weight: bold; font-size: 18px; display: flex; justify-content: space-between; align-items: center; }
        .form-body { padding: 30px; }
        .lang-select { padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: #fff; cursor: pointer; }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="top-banner">
            <span><i class="fa-solid fa-pen-to-square me-2"></i> <?php echo __('edit_equipment_title'); ?></span>
            <div class="d-flex align-items-center gap-2">
                <select class="lang-select text-dark" onchange="location = this.value;">
                    <option value="edit_equipment.php?id=<?php echo $equipment_id; ?>&lang=en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>🌐 English</option>
                    <option value="edit_equipment.php?id=<?php echo $equipment_id; ?>&lang=hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>🌐 हिन्दी</option>
                    <option value="edit_equipment.php?id=<?php echo $equipment_id; ?>&lang=kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ</option>
                </select>
                <a href="equipment_details.php?id=<?php echo $equipment_id; ?>" class="btn btn-sm btn-light fw-bold text-dark"><i class="fa-solid fa-arrow-left me-1"></i> <?php echo __('back_to_details'); ?></a>
            </div>
        </div>

        <div class="form-body">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="edit_equipment.php?id=<?php echo $equipment_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold"><?php echo __('equipment_title_label'); ?></label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($eq['title'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('status_label'); ?></label>
                        <select name="status" class="form-select">
                            <option value="Available" <?php echo (strtolower($eq['status'] ?? '') === 'available') ? 'selected' : ''; ?>><?php echo __('available_status'); ?></option>
                            <option value="Rented" <?php echo (strtolower($eq['status'] ?? '') === 'rented') ? 'selected' : ''; ?>><?php echo __('rented_status'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo __('category_label'); ?></label>
                        <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($eq['category'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo __('brand_model_label'); ?></label>
                        <input type="text" name="brand_model" class="form-control" value="<?php echo htmlspecialchars($eq['brand_model'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('power_hp_label'); ?></label>
                        <input type="text" name="power_hp" class="form-control" value="<?php echo htmlspecialchars($eq['power_hp'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('drive_type_label'); ?></label>
                        <input type="text" name="drive_type" class="form-control" value="<?php echo htmlspecialchars($eq['drive_type'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('model_year_label'); ?></label>
                        <input type="text" name="model_year" class="form-control" value="<?php echo htmlspecialchars($eq['model_year'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('fuel_type_label'); ?></label>
                        <input type="text" name="fuel_type" class="form-control" value="<?php echo htmlspecialchars($eq['fuel_type'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('working_width_label'); ?></label>
                        <input type="text" name="wrorking_width" class="form-control" value="<?php echo htmlspecialchars($eq['wrorking_width'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><?php echo __('equipment_condition_label'); ?></label>
                        <input type="text" name="equipment_condition" class="form-control" value="<?php echo htmlspecialchars($eq['equipment_condition'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo __('price_per_day_label'); ?></label>
                        <input type="number" step="0.01" name="price_per_day" class="form-control" value="<?php echo htmlspecialchars($eq['price_per_day'] ?? 0); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo __('min_booking_days_label'); ?></label>
                        <input type="number" name="min_booking_days" class="form-control" value="<?php echo htmlspecialchars($eq['min_booking_days'] ?? 1); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo __('service_location_label'); ?></label>
                        <input type="text" name="service_location" class="form-control" value="<?php echo htmlspecialchars($eq['service_location'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><?php echo __('distance_km_label'); ?></label>
                        <input type="number" step="0.1" name="distance_km" class="form-control" value="<?php echo htmlspecialchars($eq['distance_km'] ?? 0); ?>">
                    </div>
                </div>

                <!-- RECOMMENDED / FEATURED CHECKBOX OPTION -->
                <div class="mb-3 form-check p-3 bg-light rounded border">
                    <input type="checkbox" class="form-check-input ms-0 me-2" id="is_featured" name="is_featured" value="1" <?php echo (!empty($eq['is_featured']) && $eq['is_featured'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label fw-bold text-dark" for="is_featured"><?php echo __('mark_featured_label'); ?></label>
                    <div class="form-text"><?php echo __('mark_featured_desc'); ?></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"><?php echo __('update_image_label'); ?></label>
                    <input type="file" name="image" class="form-control">
                    <?php if (!empty($eq['image'])): ?>
                        <div class="form-text"><?php echo __('current_file_label'); ?><?php echo htmlspecialchars($eq['image']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold"><?php echo __('description_label'); ?></label>
                    <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($eq['description'] ?? ''); ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="equipment_details.php?id=<?php echo $equipment_id; ?>" class="btn btn-secondary px-4"><?php echo __('cancel_btn'); ?></a>
                    <button type="submit" class="btn text-white px-4" style="background-color: #0e7490;"><i class="fa-solid fa-floppy-disk me-1"></i> <?php echo __('save_changes_btn'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>