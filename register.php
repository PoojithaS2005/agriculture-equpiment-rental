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

if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

$error = "";
$success = "";

/* =========================================================
   REGISTER TEXT
   ========================================================= */

$register_text = [

    /* =========================================================
       ENGLISH
       ========================================================= */

    'en' => [

        'title' => 'Register - Agriculture Equipment Rental System',

        'brand_main' => 'AGRICULTURE',
        'brand_sub' => 'EQUIPMENT RENTAL SYSTEM',

        'home' => 'Home',
        'how_it_works' => 'How It Works',
        'login' => 'Login',

        'create_account' => 'Create Your Account',
        'get_started' => 'and Get Started!',

        'banner_sub' =>
            'Join us and access agricultural equipment for your farming needs.',

        'register' => 'Register',

        'form_sub' =>
            'Fill in the details to create your account',

        'full_name' => 'Full Name',
        'full_name_ph' => 'Enter your full name',

        'email' => 'Email Address',
        'email_ph' => 'Enter your email address',

        'phone' => 'Phone Number',
        'phone_ph' => 'Enter your phone number',

        'address' => 'Address',
        'address_ph' => 'Enter your full address',

        'city' => 'City',
        'city_ph' => 'Enter your city',

        'district' => 'District',
        'district_ph' => 'Enter your district',

        'state' => 'State',
        'state_ph' => 'Enter your state',

        'user_type' => 'Select User Type',
        'select_user' => '-- Select User Type --',

        'renter' => 'Farmer / Renter',
        'lender' => 'Equipment Owner / Lender',

        'password' => 'Password',
        'password_ph' => 'Enter your password',

        'confirm_password' => 'Confirm Password',
        'confirm_ph' => 'Confirm your password',

        'security' => 'Security Question',
        'select_question' => '-- Select a Security Question --',

        'first_pet' => 'What was the name of your first pet?',
        'birth_city' => 'In what city were you born?',
        'mother_maiden' => "What is your mother's maiden name?",
        'first_school' => 'What was the name of your first school?',

        'answer' => 'Security Answer',
        'answer_ph' => 'Enter your answer',

        'agree' => 'I agree to the',
        'terms' => 'Terms & Conditions',
        'and' => 'and',
        'privacy' => 'Privacy Policy',

        'password_policy_title' => 'Password must contain:',
        'password_rule_length' => 'At least 8 characters',
        'password_rule_capital' => 'At least 1 capital letter (A-Z)',
        'password_rule_number' => 'At least 1 number (0-9)',
        'password_rule_special' => 'At least 1 special character',

        'err_password_policy' =>
            'Password must contain at least 8 characters, 1 capital letter, 1 number, and 1 special character.',

        'already' => 'Already have an account?',
        'login_here' => 'Login here',

        'err_terms' =>
            'Please agree to the Terms and Conditions and Privacy Policy.',

        'err_password' =>
            'Passwords do not match!',

        'err_exists' =>
            'An account with this Email or Phone number already exists!',

        'success' =>
            'Registration successful! Redirecting to your dashboard...',

        'db_error' => 'Database Error: '
    ],


    /* =========================================================
       KANNADA
       ========================================================= */

    'kn' => [

        'title' => 'ನೋಂದಣಿ - ಕೃಷಿ ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ',

        'brand_main' => 'ಕೃಷಿ',
        'brand_sub' => 'ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ',

        'home' => 'ಮುಖಪುಟ',
        'how_it_works' => 'ಇದು ಹೇಗೆ ಕೆಲಸ ಮಾಡುತ್ತದೆ',
        'login' => 'ಲಾಗಿನ್',

        'create_account' => 'ನಿಮ್ಮ ಖಾತೆಯನ್ನು ರಚಿಸಿ',
        'get_started' => 'ಮತ್ತು ಪ್ರಾರಂಭಿಸಿ!',

        'banner_sub' =>
            'ನೋಂದಣಿ ಮಾಡಿ ಮತ್ತು ನಿಮ್ಮ ಕೃಷಿ ಅಗತ್ಯಗಳಿಗೆ ಬೇಕಾದ ಉಪಕರಣಗಳನ್ನು ಪಡೆಯಿರಿ.',

        'register' => 'ನೋಂದಣಿ',

        'form_sub' =>
            'ನಿಮ್ಮ ಖಾತೆಯನ್ನು ರಚಿಸಲು ವಿವರಗಳನ್ನು ಭರ್ತಿ ಮಾಡಿ',

        'full_name' => 'ಪೂರ್ಣ ಹೆಸರು',
        'full_name_ph' => 'ನಿಮ್ಮ ಪೂರ್ಣ ಹೆಸರನ್ನು ನಮೂದಿಸಿ',

        'email' => 'ಇಮೇಲ್ ವಿಳಾಸ',
        'email_ph' => 'ನಿಮ್ಮ ಇಮೇಲ್ ವಿಳಾಸವನ್ನು ನಮೂದಿಸಿ',

        'phone' => 'ಫೋನ್ ಸಂಖ್ಯೆ',
        'phone_ph' => 'ನಿಮ್ಮ ಫೋನ್ ಸಂಖ್ಯೆಯನ್ನು ನಮೂದಿಸಿ',

        'address' => 'ವಿಳಾಸ',
        'address_ph' => 'ನಿಮ್ಮ ಪೂರ್ಣ ವಿಳಾಸವನ್ನು ನಮೂದಿಸಿ',

        'city' => 'ನಗರ',
        'city_ph' => 'ನಿಮ್ಮ ನಗರವನ್ನು ನಮೂದಿಸಿ',

        'district' => 'ಜಿಲ್ಲೆ',
        'district_ph' => 'ನಿಮ್ಮ ಜಿಲ್ಲೆಯನ್ನು ನಮೂದಿಸಿ',

        'state' => 'ರಾಜ್ಯ',
        'state_ph' => 'ನಿಮ್ಮ ರಾಜ್ಯವನ್ನು ನಮೂದಿಸಿ',

        'user_type' => 'ಬಳಕೆದಾರರ ಪ್ರಕಾರ ಆಯ್ಕೆಮಾಡಿ',
        'select_user' => '-- ಬಳಕೆದಾರರ ಪ್ರಕಾರ ಆಯ್ಕೆಮಾಡಿ --',

        'renter' => 'ರೈತ / ಬಾಡಿಗೆದಾರ',
        'lender' => 'ಉಪಕರಣ ಮಾಲೀಕ / ಸಾಲದಾತ',

        'password' => 'ಪಾಸ್‌ವರ್ಡ್',
        'password_ph' => 'ನಿಮ್ಮ ಪಾಸ್‌ವರ್ಡ್ ನಮೂದಿಸಿ',

        'confirm_password' => 'ಪಾಸ್‌ವರ್ಡ್ ದೃಢೀಕರಿಸಿ',
        'confirm_ph' => 'ನಿಮ್ಮ ಪಾಸ್‌ವರ್ಡ್ ಅನ್ನು ಮತ್ತೆ ನಮೂದಿಸಿ',

        'security' => 'ಭದ್ರತಾ ಪ್ರಶ್ನೆ',
        'select_question' => '-- ಭದ್ರತಾ ಪ್ರಶ್ನೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ --',

        'first_pet' => 'ನಿಮ್ಮ ಮೊದಲ ಸಾಕುಪ್ರಾಣಿಯ ಹೆಸರು ಏನು?',
        'birth_city' => 'ನೀವು ಯಾವ ನಗರದಲ್ಲಿ ಜನಿಸಿದ್ದೀರಿ?',
        'mother_maiden' => 'ನಿಮ್ಮ ತಾಯಿಯ ವಿವಾಹಪೂರ್ವ ಹೆಸರು ಏನು?',
        'first_school' => 'ನಿಮ್ಮ ಮೊದಲ ಶಾಲೆಯ ಹೆಸರು ಏನು?',

        'answer' => 'ಭದ್ರತಾ ಉತ್ತರ',
        'answer_ph' => 'ನಿಮ್ಮ ಉತ್ತರವನ್ನು ನಮೂದಿಸಿ',

        'agree' => 'ನಾನು ಒಪ್ಪುತ್ತೇನೆ',
        'terms' => 'ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳು',
        'and' => 'ಮತ್ತು',
        'privacy' => 'ಗೌಪ್ಯತಾ ನೀತಿ',

        'password_policy_title' =>
            'ಪಾಸ್‌ವರ್ಡ್‌ನಲ್ಲಿ ಇವು ಇರಬೇಕು:',

        'password_rule_length' =>
            'ಕನಿಷ್ಠ 8 ಅಕ್ಷರಗಳು',

        'password_rule_capital' =>
            'ಕನಿಷ್ಠ 1 ದೊಡ್ಡ ಅಕ್ಷರ (A-Z)',

        'password_rule_number' =>
            'ಕನಿಷ್ಠ 1 ಸಂಖ್ಯೆ (0-9)',

        'password_rule_special' =>
            'ಕನಿಷ್ಠ 1 ವಿಶೇಷ ಅಕ್ಷರ',

        'err_password_policy' =>
            'ಪಾಸ್‌ವರ್ಡ್‌ನಲ್ಲಿ ಕನಿಷ್ಠ 8 ಅಕ್ಷರಗಳು, 1 ದೊಡ್ಡ ಅಕ್ಷರ, 1 ಸಂಖ್ಯೆ ಮತ್ತು 1 ವಿಶೇಷ ಅಕ್ಷರ ಇರಬೇಕು.',

        'already' =>
            'ಈಗಾಗಲೇ ಖಾತೆ ಇದೆಯೇ?',

        'login_here' =>
            'ಇಲ್ಲಿ ಲಾಗಿನ್ ಮಾಡಿ',

        'err_terms' =>
            'ದಯವಿಟ್ಟು ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳು ಹಾಗೂ ಗೌಪ್ಯತಾ ನೀತಿಗೆ ಒಪ್ಪಿಕೊಳ್ಳಿ.',

        'err_password' =>
            'ಪಾಸ್‌ವರ್ಡ್‌ಗಳು ಹೊಂದಿಕೆಯಾಗುತ್ತಿಲ್ಲ!',

        'err_exists' =>
            'ಈ ಇಮೇಲ್ ಅಥವಾ ಫೋನ್ ಸಂಖ್ಯೆಯಿಂದ ಈಗಾಗಲೇ ಖಾತೆ ಇದೆ!',

        'success' =>
            'ನೋಂದಣಿ ಯಶಸ್ವಿಯಾಗಿದೆ! ನಿಮ್ಮ ಡ್ಯಾಶ್‌ಬೋರ್ಡ್‌ಗೆ ಕಳುಹಿಸಲಾಗುತ್ತಿದೆ...',

        'db_error' =>
            'ಡೇಟಾಬೇಸ್ ದೋಷ: '
    ],


    /* =========================================================
       HINDI
       ========================================================= */

    'hi' => [

        'title' => 'पंजीकरण - कृषि उपकरण किराया प्रणाली',

        'brand_main' => 'कृषि',
        'brand_sub' => 'उपकरण किराया प्रणाली',

        'home' => 'होम',
        'how_it_works' => 'यह कैसे काम करता है',
        'login' => 'लॉगिन',

        'create_account' => 'अपना खाता बनाएं',
        'get_started' => 'और शुरुआत करें!',

        'banner_sub' =>
            'पंजीकरण करें और अपनी खेती की जरूरतों के लिए कृषि उपकरण प्राप्त करें।',

        'register' => 'पंजीकरण',

        'form_sub' =>
            'अपना खाता बनाने के लिए विवरण भरें',

        'full_name' => 'पूरा नाम',
        'full_name_ph' => 'अपना पूरा नाम दर्ज करें',

        'email' => 'ईमेल पता',
        'email_ph' => 'अपना ईमेल पता दर्ज करें',

        'phone' => 'फोन नंबर',
        'phone_ph' => 'अपना फोन नंबर दर्ज करें',

        'address' => 'पता',
        'address_ph' => 'अपना पूरा पता दर्ज करें',

        'city' => 'शहर',
        'city_ph' => 'अपना शहर दर्ज करें',

        'district' => 'जिला',
        'district_ph' => 'अपना जिला दर्ज करें',

        'state' => 'राज्य',
        'state_ph' => 'अपना राज्य दर्ज करें',

        'user_type' => 'उपयोगकर्ता प्रकार चुनें',
        'select_user' => '-- उपयोगकर्ता प्रकार चुनें --',

        'renter' => 'किसान / किरायेदार',
        'lender' => 'उपकरण मालिक / ऋणदाता',

        'password' => 'पासवर्ड',
        'password_ph' => 'अपना पासवर्ड दर्ज करें',

        'confirm_password' => 'पासवर्ड की पुष्टि करें',
        'confirm_ph' => 'अपना पासवर्ड फिर से दर्ज करें',

        'security' => 'सुरक्षा प्रश्न',
        'select_question' => '-- सुरक्षा प्रश्न चुनें --',

        'first_pet' => 'आपके पहले पालतू जानवर का नाम क्या था?',
        'birth_city' => 'आपका जन्म किस शहर में हुआ था?',
        'mother_maiden' => 'आपकी माँ का विवाह से पहले का नाम क्या था?',
        'first_school' => 'आपके पहले स्कूल का नाम क्या था?',

        'answer' => 'सुरक्षा उत्तर',
        'answer_ph' => 'अपना उत्तर दर्ज करें',

        'agree' => 'मैं सहमत हूँ',
        'terms' => 'नियम और शर्तें',
        'and' => 'और',
        'privacy' => 'गोपनीयता नीति',

        'password_policy_title' =>
            'पासवर्ड में ये होना चाहिए:',

        'password_rule_length' =>
            'कम से कम 8 अक्षर',

        'password_rule_capital' =>
            'कम से कम 1 बड़ा अक्षर (A-Z)',

        'password_rule_number' =>
            'कम से कम 1 संख्या (0-9)',

        'password_rule_special' =>
            'कम से कम 1 विशेष वर्ण',

        'err_password_policy' =>
            'पासवर्ड में कम से कम 8 अक्षर, 1 बड़ा अक्षर, 1 संख्या और 1 विशेष वर्ण होना चाहिए।',

        'already' =>
            'क्या आपके पास पहले से खाता है?',

        'login_here' =>
            'यहाँ लॉगिन करें',

        'err_terms' =>
            'कृपया नियम और शर्तों तथा गोपनीयता नीति से सहमत हों।',

        'err_password' =>
            'पासवर्ड मेल नहीं खाते!',

        'err_exists' =>
            'इस ईमेल या फोन नंबर से पहले से एक खाता मौजूद है!',

        'success' =>
            'पंजीकरण सफल हुआ! आपको आपके डैशबोर्ड पर भेजा जा रहा है...',

        'db_error' =>
            'डेटाबेस त्रुटि: '
    ]
];

$t = $register_text[$current_lang];


/* =========================================================
   REGISTRATION PROCESS
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['full_name'] ?? '')
    );

    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'] ?? '')
    );

    $phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['phone'] ?? '')
    );

    $address = mysqli_real_escape_string(
        $conn,
        trim($_POST['address'] ?? '')
    );

    $city = mysqli_real_escape_string(
        $conn,
        trim($_POST['city'] ?? '')
    );

    $district = mysqli_real_escape_string(
        $conn,
        trim($_POST['district'] ?? '')
    );

    $state = mysqli_real_escape_string(
        $conn,
        trim($_POST['state'] ?? '')
    );

    $user_type = mysqli_real_escape_string(
        $conn,
        trim($_POST['user_type'] ?? '')
    );

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $security_question = mysqli_real_escape_string(
        $conn,
        trim($_POST['security_question'] ?? '')
    );

    $security_answer = mysqli_real_escape_string(
        $conn,
        trim($_POST['security_answer'] ?? '')
    );


    /* =====================================================
       PASSWORD VALIDATION
       ===================================================== */

    $password_is_valid =
        strlen($password) >= 8 &&
        preg_match('/[A-Z]/', $password) &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[^A-Za-z0-9]/', $password);


    /* =====================================================
       FORM VALIDATION
       ===================================================== */

    if (!isset($_POST['terms'])) {

        $error = $t['err_terms'];

    } elseif (!$password_is_valid) {

        $error = $t['err_password_policy'];

    } elseif ($password !== $confirm_password) {

        $error = $t['err_password'];

    } else {

        /* =================================================
           CHECK EXISTING EMAIL OR PHONE
           ================================================= */

        $check_sql = "
            SELECT *
            FROM users
            WHERE email='$email'
               OR phone='$phone'
        ";

        $check_res = mysqli_query($conn, $check_sql);

        if ($check_res && mysqli_num_rows($check_res) > 0) {

            $error = $t['err_exists'];

        } else {

            /* =============================================
               HASH PASSWORD
               ============================================= */

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            /* =============================================
               INSERT USER
               ============================================= */

            $insert_sql = "
                INSERT INTO users
                (
                    full_name,
                    email,
                    phone,
                    address,
                    city,
                    district,
                    state,
                    role,
                    password,
                    security_question,
                    security_answer
                )
                VALUES
                (
                    '$full_name',
                    '$email',
                    '$phone',
                    '$address',
                    '$city',
                    '$district',
                    '$state',
                    '$user_type',
                    '$hashed_password',
                    '$security_question',
                    '$security_answer'
                )
            ";

            if (mysqli_query($conn, $insert_sql)) {

                $user_id = mysqli_insert_id($conn);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user_type;

                $target_page =
                    ($user_type === 'lender')
                    ? 'lender_dashboard.php'
                    : 'renter_dashboard.php';

                $success = $t['success'];

                echo "
                <script>
                    setTimeout(function() {
                        window.location.href = " .
                        json_encode($target_page) .
                        ";
                    }, 1500);
                </script>
                ";

            } else {

                $error =
                    $t['db_error'] .
                    mysqli_error($conn);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang); ?>">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars($t['title']); ?>
    </title>


    <!-- Bootstrap 5 CSS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    >


    <style>

        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
            --brand-light-bg: #f4f9f5;
        }


        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8faf9;
            color: #333;
        }


        /* =====================================================
           NAVBAR
           ===================================================== */

        .brand-logo-icon {
            width: 42px;
            height: 42px;
            background-color: var(--brand-green);
            color: #ffffff;
            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 1.3rem;
        }


        .brand-text-main {
            font-weight: 800;
            color: var(--brand-green);
            font-size: 1.25rem;
            line-height: 1;
        }


        .brand-text-sub {
            font-size: 0.65rem;
            font-weight: 700;
            color: #555;
            letter-spacing: 0.5px;
        }


        .nav-link {
            font-weight: 500;
            color: #444 !important;
            margin: 0 8px;
            font-size: 0.95rem;
        }


        .nav-link:hover {
            color: var(--brand-green) !important;
        }


        .btn-outline-login {
            border: 1.5px solid var(--brand-green);
            color: var(--brand-green);

            border-radius: 10px;

            padding: 6px 20px;

            font-weight: 600;

            text-decoration: none;

            display: inline-flex;
            align-items: center;

            gap: 8px;
        }


        .btn-outline-login:hover {
            background-color: var(--brand-green);
            color: #fff;
        }


        .language-box {
            display: inline-flex;
            align-items: center;

            gap: 6px;

            margin-right: 14px;

            color: var(--brand-green);
        }


        .language-box i {
            font-size: 0.95rem;
        }


        .language-box select {
            border: none;
            outline: none;

            background: transparent;

            color: var(--brand-green);

            font-weight: 600;

            cursor: pointer;
        }


        /* =====================================================
           OUTER CARD
           ===================================================== */

        .register-outer-card {
            background: #ffffff;

            border-radius: 20px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.06);

            overflow: hidden;

            border: 1px solid #eef2f0;

            margin-top: 20px;
            margin-bottom: 40px;
        }


        /* =====================================================
           LEFT BANNER
           ===================================================== */

        .left-banner-panel {
            background-color: var(--brand-light-bg);

            padding: 40px 30px 20px 30px;

            display: flex;
            flex-direction: column;

            justify-content: space-between;

            position: relative;

            height: 100%;
        }


        .banner-heading {
            font-weight: 800;

            color: #1b4332;

            font-size: 2rem;

            line-height: 1.2;
        }


        .banner-line {
            width: 40px;
            height: 4px;

            background-color: var(--brand-green);

            margin: 15px 0;

            border-radius: 2px;
        }


        .banner-subtext {
            color: #4a5568;

            font-size: 0.95rem;

            line-height: 1.5;

            margin-bottom: 20px;
        }


        .banner-image-wrapper {
            position: relative;

            border-radius: 16px;

            overflow: hidden;

            margin-top: 10px;

            height: 380px;
        }


        .banner-image-wrapper img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }


        /* =====================================================
           RIGHT FORM
           ===================================================== */

        .right-form-panel {
            padding: 40px;
        }


        .form-header-title {
            font-weight: 800;

            font-size: 1.8rem;

            color: #111;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 10px;
        }


        .form-header-title i {
            color: var(--brand-green);
        }


        .form-header-sub {
            text-align: center;

            color: #666;

            font-size: 0.9rem;

            margin-bottom: 25px;
        }


        .form-label {
            font-weight: 700;

            font-size: 0.85rem;

            color: #222;

            margin-bottom: 6px;
        }


        .input-group-custom {
            position: relative;
        }


        .input-group-custom .form-control,
        .input-group-custom .form-select {
            border-radius: 10px;

            padding: 10px 12px 10px 40px;

            font-size: 0.9rem;

            border: 1px solid #dce1e5;

            background-color: #fff;
        }


        .input-group-custom .form-control:focus,
        .input-group-custom .form-select:focus {
            border-color: var(--brand-green);

            box-shadow:
                0 0 0 0.2rem
                rgba(45, 106, 79, 0.15);
        }


        .input-icon-left {
            position: absolute;

            left: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #777;

            font-size: 0.9rem;

            z-index: 5;
        }


        .input-icon-right {
            position: absolute;

            right: 14px;

            top: 50%;

            transform: translateY(-50%);

            color: #777;

            cursor: pointer;

            font-size: 0.9rem;

            z-index: 5;
        }


        /* =====================================================
           PASSWORD POLICY
           ===================================================== */

        .password-policy {
            display: none;

            background: #fff8f8;

            border: 1px solid #ffb3b3;

            border-radius: 10px;

            padding: 12px 14px;

            margin-top: 8px;

            font-size: 0.82rem;

            line-height: 1.5;
        }


        .password-policy.show {
            display: block;
        }


        .password-policy-title {
            display: block;

            color: #1f2937;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .password-rule {
            color: #dc3545;

            margin: 3px 0;

            transition: color 0.2s ease;
        }


        .password-rule .rule-bullet {
            display: inline-block;

            width: 14px;

            font-size: 1.05rem;

            font-weight: 700;
        }


        .password-rule.valid {
            color: #198754;
        }


        .password-invalid {
            border-color: #dc3545 !important;

            box-shadow:
                0 0 0 0.2rem
                rgba(220, 53, 69, 0.15) !important;
        }


        .password-valid {
            border-color: #198754 !important;

            box-shadow:
                0 0 0 0.2rem
                rgba(25, 135, 84, 0.12) !important;
        }


        /* =====================================================
           PASSWORD ERROR POPUP
           ===================================================== */

        .password-error-popup {
            position: fixed;

            top: 25px;

            left: 50%;

            transform:
                translateX(-50%)
                translateY(-20px);

            z-index: 9999;

            width: min(92%, 520px);

            background: #dc3545;

            color: #fff;

            border: 3px solid #ffb3b3;

            border-radius: 14px;

            padding: 15px 18px;

            box-shadow:
                0 12px 35px
                rgba(220, 53, 69, 0.45);

            display: none;

            font-weight: 600;

            animation:
                passwordPopupIn
                0.25s ease forwards;
        }


        .password-error-popup.show {
            display: flex;

            align-items: center;

            gap: 12px;
        }


        .password-error-popup i {
            font-size: 1.35rem;

            flex-shrink: 0;
        }


        @keyframes passwordPopupIn {

            from {
                opacity: 0;

                transform:
                    translateX(-50%)
                    translateY(-20px);
            }

            to {
                opacity: 1;

                transform:
                    translateX(-50%)
                    translateY(0);
            }

        }


        /* =====================================================
           SECURITY DIVIDER
           ===================================================== */

        .security-divider {
            display: flex;

            align-items: center;

            text-align: center;

            margin: 25px 0 20px 0;

            color: var(--brand-green);

            font-weight: 700;

            font-size: 0.95rem;
        }


        .security-divider::before,
        .security-divider::after {
            content: '';

            flex: 1;

            border-bottom:
                1.5px solid #81c784;
        }


        .security-divider span {
            padding: 0 15px;

            display: flex;

            align-items: center;

            gap: 6px;
        }


        /* =====================================================
           REGISTER BUTTON
           ===================================================== */

        .btn-register-submit {
            background-color: var(--brand-green);

            color: #ffffff;

            border-radius: 10px;

            padding: 12px;

            font-weight: 700;

            font-size: 1rem;

            border: none;

            width: 100%;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            box-shadow:
                0 4px 12px
                rgba(45, 106, 79, 0.2);

            transition:
                all 0.2s ease-in-out;
        }


        .btn-register-submit:hover {
            background-color:
                var(--brand-green-hover);

            color: #ffffff;
        }


        /* =====================================================
           LOGIN FOOTER
           ===================================================== */

        .login-link-footer {
            text-align: center;

            font-size: 0.9rem;

            color: #555;

            margin-top: 15px;
        }


        .login-link-footer a {
            color: var(--brand-green);

            font-weight: 700;

            text-decoration: none;
        }


        .login-link-footer a:hover {
            text-decoration: underline;
        }

    </style>

</head>


<body>


<!-- =========================================================
     PASSWORD ERROR POPUP
     ========================================================= -->

<div
    id="passwordErrorPopup"
    class="password-error-popup"
    role="alert"
>

    <i class="fa-solid fa-circle-exclamation"></i>

    <span id="passwordErrorMessage">
        <?= htmlspecialchars($t['err_password_policy']); ?>
    </span>

</div>


<!-- =========================================================
     NAVBAR
     ========================================================= -->

<nav class="navbar navbar-expand-lg">

    <div class="container">


        <a
            class="navbar-brand d-flex align-items-center gap-3"
            href="index.php?lang=<?= urlencode($current_lang); ?>"
        >

            <div class="brand-logo-icon">

                <i class="fa-solid fa-tractor"></i>

            </div>


            <div>

                <div class="brand-text-main">

                    <?= htmlspecialchars($t['brand_main']); ?>

                </div>


                <div class="brand-text-sub">

                    <?= htmlspecialchars($t['brand_sub']); ?>

                </div>

            </div>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse justify-content-end"
            id="navbarNav"
        >

            <ul class="navbar-nav align-items-center me-3">


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="index.php?lang=<?= urlencode($current_lang); ?>"
                    >

                        <?= htmlspecialchars($t['home']); ?>

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#"
                    >

                        <?= htmlspecialchars($t['how_it_works']); ?>

                    </a>

                </li>


            </ul>


            <!-- Language -->

            <div class="language-box">

                <i class="fa-solid fa-globe"></i>

                <select
                    onchange="location.href=this.value;"
                >

                    <option
                        value="register.php?lang=en"
                        <?= $current_lang === 'en' ? 'selected' : ''; ?>
                    >
                        English
                    </option>

                    <option
                        value="register.php?lang=kn"
                        <?= $current_lang === 'kn' ? 'selected' : ''; ?>
                    >
                        ಕನ್ನಡ
                    </option>

                    <option
                        value="register.php?lang=hi"
                        <?= $current_lang === 'hi' ? 'selected' : ''; ?>
                    >
                        हिंदी
                    </option>

                </select>

            </div>


            <!-- Login -->

            <a
                href="login.php?lang=<?= urlencode($current_lang); ?>"
                class="btn-outline-login"
            >

                <i class="fa-regular fa-user"></i>

                <?= htmlspecialchars($t['login']); ?>

            </a>


        </div>

    </div>

</nav>


<!-- =========================================================
     MAIN REGISTRATION CARD
     ========================================================= -->

<div class="container">

    <div class="register-outer-card">

        <div class="row g-0">


            <!-- =================================================
                 LEFT PANEL
                 ================================================= -->

            <div class="col-lg-5 d-none d-lg-block">

                <div class="left-banner-panel">

                    <div>

                        <h2 class="banner-heading">

                            <?= htmlspecialchars($t['create_account']); ?>

                            <br>

                            <?= htmlspecialchars($t['get_started']); ?>

                        </h2>


                        <div class="banner-line"></div>


                        <p class="banner-subtext">

                            <?= htmlspecialchars($t['banner_sub']); ?>

                        </p>

                    </div>


                    <div class="banner-image-wrapper">

                        <img
                            src="images/tractor.jpg"
                            alt="AgriRent Tractor"
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                 RIGHT FORM PANEL
                 ================================================= -->

            <div class="col-lg-7">

                <div class="right-form-panel">


                    <div class="form-header-title">

                        <i class="fa-solid fa-user-plus"></i>

                        <?= htmlspecialchars($t['register']); ?>

                    </div>


                    <p class="form-header-sub">

                        <?= htmlspecialchars($t['form_sub']); ?>

                    </p>


                    <!-- Error -->

                    <?php if ($error != ""): ?>

                        <div class="alert alert-danger py-2 text-center small">

                            <?= htmlspecialchars($error); ?>

                        </div>

                    <?php endif; ?>


                    <!-- Success -->

                    <?php if ($success != ""): ?>

                        <div class="alert alert-success py-2 text-center small">

                            <?= htmlspecialchars($success); ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================================
                         FORM
                         ================================================= -->

                    <form
                        method="POST"
                        action=""
                        id="registerForm"
                    >


                        <div class="row g-3">


                            <!-- Full Name -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['full_name']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-regular fa-user input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="full_name"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['full_name_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- Email -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['email']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-regular fa-envelope input-icon-left"
                                    ></i>


                                    <input
                                        type="email"
                                        name="email"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['email_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- Phone -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['phone']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-phone input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="phone"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['phone_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- Address -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['address']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-location-dot input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="address"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['address_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- City -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['city']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-city input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="city"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['city_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- District -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['district']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-map-location-dot input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="district"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['district_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- State -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['state']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-map input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="state"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['state_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- User Type -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['user_type']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-users input-icon-left"
                                    ></i>


                                    <select
                                        name="user_type"
                                        class="form-select"
                                        required
                                    >

                                        <option
                                            value=""
                                            selected
                                            disabled
                                        >

                                            <?= htmlspecialchars($t['select_user']); ?>

                                        </option>


                                        <option value="renter">

                                            <?= htmlspecialchars($t['renter']); ?>

                                        </option>


                                        <option value="lender">

                                            <?= htmlspecialchars($t['lender']); ?>

                                        </option>

                                    </select>

                                </div>

                            </div>


                            <!-- Password -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['password']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-lock input-icon-left"
                                    ></i>


                                    <input
                                        type="password"
                                        name="password"
                                        id="passInput"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['password_ph']); ?>"
                                        required
                                    >


                                    <i
                                        class="fa-regular fa-eye-slash input-icon-right"
                                        id="togglePass"
                                    ></i>

                                </div>


                                <!-- Password Rules -->

                                <div
                                    class="password-policy"
                                    id="passwordPolicy"
                                >

                                    <span class="password-policy-title">

                                        <?= htmlspecialchars($t['password_policy_title']); ?>

                                    </span>


                                    <div
                                        class="password-rule"
                                        id="lengthRule"
                                    >

                                        <span class="rule-bullet">•</span>

                                        <?= htmlspecialchars($t['password_rule_length']); ?>

                                    </div>


                                    <div
                                        class="password-rule"
                                        id="capitalRule"
                                    >

                                        <span class="rule-bullet">•</span>

                                        <?= htmlspecialchars($t['password_rule_capital']); ?>

                                    </div>


                                    <div
                                        class="password-rule"
                                        id="numberRule"
                                    >

                                        <span class="rule-bullet">•</span>

                                        <?= htmlspecialchars($t['password_rule_number']); ?>

                                    </div>


                                    <div
                                        class="password-rule"
                                        id="specialRule"
                                    >

                                        <span class="rule-bullet">•</span>

                                        <?= htmlspecialchars($t['password_rule_special']); ?>

                                    </div>

                                </div>

                            </div>


                            <!-- Confirm Password -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['confirm_password']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-lock input-icon-left"
                                    ></i>


                                    <input
                                        type="password"
                                        name="confirm_password"
                                        id="confirmPassInput"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['confirm_ph']); ?>"
                                        required
                                    >


                                    <i
                                        class="fa-regular fa-eye-slash input-icon-right"
                                        id="toggleConfirmPass"
                                    ></i>

                                </div>

                            </div>


                        </div>


                        <!-- =================================================
                             SECURITY SECTION
                             ================================================= -->

                        <div class="security-divider">

                            <span>

                                <i
                                    class="fa-solid fa-shield-halved"
                                ></i>

                                <?= htmlspecialchars($t['security']); ?>

                            </span>

                        </div>


                        <div class="row g-3 mb-3">


                            <!-- Security Question -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['security']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-regular fa-circle-question input-icon-left"
                                    ></i>


                                    <select
                                        name="security_question"
                                        class="form-select"
                                        required
                                    >

                                        <option
                                            value=""
                                            selected
                                            disabled
                                        >

                                            <?= htmlspecialchars($t['select_question']); ?>

                                        </option>


                                        <option value="first_pet">

                                            <?= htmlspecialchars($t['first_pet']); ?>

                                        </option>


                                        <option value="birth_city">

                                            <?= htmlspecialchars($t['birth_city']); ?>

                                        </option>


                                        <option value="mother_maiden">

                                            <?= htmlspecialchars($t['mother_maiden']); ?>

                                        </option>


                                        <option value="first_school">

                                            <?= htmlspecialchars($t['first_school']); ?>

                                        </option>

                                    </select>

                                </div>

                            </div>


                            <!-- Security Answer -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <?= htmlspecialchars($t['answer']); ?>

                                </label>


                                <div class="input-group-custom">

                                    <i
                                        class="fa-solid fa-lock input-icon-left"
                                    ></i>


                                    <input
                                        type="text"
                                        name="security_answer"
                                        class="form-control"
                                        placeholder="<?= htmlspecialchars($t['answer_ph']); ?>"
                                        required
                                    >

                                </div>

                            </div>


                        </div>


                        <!-- =================================================
                             TERMS & CONDITIONS
                             ================================================= -->

                        <div class="form-check mb-4 mt-2">

                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="terms"
                                id="termsCheck"
                                required
                            >


                            <label
                                class="form-check-label small text-secondary"
                                for="termsCheck"
                            >

                                <?= htmlspecialchars($t['agree']); ?>


                                <a
                                    href="terms_privacy.php?lang=<?= urlencode($current_lang); ?>&from=register"
                                    class="text-success fw-semibold text-decoration-underline"
                                    target="_blank"
                                >

                                    <?= htmlspecialchars($t['terms']); ?>

                                </a>


                                <?= htmlspecialchars($t['and']); ?>


                                <a
                                    href="terms_privacy.php?lang=<?= urlencode($current_lang); ?>&from=register#privacy"
                                    class="text-success fw-semibold text-decoration-underline"
                                    target="_blank"
                                >

                                    <?= htmlspecialchars($t['privacy']); ?>

                                </a>

                            </label>

                        </div>


                        <!-- =================================================
                             REGISTER BUTTON
                             ================================================= -->

                        <button
                            type="submit"
                            class="btn-register-submit"
                        >

                            <i class="fa-solid fa-user-plus"></i>

                            <?= htmlspecialchars($t['register']); ?>

                        </button>


                        <!-- =================================================
                             LOGIN LINK
                             ================================================= -->

                        <div class="login-link-footer">

                            <?= htmlspecialchars($t['already']); ?>


                            <a
                                href="login.php?lang=<?= urlencode($current_lang); ?>"
                            >

                                <?= htmlspecialchars($t['login_here']); ?>

                            </a>

                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     BOOTSTRAP JS
     ========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   PASSWORD VISIBILITY - PASSWORD
   ========================================================= */

const togglePass =
    document.querySelector('#togglePass');

const passInput =
    document.querySelector('#passInput');


togglePass.addEventListener('click', function () {

    const type =
        passInput.getAttribute('type') === 'password'
            ? 'text'
            : 'password';

    passInput.setAttribute('type', type);

    this.classList.toggle('fa-eye');

    this.classList.toggle('fa-eye-slash');

});


/* =========================================================
   PASSWORD VISIBILITY - CONFIRM PASSWORD
   ========================================================= */

const toggleConfirmPass =
    document.querySelector('#toggleConfirmPass');

const confirmPassInput =
    document.querySelector('#confirmPassInput');


toggleConfirmPass.addEventListener('click', function () {

    const type =
        confirmPassInput.getAttribute('type') === 'password'
            ? 'text'
            : 'password';

    confirmPassInput.setAttribute('type', type);

    this.classList.toggle('fa-eye');

    this.classList.toggle('fa-eye-slash');

});


/* =========================================================
   PASSWORD POLICY
   ========================================================= */

const registerForm =
    document.querySelector('#registerForm');

const passwordPolicy =
    document.querySelector('#passwordPolicy');

const lengthRule =
    document.querySelector('#lengthRule');

const capitalRule =
    document.querySelector('#capitalRule');

const numberRule =
    document.querySelector('#numberRule');

const specialRule =
    document.querySelector('#specialRule');

const passwordErrorPopup =
    document.querySelector('#passwordErrorPopup');

const passwordErrorMessage =
    document.querySelector('#passwordErrorMessage');


/* =========================================================
   CHECK PASSWORD RULES
   ========================================================= */

function checkPasswordRules() {

    const value = passInput.value;

    const hasLength =
        value.length >= 8;

    const hasCapital =
        /[A-Z]/.test(value);

    const hasNumber =
        /[0-9]/.test(value);

    const hasSpecial =
        /[^A-Za-z0-9]/.test(value);


    updatePasswordRule(
        lengthRule,
        hasLength
    );

    updatePasswordRule(
        capitalRule,
        hasCapital
    );

    updatePasswordRule(
        numberRule,
        hasNumber
    );

    updatePasswordRule(
        specialRule,
        hasSpecial
    );


    const valid =
        hasLength &&
        hasCapital &&
        hasNumber &&
        hasSpecial;


    if (value.length === 0) {

        passInput.classList.remove(
            'password-invalid',
            'password-valid'
        );

    } else if (valid) {

        passInput.classList.remove(
            'password-invalid'
        );

        passInput.classList.add(
            'password-valid'
        );

    } else {

        passInput.classList.remove(
            'password-valid'
        );

        passInput.classList.add(
            'password-invalid'
        );
    }


    return valid;
}


/* =========================================================
   UPDATE PASSWORD RULE
   ========================================================= */

function updatePasswordRule(
    element,
    valid
) {

    if (valid) {

        element.classList.add('valid');

    } else {

        element.classList.remove('valid');

    }

}


/* =========================================================
   SHOW PASSWORD ERROR
   ========================================================= */

function showPasswordError(message) {

    passwordErrorMessage.textContent =
        message;

    passwordErrorPopup.classList.add(
        'show'
    );


    setTimeout(function () {

        passwordErrorPopup.classList.remove(
            'show'
        );

    }, 4000);

}


/* =========================================================
   PASSWORD FOCUS
   ========================================================= */

passInput.addEventListener(
    'focus',
    function () {

        passwordPolicy.classList.add(
            'show'
        );

    }
);


/* =========================================================
   PASSWORD INPUT
   ========================================================= */

passInput.addEventListener(
    'input',
    function () {

        passwordPolicy.classList.add(
            'show'
        );

        checkPasswordRules();

    }
);


/* =========================================================
   FORM SUBMIT VALIDATION
   ========================================================= */

registerForm.addEventListener(
    'submit',
    function (event) {

        const passwordValid =
            checkPasswordRules();


        /* Password policy */

        if (!passwordValid) {

            event.preventDefault();

            passwordPolicy.classList.add(
                'show'
            );

            showPasswordError(
                <?= json_encode($t['err_password_policy']); ?>
            );

            passInput.focus();

            return;
        }


        /* Confirm password */

        if (
            passInput.value !==
            confirmPassInput.value
        ) {

            event.preventDefault();

            showPasswordError(
                <?= json_encode($t['err_password']); ?>
            );

            confirmPassInput.focus();

            return;
        }

    }
);

</script>

</body>
</html>