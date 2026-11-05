
<?php
session_start();

require_once 'includes/config.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'hi', 'kn'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

require_once 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$lender_id = (int) $_SESSION['user_id'];
$current_lang = $_SESSION['lang'] ?? 'en';

if (!in_array($current_lang, ['en', 'hi', 'kn'], true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

$lang_param = '?lang=' . urlencode($current_lang);

/* Mark one notification as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = (int) ($_POST['notification_id'] ?? 0);

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = ? AND user_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("ii", $notification_id, $lender_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: lender_notifications.php" . $lang_param);
    exit();
}

/* Mark all notifications as read */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($stmt) {
        $stmt->bind_param("i", $lender_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: lender_notifications.php" . $lang_param);
    exit();
}

/* Fetch notifications */
$notifications = [];
$notif_count = 0;

$table_check = $conn->query("SHOW TABLES LIKE 'notifications'");

if ($table_check && $table_check->num_rows > 0) {
    $count_stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM notifications
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");

    if ($count_stmt) {
        $count_stmt->bind_param("i", $lender_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $notif_count = (int) ($count_result['cnt'] ?? 0);
        $count_stmt->close();
    }

    $stmt = $conn->prepare("
        SELECT notification_id, title, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC, notification_id DESC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $lender_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();
    }
}


/* Translate lender notification titles/messages while preserving names, equipment,
   phone numbers, request IDs, dates, and amounts from the database. */
function localize_lender_notification($title, $message, $lang)
{
    $title = trim((string)$title);
    $message = trim((string)$message);
    if ($lang === 'en') return [$title, $message];

    $titles = [
      'hi' => [
        'New Rental Request'=>'नया किराया अनुरोध',
        'Booking Accepted'=>'बुकिंग स्वीकार की गई',
        'Booking Rejected'=>'बुकिंग अस्वीकार की गई',
        'Booking Cancelled by Renter'=>'किरायेदार ने बुकिंग रद्द की',
        'Delivery Confirmed'=>'डिलीवरी की पुष्टि हुई',
        'Equipment Returned'=>'उपकरण वापस किया गया',
        'Overdue Rental'=>'किराया अवधि समाप्त',
        'Equipment Return Overdue'=>'उपकरण लौटाने में देरी',
        'Rental Ends Tomorrow'=>'किराये की अवधि कल समाप्त होगी',
        'New Review'=>'नई समीक्षा',
        'New Customer Review'=>'ग्राहक की नई समीक्षा',
        'Rental Request'=>'किराये का अनुरोध',
        'Booking Request'=>'बुकिंग अनुरोध',
        'Rental Completed'=>'किराया पूरा हुआ',
        'Rental Marked Delivered'=>'उपकरण डिलीवर किया गया',
        'Return Requested'=>'वापसी का अनुरोध',
        'Return Confirmed'=>'वापसी की पुष्टि हुई',
        'Booking Cancelled'=>'बुकिंग रद्द की गई',
        'Payment Received'=>'भुगतान प्राप्त हुआ',
        'Rental Reminder'=>'किराये की याद दिलाना',
      ],
      'kn' => [
        'New Rental Request'=>'ಹೊಸ ಬಾಡಿಗೆ ವಿನಂತಿ',
        'Booking Accepted'=>'ಬುಕಿಂಗ್ ಸ್ವೀಕರಿಸಲಾಗಿದೆ',
        'Booking Rejected'=>'ಬುಕಿಂಗ್ ತಿರಸ್ಕರಿಸಲಾಗಿದೆ',
        'Booking Cancelled by Renter'=>'ಬಾಡಿಗೆದಾರರು ಬುಕಿಂಗ್ ರದ್ದುಗೊಳಿಸಿದ್ದಾರೆ',
        'Delivery Confirmed'=>'ವಿತರಣೆಯನ್ನು ದೃಢೀಕರಿಸಲಾಗಿದೆ',
        'Equipment Returned'=>'ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಲಾಗಿದೆ',
        'Overdue Rental'=>'ಅವಧಿ ಮೀರಿದ ಬಾಡಿಗೆ',
        'Equipment Return Overdue'=>'ಉಪಕರಣ ಹಿಂತಿರುಗಿಸುವಿಕೆ ವಿಳಂಬವಾಗಿದೆ',
        'Rental Ends Tomorrow'=>'ಬಾಡಿಗೆ ನಾಳೆ ಮುಕ್ತಾಯಗೊಳ್ಳುತ್ತದೆ',
        'New Review'=>'ಹೊಸ ವಿಮರ್ಶೆ',
        'New Customer Review'=>'ಗ್ರಾಹಕರ ಹೊಸ ವಿಮರ್ಶೆ',
        'Rental Request'=>'ಬಾಡಿಗೆ ವಿನಂತಿ',
        'Booking Request'=>'ಬುಕಿಂಗ್ ವಿನಂತಿ',
        'Rental Completed'=>'ಬಾಡಿಗೆ ಪೂರ್ಣಗೊಂಡಿದೆ',
        'Rental Marked Delivered'=>'ಉಪಕರಣವನ್ನು ವಿತರಿಸಲಾಗಿದೆ',
        'Return Requested'=>'ಹಿಂತಿರುಗಿಸುವ ವಿನಂತಿ',
        'Return Confirmed'=>'ಹಿಂತಿರುಗಿಸುವಿಕೆಯನ್ನು ದೃಢೀಕರಿಸಲಾಗಿದೆ',
        'Booking Cancelled'=>'ಬುಕಿಂಗ್ ರದ್ದುಗೊಳಿಸಲಾಗಿದೆ',
        'Payment Received'=>'ಪಾವತಿ ಸ್ವೀಕರಿಸಲಾಗಿದೆ',
        'Rental Reminder'=>'ಬಾಡಿಗೆ ಜ್ಞಾಪನೆ',
      ]
    ];
    $dict = $titles[$lang] ?? $titles['hi'];
    if (isset($dict[$title])) $title = $dict[$title];

    $patterns = [
      '/^(.+?) has sent a rental request for (.+?)\\.\\s*Request Code:\\s*(.+?)\\.?$/i' => [
        'hi'=>'$1 ने $2 के लिए किराये का अनुरोध भेजा है। अनुरोध कोड: $3',
        'kn'=>'$1 ಅವರು $2 ಗಾಗಿ ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ಕಳುಹಿಸಿದ್ದಾರೆ. ವಿನಂತಿ ಕೋಡ್: $3'],
      '/^(.+?) has submitted a new ([0-9.]+)-star review for your equipment [“"]?(.+?)[”"]?\\.?$/i' => [
        'hi'=>'$1 ने आपके उपकरण $3 के लिए $2 स्टार की नई समीक्षा दी है।',
        'kn'=>'$1 ಅವರು ನಿಮ್ಮ $3 ಉಪಕರಣಕ್ಕೆ $2 ಸ್ಟಾರ್ ಹೊಸ ವಿಮರ್ಶೆಯನ್ನು ಸಲ್ಲಿಸಿದ್ದಾರೆ.'],
      '/^The renter has cancelled the booking for (.+?)\\.?$/i'=>[
        'hi'=>'किरायेदार ने $1 की बुकिंग रद्द कर दी है।','kn'=>'ಬಾಡಿಗೆದಾರರು $1 ಗಾಗಿ ಬುಕಿಂಗ್ ರದ್ದುಗೊಳಿಸಿದ್ದಾರೆ.'],
      '/^Your booking for (.+?) has been accepted\\.?$/i'=>[
        'hi'=>'$1 के लिए बुकिंग स्वीकार कर ली गई है।','kn'=>'$1 ಗಾಗಿ ಬುಕಿಂಗ್ ಸ್ವೀಕರಿಸಲಾಗಿದೆ.'],
      '/^Your booking for (.+?) has been rejected\\.?$/i'=>[
        'hi'=>'$1 के लिए बुकिंग अस्वीकार कर दी गई है।','kn'=>'$1 ಗಾಗಿ ಬುಕಿಂಗ್ ತಿರಸ್ಕರಿಸಲಾಗಿದೆ.'],
      '/^(.+?) has confirmed delivery of (.+?)\\.?$/i'=>[
        'hi'=>'$1 ने $2 की डिलीवरी की पुष्टि की है।','kn'=>'$1 ಅವರು $2 ವಿತರಣೆಯನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has returned (.+?)\\.?$/i'=>[
        'hi'=>'$1 ने $2 वापस कर दिया है।','kn'=>'$1 ಅವರು $2 ಅನ್ನು ಹಿಂತಿರುಗಿಸಿದ್ದಾರೆ.'],
      '/^The rental period for (.+?) ends tomorrow\\. (.+)$/i'=>[
        'hi'=>'$1 की किराये की अवधि कल समाप्त होगी। $2','kn'=>'$1 ಬಾಡಿಗೆ ನಾಳೆ ಮುಕ್ತಾಯಗೊಳ್ಳುತ್ತದೆ. $2'],
      '/^The rental period for (.+?) has ended, but the equipment is still marked as unreturned\\. (.+)$/i'=>[
        'hi'=>'$1 की किराये की अवधि समाप्त हो गई है, लेकिन उपकरण अभी भी वापस नहीं किया गया है। $2','kn'=>'$1 ಬಾಡಿಗೆ ಅವಧಿ ಮುಗಿದಿದೆ, ಆದರೆ ಉಪಕರಣ ಇನ್ನೂ ಹಿಂತಿರುಗಿಸಿಲ್ಲ ಎಂದು ತೋರಿಸುತ್ತದೆ. $2'],
      '/^(.+?) marked (.+?) as delivered\\.?$/i'=>[
        'hi'=>'$1 ने $2 को डिलीवर किया हुआ चिह्नित किया है।','kn'=>'$1 ಅವರು $2 ಅನ್ನು ವಿತರಿಸಲಾಗಿದೆ ಎಂದು ಗುರುತಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) confirmed the return of (.+?)\\.?$/i'=>[
        'hi'=>'$1 ने $2 की वापसी की पुष्टि की है।','kn'=>'$1 ಅವರು $2 ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has requested to return (.+?)\\.?$/i'=>[
        'hi'=>'$1 ने $2 वापस करने का अनुरोध किया है।','kn'=>'$1 ಅವರು $2 ಹಿಂತಿರುಗಿಸಲು ವಿನಂತಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has completed the rental for (.+?)\\.?$/i'=>[
        'hi'=>'$1 ने $2 का किराया पूरा कर लिया है।','kn'=>'$1 ಅವರು $2 ಬಾಡಿಗೆಯನ್ನು ಪೂರ್ಣಗೊಳಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has paid ₹?([0-9,.]+) for (.+?)\\.?$/i'=>[
        'hi'=>'$1 ने $3 के लिए ₹$2 का भुगतान किया है।','kn'=>'$1 ಅವರು $3 ಗಾಗಿ ₹$2 ಪಾವತಿಸಿದ್ದಾರೆ.'],
      /* Common lender reminder wording variants (the equipment/renter/phone/amount
         captures are intentionally kept in the translated sentence). */
      '/^(?:The )?rental (?:period )?(?:for|of) (.+?) (?:has ended|ended), but (?:the )?equipment is still (?:marked as )?unreturned\.?\s*(.*)$/i'=>[
        'hi'=>'$1 की किराये की अवधि समाप्त हो गई है, लेकिन उपकरण अभी वापस नहीं किया गया है। $2',
        'kn'=>'$1 ಬಾಡಿಗೆ ಅವಧಿ ಮುಗಿದಿದೆ, ಆದರೆ ಉಪಕರಣ ಇನ್ನೂ ಹಿಂತಿರುಗಿಸಿಲ್ಲ. $2'],
      '/^(?:The )?rental (?:period )?(?:for|of) (.+?) (?:ends|will end) tomorrow\.?\s*(.*)$/i'=>[
        'hi'=>'$1 की किराये की अवधि कल समाप्त होगी। $2',
        'kn'=>'$1 ಬಾಡಿಗೆ ಅವಧಿ ನಾಳೆ ಮುಕ್ತಾಯಗೊಳ್ಳುತ್ತದೆ. $2'],
      '/^The rental for (.+?) ends tomorrow\.?\s*(.*)$/i'=>[
        'hi'=>'$1 का किराया कल समाप्त होगा। $2',
        'kn'=>'$1 ಬಾಡಿಗೆ ನಾಳೆ ಮುಕ್ತಾಯಗೊಳ್ಳುತ್ತದೆ. $2'],
      '/^(.+?) rental (?:period )?(?:has ended|ended), but (?:the )?equipment is still (?:marked as )?unreturned\.?\s*(.*)$/i'=>[
        'hi'=>'$1 की किराये की अवधि समाप्त हो गई है, लेकिन उपकरण अभी वापस नहीं किया गया है। $2',
        'kn'=>'$1 ಬಾಡಿಗೆ ಅವಧಿ ಮುಗಿದಿದೆ, ಆದರೆ ಉಪಕರಣ ಇನ್ನೂ ಹಿಂತಿರುಗಿಸಿಲ್ಲ. $2'],
      '/^(.+?) has sent (?:you )?a rental request for (.+?)(?:\.?\s*Request Code:\s*(.+))?\.?$/i'=>[
        'hi'=>'$1 ने $2 के लिए किराये का अनुरोध भेजा है। अनुरोध कोड: $3',
        'kn'=>'$1 ಅವರು $2 ಗಾಗಿ ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ಕಳುಹಿಸಿದ್ದಾರೆ. ವಿನಂತಿ ಕೋಡ್: $3'],
      '/^(.+?) has accepted (?:the )?booking for (.+?)\.?$/i'=>[
        'hi'=>'$1 ने $2 की बुकिंग स्वीकार कर ली है।',
        'kn'=>'$1 ಅವರು $2 ಬುಕಿಂಗ್ ಅನ್ನು ಸ್ವೀಕರಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has rejected (?:the )?booking for (.+?)\.?$/i'=>[
        'hi'=>'$1 ने $2 की बुकिंग अस्वीकार कर दी है।',
        'kn'=>'$1 ಅವರು $2 ಬುಕಿಂಗ್ ಅನ್ನು ತಿರಸ್ಕರಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has cancelled (?:the )?booking for (.+?)\.?$/i'=>[
        'hi'=>'$1 ने $2 की बुकिंग रद्द कर दी है।',
        'kn'=>'$1 ಅವರು $2 ಬುಕಿಂಗ್ ಅನ್ನು ರದ್ದುಗೊಳಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has (?:confirmed|marked) (?:the )?delivery of (.+?)\.?$/i'=>[
        'hi'=>'$1 ने $2 की डिलीवरी की पुष्टि की है।',
        'kn'=>'$1 ಅವರು $2 ವಿತರಣೆಯನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has (?:confirmed|completed) (?:the )?return of (.+?)\.?$/i'=>[
        'hi'=>'$1 ने $2 की वापसी की पुष्टि की है।',
        'kn'=>'$1 ಅವರು $2 ಹಿಂತಿರುಗಿಸಿರುವುದನ್ನು ದೃಢೀಕರಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has completed (?:the )?rental (?:for|of) (.+?)\.?$/i'=>[
        'hi'=>'$1 ने $2 का किराया पूरा कर लिया है।',
        'kn'=>'$1 ಅವರು $2 ಬಾಡಿಗೆಯನ್ನು ಪೂರ್ಣಗೊಳಿಸಿದ್ದಾರೆ.'],
      '/^(.+?) has left (?:a )?review for (.+?)(?:\.|$)/i'=>[
        'hi'=>'$1 ने $2 के लिए समीक्षा दी है।',
        'kn'=>'$1 ಅವರು $2 ಕುರಿತು ವಿಮರ್ಶೆ ನೀಡಿದ್ದಾರೆ.'],
    ];
    foreach ($patterns as $pattern=>$localized) {
      if (preg_match($pattern,$message,$m)) {
        $template=$localized[$lang] ?? $localized['hi'];
        for($i=1;$i<count($m);$i++) $template=str_replace('$'.$i,$m[$i],$template);
        $message=$template; break;
      }
    }

    /* Translate common English phrases embedded in notification messages. */
    $phrases = [
      'hi'=>[
        'Please contact the renter at'=>'कृपया किरायेदार से इस नंबर पर संपर्क करें:',
        'Current overdue charge:'=>'वर्तमान विलंब शुल्क:',
        'but the equipment is still marked as unreturned.'=>'लेकिन उपकरण अभी वापस नहीं किया गया है।',
        'ends tomorrow.'=>'कल समाप्त होगा।',
        'Please contact renter'=>'कृपया किरायेदार से संपर्क करें',
        'to arrange the return.'=>'वापसी की व्यवस्था करने के लिए।',
        'Request Code:'=>'अनुरोध कोड:',
        'The renter'=>'किरायेदार',
        'has sent a rental request for'=>'ने किराये का अनुरोध भेजा है:',
        'Please arrange the return.'=>'कृपया उपकरण वापस करने की व्यवस्था करें।',
        'Please contact the renter'=>'कृपया किरायेदार से संपर्क करें',
        'to arrange the return.'=>'वापसी की व्यवस्था करने के लिए।',
        'Current overdue charge: ₹'=>'वर्तमान विलंब शुल्क: ₹',
      ],
      'kn'=>[
        'Please contact the renter at'=>'ದಯವಿಟ್ಟು ಬಾಡಿಗೆದಾರರನ್ನು ಈ ಸಂಖ್ಯೆಯಲ್ಲಿ ಸಂಪರ್ಕಿಸಿ:',
        'Current overdue charge:'=>'ಪ್ರಸ್ತುತ ವಿಳಂಬ ಶುಲ್ಕ:',
        'but the equipment is still marked as unreturned.'=>'ಆದರೆ ಉಪಕರಣ ಇನ್ನೂ ಹಿಂತಿರುಗಿಸಿಲ್ಲ ಎಂದು ತೋರಿಸುತ್ತದೆ.',
        'ends tomorrow.'=>'ನಾಳೆ ಮುಕ್ತಾಯಗೊಳ್ಳುತ್ತದೆ.',
        'Please contact renter'=>'ದಯವಿಟ್ಟು ಬಾಡಿಗೆದಾರರನ್ನು ಸಂಪರ್ಕಿಸಿ',
        'to arrange the return.'=>'ಹಿಂತಿರುಗಿಸುವ ವ್ಯವಸ್ಥೆ ಮಾಡಲು.',
        'Request Code:'=>'ವಿನಂತಿ ಕೋಡ್:',
        'The renter'=>'ಬಾಡಿಗೆದಾರರು',
        'has sent a rental request for'=>'ಬಾಡಿಗೆ ವಿನಂತಿಯನ್ನು ಕಳುಹಿಸಿದ್ದಾರೆ:',
        'Please arrange the return.'=>'ದಯವಿಟ್ಟು ಉಪಕರಣ ಹಿಂತಿರುಗಿಸಲು ವ್ಯವಸ್ಥೆ ಮಾಡಿ.',
        'Please contact the renter'=>'ದಯವಿಟ್ಟು ಬಾಡಿಗೆದಾರರನ್ನು ಸಂಪರ್ಕಿಸಿ',
        'to arrange the return.'=>'ಹಿಂತಿರುಗಿಸುವ ವ್ಯವಸ್ಥೆ ಮಾಡಲು.',
        'Current overdue charge: ₹'=>'ಪ್ರಸ್ತುತ ವಿಳಂಬ ಶುಲ್ಕ: ₹',
      ]
    ];
    foreach (($phrases[$lang] ?? []) as $from=>$to) $message=str_ireplace($from,$to,$message);
    return [$title,$message];
}

function lender_notification_ui_text($key, $lang)
{
    $labels = [
      'hi'=>[
        'notifications'=>'सूचनाएं','lender_notifications_subtitle'=>'आपके उपकरण किराये से संबंधित सूचनाएं और अपडेट।',
        'mark_all_read'=>'सभी को पढ़ा हुआ चिह्नित करें','no_notifications'=>'कोई सूचना नहीं',
        'no_notifications_message'=>'आपने सभी सूचनाएं देख ली हैं। नई सूचनाएं यहां दिखाई देंगी।',
        'unread'=>'अपठित','mark_read'=>'पढ़ा हुआ चिह्नित करें','my_profile'=>'मेरी प्रोफ़ाइल',
        'brand_main'=>'कृषि','brand_sub'=>'उपकरण किराया प्रणाली'],
      'kn'=>[
        'notifications'=>'ಅಧಿಸೂಚನೆಗಳು','lender_notifications_subtitle'=>'ನಿಮ್ಮ ಉಪಕರಣ ಬಾಡಿಗೆಗಳ ಅಧಿಸೂಚನೆಗಳು ಮತ್ತು ನವೀಕರಣಗಳು.',
        'mark_all_read'=>'ಎಲ್ಲವನ್ನೂ ಓದಿದಂತೆ ಗುರುತಿಸಿ','no_notifications'=>'ಯಾವುದೇ ಅಧಿಸೂಚನೆಗಳಿಲ್ಲ',
        'no_notifications_message'=>'ನೀವು ಎಲ್ಲಾ ಅಧಿಸೂಚನೆಗಳನ್ನು ನೋಡಿದ್ದೀರಿ. ಹೊಸ ಅಧಿಸೂಚನೆಗಳು ಇಲ್ಲಿ ಕಾಣಿಸುತ್ತವೆ.',
        'unread'=>'ಓದದಿರುವುದು','mark_read'=>'ಓದಿದಂತೆ ಗುರುತಿಸಿ','my_profile'=>'ನನ್ನ ಪ್ರೊಫೈಲ್',
        'brand_main'=>'ಕೃಷಿ','brand_sub'=>'ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ']
    ];
    if ($lang==='en') return __($key);
    return $labels[$lang][$key] ?? __($key);
}

/* Notification icon based on notification type */
function notification_icon($title, $message)
{
    $text = strtolower($title . ' ' . $message);

    if (strpos($text, 'request') !== false ||
        strpos($text, 'booking') !== false) {
        return ['fa-clipboard-list', 'request'];
    }

    if (strpos($text, 'accepted') !== false ||
        strpos($text, 'confirmed') !== false) {
        return ['fa-circle-check', 'success'];
    }

    if (strpos($text, 'delivered') !== false ||
        strpos($text, 'delivery') !== false) {
        return ['fa-truck', 'delivery'];
    }

    if (strpos($text, 'returned') !== false ||
        strpos($text, 'return') !== false) {
        return ['fa-rotate-left', 'return'];
    }

    if (strpos($text, 'rejected') !== false ||
        strpos($text, 'overdue') !== false ||
        strpos($text, 'cancel') !== false) {
        return ['fa-circle-exclamation', 'danger'];
    }

    return ['fa-bell', 'default'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($current_lang); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars(lender_notification_ui_text('notifications', $current_lang)); ?> -
        <?php echo htmlspecialchars(lender_notification_ui_text('brand_main', $current_lang) . ' ' . lender_notification_ui_text('brand_sub', $current_lang)); ?>
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        :root {
            --navy-primary: #0B3478;
            --navy-dark: #082B65;
            --blue-hover: #164C9A;
            --sidebar-bg: #C7DDF7;
            --sidebar-active: #A6C8F5;
            --text-dark: #10194A;
            --text-muted: #425D80;
            --bg-body: #EAF3FF;
            --card-bg: #F8FBFF;
            --border-color: #C8DDF5;
            --blue-accent: #8DB8F0;

            --teal: #0B3478;
            --teal-dark: #082B65;
            --teal-light: #DCEBFF;
            --blue: #164C9A;
            --page-bg: #EAF3FF;
            --border: #C8DDF5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg-body);
            color: var(--text-dark);
            font-family: Arial, Helvetica, sans-serif;
        }

        /* Main content starts beside the shared sidebar */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            background: var(--bg-body);
        }

        /* Top navigation */
        .top-nav {
            min-height: 72px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 12px 32px;
        }

        .language-select {
            border: 1px solid var(--border-color);
            border-radius: 7px;
            padding: 8px 30px 8px 10px;
            font-size: 13px;
            font-weight: 600;
            background: var(--card-bg);
            color: var(--text-dark);
            cursor: pointer;
        }

        .language-select:focus {
            outline: none;
            border-color: var(--blue-accent);
            box-shadow: 0 0 0 3px rgba(141, 184, 240, 0.2);
        }

        .notification-bell {
            position: relative;
            color: var(--navy-primary);
            text-decoration: none;
            font-size: 19px;
        }

        .notification-bell:hover {
            color: var(--blue-hover);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            min-width: 17px;
            height: 17px;
            padding: 0 4px;
            border-radius: 20px;
            background: #DC2626;
            color: #fff;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .profile-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--navy-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            flex-shrink: 0;
        }

        .profile-btn:hover {
            background: var(--blue-hover);
            color: white;
        }

        /* Page container */
        .page-wrap {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
        }

        .page-title {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 6px;
            line-height: 1.5;
        }

        /* Mark all button */
        .mark-all-btn {
            border: 1px solid var(--blue-accent);
            background: var(--card-bg);
            color: var(--navy-primary);
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .mark-all-btn:hover {
            background: var(--navy-primary);
            border-color: var(--navy-primary);
            color: #fff;
        }

        /* Notification cards */
        .notification-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 3px 10px rgba(11, 52, 120, 0.04);
            transition: box-shadow 0.2s ease;
        }

        .notification-card:hover {
            box-shadow: 0 5px 14px rgba(11, 52, 120, 0.08);
        }

        .notification-card.unread {
            border-left: 4px solid var(--blue-accent);
            background: #F0F6FF;
        }

        .notification-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .notification-icon.request {
            background: #DCEBFF;
            color: var(--navy-primary);
        }

        .notification-icon.success {
            background: #DCFCE7;
            color: #15803D;
        }

        .notification-icon.delivery {
            background: #DCEBFF;
            color: var(--blue-hover);
        }

        .notification-icon.return {
            background: #FEF3C7;
            color: #B45309;
        }

        .notification-icon.danger {
            background: #FEE2E2;
            color: #DC2626;
        }

        .notification-icon.default {
            background: #DCEBFF;
            color: var(--navy-primary);
        }

        .notification-body {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 6px;
        }

        .notification-message {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .notification-time {
            color: #657B9A;
            font-size: 12px;
            margin-top: 10px;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: var(--navy-primary);
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
            vertical-align: middle;
        }

        /* Individual mark-as-read button */
        .read-btn {
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--navy-primary);
            border-radius: 7px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .read-btn:hover {
            background: #DCEBFF;
            border-color: var(--blue-accent);
            color: var(--navy-dark);
        }

        /* Empty notification state */
        .empty-state {
            background: var(--card-bg);
            border: 1px dashed var(--blue-accent);
            border-radius: 14px;
            padding: 70px 25px;
            text-align: center;
        }

        .empty-state i {
            font-size: 42px;
            color: var(--blue-accent);
            margin-bottom: 16px;
        }

        .empty-state h5 {
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--text-muted);
            margin: 0;
            font-size: 14px;
        }

        /* Responsive layout */
        @media (max-width: 1000px) {
            .page-wrap {
                padding: 25px 20px;
            }

            .top-nav {
                padding: 12px 20px;
            }
        }

        @media (max-width: 650px) {
            .page-wrap {
                padding: 20px 14px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .notification-card {
                flex-wrap: wrap;
                padding: 16px;
            }

            .notification-body {
                flex-basis: calc(100% - 65px);
            }

            .notification-card form {
                margin-left: 62px;
            }

            .top-nav {
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<?php include 'lender_sidebar.php'; ?>

<div class="main-content">

    <!-- Top Navigation -->
    <div class="top-nav">

        <form
            action="lender_notifications.php"
            method="GET"
            class="mb-0"
        >
            <select
                name="lang"
                class="language-select"
                onchange="this.form.submit()"
                aria-label="Select language"
            >
                <option value="en" <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>>
                    English
                </option>

                <option value="hi" <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>>
                    हिंदी (Hindi)
                </option>

                <option value="kn" <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>>
                    ಕನ್ನಡ (Kannada)
                </option>
            </select>
        </form>

        <a
            href="lender_notifications.php<?php echo $lang_param; ?>"
            class="notification-bell"
            title="<?php echo htmlspecialchars(lender_notification_ui_text('notifications', $current_lang)); ?>"
            aria-label="<?php echo htmlspecialchars(lender_notification_ui_text('notifications', $current_lang)); ?>"
        >
            <i class="fa-solid fa-bell"></i>

            <?php if ($notif_count > 0): ?>
                <span class="notification-badge">
                    <?php echo $notif_count; ?>
                </span>
            <?php endif; ?>
        </a>

        <a
            href="profile.php<?php echo $lang_param; ?>"
            class="profile-btn"
            title="<?php echo htmlspecialchars(lender_notification_ui_text('my_profile', $current_lang)); ?>"
            aria-label="<?php echo htmlspecialchars(lender_notification_ui_text('my_profile', $current_lang)); ?>"
        >
            <i class="fa-solid fa-user"></i>
        </a>

    </div>

    <div class="page-wrap">

        <!-- Page Heading -->
        <div class="page-header">

            <div>
                <h1 class="page-title">
                    <?php echo htmlspecialchars(lender_notification_ui_text('notifications', $current_lang)); ?>
                </h1>

                <div class="page-subtitle">
                    <?php echo htmlspecialchars(lender_notification_ui_text('lender_notifications_subtitle', $current_lang)); ?>
                </div>
            </div>

            <?php if ($notif_count > 0): ?>
                <form method="POST" class="mb-0">
                    <button
                        type="submit"
                        name="mark_all_read"
                        class="mark-all-btn"
                    >
                        <i class="fa-solid fa-check-double me-1"></i>
                        <?php echo htmlspecialchars(lender_notification_ui_text('mark_all_read', $current_lang)); ?>
                    </button>
                </form>
            <?php endif; ?>

        </div>

        <!-- Notifications -->
        <?php if (empty($notifications)): ?>

            <div class="empty-state">
                <i class="fa-regular fa-bell-slash"></i>

                <h5>
                    <?php echo htmlspecialchars(lender_notification_ui_text('no_notifications', $current_lang)); ?>
                </h5>

                <p>
                    <?php echo htmlspecialchars(lender_notification_ui_text('no_notifications_message', $current_lang)); ?>
                </p>
            </div>

        <?php else: ?>

            <?php foreach ($notifications as $notification): ?>

                <?php
                    [$icon, $icon_type] = notification_icon(
                        $notification['title'] ?? '',
                        $notification['message'] ?? ''
                    );

                    $is_unread = empty($notification['is_read']);
                    [$display_title, $display_message] = localize_lender_notification(
                        $notification['title'] ?? '',
                        $notification['message'] ?? '',
                        $current_lang
                    );
                ?>

                <div class="notification-card <?php echo $is_unread ? 'unread' : ''; ?>">

                    <div class="notification-icon <?php echo htmlspecialchars($icon_type); ?>">
                        <i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i>
                    </div>

                    <div class="notification-body">

                        <h5 class="notification-title">
                            <?php
                                echo htmlspecialchars(
                                    $display_title !== '' ? $display_title : lender_notification_ui_text('notifications', $current_lang)
                                );
                            ?>

                            <?php if ($is_unread): ?>
                                <span
                                    class="unread-dot"
                                    title="<?php echo htmlspecialchars(lender_notification_ui_text('unread', $current_lang)); ?>"
                                ></span>
                            <?php endif; ?>
                        </h5>

                        <p class="notification-message">
                            <?php
                                echo nl2br(
                                    htmlspecialchars($display_message)
                                );
                            ?>
                        </p>

                        <div class="notification-time">
                            <i class="fa-regular fa-clock me-1"></i>

                            <?php
                                $created_at = $notification['created_at'] ?? '';
                                $timestamp = $created_at ? strtotime($created_at) : false;

                                echo htmlspecialchars(
                                    $timestamp
                                        ? date('d M Y, h:i A', $timestamp)
                                        : ''
                                );
                            ?>
                        </div>

                    </div>

                    <?php if ($is_unread): ?>

                        <form method="POST" class="mb-0">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?php echo (int) $notification['notification_id']; ?>"
                            >

                            <button
                                type="submit"
                                name="mark_read"
                                class="read-btn"
                            >
                                <i class="fa-solid fa-check me-1"></i>
                                <?php echo htmlspecialchars(lender_notification_ui_text('mark_read', $current_lang)); ?>
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
