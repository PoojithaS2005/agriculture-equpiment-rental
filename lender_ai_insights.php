<?php require_once __DIR__ . '/lender_ai_insights_data.php'; ?><?php
function lai_t(string $key): string {
    $lang = strtolower((string)($_SESSION['lang'] ?? $_SESSION['language'] ?? $_GET['lang'] ?? 'en'));
    $translations = [
        'kn' => [
            "Agriculture Equipment Rental System" => "ಕೃಷಿ ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ",
            "AI-ASSISTED ANALYSIS" => "AI ನೆರವಿನ ವಿಶ್ಲೇಷಣೆ",
            "Lender AI Insights" => "ಸಾಲದಾತರ AI ಒಳನೋಟಗಳು",
            "Welcome, " => "ಸ್ವಾಗತ, ",
            ". Choose a section to explore your rental data and recommendations." => ". ನಿಮ್ಮ ಬಾಡಿಗೆ ಡೇಟಾ ಮತ್ತು ಶಿಫಾರಸುಗಳನ್ನು ನೋಡಲು ಒಂದು ವಿಭಾಗವನ್ನು ಆಯ್ಕೆಮಾಡಿ.",
            "Back to AI Insights Dashboard" => "AI ಒಳನೋಟಗಳ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ಗೆ ಹಿಂತಿರುಗಿ",
            "About these insights:" => "ಈ ಒಳನೋಟಗಳ ಬಗ್ಗೆ:",
            " These are rule-based estimates from available booking, equipment, weather and rating records—not predictions from a trained machine-learning model. More real data can improve their usefulness." => " ಇವು ಲಭ್ಯವಿರುವ ಬುಕಿಂಗ್, ಉಪಕರಣ, ಹವಾಮಾನ ಮತ್ತು ರೇಟಿಂಗ್ ದಾಖಲೆಗಳನ್ನು ಆಧರಿಸಿದ ನಿಯಮಾಧಾರಿತ ಅಂದಾಜುಗಳು; ತರಬೇತಿ ಪಡೆದ ಯಂತ್ರ-ಕಲಿಕೆ ಮಾದರಿಯ ಮುನ್ಸೂಚನೆಗಳಲ್ಲ. ಹೆಚ್ಚಿನ ನೈಜ ಡೇಟಾ ಇವುಗಳ ಉಪಯುಕ್ತತೆಯನ್ನು ಹೆಚ್ಚಿಸಬಹುದು.",
            "Your equipment" => "ನಿಮ್ಮ ಉಪಕರಣಗಳು",
            "Eligible booking records" => "ಅರ್ಹ ಬುಕಿಂಗ್ ದಾಖಲೆಗಳು",
            "Active rentals" => "ಸಕ್ರಿಯ ಬಾಡಿಗೆಗಳು",
            "Recorded completed rental value" => "ದಾಖಲಾದ ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆ ಮೊತ್ತ",
            "Explore AI features" => "AI ವೈಶಿಷ್ಟ್ಯಗಳನ್ನು ಅನ್ವೇಷಿಸಿ",
            "Equipment Recommendations" => "ಉಪಕರಣ ಶಿಫಾರಸುಗಳು",
            "Demand indicators, weather outlook, pricing guidance, utilization and price history." => "ಬೇಡಿಕೆ ಸೂಚಕಗಳು, ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ, ಬೆಲೆ ಮಾರ್ಗದರ್ಶನ, ಬಳಕೆ ಮತ್ತು ಬೆಲೆ ಇತಿಹಾಸ.",
            "Maintenance Guidance" => "ನಿರ್ವಹಣೆ ಮಾರ್ಗದರ್ಶನ",
            "Estimated rental usage and practical inspection reminders." => "ಅಂದಾಜು ಬಾಡಿಗೆ ಬಳಕೆ ಮತ್ತು ಪ್ರಾಯೋಗಿಕ ಪರಿಶೀಲನೆ ನೆನಪಿಸುವಿಕೆಗಳು.",
            "Review Analysis" => "ವಿಮರ್ಶೆ ವಿಶ್ಲೇಷಣೆ",
            "Summarized customer ratings and low-rating signals." => "ಗ್ರಾಹಕರ ರೇಟಿಂಗ್‌ಗಳ ಸಾರಾಂಶ ಮತ್ತು ಕಡಿಮೆ ರೇಟಿಂಗ್ ಸೂಚನೆಗಳು.",
            "Revenue Trends & Estimate" => "ಆದಾಯದ ಪ್ರವೃತ್ತಿಗಳು ಮತ್ತು ಅಂದಾಜು",
            "Completed rental revenue history and a simple baseline estimate." => "ಪೂರ್ಣಗೊಂಡ ಬಾಡಿಗೆ ಆದಾಯದ ಇತಿಹಾಸ ಮತ್ತು ಸರಳ ಮೂಲ ಅಂದಾಜು.",
            "Rental Booking Activity" => "ಬಾಡಿಗೆ ಬುಕಿಂಗ್ ಚಟುವಟಿಕೆ",
            "Eligible bookings" => "ಅರ್ಹ ಬುಕಿಂಗ್‌ಗಳು",
            "Eligible bookings grouped by start month. Rejected and cancelled bookings are excluded." => "ಪ್ರಾರಂಭದ ತಿಂಗಳ ಪ್ರಕಾರ ಅರ್ಹ ಬುಕಿಂಗ್‌ಗಳನ್ನು ಗುಂಪು ಮಾಡಲಾಗಿದೆ. ತಿರಸ್ಕರಿಸಿದ ಮತ್ತು ರದ್ದುಗೊಳಿಸಿದ ಬುಕಿಂಗ್‌ಗಳನ್ನು ಹೊರತುಪಡಿಸಲಾಗಿದೆ.",
        ],
        'hi' => [
            "Agriculture Equipment Rental System" => "कृषि उपकरण किराया प्रणाली",
            "AI-ASSISTED ANALYSIS" => "AI-सहायता प्राप्त विश्लेषण",
            "Lender AI Insights" => "ऋणदाता AI जानकारी",
            "Welcome, " => "स्वागत है, ",
            ". Choose a section to explore your rental data and recommendations." => ". अपने किराया डेटा और सुझावों को देखने के लिए एक अनुभाग चुनें।",
            "Back to AI Insights Dashboard" => "AI जानकारी डैशबोर्ड पर वापस जाएँ",
            "About these insights:" => "इन जानकारियों के बारे में:",
            " These are rule-based estimates from available booking, equipment, weather and rating records—not predictions from a trained machine-learning model. More real data can improve their usefulness." => " ये उपलब्ध बुकिंग, उपकरण, मौसम और रेटिंग रिकॉर्ड पर आधारित नियम-आधारित अनुमान हैं—प्रशिक्षित मशीन-लर्निंग मॉडल की भविष्यवाणियाँ नहीं। अधिक वास्तविक डेटा इनकी उपयोगिता बढ़ा सकता है।",
            "Your equipment" => "आपके उपकरण",
            "Eligible booking records" => "योग्य बुकिंग रिकॉर्ड",
            "Active rentals" => "सक्रिय किराये",
            "Recorded completed rental value" => "दर्ज पूर्ण किराया राशि",
            "Explore AI features" => "AI सुविधाएँ देखें",
            "Equipment Recommendations" => "उपकरण सुझाव",
            "Demand indicators, weather outlook, pricing guidance, utilization and price history." => "मांग संकेतक, मौसम पूर्वानुमान, मूल्य मार्गदर्शन, उपयोग और मूल्य इतिहास।",
            "Maintenance Guidance" => "रखरखाव मार्गदर्शन",
            "Estimated rental usage and practical inspection reminders." => "अनुमानित किराया उपयोग और व्यावहारिक निरीक्षण अनुस्मारक।",
            "Review Analysis" => "समीक्षा विश्लेषण",
            "Summarized customer ratings and low-rating signals." => "ग्राहक रेटिंग का सारांश और कम रेटिंग के संकेत।",
            "Revenue Trends & Estimate" => "आय के रुझान और अनुमान",
            "Completed rental revenue history and a simple baseline estimate." => "पूर्ण किराये की आय का इतिहास और एक सरल आधारभूत अनुमान।",
            "Rental Booking Activity" => "किराया बुकिंग गतिविधि",
            "Eligible bookings" => "योग्य बुकिंग",
            "Eligible bookings grouped by start month. Rejected and cancelled bookings are excluded." => "योग्य बुकिंग को शुरू होने के महीने के अनुसार समूहित किया गया है। अस्वीकृत और रद्द बुकिंग शामिल नहीं हैं।",
        ],
    ];
    return $translations[$lang][$key] ?? $key;
}
?>

<!doctype html><html lang="<?= h($current_lang) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= lai_t('Lender AI Insights') ?> - <?= lai_t('Agriculture Equipment Rental System') ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><style>
*{box-sizing:border-box}body{margin:0;background:#f3f7f4;color:#26352d;font-family:'Segoe UI',Arial,sans-serif}.main-content{margin-left:250px;padding:28px;min-height:100vh}.hero{padding:26px;margin-bottom:22px;border-radius:18px;color:white;background:linear-gradient(120deg,#147d50,#248f9b,#5366c8)}.hero h1{font-weight:750;font-size:clamp(24px,3vw,34px);margin:10px 0}.hero p{margin:0}.pill{display:inline-block;padding:5px 10px;border-radius:30px;background:#e2f5e9;color:#17683b;font-size:12px;font-weight:700}.panel,.summary-card,.feature-card,.equipment-card{background:white;border:1px solid #e5ece7;border-radius:16px;box-shadow:0 6px 20px #2e47400d}.panel{padding:22px;margin-bottom:20px}.panel h2{font-size:20px;font-weight:750;margin:0 0 16px}.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.summary-card{padding:18px}.summary-card small,.muted{color:#66756b}.summary-value{display:block;margin-top:7px;font-size:24px;font-weight:750;overflow-wrap:anywhere}.feature-grid,.equipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.feature-card{padding:20px;color:inherit;text-decoration:none;display:block;background-color:#eff9f1;transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease,background-color .25s ease;will-change:transform}.feature-card:hover,.feature-card:focus-visible{transform:translateY(-5px);border-color:#2d8a62;box-shadow:0 12px 26px rgba(30,110,75,.16);background-color:#e1f3e6}.feature-card:focus-visible{outline:3px solid rgba(45,138,98,.3);outline-offset:3px}.feature-icon{font-size:24px;color:#168050;margin-bottom:12px}.equipment-card{padding:18px;min-width:0}.metric-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #e8eee9}.metric-row strong{text-align:right;overflow-wrap:anywhere}.muted{font-size:13px}.notice{background:#fff8df;border:1px solid #f1dfa0;border-radius:12px;padding:14px 16px;margin-bottom:18px;color:#69551a}.chart-container{position:relative;height:300px}.table-responsive{overflow-x:auto}@media(max-width:1100px){.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.feature-grid,.equipment-grid{grid-template-columns:1fr}}@media(max-width:700px){.main-content{margin-left:0;padding:15px}.summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.summary-card{padding:13px}.summary-value{font-size:20px}.panel,.hero{padding:17px}}
</style></head><body><?php include __DIR__ . '/lender_sidebar.php'; ?><main class="main-content"><section class="hero"><span class="pill"><?= lai_t('AI-ASSISTED ANALYSIS') ?></span><h1><?= lai_t('Lender AI Insights') ?></h1><p><?= lai_t('Welcome, ') ?><?= h($lender_name) ?><?= lai_t('. Choose a section to explore your rental data and recommendations.') ?></p></section><div class="mb-3"><a class="btn btn-outline-success" href="lender_ai_insights.php"><i class="fa-solid fa-arrow-left me-2"></i><?= lai_t('Back to AI Insights Dashboard') ?></a></div><div class="notice"><strong><?= lai_t('About these insights:') ?></strong><?= lai_t(' These are rule-based estimates from available booking, equipment, weather and rating records—not predictions from a trained machine-learning model. More real data can improve their usefulness.') ?></div>
<section class="summary-grid"><div class="summary-card"><small><?= lai_t('Your equipment') ?></small><span class="summary-value"><?= (int)$totalEquipment ?></span></div><div class="summary-card"><small><?= lai_t('Eligible booking records') ?></small><span class="summary-value"><?= (int)$totalBookings ?></span></div><div class="summary-card"><small><?= lai_t('Active rentals') ?></small><span class="summary-value"><?= (int)$activeBookings ?></span></div><div class="summary-card"><small><?= lai_t('Recorded completed rental value') ?></small><span class="summary-value"><?= money($totalRevenue) ?></span></div></section>
<section class="panel"><h2><?= lai_t('Explore AI features') ?></h2><div class="feature-grid">
<a class="feature-card" href="lender_ai_equipment_recommendations.php"><div class="feature-icon"><i class="fa-solid fa-chart-line"></i></div><h3><?= lai_t('Equipment Recommendations') ?></h3><p><?= lai_t('Demand indicators, weather outlook, pricing guidance, utilization and price history.') ?></p></a>
<a class="feature-card" href="lender_ai_maintenance.php"><div class="feature-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div><h3><?= lai_t('Maintenance Guidance') ?></h3><p><?= lai_t('Estimated rental usage and practical inspection reminders.') ?></p></a>
<a class="feature-card" href="lender_ai_review_analysis.php"><div class="feature-icon"><i class="fa-solid fa-star"></i></div><h3><?= lai_t('Review Analysis') ?></h3><p><?= lai_t('Summarized customer ratings and low-rating signals.') ?></p></a>
<a class="feature-card" href="lender_ai_revenue_prediction.php"><div class="feature-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div><h3><?= lai_t('Revenue Trends & Estimate') ?></h3><p><?= lai_t('Completed rental revenue history and a simple baseline estimate.') ?></p></a></div></section>
<section class="panel"><h2><?= lai_t('Rental Booking Activity') ?></h2><div class="chart-container"><canvas id="bookingChart"></canvas></div><p class="muted mt-3"><?= lai_t('Eligible bookings grouped by start month. Rejected and cancelled bookings are excluded.') ?></p></section><script>new Chart(document.getElementById('bookingChart'),{type:'bar',data:{labels:<?= json_encode($monthLabels) ?>,datasets:[{label:'<?= lai_t('Eligible bookings') ?>',data:<?= json_encode($monthlyBookings) ?>,backgroundColor:'rgba(31,139,87,.72)',borderColor:'#1f8b57',borderWidth:1,borderRadius:7}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});</script></main></body></html>