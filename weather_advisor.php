<?php
session_start();

require_once 'includes/lang.php';
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'renter') {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$user_stmt = mysqli_prepare(
    $conn,
    "SELECT full_name, address, city, district, state
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

if (!$user_stmt) {
    die('Unable to prepare user query.');
}

mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_info = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

if (!$user_info) {
    header('Location: login.php');
    exit();
}

$registered_parts = [];
foreach (['city', 'district', 'state'] as $location_field) {
    $value = trim((string) ($user_info[$location_field] ?? ''));
    if ($value !== '' && !in_array(strtolower($value), array_map('strtolower', $registered_parts), true)) {
        $registered_parts[] = $value;
    }
}
$registered_location = implode(', ', $registered_parts);
if ($registered_location === '') {
    $registered_location = trim((string) ($user_info['address'] ?? ''));
}

function weather_api_get_json(string $url): array
{
    $response = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'Agriculture Equipment Rental System/1.0'
        ]);
        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false || $http_code < 200 || $http_code >= 300) {
            $response = false;
        }
    }

    if ($response === false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => "Accept: application/json\r\nUser-Agent: Agriculture Equipment Rental System/1.0\r\n"
            ]
        ]);
        $response = @file_get_contents($url, false, $context);
    }

    if ($response === false || trim($response) === '') {
        throw new RuntimeException(__('weather_service_error'));
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException(__('invalid_weather_response'));
    }
    if (!empty($data['error'])) {
        throw new RuntimeException($data['reason'] ?? __('weather_service_error'));
    }
    return $data;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'get_weather') {
    $location_mode = ($_POST['location_mode'] ?? 'registered') === 'other' ? 'other' : 'registered';
    $location_query = $location_mode === 'other'
        ? trim((string) ($_POST['location'] ?? ''))
        : $registered_location;

    if ($location_query === '') {
        json_response(['success' => false, 'message' => __('no_location_available')], 422);
    }
    if (mb_strlen($location_query) > 200) {
        json_response(['success' => false, 'message' => __('shorter_location')], 422);
    }

    try {
        $geocode_url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
            'name' => $location_query,
            'count' => 1,
            'language' => 'en',
            'format' => 'json'
        ]);
        $geo_data = weather_api_get_json($geocode_url);

        if (empty($geo_data['results'][0])) {
            json_response(['success' => false, 'message' => __('location_not_found')], 404);
        }

        $place = $geo_data['results'][0];
        $latitude = (float) ($place['latitude'] ?? 0);
        $longitude = (float) ($place['longitude'] ?? 0);

        if ($latitude === 0.0 && $longitude === 0.0) {
            throw new RuntimeException(__('invalid_weather_response'));
        }

        $weather_url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'daily' => implode(',', [
                'weather_code',
                'temperature_2m_max',
                'temperature_2m_min',
                'precipitation_probability_max',
                'precipitation_sum',
                'wind_speed_10m_max',
                'relative_humidity_2m_max'
            ]),
            'forecast_days' => 5,
            'timezone' => 'auto'
        ]);

        $weather_data = weather_api_get_json($weather_url);
        if (empty($weather_data['daily']['time'])) {
            throw new RuntimeException(__('no_forecast_data'));
        }

        $daily = $weather_data['daily'];
        $forecast = [];
        $count = count($daily['time']);

        for ($i = 0; $i < $count; $i++) {
            $forecast[] = [
                'date' => $daily['time'][$i] ?? '',
                'weather_code' => (int) ($daily['weather_code'][$i] ?? 0),
                'temperature_max' => isset($daily['temperature_2m_max'][$i]) ? (float) $daily['temperature_2m_max'][$i] : null,
                'temperature_min' => isset($daily['temperature_2m_min'][$i]) ? (float) $daily['temperature_2m_min'][$i] : null,
                'rain_probability' => isset($daily['precipitation_probability_max'][$i]) ? (int) $daily['precipitation_probability_max'][$i] : null,
                'precipitation' => isset($daily['precipitation_sum'][$i]) ? (float) $daily['precipitation_sum'][$i] : null,
                'wind_speed' => isset($daily['wind_speed_10m_max'][$i]) ? (float) $daily['wind_speed_10m_max'][$i] : null,
                'humidity' => isset($daily['relative_humidity_2m_max'][$i]) ? (float) $daily['relative_humidity_2m_max'][$i] : null
            ];
        }

        json_response([
            'success' => true,
            'location' => [
                'name' => $place['name'] ?? $location_query,
                'admin1' => $place['admin1'] ?? '',
                'country' => $place['country'] ?? '',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => $weather_data['timezone'] ?? ($place['timezone'] ?? '')
            ],
            'forecast' => $forecast,
            'source' => 'Open-Meteo'
        ]);
    } catch (Throwable $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

$weather_text_map = [
    0 => __('clear_sky'), 1 => __('mainly_clear'), 2 => __('partly_cloudy'), 3 => __('overcast'),
    45 => __('fog'), 48 => __('rime_fog'), 51 => __('light_drizzle'), 53 => __('drizzle'),
    55 => __('heavy_drizzle'), 56 => __('freezing_drizzle'), 57 => __('heavy_freezing_drizzle'),
    61 => __('light_rain'), 63 => __('rain'), 65 => __('heavy_rain'), 66 => __('freezing_rain'),
    67 => __('heavy_freezing_rain'), 71 => __('light_snow'), 73 => __('snow'), 75 => __('heavy_snow'),
    77 => __('snow_grains'), 80 => __('light_showers'), 81 => __('showers'), 82 => __('heavy_showers'),
    85 => __('snow_showers'), 86 => __('heavy_snow_showers'), 95 => __('thunderstorm'),
    96 => __('thunderstorm_hail'), 99 => __('thunderstorm_heavy_hail')
];

$translations_for_js = [
    'weather' => $weather_text_map,
    'please_enter_location' => __('please_enter_location'),
    'select_equipment' => __('select_equipment'),
    'getting_forecast' => __('getting_forecast'),
    'analyze_weather_rental' => __('analyze_weather_rental'),
    'weather_loaded' => __('weather_loaded'),
    'rain_chance' => __('rain_chance'),
    'rainfall' => __('rainfall'),
    'local_time' => __('local_time'),
    'temperature_trend' => __('temperature_trend'),
    'rain_probability' => __('rain_probability'),
    'equipment_suitability' => __('equipment_suitability'),
    'ai_recommendation' => __('ai_recommendation'),
    'good_time_to_rent' => __('good_time_to_rent'),
    'use_with_caution' => __('use_with_caution'),
    'avoid_rental' => __('avoid_rental'),
    'best_days_to_rent' => __('best_days_to_rent'),
    'avoid' => __('avoid'),
    'reason' => __('reason'),
    'expected_rainfall' => __('expected_rainfall'),
    'ideal_temperature' => __('ideal_temperature'),
    'wind_speed' => __('wind_speed'),
    'today' => __('today'),
    'tomorrow' => __('tomorrow'),
    'day' => __('day'),
    'highly_suitable' => __('highly_suitable'),
    'suitable' => __('suitable'),
    'use_caution' => __('use_caution'),
    'heavy_rain_expected' => __('heavy_rain_expected'),
    'low_rain_good_temp' => __('low_rain_good_temp'),
    'rain_unfavorable' => __('rain_unfavorable'),
    'dry_weather' => __('dry_weather'),
    'spray_rain_warning' => __('spray_rain_warning'),
    'irrigation_rain_warning' => __('irrigation_rain_warning'),
    'wind_warning' => __('wind_warning'),
    'harvesting_rain_warning' => __('harvesting_rain_warning'),
    'seeding_weather_reason' => __('seeding_weather_reason'),
    'tillage_weather_reason' => __('tillage_weather_reason')
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang ?? 'en'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(__('page_title')); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
:root{
    --navy:#0b3d78;
    --navy2:#123f78;
    --blue:#2188e8;
    --blue2:#58b8ff;
    --green:#10a878;
    --green2:#07966d;
    --ink:#163d68;
    --muted:#6c849b;
    --glass:rgba(255,255,255,.58);
    --glass-strong:rgba(255,255,255,.72);
    --glass-soft:rgba(245,251,255,.42);
    --border:rgba(255,255,255,.88);
    --line:rgba(157,204,235,.42);
    --shadow:0 18px 45px rgba(28,94,145,.13);
    --shadow-soft:0 10px 28px rgba(36,112,166,.10);
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    margin:0;
    min-height:100vh;
    overflow-x:hidden;
    font-family:"Segoe UI",Tahoma,Arial,sans-serif;
    color:var(--ink);
    background:
        radial-gradient(circle at 82% 2%,rgba(255,255,255,.96) 0 7%,transparent 25%),
        radial-gradient(circle at 68% 16%,rgba(132,213,255,.28),transparent 25%),
        radial-gradient(circle at 40% 100%,rgba(93,211,164,.18),transparent 28%),
        linear-gradient(135deg,#e7f8ff 0%,#f8fcff 45%,#e9f9f2 100%);
}
body:before{
    content:"";
    position:fixed;
    inset:0;
    pointer-events:none;
    z-index:-1;
    background:
        radial-gradient(ellipse at 75% 12%,rgba(255,255,255,.75) 0 6%,transparent 7%),
        radial-gradient(ellipse at 88% 18%,rgba(255,255,255,.65) 0 5%,transparent 6%),
        radial-gradient(circle at 12% 92%,rgba(43,166,117,.10),transparent 22%),
        linear-gradient(180deg,rgba(255,255,255,.12),rgba(255,255,255,0));
}
body:after{
    content:"";
    position:fixed;
    width:430px;
    height:430px;
    right:-180px;
    top:150px;
    border-radius:50%;
    background:rgba(111,199,255,.13);
    filter:blur(55px);
    pointer-events:none;
    z-index:-1;
}
.main-wrapper{
    margin-left:250px;
    max-width:calc(100% - 250px);
    padding:20px 24px 45px;
    position:relative;
}
.main-wrapper:before{
    content:"";
    position:absolute;
    left:4%;
    right:4%;
    top:0;
    height:145px;
    border-radius:0 0 55% 55%;
    background:
        radial-gradient(ellipse at 10% 35%,rgba(255,255,255,.68) 0 7%,transparent 8%),
        radial-gradient(ellipse at 32% 20%,rgba(255,255,255,.55) 0 9%,transparent 10%),
        radial-gradient(ellipse at 65% 35%,rgba(255,255,255,.62) 0 8%,transparent 9%),
        linear-gradient(180deg,rgba(112,204,255,.13),transparent);
    pointer-events:none;
    z-index:-1;
}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:18px;
    margin-bottom:15px;
    padding:4px 3px;
}
.brand-title{display:flex;gap:15px;align-items:center}
.sun-icon{
    width:72px;height:72px;border-radius:25px;
    display:grid;place-items:center;
    font-size:39px;
    background:linear-gradient(145deg,rgba(255,255,255,.82),rgba(229,247,255,.48));
    border:1px solid rgba(255,255,255,.95);
    box-shadow:0 12px 35px rgba(53,133,189,.16),inset 0 1px 0 rgba(255,255,255,.9);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
}
.brand-title h1{
    margin:0;
    color:#0b3d78;
    font-size:clamp(1.65rem,2.65vw,2.55rem);
    line-height:1.05;
    font-weight:900;
    letter-spacing:-.7px;
}
.brand-title p{
    margin:6px 0 0;
    color:#4c7195;
    font-size:.96rem;
    font-weight:500;
}
.date-pill{
    min-width:170px;
    padding:12px 17px;
    border-radius:17px;
    background:linear-gradient(135deg,rgba(255,255,255,.78),rgba(238,249,255,.53));
    border:1px solid rgba(255,255,255,.94);
    box-shadow:var(--shadow-soft);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    color:#184777;
    font-weight:800;
}
.glass{
    background:linear-gradient(135deg,rgba(255,255,255,.72),rgba(255,255,255,.43));
    border:1px solid rgba(255,255,255,.93);
    backdrop-filter:blur(24px) saturate(125%);
    -webkit-backdrop-filter:blur(24px) saturate(125%);
    box-shadow:
        0 20px 48px rgba(28,91,140,.11),
        inset 0 1px 0 rgba(255,255,255,.95),
        inset 0 -1px 0 rgba(157,204,235,.16);
    border-radius:22px;
}
.control-card{
    padding:17px;
    margin-bottom:15px;
    position:relative;
    overflow:hidden;
}
.control-card:after{
    content:"";
    position:absolute;
    width:220px;height:220px;
    right:-100px;top:-140px;
    border-radius:50%;
    background:rgba(98,196,255,.13);
    filter:blur(10px);
    pointer-events:none;
}
.control-grid{
    display:grid;
    grid-template-columns:minmax(0,1.12fr) minmax(230px,.85fr) auto;
    gap:14px;
    align-items:center;
}
.control-block{min-width:0}
.control-label{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
    color:#123f73;
    font-size:.91rem;
    font-weight:900;
}
.control-label i{color:#208be7}
.location-toggle{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:8px;
}
.radio-label{
    cursor:pointer;
    color:#214d79;
    font-size:.82rem;
    font-weight:800;
}
.radio-label input{
    accent-color:#2188e8;
    margin-right:5px;
}
.location-input{
    width:100%;
    height:44px;
    outline:none;
    padding:0 13px;
    border-radius:12px;
    border:1px solid rgba(164,207,234,.58);
    background:rgba(255,255,255,.55);
    color:#234e76;
    box-shadow:inset 0 1px 3px rgba(47,116,163,.05);
}
.location-input:focus{
    border-color:#55aef0;
    box-shadow:0 0 0 4px rgba(33,136,232,.08),inset 0 1px 3px rgba(47,116,163,.05);
}
.saved-chip{
    margin-top:7px;
    padding:8px 10px;
    border-radius:11px;
    background:linear-gradient(135deg,rgba(222,250,236,.86),rgba(239,255,248,.55));
    border:1px solid rgba(152,220,185,.65);
    color:#267253;
    font-size:.76rem;
    font-weight:600;
}
.equipment-select{
    width:100%;
    height:46px;
    border-radius:12px;
    border:1px solid rgba(164,207,234,.62);
    background:rgba(255,255,255,.63);
    color:#143f70;
    font-weight:800;
    box-shadow:inset 0 1px 3px rgba(47,116,163,.05);
}
.analyze-btn{
    height:46px;
    border:0;
    border-radius:12px;
    padding:0 19px;
    color:#fff;
    font-weight:900;
    white-space:nowrap;
    background:linear-gradient(135deg,#078e69,#12b37d);
    box-shadow:0 10px 22px rgba(11,155,111,.20),inset 0 1px 0 rgba(255,255,255,.18);
    transition:.2s ease;
}
.analyze-btn:hover{transform:translateY(-2px);box-shadow:0 14px 26px rgba(11,155,111,.24);color:#fff}
.analyze-btn:disabled{opacity:.68;transform:none}
.alert-box{
    display:none;
    margin-top:10px;
    padding:10px 13px;
    border-radius:12px;
    font-size:.82rem;
    font-weight:600;
}
.alert-box.info{display:block;background:rgba(227,243,255,.78);border:1px solid rgba(155,207,242,.7);color:#155e9c}
.alert-box.error{display:block;background:rgba(255,237,240,.82);border:1px solid #fecdd3;color:#9f1239}
.dashboard-grid{
    display:grid;
    grid-template-columns:minmax(0,1fr) 318px;
    gap:14px;
}
.left-stack,.right-stack{display:flex;flex-direction:column;gap:14px}
.location-result{
    padding:14px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    background:linear-gradient(135deg,rgba(226,250,238,.70),rgba(237,249,255,.52));
}
.loc-title{margin:2px 0;font-size:1.12rem;font-weight:900;color:#173f6e}
.coords{font-size:.72rem;color:#6d8499}
.live-badge{
    padding:6px 10px;
    border-radius:999px;
    background:rgba(225,249,237,.78);
    border:1px solid rgba(151,221,184,.65);
    color:#11835a;
    font-size:.69rem;
    font-weight:900;
    white-space:nowrap;
}
.section-card{padding:15px}
.section-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:11px;
}
.section-head h2{
    margin:0;
    color:#123f73;
    font-size:.96rem;
    font-weight:900;
}
.section-head h2 i{
    color:#188ae7;
    margin-right:7px;
}
.forecast-grid{
    display:grid;
    grid-template-columns:repeat(5,minmax(0,1fr));
    gap:9px;
}
.forecast-card{
    min-height:164px;
    padding:12px 10px;
    text-align:left;
    border-radius:16px;
    background:linear-gradient(145deg,rgba(255,255,255,.78),rgba(241,249,255,.45));
    border:1px solid rgba(191,220,239,.64);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9),0 8px 20px rgba(50,117,164,.05);
    transition:.2s ease;
}
.forecast-card:hover{
    transform:translateY(-3px);
    box-shadow:0 13px 25px rgba(42,104,155,.11),inset 0 1px 0 rgba(255,255,255,.9);
}
.forecast-date{font-size:.72rem;font-weight:900;color:#204f80}
.weather-icon{text-align:center;font-size:2rem;margin:7px 0 2px}
.weather-condition{text-align:center;min-height:25px;font-size:.71rem;font-weight:700;color:#55738d}
.temp{text-align:center;font-size:1rem;font-weight:900;color:#173f70}
.rain-pill{
    display:block;
    width:max-content;
    margin:6px auto 0;
    padding:4px 8px;
    border-radius:999px;
    font-size:.67rem;
    font-weight:900;
}
.rain-low{background:rgba(220,248,233,.88);color:#168253}
.rain-mid{background:rgba(255,240,207,.9);color:#b86a00}
.rain-high{background:rgba(255,222,229,.9);color:#c12c4a}
.mini-data{text-align:center;font-size:.66rem;color:#7a8da0;margin-top:5px}
.chart-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.chart-card{
    height:225px;
    padding:13px 14px;
    border-radius:18px;
    background:linear-gradient(145deg,rgba(255,255,255,.57),rgba(241,249,255,.28));
    border:1px solid rgba(255,255,255,.76);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.78);
}
.chart-wrap{height:170px}
.chart-wrap canvas{width:100%!important;height:100%!important}
.analysis-grid{
    display:grid;
    grid-template-columns:1.08fr .92fr;
    gap:12px;
}
.suitability-card,.recommend-card{
    padding:15px;
    border-radius:18px;
    background:linear-gradient(145deg,rgba(255,255,255,.60),rgba(241,249,255,.29));
    border:1px solid rgba(255,255,255,.76);
}
.score-layout{display:flex;align-items:center;gap:18px}
.score-ring{
    width:142px;height:142px;
    flex:none;
    border-radius:50%;
    display:grid;place-items:center;
    position:relative;
    background:conic-gradient(#12aa72 0deg,#12aa72 var(--score),rgba(216,232,241,.76) var(--score),rgba(216,232,241,.76) 360deg);
    box-shadow:0 8px 22px rgba(20,154,108,.10);
}
.score-ring:before{
    content:"";
    position:absolute;
    inset:5px;
    border-radius:50%;
    border:1px solid rgba(255,255,255,.68);
}
.score-ring:after{
    content:"";
    position:absolute;
    width:106px;height:106px;
    border-radius:50%;
    background:rgba(255,255,255,.76);
    border:1px solid rgba(255,255,255,.9);
    box-shadow:inset 0 1px 7px rgba(41,113,155,.06);
}
.score-number{
    position:relative;
    z-index:2;
    text-align:center;
    font-size:1.72rem;
    font-weight:950;
    color:#124b80;
}
.score-number small{
    display:block;
    margin-top:1px;
    font-size:.59rem;
    font-weight:800;
    color:#72879a;
}
.best-list{font-size:.77rem;color:#3d667f;line-height:1.55}
.best-list strong{color:#153f70}
.best-item{display:flex;gap:7px;align-items:flex-start;margin-bottom:6px}
.best-item i.ok{color:#1ca76b}
.best-item i.warn{color:#f1a400}
.best-item i.bad{color:#e24758}
.reason-box{
    margin-top:11px;
    padding:9px 11px;
    border-radius:12px;
    background:linear-gradient(135deg,rgba(232,251,241,.82),rgba(238,249,255,.62));
    border:1px solid rgba(177,226,199,.7);
    color:#356579;
    font-size:.71rem;
}
.reason-box i{color:#20a86d;margin-right:5px}
.rec-status{
    display:flex;
    align-items:flex-start;
    gap:10px;
    padding:12px;
    border-radius:14px;
    background:linear-gradient(135deg,rgba(225,249,236,.83),rgba(240,253,247,.57));
    border:1px solid rgba(174,226,195,.68);
}
.status-icon{
    width:38px;height:38px;
    flex:none;
    border-radius:50%;
    display:grid;place-items:center;
    background:#20a96e;
    color:#fff;
    font-size:18px;
    box-shadow:0 7px 15px rgba(32,169,110,.18);
}
.status-title{font-size:.91rem;font-weight:950;color:#138051;margin-bottom:3px}
.status-text{font-size:.72rem;color:#44705e;line-height:1.45}
.metric-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:7px;
    margin-top:8px;
}
.metric{
    padding:8px 9px;
    border-radius:11px;
    background:rgba(238,248,255,.62);
    border:1px solid rgba(194,224,242,.62);
    color:#55748c;
    font-size:.65rem;
}
.metric strong{
    display:block;
    margin-top:2px;
    color:#174879;
    font-size:.72rem;
}
.available-btn{
    width:100%;
    height:40px;
    margin-top:8px;
    border:0;
    border-radius:11px;
    color:#fff;
    font-size:.76rem;
    font-weight:900;
    background:linear-gradient(135deg,#0b9a70,#14b47e);
    box-shadow:0 9px 18px rgba(16,157,111,.17);
    transition:.2s ease;
}
.available-btn:hover{color:#fff;transform:translateY(-2px)}
.insight-list{display:flex;flex-direction:column;gap:8px}
.insight-card{
    display:flex;
    align-items:center;
    gap:9px;
    padding:10px;
    border-radius:15px;
    border:1px solid rgba(255,255,255,.76);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.7);
}
.insight-card.green{background:linear-gradient(135deg,rgba(226,249,237,.78),rgba(246,255,250,.48))}
.insight-card.yellow{background:linear-gradient(135deg,rgba(255,245,221,.82),rgba(255,251,241,.49))}
.insight-card.red{background:linear-gradient(135deg,rgba(255,232,235,.82),rgba(255,246,247,.49))}
.insight-card.blue{background:linear-gradient(135deg,rgba(228,243,255,.82),rgba(245,251,255,.49))}
.insight-icon{
    width:38px;height:38px;
    flex:none;
    display:grid;place-items:center;
    border-radius:12px;
    font-size:19px;
    background:rgba(255,255,255,.66);
    border:1px solid rgba(255,255,255,.78);
}
.insight-title{font-size:.74rem;font-weight:950;color:#174a78}
.insight-desc{font-size:.63rem;color:#58768d;margin-top:2px;line-height:1.35}
.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.quick{
    padding:9px;
    border-radius:12px;
    background:rgba(255,255,255,.50);
    border:1px solid rgba(207,230,243,.64);
}
.quick i{color:#238ce8}
.quick small{display:block;color:#72869a;font-size:.62rem;margin-top:4px}
.quick strong{display:block;color:#164777;font-size:.82rem;margin-top:2px}
.source-note{font-size:.66rem;color:#708598;padding:9px 2px 0;line-height:1.35}
.hidden{display:none!important}

@media(max-width:1250px){
    .control-grid{grid-template-columns:1fr 1fr}
    .control-grid .analyze-wrap{grid-column:1/-1}
    .dashboard-grid{grid-template-columns:1fr}
    .right-stack{display:grid;grid-template-columns:1fr 1fr}
    .forecast-grid{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:900px){
    .main-wrapper{margin-left:0;max-width:100%;padding:16px}
    .topbar{align-items:flex-start}
    .date-pill{display:none}
    .control-grid,.analysis-grid,.chart-grid{grid-template-columns:1fr}
    .right-stack{display:flex}
    .forecast-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:560px){
    .forecast-grid{grid-template-columns:1fr}
    .score-layout{flex-direction:column;align-items:flex-start}
    .main-wrapper{padding:12px}
    .brand-title p{font-size:.82rem}
    .brand-title h1{font-size:1.55rem}
    .sun-icon{width:58px;height:58px;font-size:30px}
}

/* ===== Enhanced Light Glassmorphism Layer ===== */
body{
    background:
      radial-gradient(circle at 15% 8%,rgba(255,255,255,.95) 0 5%,transparent 18%),
      radial-gradient(circle at 78% 8%,rgba(113,207,255,.34),transparent 28%),
      radial-gradient(circle at 85% 48%,rgba(91,188,255,.22),transparent 24%),
      radial-gradient(circle at 30% 92%,rgba(62,203,151,.20),transparent 30%),
      linear-gradient(135deg,#dff5ff 0%,#f7fcff 42%,#e9faf4 100%);
}
body:before{
    background:
      radial-gradient(ellipse at 17% 11%,rgba(255,255,255,.82) 0 8%,transparent 9%),
      radial-gradient(ellipse at 31% 8%,rgba(255,255,255,.70) 0 7%,transparent 8%),
      radial-gradient(ellipse at 68% 9%,rgba(255,255,255,.82) 0 8%,transparent 9%),
      radial-gradient(ellipse at 84% 18%,rgba(255,255,255,.65) 0 6%,transparent 7%),
      radial-gradient(circle at 7% 78%,rgba(25,151,111,.12),transparent 24%),
      linear-gradient(180deg,rgba(255,255,255,.20),rgba(255,255,255,0));
}
.main-wrapper{
    padding-top:24px;
}
.main-wrapper:after{
    content:"";
    position:absolute;
    left:8%;right:8%;top:120px;height:420px;
    border-radius:50%;
    background:linear-gradient(120deg,rgba(255,255,255,.25),rgba(119,211,255,.08));
    filter:blur(42px);
    pointer-events:none;z-index:-1;
}
.glass{
    position:relative;
    overflow:hidden;
    background:
      linear-gradient(135deg,rgba(255,255,255,.70),rgba(236,249,255,.38) 48%,rgba(255,255,255,.54));
    border:1px solid rgba(255,255,255,.96);
    box-shadow:
      0 22px 55px rgba(35,105,153,.14),
      0 3px 12px rgba(72,143,190,.07),
      inset 0 1px 0 rgba(255,255,255,1),
      inset 0 -1px 0 rgba(105,180,219,.18),
      inset 12px 0 30px rgba(255,255,255,.08);
    backdrop-filter:blur(32px) saturate(145%);
    -webkit-backdrop-filter:blur(32px) saturate(145%);
}
.glass:before{
    content:"";
    position:absolute;inset:0;
    pointer-events:none;
    background:linear-gradient(115deg,rgba(255,255,255,.34),transparent 28%,transparent 70%,rgba(255,255,255,.13));
    opacity:.9;
}
.control-card,.location-result,.section-card{
    box-shadow:
      0 24px 60px rgba(34,101,148,.15),
      inset 0 1px 0 rgba(255,255,255,1),
      inset 0 0 28px rgba(255,255,255,.13);
}
.control-card{
    background:linear-gradient(135deg,rgba(255,255,255,.76),rgba(224,245,255,.39),rgba(255,255,255,.58));
    border-radius:26px;
    padding:20px;
}
.location-result{
    background:linear-gradient(135deg,rgba(221,250,239,.63),rgba(231,248,255,.45),rgba(255,255,255,.56));
    border-radius:23px;
}
.section-card{
    border-radius:25px;
    padding:18px;
}
.topbar{
    padding:7px 8px 16px;
}
.sun-icon,.date-pill{
    background:linear-gradient(135deg,rgba(255,255,255,.86),rgba(221,245,255,.42));
    box-shadow:
      0 18px 38px rgba(37,110,158,.16),
      inset 0 1px 0 #fff,
      inset 0 -1px 0 rgba(111,191,229,.18);
    border:1px solid rgba(255,255,255,.98);
}
.sun-icon{
    border-radius:28px;
    box-shadow:0 18px 40px rgba(37,110,158,.17),inset 0 2px 0 #fff,inset 0 -2px 0 rgba(111,191,229,.15);
}
.date-pill{border-radius:20px}
.forecast-card,.chart-card,.suitability-card,.recommend-card,.insight-card,.quick,.metric{
    position:relative;
    overflow:hidden;
    background:linear-gradient(145deg,rgba(255,255,255,.73),rgba(233,247,255,.34) 55%,rgba(255,255,255,.54));
    border:1px solid rgba(255,255,255,.92);
    box-shadow:
      0 13px 30px rgba(39,108,153,.09),
      inset 0 1px 0 rgba(255,255,255,.98),
      inset 0 -1px 0 rgba(116,184,219,.13);
    backdrop-filter:blur(22px) saturate(135%);
    -webkit-backdrop-filter:blur(22px) saturate(135%);
}
.forecast-card:before,.chart-card:before,.suitability-card:before,.recommend-card:before,.insight-card:before,.quick:before,.metric:before{
    content:"";position:absolute;left:-30%;top:-55%;width:80%;height:90%;
    border-radius:50%;
    background:rgba(255,255,255,.28);
    filter:blur(15px);
    pointer-events:none;
}
.forecast-card{
    border-radius:20px;
    min-height:175px;
}
.forecast-card:hover,.insight-card:hover,.quick:hover{
    transform:translateY(-4px);
    border-color:rgba(255,255,255,1);
    box-shadow:0 18px 38px rgba(37,108,154,.14),inset 0 1px 0 #fff;
}
.chart-card{border-radius:21px;background:linear-gradient(145deg,rgba(255,255,255,.67),rgba(226,244,255,.34));}
.suitability-card,.recommend-card{border-radius:21px;background:linear-gradient(145deg,rgba(255,255,255,.68),rgba(228,246,255,.33));}
.score-ring{
    box-shadow:0 12px 32px rgba(24,157,111,.15),0 0 0 8px rgba(255,255,255,.20),inset 0 1px 0 rgba(255,255,255,.9);
}
.score-ring:after{background:rgba(248,253,255,.79);backdrop-filter:blur(10px);}
.reason-box,.rec-status,.saved-chip,.live-badge{
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9),0 7px 18px rgba(46,126,150,.06);
}
.reason-box{background:linear-gradient(135deg,rgba(225,251,239,.75),rgba(238,250,255,.54));}
.rec-status{background:linear-gradient(135deg,rgba(219,249,233,.79),rgba(240,253,248,.48));}
.metric{background:linear-gradient(145deg,rgba(244,251,255,.66),rgba(222,242,252,.32));}
.insight-card.green{background:linear-gradient(135deg,rgba(216,249,231,.74),rgba(255,255,255,.42));}
.insight-card.yellow{background:linear-gradient(135deg,rgba(255,242,207,.77),rgba(255,255,255,.43));}
.insight-card.red{background:linear-gradient(135deg,rgba(255,222,229,.76),rgba(255,255,255,.43));}
.insight-card.blue{background:linear-gradient(135deg,rgba(217,240,255,.76),rgba(255,255,255,.43));}
.quick{background:linear-gradient(145deg,rgba(255,255,255,.67),rgba(229,246,255,.35));}
.source-note{color:#58758d}
.analyze-btn,.available-btn{
    box-shadow:0 14px 28px rgba(8,151,108,.24),inset 0 1px 0 rgba(255,255,255,.30);
}
.analyze-btn:hover,.available-btn:hover{box-shadow:0 18px 34px rgba(8,151,108,.29),inset 0 1px 0 rgba(255,255,255,.35)}
.location-input,.equipment-select{
    background:rgba(255,255,255,.50);
    border:1px solid rgba(255,255,255,.92);
    box-shadow:inset 0 1px 8px rgba(71,132,166,.06),0 5px 15px rgba(45,112,151,.05);
    backdrop-filter:blur(15px);
    -webkit-backdrop-filter:blur(15px);
}
@media(max-width:900px){.glass{backdrop-filter:blur(24px) saturate(135%);-webkit-backdrop-filter:blur(24px) saturate(135%);}}

</style>
</head>
<body>
<?php include 'renter_sidebar.php'; ?>

<main class="main-wrapper">
    <div class="topbar">
        <div class="brand-title">
            <div class="sun-icon">🌤️</div>
            <div>
                <h1><?= htmlspecialchars(__('weather_advisor')); ?></h1>
                <p><?= htmlspecialchars(__('weather_advisor_desc')); ?></p>
            </div>
        </div>
        <div class="date-pill"><i class="fa-regular fa-calendar me-2"></i><span id="todayDate">-</span></div>
    </div>

    <section class="glass control-card">
        <div class="control-grid">
            <div class="control-block">
                <div class="control-label"><i class="fa-solid fa-location-dot"></i><?= htmlspecialchars(__('location')); ?></div>
                <div class="location-toggle">
                    <?php if ($registered_location !== ''): ?>
                        <label class="radio-label"><input type="radio" name="location_mode" value="registered" checked> <?= htmlspecialchars(__('use_registered_address')); ?></label>
                    <?php endif; ?>
                    <label class="radio-label"><input type="radio" name="location_mode" value="other" <?= $registered_location === '' ? 'checked' : ''; ?>> <?= htmlspecialchars(__('enter_another_location')); ?></label>
                </div>
                <input type="text" id="otherLocation" class="location-input" maxlength="200" placeholder="<?= htmlspecialchars(__('location_example')); ?>" style="display:<?= $registered_location === '' ? 'block' : 'none'; ?>">
                <?php if ($registered_location !== ''): ?>
                    <div class="saved-chip"><i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars(__('using_registered')); ?>: <strong><?= htmlspecialchars($registered_location); ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="control-block">
                <div class="control-label"><i class="fa-solid fa-tractor"></i><?= htmlspecialchars(__('equipment_type')); ?></div>
                <select id="equipmentType" class="form-select equipment-select">
                    <option value="tractor"><?= htmlspecialchars(__('tractor')); ?></option>
                    <option value="harvesting"><?= htmlspecialchars(__('harvesting')); ?></option>
                    <option value="irrigation"><?= htmlspecialchars(__('irrigation')); ?></option>
                    <option value="tillage"><?= htmlspecialchars(__('tillage')); ?></option>
                    <option value="seeding"><?= htmlspecialchars(__('seeding')); ?></option>
                    <option value="spraying"><?= htmlspecialchars(__('spraying')); ?></option>
                </select>
            </div>

            <div class="analyze-wrap">
                <button class="analyze-btn" id="analyzeBtn" type="button"><i class="fa-solid fa-magnifying-glass-chart me-2"></i><?= htmlspecialchars(__('analyze_weather_rental')); ?></button>
            </div>
        </div>
        <div class="alert-box" id="statusBox"></div>
    </section>

    <div id="dashboardArea" class="hidden">
        <div class="dashboard-grid">
            <div class="left-stack">
                <section class="glass location-result">
                    <div><div class="small text-muted fw-semibold"><?= htmlspecialchars(__('forecast_location')); ?></div><div class="loc-title" id="resultLocation">-</div><div class="coords" id="resultCoordinates">-</div></div>
                    <div class="live-badge"><i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars(__('live_open_meteo')); ?></div>
                </section>

                <section class="glass section-card">
                    <div class="section-head"><h2><i class="fa-solid fa-cloud-sun"></i><?= htmlspecialchars(__('five_day_forecast')); ?></h2><span class="small text-muted" id="resultTimezone">-</span></div>
                    <div class="forecast-grid" id="forecastGrid"></div>
                </section>

                <section class="glass section-card">
                    <div class="chart-grid">
                        <div class="chart-card"><div class="section-head"><h2><i class="fa-solid fa-temperature-half"></i><?= htmlspecialchars(__('temperature_trend')); ?></h2></div><div class="chart-wrap"><canvas id="temperatureChart"></canvas></div></div>
                        <div class="chart-card"><div class="section-head"><h2><i class="fa-solid fa-droplet"></i><?= htmlspecialchars(__('rain_probability')); ?></h2></div><div class="chart-wrap"><canvas id="rainChart"></canvas></div></div>
                    </div>
                </section>

                <section class="glass section-card">
                    <div class="analysis-grid">
                        <div class="suitability-card">
                            <div class="section-head"><h2><i class="fa-solid fa-tractor"></i><?= htmlspecialchars(__('equipment_suitability')); ?> <span id="analysisEquipment">(Tractor)</span></h2></div>
                            <div class="score-layout">
                                <div class="score-ring" id="scoreRing" style="--score:0deg"><div class="score-number"><span id="scoreValue">0%</span><small>Suitability Score</small></div></div>
                                <div class="best-list" id="bestList"></div>
                            </div>
                            <div class="reason-box" id="reasonBox"><i class="fa-solid fa-lightbulb"></i><span>-</span></div>
                        </div>
                        <div class="recommend-card">
                            <div class="section-head"><h2><i class="fa-solid fa-robot"></i><?= htmlspecialchars(__('ai_recommendation')); ?></h2></div>
                            <div class="rec-status" id="recStatus"><div class="status-icon"><i class="fa-solid fa-check"></i></div><div><div class="status-title" id="recTitle">-</div><div class="status-text" id="recText">-</div></div></div>
                            <div class="metric-row">
                                <div class="metric"><i class="fa-solid fa-temperature-half"></i> <?= htmlspecialchars(__('ideal_temperature')); ?><strong id="idealTemp">-</strong></div>
                                <div class="metric"><i class="fa-solid fa-cloud-rain"></i> <?= htmlspecialchars(__('expected_rainfall')); ?><strong id="expectedRain">-</strong></div>
                                <div class="metric"><i class="fa-solid fa-wind"></i> <?= htmlspecialchars(__('wind_speed')); ?><strong id="windMetric">-</strong></div>
                                <div class="metric"><i class="fa-solid fa-droplet"></i> <?= htmlspecialchars(__('humidity')); ?><strong id="humidityMetric">-</strong></div>
                            </div>
                            <button class="available-btn" id="availableBtn" type="button"><i class="fa-solid fa-tractor me-2"></i><?= htmlspecialchars(__('available_equipment')); ?> →</button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="right-stack">
                <section class="glass section-card">
                    <div class="section-head"><h2><i class="fa-solid fa-chart-column"></i><?= htmlspecialchars(__('insights')); ?></h2></div>
                    <div class="insight-list" id="insightList"></div>
                </section>
                <section class="glass section-card">
                    <div class="section-head"><h2><i class="fa-solid fa-cloud-sun"></i><?= htmlspecialchars(__('quick_weather_summary')); ?></h2></div>
                    <div class="quick-grid">
                        <div class="quick"><i class="fa-solid fa-temperature-high"></i><small><?= htmlspecialchars(__('max_temp')); ?></small><strong id="maxTemp">-</strong></div>
                        <div class="quick"><i class="fa-solid fa-droplet"></i><small><?= htmlspecialchars(__('avg_rain_chance')); ?></small><strong id="avgRain">-</strong></div>
                        <div class="quick"><i class="fa-solid fa-wind"></i><small><?= htmlspecialchars(__('wind_speed')); ?></small><strong id="avgWind">-</strong></div>
                        <div class="quick"><i class="fa-solid fa-water"></i><small><?= htmlspecialchars(__('humidity')); ?></small><strong id="avgHumidity">-</strong></div>
                    </div>
                    <div class="source-note"><i class="fa-solid fa-leaf me-1"></i><?= htmlspecialchars(__('weather_data_note')); ?></div>
                </section>
            </aside>
        </div>
    </div>
</main>

<script>
const T = <?= json_encode($translations_for_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const registeredAvailable = <?= $registered_location !== '' ? 'true' : 'false'; ?>;
let temperatureChart = null;
let rainChart = null;
let latestForecast = [];
let latestEquipment = 'tractor';

const equipmentMeta = {
    tractor:{label:<?= json_encode(__('tractor'), JSON_UNESCAPED_UNICODE); ?>,icon:'fa-tractor',rule:'tractor'},
    harvesting:{label:<?= json_encode(__('harvesting'), JSON_UNESCAPED_UNICODE); ?>,icon:'fa-wheat-awn',rule:'harvesting'},
    irrigation:{label:<?= json_encode(__('irrigation'), JSON_UNESCAPED_UNICODE); ?>,icon:'fa-droplet',rule:'irrigation'},
    tillage:{label:<?= json_encode(__('tillage'), JSON_UNESCAPED_UNICODE); ?>,icon:'fa-seedling',rule:'tillage'},
    seeding:{label:<?= json_encode(__('seeding'), JSON_UNESCAPED_UNICODE); ?>,icon:'fa-seedling',rule:'seeding'},
    spraying:{label:<?= json_encode(__('spraying'), JSON_UNESCAPED_UNICODE); ?>,icon:'fa-spray-can-sparkles',rule:'spraying'}
};

const el = id => document.getElementById(id);
function mode(){const r=document.querySelector('input[name="location_mode"]:checked');return r?r.value:'registered';}
function updateLocation(){const other=mode()==='other';el('otherLocation').style.display=other?'block':'none';if(other)setTimeout(()=>el('otherLocation').focus(),60);}
document.querySelectorAll('input[name="location_mode"]').forEach(r=>r.addEventListener('change',updateLocation));

function status(msg,type){const box=el('statusBox');box.className='alert-box '+type;box.innerHTML=msg;}
function weatherIcon(code){if(code===0)return'☀️';if([1,2,3].includes(code))return'🌤️';if([45,48].includes(code))return'🌫️';if([51,53,55,56,57].includes(code))return'🌦️';if([61,63,65,66,67,80,81,82].includes(code))return'🌧️';if([71,73,75,77,85,86].includes(code))return'🌨️';if([95,96,99].includes(code))return'⛈️';return'🌦️';}
function weatherText(code){return T.weather[String(code)]||'Weather condition';}
function dateLabel(s){const d=new Date(s+'T00:00:00');return d.toLocaleDateString(undefined,{weekday:'short',day:'numeric',month:'short'});}
function shortDay(i,s){if(i===0)return T.today;if(i===1)return T.tomorrow;return T.day+' '+(i+1)+' ('+dateLabel(s)+')';}
function rainClass(p){if(p>=70)return'rain-high';if(p>=40)return'rain-mid';return'rain-low';}
function renderForecast(forecast){el('forecastGrid').innerHTML='';forecast.forEach((d,i)=>{const card=document.createElement('div');card.className='forecast-card';const rain=d.rain_probability==null?'N/A':d.rain_probability+'%';const max=d.temperature_max==null?'N/A':Math.round(d.temperature_max)+'°C';const min=d.temperature_min==null?'N/A':Math.round(d.temperature_min)+'°C';const mm=d.precipitation==null?'N/A':d.precipitation.toFixed(1)+' mm';card.innerHTML=`<div class="forecast-date">${dateLabel(d.date)}</div><div class="weather-icon">${weatherIcon(d.weather_code)}</div><div class="weather-condition">${weatherText(d.weather_code)}</div><div class="temp">${max} / ${min}</div><span class="rain-pill ${rainClass(d.rain_probability||0)}"><i class="fa-solid fa-droplet"></i> ${rain} ${T.rain_chance.replace(':','')}</span><div class="mini-data">${T.rainfall} ${mm}</div>`;el('forecastGrid').appendChild(card);});}

function scoreDay(d,type){let score=82;const rain=d.rain_probability??0;const mm=d.precipitation??0;const wind=d.wind_speed??0;const temp=d.temperature_max??28;
    if(type==='tractor'){if(rain>80)score-=48;else if(rain>60)score-=28;else if(rain>40)score-=14;if(mm>10)score-=15;if(temp>=20&&temp<=34)score+=7;else if(temp<16||temp>38)score-=12;}
    if(type==='harvesting'){if(rain>80)score-=55;else if(rain>60)score-=40;else if(rain>40)score-=25;if(mm>5)score-=18;if(wind>25)score-=12;if(temp<15||temp>38)score-=10;}
    if(type==='irrigation'){if(rain>80)score-=48;else if(rain>60)score-=32;else if(rain>40)score-=15;else if(rain<20)score+=10;if(mm<2)score+=4;}
    if(type==='tillage'){if(rain>80)score-=52;else if(rain>60)score-=35;else if(rain>40)score-=20;if(mm>10)score-=18;if(temp>=18&&temp<=34)score+=7;}
    if(type==='seeding'){if(rain>=25&&rain<=60)score+=10;else if(rain>80)score-=38;else if(rain<15)score-=12;if(temp>=18&&temp<=34)score+=6;if(mm>15)score-=12;}
    if(type==='spraying'){if(rain>70)score-=62;else if(rain>40)score-=40;else if(rain>20)score-=18;if(wind>20)score-=25;else if(wind>15)score-=12;if(rain<15&&wind<15)score+=10;}
    return Math.max(0,Math.min(100,Math.round(score)));
}
function analyze(forecast,type){const scored=forecast.map((d,i)=>({...d,index:i,score:scoreDay(d,type)}));const sorted=[...scored].sort((a,b)=>b.score-a.score);const best=sorted.slice(0,2);const avoid=scored.filter(d=>d.score<45);const avg=Math.round(scored.reduce((s,d)=>s+d.score,0)/scored.length);return{scored,best,avoid,avg};}
function reasonFor(type,scored){const avgRain=scored.reduce((s,d)=>s+(d.rain_probability||0),0)/scored.length;const avgWind=scored.reduce((s,d)=>s+(d.wind_speed||0),0)/scored.length;const maxRain=Math.max(...scored.map(d=>d.rain_probability||0));if(type==='spraying'&&maxRain>40)return T.spray_rain_warning;if(type==='irrigation'&&maxRain>60)return T.irrigation_rain_warning;if(type==='harvesting'&&maxRain>50)return T.harvesting_rain_warning;if(type==='seeding'&&avgRain>=20&&avgRain<=60)return T.seeding_weather_reason;if(type==='tillage'&&avgRain<45)return T.tillage_weather_reason;if(avgRain<35)return T.low_rain_good_temp;if(avgRain>65)return T.rain_unfavorable;return T.dry_weather;}
function recommendation(a,type){const top=a.best[0];if(a.avg>=70)return{kind:'good',title:T.good_time_to_rent,text:`${equipmentMeta[type].label} ${T.suitable.toLowerCase()} — ${shortDay(top.index,top.date)} has the strongest weather score.`};if(a.avg>=45)return{kind:'warn',title:T.use_with_caution,text:`${equipmentMeta[type].label} can be used, but weather changes mean you should prefer ${shortDay(top.index,top.date)}.`};return{kind:'bad',title:T.avoid_rental,text:T.rain_unfavorable};}

function renderCharts(forecast,analysis){const labels=forecast.map(d=>dateLabel(d.date));if(temperatureChart)temperatureChart.destroy();if(rainChart)rainChart.destroy();const common={responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:9,font:{size:10}}}},scales:{x:{grid:{display:false},ticks:{font:{size:10}}},y:{ticks:{font:{size:10}}}}};temperatureChart=new Chart(el('temperatureChart'),{type:'line',data:{labels,datasets:[{label:'Max Temp',data:forecast.map(d=>d.temperature_max),borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,.08)',tension:.35,pointRadius:3},{label:'Min Temp',data:forecast.map(d=>d.temperature_min),borderColor:'#2b8bea',backgroundColor:'rgba(43,139,234,.08)',tension:.35,pointRadius:3}]},options:common});rainChart=new Chart(el('rainChart'),{type:'bar',data:{labels,datasets:[{label:'Rain %',data:forecast.map(d=>d.rain_probability),backgroundColor:'#4aa0eb',borderRadius:6}]},options:{...common,scales:{...common.scales,y:{...common.scales.y,min:0,max:100,ticks:{callback:v=>v+'%',font:{size:10}}}}}});}
function renderAnalysis(forecast,type){const a=analyze(forecast,type);latestEquipment=type;el('analysisEquipment').textContent='('+equipmentMeta[type].label+')';el('scoreValue').textContent=a.avg+'%';el('scoreRing').style.setProperty('--score',(a.avg*3.6)+'deg');let html=`<div class="best-item"><i class="fa-solid fa-calendar-check ok"></i><span><strong>${T.best_days_to_rent}</strong><br>${a.best.map(x=>shortDay(x.index,x.date)).join(', ')}</span></div>`;if(a.best[0])html+=`<div class="best-item"><i class="fa-solid fa-circle-check ok"></i><span>${shortDay(a.best[0].index,a.best[0].date)} — ${a.best[0].score}% ${T.highly_suitable}</span></div>`;if(a.scored.find(x=>x.index===2))html+=`<div class="best-item"><i class="fa-solid fa-triangle-exclamation warn"></i><span>${shortDay(2,a.scored[2].date)} — ${a.scored[2].score}% ${T.use_caution}</span></div>`;if(a.avoid.length){html+=`<div class="best-item"><i class="fa-solid fa-circle-xmark bad"></i><span><strong>${T.avoid}</strong><br>${a.avoid.map(x=>shortDay(x.index,x.date)).join(', ')}</span></div>`;}el('bestList').innerHTML=html;el('reasonBox').querySelector('span').textContent=reasonFor(type,a.scored);
const rec=recommendation(a,type);const rs=el('recStatus');rs.className='rec-status '+(rec.kind==='good'?'good':'');rs.querySelector('.status-icon').innerHTML=rec.kind==='good'?'<i class="fa-solid fa-check"></i>':rec.kind==='warn'?'<i class="fa-solid fa-triangle-exclamation"></i>':'<i class="fa-solid fa-xmark"></i>';rs.querySelector('.status-icon').style.background=rec.kind==='good'?'#20a96e':rec.kind==='warn'?'#e9a21b':'#e24758';el('recTitle').textContent=rec.title;el('recText').textContent=rec.text;
const best= a.best[0]||forecast[0];const avgRain=Math.round(forecast.reduce((s,d)=>s+(d.rain_probability||0),0)/forecast.length);const avgWind=(forecast.reduce((s,d)=>s+(d.wind_speed||0),0)/forecast.length).toFixed(0);const avgHum=Math.round(forecast.reduce((s,d)=>s+(d.humidity||0),0)/forecast.length);const maxTemp=Math.max(...forecast.map(d=>d.temperature_max||0));el('idealTemp').textContent=(Math.round(Math.min(...forecast.map(d=>d.temperature_min||0)))+'°C – '+Math.round(Math.max(...forecast.map(d=>d.temperature_max||0)))+'°C');el('expectedRain').textContent=avgRain+'% avg.';el('windMetric').textContent=avgWind+' km/h';el('humidityMetric').textContent=avgHum+'%';el('maxTemp').textContent=Math.round(maxTemp)+'°C';el('avgRain').textContent=avgRain+'%';el('avgWind').textContent=avgWind+' km/h';el('avgHumidity').textContent=avgHum+'%';
renderInsights(forecast,type,a);renderCharts(forecast,a);el('dashboardArea').classList.remove('hidden');}
function renderInsights(forecast,selected,analysis){const types=['tractor','harvesting','spraying','irrigation'];el('insightList').innerHTML='';types.forEach((type,i)=>{const a=analyze(forecast,type);const best=a.best[0];const card=document.createElement('div');card.className='insight-card '+(type===selected?'green':i===1?'yellow':i===2?'red':'blue');card.innerHTML=`<div class="insight-icon"><i class="fa-solid ${equipmentMeta[type].icon}"></i></div><div><div class="insight-title">${equipmentMeta[type].label}</div><div class="insight-desc"><strong>${T.best_days}:</strong> ${a.best.slice(0,2).map(x=>shortDay(x.index,x.date)).join(', ')}<br>${reasonFor(type,a.scored)}</div></div>`;el('insightList').appendChild(card);});}

el('analyzeBtn').addEventListener('click',async()=>{const m=mode();const loc=el('otherLocation').value.trim();const type=el('equipmentType').value;if(m==='other'&&!loc){status('<i class="fa-solid fa-circle-exclamation me-2"></i>'+T.please_enter_location,'error');return;}el('analyzeBtn').disabled=true;el('analyzeBtn').innerHTML='<i class="fa-solid fa-spinner fa-spin me-2"></i>'+T.getting_forecast;status('<i class="fa-solid fa-cloud-arrow-down me-2"></i>'+T.getting_forecast,'info');const fd=new FormData();fd.append('action','get_weather');fd.append('location_mode',m);fd.append('location',loc);try{const r=await fetch(window.location.href,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});const data=await r.json();if(!r.ok||!data.success)throw new Error(data.message||'Unable to get weather data.');const p=data.location;el('resultLocation').textContent=[p.name,p.admin1,p.country].filter(Boolean).join(', ');el('resultCoordinates').textContent=`Latitude: ${Number(p.latitude).toFixed(5)} | Longitude: ${Number(p.longitude).toFixed(5)}`;el('resultTimezone').textContent=p.timezone||T.local_time;latestForecast=data.forecast;renderForecast(latestForecast);renderAnalysis(latestForecast,type);status('<i class="fa-solid fa-circle-check me-2"></i>'+T.analysis_ready,'info');el('dashboardArea').scrollIntoView({behavior:'smooth',block:'start'});}catch(e){status('<i class="fa-solid fa-triangle-exclamation me-2"></i>'+e.message,'error');}finally{el('analyzeBtn').disabled=false;el('analyzeBtn').innerHTML='<i class="fa-solid fa-magnifying-glass-chart me-2"></i>'+T.analyze_weather_rental;}});

el('equipmentType').addEventListener('change',()=>{if(latestForecast.length)renderAnalysis(latestForecast,el('equipmentType').value);});
el('availableBtn').addEventListener('click',()=>{const q=encodeURIComponent(equipmentMeta[latestEquipment].label);window.location.href='search_equipment.php?q='+q;});
el('todayDate').textContent=new Date().toLocaleDateString(undefined,{weekday:'short',day:'2-digit',month:'short',year:'numeric'});
updateLocation();
</script>
</body>
</html>
