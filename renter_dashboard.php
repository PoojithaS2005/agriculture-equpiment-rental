<?php
session_start();
require_once 'includes/lang.php';
if (file_exists('includes/config.php')) { include('includes/config.php'); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') { header("Location: login.php"); exit(); }
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$user_query = "SELECT full_name, email, COALESCE(address, 'Not Specified') AS address, COALESCE(city, '') AS city, COALESCE(district, '') AS district, COALESCE(state, '') AS state FROM users WHERE user_id = '$user_id'";
$user_res = mysqli_query($conn, $user_query);
$user_info = mysqli_fetch_assoc($user_res);
function dashboard_category_label($category_name) {
    $key = strtolower(trim($category_name));
    $category_keys = ['tractor' => 'tractor','harvesting' => 'harvesting','irrigation' => 'irrigation','tillage' => 'tillage','seeding' => 'seeding','spraying' => 'spraying'];
    if (isset($category_keys[$key])) { $translated = __($category_keys[$key]); return ($translated !== $category_keys[$key]) ? $translated : $category_name; }
    return $category_name;
}
function dashboard_status_label($status) {
    $key = strtolower(trim($status));
    $status_keys = ['pending' => 'pending_approval','accepted' => 'accepted','delivered' => 'delivered','returned' => 'returned','completed' => 'completed','rejected' => 'rejected','overdue' => 'overdue'];
    if (isset($status_keys[$key])) { $translated = __($status_keys[$key]); return ($translated !== $status_keys[$key]) ? $translated : ucfirst($status); }
    return ucfirst($status);
}
$notif_count_sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
$notif_count_res = mysqli_query($conn, $notif_count_sql);
$unread_count = ($notif_count_res) ? mysqli_fetch_assoc($notif_count_res)['total'] : 0;
$notif_list_sql = "SELECT notification_id, title, message, is_read, created_at FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 5";
$notif_list_res = mysqli_query($conn, $notif_list_sql);
$active_sql = "SELECT COUNT(*) AS total FROM bookings WHERE renter_id = '$user_id' AND status IN ('confirmed', 'active')";
$active_res = mysqli_query($conn, $active_sql);
$active_count = ($active_res) ? mysqli_fetch_assoc($active_res)['total'] : 0;
$upcoming_sql = "SELECT COUNT(*) AS total FROM bookings WHERE renter_id = '$user_id' AND status = 'pending'";
$upcoming_res = mysqli_query($conn, $upcoming_sql);
$upcoming_count = ($upcoming_res) ? mysqli_fetch_assoc($upcoming_res)['total'] : 0;
$completed_sql = "SELECT COUNT(*) AS total FROM bookings WHERE renter_id = '$user_id' AND status = 'completed'";
$completed_res = mysqli_query($conn, $completed_sql);
$completed_count = ($completed_res) ? mysqli_fetch_assoc($completed_res)['total'] : 0;
$spent_sql = "SELECT SUM(total_amount) AS total FROM bookings WHERE renter_id = '$user_id' AND status = 'completed'";
$spent_res = mysqli_query($conn, $spent_sql);
$spent_val = mysqli_fetch_assoc($spent_res)['total'];
$total_spent = ($spent_val) ? number_format($spent_val, 0) : '0';
$cat_query = "SELECT category_id, category_name, icon_class FROM categories LIMIT 6";
$cat_res = mysqli_query($conn, $cat_query);
$featured_query = "SELECT * FROM equipment WHERE is_featured = 1 LIMIT 3";
$featured_res = mysqli_query($conn, $featured_query);
$recent_query = "SELECT b.*, e.title AS equipment_name FROM bookings b JOIN equipment e ON b.equipment_id = e.equipment_id WHERE b.renter_id = '$user_id' ORDER BY b.booking_id DESC LIMIT 5";
$recent_res = mysqli_query($conn, $recent_query);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __('renter_dashboard'); ?> - <?= __('title'); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root{--brand:#2d6a4f;--glass:rgba(255,255,255,0.72);--border:rgba(255,255,255,0.9);--shadow:0 10px 30px rgba(39,93,67,.08);}
body{
    font-family:'Segoe UI',Tahoma,Verdana,sans-serif;
    background: 
        radial-gradient(circle at 20% 25%, rgba(255,255,255,0.95) 0%, transparent 22%),
        radial-gradient(circle at 75% 35%, rgba(255,255,255,0.85) 0%, transparent 18%),
        radial-gradient(circle at 50% 65%, rgba(232,245,233,0.9) 0%, transparent 28%),
        radial-gradient(circle at 88% 78%, rgba(255,255,255,0.7) 0%, transparent 10%),
        linear-gradient(135deg,#eef6e8 0%, #f5f9ec 30%, #fdf8e7 65%, #f1f7e9 100%) !important;
    background-attachment:fixed !important;
}
.main-wrapper{margin-left:250px;padding:18px 28px;}
.top-navbar{background:rgba(255,255,255,0.78) !important;border:1px solid var(--border) !important;border-radius:18px !important;backdrop-filter:blur(20px);box-shadow:0 4px 20px rgba(0,0,0,0.04) !important;padding:12px 18px !important;}
.top-navbar .form-control{background:rgba(255,255,255,0.9) !important;border:1px solid rgba(0,0,0,0.06) !important;height:42px;border-radius:50rem;}
.lang-select{border:none;background:transparent;font-weight:600;font-size:.85rem;color:#2d6a4f;cursor:pointer;}
.welcome-banner{
    min-height:180px;display:flex;align-items:center;padding:28px 32px;border-radius:20px !important;
    border:1px solid rgba(255,255,255,0.9) !important;box-shadow:0 8px 24px rgba(0,0,0,0.05) !important;
    background:linear-gradient(90deg, rgba(255,253,240,0.96) 0%, rgba(255,253,240,0.90) 38%, rgba(255,255,255,0.35) 62%, rgba(255,255,255,0.05) 100%), url('images/tractor3.jpg') !important;
    background-size:cover !important;background-position:right center !important;
}
.welcome-banner h3{color:#163a26 !important;font-weight:800 !important;font-size:1.75rem !important;}
.welcome-banner p{color:#2d5a3a !important;font-weight:500 !important;font-size:0.95rem !important;}
.stat-card{background:rgba(255,255,255,0.80) !important;border:1px solid var(--border) !important;border-radius:16px !important;backdrop-filter:blur(12px);box-shadow:0 4px 16px rgba(0,0,0,0.04) !important;padding:16px !important;transition:0.25s;}
.stat-card:hover{transform:translateY(-3px);background:rgba(255,255,255,0.92) !important;box-shadow:0 8px 24px rgba(0,0,0,0.08) !important;}
.stat-icon-wrapper{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;}
.cat-box{background:rgba(255,255,255,0.78) !important;border:1px solid var(--border) !important;border-radius:16px !important;backdrop-filter:blur(12px);box-shadow:0 4px 16px rgba(0,0,0,0.03) !important;padding:18px 10px !important;transition:0.25s;}
.cat-box:hover{transform:translateY(-3px);background:rgba(255,255,255,0.92) !important;}
.main-wrapper>.bg-white{background:rgba(255,255,255,0.78) !important;border:1px solid var(--border) !important;border-radius:16px !important;backdrop-filter:blur(12px);box-shadow:0 4px 16px rgba(0,0,0,0.03) !important;}
.main-wrapper h6{font-weight:700;color:#1e3a2a;}
.main-wrapper .table thead th{background:#eef5ee;color:#2d6a4f;font-weight:700;}
@media (min-width:769px){.main-wrapper{margin-left:250px;}}
@media (max-width:768px){.main-wrapper{margin-left:0;padding:12px;}.welcome-banner{background:rgba(255,253,240,0.95) !important;min-height:140px;}}
</style>
</head>
<body>
<?php include 'renter_sidebar.php'; ?>
<div class="main-wrapper">
<div class="text-center mb-2"><span class="badge px-4 py-2 rounded-pill fw-bold" style="background:#2e6b4f;letter-spacing:0.5px;font-size:0.7rem;"><?= strtoupper(__('renter_dashboard')); ?></span></div>
<div class="top-navbar d-flex align-items-center justify-content-between mb-3">
<div class="d-flex align-items-center gap-3"><button class="btn btn-light border-0 d-md-none"><i class="fa-solid fa-bars"></i></button><div class="d-flex align-items-center gap-2"><i class="fa-solid fa-tractor text-success fa-xl"></i><div><strong style="color:#2d6a4f;display:block;line-height:1;font-size:.85rem;">AGRICULTURE</strong><span style="font-size:.55rem;color:#64748b;font-weight:700;">EQUIPMENT RENTAL SYSTEM</span></div></div></div>
<form action="search_equipment.php" method="GET" id="searchForm" class="flex-grow-1 mx-3" style="max-width:430px;"><div class="position-relative d-flex align-items-center"><i class="fa-solid fa-magnifying-glass position-absolute start-0 ms-3 text-muted"></i><input type="text" name="q" id="searchInput" class="form-control ps-5 pe-5" placeholder="<?= __('search_placeholder'); ?>"><button type="button" id="micBtn" onclick="startVoiceSearch()" class="btn btn-success rounded-circle position-absolute end-0 me-1 d-flex align-items-center justify-content-center" style="width:30px;height:30px;border:none;"><i class="fa-solid fa-microphone text-white" style="font-size:0.8rem;" id="micIcon"></i></button></div></form>
<div class="d-flex align-items-center gap-2"><div class="small text-secondary fw-semibold d-none d-lg-flex align-items-center gap-1"><i class="fa-solid fa-location-dot text-danger"></i><span style="font-size:0.8rem;"><?= htmlspecialchars($user_info['address'] ?? 'Devanahalli, Bengaluru Rural'); ?></span></div><div class="d-flex align-items-center gap-1 border-start ps-2 ms-1"><i class="fa-solid fa-globe text-secondary" style="font-size:0.8rem;"></i><select class="lang-select" onchange="window.location.href='renter_dashboard.php?lang='+this.value;"><option value="en" <?= ($current_lang==='en')?'selected':''; ?>>English</option><option value="kn" <?= ($current_lang==='kn')?'selected':''; ?>>ಕನ್ನಡ</option><option value="hi" <?= ($current_lang==='hi')?'selected':''; ?>>हिंदी</option></select></div><div class="border-start ps-2"><a href="notifications.php?lang=<?= urlencode($current_lang); ?>" class="btn btn-light border-0 position-relative p-1 rounded-circle"><i class="fa-regular fa-bell text-secondary"></i><?php if($unread_count>0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger" style="font-size:.6rem;"><?= $unread_count; ?></span><?php endif; ?></a></div><a href="profile.php" class="d-flex align-items-center gap-2 border-start ps-2 text-decoration-none text-dark"><div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;font-size:0.8rem;"><?= strtoupper(substr($user_info['full_name']??'P',0,1)); ?></div><div class="d-none d-sm-block"><div class="fw-bold" style="font-size:0.8rem;line-height:1;"><?= htmlspecialchars($user_info['full_name']??'Pragathi'); ?></div><span class="text-muted" style="font-size:0.65rem;"><?= htmlspecialchars(__('renter_role')); ?></span></div></a></div>
</div>
<div class="welcome-banner mb-3"><div><h3 class="fw-bold mb-1"><?= __('welcome_back'); ?>, <?= htmlspecialchars($user_info['full_name']??'User'); ?> 🌿</h3><p class="mb-0 small"><?= __('find_and_rent'); ?></p></div></div>
<div class="row g-2 mb-3">
<div class="col-md-3 col-6"><div class="stat-card d-flex align-items-center gap-2"><div class="stat-icon-wrapper bg-success-subtle text-success"><i class="fa-regular fa-calendar-check"></i></div><div><div class="text-muted" style="font-size:.7rem;"><?= __('active_bookings'); ?></div><h4 class="fw-bold mb-0"><?= $active_count; ?></h4></div></div></div>
<div class="col-md-3 col-6"><div class="stat-card d-flex align-items-center gap-2"><div class="stat-icon-wrapper bg-primary-subtle text-primary"><i class="fa-regular fa-clock"></i></div><div><div class="text-muted" style="font-size:.7rem;"><?= __('upcoming_bookings'); ?></div><h4 class="fw-bold mb-0"><?= $upcoming_count; ?></h4></div></div></div>
<div class="col-md-3 col-6"><div class="stat-card d-flex align-items-center gap-2"><div class="stat-icon-wrapper bg-warning-subtle text-warning"><i class="fa-solid fa-rotate"></i></div><div><div class="text-muted" style="font-size:.7rem;"><?= __('completed_rentals'); ?></div><h4 class="fw-bold mb-0"><?= $completed_count; ?></h4></div></div></div>
<div class="col-md-3 col-6"><div class="stat-card d-flex align-items-center gap-2"><div class="stat-icon-wrapper bg-info-subtle text-info"><i class="fa-solid fa-indian-rupee-sign"></i></div><div><div class="text-muted" style="font-size:.7rem;"><?= __('total_spent'); ?></div><h4 class="fw-bold mb-0">₹ <?= $total_spent; ?></h4></div></div></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0" style="font-size:0.95rem;"><?= __('popular_categories'); ?></h6><a href="categories.php" class="text-success small text-decoration-none fw-semibold" style="font-size:0.8rem;"><?= __('view_all'); ?> →</a></div>
<div class="row g-2 mb-3">
<?php if($cat_res && mysqli_num_rows($cat_res)>0): while($cat=mysqli_fetch_assoc($cat_res)): $cat_name=dashboard_category_label($cat['category_name']); $q_equip="SELECT COUNT(*) AS total FROM equipment WHERE category_id=".(int)$cat['category_id']; $res_equip=mysqli_query($conn,$q_equip); $row_equip=mysqli_fetch_assoc($res_equip); $total_equipment_count=$row_equip['total']??0; ?>
<div class="col-md-2 col-4"><a href="category_items.php?category_id=<?= (int)$cat['category_id']; ?>" class="text-decoration-none text-dark d-block h-100"><div class="bg-white border rounded p-3 text-center h-100 cat-box"><i class="<?= $cat['icon_class']?:'fa-solid fa-gears'; ?> text-success mb-2"></i><div class="fw-bold" style="font-size:0.8rem;"><?= htmlspecialchars($cat_name); ?></div><span class="text-muted" style="font-size:0.7rem;">(<?= (int)$total_equipment_count; ?>)</span></div></a></div>
<?php endwhile; else: ?><div class="col-12"><div class="text-muted small p-2 bg-white rounded border text-center"><?= htmlspecialchars(__('no_categories')); ?></div></div><?php endif; ?>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0" style="font-size:0.95rem;"><?= __('recent_bookings'); ?></h6><a href="my_bookings.php" class="text-success small text-decoration-none fw-semibold" style="font-size:0.8rem;"><?= __('view_all'); ?> →</a></div>
<div class="bg-white rounded border p-3">
<?php if($recent_res && mysqli_num_rows($recent_res)>0): ?>
<table class="table align-middle small mb-0"><thead class="table-light"><tr><th><?= htmlspecialchars(__('table_col_equipment')); ?></th><th><?= htmlspecialchars(__('booking_id')); ?></th><th><?= htmlspecialchars(__('from_to')); ?></th><th><?= htmlspecialchars(__('table_col_status')); ?></th><th><?= htmlspecialchars(__('amount')); ?></th></tr></thead><tbody>
<?php while($row=mysqli_fetch_assoc($recent_res)): ?>
<tr><td><strong><?= htmlspecialchars($row['equipment_name']); ?></strong></td><td>BK<?= $row['booking_id']; ?></td><td><?= $row['start_date']; ?> - <?= $row['end_date']; ?></td><td><span class="badge bg-<?= ($row['status']=='confirmed')?'success':(($row['status']=='pending')?'warning':'secondary'); ?>"><?= htmlspecialchars(dashboard_status_label($row['status'])); ?></span></td><td>₹ <?= number_format($row['total_amount']); ?></td></tr>
<?php endwhile; ?></tbody></table>
<?php else: ?>
<div class="text-center py-3"><div class="d-inline-flex align-items-center gap-2 text-muted small"><i class="fa-regular fa-calendar-check"></i> No recent bookings yet. Your recent bookings will appear here.</div></div>
<?php endif; ?>
</div>
</div>
<script>
function startVoiceSearch(){
const micBtn=document.getElementById('micBtn');const micIcon=document.getElementById('micIcon');const searchInput=document.getElementById('searchInput');const searchForm=document.getElementById('searchForm');
const SpeechRecognition=window.SpeechRecognition||window.webkitSpeechRecognition;
if(!SpeechRecognition){alert("Voice search is not supported on this browser. Please use Google Chrome or Microsoft Edge.");return;}
const recognition=new SpeechRecognition();const currentLang='<?= $current_lang; ?>';
if(currentLang==='kn'){recognition.lang='kn-IN';}else if(currentLang==='hi'){recognition.lang='hi-IN';}else{recognition.lang='en-US';}
recognition.interimResults=false;recognition.maxAlternatives=1;
micBtn.classList.remove('btn-success');micBtn.classList.add('btn-danger');micIcon.className='fa-solid fa-spinner fa-spin text-white';searchInput.placeholder="Listening... Speak now...";recognition.start();
recognition.onresult=function(event){const transcript=event.results[0][0].transcript;searchInput.value=transcript;resetMicUI();setTimeout(()=>{searchForm.submit();},500);};
recognition.onerror=function(event){console.error("Speech Recognition Error: "+event.error);alert("Could not recognize voice. Please try again.");resetMicUI();};
recognition.onend=function(){resetMicUI();};
function resetMicUI(){micBtn.classList.remove('btn-danger');micBtn.classList.add('btn-success');micIcon.className='fa-solid fa-microphone text-white';searchInput.placeholder="<?= __('search_placeholder'); ?>";}
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>