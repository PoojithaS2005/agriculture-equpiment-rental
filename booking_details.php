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
| Handle Booking Cancellation
|--------------------------------------------------------------------------
*/
if (isset($_POST['cancel_booking'])) {
    // Cancellation is allowed only before delivery: Pending or Accepted.
    // Once the lender marks the booking as Delivered, cancellation is not allowed.
    $equipment_title_for_notification = 'Equipment';
    $lender_id_for_notification = 0;

    // Get the appropriate lender and equipment before changing the status.
    $cancel_info_stmt = $conn->prepare("
        SELECT e.lender_id, e.title
        FROM bookings b
        INNER JOIN equipment e ON b.equipment_id = e.equipment_id
        WHERE b.booking_id = ?
          AND b.renter_id = ?
          AND b.status IN ('Pending', 'Accepted')
        LIMIT 1
    ");

    if ($cancel_info_stmt) {
        $cancel_info_stmt->bind_param("ii", $booking_id, $renter_id);
        $cancel_info_stmt->execute();
        $cancel_info = $cancel_info_stmt->get_result()->fetch_assoc();
        if ($cancel_info) {
            $lender_id_for_notification = (int)$cancel_info['lender_id'];
            $equipment_title_for_notification = $cancel_info['title'] ?: 'Equipment';
        }
        $cancel_info_stmt->close();
    }

    // Update only if the booking is still Pending or Accepted.
    $cancel_stmt = $conn->prepare("
        UPDATE bookings
        SET status = 'Cancelled'
        WHERE booking_id = ?
          AND renter_id = ?
          AND status IN ('Pending', 'Accepted')
    ");

    $cancelled_successfully = false;
    if ($cancel_stmt) {
        $cancel_stmt->bind_param("ii", $booking_id, $renter_id);
        $cancel_stmt->execute();
        $cancelled_successfully = ($cancel_stmt->affected_rows > 0);
        $cancel_stmt->close();
    }

    // Notify the appropriate lender only after a successful cancellation.
    if ($cancelled_successfully && $lender_id_for_notification > 0) {
        $notification_title = 'Booking Cancelled by Renter';
        $notification_message = 'The renter has cancelled the booking for ' . $equipment_title_for_notification . '.';

        $notification_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, is_read)
            VALUES (?, ?, ?, 0)
        ");

        if ($notification_stmt) {
            $notification_stmt->bind_param(
                "iss",
                $lender_id_for_notification,
                $notification_title,
                $notification_message
            );
            $notification_stmt->execute();
            $notification_stmt->close();
        }
    }

    header("Location: booking_details.php?booking_id=" . $booking_id . "&lang=" . urlencode($current_lang) . "&cancelled=1");
    exit();
}

/*
|--------------------------------------------------------------------------
| Handle Renter Delivery Confirmation
|--------------------------------------------------------------------------
*/
if (isset($_POST['confirm_delivery'])) {
    $confirm_stmt = $conn->prepare("
        UPDATE bookings
        SET delivery_confirmed = 1,
            delivery_confirmed_at = NOW()
        WHERE booking_id = ?
          AND renter_id = ?
          AND status = 'Delivered'
          AND delivery_confirmed = 0
    ");

    $confirmed_successfully = false;

    if ($confirm_stmt) {
        $confirm_stmt->bind_param("ii", $booking_id, $renter_id);
        $confirm_stmt->execute();
        $confirmed_successfully = ($confirm_stmt->affected_rows > 0);
        $confirm_stmt->close();
    }

    if ($confirmed_successfully) {
        // Mark the matching delivery notification as read after confirmation.
        $notification_check = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($notification_check && $notification_check->num_rows > 0) {
            $read_stmt = $conn->prepare("
                SELECT request_code
                FROM bookings
                WHERE booking_id = ? AND renter_id = ?
                LIMIT 1
            ");
            $request_code_for_read = '';

            if ($read_stmt) {
                $read_stmt->bind_param("ii", $booking_id, $renter_id);
                $read_stmt->execute();
                $read_data = $read_stmt->get_result()->fetch_assoc();
                $request_code_for_read = $read_data['request_code'] ?? '';
                $read_stmt->close();
            }

            if ($request_code_for_read !== '') {
                $mark_read_stmt = $conn->prepare("
                    UPDATE notifications
                    SET is_read = 1
                    WHERE user_id = ?
                      AND title = 'Equipment Delivered'
                      AND message LIKE CONCAT('%', ?, '%')
                      AND is_read = 0
                ");
                if ($mark_read_stmt) {
                    $mark_read_stmt->bind_param("is", $renter_id, $request_code_for_read);
                    $mark_read_stmt->execute();
                    $mark_read_stmt->close();
                }
            }
        }

        header("Location: booking_details.php?booking_id=" . $booking_id . "&lang=" . urlencode($current_lang) . "&delivery_confirmed=1");
        exit();
    }

    header("Location: booking_details.php?booking_id=" . $booking_id . "&lang=" . urlencode($current_lang) . "&delivery_confirmed=0");
    exit();
}

/*
|--------------------------------------------------------------------------
| Handle Renter Return Confirmation
|--------------------------------------------------------------------------
*/
if (isset($_POST['confirm_return'])) {
    $return_confirm_stmt = $conn->prepare("
        UPDATE bookings
        SET return_confirmed = 1,
            return_confirmed_at = NOW(),
            status = 'Completed'
        WHERE booking_id = ?
          AND renter_id = ?
          AND status = 'Returned'
          AND return_confirmed = 0
    ");

    $return_confirmed_successfully = false;

    if ($return_confirm_stmt) {
        $return_confirm_stmt->bind_param("ii", $booking_id, $renter_id);
        $return_confirm_stmt->execute();
        $return_confirmed_successfully = ($return_confirm_stmt->affected_rows > 0);
        $return_confirm_stmt->close();
    }

    if ($return_confirmed_successfully) {
        // Mark the matching return notification as read after confirmation.
        $notification_check = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($notification_check && $notification_check->num_rows > 0) {
            $read_stmt = $conn->prepare("
                SELECT request_code
                FROM bookings
                WHERE booking_id = ? AND renter_id = ?
                LIMIT 1
            ");
            $request_code_for_read = '';

            if ($read_stmt) {
                $read_stmt->bind_param("ii", $booking_id, $renter_id);
                $read_stmt->execute();
                $read_data = $read_stmt->get_result()->fetch_assoc();
                $request_code_for_read = $read_data['request_code'] ?? '';
                $read_stmt->close();
            }

            if ($request_code_for_read !== '') {
                $mark_read_stmt = $conn->prepare("
                    UPDATE notifications
                    SET is_read = 1
                    WHERE user_id = ?
                      AND title = 'Equipment Returned'
                      AND message LIKE CONCAT('%', ?, '%')
                      AND is_read = 0
                ");
                if ($mark_read_stmt) {
                    $mark_read_stmt->bind_param("is", $renter_id, $request_code_for_read);
                    $mark_read_stmt->execute();
                    $mark_read_stmt->close();
                }
            }
        }

        header("Location: booking_details.php?booking_id=" . $booking_id . "&lang=" . urlencode($current_lang) . "&return_confirmed=1");
        exit();
    }

    header("Location: booking_details.php?booking_id=" . $booking_id . "&lang=" . urlencode($current_lang) . "&return_confirmed=0");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Booking & Equipment Details
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        b.*,
        e.title AS equipment_title,
        e.category AS equipment_category,
        e.service_location,
        e.image AS equipment_image,
        e.price_per_day,
        e.lender_id AS lender_id,
        u.full_name AS lender_name,
        u.phone AS lender_phone,
        u.email AS lender_email
    FROM bookings b
    JOIN equipment e ON b.equipment_id = e.equipment_id
    JOIN users u ON e.lender_id = u.user_id
    WHERE b.booking_id = ? AND b.renter_id = ?
";

$stmt = $conn->prepare($sql);
$booking = null;

if ($stmt) {
    $stmt->bind_param("ii", $booking_id, $renter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();
}

if (!$booking) {
    header("Location: my_bookings.php?lang=" . urlencode($current_lang));
    exit();
}

/*
|--------------------------------------------------------------------------
| Unread Notification Count
|--------------------------------------------------------------------------
*/
$notif_count = 0;
$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($notif_check && $notif_check->num_rows > 0) {
    $n_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");
    if ($n_stmt) {
        $n_stmt->bind_param("i", $renter_id);
        $n_stmt->execute();
        $notif_count = (int)($n_stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $n_stmt->close();
    }
}

$lang_param = '?lang=' . urlencode($current_lang);

// Calculations
$start_ts = strtotime($booking['start_date']);
$end_ts = strtotime($booking['end_date']);
$total_days = max(1, ceil(($end_ts - $start_ts) / 86400) + 1);
$status = $booking['status'];
$delivery_is_confirmed = ((int)($booking['delivery_confirmed'] ?? 0) === 1);
$delivery_confirmation_pending = ($status === 'Delivered' && !$delivery_is_confirmed);
$delivery_confirmed_notice = isset($_GET['delivery_confirmed']) && $_GET['delivery_confirmed'] === '1';
$return_is_confirmed = ((int)($booking['return_confirmed'] ?? 0) === 1);
$return_confirmation_pending = ($status === 'Returned' && !$return_is_confirmed);
$return_confirmed_notice = isset($_GET['return_confirmed']) && $_GET['return_confirmed'] === '1';
$just_cancelled = isset($_GET['cancelled']) && $_GET['cancelled'] === '1' && $status === 'Cancelled';

// Translated labels/text used by the booking status and rental timeline.
$booking_ui = [
    'en' => [
        'submitted' => 'Submitted', 'pending_approval' => 'Pending Approval', 'accepted' => 'Accepted',
        'delivered' => 'Delivered', 'returned' => 'Returned', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
        'request_submitted' => 'Request Submitted', 'booking_cancelled' => 'Booking Cancelled',
        'cancelled_message' => 'You cancelled this equipment booking before delivery.',
        'request_message' => 'You have requested to book this equipment.',
        'accepted_message' => 'Lender has reviewed and accepted your request.',
        'waiting_approval' => 'Waiting for lender approval.',
        'delivered_message' => 'Equipment has been delivered.',
        'pending_delivery' => 'Pending delivery execution by the lender.',
        'returned_message' => 'Equipment returned to lender.',
        'pending_return' => 'Return will be recorded after the lender collects the equipment.',
        'completed_message' => 'Rental process successfully finished.',
        'waiting_completion' => 'Waiting for return confirmation.',
        'days' => 'Days', 'cancel_booking' => 'Cancel Booking',
        'confirm_cancel' => 'Are you sure you want to cancel this booking?',
        'lender_review' => 'Lender Review & Approval', 'equipment_delivery' => 'Equipment Delivery', 'equipment_return' => 'Equipment Return',
        'booking_details_status' => 'Booking Details & Status',
        'track_status_lender' => 'Track your equipment rental status and lender details.',
        'dashboard' => 'Dashboard',
        'my_bookings' => 'My Bookings',
        'booking_details' => 'Booking Details',
        'back_my_bookings' => 'Back to My Bookings',
        'cancelled_alert' => 'You have cancelled this equipment booking.',
        'booking_information' => 'Booking Information',
        'category' => 'Category',
        'equipment_alt' => 'Equipment',
        'delivery_address' => 'Delivery Address',
        'order_summary' => 'Order Summary',
        'price_per_day' => 'Price per Day',
        'total_days' => 'Total Days',
        'total_rent' => 'Total Rent',
        'advance_paid' => 'Advance Paid',
        'remaining_cod' => 'Remaining Amount (COD)',
        'payment_method' => 'Payment Method',
        'cash_on_delivery' => 'Cash on Delivery',
        'view_lender_details' => 'View Lender Details',
        'cancel_booking_btn' => 'Cancel Booking',
        'booking_status' => 'Booking Status',
        'rental_timeline' => 'Rental Timeline',
        'rental_completed' => 'Rental Completed',
        'delivery_update_title' => 'Delivery Update',
        'delivery_update_no_date' => 'Your equipment will be delivered on the date decided by the lender.',
        'delivery_update_with_date' => 'Your equipment will be delivered on {date}, as scheduled by the lender.',
        'delivery_confirmation_title' => 'Delivery Confirmation Required',
        'delivery_confirmation_message' => 'The lender has marked this equipment as delivered. Please confirm that you have received the equipment.',
        'confirm_delivery_btn' => 'Confirm Delivery',
        'delivery_confirmed_message' => 'You have confirmed that the equipment was delivered.',
        'delivery_confirmation_success' => 'Delivery confirmed successfully.',
    ],
    'kn' => [
        'submitted' => 'ಸಲ್ಲಿಸಲಾಗಿದೆ', 'pending_approval' => 'ಅನುಮೋದನೆ ಬಾಕಿಯಿದೆ', 'accepted' => 'ಸ್ವೀಕರಿಸಲಾಗಿದೆ',
        'delivered' => 'ತಲುಪಿಸಲಾಗಿದೆ', 'returned' => 'ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ', 'completed' => 'ಪೂರ್ಣಗೊಂಡಿದೆ', 'cancelled' => 'ರದ್ದುಗೊಳಿಸಲಾಗಿದೆ',
        'request_submitted' => 'ವಿನಂತಿಯನ್ನು ಸಲ್ಲಿಸಲಾಗಿದೆ', 'booking_cancelled' => 'ಬುಕಿಂಗ್ ರದ್ದುಗೊಳಿಸಲಾಗಿದೆ',
        'cancelled_message' => 'ವಿತರಣೆಯ ಮೊದಲು ನೀವು ಈ ಉಪಕರಣದ ಬುಕಿಂಗ್ ಅನ್ನು ರದ್ದುಗೊಳಿಸಿದ್ದೀರಿ.',
        'request_message' => 'ಈ ಉಪಕರಣವನ್ನು ಬುಕ್ ಮಾಡಲು ನೀವು ವಿನಂತಿಸಿದ್ದೀರಿ.',
        'accepted_message' => 'ಸಾಲದಾತರು ನಿಮ್ಮ ವಿನಂತಿಯನ್ನು ಪರಿಶೀಲಿಸಿ ಸ್ವೀಕರಿಸಿದ್ದಾರೆ.',
        'waiting_approval' => 'ಸಾಲದಾತರ ಪರಿಶೀಲನೆ ಮತ್ತು ದೃಢೀಕರಣಕ್ಕಾಗಿ ಕಾಯಲಾಗುತ್ತಿದೆ.',
        'delivered_message' => 'ಉಪಕರಣವನ್ನು ಯಶಸ್ವಿಯಾಗಿ ತಲುಪಿಸಲಾಗಿದೆ.',
        'pending_delivery' => 'ಸಾಲದಾತರಿಂದ ವಿತರಣೆಯ ಕಾರ್ಯಗತಗೊಳಿಸುವಿಕೆ ಬಾಕಿಯಿದೆ.',
        'returned_message' => 'ಉಪಕರಣವನ್ನು ಸಾಲದಾತರಿಗೆ ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ.',
        'pending_return' => 'ಸಾಲದಾತರು ಉಪಕರಣವನ್ನು ಸಂಗ್ರಹಿಸಿದ ನಂತರ ಹಿಂತಿರುಗಿಸುವಿಕೆಯನ್ನು ದಾಖಲಿಸಲಾಗುತ್ತದೆ.',
        'completed_message' => 'ಬಾಡಿಗೆ ಪ್ರಕ್ರಿಯೆಯು ಯಶಸ್ವಿಯಾಗಿ ಪೂರ್ಣಗೊಂಡಿದೆ.',
        'waiting_completion' => 'ಹಿಂತಿರುಗಿಸುವಿಕೆಯ ದೃಢೀಕರಣಕ್ಕಾಗಿ ಕಾಯಲಾಗುತ್ತಿದೆ.',
        'days' => 'ದಿನಗಳು', 'cancel_booking' => 'ಬುಕಿಂಗ್ ರದ್ದುಮಾಡಿ',
        'confirm_cancel' => 'ಈ ಬುಕಿಂಗ್ ಅನ್ನು ರದ್ದುಗೊಳಿಸಲು ನೀವು ಖಚಿತವಾಗಿದ್ದೀರಾ?',
        'lender_review' => 'ಸಾಲದಾತರ ಪರಿಶೀಲನೆ ಮತ್ತು ಅನುಮೋದನೆ', 'equipment_delivery' => 'ಉಪಕರಣ ವಿತರಣೆ', 'equipment_return' => 'ಉಪಕರಣ ಹಿಂತಿರುಗಿಸುವಿಕೆ',
        'booking_details_status' => 'ಬುಕಿಂಗ್ ವಿವರಗಳು ಮತ್ತು ಸ್ಥಿತಿ',
        'track_status_lender' => 'ನಿಮ್ಮ ಉಪಕರಣ ಬಾಡಿಗೆ ಸ್ಥಿತಿ ಮತ್ತು ಸಾಲದಾತರ ವಿವರಗಳನ್ನು ಟ್ರ್ಯಾಕ್ ಮಾಡಿ.',
        'dashboard' => 'ಡ್ಯಾಶ್‌ಬೋರ್ಡ್',
        'my_bookings' => 'ನನ್ನ ಬುಕಿಂಗ್‌ಗಳು',
        'booking_details' => 'ಬುಕಿಂಗ್ ವಿವರಗಳು',
        'back_my_bookings' => 'ನನ್ನ ಬುಕಿಂಗ್‌ಗಳಿಗೆ ಹಿಂತಿರುಗಿ',
        'cancelled_alert' => 'ನೀವು ಈ ಉಪಕರಣದ ಬುಕಿಂಗ್ ಅನ್ನು ರದ್ದುಗೊಳಿಸಿದ್ದೀರಿ.',
        'booking_information' => 'ಬುಕಿಂಗ್ ಮಾಹಿತಿ',
        'category' => 'ವರ್ಗ',
        'equipment_alt' => 'ಉಪಕರಣ',
        'delivery_address' => 'ವಿತರಣಾ ವಿಳಾಸ',
        'order_summary' => 'ಆರ್ಡರ್ ಸಾರಾಂಶ',
        'price_per_day' => 'ಪ್ರತಿ ದಿನದ ಬೆಲೆ',
        'total_days' => 'ಒಟ್ಟು ದಿನಗಳು',
        'total_rent' => 'ಒಟ್ಟು ಬಾಡಿಗೆ',
        'advance_paid' => 'ಮುಂಗಡ ಪಾವತಿ',
        'remaining_cod' => 'ಉಳಿದ ಮೊತ್ತ (COD)',
        'payment_method' => 'ಪಾವತಿ ವಿಧಾನ',
        'cash_on_delivery' => 'ಕ್ಯಾಶ್ ಆನ್ ಡೆಲಿವರಿ',
        'view_lender_details' => 'ಸಾಲದಾತರ ವಿವರಗಳನ್ನು ವೀಕ್ಷಿಸಿ',
        'cancel_booking_btn' => 'ಬುಕಿಂಗ್ ರದ್ದುಮಾಡಿ',
        'booking_status' => 'ಬುಕಿಂಗ್ ಸ್ಥಿತಿ',
        'rental_timeline' => 'ಬಾಡಿಗೆ ಸಮಯರೇಖೆ',
        'rental_completed' => 'ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿದೆ',
        'delivery_update_title' => 'ವಿತರಣೆ ನವೀಕರಣ',
        'delivery_update_no_date' => 'ಸಾಲದಾತರು ನಿರ್ಧರಿಸಿದ ದಿನಾಂಕದಂದು ನಿಮ್ಮ ಉಪಕರಣವನ್ನು ತಲುಪಿಸಲಾಗುತ್ತದೆ.',
        'delivery_update_with_date' => 'ಸಾಲದಾತರು ನಿಗದಿಪಡಿಸಿದಂತೆ ನಿಮ್ಮ ಉಪಕರಣವನ್ನು {date} ರಂದು ತಲುಪಿಸಲಾಗುತ್ತದೆ.',
        'delivery_confirmation_title' => 'ವಿತರಣೆ ದೃಢೀಕರಣ ಅಗತ್ಯವಿದೆ',
        'delivery_confirmation_message' => 'ಸಾಲದಾತರು ಈ ಉಪಕರಣವನ್ನು ತಲುಪಿಸಲಾಗಿದೆ ಎಂದು ಗುರುತಿಸಿದ್ದಾರೆ. ನೀವು ಉಪಕರಣವನ್ನು ಸ್ವೀಕರಿಸಿದ್ದೀರಿ ಎಂದು ದಯವಿಟ್ಟು ದೃಢೀಕರಿಸಿ.',
        'confirm_delivery_btn' => 'ವಿತರಣೆಯನ್ನು ದೃಢೀಕರಿಸಿ',
        'delivery_confirmed_message' => 'ಉಪಕರಣವನ್ನು ತಲುಪಿಸಲಾಗಿದೆ ಎಂದು ನೀವು ದೃಢೀಕರಿಸಿದ್ದೀರಿ.',
        'delivery_confirmation_success' => 'ವಿತರಣೆಯನ್ನು ಯಶಸ್ವಿಯಾಗಿ ದೃಢೀಕರಿಸಲಾಗಿದೆ.',
    ],
    'hi' => [
        'submitted' => 'सबमिट किया गया', 'pending_approval' => 'अनुमोदन लंबित', 'accepted' => 'स्वीकृत',
        'delivered' => 'डिलीवर किया गया', 'returned' => 'वापस किया गया', 'completed' => 'पूरा हुआ', 'cancelled' => 'रद्द किया गया',
        'request_submitted' => 'अनुरोध सबमिट किया गया', 'booking_cancelled' => 'बुकिंग रद्द की गई',
        'cancelled_message' => 'डिलीवरी से पहले आपने इस उपकरण की बुकिंग रद्द कर दी है।',
        'request_message' => 'आपने इस उपकरण को बुक करने का अनुरोध किया है।',
        'accepted_message' => 'लेंडर ने आपके अनुरोध की समीक्षा करके उसे स्वीकार कर लिया है।',
        'waiting_approval' => 'लेंडर की समीक्षा और पुष्टि की प्रतीक्षा है।',
        'delivered_message' => 'उपकरण सफलतापूर्वक डिलीवर कर दिया गया है।',
        'pending_delivery' => 'लेंडर द्वारा डिलीवरी की प्रक्रिया लंबित है।',
        'returned_message' => 'उपकरण लेंडर को वापस कर दिया गया है।',
        'pending_return' => 'लेंडर द्वारा उपकरण लेने के बाद वापसी दर्ज की जाएगी।',
        'completed_message' => 'किराये की प्रक्रिया सफलतापूर्वक पूरी हो गई है।',
        'waiting_completion' => 'वापसी की पुष्टि की प्रतीक्षा है।',
        'days' => 'दिन', 'cancel_booking' => 'बुकिंग रद्द करें',
        'confirm_cancel' => 'क्या आप वाकई इस बुकिंग को रद्द करना चाहते हैं?',
        'lender_review' => 'लेंडर की समीक्षा और अनुमोदन', 'equipment_delivery' => 'उपकरण की डिलीवरी', 'equipment_return' => 'उपकरण की वापसी',
        'booking_details_status' => 'बुकिंग विवरण और स्थिति',
        'track_status_lender' => 'अपने उपकरण किराये की स्थिति और लेंडर के विवरण को ट्रैक करें।',
        'dashboard' => 'डैशबोर्ड',
        'my_bookings' => 'मेरी बुकिंग',
        'booking_details' => 'बुकिंग विवरण',
        'back_my_bookings' => 'मेरी बुकिंग पर वापस जाएं',
        'cancelled_alert' => 'आपने इस उपकरण की बुकिंग रद्द कर दी है।',
        'booking_information' => 'बुकिंग जानकारी',
        'category' => 'श्रेणी',
        'equipment_alt' => 'उपकरण',
        'delivery_address' => 'डिलीवरी पता',
        'order_summary' => 'ऑर्डर सारांश',
        'price_per_day' => 'प्रति दिन कीमत',
        'total_days' => 'कुल दिन',
        'total_rent' => 'कुल किराया',
        'advance_paid' => 'अग्रिम भुगतान',
        'remaining_cod' => 'शेष राशि (COD)',
        'payment_method' => 'भुगतान विधि',
        'cash_on_delivery' => 'कैश ऑन डिलीवरी',
        'view_lender_details' => 'लेंडर विवरण देखें',
        'cancel_booking_btn' => 'बुकिंग रद्द करें',
        'booking_status' => 'बुकिंग स्थिति',
        'rental_timeline' => 'किराये की समयरेखा',
        'rental_completed' => 'किराया पूरा हुआ',
        'delivery_update_title' => 'डिलीवरी अपडेट',
        'delivery_update_no_date' => 'आपका उपकरण लेंडर द्वारा तय की गई तारीख पर डिलीवर किया जाएगा।',
        'delivery_update_with_date' => 'लेंडर द्वारा निर्धारित कार्यक्रम के अनुसार आपका उपकरण {date} को डिलीवर किया जाएगा।',
        'delivery_confirmation_title' => 'डिलीवरी की पुष्टि आवश्यक है',
        'delivery_confirmation_message' => 'लेंडर ने इस उपकरण को डिलीवर किया हुआ चिन्हित किया है। कृपया पुष्टि करें कि आपको उपकरण प्राप्त हो गया है।',
        'confirm_delivery_btn' => 'डिलीवरी की पुष्टि करें',
        'delivery_confirmed_message' => 'आपने पुष्टि कर दी है कि उपकरण डिलीवर हो गया है।',
        'delivery_confirmation_success' => 'डिलीवरी की सफलतापूर्वक पुष्टि हो गई है।',
    ]
];
$bt = $booking_ui[$current_lang] ?? $booking_ui['en'];
$status_labels = [
    'Pending' => $bt['pending_approval'], 'Accepted' => $bt['accepted'], 'Delivered' => $bt['delivered'],
    'Returned' => $bt['returned'], 'Completed' => $bt['completed'], 'Cancelled' => $bt['cancelled'], 'Rejected' => $current_lang === 'kn' ? 'ತಿರಸ್ಕರಿಸಲಾಗಿದೆ' : ($current_lang === 'hi' ? 'अस्वीकृत' : 'Rejected'), 'Overdue' => $current_lang === 'kn' ? 'ಅವಧಿ ಮೀರಿದೆ' : ($current_lang === 'hi' ? 'अवधि समाप्त' : 'Overdue')
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($bt['booking_details']); ?> - Agriculture Equipment Rental System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f6f9;
            color: #1e293b;
            margin: 0;
            font-weight: 500;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .breadcrumb-nav {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
        }

        .breadcrumb-nav a {
            color: #64748b;
            text-decoration: none;
        }

        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .page-header {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .page-subtitle {
            font-size: 13px;
            color: #64748b;
        }

        .btn-back {
            border: 1px solid #198754;
            color: #198754;
            background: #fff;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #198754;
            color: #fff;
        }

        .content-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.01);
        }

        .card-title-custom {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .equipment-box {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .equipment-img {
            width: 100px;
            height: 75px;
            border-radius: 8px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .meta-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .meta-item label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 800;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }

        .meta-item span {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }

        .info-pill {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Stepper */
        .stepper {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 25px 0 10px 0;
        }

        .stepper::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 5%;
            right: 5%;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            background: #fff;
            padding: 0 8px;
            text-align: center;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 6px auto;
            font-size: 13px;
            font-weight: 800;
        }

        .step.active .step-circle {
            background: #0284c7;
            color: #fff;
        }

        .step.completed .step-circle {
            background: #16a34a;
            color: #fff;
        }

        .step-label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
        }

        /* Timeline list */
        .timeline-list {
            list-style: none;
            padding-left: 15px;
            position: relative;
            margin: 0;
        }

        .timeline-list::before {
            content: '';
            position: absolute;
            top: 5px;
            bottom: 5px;
            left: 4px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            padding-left: 20px;
            margin-bottom: 15px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .timeline-item.active::before {
            background: #16a34a;
        }

        .timeline-item h6 {
            font-size: 13px;
            font-weight: 800;
            margin: 0;
        }

        .timeline-item p {
            font-size: 11px;
            color: #64748b;
            margin: 0;
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
            font-size: 16px;
            border: 2px solid #cbd5e1;
        }

        .btn-cancel-booking {
            background: #dc3545;
            color: #fff;
            border: none;
            width: 100%;
            padding: 9px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            transition: 0.2s;
            margin-top: 10px;
        }

        .btn-cancel-booking:hover {
            background: #bb2d3b;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/renter_sidebar.php'; ?>

<div class="main-content">

    <!-- Top Navigation Bar -->
    <div class="top-nav-bar">
        <form action="booking_details.php" method="GET" class="d-flex align-items-center mb-0">
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <select name="lang" class="form-select form-select-sm fw-bold w-auto" onchange="this.form.submit()">
                <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
            </select>
        </form>

        <a href="notifications.php<?php echo $lang_param; ?>" class="position-relative text-dark text-decoration-none p-1">
            <i class="fa-solid fa-bell fa-lg"></i>
            <?php if ($notif_count > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                    <?php echo $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a href="profile.php<?php echo $lang_param; ?>" class="profile-avatar-btn">
            <i class="fa-solid fa-user"></i>
        </a>
    </div>

    <!-- Header Section -->
    <div class="breadcrumb-nav">
        <a href="dashboard.php<?php echo $lang_param; ?>"><?php echo htmlspecialchars($bt['dashboard']); ?></a> &gt; 
        <a href="my_bookings.php<?php echo $lang_param; ?>"><?php echo htmlspecialchars($bt['my_bookings']); ?></a> &gt; 
        <span><?php echo htmlspecialchars($bt['booking_details']); ?></span>
    </div>

    <div class="page-header-container">
        <div>
            <h1 class="page-header"><?php echo htmlspecialchars($bt['booking_details_status']); ?></h1>
            <div class="page-subtitle"><?php echo htmlspecialchars($bt['track_status_lender']); ?></div>
        </div>
        <a href="my_bookings.php<?php echo $lang_param; ?>" class="btn-back">
            &larr; <?php echo htmlspecialchars($bt['back_my_bookings']); ?>
        </a>
    </div>

    <?php if ($status === 'Cancelled'): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert" style="border-radius: 10px; font-size: 13px; font-weight: 600;">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            <?php echo htmlspecialchars($bt['cancelled_alert']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            
            <!-- Booking Information Card -->
            <div class="content-card">
                <div class="card-title-custom">
                    <i class="fa-solid fa-circle-info text-success"></i> <?php echo htmlspecialchars($bt['booking_information']); ?>
                </div>

                <div class="equipment-box">
                    <?php 
                    $img_path = !empty($booking['equipment_image']) ? 'uploads/' . $booking['equipment_image'] : '';
                    if (!empty($booking['equipment_image']) && file_exists(__DIR__ . '/' . $img_path)): 
                    ?>
                        <img src="<?php echo htmlspecialchars($img_path); ?>" class="equipment-img" alt="<?php echo htmlspecialchars($bt['equipment_alt']); ?>">
                    <?php else: ?>
                        <div class="equipment-img d-flex align-items-center justify-content-center text-muted">
                            <i class="fa-solid fa-tractor fa-2x"></i>
                        </div>
                    <?php endif; ?>

                    <div>
                        <h5 class="fw-bold mb-1" style="font-size: 16px;"><?php echo htmlspecialchars($booking['equipment_title']); ?></h5>
                        <p class="text-muted mb-1" style="font-size: 12px;">
                            <?php echo htmlspecialchars($bt['category']); ?>: <strong><?php echo htmlspecialchars($booking['equipment_category']); ?></strong>
                        </p>
                        <p class="text-muted mb-0" style="font-size: 12px;">
                            <i class="fa-solid fa-location-dot text-danger me-1"></i>
                            <?php echo htmlspecialchars($booking['service_location']); ?>
                        </p>
                    </div>
                </div>

                <div class="meta-grid">
                    <div class="meta-item">
                        <label><?php echo htmlspecialchars(__('booking_id')); ?></label>
                        <span><i class="fa-solid fa-barcode me-1 text-muted"></i> <?php echo htmlspecialchars($booking['request_code']); ?></span>
                    </div>
                    <div class="meta-item">
                        <label><?php echo htmlspecialchars(__('booking_date')); ?></label>
                        <span><i class="fa-solid fa-calendar me-1 text-muted"></i> <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <label><?php echo htmlspecialchars(__('lender_name')); ?></label>
                        <span><i class="fa-solid fa-user me-1 text-muted"></i> <?php echo htmlspecialchars($booking['lender_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <label><?php echo htmlspecialchars(__('phone_number')); ?></label>
                        <span><i class="fa-solid fa-phone me-1 text-muted"></i> <?php echo htmlspecialchars($booking['lender_phone']); ?></span>
                    </div>
                </div>

                <div class="info-pill d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa-solid fa-calendar-days me-1 text-muted"></i> <strong><?php echo htmlspecialchars(__('rental_period')); ?></strong><br>
                        <span class="text-muted"><?php echo date('d M Y', strtotime($booking['start_date'])); ?> – <?php echo date('d M Y', strtotime($booking['end_date'])); ?></span>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold"><?php echo $total_days; ?> <?php echo htmlspecialchars($bt['days']); ?></span>
                </div>

                <div class="info-pill">
                    <i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong><?php echo htmlspecialchars($bt['delivery_address']); ?></strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($booking['service_location']); ?></span>
                </div>
            </div>

            <?php if ($status === 'Pending' || $status === 'Accepted'): ?>
                <div class="content-card" style="border-left: 4px solid #198754; background: #f0fdf4;">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #d1fae5; color: #198754; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1" style="color: #166534;">
                                <?php echo htmlspecialchars($bt['delivery_update_title']); ?>
                            </h6>
                            <p class="mb-0" style="font-size: 13px; color: #365314; line-height: 1.6;">
                                <?php
                                if (!empty($booking['delivery_date'])) {
                                    $delivery_date_text = date('d M Y', strtotime($booking['delivery_date']));
                                    echo htmlspecialchars(str_replace('{date}', $delivery_date_text, $bt['delivery_update_with_date']));
                                } else {
                                    echo htmlspecialchars($bt['delivery_update_no_date']);
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Booking Status Stepper -->
            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="card-title-custom mb-0">
                        <i class="fa-solid fa-bars-progress text-success"></i> <?php echo htmlspecialchars($bt['booking_status']); ?>
                    </div>
                    <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 fw-bold">
                        <?php echo htmlspecialchars($status_labels[$status] ?? $status); ?>
                    </span>
                </div>

                <?php if ($status === 'Cancelled'): ?>
                    <div class="stepper">
                        <div class="step completed">
                            <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                            <div class="step-label"><?php echo htmlspecialchars($bt['submitted']); ?></div>
                        </div>
                        <div class="step completed">
                            <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                            <div class="step-label"><?php echo htmlspecialchars($bt['pending_approval']); ?></div>
                        </div>
                        <div class="step active">
                            <div class="step-circle"><i class="fa-solid fa-xmark"></i></div>
                            <div class="step-label"><?php echo htmlspecialchars($bt['cancelled']); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                <div class="stepper">
                    <div class="step <?php echo in_array($status, ['Pending', 'Accepted', 'Delivered', 'Returned', 'Completed']) ? 'completed' : ''; ?>">
                        <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                        <div class="step-label"><?php echo htmlspecialchars($bt['submitted']); ?></div>
                    </div>
                    <div class="step <?php echo in_array($status, ['Accepted', 'Delivered', 'Returned', 'Completed']) ? 'completed' : ''; ?>">
                        <div class="step-circle"><i class="fa-solid fa-check"></i></div>
                        <div class="step-label"><?php echo htmlspecialchars($bt['pending_approval']); ?></div>
                    </div>
                    <div class="step <?php echo ($status === 'Accepted') ? 'active' : (in_array($status, ['Delivered', 'Returned', 'Completed']) ? 'completed' : ''); ?>">
                        <div class="step-circle">3</div>
                        <div class="step-label"><?php echo htmlspecialchars($bt['accepted']); ?></div>
                    </div>
                    <div class="step <?php echo ($status === 'Delivered' && $delivery_is_confirmed) ? 'active' : (in_array($status, ['Returned', 'Completed']) ? 'completed' : ''); ?>">
                        <div class="step-circle">4</div>
                        <div class="step-label"><?php echo htmlspecialchars($bt['delivered']); ?></div>
                    </div>
                    <div class="step <?php echo ($status === 'Returned') ? 'active' : ($status === 'Completed' ? 'completed' : ''); ?>">
                        <div class="step-circle">5</div>
                        <div class="step-label"><?php echo htmlspecialchars($bt['returned']); ?></div>
                    </div>
                    <div class="step <?php echo ($status === 'Completed') ? 'completed' : ''; ?>">
                        <div class="step-circle">6</div>
                        <div class="step-label"><?php echo htmlspecialchars($bt['completed']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Rental Timeline -->
            <div class="content-card">
                <div class="card-title-custom">
                    <i class="fa-solid fa-timeline text-success"></i> <?php echo htmlspecialchars($bt['rental_timeline']); ?>
                </div>

                <ul class="timeline-list">
                    <?php if ($status === 'Cancelled'): ?>
                        <li class="timeline-item active">
                            <h6><?php echo htmlspecialchars($bt['request_submitted']); ?></h6>
                            <p><?php echo htmlspecialchars($bt['request_message']); ?> (<?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>)</p>
                        </li>
                        <li class="timeline-item active">
                            <h6><?php echo htmlspecialchars($bt['booking_cancelled']); ?></h6>
                            <p><?php echo htmlspecialchars($bt['cancelled_message']); ?></p>
                        </li>
                    <?php else: ?>
                    <li class="timeline-item active">
                        <h6><?php echo htmlspecialchars($bt['request_submitted']); ?></h6>
                        <p><?php echo htmlspecialchars($bt['request_message']); ?> (<?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?>)</p>
                    </li>
                    <li class="timeline-item <?php echo in_array($status, ['Accepted', 'Delivered', 'Returned', 'Completed']) ? 'active' : ''; ?>">
                        <h6><?php echo htmlspecialchars($bt['lender_review']); ?></h6>
                        <p><?php echo htmlspecialchars(in_array($status, ['Accepted', 'Delivered', 'Returned', 'Completed']) ? $bt['accepted_message'] : $bt['waiting_approval']); ?></p>
                    </li>
                    <li class="timeline-item <?php echo (($status === 'Delivered' && $delivery_is_confirmed) || in_array($status, ['Returned', 'Completed'])) ? 'active' : ''; ?>">
                        <h6><?php echo htmlspecialchars($bt['equipment_delivery']); ?></h6>
                        <p><?php echo htmlspecialchars((($status === 'Delivered' && $delivery_is_confirmed) || in_array($status, ['Returned', 'Completed'])) ? $bt['delivered_message'] : $bt['pending_delivery']); ?></p>
                    </li>
                    <li class="timeline-item <?php echo in_array($status, ['Returned', 'Completed']) ? 'active' : ''; ?>">
                        <h6><?php echo htmlspecialchars($bt['equipment_return']); ?></h6>
                        <p><?php echo htmlspecialchars(in_array($status, ['Returned', 'Completed']) ? $bt['returned_message'] : $bt['pending_return']); ?></p>
                    </li>
                    <li class="timeline-item <?php echo ($status === 'Completed') ? 'active' : ''; ?>">
                        <h6><?php echo htmlspecialchars($bt['rental_completed']); ?></h6>
                        <p><?php echo htmlspecialchars(($status === 'Completed') ? $bt['completed_message'] : $bt['waiting_completion']); ?></p>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <!-- Right Column: <?php echo htmlspecialchars($bt['order_summary']); ?> & Actions -->
        <div class="col-lg-4">
            <div class="content-card">
                <div class="card-title-custom">
                    <i class="fa-solid fa-receipt text-success"></i> <?php echo htmlspecialchars($bt['order_summary']); ?>
                </div>

                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted"><?php echo htmlspecialchars($bt['price_per_day']); ?></span>
                    <span class="fw-bold">₹<?php echo number_format((float)($booking['price_per_day'] ?? 0), 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted"><?php echo htmlspecialchars($bt['total_days']); ?></span>
                    <span class="fw-bold"><?php echo $total_days; ?> <?php echo htmlspecialchars($bt['days']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted"><?php echo htmlspecialchars($bt['total_rent']); ?></span>
                    <span class="fw-bold text-success">₹<?php echo number_format((float)$booking['total_amount'], 2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2" style="font-size: 13px;">
                    <span class="text-muted"><?php echo htmlspecialchars($bt['advance_paid']); ?></span>
                    <span class="fw-bold">₹<?php echo number_format((float)$booking['advance_amount'], 2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3" style="font-size: 14px;">
                    <span class="fw-bold"><?php echo htmlspecialchars($bt['remaining_cod']); ?></span>
                    <span class="fw-bold text-danger">₹<?php echo number_format((float)($booking['total_amount'] - $booking['advance_amount']), 2); ?></span>
                </div>

                <div class="mb-3">
                    <span class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($bt['payment_method']); ?></span>
                    <div><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($bt['cash_on_delivery']); ?></span></div>
                </div>

                <a href="lender_details.php?booking_id=<?php echo $booking_id; ?>&lang=<?php echo urlencode($current_lang); ?>"
   class="btn btn-light border w-100 fw-bold mb-2"
   style="font-size: 13px;">
    <i class="fa-solid fa-user me-1"></i> <?php echo htmlspecialchars($bt['view_lender_details']); ?>
</a>

                <?php if ($delivery_confirmation_pending): ?>
                    <div class="alert alert-warning mt-3 mb-2" style="font-size:13px; border-radius:10px;">
                        <div class="fw-bold mb-1">
                            <i class="fa-solid fa-truck me-1"></i>
                            <?php echo htmlspecialchars($bt['delivery_confirmation_title']); ?>
                        </div>
                        <div>
                            <?php echo htmlspecialchars($bt['delivery_confirmation_message']); ?>
                        </div>
                    </div>

                    <form method="POST" action="booking_details.php?booking_id=<?php echo $booking_id; ?>&lang=<?php echo urlencode($current_lang); ?>" onsubmit="return confirm(<?php echo json_encode($bt['delivery_confirmation_message']); ?>);">
                        <button type="submit" name="confirm_delivery" value="1" class="btn btn-success w-100 fw-bold" style="font-size:13px;">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            <?php echo htmlspecialchars($bt['confirm_delivery_btn']); ?>
                        </button>
                    </form>
                <?php elseif ($delivery_is_confirmed && $status === 'Delivered'): ?>
                    <div class="alert alert-success mt-3 mb-2" style="font-size:13px; border-radius:10px;">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        <?php echo htmlspecialchars($bt['delivery_confirmed_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($delivery_confirmed_notice): ?>
                    <div class="alert alert-success mt-2 mb-2" style="font-size:13px; border-radius:10px;">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        <?php echo htmlspecialchars($bt['delivery_confirmation_success']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($return_confirmation_pending): ?>
                    <div class="alert alert-warning mt-3 mb-2" style="font-size:13px; border-radius:10px;">
                        <div class="fw-bold mb-1">
                            <i class="fa-solid fa-rotate-left me-1"></i>
                            <?php
                            if ($current_lang === 'kn') {
                                echo 'ವಾಪಸಿ ದೃಢೀಕರಣ ಅಗತ್ಯವಿದೆ';
                            } elseif ($current_lang === 'hi') {
                                echo 'वापसी की पुष्टि आवश्यक है';
                            } else {
                                echo 'Return Confirmation Required';
                            }
                            ?>
                        </div>
                        <div>
                            <?php
                            if ($current_lang === 'kn') {
                                echo 'ಲ್ಯಾಂಡರ್ ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ ಎಂದು ಗುರುತಿಸಿದ್ದಾರೆ. ದಯವಿಟ್ಟು ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಿದ್ದೀರಿ ಎಂದು ದೃಢೀಕರಿಸಿ.';
                            } elseif ($current_lang === 'hi') {
                                echo 'लेंडर ने इस उपकरण को वापस किया हुआ चिन्हित किया है। कृपया पुष्टि करें कि उपकरण वापस कर दिया गया है।';
                            } else {
                                echo 'The lender has marked this equipment as returned. Please confirm that the equipment has been returned.';
                            }
                            ?>
                        </div>
                    </div>

                    <form method="POST" action="booking_details.php?booking_id=<?php echo $booking_id; ?>&lang=<?php echo urlencode($current_lang); ?>" onsubmit="return confirm(<?php echo json_encode($current_lang === 'kn' ? 'ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಲು ನೀವು ಖಚಿತವಾಗಿದ್ದೀರಾ?' : ($current_lang === 'hi' ? 'क्या आप वाकई पुष्टि करना चाहते हैं कि उपकरण वापस कर दिया गया है?' : 'Are you sure you want to confirm that the equipment has been returned?')); ?>);">
                        <button type="submit" name="confirm_return" value="1" class="btn btn-success w-100 fw-bold" style="font-size:13px;">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            <?php
                            if ($current_lang === 'kn') {
                                echo 'ವಾಪಸಿಯನ್ನು ದೃಢೀಕರಿಸಿ';
                            } elseif ($current_lang === 'hi') {
                                echo 'वापसी की पुष्टि करें';
                            } else {
                                echo 'Confirm Return';
                            }
                            ?>
                        </button>
                    </form>
                <?php elseif ($return_is_confirmed && $status === 'Completed'): ?>
                    <div class="alert alert-success mt-3 mb-2" style="font-size:13px; border-radius:10px;">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        <?php
                        if ($current_lang === 'kn') {
                            echo 'ನೀವು ಉಪಕರಣದ ವಾಪಸಿಯನ್ನು ದೃಢೀಕರಿಸಿದ್ದೀರಿ.';
                        } elseif ($current_lang === 'hi') {
                            echo 'आपने उपकरण की वापसी की पुष्टि कर दी है।';
                        } else {
                            echo 'You have confirmed that the equipment was returned.';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($return_confirmed_notice): ?>
                    <div class="alert alert-success mt-2 mb-2" style="font-size:13px; border-radius:10px;">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        <?php
                        if ($current_lang === 'kn') {
                            echo 'ವಾಪಸಿಯನ್ನು ಯಶಸ್ವಿಯಾಗಿ ದೃಢೀಕರಿಸಲಾಗಿದೆ.';
                        } elseif ($current_lang === 'hi') {
                            echo 'वापसी की सफलतापूर्वक पुष्टि हो गई है।';
                        } else {
                            echo 'Return confirmed successfully.';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Cancel Button Section: Only available BEFORE equipment delivery -->
                <?php if ($status === 'Pending' || $status === 'Accepted'): ?>
                    <form method="POST" action="booking_details.php?booking_id=<?php echo $booking_id; ?>&lang=<?php echo urlencode($current_lang); ?>" onsubmit="return confirm(<?php echo json_encode($bt['confirm_cancel']); ?>);">
                        <button type="submit" name="cancel_booking" value="1" class="btn-cancel-booking">
                            <i class="fa-solid fa-xmark me-1"></i> <?php echo htmlspecialchars($bt['cancel_booking_btn']); ?>
                        </button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>