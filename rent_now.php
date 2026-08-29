<?php
session_start();
require_once 'includes/config.php'; // Adjust path if your config file is inside a folder

// 1. Session and Renter Validation
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

$renter_id = $_SESSION['user_id'];
$equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

if ($equipment_id <= 0) {
    header("Location: categories.php");
    exit();
}

// 2. Fetch Equipment & Lender Details
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name AS lender_name, u.phone AS lender_phone, 
           CO_ITEMS.security_deposit 
    FROM equipment e 
    JOIN users u ON e.lender_id = u.user_id 
    LEFT JOIN items CO_ITEMS ON e.lender_id = CO_ITEMS.lender_id AND e.title = CO_ITEMS.title
    WHERE e.equipment_id = ? AND e.status = 'Available'
");
$stmt->execute([$equipment_id]);
$equipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipment) {
    echo "<script>alert('Equipment not found or currently unavailable.'); window.location.href='categories.php';</script>";
    exit();
}

// Fallback security deposit if not found in items table
$security_deposit = isset($equipment['security_deposit']) ? floatval($equipment['security_deposit']) : 1000.00;
$price_per_day = floatval($equipment['price_per_day']);
$min_booking_days = max(1, intval($equipment['min_booking_days']));

// Fetch Renter Details for Registered Address
$stmt_user = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE user_id = ?");
$stmt_user->execute([$renter_id]);
$renter_user = $stmt_user->fetch(PDO::FETCH_ASSOC);

$error_msg = "";
$success_msg = "";

// 3. Handle Form Submission (Server-side processing)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 1);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    
    // Address handling
    $use_registered_address = isset($_POST['use_registered_address']) ? true : false;
    $delivery_address = $use_registered_address ? ($renter_user['address'] ?? '') : trim($_POST['manual_address'] ?? '');

    // Server-Side Validations
    $today = date('Y-m-d');
    if (empty($start_date) || empty($end_date) || empty($phone_number) || empty($id_number) || empty($delivery_address)) {
        $error_msg = "All required fields must be filled out.";
    } elseif ($start_date < $today) {
        $error_msg = "Rental start date cannot be in the past.";
    } elseif ($end_date < $start_date) {
        $error_msg = "Rental end date cannot be before the start date.";
    } elseif ($quantity < 1) {
        $error_msg = "Quantity must be at least 1.";
    } else {
        // Calculate days
        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($end_date);
        $interval = $datetime1->diff($datetime2);
        $total_days = $interval->days + 1;

        if ($total_days < $min_booking_days) {
            $error_msg = "Rental duration must satisfy the minimum booking days requirement of {$min_booking_days} days.";
        } else {
            // Check Availability (Overlapping dates check)
            $check_avail = $pdo->prepare("
                SELECT COUNT(*) FROM bookings 
                WHERE equipment_id = ? AND status IN ('Pending', 'Accepted', 'Delivered') 
                AND NOT (end_date < ? OR start_date > ?)
            ");
            $check_avail->execute([$equipment_id, $start_date, $end_date]);
            if ($check_avail->fetchColumn() > 0) {
                $error_msg = "Sorry, this equipment is already booked for the selected dates.";
            } else {
                // Secure File Upload Handling for Identity Verification
                if (!isset($_FILES['id_proof_doc']) || $_FILES['id_proof_doc']['error'] === UPLOAD_ERR_NO_FILE) {
                    $error_msg = "Please upload your Government ID proof document.";
                } else {
                    $file = $_FILES['id_proof_doc'];
                    $max_size = 5 * 1024 * 1024; // 5 MB
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if ($file['size'] > $max_size) {
                        $error_msg = "File size exceeds the maximum limit of 5 MB.";
                    } elseif (!in_array($mime_type, $allowed_types)) {
                        $error_msg = "Invalid file format. Only JPG, JPEG, PNG, and PDF formats are accepted.";
                    } else {
                        // Secure upload directory outside web or safely managed inside uploads folder
                        $upload_dir = 'uploads/id_proofs/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $secure_filename = 'ID_' . $renter_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                        $destination = $upload_dir . $secure_filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Calculations on Server Side
                            $total_rent = $price_per_day * $total_days * $quantity;
                            $advance_amount = $security_deposit; // Advance amount tied to security deposit
                            $remaining_cod = max(0, $total_rent - $advance_amount);
                            $request_code = 'REQ-' . strtoupper(substr(uniqid(), -6));

                            // Insert Booking Record
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO bookings (request_code, renter_id, equipment_id, start_date, end_date, total_days, quantity, total_amount, advance_amount, remaining_cod, phone_number, id_number, id_proof_doc, delivery_address, status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
                            ");
                            
                            $success = $insert_stmt->execute([
                                $request_code, $renter_id, $equipment_id, $start_date, $end_date, 
                                $total_days, $quantity, $total_rent, $advance_amount, $remaining_cod, 
                                $phone_number, $id_number, $secure_filename, $delivery_address
                            ]);

                            if ($success) {
                                echo "<script>alert('Booking request submitted successfully! Your Request Code is {$request_code}'); window.location.href='renter_dashboard.php';</script>";
                                exit();
                            } else {
                                $error_msg = "Database error occurred while saving your booking request.";
                            }
                        } else {
                            $error_msg = "Failed to upload the identity verification document.";
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Now - Equipment Rental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background: #ffffff; border-right: 1px solid #dee2e6; position: fixed; width: 250px; top: 0; left: 0; padding-top: 20px; }
        .sidebar a { padding: 12px 20px; display: block; color: #333; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; }
        .main-content { margin-left: 250px; padding: 30px; }
        .agro-theme-header { color: #2e7d32; font-weight: 700; }
        .btn-agro { background-color: #2e7d32; color: #fff; border: none; }
        .btn-agro:hover { background-color: #1b5e20; color: #fff; }
        .card-custom { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; min-height: auto; }
            .main-content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar d-none d-md-block">
        <h4 class="text-center agro-theme-header mb-4"><i class="fa-solid fa-tractor"></i> AgriRent</h4>
        <a href="renter_dashboard.php"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
        <a href="my_bookings.php"><i class="fa-solid fa-calendar-check me-2"></i> My Bookings</a>
        <a href="categories.php" class="active"><i class="fa-solid fa-list me-2"></i> Categories</a>
        <a href="profile.php"><i class="fa-solid fa-user me-2"></i> My Profile</a>
        <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
    </div>

    <!-- MAIN CONTENT CONTAINER -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4 agro-theme-header"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Rent Now</h2>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_msg); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- LEFT COLUMN: Booking Configurations -->
                    <div class="col-lg-8">
                        
                        <!-- 1. Selected Equipment Section -->
                        <div class="card card-custom p-4 mb-4">
                            <h4 class="mb-3 text-success"><i class="fa-solid fa-circle-check me-2"></i> Selected Equipment</h4>
                            <div class="row align-items-center">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <?php 
                                        $img_path = !empty($equipment['image']) ? 'uploads/' . $equipment['image'] : 'assets/img/default.png';
                                    ?>
                                    <img src="<?= htmlspecialchars($img_path); ?>" alt="Equipment Image" class="img-fluid rounded border" style="height: 140px; width: 100%; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($equipment['title']); ?></h5>
                                    <p class="text-muted mb-2">Category: <strong><?= htmlspecialchars($equipment['category']); ?></strong> | Brand/Model: <strong><?= htmlspecialchars($equipment['brand_model']); ?></strong></p>
                                    <p class="mb-1 text-secondary small"><i class="fa-solid fa-user-tie me-1"></i> Lender: <?= htmlspecialchars($equipment['lender_name']); ?></p>
                                    <p class="mb-1 text-secondary small"><i class="fa-solid fa-location-dot me-1"></i> Service Location: <?= htmlspecialchars($equipment['service_location']); ?></p>
                                    <p class="mb-0 text-success fw-bold">₹<?= number_format($price_per_day, 2); ?> / day &nbsp;|&nbsp; Min. Booking: <?= $min_booking_days; ?> Days</p>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Rental Details Section -->
                        <div class="card card-custom p-4 mb-4">
                            <h4 class="mb-3 text-success"><i class="fa-solid fa-calendar-days me-2"></i> Rental Details</h4>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Rental Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d'); ?>" required value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Rental End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" min="<?= date('Y-m-d'); ?>" required value="<?= htmlspecialchars($_POST['end_date'] ?? date('Y-m-d', strtotime('+1 day'))); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-semibold">Quantity</label>
                                    <select class="form-select" id="quantity" name="quantity">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <option value="<?= $i; ?>" <?= (isset($_POST['quantity']) && $_POST['quantity'] == $i) ? 'selected' : ''; ?>><= $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted">Note: Minimum booking requirement for this item is <strong><?= $min_booking_days; ?></strong> days.</small>
                        </div>

                        <!-- 3. Delivery Address Section -->
                        <div class="card card-custom p-4 mb-4">
                            <h4 class="mb-3 text-success"><i class="fa-solid fa-truck-fast me-2"></i> Delivery Address</h4>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="use_registered_address" name="use_registered_address" checked onchange="toggleAddressFields()">
                                <label class="form-check-label fw-semibold" for="use_registered_address">
                                    Use Registered Profile Address
                                </label>
                            </div>
                            <div id="registered_address_display" class="p-3 bg-light rounded mb-2 border text-secondary">
                                <strong>Saved Address:</strong> <?= !empty($renter_user['address']) ? htmlspecialchars($renter_user['address']) : 'No registered address found in profile. Please uncheck to enter manually.'; ?>
                            </div>
                            <div id="manual_address_wrapper" style="display: none;" class="mb-2">
                                <label class="form-label fw-semibold">Enter Delivery Address</label>
                                <textarea class="form-control" name="manual_address" rows="3" placeholder="Provide complete delivery address (State, District, Area, PIN Code)"><?= htmlspecialchars($_POST['manual_address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- 4. Identity Verification Section -->
                        <div class="card card-custom p-4 mb-4">
                            <h4 class="mb-3 text-success"><i class="fa-solid fa-id-card me-2"></i> Identity Verification</h4>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" class="form-control" name="phone_number" value="<?= htmlspecialchars($_POST['phone_number'] ?? $renter_user['phone']); ?>" required placeholder="Enter contact phone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Government ID Number (Aadhaar / Voter ID / PAN)</label>
                                    <input type="text" class="form-control" name="id_number" value="<?= htmlspecialchars($_POST['id_number'] ?? ''); ?>" required placeholder="Enter valid ID number">
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label fw-semibold">Upload ID Proof Document (JPG, PNG, PDF | Max: 5MB)</label>
                                    <input type="file" class="form-control" name="id_proof_doc" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <small class="text-muted">Your document is kept strictly confidential and secure.</small>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: Order Summary & Actions -->
                    <div class="col-lg-4">
                        <div class="card card-custom p-4 sticky-top" style="top: 20px;">
                            <h4 class="mb-3 text-success border-bottom pb-2">Order Summary</h4>
                            
                            <ul class="list-unstyled mb-4">
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Price per day:</span>
                                    <span class="fw-semibold">₹<span id="summary_price_per_day"><?= number_format($price_per_day, 2); ?></span></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Rental Days:</span>
                                    <span class="fw-semibold" id="summary_rental_days">1</span>
                                </li>
                                <li class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Quantity:</span>
                                    <span class="fw-semibold" id="summary_quantity">1</span>
                                </li>
                                <hr>
                                <li class="d-flex justify-content-between mb-2 fs-5">
                                    <span class="fw-bold text-dark">Total Rent:</span>
                                    <span class="fw-bold text-success">₹<span id="summary_total_rent">0.00</span></span>
                                </li>
                                <li class="d-flex justify-content-between mb-2 text-danger">
                                    <span>Advance / Security Deposit:</span>
                                    <span class="fw-semibold">₹<span id="summary_advance"><?= number_format($security_deposit, 2); ?></span></span>
                                </li>
                                <li class="d-flex justify-content-between mb-3 bg-light p-2 rounded">
                                    <span class="fw-bold">Remaining (COD):</span>
                                    <span class="fw-bold text-primary">₹<span id="summary_remaining">0.00</span></span>
                                </li>
                            </ul>

                            <button type="submit" class="btn btn-agro w-100 py-2 mb-2 fw-bold"><i class="fa-solid fa-check-circle me-1"></i> Confirm Booking</button>
                            <a href="categories.php" class="btn btn-outline-secondary w-100 py-2"><i class="fa-solid fa-arrow-left me-1"></i> Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Dynamic Real-Time Calculations -->
    <script>
        const pricePerDay = <?= $price_per_day; ?>;
        const securityDeposit = <?= $security_deposit; ?>;
        const minBookingDays = <?= $min_booking_days; ?>;

        function toggleAddressFields() {
            const checkbox = document.getElementById('use_registered_address');
            const displayDiv = document.getElementById('registered_address_display');
            const manualDiv = document.getElementById('manual_address_wrapper');
            
            if (checkbox.checked) {
                displayDiv.style.display = 'block';
                manualDiv.style.display = 'none';
            } else {
                displayDiv.style.display = 'none';
                manualDiv.style.display = 'block';
            }
        }

        function calculateSummary() {
            const startDateVal = document.getElementById('start_date').value;
            const endDateVal = document.getElementById('end_date').value;
            const quantityVal = parseInt(document.getElementById('quantity').value) || 1;

            if (startDateVal && endDateVal) {
                const start = new Date(startDateVal);
                const end = new Date(endDateVal);
                const diffTime = end - start;
                let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                if (diffDays < 1) diffDays = 1;

                document.getElementById('summary_rental_days').innerText = diffDays;
                document.getElementById('summary_quantity').innerText = quantityVal;

                const totalRent = pricePerDay * diffDays * quantityVal;
                let remaining = totalRent - securityDeposit;
                if (remaining < 0) remaining = 0;

                document.getElementById('summary_total_rent').innerText = totalRent.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('summary_advance').innerText = securityDeposit.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('summary_remaining').innerText = remaining.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        }

        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').min = this.value;
            calculateSummary();
        });
        document.getElementById('end_date').addEventListener('change', calculateSummary);
        document.getElementById('quantity').addEventListener('change', calculateSummary);

        // Run calculation on load
        window.onload = calculateSummary;
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>