<?php
session_start();
require_once 'includes/config.php';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','hi','kn'], true)) $_SESSION['lang']=$_GET['lang'];
require_once 'includes/lang.php';
if (!isset($_SESSION['user_id'])) {
     header('Location: login.php'); exit; 
     }
$renter_id=(int)$_SESSION['user_id'];
$current_lang=$_SESSION['lang'] ?? 'en';
$booking_id=(int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$lang_param='&lang='.urlencode($current_lang);
if ($booking_id<=0) { header('Location: my_bookings.php?lang='.urlencode($current_lang)); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$errors=[]; $success='';
$stmt=$conn->prepare("SELECT b.booking_id,b.request_code,b.status,b.equipment_id,e.title AS equipment_title,e.image AS equipment_image,u.full_name AS lender_name FROM bookings b JOIN equipment e ON b.equipment_id=e.equipment_id JOIN users u ON e.lender_id=u.user_id WHERE b.booking_id=? AND b.renter_id=? LIMIT 1");
$stmt->bind_param('ii',$booking_id,$renter_id); $stmt->execute(); $booking=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$booking || $booking['status']!=='Completed') { header('Location: booking_details.php?booking_id='.$booking_id.$lang_param); exit; }
$dup=$conn->prepare("SELECT review_id FROM reviews WHERE booking_id=? AND renter_id=? LIMIT 1"); $dup->bind_param('ii',$booking_id,$renter_id); $dup->execute(); $existing=$dup->get_result()->fetch_assoc(); $dup->close();
if ($existing) { header('Location: booking_details.php?booking_id='.$booking_id.$lang_param); exit; }
$rating=(int)($_POST['rating'] ?? 0); $review_text=trim($_POST['review_text'] ?? '');
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) $errors[]='Invalid request. Please try again.';
    if ($rating<1 || $rating>5) $errors[]='Please select a rating from 1 to 5 stars.';
    if (mb_strlen($review_text)<3 || mb_strlen($review_text)>1000) $errors[]='Review must be between 3 and 1000 characters.';
    if (!$errors) {
        $conn->begin_transaction();
        try {
            $lock=$conn->prepare("SELECT b.equipment_id,e.title,e.lender_id FROM bookings b JOIN equipment e ON b.equipment_id=e.equipment_id WHERE b.booking_id=? AND b.renter_id=? AND b.status='Completed' LIMIT 1");
            $lock->bind_param('ii',$booking_id,$renter_id); $lock->execute(); $fresh=$lock->get_result()->fetch_assoc(); $lock->close();
            if (!$fresh) throw new Exception('This booking is no longer eligible for review.');
            $check=$conn->prepare("SELECT review_id FROM reviews WHERE booking_id=? LIMIT 1"); $check->bind_param('i',$booking_id); $check->execute(); $already=$check->get_result()->fetch_assoc(); $check->close();
            if ($already) throw new Exception('A review has already been submitted for this booking.');
            $ins=$conn->prepare("INSERT INTO reviews (booking_id,equipment_id,renter_id,lender_id,rating,review_text) VALUES (?,?,?,?,?,?)");
            $ins->bind_param('iiiiis',$booking_id,$fresh['equipment_id'],$renter_id,$fresh['lender_id'],$rating,$review_text); if (!$ins->execute()) throw new Exception('Unable to save the review.'); $ins->close();
            $agg=$conn->prepare("SELECT COALESCE(AVG(rating),0),COUNT(*) FROM reviews WHERE equipment_id=?"); $agg->bind_param('i',$fresh['equipment_id']); $agg->execute(); $a=$agg->get_result()->fetch_row(); $agg->close();
            $upd=$conn->prepare("UPDATE equipment SET rating=?, rating_count=? WHERE equipment_id=?"); $avg=(float)$a[0]; $cnt=(int)$a[1]; $upd->bind_param('dii',$avg,$cnt,$fresh['equipment_id']); if (!$upd->execute()) throw new Exception('Unable to update equipment rating.'); $upd->close();
            $title='New Customer Review'; $message='A renter has submitted a new '.$rating.'-star review for your equipment "'.$fresh['title'].'".';
            $n=$conn->prepare("INSERT INTO notifications (user_id,title,message,is_read,created_at) VALUES (?,?,?,0,NOW())");
            if ($n) { $n->bind_param('iss',$fresh['lender_id'],$title,$message); $n->execute(); $n->close(); }
            $conn->commit(); header('Location: booking_details.php?booking_id='.$booking_id.$lang_param); exit;
        } catch (Throwable $e) { $conn->rollback(); $errors[]=$e->getMessage(); }
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">
    <head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo __('submit_review'); ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body{background:#f4f6f9;
    font-family:'Segoe UI',sans-serif}.review-box{max-width:620px;
    margin:60px auto;
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    padding:30px;box-shadow:0 4px 15px rgba(0,0,0,.05)}.stars{display:flex;gap:8px;
    font-size:32px;
    flex-direction:row-reverse;
    justify-content:flex-end}
    .stars input{display:none}
    .stars label{color:#cbd5e1;cursor:pointer}
    .stars label:hover,.stars label:hover~label,
    .stars input:checked~label{color:#f59e0b}
    .btn-submit{background:#198754;
    border:0;color:#fff;font-weight:800}
    .btn-submit:hover{background:#157347;color:#fff}
    </style>
    </head>
    <body>
        <div class="review-box">
            <a class="text-decoration-none" href="booking_details.php?booking_id=<?php echo $booking_id.$lang_param; ?>">← <?php echo __('back_btn'); ?></a><h2 class="fw-bold mt-3 mb-1"><?php echo __('submit_review'); ?></h2><p class="text-muted mb-4"><?php echo htmlspecialchars($booking['equipment_title']); ?> · <?php echo htmlspecialchars($booking['lender_name']); ?></p><?php if($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?><form method="post"><input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"><label class="fw-bold d-block mb-2"><?php echo __('give_rating'); ?></label><div class="stars mb-4"><?php for($i=5;$i>=1;$i--): ?><input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $rating===$i?'checked':''; ?>><label for="star<?php echo $i; ?>">★</label><?php endfor; ?></div><label class="fw-bold mb-2"><?php echo __('write_review'); ?></label><textarea name="review_text" class="form-control" rows="6" maxlength="1000" placeholder="<?php echo __('review_placeholder'); ?>" required><?php echo htmlspecialchars($review_text); ?></textarea><div class="d-flex gap-2 justify-content-end mt-4"><a class="btn btn-light border fw-bold" href="booking_details.php?booking_id=<?php echo $booking_id.$lang_param; ?>"><?php echo __('back_btn'); ?></a><button class="btn btn-submit px-4" type="submit"><?php echo __('submit_review'); ?></button></div></form></div></body></html>