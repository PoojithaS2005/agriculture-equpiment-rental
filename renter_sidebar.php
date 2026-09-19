<?php
// Common Renter Sidebar

$current_page = basename($_SERVER['PHP_SELF']);
$current_language = $current_lang ?? ($_SESSION['lang'] ?? 'en');

function renter_sidebar_link($page, $language)
{
    return $page . '?lang=' . urlencode($language);
}
?>

<style>
    /* ================================
       COMMON RENTER SIDEBAR
       ================================ */

    .renter-sidebar {
        width: 250px;
        min-height: 100vh;
        height: 100vh;
        background-color: #ffffff;
        border-right: 1px solid #edf2f7;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        padding-top: 20px;
        overflow-y: auto;
        box-sizing: border-box;
    }

    .renter-sidebar .renter-brand-logo {
        padding: 0 20px 20px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .renter-sidebar .renter-brand-logo i {
        font-size: 1.8rem;
        color: #2d6a4f;
    }

    .renter-sidebar .renter-brand-name {
        color: #2d6a4f;
        display: block;
        line-height: 1;
        font-size: 1rem;
        font-weight: 700;
    }

    .renter-sidebar .renter-brand-subtitle {
        font-size: 0.65rem;
        color: #64748b;
        font-weight: 700;
        display: block;
        margin-top: 5px;
        white-space: nowrap;
    }

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

    .renter-sidebar .renter-nav-link {
        color: #4a5568;
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 0 20px 20px 0;
        margin-bottom: 2px;
        text-decoration: none;
        transition: all 0.2s ease;
        min-height: 42px;
        box-sizing: border-box;
    }

    .renter-sidebar .renter-nav-link i {
        width: 18px;
        text-align: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .renter-sidebar .renter-nav-link:hover {
        color: #2d6a4f;
        background-color: #e8f5e9;
        font-weight: 600;
    }

    .renter-sidebar .renter-nav-link.active {
        color: #2d6a4f;
        background-color: #e8f5e9;
        font-weight: 600;
    }

    .renter-sidebar .renter-logout {
        margin-top: 12px;
    }

    .renter-sidebar .renter-logout-link {
        color: #dc3545 !important;
    }

    .renter-sidebar .renter-logout-link:hover {
        color: #dc3545 !important;
        background-color: #fff1f2 !important;
    }

    .renter-sidebar::-webkit-scrollbar {
        width: 5px;
    }

    .renter-sidebar::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 10px;
    }

    @media (max-width: 768px) {
        .renter-sidebar {
            width: 250px;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .renter-sidebar.show {
            transform: translateX(0);
        }
    }
</style>

<!-- COMMON RENTER SIDEBAR -->

<div class="renter-sidebar">

    <!-- LOGO -->
    <div class="renter-brand-logo">

        <i class="fa-solid fa-tractor"></i>

        <div>
            <strong class="renter-brand-name">
                AGRICULTURE
            </strong>

            <span class="renter-brand-subtitle">
                EQUIPMENT RENTAL SYSTEM
            </span>
        </div>

    </div>


    <!-- MENU -->
    <ul class="renter-nav">

        <!-- Dashboard -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('renter_dashboard.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'renter_dashboard.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-border-all"></i>
                <span><?= __('dashboard'); ?></span>
            </a>
        </li>


        <!-- Search Equipment -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('search_equipment.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'search_equipment.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-magnifying-glass"></i>
                <span><?= __('search_equipment'); ?></span>
            </a>
        </li>


        <!-- Categories -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('categories.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'categories.php' || $current_page === 'category_items.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-layer-group"></i>
                <span><?= __('categories'); ?></span>
            </a>
        </li>


        <!-- Notifications -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('notifications.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'notifications.php') ? 'active' : ''; ?>"
            >
                <i class="fa-regular fa-bell"></i>
                <span>Notifications</span>
            </a>
        </li>


        <!-- Recommended -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('recommended.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'recommended.php') ? 'active' : ''; ?>"
            >
                <i class="fa-regular fa-thumbs-up"></i>
                <span><?= __('recommended'); ?></span>
            </a>
        </li>


        <!-- My Bookings -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('my_bookings.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'my_bookings.php' || $current_page === 'booking_details.php') ? 'active' : ''; ?>"
            >
                <i class="fa-regular fa-calendar-check"></i>
                <span><?= __('my_bookings'); ?></span>
            </a>
        </li>


        <!-- Rental History -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('rental_history.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'rental_history.php') ? 'active' : ''; ?>"
            >
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span><?= __('rental_history'); ?></span>
            </a>
        </li>


        <!-- My Profile -->
        <li class="renter-nav-item">
            <a
                href="<?= renter_sidebar_link('profile.php', $current_language); ?>"
                class="renter-nav-link <?= ($current_page === 'profile.php') ? 'active' : ''; ?>"
            >
                <i class="fa-regular fa-user"></i>
                <span>My Profile</span>
            </a>
        </li>


        <!-- Logout -->
        <li class="renter-nav-item renter-logout">
            <a
                href="logout.php"
                class="renter-nav-link renter-logout-link"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                <span><?= __('logout'); ?></span>
            </a>
        </li>

    </ul>

</div>