<?php
session_start();

// Security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('includes/config.php');

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Renter';

// 1. Dynamic Stats Queries
// Active Bookings (Current active rentals)
$active_query = "SELECT COUNT(*) as total FROM bookings WHERE user_id = '$user_id' AND status = 'Active'";
$active_res = mysqli_query($conn, $active_query);
$active_bookings = ($active_res && $row = mysqli_fetch_assoc($active_res)) ? $row['total'] : 0;

// Upcoming Bookings
$upcoming_query = "SELECT COUNT(*) as total FROM bookings WHERE user_id = '$user_id' AND status = 'Upcoming'";
$upcoming_res = mysqli_query($conn, $upcoming_query);
$upcoming_bookings = ($upcoming_res && $row = mysqli_fetch_assoc($upcoming_res)) ? $row['total'] : 0;

// Completed Rentals
$completed_query = "SELECT COUNT(*) as total FROM bookings WHERE user_id = '$user_id' AND status = 'Completed'";
$completed_res = mysqli_query($conn, $completed_query);
$completed_rentals = ($completed_res && $row = mysqli_fetch_assoc($completed_res)) ? $row['total'] : 0;

// Total Spent
$spent_query = "SELECT SUM(total_amount) as total FROM bookings WHERE user_id = '$user_id' AND status IN ('Completed', 'Active')";
$spent_res = mysqli_query($conn, $spent_query);
$total_spent = ($spent_res && $row = mysqli_fetch_assoc($spent_res) && $row['total']) ? $row['total'] : 0;

// 2. Fetch Category Counts dynamically
$category_counts = [
    'Tractors'   => 0,
    'Harvesting' => 0,
    'Irrigation' => 0,
    'Tillage'    => 0,
    'Seeding'    => 0,
    'Spraying'   => 0
];

$cat_query = "SELECT category_name, COUNT(*) as count FROM equipment GROUP BY category_name";
$cat_res = mysqli_query($conn, $cat_query);
if ($cat_res) {
    while ($cat_row = mysqli_fetch_assoc($cat_res)) {
        if (array_key_exists($cat_row['category_name'], $category_counts)) {
            $category_counts[$cat_row['category_name']] = $cat_row['count'];
        }
    }
}

// 3. Fetch Featured Equipment
$featured_query = "SELECT * FROM equipment WHERE is_featured = 1 LIMIT 3";
$featured_res = mysqli_query($conn, $featured_query);

// 4. Fetch Recent Bookings dynamically
$recent_query = "SELECT b.*, e.title as equipment_name, e.image 
                FROM bookings b 
                LEFT JOIN equipment e ON b.equipment_id = e.id 
                WHERE b.user_id = '$user_id' 
                ORDER BY b.id DESC LIMIT 5";
$recent_res = mysqli_query($conn, $recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renter Dashboard - AgriRent</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-light-green: #eef5f0;
            --brand-accent-green: #52b788;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8faf9;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Top Header Badge */
        .top-banner-title {
            background-color: var(--brand-green);
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 30px;
            border-radius: 8px;
            display: inline-block;
            font-size: 1.1rem;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background: #ffffff;
            min-height: 100vh;
            border-right: 1px solid var(--border-color);
            padding: 20px 15px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 20px;
        }

        .brand-logo i {
            font-size: 2rem;
            color: var(--brand-green);
        }

        .brand-logo-text {
            font-weight: 800;
            color: var(--brand-green);
            font-size: 1.05rem;
            line-height: 1.1;
        }

        .brand-logo-sub {
            font-size: 0.6rem;
            color: #666;
            font-weight: 700;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-item {
            margin-bottom: 6px;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.92rem;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .nav-link-item:hover, .nav-link-item.active {
            background-color: var(--brand-light-green);
            color: var(--brand-green);
        }

        .nav-link-item i {
            font-size: 1.1rem;
            width: 20px;
        }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-left: 260px;
            padding: 20px 30px;
        }

        /* Header Bar */
        .header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 12px 25px;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .search-input-box {
            position: relative;
            width: 320px;
        }

        .search-input-box input {
            border-radius: 20px;
            padding-left: 15px;
            padding-right: 35px;
            font-size: 0.88rem;
            border: 1px solid #dce1e5;
            background-color: #f8faf9;
        }

        .search-input-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .header-right-tools {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #4a5568;
        }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--brand-green);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Welcome Banner */
        .welcome-card {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-radius: 18px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            margin-bottom: 25px;
            border: 1px solid #d4edda;
        }

        .welcome-card h2 {
            font-weight: 800;
            color: #1b4332;
            margin-bottom: 5px;
            font-size: 1.8rem;
        }

        .welcome-card p {
            color: #2d6a4f;
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .welcome-illustration {
            position: absolute;
            right: 30px;
            bottom: -10px;
            height: 120px;
            opacity: 0.85;
        }

        /* Stats Cards */
        .stat-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stat-icon.green { background-color: #e8f5e9; color: #2e7d32; }
        .stat-icon.blue { background-color: #e3f2fd; color: #1565c0; }
        .stat-icon.orange { background-color: #fff3e0; color: #ef6c00; }
        .stat-icon.purple { background-color: #f3e5f5; color: #7b1fa2; }

        .stat-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #666;
            margin-bottom: 2px;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: #111;
            line-height: 1;
        }

        .stat-link {
            font-size: 0.72rem;
            color: var(--brand-green);
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            margin-top: 6px;
        }

        /* Categories Section */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .section-header h5 {
            font-weight: 800;
            color: #1b4332;
            margin: 0;
            font-size: 1.15rem;
        }

        .section-header a {
            color: var(--brand-green);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .category-box {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px 10px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .category-box:hover {
            border-color: var(--brand-green);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .category-icon-wrapper {
            font-size: 1.8rem;
            color: var(--brand-green);
            margin-bottom: 8px;
        }

        .category-name {
            font-weight: 700;
            font-size: 0.85rem;
            color: #222;
            margin-bottom: 2px;
        }

        .category-count {
            font-size: 0.75rem;
            color: #777;
        }

        /* Equipment Cards */
        .equipment-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }

        .equipment-card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--brand-green);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .equipment-card-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background-color: #f0f0f0;
        }

        .equipment-card-body {
            padding: 12px 15px;
        }

        .equipment-title {
            font-weight: 800;
            font-size: 0.95rem;
            color: #111;
            margin-bottom: 4px;
        }

        .equipment-location {
            font-size: 0.78rem;
            color: #666;
            margin-bottom: 8px;
        }

        .equipment-price {
            font-weight: 800;
            color: var(--brand-green);
            font-size: 0.95rem;
        }

        .btn-view-details {
            border: 1px solid var(--brand-green);
            color: var(--brand-green);
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 12px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view-details:hover {
            background-color: var(--brand-green);
            color: #fff;
        }

        /* Tables */
        .recent-bookings-table {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .table {
            margin: 0;
            font-size: 0.88rem;
        }

        .table th {
            background-color: #f8faf9;
            font-weight: 700;
            color: #555;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 15px;
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .status-badge.confirmed { background-color: #e8f5e9; color: #2e7d32; }
        .status-badge.pending { background-color: #fff3e0; color: #ef6c00; }
        .status-badge.completed { background-color: #e3f2fd; color: #1565c0; }
    </style>
</head>
<body>

    <!-- SIDEBAR NAVIGATION -->
    <div class="sidebar">
        <div class="brand-logo">
            <i class="fa-solid fa-tractor"></i>
            <div>
                <div class="brand-logo-text">AGRICULTURE</div>
                <div class="brand-logo-sub">EQUIPMENT RENTAL SYSTEM</div>
            </div>
        </div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="renter_dashboard.php" class="nav-link-item active">
                    <i class="fa-solid fa-border-all"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link-item">
                    <i class="fa-solid fa-cubes"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link-item">
                    <i class="fa-regular fa-star"></i> Featured Equipment
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link-item">
                    <i class="fa-regular fa-thumbs-up"></i> Recommended
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link-item">
                    <i class="fa-regular fa-calendar-check"></i> My Bookings
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link-item">
                    <i class="fa-solid fa-clock-rotate-left"></i> Rental History
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link-item">
                    <i class="fa-regular fa-user"></i> My Profile
                </a>
            </li>
            <li class="nav-item" style="margin-top: 20px;">
                <a href="logout.php" class="nav-link-item text-danger">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main-wrapper">

        <!-- CENTERED RENTER DASHBOARD BANNER -->
        <div class="text-center mb-3">
            <div class="top-banner-title">Renter Dashboard</div>
        </div>

        <!-- TOP HEADER BAR -->
        <div class="header-bar">
            <!-- Search Equipment Input (No 3 Horizontal Lines Icon) -->
            <div class="search-input-box">
                <input type="text" class="form-control" placeholder="Search equipment...">
                <i class="fa-solid fa-microphone"></i>
            </div>

            <div class="header-right-tools">
                <div><i class="fa-solid fa-location-dot text-success me-1"></i> Devanahalli, Bengaluru Rural</div>
                <div><i class="fa-solid fa-globe me-1"></i> English <i class="fa-solid fa-chevron-down ms-1 small"></i></div>
                <div class="user-profile-badge">
                    <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                </div>
            </div>
        </div>

        <!-- WELCOME BANNER CARD -->
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?> <i class="fa-solid fa-leaf text-success fs-5"></i></h2>
            <p>Find and rent the best agricultural equipment near you.</p>
            <i class="fa-solid fa-tractor welcome-illustration fs-1 text-success opacity-25"></i>
        </div>

        <!-- STATS CARDS GRID -->
        <div class="row g-3">
            <!-- Active Bookings -->
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-regular fa-calendar-check"></i></div>
                    <div>
                        <div class="stat-title">Active Bookings</div>
                        <div class="stat-value"><?php echo $active_bookings; ?></div>
                        <a href="#" class="stat-link">View Details &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Upcoming Bookings -->
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-regular fa-calendar"></i></div>
                    <div>
                        <div class="stat-title">Upcoming Bookings</div>
                        <div class="stat-value"><?php echo $upcoming_bookings; ?></div>
                        <a href="#" class="stat-link">View Details &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Completed Rentals -->
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fa-regular fa-clock"></i></div>
                    <div>
                        <div class="stat-title">Completed Rentals</div>
                        <div class="stat-value"><?php echo $completed_rentals; ?></div>
                        <a href="#" class="stat-link">View Details &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-wallet"></i></div>
                    <div>
                        <div class="stat-title">Total Spent</div>
                        <div class="stat-value">₹ <?php echo number_format($total_spent); ?></div>
                        <a href="#" class="stat-link">View Details &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- POPULAR CATEGORIES SECTION -->
        <div class="section-header">
            <h5>Popular Categories</h5>
            <a href="#">View All &rarr;</a>
        </div>

        <div class="row g-3">
            <div class="col-2">
                <div class="category-box">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-tractor"></i></div>
                    <div class="category-name">Tractors</div>
                    <div class="category-count">(<?php echo $category_counts['Tractors']; ?>)</div>
                </div>
            </div>
            <div class="col-2">
                <div class="category-box">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-wheat-awn"></i></div>
                    <div class="category-name">Harvesting</div>
                    <div class="category-count">(<?php echo $category_counts['Harvesting']; ?>)</div>
                </div>
            </div>
            <div class="col-2">
                <div class="category-box">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-faucet-drip"></i></div>
                    <div class="category-name">Irrigation</div>
                    <div class="category-count">(<?php echo $category_counts['Irrigation']; ?>)</div>
                </div>
            </div>
            <div class="col-2">
                <div class="category-box">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-gears"></i></div>
                    <div class="category-name">Tillage</div>
                    <div class="category-count">(<?php echo $category_counts['Tillage']; ?>)</div>
                </div>
            </div>
            <div class="col-2">
                <div class="category-box">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-seedling"></i></div>
                    <div class="category-name">Seeding</div>
                    <div class="category-count">(<?php echo $category_counts['Seeding']; ?>)</div>
                </div>
            </div>
            <div class="col-2">
                <div class="category-box">
                    <div class="category-icon-wrapper"><i class="fa-solid fa-spray-can"></i></div>
                    <div class="category-name">Spraying</div>
                    <div class="category-count">(<?php echo $category_counts['Spraying']; ?>)</div>
                </div>
            </div>
        </div>

        <!-- FEATURED EQUIPMENT SECTION -->
        <div class="section-header">
            <h5>Featured Equipment</h5>
            <a href="#">View All &rarr;</a>
        </div>

        <div class="row g-3">
            <?php if ($featured_res && mysqli_num_rows($featured_res) > 0): ?>
                <?php while ($eq = mysqli_fetch_assoc($featured_res)): ?>
                    <div class="col-md-4">
                        <div class="equipment-card">
                            <span class="equipment-card-badge">FEATURED</span>
                            <img src="<?php echo !empty($eq['image']) ? $eq['image'] : 'images/tractor.jpg'; ?>" class="equipment-card-img" alt="Equipment">
                            <div class="equipment-card-body">
                                <div class="equipment-title"><?php echo htmlspecialchars($eq['title']); ?></div>
                                <div class="equipment-location"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($eq['location']); ?></div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="equipment-price">₹ <?php echo number_format($eq['price_per_day']); ?> <span class="small text-muted font-normal">/ day</span></div>
                                    <a href="#" class="btn-view-details">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Fallback Mock Display matching screenshot layout -->
                <div class="col-md-4">
                    <div class="equipment-card">
                        <span class="equipment-card-badge">FEATURED</span>
                        <img src="images/tractor.jpg" class="equipment-card-img" alt="Mahindra 575 DI">
                        <div class="equipment-card-body">
                            <div class="equipment-title">Mahindra 575 DI</div>
                            <div class="equipment-location"><i class="fa-solid fa-location-dot"></i> Devanahalli</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="equipment-price">₹ 2,500 <span class="small text-muted">/ day</span></div>
                                <a href="#" class="btn-view-details">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="equipment-card">
                        <span class="equipment-card-badge">FEATURED</span>
                        <img src="images/tractor3.jpg" class="equipment-card-img" alt="Sonalika DI 745 III">
                        <div class="equipment-card-body">
                            <div class="equipment-title">Sonalika DI 745 III</div>
                            <div class="equipment-location"><i class="fa-solid fa-location-dot"></i> Hoskote</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="equipment-price">₹ 2,200 <span class="small text-muted">/ day</span></div>
                                <a href="#" class="btn-view-details">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="equipment-card">
                        <span class="equipment-card-badge">FEATURED</span>
                        <img src="images/tractor.jpg" class="equipment-card-img" alt="John Deere 5310">
                        <div class="equipment-card-body">
                            <div class="equipment-title">John Deere 5310</div>
                            <div class="equipment-location"><i class="fa-solid fa-location-dot"></i> Doddaballapur</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="equipment-price">₹ 3,500 <span class="small text-muted">/ day</span></div>
                                <a href="#" class="btn-view-details">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENT BOOKINGS SECTION -->
        <div class="section-header">
            <h5>Recent Bookings</h5>
            <a href="#">View All &rarr;</a>
        </div>

        <div class="recent-bookings-table mb-4">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Booking ID</th>
                        <th>From - To</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_res && mysqli_num_rows($recent_res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recent_res)): ?>
                            <tr>
                                <td class="fw-bold">
                                    <i class="fa-solid fa-tractor text-success me-2"></i>
                                    <?php echo htmlspecialchars($row['equipment_name']); ?>
                                </td>
                                <td>BK<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['start_date'])); ?> - <?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                                <td>
                                    <?php 
                                        $statusClass = strtolower($row['status']);
                                        echo "<span class='status-badge {$statusClass}'>{$row['status']}</span>";
                                    ?>
                                </td>
                                <td class="fw-bold">₹ <?php echo number_format($row['total_amount']); ?></td>
                                <td class="text-center"><a href="#" class="text-secondary"><i class="fa-regular fa-eye"></i></a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Default empty state if no bookings found -->
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fa-regular fa-folder-open fs-3 d-block mb-2"></i>
                                No recent bookings found. Rent equipment to see your bookings here!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>