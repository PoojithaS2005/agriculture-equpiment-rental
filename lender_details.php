<?php
session_start();

require_once __DIR__ . '/includes/config.php';

$allowed_languages = ['en', 'kn', 'hi'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_languages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    $_SESSION['language'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? $_SESSION['language'] ?? 'en';

if (!in_array($current_lang, $allowed_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
    $_SESSION['language'] = 'en';
}

require_once __DIR__ . '/includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$renter_id = (int)$_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) {
    header("Location: my_bookings.php?lang=" . urlencode($current_lang));
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch the lender belonging to THIS booking's equipment
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        b.booking_id,
        b.request_code,
        b.equipment_id,
        e.title AS equipment_title,
        e.category AS equipment_category,
        e.image AS equipment_image,
        e.service_location,
        u.user_id AS lender_id,
        u.full_name AS lender_name,
        u.email AS lender_email,
        u.phone AS lender_phone,
        u.address AS lender_address,
        u.profile_pic AS lender_profile_pic
    FROM bookings b
    JOIN equipment e
        ON b.equipment_id = e.equipment_id
    JOIN users u
        ON e.lender_id = u.user_id
    WHERE b.booking_id = ?
      AND b.renter_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$lender = null;

if ($stmt) {
    $stmt->bind_param("ii", $booking_id, $renter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $lender = $result->fetch_assoc();
    $stmt->close();
}

if (!$lender) {
    header("Location: my_bookings.php?lang=" . urlencode($current_lang));
    exit();
}

$lang_param = '?lang=' . urlencode($current_lang);

/*
|--------------------------------------------------------------------------
| Unread notification count
|--------------------------------------------------------------------------
*/
$notif_count = 0;
$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($notif_check && $notif_check->num_rows > 0) {
    $n_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ?
          AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($n_stmt) {
        $n_stmt->bind_param("i", $renter_id);
        $n_stmt->execute();
        $n_res = $n_stmt->get_result()->fetch_assoc();
        $notif_count = (int)($n_res['cnt'] ?? 0);
        $n_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars(__('lender_details_title')); ?> - Agriculture Equipment Rental System</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f4f6f9;
            color: #1e293b;
            margin: 0;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px 30px;
        }

        .top-nav-bar {
            background: #fff;
            padding: 12px 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .profile-avatar-btn {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            text-decoration: none;
            border: 2px solid #cbd5e1;
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 5px;
            color: #0f172a;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 13px;
        }

        .btn-back {
            border: 1px solid #198754;
            color: #198754;
            background: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .btn-back:hover {
            background: #198754;
            color: #fff;
        }

        .content-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,.02);
        }

        .card-title {
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 18px;
            color: #0f172a;
        }

        .lender-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        .lender-photo {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #dbe3ea;
            background: #f1f5f9;
        }

        .lender-placeholder {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 32px;
            border: 1px solid #dbe3ea;
        }

        .lender-name {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-top: 20px;
        }

        .detail-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 13px 15px;
        }

        .detail-label {
            display: block;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .detail-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 700;
            word-break: break-word;
        }

        .equipment-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .equipment-image {
            width: 110px;
            height: 80px;
            border-radius: 8px;
            object-fit: contain;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .equipment-placeholder {
            width: 110px;
            height: 80px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 26px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .page-head {
                flex-direction: column;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    

        /* Screenshot-matched renter interface theme: mint + warm cream */
        :root {
            --renter-ink: #173b2b;
            --renter-green: #236b45;
            --renter-green-dark: #155333;
            --renter-mint: #dcefe0;
            --renter-border: rgba(255,255,255,.88);
            --renter-shadow: 0 12px 30px rgba(37,82,52,.10);
        }
        html { min-height: 100%; }
        body {
            background: linear-gradient(125deg,#dcebd5 0%,#f4f0d9 48%,#dcebdc 100%) !important;
            color: var(--renter-ink) !important;
            min-height: 100vh;
        }
        .main-content { color: var(--renter-ink); }
        .top-nav-bar, .top-navbar, .details-container, .content-card,
        .card-custom, .payment-box, .spec-box, .detail-item, .meta-item,
        .info-pill, .review-item, .empty-state, .upload-box {
            background: rgba(255,255,255,.68) !important;
            border: 1px solid var(--renter-border) !important;
            box-shadow: var(--renter-shadow), inset 0 1px 0 rgba(255,255,255,.92) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 18px !important;
        }
        .top-nav-bar, .top-navbar {
            background: rgba(255,255,255,.62) !important;
        }
        .page-title, .page-header, .page-head h1, .equipment-title,
        .card-title, .card-title-custom, .lender-name, .details-container h1,
        .details-container h2, .details-container h3, .main-content h1,
        .main-content h2, .main-content h3, .main-content h4, .main-content h5 {
            color: #173b2b !important;
        }
        .page-subtitle, .breadcrumb-nav, .detail-label, .spec-label,
        .review-date, .text-muted { color: #668071 !important; }
        .detail-value, .spec-value, .meta-item span, .reviewer-name,
        .review-text, .equipment-title, .card-title-custom { color: #244b35 !important; }
        .detail-item, .spec-box, .meta-item, .info-pill {
            background: rgba(245,251,244,.72) !important;
            border-color: rgba(76,130,91,.13) !important;
        }
        input:not([type=checkbox]):not([type=radio]), select, textarea,
        .form-control, .form-select, .lang-select {
            background: rgba(255,255,255,.82) !important;
            border: 1px solid rgba(45,106,79,.18) !important;
            color: #244b35 !important;
            border-radius: 11px !important;
            box-shadow: none !important;
        }
        input:focus, select:focus, textarea:focus, .form-control:focus,
        .form-select:focus {
            border-color: #79b88b !important;
            box-shadow: 0 0 0 3px rgba(45,106,79,.12) !important;
        }
        .btn-agro, .btn-primary, .btn-success, .btn-rent-now,
        .top-nav-bar .btn-success, .top-navbar .btn-success {
            background: linear-gradient(135deg,#438b5c,#236b45) !important;
            border-color: transparent !important;
            color: #fff !important;
            border-radius: 11px !important;
            box-shadow: 0 6px 14px rgba(35,107,69,.16);
        }
        .btn-agro:hover, .btn-primary:hover, .btn-success:hover,
        .btn-rent-now:hover { background: #155333 !important; color:#fff !important; }
        .btn-back, .btn-outline-success {
            color: #236b45 !important;
            border-color: rgba(35,107,69,.35) !important;
            background: rgba(255,255,255,.7) !important;
            border-radius: 10px !important;
        }
        .btn-back:hover, .btn-outline-success:hover {
            color:#fff !important; background:#236b45 !important;
        }
        .top-banner {
            background: linear-gradient(115deg,#397c50,#1e5939) !important;
            color:#fff !important;
        }
        .img-preview-container, .equipment-image, .equipment-placeholder,
        .equipment-img, .card-img-container {
            background: rgba(236,247,235,.82) !important;
            border-color: rgba(45,106,79,.12) !important;
        }
        .action-footer {
            background: rgba(239,248,237,.78) !important;
            border-color: rgba(45,106,79,.12) !important;
        }
        .step.active .step-circle, .step.completed .step-circle {
            background: #398454 !important;
        }
        .step { background: transparent !important; }
        .stepper::before, .timeline-list::before { background: rgba(45,106,79,.20) !important; }
        .timeline-item.active::before { background:#398454 !important; }
        .upload-box:hover { border-color:#438b5c !important; background:rgba(232,247,234,.88) !important; }
        .price-highlight, .price-tag, .brand-logo { color:#236b45 !important; }
        .table, .table td, .table th { color:#244b35; }
        .table thead th { background:rgba(224,241,225,.8) !important; color:#214b32 !important; }
        .table tbody tr { background:rgba(255,255,255,.45); }
        .alert { border-radius:14px !important; }
        @media (max-width: 768px) {
            .main-content { padding:16px !important; }
            .content-card, .card-custom { border-radius:16px !important; }
        }

    </style>
</head>

<body>

<?php include __DIR__ . '/renter_sidebar.php'; ?>

<div class="main-content">

    <div class="top-nav-bar">
        <form action="lender_details.php" method="GET" class="d-flex align-items-center mb-0">
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">

            <select
                name="lang"
                class="form-select form-select-sm fw-bold w-auto"
                onchange="this.form.submit()"
            >
                <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>
                    English
                </option>
                <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>
                    हिंदी (Hindi)
                </option>
                <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>
                    ಕನ್ನಡ (Kannada)
                </option>
            </select>
        </form>

        <a
            href="notifications.php<?php echo $lang_param; ?>"
            class="position-relative text-dark text-decoration-none p-1"
            title="<?php echo htmlspecialchars(__('notifications')); ?>"
        >
            <i class="fa-solid fa-bell fa-lg"></i>

            <?php if ($notif_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;">
                    <?php echo $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a
            href="profile.php<?php echo $lang_param; ?>"
            class="profile-avatar-btn"
            title="<?php echo htmlspecialchars(__('my_profile')); ?>"
        >
            <i class="fa-solid fa-user"></i>
        </a>
    </div>

    <div class="page-head">
        <div>
            <h1 class="page-title">
                <?php echo htmlspecialchars(__('lender_details_title')); ?>
            </h1>
            <div class="page-subtitle">
                <?php echo htmlspecialchars(__('lender_details_subtitle')); ?>
            </div>
        </div>

        <a href="booking_details.php?booking_id=<?php echo $booking_id; ?>&lang=<?php echo urlencode($current_lang); ?>" class="btn-back">
            &larr; <?php echo htmlspecialchars(__('back_to_bookings_details')); ?>
        </a>
    </div>

    <div class="content-card">
        <div class="card-title">
            <i class="fa-solid fa-user text-success me-2"></i>
            <?php echo htmlspecialchars(__('lender_name')); ?>
        </div>

        <div class="lender-header">

            <?php
            $profile_path = '';
            if (!empty($lender['lender_profile_pic'])) {
                $candidate = 'uploads/' . $lender['lender_profile_pic'];
                if (is_file(__DIR__ . '/' . $candidate)) {
                    $profile_path = $candidate;
                }
            }
            ?>

            <?php if ($profile_path): ?>
                <img
                    src="<?php echo htmlspecialchars($profile_path); ?>"
                    alt="<?php echo htmlspecialchars($lender['lender_name']); ?>"
                    class="lender-photo"
                >
            <?php else: ?>
                <div class="lender-placeholder">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>

            <div>
                <div class="lender-name">
                    <?php echo htmlspecialchars($lender['lender_name']); ?>
                </div>
                <div class="text-muted" style="font-size:13px;">
                    <?php echo htmlspecialchars(__('lender_role')); ?>
                </div>
            </div>

        </div>

        <div class="detail-grid">

            <div class="detail-item">
                <span class="detail-label"><?php echo htmlspecialchars(__('full_name')); ?></span>
                <span class="detail-value"><?php echo htmlspecialchars($lender['lender_name']); ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?php echo htmlspecialchars(__('email_address')); ?></span>
                <span class="detail-value"><?php echo htmlspecialchars($lender['lender_email']); ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?php echo htmlspecialchars(__('phone_number')); ?></span>
                <span class="detail-value"><?php echo htmlspecialchars($lender['lender_phone']); ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?php echo htmlspecialchars(__('address')); ?></span>
                <span class="detail-value">
                    <?php echo !empty($lender['lender_address']) ? htmlspecialchars($lender['lender_address']) : '—'; ?>
                </span>
            </div>

        </div>
    </div>

    <div class="content-card">

        <div class="card-title">
            <i class="fa-solid fa-tractor text-success me-2"></i>
            <?php echo htmlspecialchars(__('equipment')); ?>
        </div>

        <div class="equipment-box">

            <?php
            $equipment_path = '';
            if (!empty($lender['equipment_image'])) {
                $candidate = 'uploads/' . $lender['equipment_image'];
                if (is_file(__DIR__ . '/' . $candidate)) {
                    $equipment_path = $candidate;
                }
            }
            ?>

            <?php if ($equipment_path): ?>
                <img
                    src="<?php echo htmlspecialchars($equipment_path); ?>"
                    alt="<?php echo htmlspecialchars($lender['equipment_title']); ?>"
                    class="equipment-image"
                >
            <?php else: ?>
                <div class="equipment-placeholder">
                    <i class="fa-solid fa-tractor"></i>
                </div>
            <?php endif; ?>

            <div>
                <div class="fw-bold" style="font-size:16px;">
                    <?php echo htmlspecialchars($lender['equipment_title']); ?>
                </div>

                <div class="text-muted" style="font-size:12px;">
                    <?php echo htmlspecialchars(__('category')); ?>:
                    <?php echo htmlspecialchars($lender['equipment_category']); ?>
                </div>

                <div class="text-muted mt-1" style="font-size:12px;">
                    <i class="fa-solid fa-location-dot text-danger me-1"></i>
                    <?php echo htmlspecialchars($lender['service_location']); ?>
                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>
