<?php
session_start();

require_once 'includes/lang.php';

$allowed_languages = ['en', 'kn', 'hi'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_languages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? 'en';
if (!in_array($current_lang, $allowed_languages, true)) {
    $current_lang = 'en';
    $_SESSION['lang'] = 'en';
}

$page = [
'en' => [
'title'=>'Terms & Conditions and Privacy Policy',
'brand_main'=>'AGRICULTURE','brand_sub'=>'EQUIPMENT RENTAL SYSTEM',
'home'=>'Home','register'=>'Register','login'=>'Login',
'heading'=>'Terms & Conditions and Privacy Policy',
'intro'=>'Please read these terms before using the Agriculture Equipment Rental System.',
'terms_heading'=>'Terms & Conditions',
'general'=>'1. General Terms',
'general_points'=>[
'Users must provide accurate and truthful registration information.',
'Each account is for the registered user only. Do not share your password or account access with others.',
'Users must use the platform only for lawful agricultural equipment rental activities.',
'The platform connects renters and lenders; users are responsible for the accuracy of information they provide about equipment, rental dates, address, and contact details.'
],
'lender'=>'2. Lender Terms',
'lender_points'=>[
'Lenders must provide accurate equipment details, rental price, availability, condition, and security deposit information.',
'The lender is responsible for physically delivering the equipment to the agreed delivery location.',
'The lender should inspect the equipment before delivery and explain basic operating requirements to the renter.',
'The lender may collect the security deposit/advance amount in cash at the time of delivery as agreed in the booking.',
'The lender is responsible for physically collecting the equipment after the rental period.',
'The lender should mark the equipment as Delivered or Returned in the system only when the physical activity has actually taken place.',
'Lenders must not knowingly provide unsafe, damaged, or unsuitable equipment.'
],
'renter'=>'3. Renter Terms',
'renter_points'=>[
'Renters must provide correct identity, phone, email, and delivery-address information.',
'Renters must use the equipment carefully and only for its intended agricultural purpose.',
'The equipment must be returned on or before the agreed end date to avoid applicable late charges.',
'Renters must inspect the equipment upon delivery and report mechanical or visible issues immediately.',
'Renters must keep the equipment clean and securely stored during the rental period.',
'The renter must confirm delivery in the system after physically receiving the equipment.',
'The renter must confirm return in the system after the equipment has been physically collected by the lender.',
'Any remaining rental amount due under a cash-on-delivery arrangement must be paid as agreed at return.'
],
'safety'=>'4. Safety and Equipment Use',
'safety_points'=>[
'Ensure the equipment is operated carefully and used only for intended agricultural purposes.',
'Return the equipment on or before the agreed end date to avoid late penalty charges.',
'Inspect the equipment upon delivery and report any mechanical issues immediately.',
'Keep the equipment clean and securely stored when not in use during your rental period.',
'Contact the lender directly if you need assistance or have questions regarding operation.'
],
'privacy_heading'=>'Privacy Policy',
'privacy_intro'=>'We respect the privacy of users and use account information only for operating the rental system.',
'privacy_points'=>[
'We may collect name, email, phone number, address, user role, booking information, identity details submitted for a booking, and account-security information.',
'Account information is used to create and manage accounts, process rental requests, communicate booking status, and provide system features.',
'Booking information may be visible to the lender or renter involved in that booking when necessary to complete the rental.',
'Passwords are stored using secure password hashing and should never be shared with anyone.',
'Users should keep their login credentials confidential and notify the system administrator if they suspect unauthorized access.',
'We do not intentionally sell users’ personal information to third parties.',
'Users should provide only information that is necessary and accurate for using the rental service.',
'Information may be retained as required for account records, rental history, security, and system operation.',
'Users should not upload or submit another person’s personal information without proper permission.',
'The platform may record technical information such as login or activity details for security and system maintenance.'
],
'notice'=>'Important Note',
'notice_text'=>'This page describes the rules for using the project system. It does not replace any legal agreement between a lender and renter. Users should follow applicable local laws and safe equipment practices.',
'back'=>'Back to Register'
],
'kn'=>[
'title'=>'ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳು ಹಾಗೂ ಗೌಪ್ಯತಾ ನೀತಿ',
'brand_main'=>'ಕೃಷಿ','brand_sub'=>'ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ',
'home'=>'ಮುಖಪುಟ','register'=>'ನೋಂದಣಿ','login'=>'ಲಾಗಿನ್',
'heading'=>'ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳು ಹಾಗೂ ಗೌಪ್ಯತಾ ನೀತಿ',
'intro'=>'ಕೃಷಿ ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆಯನ್ನು ಬಳಸುವ ಮೊದಲು ಈ ನಿಯಮಗಳನ್ನು ಓದಿ.',
'terms_heading'=>'ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳು',
'general'=>'1. ಸಾಮಾನ್ಯ ನಿಯಮಗಳು',
'general_points'=>[
'ಬಳಕೆದಾರರು ನೋಂದಣಿ ಸಮಯದಲ್ಲಿ ಸರಿಯಾದ ಮತ್ತು ನಿಜವಾದ ಮಾಹಿತಿಯನ್ನು ನೀಡಬೇಕು.',
'ಪ್ರತಿ ಖಾತೆಯನ್ನು ನೋಂದಾಯಿತ ಬಳಕೆದಾರರು ಮಾತ್ರ ಬಳಸಬೇಕು. ಪಾಸ್‌ವರ್ಡ್ ಅಥವಾ ಖಾತೆಯ ಪ್ರವೇಶವನ್ನು ಇತರರೊಂದಿಗೆ ಹಂಚಿಕೊಳ್ಳಬೇಡಿ.',
'ವ್ಯವಸ್ಥೆಯನ್ನು ಕಾನೂನುಬದ್ಧ ಕೃಷಿ ಉಪಕರಣ ಬಾಡಿಗೆ ಚಟುವಟಿಕೆಗಳಿಗೆ ಮಾತ್ರ ಬಳಸಬೇಕು.',
'ಬಳಕೆದಾರರು ಉಪಕರಣ, ಬಾಡಿಗೆ ದಿನಾಂಕ, ವಿಳಾಸ ಮತ್ತು ಸಂಪರ್ಕ ಮಾಹಿತಿಯನ್ನು ಸರಿಯಾಗಿ ನೀಡುವ ಜವಾಬ್ದಾರಿ ಹೊಂದಿರುತ್ತಾರೆ.'
],
'lender'=>'2. ಸಾಲದಾತರ ನಿಯಮಗಳು',
'lender_points'=>[
'ಸಾಲದಾತರು ಉಪಕರಣದ ವಿವರಗಳು, ಬಾಡಿಗೆ ದರ, ಲಭ್ಯತೆ, ಸ್ಥಿತಿ ಮತ್ತು ಭದ್ರತಾ ಠೇವಣಿ ಮಾಹಿತಿಯನ್ನು ಸರಿಯಾಗಿ ನೀಡಬೇಕು.',
'ಒಪ್ಪಿಕೊಂಡ ವಿತರಣಾ ಸ್ಥಳಕ್ಕೆ ಉಪಕರಣವನ್ನು ಭೌತಿಕವಾಗಿ ತಲುಪಿಸುವ ಜವಾಬ್ದಾರಿ ಸಾಲದಾತರದು.',
'ವಿತರಣೆಗೆ ಮೊದಲು ಉಪಕರಣವನ್ನು ಪರಿಶೀಲಿಸಿ ಅದರ ಮೂಲಭೂತ ಬಳಕೆಯ ಬಗ್ಗೆ ಬಾಡಿಗೆದಾರರಿಗೆ ತಿಳಿಸಬೇಕು.',
'ಬುಕಿಂಗ್‌ನಲ್ಲಿ ಒಪ್ಪಿಕೊಂಡಂತೆ ವಿತರಣೆಯ ಸಮಯದಲ್ಲಿ ಭದ್ರತಾ ಠೇವಣಿ/ಮುಂಗಡ ಮೊತ್ತವನ್ನು ನಗದಾಗಿ ಪಡೆಯಬಹುದು.',
'ಬಾಡಿಗೆ ಅವಧಿ ಮುಗಿದ ನಂತರ ಉಪಕರಣವನ್ನು ಭೌತಿಕವಾಗಿ ಸಂಗ್ರಹಿಸುವ ಜವಾಬ್ದಾರಿ ಸಾಲದಾತರದು.',
'ಭೌತಿಕವಾಗಿ ವಿತರಣೆ ಅಥವಾ ವಾಪಸಾತಿ ನಡೆದ ನಂತರ ಮಾತ್ರ ವ್ಯವಸ್ಥೆಯಲ್ಲಿ Delivered ಅಥವಾ Returned ಎಂದು ಗುರುತಿಸಬೇಕು.',
'ಅಸುರಕ್ಷಿತ, ಹಾನಿಗೊಂಡ ಅಥವಾ ಸೂಕ್ತವಲ್ಲದ ಉಪಕರಣವನ್ನು ಉದ್ದೇಶಪೂರ್ವಕವಾಗಿ ನೀಡಬಾರದು.'
],
'renter'=>'3. ಬಾಡಿಗೆದಾರರ ನಿಯಮಗಳು',
'renter_points'=>[
'ಬಾಡಿಗೆದಾರರು ಸರಿಯಾದ ಗುರುತು, ಫೋನ್, ಇಮೇಲ್ ಮತ್ತು ವಿತರಣಾ ವಿಳಾಸವನ್ನು ನೀಡಬೇಕು.',
'ಉಪಕರಣವನ್ನು ಜಾಗರೂಕತೆಯಿಂದ ಮತ್ತು ಉದ್ದೇಶಿತ ಕೃಷಿ ಬಳಕೆಗೆ ಮಾತ್ರ ಬಳಸಬೇಕು.',
'ತಡ ಶುಲ್ಕವನ್ನು ತಪ್ಪಿಸಲು ಒಪ್ಪಿಕೊಂಡ ಅಂತಿಮ ದಿನಾಂಕದೊಳಗೆ ಅಥವಾ ಅದಕ್ಕೂ ಮೊದಲು ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಬೇಕು.',
'ವಿತರಣೆಯ ಸಮಯದಲ್ಲಿ ಉಪಕರಣವನ್ನು ಪರಿಶೀಲಿಸಿ ಯಾವುದೇ ಯಾಂತ್ರಿಕ ಅಥವಾ ಗೋಚರ ದೋಷವನ್ನು ತಕ್ಷಣ ವರದಿ ಮಾಡಬೇಕು.',
'ಬಾಡಿಗೆ ಅವಧಿಯಲ್ಲಿ ಉಪಕರಣವನ್ನು ಸ್ವಚ್ಛವಾಗಿ ಮತ್ತು ಸುರಕ್ಷಿತವಾಗಿ ಇಡಬೇಕು.',
'ಉಪಕರಣವನ್ನು ಭೌತಿಕವಾಗಿ ಸ್ವೀಕರಿಸಿದ ನಂತರ ವ್ಯವಸ್ಥೆಯಲ್ಲಿ ವಿತರಣೆಯನ್ನು ದೃಢೀಕರಿಸಬೇಕು.',
'ಸಾಲದಾತರು ಉಪಕರಣವನ್ನು ಭೌತಿಕವಾಗಿ ಸಂಗ್ರಹಿಸಿದ ನಂತರ ವ್ಯವಸ್ಥೆಯಲ್ಲಿ ವಾಪಸಾತಿಯನ್ನು ದೃಢೀಕರಿಸಬೇಕು.',
'ಕ್ಯಾಶ್ ವ್ಯವಸ್ಥೆಯ ಪ್ರಕಾರ ಬಾಕಿ ಇರುವ ಬಾಡಿಗೆ ಮೊತ್ತವನ್ನು ವಾಪಸಾತಿಯ ಸಮಯದಲ್ಲಿ ಒಪ್ಪಿಕೊಂಡಂತೆ ಪಾವತಿಸಬೇಕು.'
],
'safety'=>'4. ಸುರಕ್ಷತೆ ಮತ್ತು ಉಪಕರಣ ಬಳಕೆ',
'safety_points'=>[
'ಉಪಕರಣವನ್ನು ಜಾಗರೂಕತೆಯಿಂದ ಮತ್ತು ಉದ್ದೇಶಿತ ಕೃಷಿ ಬಳಕೆಗೆ ಮಾತ್ರ ಬಳಸಬೇಕು.',
'ತಡ ದಂಡವನ್ನು ತಪ್ಪಿಸಲು ಒಪ್ಪಿಕೊಂಡ ಅಂತಿಮ ದಿನಾಂಕದೊಳಗೆ ಉಪಕರಣವನ್ನು ಹಿಂತಿರುಗಿಸಬೇಕು.',
'ವಿತರಣೆಯ ಸಮಯದಲ್ಲಿ ಉಪಕರಣವನ್ನು ಪರಿಶೀಲಿಸಿ ಯಾವುದೇ ಯಾಂತ್ರಿಕ ಸಮಸ್ಯೆಯನ್ನು ತಕ್ಷಣ ವರದಿ ಮಾಡಬೇಕು.',
'ಬಾಡಿಗೆ ಅವಧಿಯಲ್ಲಿ ಬಳಸದೇ ಇರುವಾಗ ಉಪಕರಣವನ್ನು ಸ್ವಚ್ಛವಾಗಿ ಮತ್ತು ಸುರಕ್ಷಿತವಾಗಿ ಇಡಬೇಕು.',
'ಬಳಕೆಯ ಕುರಿತು ಸಹಾಯ ಬೇಕಾದರೆ ಸಾಲದಾತರನ್ನು ನೇರವಾಗಿ ಸಂಪರ್ಕಿಸಬೇಕು.'
],
'privacy_heading'=>'ಗೌಪ್ಯತಾ ನೀತಿ',
'privacy_intro'=>'ನಾವು ಬಳಕೆದಾರರ ಗೌಪ್ಯತೆಯನ್ನು ಗೌರವಿಸುತ್ತೇವೆ ಮತ್ತು ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆಯನ್ನು ನಡೆಸಲು ಮಾತ್ರ ಖಾತೆ ಮಾಹಿತಿಯನ್ನು ಬಳಸುತ್ತೇವೆ.',
'privacy_points'=>[
'ಹೆಸರು, ಇಮೇಲ್, ಫೋನ್ ಸಂಖ್ಯೆ, ವಿಳಾಸ, ಬಳಕೆದಾರರ ಪಾತ್ರ, ಬುಕಿಂಗ್ ಮಾಹಿತಿ, ಬುಕಿಂಗ್‌ಗೆ ಸಲ್ಲಿಸಿದ ಗುರುತು ವಿವರಗಳು ಮತ್ತು ಖಾತೆ ಭದ್ರತಾ ಮಾಹಿತಿಯನ್ನು ಸಂಗ್ರಹಿಸಬಹುದು.',
'ಖಾತೆ ರಚನೆ ಮತ್ತು ನಿರ್ವಹಣೆ, ಬಾಡಿಗೆ ವಿನಂತಿಗಳ ಪ್ರಕ್ರಿಯೆ, ಬುಕಿಂಗ್ ಸ್ಥಿತಿ ಮಾಹಿತಿ ಮತ್ತು ವ್ಯವಸ್ಥೆಯ ವೈಶಿಷ್ಟ್ಯಗಳನ್ನು ಒದಗಿಸಲು ಮಾಹಿತಿಯನ್ನು ಬಳಸಲಾಗುತ್ತದೆ.',
'ಬುಕಿಂಗ್ ಪೂರ್ಣಗೊಳಿಸಲು ಅಗತ್ಯವಿರುವಾಗ ಆ ಬುಕಿಂಗ್‌ಗೆ ಸಂಬಂಧಿಸಿದ ಸಾಲದಾತ ಅಥವಾ ಬಾಡಿಗೆದಾರರಿಗೆ ಬುಕಿಂಗ್ ಮಾಹಿತಿ ಕಾಣಿಸಬಹುದು.',
'ಪಾಸ್‌ವರ್ಡ್‌ಗಳನ್ನು ಸುರಕ್ಷಿತ ಹ್ಯಾಶಿಂಗ್ ಮೂಲಕ ಸಂಗ್ರಹಿಸಲಾಗುತ್ತದೆ ಮತ್ತು ಯಾರೊಂದಿಗೂ ಹಂಚಿಕೊಳ್ಳಬಾರದು.',
'ಬಳಕೆದಾರರು ತಮ್ಮ ಲಾಗಿನ್ ವಿವರಗಳನ್ನು ರಹಸ್ಯವಾಗಿರಿಸಬೇಕು ಮತ್ತು ಅನಧಿಕೃತ ಪ್ರವೇಶದ ಅನುಮಾನವಿದ್ದರೆ ನಿರ್ವಾಹಕರಿಗೆ ತಿಳಿಸಬೇಕು.',
'ಬಳಕೆದಾರರ ವೈಯಕ್ತಿಕ ಮಾಹಿತಿಯನ್ನು ಮೂರನೇ ವ್ಯಕ್ತಿಗಳಿಗೆ ಉದ್ದೇಶಪೂರ್ವಕವಾಗಿ ಮಾರಾಟ ಮಾಡುವುದಿಲ್ಲ.',
'ಸೇವೆಯನ್ನು ಬಳಸಲು ಅಗತ್ಯವಾದ ಮತ್ತು ಸರಿಯಾದ ಮಾಹಿತಿಯನ್ನು ಮಾತ್ರ ನೀಡಬೇಕು.',
'ಖಾತೆ ದಾಖಲೆ, ಬಾಡಿಗೆ ಇತಿಹಾಸ, ಭದ್ರತೆ ಮತ್ತು ವ್ಯವಸ್ಥೆಯ ಕಾರ್ಯಾಚರಣೆಗೆ ಅಗತ್ಯವಿರುವ ಅವಧಿಯವರೆಗೆ ಮಾಹಿತಿಯನ್ನು ಉಳಿಸಬಹುದು.',
'ಸರಿಯಾದ ಅನುಮತಿಯಿಲ್ಲದೆ ಮತ್ತೊಬ್ಬರ ವೈಯಕ್ತಿಕ ಮಾಹಿತಿಯನ್ನು ಸಲ್ಲಿಸಬಾರದು.',
'ಭದ್ರತೆ ಮತ್ತು ನಿರ್ವಹಣೆಗೆ ಲಾಗಿನ್ ಅಥವಾ ಚಟುವಟಿಕೆಗಳಂತಹ ತಾಂತ್ರಿಕ ಮಾಹಿತಿಯನ್ನು ದಾಖಲಿಸಬಹುದು.'
],
'notice'=>'ಮುಖ್ಯ ಸೂಚನೆ',
'notice_text'=>'ಈ ಪುಟವು ಈ ಪ್ರಾಜೆಕ್ಟ್ ವ್ಯವಸ್ಥೆಯನ್ನು ಬಳಸುವ ನಿಯಮಗಳನ್ನು ವಿವರಿಸುತ್ತದೆ. ಇದು ಸಾಲದಾತ ಮತ್ತು ಬಾಡಿಗೆದಾರರ ನಡುವಿನ ಯಾವುದೇ ಕಾನೂನು ಒಪ್ಪಂದಕ್ಕೆ ಪರ್ಯಾಯವಲ್ಲ. ಅನ್ವಯಿಸುವ ಸ್ಥಳೀಯ ಕಾನೂನುಗಳು ಮತ್ತು ಸುರಕ್ಷಿತ ಉಪಕರಣ ಬಳಕೆಯ ನಿಯಮಗಳನ್ನು ಪಾಲಿಸಿ.',
'back'=>'ನೋಂದಣಿಗೆ ಹಿಂತಿರುಗಿ'
],
'hi'=>[
'title'=>'नियम और शर्तें तथा गोपनीयता नीति',
'brand_main'=>'कृषि','brand_sub'=>'उपकरण किराया प्रणाली',
'home'=>'होम','register'=>'पंजीकरण','login'=>'लॉगिन',
'heading'=>'नियम और शर्तें तथा गोपनीयता नीति',
'intro'=>'कृषि उपकरण किराया प्रणाली का उपयोग करने से पहले इन नियमों को पढ़ें।',
'terms_heading'=>'नियम और शर्तें',
'general'=>'1. सामान्य नियम',
'general_points'=>[
'उपयोगकर्ताओं को पंजीकरण के समय सही और सत्य जानकारी देनी होगी।',
'प्रत्येक खाता केवल पंजीकृत उपयोगकर्ता के लिए है। पासवर्ड या खाते की पहुंच किसी अन्य व्यक्ति के साथ साझा न करें।',
'सिस्टम का उपयोग केवल कानूनी कृषि उपकरण किराया गतिविधियों के लिए किया जाना चाहिए।',
'उपकरण, किराये की तारीख, पता और संपर्क जानकारी सही देना उपयोगकर्ता की जिम्मेदारी है।'
],
'lender'=>'2. ऋणदाता के नियम',
'lender_points'=>[
'ऋणदाता को उपकरण का विवरण, किराया, उपलब्धता, स्थिति और सुरक्षा जमा की जानकारी सही देनी होगी।',
'सहमत डिलीवरी स्थान तक उपकरण को भौतिक रूप से पहुंचाना ऋणदाता की जिम्मेदारी है।',
'डिलीवरी से पहले उपकरण की जांच करें और किरायेदार को बुनियादी उपयोग की जानकारी दें।',
'बुकिंग में सहमत होने पर डिलीवरी के समय सुरक्षा जमा/अग्रिम राशि नकद ली जा सकती है।',
'किराये की अवधि समाप्त होने के बाद उपकरण को भौतिक रूप से वापस लेना ऋणदाता की जिम्मेदारी है।',
'भौतिक डिलीवरी या वापसी होने के बाद ही सिस्टम में Delivered या Returned दर्ज करें।',
'जानबूझकर असुरक्षित, क्षतिग्रस्त या अनुपयुक्त उपकरण उपलब्ध नहीं कराया जाना चाहिए।'
],
'renter'=>'3. किरायेदार के नियम',
'renter_points'=>[
'किरायेदार को सही पहचान, फोन, ईमेल और डिलीवरी पता देना होगा।',
'उपकरण का सावधानी से और केवल निर्धारित कृषि उद्देश्य के लिए उपयोग करें।',
'देर से लगने वाले शुल्क से बचने के लिए सहमत अंतिम तारीख तक या उससे पहले उपकरण लौटाएं।',
'डिलीवरी के समय उपकरण की जांच करें और किसी यांत्रिक या दिखाई देने वाली समस्या की तुरंत सूचना दें।',
'किराये की अवधि के दौरान उपकरण को साफ और सुरक्षित रखें।',
'उपकरण को भौतिक रूप से प्राप्त करने के बाद सिस्टम में डिलीवरी की पुष्टि करें।',
'ऋणदाता द्वारा उपकरण को भौतिक रूप से वापस लेने के बाद सिस्टम में वापसी की पुष्टि करें।',
'कैश व्यवस्था के अनुसार बकाया किराया वापसी के समय सहमति के अनुसार भुगतान करें।'
],
'safety'=>'4. सुरक्षा और उपकरण का उपयोग',
'safety_points'=>[
'उपकरण का सावधानी से और केवल निर्धारित कृषि उद्देश्य के लिए उपयोग करें।',
'देर से लगने वाले जुर्माने से बचने के लिए सहमत अंतिम तारीख तक उपकरण लौटाएं।',
'डिलीवरी के समय उपकरण की जांच करें और किसी यांत्रिक समस्या की तुरंत सूचना दें।',
'उपयोग में न होने पर उपकरण को साफ और सुरक्षित रखें।',
'उपयोग के बारे में सहायता चाहिए तो ऋणदाता से सीधे संपर्क करें।'
],
'privacy_heading'=>'गोपनीयता नीति',
'privacy_intro'=>'हम उपयोगकर्ताओं की गोपनीयता का सम्मान करते हैं और किराया प्रणाली चलाने के लिए ही खाता जानकारी का उपयोग करते हैं।',
'privacy_points'=>[
'हम नाम, ईमेल, फोन नंबर, पता, उपयोगकर्ता की भूमिका, बुकिंग जानकारी, बुकिंग के लिए जमा किए गए पहचान विवरण और खाता सुरक्षा जानकारी एकत्र कर सकते हैं।',
'जानकारी का उपयोग खाता बनाने और प्रबंधित करने, किराये के अनुरोधों को संसाधित करने, बुकिंग स्थिति बताने और सिस्टम सुविधाएं प्रदान करने के लिए किया जाता है।',
'बुकिंग पूरी करने के लिए आवश्यक होने पर संबंधित ऋणदाता या किरायेदार को उस बुकिंग की जानकारी दिखाई जा सकती है।',
'पासवर्ड सुरक्षित हैशिंग के साथ संग्रहीत किए जाते हैं और किसी के साथ साझा नहीं किए जाने चाहिए।',
'उपयोगकर्ताओं को अपने लॉगिन विवरण गोपनीय रखने चाहिए और अनधिकृत पहुंच का संदेह होने पर व्यवस्थापक को सूचित करना चाहिए।',
'हम जानबूझकर उपयोगकर्ताओं की व्यक्तिगत जानकारी तीसरे पक्ष को नहीं बेचते हैं।',
'सेवा के लिए आवश्यक और सही जानकारी ही प्रदान करें।',
'खाता रिकॉर्ड, किराया इतिहास, सुरक्षा और सिस्टम संचालन के लिए आवश्यक अवधि तक जानकारी रखी जा सकती है।',
'उचित अनुमति के बिना किसी अन्य व्यक्ति की व्यक्तिगत जानकारी जमा न करें।',
'सुरक्षा और सिस्टम रखरखाव के लिए लॉगिन या गतिविधि जैसी तकनीकी जानकारी दर्ज की जा सकती है।'
],
'notice'=>'महत्वपूर्ण सूचना',
'notice_text'=>'यह पृष्ठ इस प्रोजेक्ट सिस्टम के उपयोग के नियम बताता है। यह ऋणदाता और किरायेदार के बीच किसी कानूनी समझौते का विकल्प नहीं है। लागू स्थानीय कानूनों और सुरक्षित उपकरण उपयोग के नियमों का पालन करें।',
'back'=>'पंजीकरण पर वापस जाएं'
]
];

$t = $page[$current_lang];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($t['title']); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
:root{--green:#2d6a4f;--dark:#1b4332;--light:#f4f9f5}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f8faf9;color:#333}
.navbar{background:#fff;border-bottom:1px solid #edf2ef}
.brand-logo-icon{width:42px;height:42px;background:var(--green);color:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem}
.brand-text-main{font-weight:800;color:var(--green);font-size:1.1rem;line-height:1}
.brand-text-sub{font-size:.62rem;font-weight:700;color:#555;letter-spacing:.4px}
.nav-link{font-weight:500;color:#444!important;margin:0 8px}
.nav-link:hover{color:var(--green)!important}
.language-box{display:inline-flex;align-items:center;gap:6px;margin-right:14px;color:var(--green)}
.language-box select{border:0;outline:0;background:transparent;color:var(--green);font-weight:600}
.page-card{max-width:1000px;margin:35px auto 50px;background:#fff;border-radius:18px;padding:35px 42px;box-shadow:0 8px 28px rgba(0,0,0,.06)}
h1{color:var(--dark);font-weight:800}
h2{color:var(--green);font-size:1.35rem;margin-top:30px;font-weight:800}
h3{color:#1b4332;font-size:1.05rem;font-weight:750;margin-top:22px}
li{margin-bottom:9px;line-height:1.55}
.intro{color:#5b6673}
.notice{background:var(--light);border-left:4px solid var(--green);padding:16px 18px;border-radius:8px;margin-top:28px}
.back{display:inline-flex;margin-top:25px;background:var(--green);color:#fff;text-decoration:none;padding:10px 18px;border-radius:9px;font-weight:700}
.back:hover{background:var(--dark);color:#fff}
</style>
</head>
<body>
<nav class="navbar">
<div class="container py-2">
<a class="navbar-brand d-flex align-items-center gap-3" href="index.php?lang=<?= urlencode($current_lang); ?>">
<div class="brand-logo-icon"><i class="fa-solid fa-tractor"></i></div>
<div><div class="brand-text-main"><?= htmlspecialchars($t['brand_main']); ?></div><div class="brand-text-sub"><?= htmlspecialchars($t['brand_sub']); ?></div></div>
</a>
<div class="d-flex align-items-center float-end" style="margin-top:-42px;">
<div class="language-box"><i class="fa-solid fa-globe"></i>
<select onchange="location.href=this.value;">
<option value="terms_privacy.php?lang=en" <?= $current_lang==='en'?'selected':''; ?>>English</option>
<option value="terms_privacy.php?lang=kn" <?= $current_lang==='kn'?'selected':''; ?>>ಕನ್ನಡ</option>
<option value="terms_privacy.php?lang=hi" <?= $current_lang==='hi'?'selected':''; ?>>हिंदी</option>
</select></div>
<a class="nav-link" href="index.php?lang=<?= urlencode($current_lang); ?>"><?= htmlspecialchars($t['home']); ?></a>
<a class="nav-link" href="register.php?lang=<?= urlencode($current_lang); ?>"><?= htmlspecialchars($t['register']); ?></a>
<a class="nav-link" href="login.php?lang=<?= urlencode($current_lang); ?>"><?= htmlspecialchars($t['login']); ?></a>
</div>
</div>
</nav>

<main class="container">
<div class="page-card">
<h1><?= htmlspecialchars($t['heading']); ?></h1>
<p class="intro"><?= htmlspecialchars($t['intro']); ?></p>

<h2><?= htmlspecialchars($t['terms_heading']); ?></h2>

<h3><?= htmlspecialchars($t['general']); ?></h3>
<ul><?php foreach($t['general_points'] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul>

<h3><?= htmlspecialchars($t['lender']); ?></h3>
<ul><?php foreach($t['lender_points'] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul>

<h3><?= htmlspecialchars($t['renter']); ?></h3>
<ul><?php foreach($t['renter_points'] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul>

<h3><?= htmlspecialchars($t['safety']); ?></h3>
<ul><?php foreach($t['safety_points'] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul>

<h2 id="privacy"><?= htmlspecialchars($t['privacy_heading']); ?></h2>
<p><?= htmlspecialchars($t['privacy_intro']); ?></p>
<ul><?php foreach($t['privacy_points'] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul>

<div class="notice">
<strong><i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($t['notice']); ?></strong>
<p class="mb-0 mt-2"><?= htmlspecialchars($t['notice_text']); ?></p>
</div>

<a class="back" href="register.php?lang=<?= urlencode($current_lang); ?>">
<i class="fa-solid fa-arrow-left me-2"></i><?= htmlspecialchars($t['back']); ?>
</a>
</div>
</main>
</body>
</html>
