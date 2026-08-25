<?php
session_start();
require_once 'includes/lang.php';

if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

// Redirect to login if user is not logged in or not a renter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

// 1. Fetch User Profile & Registered Address from Database
$user_query = "SELECT full_name, email, COALESCE(address, 'Not Specified') AS address FROM users WHERE user_id = '$user_id'";
$user_res = mysqli_query($conn, $user_query);
$user_info = mysqli_fetch_assoc($user_res);

// 2. Fetch Notifications Data
$notif_count_sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
$notif_count_res = mysqli_query($conn, $notif_count_sql);
$unread_count = ($notif_count_res) ? mysqli_fetch_assoc($notif_count_res)['total'] : 0;

$notif_list_sql = "SELECT notification_id, title, message, is_read, created_at 
                    FROM notifications 
                    WHERE user_id = '$user_id' 
                    ORDER BY created_at DESC LIMIT 5";
$notif_list_res = mysqli_query($conn, $notif_list_sql);

// 3. Dynamic Stats Queries for Logged-In Renter
$active_sql = "SELECT COUNT(*) AS total FROM bookings WHERE renter_id = '$user_id' AND status IN ('confirmed', 'active')";
$active_res = mysqli_query($conn, $active_sql);
$active_count = ($active_res) ? mysqli_fetch_assoc($active_res)['total'] : 0;

$upcoming_sql = "SELECT COUNT(*) AS total FROM bookings WHERE renter_id = '$user_id' AND status = 'pending'";
$upcoming_res = mysqli_query($conn, $upcoming_sql);
$upcoming_count = ($upcoming_res) ? mysqli_fetch_assoc($upcoming_res)['total'] : 0;

$completed_sql = "SELECT COUNT(*) AS total FROM bookings WHERE renter_id = '$user_id' AND status = 'completed'";
$completed_res = mysqli_query($conn, $completed_sql);
$completed_count = ($completed_res) ? mysqli_fetch_assoc($completed_res)['total'] : 0;

$spent_sql = "SELECT SUM(total_amount) AS total FROM bookings WHERE renter_id = '$user_id' AND status = 'completed'";
$spent_res = mysqli_query($conn, $spent_sql);
$spent_val = mysqli_fetch_assoc($spent_res)['total'];
$total_spent = ($spent_val) ? number_format($spent_val, 0) : '0';

// 4. Fetch Categories Base Info
$cat_query = "SELECT category_id, category_name, icon_class FROM categories LIMIT 6";
$cat_res = mysqli_query($conn, $cat_query);

$featured_query = "SELECT * FROM equipment WHERE is_featured = 1 LIMIT 3";
$featured_res = mysqli_query($conn, $featured_query);

$recent_query = "SELECT b.*, e.title AS equipment_name 
                 FROM bookings b 
                 JOIN equipment e ON b.equipment_id = e.equipment_id 
                 WHERE b.renter_id = '$user_id' 
                 ORDER BY b.booking_id DESC LIMIT 5";
$recent_res = mysqli_query($conn, $recent_query);
?>

<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('renter_dashboard'); ?> - <?= __('title'); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-green-light: #e8f5e9;
            --sidebar-bg: #ffffff;
            --sidebar-text: #4a5568;
            --bg-gray: #f8fafc;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-gray);
            color: #2d3748;
        }

        /* Full Sidebar Styling */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            border-right: 1px solid #edf2f7;
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
            color: var(--sidebar-text);
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

        .main-wrapper {
            margin-left: 250px;
            padding: 20px 30px;
        }

        .top-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            margin-bottom: 20px;
        }

        /* Welcome Banner with Full Background Image */
        .welcome-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('images/tractor3.jpg');
            background-size: cover;
            background-position: center;
            border-radius: 16px;
            padding: 35px 30px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
            border: 1px solid #d4edda;
            color: #ffffff;
        }

        .welcome-banner h3 {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .welcome-banner p {
            color: #f1f5f9 !important;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }

        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #f1f5f9;
        }

        .stat-icon-wrapper {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .lang-select {
            border: none;
            background: transparent;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .cat-box {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .cat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

    <!-- FULL SIDEBAR -->
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
                <a href="renter_dashboard.php" class="nav-link active">
                    <i class="fa-solid fa-border-all"></i> <?= __('dashboard'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="search_equipment.php" class="nav-link">
                    <i class="fa-solid fa-magnifying-glass"></i> <?= __('search_equipment'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link">
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

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        
        <!-- TOP GREEN HIGHLIGHT BADGE -->
        <div class="text-center mb-3">
            <span class="badge bg-success text-white px-4 py-2 rounded-pill fs-6 fw-bold shadow-sm" style="background-color: #2d6a4f !important; letter-spacing: 1px;">
                <?= strtoupper(__('renter_dashboard')); ?>
            </span>
        </div>

        <!-- TOP NAVBAR -->
        <div class="top-navbar bg-white p-3 rounded-4 shadow-sm border d-flex align-items-center justify-content-between mb-4">
            
            <!-- BRAND LOGO & MENU ICON -->
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light border-0 d-md-none"><i class="fa-solid fa-bars"></i></button>
                <div class="brand-logo d-flex align-items-center gap-2">
                    <i class="fa-solid fa-tractor text-success fa-xl"></i>
                    <div>
                        <strong style="color: #2d6a4f; display: block; line-height: 1; font-size: 0.9rem;">AGRICULTURE</strong>
                        <span style="font-size: 0.6rem; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">EQUIPMENT RENTAL SYSTEM</span>
                    </div>
                </div>
            </div>

            <!-- VOICE SEARCH BAR WITH MICROPHONE -->
            <form action="search_equipment.php" method="GET" id="searchForm" class="flex-grow-1 mx-4" style="max-width: 450px;">
                <div class="position-relative d-flex align-items-center">
                    <i class="fa-solid fa-magnifying-glass position-absolute start-0 ms-3 text-muted"></i>
                    <input type="text" name="q" id="searchInput" class="form-control rounded-pill ps-5 pe-5 border bg-light" 
                           placeholder="<?= __('search_placeholder'); ?>" style="height: 42px;">
                    
                    <!-- Voice Search Mic Button -->
                    <button type="button" id="micBtn" onclick="startVoiceSearch()" 
                            class="btn btn-success rounded-circle position-absolute end-0 me-1 d-flex align-items-center justify-content-center" 
                            style="width: 34px; height: 34px; border: none;" title="Click to speak">
                        <i class="fa-solid fa-microphone text-white" id="micIcon"></i>
                    </button>
                </div>
            </form>

            <!-- LOCATION, LANGUAGE & PROFILE -->
            <div class="d-flex align-items-center gap-3">
                <!-- Location Display -->
                <div class="small text-secondary fw-semibold d-none d-lg-flex align-items-center gap-1">
                    <i class="fa-solid fa-location-dot text-danger"></i>
                    <span><?= htmlspecialchars($user_info['address'] ?? 'Devanahalli, Bengaluru Rural'); ?></span>
                </div>

                <!-- Language Switcher Dropdown -->
                <div class="d-flex align-items-center gap-1 border-start ps-3">
                    <i class="fa-solid fa-globe text-secondary"></i>
                    <select class="lang-select border-0 bg-transparent fw-semibold text-secondary small" 
                            onchange="window.location.href='renter_dashboard.php?lang=' + this.value;">
                        <option value="en" <?= ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                        <option value="kn" <?= ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ</option>
                        <option value="hi" <?= ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी</option>
                    </select>
                </div>

                <!-- DYNAMIC NOTIFICATIONS DROPDOWN -->
                <div class="dropdown border-start ps-3">
                    <button class="btn btn-light border-0 position-relative p-2 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="fa-regular fa-bell text-secondary fa-lg"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger" style="font-size: 0.65rem;">
                                <?= $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="width: 320px; max-height: 350px; overflow-y: auto;">
                        <li class="dropdown-header fw-bold border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center">
                            <span class="text-dark">Notifications</span>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger-subtle text-danger small"><?= $unread_count; ?> New</span>
                            <?php endif; ?>
                        </li>

                        <?php if ($notif_list_res && mysqli_num_rows($notif_list_res) > 0): ?>
                            <?php while ($notif = mysqli_fetch_assoc($notif_list_res)): ?>
                                <li class="mb-1">
                                    <a class="dropdown-item rounded p-2 text-wrap small <?= ($notif['is_read'] == 0) ? 'bg-light border-start border-3 border-success fw-semibold' : 'text-muted'; ?>" href="#">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="text-dark" style="font-size: 0.82rem;"><?= htmlspecialchars($notif['title']); ?></strong>
                                            <span class="text-muted" style="font-size: 0.65rem;"><?= date('M d, g:i a', strtotime($notif['created_at'])); ?></span>
                                        </div>
                                        <div class="text-secondary" style="font-size: 0.78rem; line-height: 1.3;">
                                            <?= htmlspecialchars($notif['message']); ?>
                                        </div>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="text-center text-muted py-4 small">
                                <i class="fa-regular fa-bell-slash d-block fa-2x mb-2 opacity-50"></i>
                                No notifications found
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Profile Badge -->
                <div class="d-flex align-items-center gap-2 border-start ps-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" 
                         style="width: 36px; height: 36px; font-size: 0.9rem;">
                        <?= strtoupper(substr($user_info['full_name'] ?? 'P', 0, 1)); ?>
                    </div>
                    <div class="d-none d-sm-block">
                        <div class="fw-bold small lh-1"><?= htmlspecialchars($user_info['full_name'] ?? 'Pragathi'); ?></div>
                        <span class="text-muted" style="font-size: 0.75rem;">Renter</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- WELCOME BANNER WITH tractor3.jpg FULL BACKGROUND -->
        <div class="welcome-banner">
            <div style="max-width: 100%;">
                <h3 class="fw-bold mb-2"><?= __('welcome_back'); ?>, <?= htmlspecialchars($user_info['full_name'] ?? 'User'); ?> 🌿</h3>
                <p class="mb-0 small"><?= __('find_and_rent'); ?></p>
            </div>
        </div>

        <!-- DYNAMIC STATS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= __('active_bookings'); ?></div>
                        <h4 class="fw-bold mb-0"><?= $active_count; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= __('upcoming_bookings'); ?></div>
                        <h4 class="fw-bold mb-0"><?= $upcoming_count; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                        <i class="fa-solid fa-history"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= __('completed_rentals'); ?></div>
                        <h4 class="fw-bold mb-0"><?= $completed_count; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-info-subtle text-info">
                        <i class="fa-solid fa-indian-rupee-sign"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= __('total_spent'); ?></div>
                        <h4 class="fw-bold mb-0">₹ <?= $total_spent; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- POPULAR CATEGORIES (WITH FLEXIBLE DYNAMIC COUNTING LOGIC) -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><?= __('popular_categories'); ?></h6>
            <a href="categories.php" class="text-success small text-decoration-none fw-semibold"><?= __('view_all'); ?> →</a>
        </div>
        <div class="row g-3 mb-4">
            <?php if ($cat_res && mysqli_num_rows($cat_res) > 0): ?>
                <?php while ($cat = mysqli_fetch_assoc($cat_res)): ?>
                    <?php 
                        $cat_name = $cat['category_name'];
                        $base_name = rtrim(strtolower($cat_name), 's');
                        $searchTerm = '%' . $base_name . '%';

                        $q_items = "SELECT COUNT(*) AS total FROM items WHERE LOWER(category) LIKE '$searchTerm'";
                        $res_items = mysqli_query($conn, $q_items);
                        $row_items = mysqli_fetch_assoc($res_items);
                        $count_items = $row_items['total'] ?? 0;

                        $q_equip = "SELECT COUNT(*) AS total FROM equipment WHERE LOWER(category) LIKE '$searchTerm'";
                        $res_equip = mysqli_query($conn, $q_equip);
                        $row_equip = mysqli_fetch_assoc($res_equip);
                        $count_equip = $row_equip['total'] ?? 0;

                        $total_equipment_count = max($count_items, $count_equip);
                    ?>
                    <div class="col-md-2 col-4">
                        <a href="category_items.php?category_id=<?= (int)$cat['category_id']; ?>" class="text-decoration-none text-dark d-block h-100">
                            <div class="bg-white border rounded p-3 text-center h-100 cat-box">
                                <i class="<?= $cat['icon_class'] ?: 'fa-solid fa-gears'; ?> text-success fa-xl mb-2"></i>
                                <div class="fw-bold small"><?= htmlspecialchars($cat_name); ?></div>
                                <span class="text-muted" style="font-size: 0.7rem;">(<?= (int)$total_equipment_count; ?>)</span>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><div class="text-muted small p-2 bg-white rounded border text-center">No Categories Available Yet</div></div>
            <?php endif; ?>
        </div>

        <!-- RECENT BOOKINGS -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><?= __('recent_bookings'); ?></h6>
            <a href="my_bookings.php" class="text-success small text-decoration-none fw-semibold"><?= __('view_all'); ?> →</a>
        </div>
        <div class="bg-white rounded border p-3">
            <?php if ($recent_res && mysqli_num_rows($recent_res) > 0): ?>
                <table class="table align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Equipment</th>
                            <th>Booking ID</th>
                            <th>From - To</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_res)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['equipment_name']); ?></strong></td>
                                <td>BK<?= $row['booking_id']; ?></td>
                                <td><?= $row['start_date']; ?> - <?= $row['end_date']; ?></td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] == 'confirmed') ? 'success' : (($row['status'] == 'pending') ? 'warning' : 'secondary'); ?>">
                                        <?= ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>₹ <?= number_format($row['total_amount']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fa-solid fa-folder-open text-muted fa-2x mb-2"></i>
                    <p class="text-muted small mb-2"><?= __('no_active_rentals'); ?></p>
                    <a href="search_equipment.php" class="btn btn-sm btn-success fw-semibold">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> <?= __('browse_equipment'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- VOICE SEARCH JAVASCRIPT ENGINE -->
    <script>
    function startVoiceSearch() {
        const micBtn = document.getElementById('micBtn');
        const micIcon = document.getElementById('micIcon');
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

        if (!SpeechRecognition) {
            alert("Voice search is not supported on this browser. Please use Google Chrome or Microsoft Edge.");
            return;
        }

        const recognition = new SpeechRecognition();

        const currentLang = '<?= $current_lang; ?>';
        if (currentLang === 'kn') {
            recognition.lang = 'kn-IN';
        } else if (currentLang === 'hi') {
            recognition.lang = 'hi-IN';
        } else {
            recognition.lang = 'en-US';
        }

        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        micBtn.classList.remove('btn-success');
        micBtn.classList.add('btn-danger');
        micIcon.className = 'fa-solid fa-spinner fa-spin text-white';
        searchInput.placeholder = "Listening... Speak now...";

        recognition.start();

        recognition.onresult = function(event) {
            const transcript = event.results[0][0].transcript;
            searchInput.value = transcript;
            resetMicUI();

            setTimeout(() => {
                searchForm.submit();
            }, 500);
        };

        recognition.onerror = function(event) {
            console.error("Speech Recognition Error: " + event.error);
            alert("Could not recognize voice. Please try again.");
            resetMicUI();
        };

        recognition.onend = function() {
            resetMicUI();
        };

        function resetMicUI() {
            micBtn.classList.remove('btn-danger');
            micBtn.classList.add('btn-success');
            micIcon.className = 'fa-solid fa-microphone text-white';
            searchInput.placeholder = "<?= __('search_placeholder'); ?>";
        }
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>