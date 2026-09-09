<?php
session_start();

// 1. Load persistent language system & DB config
require_once 'includes/lang.php';
require_once 'includes/config.php';

// 2. Protect Page: Ensure user is logged in as Lender
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'lender') {
    header("Location: login.php");
    exit();
}

$lender_id = $_SESSION['user_id'];
$lender_name = $_SESSION['full_name'] ?? 'Lender';

// Fetch profile picture if available
$user_query = mysqli_query($conn, "SELECT profile_pic FROM users WHERE user_id = '$lender_id'");
$user_data = mysqli_fetch_assoc($user_query);
$profile_pic = !empty($user_data['profile_pic']) ? $user_data['profile_pic'] : 'default_avatar.png';

// 3. Real-Time Database Queries
$total_equip_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM items WHERE lender_id = '$lender_id'");
$total_equipment = mysqli_fetch_assoc($total_equip_res)['total'] ?? 0;

$active_rentals_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings b JOIN items i ON b.equipment_id = i.item_id WHERE i.lender_id = '$lender_id' AND b.status = 'approved'");
$active_rentals = mysqli_fetch_assoc($active_rentals_res)['total'] ?? 0;

$pending_req_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings b JOIN items i ON b.equipment_id = i.item_id WHERE i.lender_id = '$lender_id' AND b.status = 'pending'");
$pending_requests = mysqli_fetch_assoc($pending_req_res)['total'] ?? 0;

$earnings_res = mysqli_query($conn, "SELECT SUM(total_amount) AS total FROM bookings b JOIN items i ON b.equipment_id = i.item_id WHERE i.lender_id = '$lender_id' AND b.status IN ('approved', 'completed')");
$total_earnings = mysqli_fetch_assoc($earnings_res)['total'] ?? 0;

// Fetch Rental Requests (Pending)
$requests_query = "SELECT b.*, i.title AS item_title, i.image, u.full_name AS renter_name, u.phone AS renter_phone 
                  FROM bookings b 
                  JOIN items i ON b.equipment_id = i.item_id 
                  JOIN users u ON b.renter_id = u.user_id 
                  WHERE i.lender_id = '$lender_id' AND b.status = 'pending' 
                  ORDER BY b.booking_id DESC LIMIT 5";
$requests_result = mysqli_query($conn, $requests_query);

// Fetch Active Rentals (Approved)
$active_query = "SELECT b.*, i.title AS item_title, i.image, u.full_name AS renter_name 
                FROM bookings b 
                JOIN items i ON b.equipment_id = i.item_id 
                JOIN users u ON b.renter_id = u.user_id 
                WHERE i.lender_id = '$lender_id' AND b.status = 'approved' 
                ORDER BY b.booking_id DESC LIMIT 5";
$active_result = mysqli_query($conn, $active_query);
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <title>Lender Dashboard - Agriculture Equipment Rental System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; color: #333; }
        
        .sidebar { width: 250px; background: #fff; min-height: 100vh; padding: 20px; border-right: 1px solid #e0e0e0; }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: bold; color: #1e3a8a; font-size: 15px; margin-bottom: 30px; }
        .logo i { font-size: 24px; color: #0f4c5c; }
        .nav-list { list-style: none; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #64748b; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 14px; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: #0f4c5c; color: #fff; }
        .nav-link .badge { margin-left: auto; background: #ef4444; color: #fff; font-size: 11px; padding: 2px 7px; border-radius: 12px; }
        .nav-link .badge-blue { background: #3b82f6; }

        .main-content { flex: 1; padding: 20px 30px; }
        .top-banner { background: #0f4c5c; color: white; text-align: center; padding: 8px; font-weight: bold; border-radius: 6px; margin-bottom: 20px; letter-spacing: 1px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        
        .search-box { position: relative; width: 350px; display: flex; align-items: center; }
        .search-box input { width: 100%; padding: 8px 40px 8px 35px; border: 1px solid #cbd5e1; border-radius: 20px; outline: none; }
        .search-box .fa-magnifying-glass { position: absolute; left: 12px; color: #94a3b8; }
        .mic-btn { position: absolute; right: 5px; width: 30px; height: 30px; border-radius: 50%; border: none; background: #0f4c5c; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .mic-btn:hover { background: #0b3844; }
        .mic-btn.recording { background: #ef4444; }

        .user-menu { display: flex; align-items: center; gap: 12px; }
        .user-profile-img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #0f4c5c; }

        .lang-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; background: #fff; cursor: pointer; }

        .welcome-card { 
            background: #e0f2fe url('uploads/welcome-bg.jpg') no-repeat right center; 
            background-size: contain; /* Keeps the full tractor image visible without cropping */
            padding: 25px; 
            border-radius: 12px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            min-height: 120px; /* Gives the card enough height to show the full image */
        }
        
        .welcome-text h2 { color: #0f172a; font-size: 22px; margin-bottom: 5px; }
        .welcome-text p { color: #334155; font-size: 14px; font-weight: 600; }
        
        /* Make the text white so it pops nicely over the full background */
        .welcome-text h2 { color: #ffffff; font-size: 24px; margin-bottom: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .welcome-text p { color: #f1f5f9; font-size: 15px; font-weight: 500; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }
        .welcome-text h2 { color: #0f172a; font-size: 22px; margin-bottom: 5px; }
        .welcome-text p { color: #334155; font-size: 14px; font-weight: 600; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 10px; }
        .stat-header { display: flex; align-items: center; gap: 10px; color: #64748b; font-size: 13px; font-weight: 600; }
        .stat-icon { font-size: 18px; padding: 8px; border-radius: 6px; }
        .stat-value { font-size: 22px; font-weight: bold; color: #0f172a; }
        .stat-link { font-size: 12px; color: #0284c7; text-decoration: none; font-weight: 600; margin-top: auto; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px; }
        .card { background: #fff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-title { font-size: 16px; font-weight: bold; color: #0f172a; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px; color: #64748b; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .item-info { display: flex; align-items: center; gap: 10px; }
        .item-img { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; background: #f1f5f9; }
        
        .btn-act { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; border: none; cursor: pointer; }
        .btn-accept { background: #22c55e; color: #fff; margin-right: 5px; }
        .btn-reject { background: #ef4444; color: #fff; }

        .badge-status { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
        .status-inuse { background: #dbeafe; color: #1d4ed8; }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <i class="fa-solid fa-tractor"></i>
            <span>AGRICULTURE<br><small style="font-size: 9px; color: #64748b;">EQUIPMENT RENTAL SYSTEM</small></span>
        </div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="lender_dashboard.php" class="nav-link active">
                    <i class="fa-solid fa-chart-line"></i> <?php echo __('dashboard'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="add_item.php" class="nav-link">
                    <i class="fa-solid fa-circle-plus"></i> <?php echo __('add_equipment'); ?>
                </a>
            </li>
            <li class="nav-item">
             <a href="my_equipment.php" class="nav-link">
                <i class="fa-solid fa-list"></i> <?php echo __('my_equipment'); ?>
              </a>
            </li>
            <li class="nav-item">
                <a href="rental_requests.php" class="nav-link">
                    <i class="fa-solid fa-clock-rotate-left"></i> <?php echo __('rental_requests'); ?>
                    <span class="badge"><?php echo $pending_requests; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="active_rentals.php" class="nav-link">
                    <i class="fa-solid fa-truck-ramp-box"></i> <?php echo __('active_rentals'); ?>
                    <span class="badge badge-blue"><?php echo $active_rentals; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="my_bookings.php" class="nav-link">
                    <i class="fa-solid fa-calendar-check"></i> <?php echo __('my_bookings'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="rental_history.php" class="nav-link">
                    <i class="fa-solid fa-history"></i> <?php echo __('rental_history'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="Reviews.php" class="nav-link">
                    <i class="fa-solid fa-star"></i> <?php echo __('Reviews'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="earnings.php" class="nav-link">
                    <i class="fa-solid fa-wallet"></i> <?php echo __('total_earnings'); ?>
                </a>
            </li>
            <li class="nav-item">
            <a href="profile.php" class="nav-link">
               <i class="fa-regular fa-user"></i>
               My Profile
            </a>
             </li>
            <li class="nav-item" style="margin-top: 20px;">
                <a href="logout.php" class="nav-link" style="color: #ef4444;">
                    <i class="fa-solid fa-right-from-bracket"></i> <?php echo __('logout'); ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="top-banner"><?php echo __('lender_dashboard'); ?></div>
        
        <div class="top-bar">
            <!-- Form with Microphone Voice Search -->
            <form action="search_equipment.php" method="GET" id="searchForm" class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" id="searchInput" placeholder="<?php echo __('search_placeholder'); ?>">
                <button type="button" id="micBtn" onclick="startVoiceSearch()" class="mic-btn" title="Click to speak">
                    <i class="fa-solid fa-microphone" id="micIcon"></i>
                </button>
            </form>
            
            <div class="user-menu">
                <select class="lang-select" onchange="location = this.value;">
                    <option value="?lang=en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>🌐 English</option>
                    <option value="?lang=hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>🌐 हिन्दी (Hindi)</option>
                    <option value="?lang=kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>🌐 ಕನ್ನಡ (Kannada)</option>
                </select>

                <i class="fa-regular fa-bell" style="font-size: 18px; color: #64748b;"></i>
                
                <!-- Profile Pic Next to Name -->
                <span style="font-size: 14px; font-weight: bold;"><?php echo htmlspecialchars($lender_name); ?></span>
                <a href="profile.php">
                    <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" 
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($lender_name); ?>&background=0f4c5c&color=fff';" 
                         class="user-profile-img" alt="Profile Picture">
                </a>
            </div>
        </div>

        <!-- Banner Card -->
        <div class="welcome-card">
            <div class="welcome-text">
                <h2><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($lender_name); ?> 👋</h2>
                <p><?php echo __('banner_subtitle'); ?></p>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <i class="fa-solid fa-clipboard-list stat-icon" style="background: #e0f2fe; color: #0284c7;"></i>
                    <span><?php echo __('total_equipment'); ?></span>
                </div>
                <div class="stat-value"><?php echo $total_equipment; ?></div>
                <a href="my_equipment.php" class="stat-link"><?php echo __('view_details'); ?></a>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <i class="fa-solid fa-calendar-check stat-icon" style="background: #e0f2fe; color: #0284c7;"></i>
                    <span><?php echo __('active_rentals'); ?></span>
                </div>
                <div class="stat-value"><?php echo $active_rentals; ?></div>
                <a href="active_rentals.php" class="stat-link"><?php echo __('view_details'); ?></a>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <i class="fa-solid fa-hourglass-half stat-icon" style="background: #fef3c7; color: #d97706;"></i>
                    <span><?php echo __('rental_requests'); ?></span>
                </div>
                <div class="stat-value"><?php echo $pending_requests; ?></div>
                <a href="rental_requests.php" class="stat-link"><?php echo __('view_details'); ?></a>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <i class="fa-solid fa-indian-rupee-sign stat-icon" style="background: #f3e8ff; color: #9333ea;"></i>
                    <span><?php echo __('total_earnings'); ?></span>
                </div>
                <div class="stat-value">₹ <?php echo number_format($total_earnings, 2); ?></div>
                <a href="earnings.php" class="stat-link"><?php echo __('view_details'); ?></a>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><?php echo __('rental_requests'); ?></span>
                    <a href="rental_requests.php" class="stat-link"><?php echo __('view_all'); ?></a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo __('renter'); ?></th>
                            <th><?php echo __('equipment'); ?></th>
                            <th><?php echo __('from_to'); ?></th>
                            <th><?php echo __('amount'); ?></th>
                            <th><?php echo __('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests_result && mysqli_num_rows($requests_result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($requests_result)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['renter_name']); ?></strong><br>
                                        <small style="color: #64748b;"><?php echo htmlspecialchars($row['renter_phone'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <div class="item-info">
                                            <img src="uploads/<?php echo !empty($row['image']) ? $row['image'] : 'default.png'; ?>" class="item-img" alt="equipment">
                                            <span><?php echo htmlspecialchars($row['item_title']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo date('d M', strtotime($row['start_date'])); ?> - <?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                                    <td><strong>₹<?php echo number_format($row['total_amount']); ?></strong></td>
                                    <td>
                                        <a href="update_booking.php?id=<?php echo $row['booking_id']; ?>&action=approve" class="btn-act btn-accept"><?php echo __('accept'); ?></a>
                                        <a href="update_booking.php?id=<?php echo $row['booking_id']; ?>&action=reject" class="btn-act btn-reject"><?php echo __('reject'); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;"><?php echo __('no_pending_requests'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title"><?php echo __('active_rentals'); ?></span>
                    <a href="active_rentals.php" class="stat-link"><?php echo __('view_all'); ?></a>
                </div>
                <?php if ($active_result && mysqli_num_rows($active_result) > 0): ?>
                    <?php while($active = mysqli_fetch_assoc($active_result)): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                            <div class="item-info">
                                <img src="uploads/<?php echo !empty($active['image']) ? $active['image'] : 'default.png'; ?>" class="item-img" alt="equipment">
                                <div>
                                    <strong style="font-size: 13px;"><?php echo htmlspecialchars($active['item_title']); ?></strong><br>
                                    <small style="color: #64748b;"><?php echo __('renter'); ?>: <?php echo htmlspecialchars($active['renter_name']); ?></small>
                                </div>
                            </div>
                            <span class="badge-status status-inuse"><?php echo __('in_use'); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;"><?php echo __('no_active_rentals'); ?></p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Voice Search JavaScript -->
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

        micBtn.classList.add('recording');
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
            micBtn.classList.remove('recording');
            micIcon.className = 'fa-solid fa-microphone';
            searchInput.placeholder = "<?php echo __('search_placeholder'); ?>";
        }
    }
    </script>
</body>
</html>