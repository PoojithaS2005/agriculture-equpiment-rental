
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

// Fetch the lender's saved location so weather insights do not require manual coordinates.
$lenderLocation = '';
$lenderLocationCandidates = [];
$locationStmt = $conn->prepare("SELECT address, city, district, state FROM users WHERE user_id = ? LIMIT 1");
if ($locationStmt) {
    $locationStmt->bind_param('i', $lender_id);
    $locationStmt->execute();
    $locationRow = $locationStmt->get_result()->fetch_assoc() ?: [];
    $locationStmt->close();
    $locationParts = [];
    foreach (['city', 'district', 'state'] as $field) {
        $part = trim((string)($locationRow[$field] ?? ''));
        if ($part !== '' && !in_array(strtolower($part), array_map('strtolower', $locationParts), true)) {
            $locationParts[] = $part;
        }
    }
    $lenderLocation = implode(', ', $locationParts);
    // Try the city and district separately if the geocoder does not recognise the full location string.
    foreach (['city', 'district', 'address'] as $field) {
        $candidate = trim((string)($locationRow[$field] ?? ''));
        if ($candidate !== '' && !in_array($candidate, $lenderLocationCandidates, true)) {
            $lenderLocationCandidates[] = $candidate;
        }
    }
    if ($lenderLocation === '') {
        $lenderLocation = trim((string)($locationRow['address'] ?? ''));
    }
    if ($lenderLocation !== '' && !in_array($lenderLocation, $lenderLocationCandidates, true)) {
        $lenderLocationCandidates[] = $lenderLocation;
    }
}

function lender_ai_fetch_json(string $url): array {
    $body = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'Agriculture Equipment Rental System/1.0'
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $status < 200 || $status >= 300) {
            return [];
        }
    } else {
        $context = stream_context_create(['http' => [
            'timeout' => 10,
            'header' => "Accept: application/json\r\nUser-Agent: Agriculture Equipment Rental System/1.0\r\n"
        ]]);
        $body = @file_get_contents($url, false, $context);
    }
    if (!is_string($body) || $body === '') return [];
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

// This is a transparent rule-based outlook, not a trained machine-learning forecast.
$weatherOutlook = [
    'available' => false,
    'location' => $lenderLocation,
    'days' => [],
    'rain_probability' => null,
    'precipitation' => null,
    'message' => 'Weather forecast is not available right now. Check your saved lender location and internet connection.'
];

if ($lenderLocation !== '') {
    $place = null;
    foreach ($lenderLocationCandidates as $locationCandidate) {
        $geoUrl = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
            'name' => $locationCandidate,
            'count' => 1,
            'language' => 'en',
            'format' => 'json'
        ]);
        $geo = lender_ai_fetch_json($geoUrl);
        $candidatePlace = $geo['results'][0] ?? null;
        if (is_array($candidatePlace) && isset($candidatePlace['latitude'], $candidatePlace['longitude'])) {
            $place = $candidatePlace;
            break;
        }
    }
    if (is_array($place) && isset($place['latitude'], $place['longitude'])) {
        $forecastUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => (float)$place['latitude'],
            'longitude' => (float)$place['longitude'],
            'daily' => 'weather_code,precipitation_probability_max,precipitation_sum,temperature_2m_max,temperature_2m_min',
            'forecast_days' => 3,
            'timezone' => 'auto'
        ]);
        $forecastData = lender_ai_fetch_json($forecastUrl);
        $daily = $forecastData['daily'] ?? [];
        if (!empty($daily['time']) && count($daily['time']) >= 2) {
            $forecastCount = min(2, count($daily['time']));
            $rainValues = [];
            $precipValues = [];
            for ($i = 0; $i < $forecastCount; $i++) {
                $rain = isset($daily['precipitation_probability_max'][$i]) ? (int)$daily['precipitation_probability_max'][$i] : null;
                $precip = isset($daily['precipitation_sum'][$i]) ? (float)$daily['precipitation_sum'][$i] : null;
                if ($rain !== null) $rainValues[] = $rain;
                if ($precip !== null) $precipValues[] = $precip;
                $weatherOutlook['days'][] = [
                    'date' => (string)($daily['time'][$i] ?? ''),
                    'weather_code' => (int)($daily['weather_code'][$i] ?? 0),
                    'rain_probability' => $rain,
                    'precipitation' => $precip,
                    'temperature_max' => isset($daily['temperature_2m_max'][$i]) ? (float)$daily['temperature_2m_max'][$i] : null,
                    'temperature_min' => isset($daily['temperature_2m_min'][$i]) ? (float)$daily['temperature_2m_min'][$i] : null
                ];
            }
            $weatherOutlook['available'] = true;
            $weatherOutlook['location'] = trim(($place['name'] ?? $lenderLocation) . ', ' . ($place['admin1'] ?? '') . ', ' . ($place['country'] ?? ''), ' ,');
            $weatherOutlook['rain_probability'] = $rainValues ? (int)round(array_sum($rainValues) / count($rainValues)) : null;
            $weatherOutlook['precipitation'] = $precipValues ? round(array_sum($precipValues), 1) : null;
            $weatherOutlook['message'] = '';
        } else {
            $weatherOutlook['message'] = 'The weather service did not return a usable forecast for your saved location.';
        }
    } else {
        $weatherOutlook['message'] = 'We could not find your saved lender location in the weather service. Update your city or district in your profile and try again.';
    }
} else {
    $weatherOutlook['message'] = 'Your profile does not contain a city, district, state, or address. Add your location to enable weather-based insights.';
}

function lender_ai_weather_advice(string $category, ?int $rainProbability, ?float $precipitation): array {
    if ($rainProbability === null || $precipitation === null) {
        return ['label' => 'Weather data unavailable', 'reason' => 'No reliable forecast values were returned.'];
    }
    $categoryKey = strtolower(trim($category));
    $wet = ($rainProbability >= 60 || $precipitation >= 8.0);
    $veryWet = ($rainProbability >= 75 || $precipitation >= 15.0);
    $moderateRain = ($rainProbability >= 20 && $rainProbability <= 60) || ($precipitation >= 1.0 && $precipitation < 8.0);

    if (strpos($categoryKey, 'spray') !== false) {
        if ($rainProbability >= 50 || $precipitation >= 5.0) return ['label' => 'Likely lower / reschedule', 'reason' => 'Rain may reduce suitable spraying windows; renters may prefer a drier day.'];
        return ['label' => 'Weather looks more suitable', 'reason' => 'Lower forecast rainfall may provide a better spraying window.'];
    }
    if (strpos($categoryKey, 'harvest') !== false) {
        if ($wet) return ['label' => 'Likely lower / weather risk', 'reason' => 'Rain can make harvesting and field access more difficult.'];
        return ['label' => 'Weather looks more suitable', 'reason' => 'The forecast is relatively dry, which can be more suitable for harvesting.'];
    }
    if (strpos($categoryKey, 'irrigation') !== false) {
        if ($wet) return ['label' => 'Potentially lower need', 'reason' => 'Forecast rain may reduce immediate irrigation need; local soil and crop conditions still matter.'];
        return ['label' => 'Potentially higher need', 'reason' => 'A relatively dry forecast may increase irrigation needs depending on crop and soil conditions.'];
    }
    if (strpos($categoryKey, 'seeding') !== false) {
        if ($veryWet) return ['label' => 'Weather risk', 'reason' => 'Heavy rain may delay field access or seeding operations.'];
        if ($moderateRain) return ['label' => 'Potentially favourable', 'reason' => 'Moderate rain may support some seeding work, depending on soil and crop.'];
        return ['label' => 'Mixed / monitor forecast', 'reason' => 'Very dry conditions may affect soil moisture; check local field conditions.'];
    }
    if (strpos($categoryKey, 'tillage') !== false) {
        if ($veryWet) return ['label' => 'Weather risk', 'reason' => 'Heavy rain can make soil too wet for some tillage work.'];
        if ($moderateRain) return ['label' => 'Potentially favourable', 'reason' => 'Moderate moisture may suit some soil preparation, depending on field conditions.'];
        return ['label' => 'Mixed / monitor forecast', 'reason' => 'Very dry or hard soil can affect tillage suitability.'];
    }
    if (strpos($categoryKey, 'tractor') !== false) {
        if ($wet) return ['label' => 'Possible field-access risk', 'reason' => 'Heavy rain may make some field operations harder to schedule.'];
        return ['label' => 'Weather looks more suitable', 'reason' => 'The forecast is relatively dry for many general tractor tasks.'];
    }
    if ($wet) return ['label' => 'Weather may affect use', 'reason' => 'Rain may affect field access and the timing of some equipment tasks.'];
    return ['label' => 'No strong weather signal', 'reason' => 'Forecast weather alone does not strongly indicate a change for this category.'];
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

// Load actual review signals for each piece of equipment.
$reviewSignals = [];
$reviewStmt = $conn->prepare("
    SELECT equipment_id, COUNT(*) AS review_count,
           AVG(rating) AS average_rating,
           SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) AS low_rating_count,
           SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) AS positive_rating_count
    FROM reviews
    WHERE lender_id = ?
    GROUP BY equipment_id
");
if ($reviewStmt) {
    $reviewStmt->bind_param('i', $lender_id);
    if ($reviewStmt->execute()) {
        $reviewResult = $reviewStmt->get_result();
        while ($reviewRow = $reviewResult->fetch_assoc()) {
            $reviewSignals[(int)$reviewRow['equipment_id']] = $reviewRow;
        }
    }
    $reviewStmt->close();
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

    // Count bookings and estimate actual rental-use exposure for this equipment.
    // Completed/returned bookings use their recorded rental days. Delivered/overdue
    // bookings count only elapsed days so future planned days do not inflate wear.
    $equipmentBookingCount = 0;
    $equipmentBookedDays = 0;
    $equipmentRentalDays = 0;
    $equipmentCompletedRentals = 0;

    foreach ($bookings as $booking) {
        if ((int)$booking['equipment_id'] !== (int)$item['equipment_id']) {
            continue;
        }

        $status = strtolower(trim($booking['status'] ?? ''));

        if (in_array($status, ['rejected', 'cancelled', 'pending'], true)) {
            continue;
        }

        $equipmentBookingCount++;
        $days = max(1, (int)$booking['total_days']);
        $quantity = max(1, (int)($booking['quantity'] ?? 1));
        $equipmentBookedDays += $days;

        if (in_array($status, ['completed', 'returned'], true)) {
            $equipmentCompletedRentals++;
            $equipmentRentalDays += $days * $quantity;
        } elseif (in_array($status, ['delivered', 'overdue'], true)) {
            $startTimestamp = strtotime((string)$booking['start_date']);
            $elapsedDays = $startTimestamp ? max(1, (int)floor((time() - $startTimestamp) / 86400) + 1) : 1;
            $equipmentRentalDays += min($days, $elapsedDays) * $quantity;
        }
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

    // Usage-based maintenance guidance. These are conservative project rules,
    // not a substitute for the equipment's hour-meter or manufacturer schedule.
    $condition = strtolower(trim($item['equipment_condition'] ?? ''));
    $statusText = strtolower(trim($item['status'] ?? ''));

    if ($statusText === 'under maintenance') {
        $maintenance = 'Already marked under maintenance. Complete the service and record the check before renting it again.';
    } elseif ($equipmentRentalDays >= 30 || $equipmentCompletedRentals >= 8) {
        $maintenance = 'High usage: ' . $equipmentRentalDays . ' estimated rental-days across ' . $equipmentCompletedRentals . ' completed rentals. Arrange a thorough inspection before the next rental; check oil/fluids, tyres, brakes, belts and visible wear as applicable.';
    } elseif ($equipmentRentalDays >= 15 || $equipmentCompletedRentals >= 4) {
        $maintenance = 'Moderate usage: ' . $equipmentRentalDays . ' estimated rental-days across ' . $equipmentCompletedRentals . ' completed rentals. Plan a maintenance inspection soon and check key wear parts before the next rental.';
    } elseif ($equipmentRentalDays > 0 || $equipmentCompletedRentals > 0) {
        $maintenance = 'Some usage: ' . $equipmentRentalDays . ' estimated rental-days across ' . $equipmentCompletedRentals . ' completed rentals. Do a quick condition and safety check after each return, and follow the manufacturer service schedule.';
    } elseif ($condition === 'used') {
        $maintenance = 'No completed rental-use history recorded yet. Inspect its existing condition and follow the manufacturer service schedule before renting.';
    } else {
        $maintenance = 'No completed rental-use history recorded yet. Continue routine checks and follow the manufacturer service schedule.';
    }

    $weatherAdvice = lender_ai_weather_advice(
        (string)$category,
        $weatherOutlook['rain_probability'],
        $weatherOutlook['precipitation']
    );

    // Combine condition, review ratings, booking demand, weather outlook and
    // category price comparison into an explainable pricing recommendation.
    $review = $reviewSignals[(int)$item['equipment_id']] ?? null;
    $reviewCount = $review ? (int)$review['review_count'] : 0;
    $averageRating = ($reviewCount > 0 && $review['average_rating'] !== null)
        ? (float)$review['average_rating'] : null;
    $lowRatingCount = $review ? (int)$review['low_rating_count'] : 0;
    $conditionLabel = ucfirst(strtolower(trim((string)($item['equipment_condition'] ?? 'Unknown'))));
    $score = 0;
    $reasons = [];

    if ($conditionLabel === 'New') {
        $score++;
        $reasons[] = 'The equipment is marked New, which can support a competitive premium if it is in good working order.';
    } elseif ($conditionLabel === 'Used') {
        $score--;
        $reasons[] = 'The equipment is marked Used, so inspect wear and maintenance needs before raising the price.';
    } elseif ($conditionLabel === 'Good') {
        $reasons[] = 'The equipment is marked Good; maintain its condition with regular inspections.';
    }

    if ($reviewCount >= 3 && $averageRating !== null) {
        if ($averageRating >= 4.5) {
            $score++;
            $reasons[] = 'Customer feedback is strong (' . number_format($averageRating, 1) . '/5 across ' . $reviewCount . ' reviews).';
        } elseif ($averageRating < 3.0 || $lowRatingCount >= 2) {
            $score -= 2;
            $reasons[] = 'Reviews show customer concerns; address the issues before considering a price increase.';
        } elseif ($averageRating < 3.8) {
            $score--;
            $reasons[] = 'The average review rating is moderate (' . number_format($averageRating, 1) . '/5); improve the rental experience before raising the price.';
        } else {
            $reasons[] = 'Customer ratings are generally positive but do not strongly justify a price increase on their own.';
        }
    } elseif ($reviewCount > 0) {
        $reasons[] = 'Only ' . $reviewCount . ' review(s) are available, so review evidence is limited.';
    } else {
        $reasons[] = 'No customer reviews are available yet; pricing confidence is limited.';
    }

    if ($equipmentBookingCount >= 5) {
        $score++;
        $reasons[] = 'Booking history indicates relatively strong demand (' . $equipmentBookingCount . ' recorded bookings).';
    } elseif ($equipmentBookingCount <= 1) {
        $score--;
        $reasons[] = 'Recorded booking history is low (' . $equipmentBookingCount . ' booking(s)); avoid assuming customers will accept a higher price.';
    } else {
        $reasons[] = 'Booking history indicates some demand (' . $equipmentBookingCount . ' recorded bookings).';
    }

    $weatherLabelLower = strtolower($weatherAdvice['label']);
    if (strpos($weatherLabelLower, 'higher need') !== false || strpos($weatherLabelLower, 'favourable') !== false) {
        $score++;
        $reasons[] = 'The next-two-day weather outlook may support demand for this equipment category.';
    } elseif (
        strpos($weatherLabelLower, 'lower') !== false ||
        strpos($weatherLabelLower, 'risk') !== false ||
        strpos($weatherLabelLower, 'affect use') !== false ||
        strpos($weatherLabelLower, 'field-access') !== false
    ) {
        $score--;
        $reasons[] = 'Weather may reduce or delay use of this equipment category in the next two days.';
    } else {
        $reasons[] = 'The current weather forecast does not provide a strong demand signal for this category.';
    }

    $pricePosition = 'near category average';
    if ($currentPrice < $averagePrice * 0.90) {
        $pricePosition = 'below category average';
        $score++;
        $reasons[] = 'The current price is more than 10% below the category average.';
    } elseif ($currentPrice > $averagePrice * 1.10) {
        $pricePosition = 'above category average';
        $score--;
        $reasons[] = 'The current price is more than 10% above the category average; check local competitors before increasing it.';
    } else {
        $reasons[] = 'The current price is close to the category average.';
    }

    $statusLower = strtolower(trim((string)$item['status']));
    if ($statusLower === 'under maintenance' || $statusLower === 'inactive') {
        $action = 'Fix / activate before repricing';
        $actionDetail = 'This equipment is not currently available for normal rental. Resolve its status first; do not increase the price now.';
        $actionPrice = $currentPrice;
    } elseif ($score >= 3 && $conditionLabel !== 'Used' && $currentPrice < $averagePrice * 1.10) {
        $action = 'Consider a small increase';
        $actionPrice = min($currentPrice * 1.05, $averagePrice * 1.10);
        $actionPrice = round($actionPrice / 10) * 10;
        $actionDetail = 'Signals are reasonably supportive. Test an increase of about 5%, then monitor new booking requests and reviews.';
    } elseif ($score <= -2) {
        $action = 'Maintain or consider a small reduction';
        $actionPrice = $currentPrice > $averagePrice ? max($averagePrice, $currentPrice * 0.95) : $currentPrice;
        $actionPrice = round($actionPrice / 10) * 10;
        $actionDetail = 'Demand, condition, reviews, weather or price comparison raise caution. Improve the underlying issue first; if the price is above category average, test a reduction of about 5%.';
    } else {
        $action = 'Maintain current price';
        $actionPrice = $currentPrice;
        $actionDetail = 'The available signals do not strongly support a price change. Keep the current rate and collect more booking and review data.';
    }

    $equipmentInsights[] = [
        'equipment_id' => (int)$item['equipment_id'],
        'title' => $item['title'],
        'category' => $category,
        'price' => $currentPrice,
        'average' => $averagePrice,
        'suggested' => $suggestedPrice,
        'bookings' => $equipmentBookingCount,
        'rental_days' => $equipmentRentalDays,
        'completed_rentals' => $equipmentCompletedRentals,
        'utilization' => $utilization,
        'demand' => $demand,
        'weather_demand' => $weatherAdvice['label'],
        'weather_reason' => $weatherAdvice['reason'],
        'status' => $item['status'],
        'condition' => $conditionLabel,
        'review_count' => $reviewCount,
        'average_rating' => $averageRating,
        'low_rating_count' => $lowRatingCount,
        'price_position' => $pricePosition,
        'price_action' => $action,
        'action_price' => $actionPrice,
        'action_detail' => $actionDetail,
        'pricing_reasons' => $reasons,
        'maintenance' => $maintenance
    ];
}

// Load this lender's price history created by the database trigger.
// The trigger records a row whenever price_per_day changes.
$priceHistory = [];
$priceHistoryStmt = $conn->prepare("\n    SELECT h.history_id, h.equipment_id, h.old_price, h.new_price,\n           h.change_type, h.changed_at, e.title\n    FROM equipment_price_history h\n    INNER JOIN equipment e ON e.equipment_id = h.equipment_id\n    WHERE h.lender_id = ?\n    ORDER BY h.changed_at ASC, h.history_id ASC\n");
if ($priceHistoryStmt) {
    $priceHistoryStmt->bind_param('i', $lender_id);
    if ($priceHistoryStmt->execute()) {
        $priceHistoryResult = $priceHistoryStmt->get_result();
        while ($historyRow = $priceHistoryResult->fetch_assoc()) {
            $priceHistory[] = $historyRow;
        }
    }
    $priceHistoryStmt->close();
}

// Prepare chart data: each equipment gets its own series.
$priceChartLabels = [];
$priceChartDatasets = [];
$priceHistoryEquipment = [];
foreach ($priceHistory as $historyRow) {
    $priceChartLabels[] = date('d M y H:i', strtotime($historyRow['changed_at']));
    $historyEquipmentId = (int)$historyRow['equipment_id'];
    $priceHistoryEquipment[$historyEquipmentId] = $historyRow['title'];
}
$priceChartLabels = array_values($priceChartLabels);
$priceChartPalette = [
    ['border' => '#1f8b57', 'background' => 'rgba(31,139,87,0.12)'],
    ['border' => '#4f69d2', 'background' => 'rgba(79,105,210,0.12)'],
    ['border' => '#d97706', 'background' => 'rgba(217,119,6,0.12)'],
    ['border' => '#9333ea', 'background' => 'rgba(147,51,234,0.12)'],
    ['border' => '#0891b2', 'background' => 'rgba(8,145,178,0.12)'],
    ['border' => '#dc2626', 'background' => 'rgba(220,38,38,0.12)']
];
$paletteIndex = 0;
foreach ($priceHistoryEquipment as $historyEquipmentId => $historyEquipmentTitle) {
    $series = array_fill(0, count($priceHistory), null);
    foreach ($priceHistory as $pointIndex => $historyRow) {
        if ((int)$historyRow['equipment_id'] === (int)$historyEquipmentId) {
            $series[$pointIndex] = (float)$historyRow['new_price'];
        }
    }
    $palette = $priceChartPalette[$paletteIndex % count($priceChartPalette)];
    $priceChartDatasets[] = [
        'label' => $historyEquipmentTitle,
        'data' => $series,
        'borderColor' => $palette['border'],
        'backgroundColor' => $palette['background'],
        'tension' => 0.25,
        'spanGaps' => true,
        'pointRadius' => 4
    ];
    $paletteIndex++;
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
