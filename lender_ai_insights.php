
<?php
session_start();

require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/config.php';

// Only logged-in lenders can access this page
if (
    !isset($_SESSION['user_id']) ||
    strtolower($_SESSION['role'] ?? '') !== 'lender'
) {
    header("Location: login.php");
    exit();
}

$lender_id = (int) $_SESSION['user_id'];
$lender_name = $_SESSION['full_name'] ?? 'Lender';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '₹' . number_format((float)$value, 2);
}

// Get this lender's equipment
$equipment = [];

$stmt = $conn->prepare("
    SELECT equipment_id, title, category, price_per_day,
           equipment_condition, status, total_quantity
    FROM equipment
    WHERE lender_id = ?
    ORDER BY title
");

$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $equipment[] = $row;
}
$stmt->close();

// Get booking history for this lender
$bookings = [];

$stmt = $conn->prepare("
    SELECT b.booking_id, b.equipment_id, b.start_date,
           b.end_date, b.total_days, b.quantity,
           b.total_amount, b.status, e.title
    FROM bookings b
    INNER JOIN equipment e
        ON e.equipment_id = b.equipment_id
    WHERE e.lender_id = ?
    ORDER BY b.start_date DESC
");

$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

// Initialize summary values
$totalEquipment = count($equipment);
$totalBookings = 0;
$completedBookings = 0;
$activeBookings = 0;
$totalRevenue = 0;
$bookedDays = 0;
$monthlyBookings = array_fill(0, 6, 0);

$monthLabels = [];

for ($i = 5; $i >= 0; $i--) {
    $monthLabels[] = date('M Y', strtotime("-$i months"));
}

$monthKeys = [];

for ($i = 5; $i >= 0; $i--) {
    $monthKeys[] = date('Y-m', strtotime("-$i months"));
}

$validStatuses = [
    'accepted', 'delivered', 'returned', 'completed', 'overdue'
];

foreach ($bookings as $booking) {
    $status = strtolower(trim($booking['status'] ?? ''));

    if (in_array($status, ['rejected', 'cancelled'])) {
        continue;
    }

    $totalBookings++;

    if ($status === 'completed' || $status === 'returned') {
        $completedBookings++;
        $totalRevenue += (float)$booking['total_amount'];
    }

    if (in_array($status, ['accepted', 'delivered', 'overdue'])) {
        $activeBookings++;
    }

    $days = max(1, (int)$booking['total_days']);
    $bookedDays += $days;

    $month = date('Y-m', strtotime($booking['start_date']));

    $index = array_search($month, $monthKeys, true);

    if ($index !== false) {
        $monthlyBookings[$index]++;
    }
}

// Equipment price comparison and recommendations
$equipmentInsights = [];

foreach ($equipment as $item) {
    $category = $item['category'];
    $currentPrice = (float)$item['price_per_day'];

    $averagePrice = $currentPrice;

    $priceStmt = $conn->prepare("
        SELECT AVG(price_per_day) AS average_price,
               COUNT(*) AS equipment_count
        FROM equipment
        WHERE category = ?
          AND status <> 'Inactive'
    ");

    $priceStmt->bind_param("s", $category);
    $priceStmt->execute();

    $priceData = $priceStmt->get_result()->fetch_assoc();
    $priceStmt->close();

    if (
        !empty($priceData['equipment_count']) &&
        (float)$priceData['average_price'] > 0
    ) {
        $averagePrice = (float)$priceData['average_price'];
    }

    // Suggest a rounded price based on the category average
    $suggestedPrice = round($averagePrice / 10) * 10;

    // Count valid bookings for this specific equipment
    $equipmentBookingCount = 0;
    $equipmentBookedDays = 0;

    foreach ($bookings as $booking) {
        if ((int)$booking['equipment_id'] !== (int)$item['equipment_id']) {
            continue;
        }

        $status = strtolower(trim($booking['status'] ?? ''));

        if (in_array($status, ['rejected', 'cancelled'])) {
            continue;
        }

        $equipmentBookingCount++;
        $equipmentBookedDays += max(1, (int)$booking['total_days']);
    }

    // Estimate utilization over the previous 90 days
    $utilization = min(100, round(($equipmentBookedDays / 90) * 100));

    // Demand indicator based on observed booking count
    if ($equipmentBookingCount >= 5) {
        $demand = 'High';
    } elseif ($equipmentBookingCount >= 2) {
        $demand = 'Medium';
    } else {
        $demand = 'Low / Limited data';
    }

    // Basic maintenance guidance, not a mechanical diagnosis
    $condition = strtolower(trim($item['equipment_condition'] ?? ''));
    $statusText = strtolower(trim($item['status'] ?? ''));

    if ($statusText === 'under maintenance') {
        $maintenance = 'Currently marked under maintenance';
    } elseif ($condition === 'used') {
        $maintenance = 'Inspect condition before the next rental';
    } elseif ($utilization >= 70) {
        $maintenance = 'Consider a routine inspection';
    } else {
        $maintenance = 'Follow the manufacturer service schedule';
    }

    $equipmentInsights[] = [
        'title' => $item['title'],
        'category' => $category,
        'price' => $currentPrice,
        'average' => $averagePrice,
        'suggested' => $suggestedPrice,
        'bookings' => $equipmentBookingCount,
        'utilization' => $utilization,
        'demand' => $demand,
        'status' => $item['status'],
        'maintenance' => $maintenance
    ];
}

// Overall demand summary
if ($totalBookings >= 10) {
    $overallDemand = 'Good booking activity';
} elseif ($totalBookings >= 1) {
    $overallDemand = 'Some booking activity; collect more history';
} else {
    $overallDemand = 'Not enough booking history yet';
}

$current_lang = $_SESSION['lang'] ?? $_SESSION['language'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="<?= h($current_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Lender AI Insights - Agriculture Equipment Rental</title>

    
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #eef8f0, #edf4ff, #f7f1ff);
            color: #26352d;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            padding: 28px;
            min-height: 100vh;
        }

        .hero {
            padding: 28px;
            margin-bottom: 24px;
            border-radius: 22px;
            color: white;
            background: linear-gradient(120deg, #147d50, #248f9b, #5366c8);
            box-shadow: 0 12px 30px rgba(43, 93, 100, .18);
        }

        .hero h1 {
            font-weight: 750;
            font-size: clamp(24px, 3vw, 34px);
        }

        .hero p {
            margin-bottom: 0;
            opacity: .93;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card, .panel, .equipment-card {
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(255, 255, 255, .95);
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(46, 71, 64, .07);
        }

        .summary-card {
            padding: 20px;
        }

        .summary-card small {
            color: #66756b;
        }

        .summary-value {
            display: block;
            margin-top: 8px;
            font-size: 25px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .panel {
            padding: 22px;
            margin-bottom: 22px;
        }

        .panel h2 {
            font-size: 20px;
            font-weight: 750;
            margin-bottom: 18px;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .equipment-card {
            padding: 20px;
            min-width: 0;
        }

        .equipment-card h3 {
            font-size: 18px;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #e8eee9;
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-row span:first-child {
            color: #647269;
        }

        .metric-row strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .notice {
            background: #fff8df;
            border: 1px solid #f1dfa0;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 20px;
            color: #69551a;
        }

        .pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 30px;
            background: #e2f5e9;
            color: #17683b;
            font-size: 12px;
            font-weight: 700;
        }

        .muted {
            color: #66756b;
            font-size: 13px;
        }

        @media (max-width: 1100px) {
            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .equipment-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .summary-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .summary-card {
                padding: 14px;
            }

            .summary-value {
                font-size: 20px;
            }

            .panel, .hero {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/lender_sidebar.php'; ?>

<main class="main-content">

    <section class="hero">
        <span class="pill">AI-ASSISTED ANALYSIS</span>
        <h1 class="mt-3">Lender AI Insights</h1>
        <p>
            Welcome, <?= h($lender_name) ?>.
            Review rental demand, pricing, utilization and equipment care.
        </p>
    </section>

    <div class="notice">
        <strong>Important:</strong>
        These are rule-based estimates from available booking and equipment
        records, not predictions from a trained machine-learning model.
        More booking history will improve the usefulness of the estimates.
    </div>

    <section class="summary-grid">

        <div class="summary-card">
            <small>Your equipment</small>
            <span class="summary-value"><?= $totalEquipment ?></span>
        </div>

        <div class="summary-card">
            <small>Eligible booking records</small>
            <span class="summary-value"><?= $totalBookings ?></span>
        </div>

        <div class="summary-card">
            <small>Active rentals</small>
            <span class="summary-value"><?= $activeBookings ?></span>
        </div>

        <div class="summary-card">
            <small>Recorded completed rental value</small>
            <span class="summary-value"><?= money($totalRevenue) ?></span>
        </div>

    </section>

    <section class="panel">
        <h2>Rental Booking Activity</h2>
        <div class="chart-container">
            <canvas id="bookingChart"></canvas>
        </div>
        <p class="muted mt-3">
            Shows eligible booking records grouped by their start month.
            Rejected and cancelled bookings are excluded.
        </p>
    </section>

    <section class="panel">
        <h2>Equipment Utilization Comparison</h2>

        <?php if (empty($equipmentInsights)): ?>
            <p>No equipment has been added to your account yet.</p>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="utilizationChart"></canvas>
            </div>
            <p class="muted mt-3">
                Estimated from recorded rental days over a 90-day period.
                This is an approximate indicator, not verified machine operating time.
            </p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Equipment AI Recommendations</h2>

        <p>
            <strong>Overall summary:</strong>
            <?= h($overallDemand) ?>.
            Review each equipment item below before changing prices or scheduling maintenance.
        </p>

        <?php if (empty($equipmentInsights)): ?>
            <p>No recommendations are available until you add equipment.</p>
        <?php else: ?>

            <div class="equipment-grid">

                <?php foreach ($equipmentInsights as $item): ?>

                    <article class="equipment-card">

                        <span class="pill"><?= h($item['category']) ?></span>

                        <h3 class="mt-3"><?= h($item['title']) ?></h3>

                        <div class="metric-row">
                            <span>Equipment status</span>
                            <strong><?= h($item['status']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Recorded bookings</span>
                            <strong><?= (int)$item['bookings'] ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Estimated demand</span>
                            <strong><?= h($item['demand']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Current daily price</span>
                            <strong><?= money($item['price']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Category average price</span>
                            <strong><?= money($item['average']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Suggested daily price</span>
                            <strong><?= money($item['suggested']) ?></strong>
                        </div>

                        <div class="metric-row">
                            <span>Estimated utilization</span>
                            <strong><?= (int)$item['utilization'] ?>%</strong>
                        </div>

                        <div class="metric-row">
                            <span>Maintenance guidance</span>
                            <strong><?= h($item['maintenance']) ?></strong>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>
    </section>

    <p class="muted text-center mt-4">
        Recommendations are advisory only. Check actual equipment condition,
        booking details and local market prices before making decisions.
    </p>

</main>

<script>
const monthLabels = <?= json_encode($monthLabels) ?>;
const monthlyBookings = <?= json_encode($monthlyBookings) ?>;

new Chart(document.getElementById('bookingChart'), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Eligible bookings',
            data: monthlyBookings,
            backgroundColor: 'rgba(31, 139, 87, 0.72)',
            borderColor: '#1f8b57',
            borderWidth: 1,
            borderRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});

const equipmentLabels = <?= json_encode(
    array_column($equipmentInsights, 'title')
) ?>;

const utilizationValues = <?= json_encode(
    array_column($equipmentInsights, 'utilization')
) ?>;

<?php if (!empty($equipmentInsights)): ?>
new Chart(document.getElementById('utilizationChart'), {
    type: 'bar',
    data: {
        labels: equipmentLabels,
        datasets: [{
            label: 'Estimated utilization (%)',
            data: utilizationValues,
            backgroundColor: 'rgba(79, 105, 210, 0.72)',
            borderColor: '#4f69d2',
            borderWidth: 1,
            borderRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: value => value + '%'
                }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>