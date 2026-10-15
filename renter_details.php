<?php
session_start();
require_once 'includes/config.php';

$allowed_languages = ['en', 'kn', 'hi'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_languages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
    $_SESSION['language'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] ?? $_SESSION['language'] ?? 'en';
if (!in_array($current_lang, $allowed_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = $current_lang;
    $_SESSION['language'] = $current_lang;
}
require_once 'includes/lang.php';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function tr_local($en, $kn, $hi, $lang) {
    return $lang === 'kn' ? $kn : ($lang === 'hi' ? $hi : $en);
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php?lang=' . urlencode($current_lang));
    exit;
}
$lender_id = (int)$_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    http_response_code(400);
    exit('Invalid booking.');
}

/* Enforce booking -> equipment -> logged-in lender ownership on every request,
   including the protected document preview/download mode. */
$stmt = $conn->prepare("
    SELECT b.booking_id, b.request_code, b.renter_id, b.phone_number,
           b.id_number, b.id_proof_doc, b.delivery_address, b.start_date,
           b.end_date, b.total_days, b.status, b.total_amount,
           u.full_name, u.email, u.phone AS account_phone, u.address AS account_address,
           u.city, u.district, u.state, e.title AS equipment_title, e.lender_id
    FROM bookings b
    INNER JOIN equipment e ON e.equipment_id = b.equipment_id
    INNER JOIN users u ON u.user_id = b.renter_id
    WHERE b.booking_id = ? AND e.lender_id = ? AND u.role = 'renter'
    LIMIT 1
");
if (!$stmt) {
    http_response_code(500);
    exit('Database error.');
}
$stmt->bind_param('ii', $booking_id, $lender_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$booking) {
    http_response_code(404);
    exit(tr_local('Booking not found or access denied.', 'ಬುಕಿಂಗ್ ಕಂಡುಬಂದಿಲ್ಲ ಅಥವಾ ಪ್ರವೇಶ ನಿರಾಕರಿಸಲಾಗಿದೆ.', 'बुकिंग नहीं मिली या पहुँच अस्वीकृत है।', $current_lang));
}

/* Serve the ID file only after the ownership check above. Do not expose its
   filesystem path in the page or accept a user-supplied file path. */
if (isset($_GET['document']) && $_GET['document'] === '1' && (($_GET['raw'] ?? '') === '1' || ($_GET['download'] ?? '') === '1')) {
    $stored = trim((string)($booking['id_proof_doc'] ?? ''));
    if ($stored === '') {
        http_response_code(404);
        exit('ID proof is not available.');
    }
    $stored = str_replace('\\', '/', $stored);
    $basename = basename($stored);
    $candidates = [
        __DIR__ . '/uploads/' . $basename,
        __DIR__ . '/' . ltrim($stored, '/'),
        __DIR__ . '/uploads/id_proofs/' . $basename,
        __DIR__ . '/uploads/id_proof/' . $basename
    ];
    $file = false;
    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real && is_file($real) && str_starts_with($real, realpath(__DIR__) . DIRECTORY_SEPARATOR)) {
            $file = $real;
            break;
        }
    }
    if (!$file) {
        http_response_code(404);
        exit('Stored ID proof file could not be found. Check the upload path saved in bookings.id_proof_doc.');
    }
    $mime = 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_file($finfo, $file);
            if (is_string($detected) && in_array($detected, ['image/jpeg','image/png','image/gif','image/webp','application/pdf'], true)) {
                $mime = $detected;
            }
            finfo_close($finfo);
        }
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $download = isset($_GET['download']) && $_GET['download'] === '1';
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="renter-id-proof.' . preg_replace('/[^a-z0-9]/', '', $ext) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

$lang_q = urlencode($current_lang);
$back_url = 'lender_booking_details.php?booking_id=' . $booking_id . '&lang=' . $lang_q;
$phone = trim((string)($booking['phone_number'] ?? ''));
if ($phone === '') $phone = trim((string)($booking['account_phone'] ?? ''));
$address = trim((string)($booking['delivery_address'] ?? ''));
if ($address === '') {
    $parts = array_filter([
        trim((string)($booking['account_address'] ?? '')),
        trim((string)($booking['city'] ?? '')),
        trim((string)($booking['district'] ?? '')),
        trim((string)($booking['state'] ?? ''))
    ]);
    $address = implode(', ', $parts);
}
if ($address === '') $address = '—';
$proof_available = trim((string)($booking['id_proof_doc'] ?? '')) !== '';
$document_url = 'renter_details.php?booking_id=' . $booking_id . '&lang=' . $lang_q . '&document=1';
$preview_url = 'renter_details.php?booking_id=' . $booking_id . '&lang=' . $lang_q . '&preview=1';
$is_preview = isset($_GET['preview']) && $_GET['preview'] === '1';
?>
<!doctype html>
<html lang="<?php echo e($current_lang); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e(tr_local('Renter Details','ಬಾಡಿಗೆದಾರರ ವಿವರಗಳು','किराएदार का विवरण',$current_lang)); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9;color:#1e293b;font-family:'Segoe UI',Tahoma,sans-serif;margin:0}
.wrap{max-width:1050px;margin:28px auto;padding:0 18px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.cardx{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:18px;box-shadow:0 2px 6px #00000005}
h1{font-size:25px;font-weight:850}.label{font-size:11px;text-transform:uppercase;color:#64748b;font-weight:800;margin-bottom:5px}
.value{font-size:15px;font-weight:700;overflow-wrap:anywhere}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.field{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:13px}
.btn-main{background:#0f4c5c;color:white;text-decoration:none;border:0;border-radius:8px;padding:10px 14px;font-weight:750;display:inline-flex;gap:8px;align-items:center}
.btn-main:hover{color:white;background:#0b3945}.warning{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:10px;padding:14px;line-height:1.55;margin-bottom:18px}
.proof{display:block;max-width:100%;max-height:520px;object-fit:contain;margin:14px auto;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}
@media(max-width:650px){.grid{grid-template-columns:1fr}.cardx{padding:16px}}
</style>
</head>
<body>
<?php if ($is_preview): ?>
<div class="wrap">
  <div class="top">
    <div>
      <h1><i class="fa-solid fa-file-shield"></i> <?php echo e(tr_local('View Renter ID Proof','ಬಾಡಿಗೆದಾರರ ಗುರುತಿನ ದಾಖಲೆ ವೀಕ್ಷಿಸಿ','किराएदार का पहचान प्रमाण देखें',$current_lang)); ?></h1>
      <div class="text-secondary"><?php echo e($booking['request_code'] ?: ('#'.$booking_id)); ?> · <?php echo e($booking['equipment_title']); ?></div>
    </div>
    <a class="btn-main" href="renter_details.php?booking_id=<?php echo (int)$booking_id; ?>&amp;lang=<?php echo e($lang_q); ?>"><i class="fa-solid fa-arrow-left"></i> <?php echo e(tr_local('Back to Renter Details','ಬಾಡಿಗೆದಾರರ ವಿವರಗಳಿಗೆ ಹಿಂತಿರುಗಿ','किराएदार के विवरण पर वापस जाएँ',$current_lang)); ?></a>
  </div>
  <div class="warning"><strong><i class="fa-solid fa-shield-halved"></i> <?php echo e(tr_local('Confidential document','ಗೌಪ್ಯ ದಾಖಲೆ','गोपनीय दस्तावेज़',$current_lang)); ?></strong><br><?php echo e(tr_local('Use this document only for rental verification. Do not copy, misuse, or share it with unauthorized people.','ಈ ದಾಖಲೆಯನ್ನು ಬಾಡಿಗೆ ಪರಿಶೀಲನೆಗಾಗಿ ಮಾತ್ರ ಬಳಸಿ. ದುರುಪಯೋಗಪಡಿಸಿಕೊಳ್ಳಬೇಡಿ ಅಥವಾ ಅನಧಿಕೃತ ವ್ಯಕ್ತಿಗಳೊಂದಿಗೆ ಹಂಚಿಕೊಳ್ಳಬೇಡಿ.','इस दस्तावेज़ का उपयोग केवल किराया सत्यापन के लिए करें। इसका दुरुपयोग न करें या अनधिकृत लोगों से साझा न करें।',$current_lang)); ?></div>
  <section class="cardx">
    <?php if ($proof_available): ?>
      <iframe title="Renter ID proof" src="<?php echo e($document_url . '&raw=1'); ?>" style="width:100%;height:75vh;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc"></iframe>
      <div class="mt-3"><a class="btn-main" href="<?php echo e($document_url . '&download=1'); ?>"><i class="fa-solid fa-download"></i> <?php echo e(tr_local('Download ID Proof','ಗುರುತಿನ ದಾಖಲೆ ಡೌನ್‌ಲೋಡ್ ಮಾಡಿ','पहचान प्रमाण डाउनलोड करें',$current_lang)); ?></a></div>
    <?php else: ?>
      <p><?php echo e(tr_local('No ID proof file is recorded for this booking.','ಈ ಬುಕಿಂಗ್‌ಗೆ ಗುರುತಿನ ದಾಖಲೆ ಫೈಲ್ ದಾಖಲಾಗಿಲ್ಲ.','इस बुकिंग के लिए कोई पहचान प्रमाण फ़ाइल दर्ज नहीं है।',$current_lang)); ?></p>
    <?php endif; ?>
  </section>
</div>
<?php else: ?>
<div class="wrap">
  <div class="top">
    <div>
      <h1><i class="fa-solid fa-id-card"></i> <?php echo e(tr_local('Renter Details','ಬಾಡಿಗೆದಾರರ ವಿವರಗಳು','किराएदार का विवरण',$current_lang)); ?></h1>
      <div class="text-secondary"><?php echo e(tr_local('Booking','ಬುಕಿಂಗ್','बुकिंग',$current_lang)); ?>: <?php echo e($booking['request_code'] ?: ('#'.$booking_id)); ?> · <?php echo e($booking['equipment_title']); ?></div>
    </div>
    <a class="btn-main" href="<?php echo e($back_url); ?>"><i class="fa-solid fa-arrow-left"></i> <?php echo e(tr_local('Back to Booking Details','ಬುಕಿಂಗ್ ವಿವರಗಳಿಗೆ ಹಿಂತಿರುಗಿ','बुकिंग विवरण पर वापस जाएँ',$current_lang)); ?></a>
  </div>

  <div class="warning"><strong><i class="fa-solid fa-shield-halved"></i> <?php echo e(tr_local('Privacy and responsible use','ಗೌಪ್ಯತೆ ಮತ್ತು ಜವಾಬ್ದಾರಿಯುತ ಬಳಕೆ','गोपनीयता और जिम्मेदार उपयोग',$current_lang)); ?></strong><br>
  <?php echo e(tr_local(
    'This ID proof, ID number, phone number and address are confidential. Use them only for legitimate rental verification and booking-related communication. Do not misuse, copy, publish, or share them with unauthorized people. Keep this information secure and close the page when you have finished.',
    'ಈ ಗುರುತಿನ ದಾಖಲೆ, ಐಡಿ ಸಂಖ್ಯೆ, ಫೋನ್ ಸಂಖ್ಯೆ ಮತ್ತು ವಿಳಾಸ ಗೌಪ್ಯವಾಗಿವೆ. ಬಾಡಿಗೆ ಪರಿಶೀಲನೆ ಮತ್ತು ಬುಕಿಂಗ್‌ಗೆ ಸಂಬಂಧಿಸಿದ ಸಂಪರ್ಕಕ್ಕಾಗಿ ಮಾತ್ರ ಬಳಸಿ. ದುರುಪಯೋಗಪಡಿಸಿಕೊಳ್ಳಬೇಡಿ, ನಕಲಿಸಬೇಡಿ, ಪ್ರಕಟಿಸಬೇಡಿ ಅಥವಾ ಅನುಮತಿಯಿಲ್ಲದವರೊಂದಿಗೆ ಹಂಚಿಕೊಳ್ಳಬೇಡಿ.',
    'यह पहचान प्रमाण, आईडी नंबर, फोन नंबर और पता गोपनीय हैं। इनका उपयोग केवल वैध किराया सत्यापन और बुकिंग से संबंधित संपर्क के लिए करें। इनका दुरुपयोग, कॉपी, प्रकाशन या अनधिकृत लोगों के साथ साझा न करें।',
    $current_lang
  )); ?></div>

  <section class="cardx">
    <h2 class="h5 fw-bold mb-3"><?php echo e(tr_local('Renter Information','ಬಾಡಿಗೆದಾರರ ಮಾಹಿತಿ','किराएदार की जानकारी',$current_lang)); ?></h2>
    <div class="grid">
      <div class="field"><div class="label"><?php echo e(tr_local('Full Name','ಪೂರ್ಣ ಹೆಸರು','पूरा नाम',$current_lang)); ?></div><div class="value"><?php echo e($booking['full_name'] ?: '—'); ?></div></div>
      <div class="field"><div class="label"><?php echo e(tr_local('Mobile Number','ಮೊಬೈಲ್ ಸಂಖ್ಯೆ','मोबाइल नंबर',$current_lang)); ?></div><div class="value"><?php echo e($phone ?: '—'); ?></div></div>
      <div class="field"><div class="label"><?php echo e(tr_local('Email','ಇಮೇಲ್','ईमेल',$current_lang)); ?></div><div class="value"><?php echo e($booking['email'] ?: '—'); ?></div></div>
      <div class="field"><div class="label"><?php echo e(tr_local('ID Proof Number','ಗುರುತಿನ ದಾಖಲೆ ಸಂಖ್ಯೆ','पहचान प्रमाण संख्या',$current_lang)); ?></div><div class="value"><?php echo e($booking['id_number'] ?: '—'); ?></div></div>
      <div class="field" style="grid-column:1/-1"><div class="label"><?php echo e(tr_local('Address','ವಿಳಾಸ','पता',$current_lang)); ?></div><div class="value"><?php echo nl2br(e($address)); ?></div></div>
    </div>
  </section>

  <section class="cardx">
    <h2 class="h5 fw-bold mb-2"><i class="fa-solid fa-file-shield"></i> <?php echo e(tr_local('Uploaded ID Proof','ಅಪ್‌ಲೋಡ್ ಮಾಡಿದ ಗುರುತಿನ ದಾಖಲೆ','अपलोड किया गया पहचान प्रमाण',$current_lang)); ?></h2>
    <?php if ($proof_available): ?>
      <p class="text-secondary"><?php echo e(tr_local('Open the file securely. Only the lender assigned to this booking can access it.','ಫೈಲ್ ಅನ್ನು ಸುರಕ್ಷಿತವಾಗಿ ತೆರೆಯಿರಿ. ಈ ಬುಕಿಂಗ್‌ಗೆ ನಿಯೋಜಿತ ಸಾಲದಾತರಿಗೆ ಮಾತ್ರ ಪ್ರವೇಶವಿದೆ.','फ़ाइल सुरक्षित रूप से खोलें। केवल इस बुकिंग का संबंधित ऋणदाता इसे देख सकता है।',$current_lang)); ?></p>
      <a class="btn-main" href="<?php echo e($preview_url); ?>"><i class="fa-solid fa-eye"></i> <?php echo e(tr_local('View ID Proof','ಗುರುತಿನ ದಾಖಲೆ ವೀಕ್ಷಿಸಿ','पहचान प्रमाण देखें',$current_lang)); ?></a>
      <a class="btn-main ms-1" href="<?php echo e($document_url . '&download=1'); ?>"><i class="fa-solid fa-download"></i> <?php echo e(tr_local('Download ID Proof','ಗುರುತಿನ ದಾಖಲೆ ಡೌನ್‌ಲೋಡ್ ಮಾಡಿ','पहचान प्रमाण डाउनलोड करें',$current_lang)); ?></a>
    <?php else: ?>
      <p class="text-secondary mb-0"><?php echo e(tr_local('No ID proof file is recorded for this booking.','ಈ ಬುಕಿಂಗ್‌ಗೆ ಗುರುತಿನ ದಾಖಲೆ ಫೈಲ್ ದಾಖಲಾಗಿಲ್ಲ.','इस बुकिंग के लिए कोई पहचान प्रमाण फ़ाइल दर्ज नहीं है।',$current_lang)); ?></p>
    <?php endif; ?>
  </section>

  <div class="mb-4"><a class="btn-main" href="<?php echo e($back_url); ?>"><i class="fa-solid fa-arrow-left"></i> <?php echo e(tr_local('Back to Booking Details','ಬುಕಿಂಗ್ ವಿವರಗಳಿಗೆ ಹಿಂತಿರುಗಿ','बुकिंग विवरण पर वापस जाएँ',$current_lang)); ?></a></div>
</div>
<?php endif; ?>
</body>
</html>
