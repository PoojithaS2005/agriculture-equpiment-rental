<?php
// Common Renter Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$current_language = $current_lang ?? ($_SESSION['lang'] ?? 'en');

function renter_sidebar_link($page, $language)
{
    return $page . '?lang=' . urlencode($language);
}

require_once 'includes/lang.php';
?>

<style>
/* Renter Sidebar - Green Glassmorphism Theme */
.renter-sidebar {
    width: 250px;
    min-height: 100vh;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    padding-top: 20px;
    overflow-y: auto;
    box-sizing: border-box;

    /* Soft green glass effect */
    background: linear-gradient(
        180deg,
        rgba(190, 224, 183, 0.96) 0%,
        rgba(143, 190, 135, 0.96) 50%,
        rgba(112, 166, 105, 0.96) 100%
    );
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-right: 1px solid rgba(255, 255, 255, 0.35);
    box-shadow: 4px 0 20px rgba(35, 75, 39, 0.10);
}

/* Brand */
.renter-sidebar .renter-brand-logo {
    padding: 0 20px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.renter-sidebar .renter-brand-logo i {
    font-size: 1.8rem;
    color: #356b3c;
}

.renter-sidebar .renter-brand-name {
    color: #356b3c;
    display: block;
    line-height: 1;
    font-size: 1rem;
    font-weight: 700;
}

.renter-sidebar .renter-brand-subtitle {
    font-size: .65rem;
    color: #456c49;
    font-weight: 700;
    display: block;
    margin-top: 5px;
    white-space: nowrap;
}

/* Navigation */
.renter-sidebar .renter-nav {
    list-style: none;
    padding: 0;
    margin: 0;
}

.renter-sidebar .renter-nav-item {
    list-style: none;
    margin: 0;
    padding: 0;
}

/* Default navigation link */
.renter-sidebar .renter-nav-link {
    color: #f7fff5;
    padding: 10px 20px;
    font-size: .9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    min-height: 42px;
    box-sizing: border-box;
    border-radius: 0 8px 8px 0;
    margin-right: 3px;
    transition:
        background .25s ease,
        color .25s ease,
        padding-left .25s ease;
}

/* Navigation icons */
.renter-sidebar .renter-nav-link i {
    width: 18px;
    text-align: center;
    font-size: 1rem;
    flex-shrink: 0;
    color: inherit;
}

/* Hover effect */
.renter-sidebar .renter-nav-link:hover {
    background: rgba(255, 255, 255, 0.22);
    color: #244f2d;
    padding-left: 24px;
}

/* Active navigation item */
.renter-sidebar .renter-nav-link.active {
    background: #2f6b3a;
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 3px 10px rgba(30, 75, 39, 0.15);
}

/* Active item hover */
.renter-sidebar .renter-nav-link.active:hover {
    background: #285d32;
    color: #ffffff;
}

/* Logout */
.renter-sidebar .renter-logout {
    margin-top: 12px;
}

.renter-sidebar .renter-logout-link {
    color: #fff3f0 !important;
}

.renter-sidebar .renter-logout-link:hover {
    background: rgba(220, 53, 69, 0.14);
    color: #842029 !important;
}

/* Sidebar scrollbar */
.renter-sidebar::-webkit-scrollbar {
    width: 5px;
}

.renter-sidebar::-webkit-scrollbar-thumb {
    background: rgba(45, 106, 79, 0.30);
    border-radius: 10px;
}

.renter-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

/* Mobile responsive behavior */
@media (max-width: 768px) {
    .renter-sidebar {
        width: 250px;
        transform: translateX(-100%);
        transition: transform .3s ease;
    }

    .renter-sidebar.show {
        transform: translateX(0);
    }
}
</style>

<div class="renter-sidebar">
    <div class="renter-brand-logo">
        <i class="fa-solid fa-tractor"></i>
        <div>
            <strong class="renter-brand-name">
                <?= __('brand_main'); ?>
            </strong>
            <span class="renter-brand-subtitle">
                <?= __('brand_sub'); ?>
            </span>
        </div>
    </div>

    <ul class="renter-nav">
        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('renter_dashboard.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'renter_dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-border-all"></i>
                <span><?= __('dashboard'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('search_equipment.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'search_equipment.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span><?= __('search_equipment'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('categories.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'categories.php' || $current_page === 'category_items.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-layer-group"></i>
                <span><?= __('categories'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('notifications.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'notifications.php') ? 'active' : ''; ?>">
                <i class="fa-regular fa-bell"></i>
                <span><?= __('notifications'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('recommended.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'recommended.php') ? 'active' : ''; ?>">
                <i class="fa-regular fa-thumbs-up"></i>
                <span><?= __('recommended'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('weather_advisor.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'weather_advisor.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cloud-sun"></i>
                <span><?= __('weather_advisor'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('my_bookings.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'my_bookings.php' || $current_page === 'booking_details.php') ? 'active' : ''; ?>">
                <i class="fa-regular fa-calendar-check"></i>
                <span><?= __('my_bookings'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('rental_history.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'rental_history.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span><?= __('rental_history'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item">
            <a href="<?= renter_sidebar_link('profile.php', $current_language); ?>"
               class="renter-nav-link <?= ($current_page === 'profile.php') ? 'active' : ''; ?>">
                <i class="fa-regular fa-user"></i>
                <span><?= __('my_profile'); ?></span>
            </a>
        </li>

        <li class="renter-nav-item renter-logout">
            <a href="logout.php" class="renter-nav-link renter-logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span><?= __('logout'); ?></span>
            </a>
        </li>
    </ul>
</div>