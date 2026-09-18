<?php
session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','hi','kn'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';
require_once 'includes/config.php';

// Protect Page: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Equipment Details using exact columns
$stmt = $conn->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    /*
     * Renter -> renter dashboard
     * Lender -> My Equipment
     */
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'renter') {
        header("Location: renter_dashboard.php");
    } else {
        header("Location: my_equipment.php");
    }
    exit();
}

$eq = $result->fetch_assoc();
$is_owner = ($eq['lender_id'] == $user_id);

// Use live review data instead of relying only on stored rating columns.
$review_avg = 0.0;
$review_count = 0;
$rating_stmt = $conn->prepare("SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE equipment_id = ?");
if ($rating_stmt) {
    $rating_stmt->bind_param('i', $equipment_id);
    $rating_stmt->execute();
    $rating_data = $rating_stmt->get_result()->fetch_assoc();
    $review_avg = (float)($rating_data['avg_rating'] ?? 0);
    $review_count = (int)($rating_data['review_count'] ?? 0);
    $rating_stmt->close();
}
$eq['rating'] = $review_avg;
$eq['rating_count'] = $review_count;

$customer_reviews = [];
$reviews_stmt = $conn->prepare("SELECT rv.rating, rv.review_text, rv.created_at, u.full_name AS renter_name, b.request_code FROM reviews rv LEFT JOIN users u ON rv.renter_id = u.user_id LEFT JOIN bookings b ON rv.booking_id = b.booking_id WHERE rv.equipment_id = ? ORDER BY rv.created_at DESC");
if ($reviews_stmt) {
    $reviews_stmt->bind_param('i', $equipment_id);
    $reviews_stmt->execute();
    $rr = $reviews_stmt->get_result();
    while ($r = $rr->fetch_assoc()) { $customer_reviews[] = $r; }
    $reviews_stmt->close();
}

/*
 * Renter -> renter dashboard
 * Lender -> My Equipment
 */
$back_page = (
    isset($_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'renter'
)
    ? 'renter_dashboard.php'
    : 'my_equipment.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($eq['title'] ?? __('equipment_details_title')); ?> - Agriculture Equipment Rental</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        
        .details-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .top-banner { background: #0e7490; color: white; padding: 15px 20px; font-weight: bold; font-size: 18px; display: flex; justify-content: space-between; align-items: center; }
        
        .img-preview-container { height: 350px; width: 100%; background: #f1f5f9; position: relative; border-bottom: 1px solid #e2e8f0; }
        .img-preview-container img { width: 100%; height: 100%; object-fit: cover; }
        
        .content-body { padding: 30px; }
        .equipment-title { font-size: 26px; font-weight: bold; color: #0f172a; margin-bottom: 20px; }
        
        .specs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .spec-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 15px; border-radius: 8px; }
        .spec-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 4px; }
        .spec-value { font-size: 15px; font-weight: 600; color: #1e293b; }
        
        .price-highlight { color: #0e7490; font-size: 18px; font-weight: bold; }
        
        .badge-status { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .status-available { background: #dcfce7; color: #166534; }
        .status-rented { background: #dbeafe; color: #1e40af; }

        .reviews-section { margin-top:25px; padding-top:25px; border-top:1px solid #e2e8f0; }
        .review-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:15px; margin-top:12px; }
        .review-stars { color:#f59e0b; letter-spacing:1px; }
        .reviewer-name { font-weight:800; color:#0f172a; }
        .review-date { font-size:12px; color:#64748b; }
        .review-text { margin-top:7px; color:#334155; line-height:1.5; white-space:pre-wrap; }
        .action-footer { padding: 15px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .lang-select { padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: #fff; cursor: pointer; }
    </style>
</head>
<body>

    <div class="details-container">
        <div class="top-banner">
            <span><i class="fa-solid fa-circle-info me-2"></i> <?php echo __('equipment_details_title'); ?></span>
            <div class="d-flex align-items-center gap-2">
                <select class="lang-select text-dark" onchange="location = this.value;">
                    <option value="equipment_details.php?id=<?php echo $equipment_id; ?>&lang=en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>🌐 English</option>
                    <option value="equipment_details.php?id=<?php echo $equipment_id; ?>&lang=hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>🌐 हिन्दी</option>
                    <option value="equipment_details.php?id=<?php echo $equipment_id; ?>&lang=kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ</option>
                </select>
                <a href="<?php echo $back_page; ?>" class="btn btn-sm btn-light fw-bold text-dark"><i class="fa-solid fa-arrow-left me-1"></i> <?php echo __('back_to_my_equipment'); ?></a>
            </div>
        </div>

        <div class="img-preview-container">
            <?php 
                $img_path = !empty($eq['image']) ? 'uploads/' . htmlspecialchars($eq['image']) : '';
                $has_valid_img = !empty($eq['image']) && file_exists($img_path);
            ?>
            <?php if ($has_valid_img): ?>
                <img src="<?php echo $img_path; ?>" alt="Equipment Image">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <i class="fa-solid fa-tractor fa-3x"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-body">
            <h1 class="equipment-title"><?php echo htmlspecialchars($eq['title'] ?? __('unnamed_equipment')); ?></h1>

            <div class="specs-grid">
                <div class="spec-box">
                    <div class="spec-label"><?php echo __('category_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['category'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="spec-box">
                    <div class="spec-label"><?php echo __('brand_model_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['brand_model'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('power_hp_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['power_hp'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('drive_type_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['drive_type'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('model_year_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['model_year'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('fuel_type_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['fuel_type'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('working_width_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['working_width'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('equipment_condition_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['equipment_condition'] ?? 'N/A'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('price_per_day_spec'); ?></div>
                    <div class="spec-value price-highlight">₹<?php echo number_format($eq['price_per_day'] ?? 0, 2); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('min_booking_days_spec'); ?></div>
                    <div class="spec-value"><?php echo htmlspecialchars($eq['min_booking_days'] ?? 1); ?> <?php echo __('days_label'); ?></div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('service_location_spec'); ?></div>
                    <div class="spec-value">
                        <?php echo htmlspecialchars($eq['service_location'] ?? 'N/A'); ?> 
                        <?php if (!empty($eq['distance_km'])): ?>
                            <small class="text-muted">(<?php echo htmlspecialchars($eq['distance_km']); ?> km)</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('status_featured_spec'); ?></div>
                    <div class="spec-value">
                        <span class="badge-status <?php echo (strtolower($eq['status'] ?? '') === 'rented') ? 'status-rented' : 'status-available'; ?>">
                            <?php echo htmlspecialchars(ucfirst($eq['status'] ?? 'Available')); ?>
                        </span>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('rating_spec'); ?></div>
                    <div class="spec-value text-warning">
                        <i class="fa-solid fa-star"></i> <?php echo number_format($eq['rating'] ?? 0, 1); ?> / 5 
                        <span class="text-muted fs-6 fw-normal">(<?php echo intval($eq['rating_count'] ?? 0); ?> <?php echo __('reviews_label'); ?>)</span>
                    </div>
                </div>

                <div class="spec-box">
                    <div class="spec-label"><?php echo __('created_date_spec'); ?></div>
                    <div class="spec-value fs-6"><?php echo htmlspecialchars($eq['created_at'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <div class="spec-box w-100">
                <div class="spec-label"><?php echo __('description_spec'); ?></div>
                <div class="spec-value fw-normal text-muted mt-1"><?php echo nl2br(htmlspecialchars($eq['description'] ?? __('no_description_provided'))); ?></div>
            </div>

            <div class="reviews-section">
                <h3 class="h5 fw-bold mb-1"><i class="fa-solid fa-star text-warning me-2"></i><?php echo __('customer_reviews'); ?></h3>
                <div class="text-muted fw-semibold mb-3"><?php echo number_format($eq['rating'],1); ?>/5 · <?php echo $review_count; ?> <?php echo __('reviews_label'); ?></div>
                <?php if (empty($customer_reviews)): ?>
                    <div class="text-muted fw-semibold"><?php echo __('no_reviews_yet'); ?></div>
                <?php else: ?>
                    <?php foreach ($customer_reviews as $cr): ?>
                        <div class="review-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="reviewer-name"><?php echo htmlspecialchars($cr['renter_name'] ?? 'Customer'); ?></div>
                                    <div class="review-stars"><?php echo str_repeat('★',(int)$cr['rating']); ?><span style="color:#cbd5e1"><?php echo str_repeat('★',5-(int)$cr['rating']); ?></span></div>
                                </div>
                                <div class="review-date"><?php echo htmlspecialchars(date('d M Y', strtotime($cr['created_at']))); ?></div>
                            </div>
                            <div class="review-text"><?php echo nl2br(htmlspecialchars($cr['review_text'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="action-footer">
            <a href="<?php echo $back_page; ?>" class="btn btn-secondary btn-sm px-4"><i class="fa-solid fa-arrow-left me-1"></i> <?php echo __('back_btn'); ?></a>
            <?php if ($is_owner): ?>
                <a href="edit_equipment.php?id=<?php echo $eq['equipment_id']; ?>" class="btn btn-warning btn-sm px-4 fw-bold text-dark" style="background-color: #f59e0b; border-color: #d97706;"><i class="fa-solid fa-pen-to-square me-1"></i> <?php echo __('edit_equipment_btn'); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>