// Master Translation Dictionary
const translations = {
    en: {
        brandTitle: "AGRICULTURE",
        brandSub: "EQUIPMENT RENTAL SYSTEM",
        navHome: '<i class="fa-solid fa-house me-1"></i> Home',
        navHow: 'How It Works',
        langLabel: '<i class="fa-solid fa-globe text-secondary me-1"></i> English',
        welcomeTitle: 'Welcome Back!',
        welcomeSub: 'Login to your account and start renting equipment.',
        loginHeading: 'Login',
        regHeading: 'Create an Account',
        regSub: 'Sign up to rent or list agricultural equipment near you.',
        labelLogin: 'Email / Phone Number',
        phLogin: 'Enter your email or mobile number',
        labelPass: 'Password',
        phPass: 'Enter your password',
        labelName: 'Full Name',
        phName: 'Enter your full name',
        labelPhone: 'Phone Number',
        phPhone: 'Enter your phone number',
        labelRole: 'I want to:',
        roleRenter: 'Rent Equipment (Renter)',
        roleLender: 'Lend Equipment (Lender)',
        rememberMe: 'Remember Me',
        forgotPass: 'Forgot Password?',
        btnLogin: 'Login <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>',
        btnRegister: 'Register <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>',
        orText: 'OR',
        linkToRegister: '<i class="fa-solid fa-user-plus me-2 text-success"></i> Don\'t have an account? <strong class="text-success">Register</strong>',
        linkToLogin: '<i class="fa-solid fa-right-to-bracket me-2 text-success"></i> Already have an account? <strong class="text-success">Login</strong>',
        termsText: 'By continuing, you agree to our <a href="#" class="text-success text-decoration-none fw-semibold">Terms & Conditions</a>.'
    },
    kn: {
        brandTitle: "ಕೃಷಿ",
        brandSub: "ಉಪಕರಣ ಬಾಡಿಗೆ ವ್ಯವಸ್ಥೆ",
        navHome: '<i class="fa-solid fa-house me-1"></i> ಮುಖಪುಟ',
        navHow: 'ಇದು ಹೇಗೆ ಕಾರ್ಯನಿರ್ವಹಿಸುತ್ತದೆ',
        langLabel: '<i class="fa-solid fa-globe text-secondary me-1"></i> ಕನ್ನಡ',
        welcomeTitle: 'ಪುನಃ ಸುಸ್ವಾಗತ!',
        welcomeSub: 'ನಿಮ್ಮ ಖಾತೆಗೆ ಲಾಗಿನ್ ಮಾಡಿ ಮತ್ತು ಉಪಕರಣಗಳನ್ನು ಬಾಡಿಗೆಗೆ ಪಡೆಯಿರಿ.',
        loginHeading: 'ಲಾಗಿನ್',
        regHeading: 'ಖಾತೆಯನ್ನು ರಚಿಸಿ',
        regSub: 'ಕೃಷಿ ಉಪಕರಣಗಳನ್ನು ಬಾಡಿಗೆಗೆ ಪಡೆಯಲು ಅಥವಾ ನೀಡಲು ನೋಂದಾಯಿಸಿ.',
        labelLogin: 'ಇಮೇಲ್ / ಮೊಬೈಲ್ ಸಂಖ್ಯೆ',
        phLogin: 'ನಿಮ್ಮ ಇಮೇಲ್ ಅಥವಾ ಮೊಬೈಲ್ ಸಂಖ್ಯೆಯನ್ನು ನಮೂದಿಸಿ',
        labelPass: 'ಪಾಸ್‌ವರ್ಡ್',
        phPass: 'ನಿಮ್ಮ ಪಾಸ್‌ವರ್ಡ್ ನಮೂದಿಸಿ',
        labelName: 'ಪೂರ್ಣ ಹೆಸರು',
        phName: 'ನಿಮ್ಮ ಪೂರ್ಣ ಹೆಸರನ್ನು ನಮೂದಿಸಿ',
        labelPhone: 'ಮೊಬೈಲ್ ಸಂಖ್ಯೆ',
        phPhone: 'ನಿಮ್ಮ ಮೊಬೈಲ್ ಸಂಖ್ಯೆಯನ್ನು ನಮೂದಿಸಿ',
        labelRole: 'ನನ್ನ ಉದ್ದೇಶ:',
        roleRenter: 'ಉಪಕರಣ ಬಾಡಿಗೆಗೆ ಪಡೆಯುವುದು (Renter)',
        roleLender: 'ಉಪಕರಣ ಬಾಡಿಗೆಗೆ ನೀಡುವುದು (Lender)',
        rememberMe: 'ನನ್ನನ್ನು ನೆನಪಿಡಿ',
        forgotPass: 'ಪಾಸ್‌ವರ್ಡ್ ಮರೆತಿದ್ದೀರಾ?',
        btnLogin: 'ಲಾಗಿನ್ <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>',
        btnRegister: 'ನೋಂದಾಯಿಸಿ <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>',
        orText: 'ಅಥವಾ',
        linkToRegister: '<i class="fa-solid fa-user-plus me-2 text-success"></i> ಖಾತೆ ಇಲ್ಲವೇ? <strong class="text-success">ನೋಂದಾಯಿಸಿ</strong>',
        linkToLogin: '<i class="fa-solid fa-right-to-bracket me-2 text-success"></i> ಈಗಾಗಲೇ ಖಾತೆ ಇದೆಯೇ? <strong class="text-success">ಲಾಗಿನ್ ಮಾಡಿ</strong>',
        termsText: 'ಮುಂದುವರಿಯುವ ಮೂಲಕ, ನೀವು ನಮ್ಮ <a href="#" class="text-success text-decoration-none fw-semibold">ನಿಯಮಗಳು ಮತ್ತು ಷರತ್ತುಗಳಿಗೆ</a> ಒಪ್ಪುತ್ತೀರಿ.'
    },
    hi: {
        brandTitle: "कृषि",
        brandSub: "उपकरण किराया प्रणाली",
        navHome: '<i class="fa-solid fa-house me-1"></i> मुख्य पृष्ठ',
        navHow: 'यह कैसे काम करता है',
        langLabel: '<i class="fa-solid fa-globe text-secondary me-1"></i> हिंदी',
        welcomeTitle: 'वापसी पर स्वागत है!',
        welcomeSub: 'अपने खाते में लॉगिन करें और उपकरण किराए पर लें।',
        loginHeading: 'लॉगिन',
        regHeading: 'खाता बनाएं',
        regSub: 'कृषि उपकरण किराए पर लेने या सूचीबद्ध करने के लिए साइन अप करें।',
        labelLogin: 'ईमेल / फोन नंबर',
        phLogin: 'अपना ईमेल या मोबाइल नंबर दर्ज करें',
        labelPass: 'पासवर्ड',
        phPass: 'अपना पासवर्ड दर्ज करें',
        labelName: 'पूरा नाम',
        phName: 'अपना पूरा नाम दर्ज करें',
        labelPhone: 'फोन नंबर',
        phPhone: 'अपना फोन नंबर दर्ज करें',
        labelRole: 'मैं चाहता हूं:',
        roleRenter: 'उपकरण किराए पर लेना (Renter)',
        roleLender: 'उपकरण किराए पर देना (Lender)',
        rememberMe: 'मुझे याद रखें',
        forgotPass: 'पासवर्ड भूल गए?',
        btnLogin: 'लॉगिन <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>',
        btnRegister: 'पंजीकरण करें <span class="arrow-icon"><i class="fa-solid fa-arrow-right"></i></span>',
        orText: 'या',
        linkToRegister: '<i class="fa-solid fa-user-plus me-2 text-success"></i> खाता नहीं है? <strong class="text-success">पंजीकरण करें</strong>',
        linkToLogin: '<i class="fa-solid fa-right-to-bracket me-2 text-success"></i> क्या आपके पास पहले से एक खाता मौजूद है? <strong class="text-success">लॉगिन करें</strong>',
        termsText: 'जारी रखकर, आप हमारी <a href="#" class="text-success text-decoration-none fw-semibold">नियम और शर्तों</a> से सहमत होते हैं।'
    }
};

// Auto-Load saved language on page load
document.addEventListener("DOMContentLoaded", function () {
    const savedLang = localStorage.getItem('appLanguage') || 'en';
    applyLanguage(savedLang);
});

// Switch & Save language locally
function changeLanguage(lang) {
    localStorage.setItem('appLanguage', lang);
    applyLanguage(lang);
}

// Safely update elements on any page
function applyLanguage(lang) {
    if (!translations[lang]) return;
    const t = translations[lang];

    const setHTML = (id, html) => { const el = document.getElementById(id); if (el) el.innerHTML = html; };
    const setText = (id, text) => { const el = document.getElementById(id); if (el) el.innerText = text; };
    const setAttr = (id, attr, val) => { const el = document.getElementById(id); if (el) el.setAttribute(attr, val); };

    // Navigation & Header
    setText('txt-brand-title', t.brandTitle);
    setText('txt-brand-sub', t.brandSub);
    setHTML('txt-nav-home', t.navHome);
    setText('txt-nav-how', t.navHow);
    setHTML('langDropdownBtn', t.langLabel);

    // Common Text
    setText('txt-welcome-title', t.welcomeTitle);
    setText('txt-welcome-sub', t.welcomeSub);
    setText('txt-login-heading', t.loginHeading);
    setText('txt-reg-heading', t.regHeading);
    setText('txt-reg-sub', t.regSub);

    // Labels & Placeholders
    setText('txt-label-login', t.labelLogin);
    setAttr('inputLogin', 'placeholder', t.phLogin);
    setText('txt-label-pass', t.labelPass);
    setAttr('passwordInput', 'placeholder', t.phPass);
    setText('txt-label-name', t.labelName);
    setAttr('inputName', 'placeholder', t.phName);
    setText('txt-label-phone', t.labelPhone);
    setAttr('inputPhone', 'placeholder', t.phPhone);
    setText('txt-label-role', t.labelRole);

    // Actions & Links
    setText('txt-remember', t.rememberMe);
    setText('txt-forgot', t.forgotPass);
    setHTML('txt-btn-login', t.btnLogin);
    setHTML('txt-btn-reg', t.btnRegister);
    setText('txt-or', t.orText);
    setHTML('txt-register-link', t.linkToRegister);
    setHTML('txt-login-link', t.linkToLogin);
    setHTML('txt-terms', t.termsText);
}