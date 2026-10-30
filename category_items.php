<?php
session_start();

/*
|--------------------------------------------------------------------------
| UPDATE SESSION LANGUAGE BEFORE LOADING LANG FILE
|--------------------------------------------------------------------------
*/
if (isset($_GET['lang'])) {
    $allowed_languages = ['en', 'kn', 'hi'];
    if (in_array($_GET['lang'], $allowed_languages, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

require_once __DIR__ . '/includes/lang.php';

if (file_exists(__DIR__ . '/includes/config.php')) {
    include(__DIR__ . '/includes/config.php');
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_lang = $_SESSION['lang'] ?? 'en';

/*
|--------------------------------------------------------------------------
| GET CATEGORY ID
|--------------------------------------------------------------------------
*/

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fallback: if category_id is missing but category name is available
if ($category_id <= 0 && !empty($_GET['category'])) {
    $category_name_from_url = mysqli_real_escape_string($conn, $_GET['category']);

    $fallback_query = "SELECT category_id 
                       FROM categories 
                       WHERE category_name = ?
                       LIMIT 1";

    $fallback_stmt = mysqli_prepare($conn, $fallback_query);

    if ($fallback_stmt) {
        mysqli_stmt_bind_param($fallback_stmt, "s", $category_name_from_url);
        mysqli_stmt_execute($fallback_stmt);
        $fallback_result = mysqli_stmt_get_result($fallback_stmt);

        if ($fallback_result && mysqli_num_rows($fallback_result) > 0) {
            $fallback_row = mysqli_fetch_assoc($fallback_result);
            $category_id = (int)$fallback_row['category_id'];
        }

        mysqli_stmt_close($fallback_stmt);
    }
}

/*
|--------------------------------------------------------------------------
| GET CATEGORY NAME
|--------------------------------------------------------------------------
*/

$category = '';

if ($category_id > 0) {

    $category_query = "SELECT category_name 
                       FROM categories 
                       WHERE category_id = ?";

    $category_stmt = mysqli_prepare($conn, $category_query);

    if ($category_stmt) {
        mysqli_stmt_bind_param($category_stmt, "i", $category_id);
        mysqli_stmt_execute($category_stmt);

        $category_result = mysqli_stmt_get_result($category_stmt);

        if ($category_result && mysqli_num_rows($category_result) > 0) {
            $category_row = mysqli_fetch_assoc($category_result);
            $category = $category_row['category_name'];
        }

        mysqli_stmt_close($category_stmt);
    }
}

/*
|--------------------------------------------------------------------------
| FETCH EQUIPMENT FOR THIS CATEGORY
|--------------------------------------------------------------------------
*/

$result = false;

if ($category_id > 0) {

    $query = "SELECT * 
              FROM equipment 
              WHERE category_id = ?
              ORDER BY equipment_id DESC";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
    }
}

/*
|--------------------------------------------------------------------------
| CATEGORY DESCRIPTION & BUTTON TRANSLATIONS
|--------------------------------------------------------------------------
*/

$category_description = 'Explore available equipment in this category.';
$view_details_label = 'View Details';
$day_label = 'day';

if ($current_lang == 'kn') {
    $category_description = 'ಈ ವರ್ಗದಲ್ಲಿ ಲಭ್ಯವಿರುವ ಕೃಷಿ ಉಪಕರಣಗಳನ್ನು ಹುಡುಕಿ ಮತ್ತು ಬಾಡಿಗೆಗೆ ಪಡೆಯಿರಿ.';
    $view_details_label = 'ವಿವರಗಳನ್ನು ವೀಕ್ಷಿಸಿ';
    $day_label = 'ದಿನ';
} elseif ($current_lang == 'hi') {
    $category_description = 'इस श्रेणी में उपलब्ध कृषि उपकरण देखें और किराए पर लें।';
    $view_details_label = 'विवरण देखें';
    $day_label = 'दिन';
}

$translated_description = __('category_items_desc', '');
if (!empty($translated_description) && $translated_description !== 'category_items_desc') {
    $category_description = $translated_description;
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang); ?>">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($category); ?> -
        <?php echo __('app_title', 'Agriculture Equipment Rental'); ?>
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* === SAME AS RENTER DASHBOARD & SEARCH EQUIPMENT - IMAGE 2 BOKEH GLASS === */
        :root {
            --brand-green: #2d6a4f;
            --glass: rgba(255,255,255,0.78);
            --border: rgba(255,255,255,0.9);
        }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 20% 25%, rgba(255,255,255,0.95) 0%, transparent 22%), radial-gradient(circle at 75% 35%, rgba(255,255,255,0.85) 0%, transparent 18%), radial-gradient(circle at 50% 65%, rgba(232,245,233,0.9) 0%, transparent 28%), linear-gradient(135deg,#eef6e8 0%, #f5f9ec 30%, #fdf8e7 65%, #f1f7e9 100%) !important;
            background-attachment: fixed !important;
            color: #1e3a2a;
        }
        .main-wrapper {
            margin-left: 250px;
            padding: 18px 28px;
            min-height: 100vh;
        }
        .top-navbar {
            background: rgba(255,255,255,0.80) !important;
            border: 1px solid var(--border) !important;
            border-radius: 18px !important;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04) !important;
        }
        .top-navbar .form-control{
            background: rgba(255,255,255,0.9) !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
            border-radius: 50rem !important;
            height: 40px;
        }
        .top-navbar .input-group-text{
            background: rgba(255,255,255,0.9) !important;
            border: 1px solid rgba(0,0,0,0.06) !important;
            border-radius: 50rem 0 0 50rem !important;
        }
        .eq-card {
            background: rgba(255,255,255,0.80) !important;
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
            overflow: hidden;
            transition: all 0.25s ease-in-out;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.04) !important;
        }

        .eq-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(0,0,0,0.08) !important;
            background: rgba(255,255,255,0.92) !important;
        }
        .eq-img-container {
            width: 100%;
            height: 220px;
            background: linear-gradient(145deg,rgba(224,248,231,.7),rgba(255,255,255,.8)) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-bottom: 1px solid rgba(0,0,0,0.04);
        }
        .eq-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            transition: 0.3s ease;
        }
        .eq-card:hover .eq-img-container img{ transform: scale(1.05); }
        .eq-card-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
        }
        .btn-agro{ background:#2e6b4f !important; color:#fff !important; border-radius:10px !important; border:none !important; }
        .btn-agro:hover{ background:#244f3a !important; color:#fff !important; }
        .btn-outline-secondary{ background:rgba(255,255,255,0.9) !important; border:1px solid rgba(0,0,0,0.08) !important; border-radius:10px !important; color:#1e3a2a !important; }
        .btn-outline-secondary:hover{ background:#2f8050 !important; color:#fff !important; border-color:#2f8050 !important; }
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 12px 15px;
            }
        }
    </style>

</head>

<body>

<?php include __DIR__ . '/renter_sidebar.php'; ?>

<div class="main-wrapper">

    <div class="top-navbar">
        <form action="search_equipment.php" method="GET" class="d-flex w-75 gap-2 align-items-center">
            <?php if (!empty($current_lang)): ?>
                <input type="hidden" name="lang" value="<?= htmlspecialchars($current_lang); ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="<?php echo __('search_placeholder', 'Search equipment...'); ?>" required>
            </div>
            <button type="submit" class="btn btn-sm text-white px-3" style="background-color: #2d6a4f; border-radius:50rem;">
                <?php echo __('search', 'Search'); ?>
            </button>
        </form>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border-radius:50rem; background:rgba(255,255,255,0.9) !important;">
                    <?php if ($current_lang == 'kn') { echo 'ಕನ್ನಡ'; } elseif ($current_lang == 'hi') { echo 'हिन्दी'; } else { echo 'English'; } ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="category_items.php?category_id=<?= $category_id; ?>&lang=en">English</a></li>
                    <li><a class="dropdown-item" href="category_items.php?category_id=<?= $category_id; ?>&lang=kn">ಕನ್ನಡ</a></li>
                    <li><a class="dropdown-item" href="category_items.php?category_id=<?= $category_id; ?>&lang=hi">हिन्दी</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                    <li class="breadcrumb-item"><a href="renter_dashboard.php?lang=<?= urlencode($current_lang); ?>" class="text-muted text-decoration-none"><?php echo __('home', 'Home'); ?></a></li>
                    <li class="breadcrumb-item"><a href="categories.php?lang=<?= urlencode($current_lang); ?>" class="text-muted text-decoration-none"><?php echo __('categories', 'Categories'); ?></a></li>
                    <li class="breadcrumb-item active text-success fw-semibold"><?php echo htmlspecialchars($category); ?></li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-1" style="color:#163a26; font-weight:800;"><?php echo htmlspecialchars($category); ?></h3>
            <p class="text-muted small mb-0"><?= htmlspecialchars($category_description); ?></p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($eq = mysqli_fetch_assoc($result)): ?>
                <?php
                $eq_id = $eq['equipment_id'] ?? 0;
                $raw_img = trim($eq['image'] ?? '');
                $imgSrc = '';
                $has_valid_img = false;
                if (!empty($raw_img)) {
                    if (file_exists(__DIR__ . '/uploads/' . $raw_img)) {
                        $imgSrc = 'uploads/' . $raw_img;
                        $has_valid_img = true;
                    } elseif (file_exists(__DIR__ . '/' . $raw_img)) {
                        $imgSrc = $raw_img;
                        $has_valid_img = true;
                    } elseif (file_exists(__DIR__ . '/uploads/equipment/' . $raw_img)) {
                        $imgSrc = 'uploads/equipment/' . $raw_img;
                        $has_valid_img = true;
                    } elseif (preg_match('/^https?:\/\//i', $raw_img)) {
                        $imgSrc = $raw_img;
                        $has_valid_img = true;
                    }
                }
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="eq-card">
                        <div class="eq-img-container">
                            <?php if ($has_valid_img): ?>
                                <img src="<?= htmlspecialchars($imgSrc); ?>" alt="<?= htmlspecialchars($eq['title'] ?? $eq['equipment_title'] ?? 'Equipment'); ?>">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="fa-solid fa-image fa-2x text-secondary opacity-50"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="eq-card-body">
                            <div>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($eq['title'] ?? $eq['equipment_title'] ?? 'Unnamed Equipment'); ?></h5>
                                <p class="text-muted small mb-2" style="font-size: 0.85rem;"><?= htmlspecialchars($eq['description'] ?? ''); ?></p>
                                <p class="text-secondary small mb-3 fw-semibold">📍 <?= htmlspecialchars($eq['service_location'] ?? 'Location not specified'); ?></p>
                            </div>
                            <div>
                                <div class="d-flex align-items-center justify-content-between pt-3 border-top mb-2">
                                    <span class="text-dark fw-bold">₹<?= number_format($eq['price_per_day'] ?? $eq['price'] ?? 0); ?> / <?= htmlspecialchars($day_label); ?></span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="equipment_details.php?equipment_id=<?= $eq_id; ?>&id=<?= $eq_id; ?>&from=category_items&category_id=<?= $category_id; ?>&lang=<?= urlencode($current_lang); ?>" class="btn btn-outline-secondary btn-sm w-50">
                                        <?= htmlspecialchars($view_details_label); ?> →
                                    </a>
                                    <a href="rent_now.php?equipment_id=<?= $eq_id; ?>&id=<?= $eq_id; ?>&lang=<?= urlencode($current_lang); ?>" class="btn btn-agro btn-sm w-100" style="background-color:#2d6a4f; color:white;">
                                        <i class="fa-solid fa-cart-shopping me-1"></i><?php echo __('rent_now', 'Rent Now'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center border py-4" style="border-radius:16px; background:rgba(255,255,255,0.8);">
                    <?php echo __('no_equipment_found','No equipment found in this category.'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentLang = "<?= $current_lang; ?>";
    const sidebarTranslations = {
        'kn': {'Dashboard': 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್','Search Equipment': 'ಉಪಕರಣಗಳನ್ನು ಹುಡುಕಿ','Categories': 'ವರ್ಗಗಳು','Notifications': 'ಅಧಿಸೂಚನೆಗಳು','Recommended': 'ಶಿಫಾರಸು ಮಾಡಲಾಗಿದೆ','My Bookings': 'ನನ್ನ ಬುಕಿಂಗ್‌ಗಳು','Rental History': 'ಬಾಡಿಗೆ ಇತಿಹಾಸ','My Profile': 'ನನ್ನ ಪ್ರೊಫೈಲ್','Logout': 'ನಿರ್ಗಮನ'},
        'hi': {'Dashboard': 'डैशबोर्ड','Search Equipment': 'उपकरण खोजें','Categories': 'श्रेणियां','Notifications': 'सूचनाएं','Recommended': 'अनुशंसित','My Bookings': 'मेरी बुकिंग','Rental History': 'किराए का इतिहास','My Profile': 'मेरी प्रोफाइल','Logout': 'लॉग आउट'}
    };
    if (sidebarTranslations[currentLang]) {
        const dict = sidebarTranslations[currentLang];
        const sidebarLinks = document.querySelectorAll('.renter-sidebar a span');
        sidebarLinks.forEach(span => {
            const originalText = span.textContent.trim();
            if (dict[originalText]) { span.textContent = dict[originalText]; }
        });
    }
});
</script>
</body>
</html>