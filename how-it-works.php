<?php
require_once 'includes/lang.php';
if (file_exists('includes/config.php')) {
    include('includes/config.php');
}
?>
<!DOCTYPE html>
<html lang="<?= $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('how_it_works'); ?> - <?= __('title'); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --brand-green: #2d6a4f;
            --brand-green-hover: #1b4332;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #ffffff;
            color: #333;
        }

        .brand-logo-icon {
            width: 46px;
            height: 46px;
            background-color: var(--brand-green);
            color: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: 0 4px 10px rgba(45, 106, 79, 0.25);
        }

        .brand-text-main {
            font-weight: 800;
            color: var(--brand-green);
            font-size: 1.25rem;
            line-height: 1.1;
            letter-spacing: 0.5px;
        }

        .brand-text-sub {
            font-size: 0.68rem;
            font-weight: 700;
            color: #555;
            letter-spacing: 1px;
        }

        .nav-link {
            font-weight: 500;
            color: #444 !important;
            margin: 0 10px;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--brand-green) !important;
        }

        .lang-dropdown {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 6px 16px;
            font-size: 0.9rem;
            background: #fff;
            color: #333;
            cursor: pointer;
        }

        /* Step Cards */
        .step-card {
            border: 1px solid #e9ecef;
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            background: #fff;
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .step-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f4f9f5;
            color: var(--brand-green);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
        }

        .step-icon {
            font-size: 2.5rem;
            color: var(--brand-green);
            margin-bottom: 15px;
        }

        .footer-bottom {
            background-color: var(--brand-green);
            color: #ffffff;
            padding: 15px 0;
            text-align: center;
            font-size: 0.9rem;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    <!-- NAVBAR HEADER -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-tractor"></i>
                </div>
                <div>
                    <div class="brand-text-main">AGRICULTURE</div>
                    <div class="brand-text-sub">EQUIPMENT RENTAL SYSTEM</div>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center me-4">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fa-solid fa-house me-1"></i> <?= __('home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="how-it-works.php"><?= __('how_it_works'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><?= __('login'); ?></a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-globe text-secondary"></i>
                    <select class="lang-dropdown fw-bold text-success" onchange="location = this.value;">
                        <option value="?lang=en" <?= ($current_lang === 'en') ? 'selected' : ''; ?>>English</option>
                        <option value="?lang=kn" <?= ($current_lang === 'kn') ? 'selected' : ''; ?>>ಕನ್ನಡ (Kannada)</option>
                        <option value="?lang=hi" <?= ($current_lang === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                    </select>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO HEADER -->
    <div class="bg-light py-5 text-center border-bottom">
        <div class="container">
            <h1 class="fw-bold text-success mb-2"><?= __('how_it_works'); ?></h1>
            <p class="text-secondary mb-0">Simple steps to rent or list agricultural equipment in your local area.</p>
        </div>
    </div>

    <!-- STEPS FOR RENTERS & LENDERS -->
    <div class="container my-5">
        
        <!-- FOR FARMERS / RENTERS -->
        <h3 class="fw-bold mb-4 text-center text-dark"><i class="fa-solid fa-user-gear text-success me-2"></i>For Renters (Farmers)</h3>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">1</div>
                    <div class="step-icon"><i class="fa-solid fa-magnifying-glass-location"></i></div>
                    <h5 class="fw-bold mb-2">Search Equipment</h5>
                    <p class="text-secondary small mb-0">Find tractors, harvesters, or tillage tools available near your location.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">2</div>
                    <div class="step-icon"><i class="fa-regular fa-calendar-check"></i></div>
                    <h5 class="fw-bold mb-2">Book & Pay</h5>
                    <p class="text-secondary small mb-0">Select your required rental duration and confirm the booking request securely.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">3</div>
                    <div class="step-icon"><i class="fa-solid fa-wheat-field"></i></div>
                    <h5 class="fw-bold mb-2">Start Farming</h5>
                    <p class="text-secondary small mb-0">Get the equipment delivered or pick it up directly from the owner to start work.</p>
                </div>
            </div>
        </div>

        <hr class="my-5 opacity-25">

        <!-- FOR EQUIPMENT OWNERS / LENDERS -->
        <h3 class="fw-bold mb-4 text-center text-dark"><i class="fa-solid fa-hand-holding-hand text-success me-2"></i>For Equipment Owners (Lenders)</h3>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">1</div>
                    <div class="step-icon"><i class="fa-solid fa-plus-square"></i></div>
                    <h5 class="fw-bold mb-2">List Equipment</h5>
                    <p class="text-secondary small mb-0">Post details, pricing, and images of your idle tractors or farming tools.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">2</div>
                    <div class="step-icon"><i class="fa-solid fa-check-double"></i></div>
                    <h5 class="fw-bold mb-2">Accept Requests</h5>
                    <p class="text-secondary small mb-0">Review incoming rental requests from local farmers and accept them easily.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">3</div>
                    <div class="step-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <h5 class="fw-bold mb-2">Earn Income</h5>
                    <p class="text-secondary small mb-0">Receive payment directly once the rental agreement period is completed.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container pt-3">
            <hr class="my-4 text-muted opacity-25">
            <div class="row g-4 justify-content-center">
                <div class="col-md-8 text-center">
                    <h6 class="fw-bold mb-3 text-success"><?= __('popular_categories'); ?></h6>
                    <div class="d-flex flex-wrap justify-content-center gap-4 small text-secondary">
                        <span><i class="fa-solid fa-tractor me-1 text-success"></i> <?= __('tractor'); ?></span>
                        <span><i class="fa-solid fa-wheat-awn me-1 text-success"></i> <?= __('harvesting'); ?></span>
                        <span><i class="fa-solid fa-droplet me-1 text-success"></i> <?= __('irrigation'); ?></span>
                        <span><i class="fa-solid fa-gears me-1 text-success"></i> <?= __('tillage'); ?></span>
                        <span><i class="fa-solid fa-seedling me-1 text-success"></i> <?= __('seeding'); ?></span>
                        <span><i class="fa-solid fa-spray-can me-1 text-success"></i> <?= __('spraying'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container">
                © 2026 <?= __('title'); ?>. <?= __('all_rights_reserved'); ?>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>