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
            color: #20352b;
            min-height: 100vh;
            background:
                linear-gradient(rgba(255, 255, 255, 0.10), rgba(255, 255, 255, 0.10)),
                url('images/home-farm-background.jpeg') center center / cover fixed no-repeat;
        }

        /* Frosted-glass surfaces over the farm background */
        .glass-navbar {
            background: rgba(255, 255, 255, 0.78) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.45) !important;
            box-shadow: 0 4px 24px rgba(20, 55, 35, 0.08);
        }

        .glass-hero-header {
            background: rgba(255, 255, 255, 0.76) !important;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.42) !important;
        }

        .page-content {
            position: relative;
            z-index: 1;
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
            border: 1px solid rgba(255, 255, 255, 0.58);
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            box-shadow: 0 8px 28px rgba(25, 60, 40, 0.10),
                        inset 0 1px 0 rgba(255, 255, 255, 0.45);
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.16);
            background: rgba(255, 255, 255, 0.88);
        }

        .step-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(244, 249, 245, 0.78);
            color: var(--brand-green);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
        }

        .step-card h5 {
            color: #1b4332;
        }

        .step-card p {
            color: #344e41 !important;
        }

        .step-icon {
            font-size: 2.5rem;
            color: var(--brand-green);
            margin-bottom: 15px;
        }

        /* Popular categories panel */
        .popular-categories-box {
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 18px;
            padding: 24px 28px;
            margin: 0 auto 28px;
            max-width: 980px;
            box-shadow: 0 8px 24px rgba(24, 55, 43, 0.12);
        }

        .popular-categories-box h6 {
            color: #176344;
            letter-spacing: 0.2px;
        }

        .popular-category-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #405448;
            padding: 7px 10px;
            border-radius: 10px;
            background: rgba(235, 245, 237, 0.85);
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
    <nav class="navbar navbar-expand-lg navbar-light glass-navbar py-3 border-bottom">
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
    <div class="glass-hero-header py-5 text-center border-bottom">
        <div class="container">
            <h1 class="fw-bold text-success mb-2"><?= __('how_it_works'); ?></h1>
            <p class="text-secondary mb-0"><?= __('how_it_works_sub'); ?></p>
        </div>
    </div>

    <!-- STEPS FOR RENTERS & LENDERS -->
    <div class="container my-5 page-content">

        <!-- FOR FARMERS / RENTERS -->
        <h3 class="fw-bold mb-4 text-center text-dark">
            <i class="fa-solid fa-user-gear text-success me-2"></i><?= __('for_renters'); ?>
        </h3>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">1</div>
                    <div class="step-icon"><i class="fa-solid fa-magnifying-glass-location"></i></div>
                    <h5 class="fw-bold mb-2"><?= __('search_equipment'); ?></h5>
                    <p class="text-secondary small mb-0"><?= __('search_equipment_desc'); ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">2</div>
                    <div class="step-icon"><i class="fa-regular fa-calendar-check"></i></div>
                    <h5 class="fw-bold mb-2"><?= __('book_and_pay'); ?></h5>
                    <p class="text-secondary small mb-0"><?= __('book_and_pay_desc'); ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">3</div>
                    <div class="step-icon"><i class="fa-solid fa-wheat-field"></i></div>
                    <h5 class="fw-bold mb-2"><?= __('start_farming'); ?></h5>
                    <p class="text-secondary small mb-0"><?= __('start_farming_desc'); ?></p>
                </div>
            </div>
        </div>

        <hr class="my-5 opacity-25">

        <!-- FOR EQUIPMENT OWNERS / LENDERS -->
        <h3 class="fw-bold mb-4 text-center text-dark">
            <i class="fa-solid fa-hand-holding-hand text-success me-2"></i><?= __('for_lenders'); ?>
        </h3>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">1</div>
                    <div class="step-icon"><i class="fa-solid fa-plus-square"></i></div>
                    <h5 class="fw-bold mb-2"><?= __('list_equipment'); ?></h5>
                    <p class="text-secondary small mb-0"><?= __('list_equipment_desc'); ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">2</div>
                    <div class="step-icon"><i class="fa-solid fa-check-double"></i></div>
                    <h5 class="fw-bold mb-2"><?= __('accept_requests'); ?></h5>
                    <p class="text-secondary small mb-0"><?= __('accept_requests_desc'); ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-badge">3</div>
                    <div class="step-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <h5 class="fw-bold mb-2"><?= __('earn_income'); ?></h5>
                    <p class="text-secondary small mb-0"><?= __('earn_income_desc'); ?></p>
                </div>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container pt-3">
            <div class="popular-categories-box">
                <div class="text-center">
                    <h6 class="fw-bold mb-3"><?= __('popular_categories'); ?></h6>
                    <div class="d-flex flex-wrap justify-content-center gap-2 gap-md-3 small">
                        <span class="popular-category-item"><i class="fa-solid fa-tractor text-success"></i> <?= __('tractor'); ?></span>
                        <span class="popular-category-item"><i class="fa-solid fa-wheat-awn text-success"></i> <?= __('harvesting'); ?></span>
                        <span class="popular-category-item"><i class="fa-solid fa-droplet text-success"></i> <?= __('irrigation'); ?></span>
                        <span class="popular-category-item"><i class="fa-solid fa-gears text-success"></i> <?= __('tillage'); ?></span>
                        <span class="popular-category-item"><i class="fa-solid fa-seedling text-success"></i> <?= __('seeding'); ?></span>
                        <span class="popular-category-item"><i class="fa-solid fa-spray-can text-success"></i> <?= __('spraying'); ?></span>
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
