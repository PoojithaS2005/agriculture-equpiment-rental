<?php
session_start();

require_once 'includes/config.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'hi', 'kn'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$lender_id = (int)$_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';
$lang_param = '?lang=' . urlencode($current_lang);

$role = $_SESSION['user_type'] ?? $_SESSION['role'] ?? 'lender';

if ($role !== 'lender') {
    header('Location: renter_dashboard.php' . $lang_param);
    exit;
}

/*
|--------------------------------------------------------------------------
| Average Rating and Review Count
|--------------------------------------------------------------------------
*/
$avg_stmt = $conn->prepare("
    SELECT
        COALESCE(AVG(rv.rating), 0) AS avg_rating,
        COUNT(rv.review_id) AS review_count
    FROM reviews rv
    JOIN equipment e
        ON rv.equipment_id = e.equipment_id
    WHERE e.lender_id = ?
      AND rv.lender_id = ?
");

$avg_stmt->bind_param('ii', $lender_id, $lender_id);
$avg_stmt->execute();

$summary = $avg_stmt
    ->get_result()
    ->fetch_assoc();

$avg_stmt->close();

/*
|--------------------------------------------------------------------------
| Fetch Lender Reviews
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        rv.*,
        e.title AS equipment_title,
        e.image AS equipment_image,
        b.request_code,
        u.full_name AS renter_name
    FROM reviews rv
    JOIN equipment e
        ON rv.equipment_id = e.equipment_id
    LEFT JOIN bookings b
        ON rv.booking_id = b.booking_id
    LEFT JOIN users u
        ON rv.renter_id = u.user_id
    WHERE e.lender_id = ?
      AND rv.lender_id = ?
    ORDER BY rv.created_at DESC
");

$stmt->bind_param('ii', $lender_id, $lender_id);
$stmt->execute();

$res = $stmt->get_result();

$reviews = [];

while ($r = $res->fetch_assoc()) {
    $reviews[] = $r;
}

$stmt->close();
?>

<!doctype html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        <?php echo __('reviews'); ?> - Agriculture Equipment Rental
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <style>

        body {
            margin: 0;
            background: #f4f6f9;
            color: #1e293b;
            font-family: 'Segoe UI', sans-serif;
        }

        .main {
            margin-left: 250px;
            padding: 25px 30px;
        }

        .top {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 18px;
            margin-bottom: 25px;
        }

        .summary {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .stat {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 25px;
            min-width: 200px;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 800;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 900;
            color: #0f172a;
        }

        .review {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .equipment {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .equipment img {
            width: 90px;
            height: 70px;
            object-fit: cover;
            border-radius: 9px;
            background: #f1f5f9;
        }

        .stars {
            color: #f59e0b;
            letter-spacing: 1px;
        }

        .review-text {
            line-height: 1.6;
            color: #334155;
            white-space: pre-wrap;
        }

        .muted {
            color: #64748b;
            font-size: 12px;
        }

        .empty {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            text-align: center;
            padding: 50px;
        }

        @media (max-width: 991.98px) {

            .main {
                margin-left: 220px;
                padding: 20px;
            }

        }

        @media (max-width: 768px) {

            .main {
                margin-left: 0;
                padding: 15px;
            }

        }

    </style>

</head>

<body>

    <!-- SHARED LENDER SIDEBAR -->
    <?php include 'lender_sidebar.php'; ?>

    <main class="main">

        <div class="top">

            <select
                class="form-select form-select-sm"
                style="width:auto"
                onchange="location=this.value"
            >

                <option
                    value="reviews.php?lang=en"
                    <?php echo $current_lang === 'en' ? 'selected' : ''; ?>
                >
                    English
                </option>

                <option
                    value="reviews.php?lang=hi"
                    <?php echo $current_lang === 'hi' ? 'selected' : ''; ?>
                >
                    हिन्दी
                </option>

                <option
                    value="reviews.php?lang=kn"
                    <?php echo $current_lang === 'kn' ? 'selected' : ''; ?>
                >
                    ಕನ್ನಡ
                </option>

            </select>

            <a
                class="text-dark"
                href="lender_notifications.php<?php echo $lang_param; ?>"
            >
                <i class="fa-solid fa-bell fa-lg"></i>
            </a>

        </div>

        <h1 class="fw-bold">
            ⭐ <?php echo __('reviews'); ?>
        </h1>

        <p class="text-muted fw-semibold">
            <?php echo __('lender_reviews_subtitle'); ?>
        </p>

        <div class="summary">

            <div class="stat">

                <div class="stat-label">
                    <?php echo __('average_rating'); ?>
                </div>

                <div class="stat-value">
                    ⭐
                    <?php echo number_format((float)$summary['avg_rating'], 1); ?>/5
                </div>

            </div>

            <div class="stat">

                <div class="stat-label">
                    <?php echo __('total_reviews'); ?>
                </div>

                <div class="stat-value">
                    <?php echo (int)$summary['review_count']; ?>
                </div>

            </div>

        </div>

        <?php if (!$reviews): ?>

            <div class="empty">

                <i class="fa-regular fa-star fa-3x text-muted mb-3"></i>

                <h5 class="fw-bold">
                    <?php echo __('no_reviews_yet'); ?>
                </h5>

                <p class="text-muted">
                    <?php echo __('no_lender_reviews_message'); ?>
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($reviews as $r): ?>

                <div class="review">

                    <div class="equipment">

                        <div>

                            <?php if (!empty($r['equipment_image'])): ?>

                                <img
                                    src="uploads/<?php echo htmlspecialchars($r['equipment_image']); ?>"
                                    alt="Agricultural equipment"
                                >

                            <?php else: ?>

                                <div
                                    style="
                                        width:90px;
                                        height:70px;
                                        border-radius:9px;
                                        background:#f1f5f9;
                                        display:flex;
                                        align-items:center;
                                        justify-content:center;
                                    "
                                >

                                    <i class="fa-solid fa-tractor text-muted"></i>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="flex-grow-1">

                            <h5 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($r['equipment_title']); ?>
                            </h5>

                            <div class="muted">

                                <?php echo __('id'); ?>:
                                <?php echo htmlspecialchars($r['request_code'] ?? '-'); ?>

                                ·

                                <?php echo htmlspecialchars(
                                    $r['renter_name'] ?? 'Customer'
                                ); ?>

                            </div>

                        </div>

                        <div class="text-end">

                            <div class="stars">

                                <?php
                                echo str_repeat(
                                    '★',
                                    (int)$r['rating']
                                );
                                ?>

                                <span style="color:#cbd5e1">

                                    <?php
                                    echo str_repeat(
                                        '★',
                                        5 - (int)$r['rating']
                                    );
                                    ?>

                                </span>

                            </div>

                            <div class="muted">

                                <?php
                                echo htmlspecialchars(
                                    date(
                                        'd M Y',
                                        strtotime($r['created_at'])
                                    )
                                );
                                ?>

                            </div>

                        </div>

                    </div>

                    <div class="review-text mt-3">

                        <?php
                        echo nl2br(
                            htmlspecialchars($r['review_text'])
                        );
                        ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </main>

</body>

</html>