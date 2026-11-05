<?php
session_start();

require_once 'includes/config.php';

/* =========================================================
   LANGUAGE
   ========================================================= */
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

require_once 'includes/lang.php';

/* =========================================================
   OVERDUE PENALTY TRANSLATIONS (page-local)
   ========================================================= */
$overdue_text = [
    'en' => [
        'title' => 'Overdue Penalty',
        'assessed' => 'Assessed overdue amount:',
        'waiting' => 'The overdue penalty is recorded. Collection can be confirmed only after the renter confirms the equipment return.',
        'collected' => 'Penalty collected and recorded.',
        'amount_collected' => 'Amount collected:',
        'collection_date' => 'Collection date:',
        'recorded' => 'Recorded',
        'return_confirmed' => 'The renter has confirmed the equipment return.',
        'collect_instruction' => 'Collect the amount physically, then confirm it below.',
        'button' => 'Penalty Collected & Clear Overdue',
        'confirm' => 'Confirm that you have received {amount} from the renter?',
        'success' => 'Overdue penalty collection recorded successfully.',
        'error' => 'Penalty could not be recorded. Confirm that the renter has confirmed the return and that the penalty has not already been collected.'
    ],
    'kn' => [
        'title' => 'ತಡವಾದ ಬಾಡಿಗೆ ದಂಡ',
        'assessed' => 'ಒಟ್ಟು ತಡವಾದ ದಂಡದ ಮೊತ್ತ:',
        'waiting' => 'ತಡವಾದ ದಂಡವನ್ನು ದಾಖಲಿಸಲಾಗಿದೆ. ಬಾಡಿಗೆದಾರರು ಉಪಕರಣ ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ ನಂತರ ಮಾತ್ರ ಸಂಗ್ರಹವನ್ನು ದೃಢೀಕರಿಸಬಹುದು.',
        'collected' => 'ದಂಡವನ್ನು ಸಂಗ್ರಹಿಸಿ ದಾಖಲಿಸಲಾಗಿದೆ.',
        'amount_collected' => 'ಸಂಗ್ರಹಿಸಿದ ಮೊತ್ತ:',
        'collection_date' => 'ಸಂಗ್ರಹಿಸಿದ ದಿನಾಂಕ:',
        'recorded' => 'ದಾಖಲಿಸಲಾಗಿದೆ',
        'return_confirmed' => 'ಬಾಡಿಗೆದಾರರು ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ.',
        'collect_instruction' => 'ಮೊದಲು ಹಣವನ್ನು ನೇರವಾಗಿ ಸಂಗ್ರಹಿಸಿ, ನಂತರ ಕೆಳಗೆ ದೃಢೀಕರಿಸಿ.',
        'button' => 'ದಂಡ ಸಂಗ್ರಹಿಸಲಾಗಿದೆ ಮತ್ತು ಬಾಕಿ ತೆರವುಗೊಳಿಸಿ',
        'confirm' => 'ಬಾಡಿಗೆದಾರರಿಂದ {amount} ಸ್ವೀಕರಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸುತ್ತೀರಾ?',
        'success' => 'ತಡವಾದ ದಂಡದ ಸಂಗ್ರಹವನ್ನು ಯಶಸ್ವಿಯಾಗಿ ದಾಖಲಿಸಲಾಗಿದೆ.',
        'error' => 'ದಂಡವನ್ನು ದಾಖಲಿಸಲಾಗಲಿಲ್ಲ. ಬಾಡಿಗೆದಾರರು ಉಪಕರಣ ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆಯೇ ಮತ್ತು ದಂಡವನ್ನು ಈಗಾಗಲೇ ಸಂಗ್ರಹಿಸಿಲ್ಲವೇ ಎಂಬುದನ್ನು ಪರಿಶೀಲಿಸಿ.'
    ],
    'hi' => [
        'title' => 'देरी का जुर्माना',
        'assessed' => 'कुल देरी का जुर्माना:',
        'waiting' => 'देरी का जुर्माना दर्ज है। वसूली की पुष्टि किरायेदार द्वारा उपकरण लौटाने की पुष्टि के बाद ही की जा सकती है।',
        'collected' => 'जुर्माना वसूल करके दर्ज कर लिया गया है।',
        'amount_collected' => 'वसूली गई राशि:',
        'collection_date' => 'वसूली की तारीख:',
        'recorded' => 'दर्ज किया गया',
        'return_confirmed' => 'किरायेदार ने उपकरण वापस करने की पुष्टि कर दी है।',
        'collect_instruction' => 'पहले राशि सीधे वसूल करें, फिर नीचे पुष्टि करें।',
        'button' => 'जुर्माना वसूल हुआ और बकाया साफ करें',
        'confirm' => 'क्या आप पुष्टि करते हैं कि आपने किरायेदार से {amount} प्राप्त कर लिया है?',
        'success' => 'देरी के जुर्माने की वसूली सफलतापूर्वक दर्ज की गई।',
        'error' => 'जुर्माना दर्ज नहीं हो सका। पुष्टि करें कि किरायेदार ने उपकरण लौटाने की पुष्टि की है और जुर्माना पहले से वसूला नहीं गया है।'
    ]
];
$overdue_lang = $overdue_text[$current_lang] ?? $overdue_text['en'];

$lang_param = '?lang=' . urlencode($current_lang);

/*
 * Remember the lender page that opened this booking.
 * Only accept same-site lender pages, so an external URL can never become
 * the destination of the Back link.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    $referer_parts = parse_url($referer);
    $current_host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $referer_host = strtolower($referer_parts['host'] ?? '');
    $referer_file = basename($referer_parts['path'] ?? '');

    $is_same_site = $referer_host !== '' && $referer_host === $current_host;
    $is_booking_page = $referer_file === 'lender_booking_details.php';

    // Accept the same-site PHP page that opened the booking details.
    // This covers Rental Requests, My Bookings, Active Rentals,
    // Rental History, and other pages with a View Details link.
    $is_php_page = strtolower(pathinfo($referer_file, PATHINFO_EXTENSION)) === 'php';
    // These are the exact lender pages that contain the View Details icon.
    // Keep this list synchronized with the actual PHP filenames in the project.
    $allowed_return_pages = [
        'lender_my_bookings.php',
        'rental_request.php',
        'lender_rental_history.php',
        'active_rentals.php'
    ];
    $is_allowed_return_page = $is_php_page
        && in_array(strtolower($referer_file), $allowed_return_pages, true);

    if ($is_same_site && !$is_booking_page && $is_allowed_return_page) {
        $return_path = $referer_parts['path'] ?? '';
        $return_query = [];
        if (!empty($referer_parts['query'])) {
            parse_str($referer_parts['query'], $return_query);
        }
        $return_query['lang'] = $current_lang;
        $_SESSION['lender_booking_details_return'] =
            $return_path . '?' . http_build_query($return_query);
    }
}


/* =========================================================
   LOGIN + LENDER CHECK
   ========================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php" . $lang_param);
    exit();
}

$lender_id = intval($_SESSION['user_id']);


/* =========================================================
   BOOKING ID
   ========================================================= */
$booking_id = isset($_GET['booking_id'])
    ? intval($_GET['booking_id'])
    : 0;

if ($booking_id <= 0) {
    header("Location: active_rentals.php" . $lang_param);
    exit();
}


/* =========================================================
   CSRF TOKEN
   ========================================================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =========================================================
   HANDLE STATUS UPDATE
   ========================================================= */
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = __('lbd_invalid_request');
    } else {

        /*
         * Only these status changes are allowed:
         *
         * Pending  -> Accepted
         * Pending  -> Rejected
         * Accepted -> Delivered
         * Delivered -> Returned
         */

        $new_status = '';

        if ($action === 'accept') {
            $new_status = 'Accepted';
        } elseif ($action === 'reject') {
            $new_status = 'Rejected';
        } elseif ($action === 'delivered') {
            $new_status = 'Delivered';
        } elseif ($action === 'returned') {
            $new_status = 'Returned';
        } elseif ($action === 'save_delivery_date') {
            $new_status = 'SaveDeliveryDate';
        } elseif ($action === 'collect_overdue') {
            $new_status = 'CollectOverduePenalty';
        }

        $delivery_date = trim($_POST['delivery_date'] ?? '');

        if ($new_status === '') {

            $error = __('lbd_invalid_action');

        } elseif ($new_status === 'SaveDeliveryDate') {

            /*
             * Save Delivery Date uses the date submitted by the lender.
             */
            if ($delivery_date === '') {
                $error = 'Please select a delivery date.';
            } else {
                $delivery_date_check = DateTime::createFromFormat('Y-m-d', $delivery_date);

                if (!$delivery_date_check || $delivery_date_check->format('Y-m-d') !== $delivery_date) {
                    $error = 'Please select a valid delivery date.';
                }
            }

        } elseif ($new_status === 'Delivered') {

            /*
             * Mark as Delivered must use the delivery date already saved
             * for this booking. The confirmation form does not need to
             * submit the date again.
             */
            $saved_date_stmt = $conn->prepare("
                SELECT delivery_date
                FROM bookings b
                INNER JOIN equipment e
                    ON b.equipment_id = e.equipment_id
                WHERE b.booking_id = ?
                  AND e.lender_id = ?
                LIMIT 1
            ");

            $saved_delivery_date = '';

            if ($saved_date_stmt) {
                $saved_date_stmt->bind_param('ii', $booking_id, $lender_id);
                $saved_date_stmt->execute();
                $saved_date_result = $saved_date_stmt->get_result();
                $saved_date_row = $saved_date_result ? $saved_date_result->fetch_assoc() : null;
                $saved_delivery_date = trim($saved_date_row['delivery_date'] ?? '');
                $saved_date_stmt->close();
            }

            if ($saved_delivery_date === '') {
                $error = 'Please save the delivery date before marking the equipment as delivered.';
            } else {
                $delivery_date = $saved_delivery_date;
            }

        }

        if ($error !== '') {
            // Validation error; do not update the booking.
        } elseif ($new_status === 'SaveDeliveryDate') {

            $save_date_stmt = $conn->prepare("
                UPDATE bookings b
                INNER JOIN equipment e
                    ON b.equipment_id = e.equipment_id
                SET b.delivery_date = ?
                WHERE b.booking_id = ?
                  AND e.lender_id = ?
                  AND b.status = 'Accepted'
            ");

            if ($save_date_stmt) {
                $save_date_stmt->bind_param(
                    'sii',
                    $delivery_date,
                    $booking_id,
                    $lender_id
                );

                if ($save_date_stmt->execute()) {
                    if ($save_date_stmt->affected_rows > 0) {
                        if ($current_lang === 'kn') {
                            $message = 'ವಿತರಣಾ ದಿನಾಂಕವನ್ನು ಯಶಸ್ವಿಯಾಗಿ ಉಳಿಸಲಾಗಿದೆ.';
                        } elseif ($current_lang === 'hi') {
                            $message = 'डिलीवरी की तारीख सफलतापूर्वक सहेज दी गई है।';
                        } else {
                            $message = 'Delivery date saved successfully.';
                        }
                    } else {
                        $error = 'The delivery date could not be saved. Please make sure the booking is still accepted.';
                    }
                } else {
                    $error = __('lbd_update_failed');
                }

                $save_date_stmt->close();
            } else {
                $error = __('lbd_database_error');
            }

        } elseif ($new_status === 'CollectOverduePenalty') {

            /*
             * Record the lender's confirmation that the overdue penalty
             * was collected. Keep late_charge unchanged as the historical
             * assessed amount.
             */
            $collect_stmt = $conn->prepare("
                UPDATE bookings b
                INNER JOIN equipment e
                    ON b.equipment_id = e.equipment_id
                SET
                    b.late_charge_collected = 1,
                    b.late_charge_collected_amount = b.late_charge,
                    b.late_charge_collected_at = NOW()
                WHERE b.booking_id = ?
                  AND e.lender_id = ?
                  AND b.status IN ('Returned', 'Completed')
                  AND b.return_confirmed = 1
                  AND b.late_charge > 0
                  AND b.late_charge_collected = 0
            ");

            if ($collect_stmt) {
                $collect_stmt->bind_param('ii', $booking_id, $lender_id);

                if ($collect_stmt->execute() && $collect_stmt->affected_rows === 1) {
                    $message = $overdue_lang['success'];
                } else {
                    $error = $overdue_lang['error'];
                }

                $collect_stmt->close();
            } else {
                $error = __('lbd_database_error');
            }

        } else {

            /*
             * IMPORTANT:
             * We verify that this equipment actually belongs
             * to the logged-in lender.
             */

            $update_sql = "
                UPDATE bookings b
                INNER JOIN equipment e
                    ON b.equipment_id = e.equipment_id
                SET b.status = ?,
                    b.delivery_date = CASE WHEN ? = 'Delivered' THEN ? ELSE b.delivery_date END,
                    b.delivery_confirmed = CASE WHEN ? = 'Delivered' THEN 0 ELSE b.delivery_confirmed END,
                    b.return_confirmed = CASE WHEN ? = 'Returned' THEN 0 ELSE b.return_confirmed END,
                    b.delivery_confirmed_at = CASE WHEN ? = 'Delivered' THEN NULL ELSE b.delivery_confirmed_at END,
                    b.return_confirmed_at = CASE WHEN ? = 'Returned' THEN NULL ELSE b.return_confirmed_at END
                WHERE b.booking_id = ?
                  AND e.lender_id = ?
                  AND (
                    (? = 'Delivered' AND b.status = 'Accepted') OR
                    (? = 'Returned' AND b.status IN ('Delivered', 'Overdue') AND b.delivery_confirmed = 1) OR
                    (? NOT IN ('Delivered', 'Returned'))
                  )
            ";

            $update_stmt = $conn->prepare($update_sql);

            if ($update_stmt) {

                $update_stmt->bind_param(
                    "sssssssiisss",
                    $new_status,
                    $new_status,
                    $delivery_date,
                    $new_status,
                    $new_status,
                    $new_status,
                    $new_status,
                    $booking_id,
                    $lender_id,
                    $new_status,
                    $new_status,
                    $new_status
                );

                if ($update_stmt->execute()) {

                    if ($update_stmt->affected_rows > 0) {

                        /*
                         * Notify the renter when the lender performs a
                         * physical delivery or return action.
                         */
                        if ($new_status === 'Delivered' || $new_status === 'Returned') {
                            $notify_stmt = $conn->prepare("
                                SELECT renter_id, request_code
                                FROM bookings
                                WHERE booking_id = ?
                                LIMIT 1
                            ");
                            if ($notify_stmt) {
                                $notify_stmt->bind_param('i', $booking_id);
                                $notify_stmt->execute();
                                $notify_data = $notify_stmt->get_result()->fetch_assoc();
                                $notify_stmt->close();

                                if ($notify_data) {
                                    $renter_id_for_notification = intval($notify_data['renter_id']);
                                    $request_code_for_notification = $notify_data['request_code'] ?? ('#' . $booking_id);

                                    if ($new_status === 'Delivered') {
                                        $notification_title = 'Equipment Delivered';
                                        $notification_message = 'The lender has marked your equipment as delivered for booking ' . $request_code_for_notification . '. Please open your booking details and confirm delivery.';
                                    } else {
                                        $notification_title = 'Equipment Returned';
                                        $notification_message = 'The lender has marked your equipment as returned for booking ' . $request_code_for_notification . '. Please open your booking details and confirm the return.';
                                    }

                                    $insert_notification = $conn->prepare("
                                        INSERT INTO notifications (user_id, title, message, is_read)
                                        VALUES (?, ?, ?, 0)
                                    ");
                                    if ($insert_notification) {
                                        $insert_notification->bind_param(
                                            'iss',
                                            $renter_id_for_notification,
                                            $notification_title,
                                            $notification_message
                                        );
                                        $insert_notification->execute();
                                        $insert_notification->close();
                                    }
                                }
                            }
                        }

                        // Do not make equipment available here. The renter must confirm
                        // the return first; the renter confirmation handler should then
                        // set booking status to Completed and equipment to Available.

                        $message = __('lbd_status_updated');

                    } else {

                        $error = __('lbd_update_failed');
                    }
                } else {

                    $error = __('lbd_update_failed');
                }

                $update_stmt->close();

            } else {

                $error = __('lbd_database_error');
            }
        }
    }
}


/* =========================================================
   NOTIFICATION COUNT
   ========================================================= */
$notif_count = 0;

$notif_check = $conn->query(
    "SHOW TABLES LIKE 'notifications'"
);

if ($notif_check && $notif_check->num_rows > 0) {

    $n_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ?
          AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($n_stmt) {

        $n_stmt->bind_param("i", $lender_id);
        $n_stmt->execute();

        $notif_result = $n_stmt->get_result();

        if ($notif_result) {
            $notif_count = intval(
                $notif_result->fetch_assoc()['cnt'] ?? 0
            );
        }

        $n_stmt->close();
    }
}


/* =========================================================
   FETCH BOOKING
   =========================================================
   b.* is used so this page works with your current booking
   table even if you have extra columns such as:
   quantity, phone_number, id_number, delivery_address.
   ========================================================= */

$sql = "
    SELECT
        b.*,

        e.title AS equipment_title,
        e.category AS equipment_category,
        e.service_location,
        e.image AS equipment_image,
        e.price_per_day,
        e.brand_model,
        e.power_hp,
        e.drive_type,
        e.model_year,
        e.fuel_type,
        e.equipment_condition,

        u.user_id AS renter_user_id,
        u.full_name AS renter_name,
        u.phone AS renter_phone,
        u.email AS renter_email,
        u.address AS renter_address,
        u.profile_pic AS renter_profile_pic

    FROM bookings b

    INNER JOIN equipment e
        ON b.equipment_id = e.equipment_id

    INNER JOIN users u
        ON b.renter_id = u.user_id

    WHERE b.booking_id = ?
      AND e.lender_id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error.");
}

$stmt->bind_param(
    "ii",
    $booking_id,
    $lender_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    header(
        "Location: active_rentals.php" . $lang_param
    );

    exit();
}

$booking = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   VALUES
   ========================================================= */

$status = $booking['status'] ?? 'Pending';
$delivery_confirmed = (int)($booking['delivery_confirmed'] ?? 0) === 1;
$return_confirmed = (int)($booking['return_confirmed'] ?? 0) === 1;
$waiting_delivery_confirmation = ($status === 'Delivered' && !$delivery_confirmed);
$waiting_return_confirmation = ($status === 'Returned' && !$return_confirmed);

$quantity = isset($booking['quantity'])
    ? intval($booking['quantity'])
    : 1;

$delivery_address = $booking['delivery_address']
    ?? $booking['renter_address']
    ?? 'N/A';

$phone_number = $booking['phone_number']
    ?? $booking['renter_phone']
    ?? 'N/A';

$id_number = $booking['id_number'] ?? '';

$total_amount = floatval(
    $booking['total_amount'] ?? 0
);

$advance_amount = floatval(
    $booking['advance_amount'] ?? 0
);

$remaining_cod = isset($booking['remaining_cod'])
    ? floatval($booking['remaining_cod'])
    : max(
        0,
        $total_amount - $advance_amount
    );


/* =========================================================
   STATUS STYLE
   ========================================================= */

$status_class = 'status-pending';
$status_icon = 'fa-clock';

if ($status === 'Accepted') {

    $status_class = 'status-accepted';
    $status_icon = 'fa-calendar-check';

} elseif ($status === 'Delivered') {

    $status_class = 'status-delivered';
    $status_icon = 'fa-truck';

} elseif ($status === 'Returned') {

    $status_class = 'status-returned';
    $status_icon = 'fa-hourglass-half';

} elseif ($status === 'Completed') {

    $status_class = 'status-returned';
    $status_icon = 'fa-circle-check';

} elseif ($status === 'Rejected') {

    $status_class = 'status-rejected';
    $status_icon = 'fa-circle-xmark';

} elseif ($status === 'Overdue') {

    $status_class = 'status-overdue';
    $status_icon = 'fa-triangle-exclamation';
}


/* =========================================================
   IMAGE
   ========================================================= */

$img_path = '';

if (!empty($booking['equipment_image'])) {

    if (
        file_exists(
            __DIR__ . '/uploads/' .
            $booking['equipment_image']
        )
    ) {

        $img_path =
            'uploads/' .
            $booking['equipment_image'];

    } elseif (
        file_exists(
            __DIR__ . '/' .
            $booking['equipment_image']
        )
    ) {

        $img_path =
            $booking['equipment_image'];
    }
}


/* =========================================================
   BACK PAGE
   ========================================================= */

$back_url = $_SESSION['lender_booking_details_return'] ?? '';
if ($back_url === '') {
    $back_url = 'rental_history.php' . $lang_param;
}

?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo __('lbd_page_title'); ?>
    </title>

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
            font-family:
                'Segoe UI',
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }

        body {
            background: #f4f6f9;
            color: #1e293b;
            margin: 0;
            font-weight: 500;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px 30px;
        }

        .top-bar {
            background: #fff;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 18px;
            margin-bottom: 20px;
        }

        .profile-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #334155;
            text-decoration: none;
        }

        .profile-btn:hover {
            background: #0f4c5c;
            color: #fff;
        }

        /* ================= BREADCRUMB ================= */

        .breadcrumb-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #64748b;
        }

        .breadcrumb-custom a {
            color: #0f4c5c;
            text-decoration: none;
            font-weight: 700;
        }

        /* ================= HEADER ================= */

        .page-header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .page-header {
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 4px;
            color: #0f172a;
        }

        .page-subtitle {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
        }

        /* ================= GRID ================= */

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 370px;
            gap: 25px;
            align-items: start;
        }

        .content-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,.02);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .card-title i {
            color: #0f4c5c;
        }

        /* ================= EQUIPMENT ================= */

        .equipment-row {
            display: flex;
            gap: 20px;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .equipment-image {
            width: 145px;
            height: 105px;
            border-radius: 10px;
            overflow: hidden;
            background: #f1f5f9;
            flex-shrink: 0;
        }

        .equipment-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .equipment-image-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 30px;
        }

        .equipment-info h4 {
            font-size: 19px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .equipment-info p {
            margin: 3px 0;
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
        }

        /* ================= SPECIFICATIONS ================= */

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2,1fr);
            gap: 12px;
        }

        .spec-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 13px;
        }

        .spec-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .spec-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            word-break: break-word;
        }

        /* ================= RENTER ================= */

        .renter-box {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #f8fafc;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 18px;
        }

        .renter-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #d1fae5;
            color: #0f4c5c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .renter-info h4 {
            margin: 0 0 3px;
            font-size: 17px;
            font-weight: 900;
        }

        .renter-info p {
            margin: 2px 0;
            color: #64748b;
            font-size: 13px;
        }

        .contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 8px;
            background: #0f4c5c;
            color: #fff;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
        }

        .contact-btn:hover {
            background: #0f4c5c;
            color: #fff;
        }

        /* ================= ADDRESS ================= */

        .address-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
        }

        .address-title {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .address-value {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        /* ================= STATUS ================= */

        .status-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 900;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .status-pending {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-accepted {
            background: #f1f5f9;
            color: #0f4c5c;
        }

        .status-delivered {
            background: #f1f5f9;
            color: #0f4c5c;
        }

        .status-returned {
            background: #f1f5f9;
            color: #0f4c5c;
        }

        .status-rejected,
        .status-overdue {
            background: #f1f5f9;
            color: #0f4c5c;
        }

        /* ================= TIMELINE ================= */

        .progress-timeline {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0 10px;
        }

        .progress-timeline::before {
            content: '';
            position: absolute;
            top: 16px;
            left: 30px;
            right: 30px;
            height: 3px;
            background: #e2e8f0;
        }

        .timeline-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto auto 8px;
            border: 3px solid #fff;
            font-size: 12px;
            font-weight: 900;
        }

        .timeline-step.completed .step-icon {
            background: #0f4c5c;
            color: #fff;
        }

        .timeline-step.current .step-icon {
            background: #0f4c5c;
            color: #fff;
        }

        .step-title {
            font-size: 11px;
            font-weight: 800;
        }

        /* ================= ACTIONS ================= */

        .action-box {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-accept {
            background: #0f4c5c;
            color: #fff;
        }

        /* Make the renter-details link clearly readable as a primary action. */
        .renter-details-btn,
        .renter-details-btn:link,
        .renter-details-btn:visited {
            background: linear-gradient(135deg, #1455a6, #092d68) !important;
            color: #ffffff !important;
            border: 1px solid #0b397a !important;
            font-weight: 800 !important;
            min-height: 46px;
            text-align: center;
            box-shadow: 0 5px 14px rgba(9, 45, 104, .22);
        }

        .renter-details-btn i {
            color: #ffffff !important;
        }

        .renter-details-btn:hover,
        .renter-details-btn:focus {
            background: linear-gradient(135deg, #0b4699, #061f50) !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        .btn-reject {
            background: #f1f5f9;
            color: #0f4c5c;
        }

        .btn-delivered {
            background: #0f4c5c;
            color: #fff;
        }

        .btn-returned {
            background: #0f4c5c;
            color: #fff;
        }

        .btn-accept:hover,
        .btn-delivered:hover,
        .btn-returned:hover {
            opacity: .9;
        }

        .btn-reject:hover {
            background: #fecaca;
        }

        .completed-message {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #0f4c5c;
            padding: 13px;
            border-radius: 10px;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
        }

        .alert-custom {
            border-radius: 10px;
            font-weight: 700;
        }

        /* ================= SUMMARY ================= */

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 13px;
            color: #475569;
            font-size: 14px;
            font-weight: 600;
        }

        .summary-row .amount {
            color: #0f4c5c;
            font-weight: 900;
        }

        .summary-total {
            border-top: 1px dashed #cbd5e1;
            padding-top: 13px;
            margin-top: 13px;
            font-weight: 900;
            color: #0f172a;
        }

        /* ================= RESPONSIVE ================= */

        @media(max-width: 1100px) {

            .details-grid {
                grid-template-columns: 1fr;
            }

        }

        @media(max-width: 768px) {

            .main-content {
                margin-left: 75px;
                padding: 15px;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }

            .equipment-row {
                align-items: flex-start;
            }

        }

        @media(max-width: 500px) {

            .equipment-row {
                flex-direction: column;
            }

            .equipment-image {
                width: 100%;
                height: 160px;
            }

            .progress-timeline {
                overflow-x: auto;
            }

            .step-title {
                font-size: 9px;
            }
        }

        /* ================= BLUE THEME ================= */

        .text-danger,
        .text-success,
        .text-primary {
            color: #0f4c5c !important;
        }

        .bg-success,
        .bg-danger {
            background-color: #0f4c5c !important;
        }

        .btn-outline-success {
            color: #0f4c5c !important;
            border-color: #0f4c5c !important;
        }

        .btn-outline-success:hover {
            background: #0f4c5c !important;
            color: #fff !important;
        }

    

/* BlueField Lending interface theme — content area only; sidebar intentionally untouched */
:root{
  --bluefield-navy:#092d68;
  --bluefield-blue:#1455a6;
  --bluefield-mid:#2f70c7;
  --bluefield-ink:#101b49;
  --bluefield-muted:#496887;
  --bluefield-line:rgba(69,126,190,.20);
  --bluefield-glass:rgba(255,255,255,.66);
  --bluefield-shadow:0 12px 30px rgba(31,82,145,.10);
}
body{
  background:linear-gradient(135deg,#dcecff 0%,#edf6ff 48%,#d7eaff 100%)!important;
  color:var(--bluefield-ink)!important;
}
/* Keep layout positioning and dimensions; only update the content surface */
.main-content,.main-wrapper,.content,.wrap{
  background:transparent!important;
  color:var(--bluefield-ink)!important;
}
.top-nav-bar,.top-bar,.topbar,.top-navbar,.top-search-bar,.top,.top-banner{
  background:linear-gradient(110deg,rgba(9,45,104,.96),rgba(20,85,166,.94))!important;
  color:#fff!important;
  border:1px solid rgba(255,255,255,.38)!important;
  border-radius:18px!important;
  box-shadow:0 10px 26px rgba(12,54,112,.16)!important;
}
.top-nav-bar a,.top-bar a,.topbar a,.top-navbar a,.top-search-bar a,.top-banner a,
.top-nav-bar .btn,.top-bar .btn,.topbar .btn,.top-navbar .btn{color:inherit;}
.page-header,.page-head,.page-header-box,.page-header-container,.hero,
.heading-row,.page-header-row{
  color:var(--bluefield-ink)!important;
}
.page-title,.page-header h1,.page-header h2,.page-header h3,.page-head h1,
.hero h1,.heading h1,.main-content h1,.main-content h2,.main-content h3,.main-content h4,.main-content h5,.main-wrapper h1,.main-wrapper h2,.main-wrapper h3,.main-wrapper h4,.main-wrapper h5,.content h1,.content h2,.content h3,.content h4,.content h5,.wrap h1,.wrap h2,.wrap h3,.wrap h4,.wrap h5,.form-container h1,.form-container h2,.form-container h3,.details-container h1,.details-container h2,.details-container h3{
  color:var(--bluefield-ink)!important;
}
p,.muted,.page-subtitle,.hero p,.label,.meta-text{color:var(--bluefield-muted);}
.card,.cardx,.content-card,.panel,.summary-card,.feature-card,.equipment-card,
.review-entry,.notification-card,.empty-state,.field,.spec-box,.meta-item,
.detail-item,.equipment-box,.lender-header,.proof,.form-container,.details-container,
.main-content .table-responsive,.chart-container,.payment-box,.upload-box,
.form-section,.info-card,.stat-card,.metric-card,.section-card{
  background:var(--bluefield-glass)!important;
  border:1px solid rgba(255,255,255,.88)!important;
  border-radius:20px!important;
  box-shadow:var(--bluefield-shadow),inset 0 1px 0 rgba(255,255,255,.92)!important;
  backdrop-filter:blur(16px);
  -webkit-backdrop-filter:blur(16px);
}
.card:hover,.equipment-card:hover,.feature-card:hover,.summary-card:hover{
  box-shadow:0 17px 36px rgba(31,82,145,.15)!important;
  border-color:rgba(69,126,190,.35)!important;
}
.card-header,.card-footer,.action-footer,.card-footer-actions{
  background:rgba(222,238,255,.66)!important;
  border-color:var(--bluefield-line)!important;
}
input:not([type=checkbox]):not([type=radio]),select,textarea,.form-control,.form-select,.lang-select{
  background:rgba(255,255,255,.82)!important;
  border:1px solid rgba(56,111,177,.24)!important;
  border-radius:11px!important;
  color:var(--bluefield-ink)!important;
  box-shadow:none!important;
}
input:focus,select:focus,textarea:focus,.form-control:focus,.form-select:focus{
  border-color:#4b8bd4!important;
  box-shadow:0 0 0 3px rgba(47,112,199,.14)!important;
}
.btn-primary,.btn-main,.btn-agro,.btn-view,.btn-rent-now,.btn-back,.browse,
button[type=submit],.top-banner .btn{
  background:linear-gradient(135deg,#236ac0,#0b4699)!important;
  border:1px solid rgba(255,255,255,.25)!important;
  color:#fff!important;
  border-radius:11px!important;
  box-shadow:0 6px 15px rgba(16,74,151,.18)!important;
}
.btn-primary:hover,.btn-main:hover,.btn-agro:hover,.btn-view:hover,.btn-rent-now:hover,
.btn-back:hover,.browse:hover,button[type=submit]:hover{
  background:linear-gradient(135deg,#1858a9,#082f72)!important;
  color:#fff!important;
  transform:translateY(-1px);
}
.btn-outline-primary,.btn-outline-secondary,.btn-outline-dark{
  border-color:rgba(30,91,163,.35)!important;color:#164b91!important;
}
.btn-outline-primary:hover,.btn-outline-secondary:hover,.btn-outline-dark:hover{
  background:#dcecff!important;color:#0b397a!important;
}
.main-content a:not(.btn):not(.nav-link),.main-wrapper a:not(.btn):not(.nav-link),.content a:not(.btn):not(.nav-link),.wrap a:not(.btn):not(.nav-link),.form-container a:not(.btn):not(.nav-link),.details-container a:not(.btn):not(.nav-link){color:#1556a2;}
.main-content a:not(.btn):not(.nav-link):hover,.main-wrapper a:not(.btn):not(.nav-link):hover,.content a:not(.btn):not(.nav-link):hover,.wrap a:not(.btn):not(.nav-link):hover,.form-container a:not(.btn):not(.nav-link):hover,.details-container a:not(.btn):not(.nav-link):hover{color:#082f72;}
table,.table{color:var(--bluefield-ink)!important;--bs-table-bg:transparent;}
.table thead th,thead th{background:rgba(203,225,250,.72)!important;color:#173f78!important;border-color:var(--bluefield-line)!important;}
.table td,.table th,tbody td{border-color:rgba(69,126,190,.15)!important;}
hr,.border-top,.border-bottom{border-color:var(--bluefield-line)!important;}
.alert,.notice,.warning{
  border-radius:14px!important;
  box-shadow:0 7px 18px rgba(31,82,145,.06);
}
.breadcrumb-custom,.breadcrumb-nav,.breadcrumb{color:#52769e!important;}
.badge{letter-spacing:.1px;}
@media(max-width:767.98px){
  .main-content,.main-wrapper,.content,.wrap{padding-left:14px!important;padding-right:14px!important;}
  .top-nav-bar,.top-bar,.topbar,.top-navbar,.top-search-bar{border-radius:15px!important;}
}
@media(prefers-reduced-motion:reduce){.card,.equipment-card,.feature-card,.summary-card,button,a{transition:none!important;}}

</style>

</head>

<body>


<?php include 'lender_sidebar.php'; ?>


<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<div class="main-content">


    <!-- TOP BAR -->

    <div class="top-bar">

        <form
            action="lender_booking_details.php"
            method="GET"
            class="mb-0"
        >

            <input
                type="hidden"
                name="booking_id"
                value="<?php echo $booking_id; ?>"
            >

            <select
                name="lang"
                class="form-select form-select-sm fw-bold"
                onchange="this.form.submit()"
            >

                <option
                    value="en"
                    <?php
                    echo ($current_lang === 'en')
                        ? 'selected'
                        : '';
                    ?>
                >
                    English
                </option>

                <option
                    value="kn"
                    <?php
                    echo ($current_lang === 'kn')
                        ? 'selected'
                        : '';
                    ?>
                >
                    ಕನ್ನಡ (Kannada)
                </option>

                <option
                    value="hi"
                    <?php
                    echo ($current_lang === 'hi')
                        ? 'selected'
                        : '';
                    ?>
                >
                    हिंदी (Hindi)
                </option>

            </select>

        </form>


        <a
            href="notifications.php<?php echo $lang_param; ?>"
            class="position-relative text-dark"
        >

            <i class="fa-solid fa-bell fa-lg"></i>

            <?php if ($notif_count > 0): ?>

                <span
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                >
                    <?php echo $notif_count; ?>
                </span>

            <?php endif; ?>

        </a>


        <a
            href="profile.php<?php echo $lang_param; ?>"
            class="profile-btn"
        >

            <i class="fa-solid fa-user"></i>

        </a>

    </div>


    <!-- BREADCRUMB -->

    <div class="breadcrumb-custom">

        <a
            href="lender_dashboard.php<?php echo $lang_param; ?>"
        >
            <?php echo __('dashboard'); ?>
        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <a
            href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>"
        >
            <?php echo __('lbd_rentals'); ?>
        </a>

        <i class="fa-solid fa-chevron-right"></i>

        <span>
            <?php echo __('lbd_booking_details'); ?>
        </span>

    </div>


    <!-- PAGE HEADER -->

    <div class="page-header-box">

        <div>

            <h1 class="page-header">

                <?php echo __('lbd_page_title'); ?>

            </h1>

            <p class="page-subtitle">

                <?php echo __('lbd_page_subtitle'); ?>

            </p>

        </div>


        <a
            href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>"
            class="btn btn-outline-success fw-bold"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            <?php echo __('lbd_back'); ?>

        </a>

    </div>


    <!-- ALERTS -->

    <?php if ($message): ?>

        <div class="alert alert-success alert-custom">

            <i class="fa-solid fa-circle-check me-2"></i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger alert-custom">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="details-grid">


        <!-- =================================================
             LEFT COLUMN
             ================================================= -->

        <div>


            <!-- EQUIPMENT + BOOKING INFORMATION -->

            <div class="content-card">

                <div class="card-title">

                    <i class="fa-solid fa-circle-info"></i>

                    <?php echo __('lbd_booking_information'); ?>

                </div>


                <div class="equipment-row">

                    <div class="equipment-image">

                        <?php if ($img_path): ?>

                            <img
                                src="<?php echo htmlspecialchars($img_path); ?>"
                                alt="Equipment"
                            >

                        <?php else: ?>

                            <div class="equipment-image-placeholder">

                                <i class="fa-solid fa-tractor"></i>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="equipment-info">

                        <h4>

                            <?php
                            echo htmlspecialchars(
                                $booking['equipment_title']
                            );
                            ?>

                        </h4>

                        <p>

                            <i class="fa-solid fa-layer-group me-1"></i>

                            <?php echo __('lbd_category'); ?>:

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $booking['equipment_category']
                                );
                                ?>

                            </strong>

                        </p>

                        <p>

                            <i class="fa-solid fa-location-dot me-1 text-danger"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['service_location']
                            );
                            ?>

                        </p>

                    </div>

                </div>


                <div class="specs-grid">


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_booking_id'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-barcode text-muted me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['request_code']
                                ?? ('#' . $booking['booking_id'])
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_booking_date'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-calendar-days text-muted me-1"></i>

                            <?php
                            echo date(
                                'd M Y, h:i A',
                                strtotime(
                                    $booking['created_at']
                                )
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_renter_name'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-user text-muted me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['renter_name']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_phone'); ?>

                        </div>

                        <div class="spec-value">

                            <i class="fa-solid fa-phone text-muted me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $phone_number
                            );
                            ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_quantity'); ?>

                        </div>

                        <div class="spec-value">

                            <?php echo $quantity; ?>

                        </div>

                    </div>


                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_status'); ?>

                        </div>

                        <div class="spec-value">

                            <span
                                class="status-badge <?php echo $status_class; ?>"
                            >

                                <i
                                    class="fa-solid <?php echo $status_icon; ?>"
                                ></i>

                                <?php
                                echo htmlspecialchars(
                                    $waiting_delivery_confirmation ? 'Delivered - Waiting for Renter Confirmation' : ($waiting_return_confirmation ? 'Returned - Waiting for Renter Confirmation' : (($status === 'Delivered' && $delivery_confirmed) ? 'In Use' : $status))
                                );
                                ?>

                            </span>

                        </div>

                    </div>

                </div>


                <!-- RENTAL PERIOD -->

                <div
                    class="address-box"
                    style="margin-top:15px;"
                >

                    <div class="address-title">

                        <i class="fa-solid fa-calendar-week me-1"></i>

                        <?php echo __('lbd_rental_period'); ?>

                    </div>

                    <div class="address-value">

                        <?php
                        echo date(
                            'd M Y',
                            strtotime(
                                $booking['start_date']
                            )
                        );
                        ?>

                        &nbsp; – &nbsp;

                        <?php
                        echo date(
                            'd M Y',
                            strtotime(
                                $booking['end_date']
                            )
                        );
                        ?>

                        <span
                            class="badge bg-success ms-2"
                        >

                            <?php
                            echo intval(
                                $booking['total_days']
                            );
                            ?>

                            <?php echo __('lbd_days'); ?>

                        </span>

                    </div>

                </div>


                <!-- DELIVERY ADDRESS -->

                <div
                    class="address-box"
                    style="margin-top:12px;"
                >

                    <div class="address-title">

                        <i class="fa-solid fa-location-crosshairs me-1 text-danger"></i>

                        <?php echo __('lbd_delivery_address'); ?>

                    </div>

                    <div class="address-value">

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $delivery_address
                            )
                        );
                        ?>

                    </div>

                </div>

            </div>


            <!-- RENTER DETAILS -->

            <div class="content-card">

                <div class="card-title">

                    <i class="fa-solid fa-user"></i>

                    <?php echo __('lbd_renter_information'); ?>

                </div>


                <div class="renter-box">

                    <div class="renter-avatar">

                        <i class="fa-solid fa-user"></i>

                    </div>


                    <div class="renter-info">

                        <h4>

                            <?php
                            echo htmlspecialchars(
                                $booking['renter_name']
                            );
                            ?>

                        </h4>

                        <p>

                            <i class="fa-solid fa-envelope me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $booking['renter_email']
                            );
                            ?>

                        </p>

                        <p>

                            <i class="fa-solid fa-phone me-1"></i>

                            <?php
                            echo htmlspecialchars(
                                $phone_number
                            );
                            ?>

                        </p>


                    </div>

                </div>


                <?php if ($id_number): ?>

                    <div class="spec-item">

                        <div class="spec-label">

                            <?php echo __('lbd_id_number'); ?>

                        </div>

                        <div class="spec-value">

                            <?php
                            echo htmlspecialchars(
                                $id_number
                            );
                            ?>

                        </div>

                    </div>

                <?php endif; ?>

            </div>


            <!-- BOOKING STATUS -->

            <div class="content-card">

                <div class="status-card-header">

                    <div class="card-title mb-0">

                        <i class="fa-solid fa-bars-progress"></i>

                        <?php echo __('lbd_booking_status'); ?>

                    </div>


                    <span
                        class="status-badge <?php echo $status_class; ?>"
                    >

                        <i
                            class="fa-solid <?php echo $status_icon; ?>"
                        ></i>

                        <?php echo htmlspecialchars($waiting_delivery_confirmation ? 'Delivered - Waiting for Renter Confirmation' : ($waiting_return_confirmation ? 'Returned - Waiting for Renter Confirmation' : (($status === 'Delivered' && $delivery_confirmed) ? 'In Use' : $status))); ?>

                    </span>

                </div>

                <?php if ($waiting_delivery_confirmation): ?>
                    <div class="alert alert-warning mt-3 mb-0" role="status">
                        <i class="fa-solid fa-hourglass-half me-2"></i>
                        The lender has marked this equipment as delivered. Waiting for the renter to confirm delivery. The rental will become In Use only after confirmation.
                    </div>
                <?php elseif ($waiting_return_confirmation): ?>
                    <div class="alert alert-warning mt-3 mb-0" role="status">
                        <i class="fa-solid fa-hourglass-half me-2"></i>
                        The lender has marked this equipment as returned. Waiting for the renter to confirm the return. The booking will be Completed after confirmation.
                    </div>
                <?php elseif ($status === 'Delivered' && $delivery_confirmed): ?>
                    <div class="alert alert-success mt-3 mb-0" role="status">
                        <i class="fa-solid fa-circle-check me-2"></i>Delivery confirmed by the renter. Equipment is In Use.
                    </div>
                <?php endif; ?>

                <?php

                $pending = ($status === 'Pending');
                $accepted = ($status === 'Accepted');

                // A lender marking delivery advances the timeline to Delivered,
                // but the rental is not In Use until the renter confirms it.
                $delivery_marked = in_array(
                    $status,
                    ['Delivered', 'Overdue', 'Returned', 'Completed'],
                    true
                );
                $delivered = $delivery_marked && $delivery_confirmed;

                // A lender marking return advances the timeline to Returned,
                // but completion waits for the renter's return confirmation.
                $return_marked = in_array($status, ['Returned', 'Completed'], true);
                $returned = $return_marked && $return_confirmed;
                $completed = ($status === 'Completed' && $return_confirmed);

                ?>


                <div class="progress-timeline">


                    <div
                        class="timeline-step completed"
                    >

                        <div class="step-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_submitted'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo (!$pending)
                            ? 'completed'
                            : 'current';
                        ?>"
                    >

                        <div class="step-icon">

                            <?php if (!$pending): ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                2

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_pending_approval'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo $delivery_marked
                            ? 'completed'
                            : ($accepted ? 'current' : '');
                        ?>"
                    >

                        <div class="step-icon">

                            <?php
                            if ($delivery_marked):
                            ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                3

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_accepted'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo $delivered
                            ? 'completed'
                            : ($delivery_marked ? 'current' : '');
                        ?>"
                    >

                        <div class="step-icon">

                            <?php if ($delivered): ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                4

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_delivered'); ?>

                        </div>

                    </div>


                    <div
                        class="timeline-step
                        <?php
                        echo $returned
                            ? 'completed'
                            : ($return_marked ? 'current' : '');
                        ?>"
                    >

                        <div class="step-icon">

                            <?php if ($returned): ?>

                                <i class="fa-solid fa-check"></i>

                            <?php else: ?>

                                5

                            <?php endif; ?>

                        </div>

                        <div class="step-title">

                            <?php echo __('lbd_returned'); ?>

                        </div>

                    </div>

                    <div class="timeline-step <?php echo $completed ? 'completed' : ''; ?>">
                        <div class="step-icon">
                            <?php if ($completed): ?>
                                <i class="fa-solid fa-check"></i>
                            <?php else: ?>
                                6
                            <?php endif; ?>
                        </div>
                        <div class="step-title">Completed</div>
                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             RIGHT COLUMN
             ================================================= -->

        <div>


            <!-- ORDER SUMMARY -->

            <div
                class="content-card"
                style="position:sticky;top:20px;"
            >

                <div class="card-title">

                    <i class="fa-solid fa-receipt"></i>

                    <?php echo __('lbd_order_summary'); ?>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_price_per_day'); ?>
                    </span>

                    <strong>
                        ₹<?php
                        echo number_format(
                            $booking['price_per_day'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_total_days'); ?>
                    </span>

                    <strong>
                        <?php
                        echo intval(
                            $booking['total_days']
                        );
                        ?>
                        <?php echo __('lbd_days'); ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_quantity'); ?>
                    </span>

                    <strong>
                        <?php echo $quantity; ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_total_rent'); ?>
                    </span>

                    <span class="amount">

                        ₹<?php
                        echo number_format(
                            $total_amount,
                            2
                        );
                        ?>

                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_advance'); ?>
                    </span>

                    <strong>

                        ₹<?php
                        echo number_format(
                            $advance_amount,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        <?php echo __('lbd_remaining'); ?>
                    </span>

                    <strong class="text-danger">

                        ₹<?php
                        echo number_format(
                            $remaining_cod,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <div class="summary-row summary-total">

                    <span>
                        <?php echo __('lbd_payment_method'); ?>
                    </span>

                    <span class="badge bg-light text-dark border">

                        <?php echo __('lbd_cash'); ?>

                    </span>

                </div>


                <!-- RENTER DETAILS: only this booking's authorized lender can access it -->
                <a
                    href="renter_details.php?booking_id=<?php echo (int)$booking_id; ?>&amp;lang=<?php echo urlencode($current_lang); ?>"
                    class="action-btn btn-accept btn renter-details-btn"
                    style="text-decoration:none;margin-top:14px;"
                >
                    <i class="fa-solid fa-id-card"></i>
                    <?php
                    echo $current_lang === 'kn' ? 'ಬಾಡಿಗೆದಾರರ ವಿವರಗಳನ್ನು ವೀಕ್ಷಿಸಿ'
                        : ($current_lang === 'hi' ? 'किराएदार का विवरण देखें' : 'View Renter Details');
                    ?>
                </a>

                <!-- =========================================
                     LENDER ACTIONS
                     ========================================= -->

                <div
                    style="
                    margin-top:22px;
                    border-top:1px solid #e2e8f0;
                    padding-top:20px;
                    "
                >

                    <div class="card-title">

                        <i class="fa-solid fa-hand-pointer"></i>

                        <?php echo __('lbd_actions'); ?>

                    </div>


                    <div class="action-box">


                        <!-- PENDING -->

                        <?php if ($status === 'Pending'): ?>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_accept')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="accept"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-accept"
                                >

                                    <i class="fa-solid fa-check"></i>

                                    <?php echo __('lbd_accept'); ?>

                                </button>

                            </form>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_reject')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="reject"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-reject"
                                >

                                    <i class="fa-solid fa-xmark"></i>

                                    <?php echo __('lbd_reject'); ?>

                                </button>

                            </form>


                        <!-- ACCEPTED -->

                        <?php elseif ($status === 'Accepted'): ?>


                            <div
                                class="alert alert-info mb-0"
                                style="font-size:13px;"
                            >

                                <i class="fa-solid fa-truck me-1"></i>

                                <?php
                                echo __('lbd_delivery_instruction');
                                ?>

                            </div>


                            <form method="POST" style="margin-bottom:10px;">

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <div style="margin-bottom:12px;">

                                    <label
                                        for="delivery_date"
                                        style="display:block; margin-bottom:6px; font-size:13px; font-weight:800; color:#334155;"
                                    >
                                        <?php
                                        if ($current_lang === 'kn') {
                                            echo 'ವಿತರಣಾ ದಿನಾಂಕ';
                                        } elseif ($current_lang === 'hi') {
                                            echo 'डिलीवरी की तारीख';
                                        } else {
                                            echo 'Delivery Date';
                                        }
                                        ?>
                                    </label>

                                    <input
                                        type="date"
                                        id="delivery_date"
                                        name="delivery_date"
                                        class="form-control"
                                        value="<?php echo htmlspecialchars($booking['delivery_date'] ?? ''); ?>"
                                        min="<?php echo date('Y-m-d'); ?>"
                                        required
                                    >

                                </div>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="save_delivery_date"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-delivered"
                                >

                                    <i class="fa-solid fa-floppy-disk"></i>

                                    <?php
                                    if ($current_lang === 'kn') {
                                        echo 'ವಿತರಣಾ ದಿನಾಂಕ ಉಳಿಸಿ';
                                    } elseif ($current_lang === 'hi') {
                                        echo 'डिलीवरी की तारीख सहेजें';
                                    } else {
                                        echo 'Save Delivery Date';
                                    }
                                    ?>

                                </button>

                            </form>

                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_delivered')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delivered"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-delivered"
                                >

                                    <i class="fa-solid fa-truck"></i>

                                    <?php echo __('lbd_mark_delivered'); ?>

                                </button>

                            </form>


                        <!-- DELIVERED -->

                        <?php elseif (in_array($status, ['Delivered', 'Overdue'], true) && $delivery_confirmed): ?>


                            <div
                                class="alert alert-warning mb-0"
                                style="font-size:13px;"
                            >

                                <i class="fa-solid fa-rotate-left me-1"></i>

                                <?php
                                echo __('lbd_return_instruction');
                                ?>

                            </div>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        '<?php echo addslashes(__('lbd_confirm_returned')); ?>'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $_SESSION['csrf_token']
                                    );
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="returned"
                                >

                                <button
                                    type="submit"
                                    class="action-btn btn-returned"
                                >

                                    <i class="fa-solid fa-rotate-left"></i>

                                    <?php echo __('lbd_mark_returned'); ?>

                                </button>

                            </form>


                        <?php elseif ($status === 'Delivered' && !$delivery_confirmed): ?>
                            <div class="alert alert-warning mb-0" style="font-size:13px;">
                                <i class="fa-solid fa-hourglass-half me-1"></i>
                                Waiting for the renter to confirm delivery. The equipment will be considered In Use after confirmation.
                            </div>

                        <!-- RETURNED -->

                        <?php elseif ($status === 'Returned' && !$return_confirmed): ?>
                            <div class="alert alert-warning mb-0" style="font-size:13px;">
                                <i class="fa-solid fa-hourglass-half me-1"></i>
                                Waiting for the renter to confirm the return. This booking is not completed yet.
                            </div>

                        <?php elseif ($status === 'Returned' && $return_confirmed): ?>


                            <div class="completed-message">

                                <i
                                    class="fa-solid fa-circle-check me-1"
                                ></i>

                                <?php
                                echo __('lbd_completed');
                                ?>

                            </div>


                        <!-- COMPLETED -->

                        <?php elseif ($status === 'Completed'): ?>

                            <div class="completed-message">
                                <i class="fa-solid fa-circle-check me-1"></i>
                                <?php
                                if ($current_lang === 'kn') {
                                    echo 'ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿದೆ. ಬಾಡಿಗೆದಾರರು ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ.';
                                } elseif ($current_lang === 'hi') {
                                    echo 'किराया पूरा हो गया है। किराएदार ने वापसी की पुष्टि कर दी है।';
                                } else {
                                    echo 'Rental completed. The renter has confirmed the return.';
                                }
                                ?>
                            </div>

                        <!-- REJECTED -->

                        <?php elseif ($status === 'Rejected'): ?>


                            <div
                                class="completed-message"
                                style="
                                background:#f1f5f9;
                                color:#0f4c5c;
                                border-color:#e2e8f0;
                                "
                            >

                                <i
                                    class="fa-solid fa-circle-xmark me-1"
                                ></i>

                                <?php
                                echo __('lbd_rejected_message');
                                ?>

                            </div>


                        <?php endif; ?>

                        <?php
                        $overdue_amount = (float)($booking['late_charge'] ?? 0);
                        $penalty_is_collected = (int)($booking['late_charge_collected'] ?? 0) === 1;
                        $show_overdue_panel = $overdue_amount > 0;
                        $can_collect_penalty = in_array($status, ['Returned', 'Completed'], true)
                            && $return_confirmed
                            && $overdue_amount > 0;
                        ?>

                        <?php if ($show_overdue_panel): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <h6 class="fw-bold mb-2">
                                    <i class="fa-solid fa-money-bill-wave me-1"></i>
                                    <?php echo htmlspecialchars($overdue_lang['title']); ?>
                                </h6>

                                <p class="mb-2">
                                    <?php echo htmlspecialchars($overdue_lang['assessed']); ?>
                                    <strong>₹<?php echo number_format($overdue_amount, 2); ?></strong>
                                </p>

                                <?php if ($penalty_is_collected): ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                        <strong><?php echo htmlspecialchars($overdue_lang['collected']); ?></strong><br>
                                        <?php echo htmlspecialchars($overdue_lang['amount_collected']); ?>
                                        ₹<?php echo number_format((float)($booking['late_charge_collected_amount'] ?? 0), 2); ?><br>
                                        <?php echo htmlspecialchars($overdue_lang['collection_date']); ?>
                                        <?php
                                        echo !empty($booking['late_charge_collected_at'])
                                            ? htmlspecialchars(date('d M Y, h:i A', strtotime($booking['late_charge_collected_at'])))
                                            : htmlspecialchars($overdue_lang['recorded']);
                                        ?>
                                    </div>
                                <?php elseif ($can_collect_penalty): ?>
                                    <p class="small mb-2">
                                        <?php echo htmlspecialchars($overdue_lang['return_confirmed']); ?>
                                        <?php echo htmlspecialchars($overdue_lang['collect_instruction']); ?>
                                    </p>

                                    <?php
                                    $confirm_message = str_replace(
                                        '{amount}',
                                        '₹' . number_format($overdue_amount, 2),
                                        $overdue_lang['confirm']
                                    );
                                    ?>
                                    <form method="POST"
                                          onsubmit="return confirm(<?php echo htmlspecialchars(json_encode($confirm_message), ENT_QUOTES, 'UTF-8'); ?>);">
                                        <input type="hidden" name="csrf_token"
                                               value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="collect_overdue">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fa-solid fa-check-circle me-1"></i>
                                            <?php echo htmlspecialchars($overdue_lang['button']); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <p class="small mb-0">
                                        <?php echo htmlspecialchars($overdue_lang['waiting']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>