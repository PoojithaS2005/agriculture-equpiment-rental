<?php
session_start();
// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'lender') {
        header("Location: lender_dashboard.php");
    } else {
        header("Location: renter_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agriculture Equipment Rental System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background-color: #eef5f0; /* Fallback light background */
        }

        /* Hero Wrapper */
        .hero-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Fail-Safe Background Image */
        .hero-bg-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            filter: brightness(0.95);
        }

        /* Overlay to keep text readable */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.1) 100%);
            z-index: 2;
        }

        /* Top-Left Logo Badge */
        .brand-badge {
            position: absolute;
            top: 25px;
            left: 30px;
            background: #ffffff;
            padding: 10px 22px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            display: inline-flex;
            align-items: center;
            gap: 12px;
            z-index: 10;
        }

        .brand-badge i {
            font-size: 2rem;
            color: var(--brand-green);
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--brand-green);
            line-height: 1;
            letter-spacing: 0.5px;
        }

        .brand-sub {
            font-size: 0.62rem;
            font-weight: 700;
            color: #555;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }

        /* Main Headline Area */
        .hero-content {
            position: relative;
            padding-top: 140px;
            padding-left: 80px;
            max-width: 650px;
            z-index: 10;
        }

        .hero-heading {
            font-size: 3.2rem;
            font-weight: 800;
            color: #1b4332;
            line-height: 1.15;
            margin-bottom: 20px;
            text-shadow: 0 1px 3px rgba(255, 255, 255, 0.9);
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 35px;
            line-height: 1.5;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        /* Buttons */
        .btn-action-group {
            display: flex;
            gap: 15px;
        }

        .btn-login-custom {
            background-color: var(--brand-green);
            color: #ffffff;
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(45, 106, 79, 0.3);
            transition: all 0.2s ease-in-out;
            border: 2px solid var(--brand-green);
        }

        .btn-login-custom:hover {
            background-color: var(--brand-green-hover);
            border-color: var(--brand-green-hover);
            color: #ffffff;
        }

        .btn-register-custom {
            background-color: #ffffff;
            color: var(--brand-green);
            border-radius: 10px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease-in-out;
            border: 2px solid #ffffff;
        }

        .btn-register-custom:hover {
            background-color: #f0f7f4;
            color: var(--brand-green-hover);
        }

        /* Bottom Curved White Banner */
        .bottom-features-banner {
            background-color: #ffffff;
            position: relative;
            padding: 25px 20px 20px 20px;
            margin-top: 50px;
            border-radius: 50px 50px 0 0;
            box-shadow: 0 -8px 20px rgba(0,0,0,0.06);
            z-index: 10;
        }

        .banner-leaf-icon {
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--brand-green);
            font-size: 1.8rem;
            background: #ffffff;
            border-radius: 50%;
            padding: 2px 8px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 0 10px;
        }

        .feature-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #f0f7f4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--brand-green);
            flex-shrink: 0;
        }

        .feature-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #222222;
            margin-bottom: 2px;
        }

        .feature-desc {
            font-size: 0.78rem;
            color: #666666;
            margin: 0;
            line-height: 1.2;
        }

        .feature-divider {
            border-right: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .hero-content {
                padding-left: 20px;
                padding-right: 20px;
            }
            .hero-heading {
                font-size: 2.2rem;
            }
            .feature-divider {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 15px;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

    <div class="hero-container">
        <!-- BACKGROUND IMAGE -->
        <img src="images\tractor3.jpg" class="hero-bg-img" alt="Green Farm Field">
        <div class="hero-overlay"></div>
        
        <!-- LOGO BADGE TOP LEFT -->
        <div class="brand-badge">
            <i class="fa-solid fa-tractor"></i>
            <div>
                <div class="brand-title">AGRICULTURE</div>
                <div class="brand-sub">EQUIPMENT RENTAL SYSTEM</div>
            </div>
        </div>

        <!-- HERO CONTENT -->
        <div class="hero-content">
            <h1 class="hero-heading">Smart Solutions<br>for Modern Farming</h1>
            <p class="hero-subtitle">
                Rent quality agricultural equipment easily<br>and grow your productivity.
            </p>

            <div class="btn-action-group">
                <!-- Links to login.php -->
                <a href="login.php" class="btn-login-custom">
                    <i class="fa-solid fa-user-plus"></i> Login
                </a>
                <!-- Links to register.php -->
                <a href="register.php" class="btn-register-custom">
                    <i class="fa-solid fa-user-plus"></i> Register
                </a>
            </div>
        </div>

        <!-- BOTTOM FEATURE BANNER -->
        <div class="bottom-features-banner">
            <div class="banner-leaf-icon">
                <i class="fa-solid fa-leaf"></i>
            </div>

            <div class="container">
                <div class="row align-items-center">
                    
                    <!-- Feature 1 -->
                    <div class="col-md-4 feature-divider">
                        <div class="feature-item">
                            <div class="feature-icon-circle">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div>
                                <div class="feature-title">Trusted & Secure</div>
                                <p class="feature-desc">Your data is safe with us</p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="col-md-4 feature-divider">
                        <div class="feature-item">
                            <div class="feature-icon-circle">
                                <i class="fa-solid fa-gear"></i>
                            </div>
                            <div>
                                <div class="feature-title">Quality Equipment</div>
                                <p class="feature-desc">Well-maintained and reliable</p>
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="col-md-4">
                        <div class="feature-item">
                            <div class="feature-icon-circle">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <div class="feature-title">Near You</div>
                                <p class="feature-desc">Find equipment nearby</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</body>
</html>