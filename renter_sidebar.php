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
/* Premium Emerald Glassmorphism - visual styling only */
.renter-sidebar {
    width: 250px;
    min-height: 100vh;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    padding: 18px 12px 22px 12px;
    overflow-y: auto;
    box-sizing: border-box;

    background:
        radial-gradient(circle at 15% 8%, rgba(255, 255, 255, 0.18), transparent 30%),
        radial-gradient(circle at 90% 75%, rgba(45, 106, 55, 0.10), transparent 32%),
        linear-gradient(160deg, #338323 0%, #1f844c 50%, #8eb489 100%);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    border-right: 1px solid rgba(255, 255, 255, 0.22);
    box-shadow: 8px 0 28px rgba(5, 48, 39, 0.18);
}

/* Soft decorative glow; does not affect sidebar content or clicks */
.renter-sidebar::before {
    content: "";
    position: absolute;
    top: 150px;
    right: -75px;
    width: 170px;
    height: 170px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.16);
    filter: blur(35px);
    pointer-events: none;
}

.renter-sidebar .renter-brand-logo {
    position: relative;
    z-index: 1;
    min-height: 94px;
    margin: 0 3px 20px;
    padding: 17px 13px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(255,255,255,0.38), rgba(255,255,255,0.16));
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.16), 0 8px 20px rgba(0,0,0,0.10);
}

.renter-sidebar .renter-brand-logo i {
    width: 42px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 13px;
    color: #3c6342;
    font-size: 1.65rem;
    background: rgba(255,255,255,0.13);
    box-shadow: 0 4px 12px rgba(0,0,0,0.10);
}

.renter-sidebar .renter-brand-name {
    color: #21852f;
    display: block;
    line-height: 1.15;
    font-size: .96rem;
    font-weight: 800;
    letter-spacing: .35px;
}

.renter-sidebar .renter-brand-subtitle {
    font-size: .61rem;
    color: #1d4721;
    font-weight: 700;
    display: block;
    margin-top: 6px;
    white-space: nowrap;
    letter-spacing: .45px;
}

/* Navigation */
.renter-sidebar .renter-nav {
    position: relative;
    z-index: 1;
    list-style: none;
    padding: 0;
    margin: 0;
}

.renter-sidebar .renter-nav-item {
    list-style: none;
    margin: 0 0 5px;
    padding: 0;
}

.renter-sidebar .renter-nav-link {
    position: relative;
    color: #ffffff;
    padding: 11px 13px;
    font-size: .88rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 13px;
    text-decoration: none;
    min-height: 44px;
    box-sizing: border-box;
    border: 1px solid transparent;
    border-radius: 12px;
    margin-right: 0;
    transition: background .22s ease, color .22s ease, transform .22s ease,
                box-shadow .22s ease, border-color .22s ease;
}

.renter-sidebar .renter-nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1.03rem;
    flex-shrink: 0;
    color: #f7fff5;
    transition: color .22s ease, transform .22s ease;
}

.renter-sidebar .renter-nav-link:hover {
    background: rgba(255, 255, 255, 0.22);
    border-color: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    transform: translateX(3px);
}

.renter-sidebar .renter-nav-link:hover i {
    color: #ffffff;
    transform: scale(1.08);
}

/* Active page */
.renter-sidebar .renter-nav-link.active {
    background: linear-gradient(110deg, #2f6b3a 0%, #397b45 100%);
    color: #ffffff;
    font-weight: 750;
    border-color: rgba(255, 255, 255, 0.22);
    box-shadow: 0 5px 16px rgba(30, 75, 39, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.renter-sidebar .renter-nav-link.active i {
    color: #ffffff;
}

.renter-sidebar .renter-nav-link.active:hover {
    background: linear-gradient(110deg, #285d32 0%, #34713e 100%);
    color: #ffffff;
    transform: translateX(2px);
}

/* Logout */
.renter-sidebar .renter-logout {
    margin-top: 17px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.17);
}

.renter-sidebar .renter-logout-link {
    color: #fff3f0 !important;
}

.renter-sidebar .renter-logout-link i {
    color: #fff3f0;
}

.renter-sidebar .renter-logout-link:hover {
    background: rgba(220, 53, 69, 0.14);
    border-color: rgba(255, 255, 255, 0.14);
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

/* Mobile responsive behavior - existing show/hide behavior retained */
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