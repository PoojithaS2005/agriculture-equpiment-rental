<?php  
/*  
 * SHARED LENDER SIDEBAR  
 * Include on every lender page with: 
 * <?php include 'lender_sidebar.php'; ?> 
 */ 
 
$current_lender_page = basename($_SERVER['PHP_SELF'] ?? ''); 
 
if (!function_exists('lender_nav_text')) { 
    function lender_nav_text($key, $fallback) { 
        if (function_exists('__')) { 
            $translated = __($key); 
            if ($translated !== $key && trim((string)$translated) !== '') { 
                return $translated; 
            } 
        } 
        return $fallback; 
    } 
} 
 
/* Pending rental-request badge */ 
$pending_rental_requests = 0; 
$active_lender_rentals = 0; 
 
if (isset($conn) && $conn instanceof mysqli && isset($_SESSION['user_id'])) { 
    $lender_sidebar_user_id = (int)$_SESSION['user_id']; 
 
    $stmt = $conn->prepare(" 
        SELECT 
            SUM( 
                CASE 
                    WHEN LOWER(COALESCE(b.status, '')) = 'pending' 
                    THEN 1 
                    ELSE 0 
                END 
            ) AS pending_requests, 
 
            SUM( 
                CASE 
                    WHEN LOWER(COALESCE(b.status, '')) IN ('accepted', 'delivered') 
                    THEN 1 
                    ELSE 0 
                END 
            ) AS active_rentals 
 
        FROM bookings b 
 
        INNER JOIN equipment e 
            ON e.equipment_id = b.equipment_id 
 
        WHERE e.lender_id = ? 
    "); 
 
    if ($stmt) { 
        $stmt->bind_param('i', $lender_sidebar_user_id); 
        $stmt->execute(); 
 
        $data = $stmt->get_result()->fetch_assoc(); 
 
        $pending_rental_requests = 
            (int)($data['pending_requests'] ?? 0); 
 
        $active_lender_rentals = 
            (int)($data['active_rentals'] ?? 0); 
 
        $stmt->close(); 
    } 
} 
 
$add_equipment_page = 'add_item.php'; 
$rental_requests_page = 'rental_request.php'; 
 
$current_lang_sidebar = $_SESSION['lang'] ?? $_SESSION['language'] ?? 'en'; 
$lang_param_sidebar = '?lang=' . urlencode($current_lang_sidebar); 
 
$is_dashboard = 
    ($current_lender_page === 'lender_dashboard.php'); 
 
$is_add_equipment = 
    in_array( 
        $current_lender_page, 
        ['add_item.php', 'add_equipment.php'], 
        true 
    ); 
 
$is_my_equipment = 
    ($current_lender_page === 'my_equipment.php'); 
 
$is_rental_requests = 
    in_array( 
        $current_lender_page, 
        ['rental_request.php', 'rental_requests.php'], 
        true 
    ); 
 
$is_active_rentals = 
    ($current_lender_page === 'active_rentals.php'); 
 
$is_lender_my_bookings = 
    ($current_lender_page === 'lender_my_bookings.php'); 
 
$is_lender_rental_history = 
    ($current_lender_page === 'lender_rental_history.php'); 
 
$is_reviews = 
    ($current_lender_page === 'reviews.php'); 
 
$is_total_earnings = 
    ($current_lender_page === 'total_earnings.php'); 
 
$is_my_profile = 
    ($current_lender_page === 'my_profile.php'); 
 
?> 
 
<style> 
 
:root { 
    --lender-teal-primary: #134e5e; 
    --lender-teal-hover: #114b5f; 
    --lender-sidebar-bg: #ffffff; 
    --lender-text-muted: #475569; 
    --lender-border: #e2e8f0; 
} 
 
.lender-sidebar { 
    width: 250px; 
    min-height: 100vh; 
    height: 100vh; 
    background: var(--lender-sidebar-bg); 
    border-right: 1px solid var(--lender-border); 
    position: fixed; 
    top: 0; 
    left: 0; 
    z-index: 1050; 
    padding-top: 15px; 
    box-sizing: border-box; 
    overflow-y: auto; 
} 
 
.lender-sidebar-brand-header { 
    padding: 5px 20px 15px 20px; 
    border-bottom: 1px solid var(--lender-border); 
    margin-bottom: 10px; 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    box-sizing: border-box; 
} 
 
.lender-tractor-avatar { 
    width: 32px; 
    height: 32px; 
    background: #e0f2f1; 
    color: var(--lender-teal-primary); 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: .95rem; 
    flex: 0 0 32px; 
} 
 
.lender-sidebar-brand-title { 
    font-weight: 800; 
    color: var(--lender-teal-primary); 
    font-size: .85rem; 
    line-height: 1.1; 
} 
 
.lender-sidebar-brand-title small { 
    font-size: .55rem; 
    color: #64748b; 
    font-weight: 600; 
} 
 
.lender-sidebar .nav { 
    padding: 0; 
    margin: 0; 
    list-style: none; 
    display: flex; 
    flex-direction: column; 
} 
 
.lender-sidebar .nav-item { 
    list-style: none; 
    margin: 0; 
    padding: 0; 
} 
 
.lender-sidebar .nav-link { 
    color: var(--lender-text-muted); 
    padding: 9px 20px; 
    font-size: .85rem; 
    font-weight: 500; 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    border-radius: 50px; 
    margin: 3px 15px; 
    min-height: 40px; 
    text-decoration: none; 
    transition: all .2s ease; 
    box-sizing: border-box; 
} 
 
.lender-sidebar .nav-link i { 
    width: 18px; 
    min-width: 18px; 
    text-align: center; 
    font-size: .9rem; 
} 
 
.lender-sidebar .nav-link:hover { 
    color: var(--lender-teal-primary); 
    background: #f0fdf4; 
} 
 
.lender-sidebar .nav-link.active { 
    color: #fff; 
    background: var(--lender-teal-primary); 
    font-weight: 600; 
} 
 
.lender-sidebar .nav-link.active:hover { 
    color: #fff; 
    background: var(--lender-teal-hover); 
} 
 
.lender-sidebar .lender-logout-item { 
    margin-top: 8px; 
} 
 
.lender-sidebar .lender-logout-link { 
    color: #dc2626; 
} 
 
.lender-sidebar .lender-logout-link:hover { 
    color: #dc2626; 
    background: #fef2f2; 
} 
 
.lender-request-badge, 
.lender-active-badge { 
    margin-left: auto; 
    min-width: 20px; 
    height: 20px; 
    padding: 2px 7px; 
    border-radius: 50px; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    font-size: .7rem; 
    font-weight: 700; 
    line-height: 1; 
    box-sizing: border-box; 
} 
 
.lender-request-badge { 
    background: #ef4444; 
    color: #fff; 
} 
 
.lender-active-badge { 
    background: #3b82f6; 
    color: #fff; 
} 
 
@media (max-width: 991.98px) { 
 
    .lender-sidebar { 
        width: 220px; 
    } 
 
    .lender-sidebar .nav-link { 
        margin-left: 10px; 
        margin-right: 10px; 
        padding-left: 15px; 
        padding-right: 15px; 
    } 
 
} 
 
</style> 
 
<aside class="lender-sidebar"> 
 
    <div class="lender-sidebar-brand-header"> 
 
        <div class="lender-tractor-avatar"> 
            <i class="fa-solid fa-tractor"></i> 
        </div> 
 
        <div class="lender-sidebar-brand-title"> 
 
            AGRICULTURE<br> 
 
            <small> 
                EQUIPMENT RENTAL 
            </small> 
 
        </div> 
 
    </div> 
 
    <ul class="nav"> 
 
        <!-- DASHBOARD --> 
 
        <li class="nav-item"> 
 
            <a 
                href="lender_dashboard.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_dashboard ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-house"></i> 
 
                <span> 
                    <?= lender_nav_text('dashboard', 'Dashboard'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- ADD EQUIPMENT --> 
 
        <li class="nav-item"> 
 
            <a 
                href="<?= $add_equipment_page; ?>?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_add_equipment ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-circle-plus"></i> 
 
                <span> 
                    <?= lender_nav_text('add_equipment', 'Add Equipment'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- MY EQUIPMENT --> 
 
        <li class="nav-item"> 
 
            <a 
                href="my_equipment.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_my_equipment ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-tractor"></i> 
 
                <span> 
                    <?= lender_nav_text('my_equipment', 'My Equipment'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- RENTAL REQUESTS --> 
 
        <li class="nav-item"> 
 
            <a 
                href="<?= $rental_requests_page; ?>?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_rental_requests ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-bell"></i> 
 
                <span> 
                    <?= lender_nav_text('rental_requests', 'Rental Requests'); ?> 
                </span> 
 
                <span class="lender-request-badge"> 
                    <?= $pending_rental_requests; ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- ACTIVE RENTALS --> 
 
        <li class="nav-item"> 
 
            <a 
                href="active_rentals.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_active_rentals ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-clock-rotate-left"></i> 
 
                <span> 
                    <?= lender_nav_text('active_rentals', 'Active Rentals'); ?> 
                </span> 
 
                <span class="lender-active-badge"> 
                    <?= $active_lender_rentals; ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- MY BOOKINGS --> 
 
        <li class="nav-item"> 
 
            <a 
                href="./lender_my_bookings.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_lender_my_bookings ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-calendar-check"></i> 
 
                <span> 
                    <?= lender_nav_text('my_bookings', 'My Bookings'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- RENTAL HISTORY --> 
 
        <li class="nav-item"> 
 
            <a 
                href="lender_rental_history.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_lender_rental_history ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-clock-rotate-left"></i> 
 
                <span> 
                    <?= lender_nav_text('rental_history', 'Rental History'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- REVIEWS --> 
 
        <li class="nav-item"> 
 
            <a 
                href="reviews.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_reviews ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-regular fa-star"></i> 
 
                <span> 
                    <?= lender_nav_text('reviews', 'Reviews'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- TOTAL EARNINGS --> 
 
        <li class="nav-item"> 
 
            <a 
                href="total_earnings.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_total_earnings ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-solid fa-indian-rupee-sign"></i> 
 
                <span> 
                    <?= lender_nav_text('total_earnings', 'Total Earnings'); ?> 
                </span> 
 
            </a> 
 
        </li> 

        
<!-- AI INSIGHTS -->
<li class="nav-item">
    <a
        href="lender_ai_insights.php?lang=<?= urlencode($current_lang_sidebar); ?>"
        class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'lender_ai_insights.php' ? 'active' : ''; ?>"
    >
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span><?= lender_nav_text('ai_insights', 'AI Insights'); ?></span>
    </a>
</li>
 
        <!-- MY PROFILE --> 
 
        <li class="nav-item"> 
 
            <a 
                href="./profile.php?lang=<?= urlencode($current_lang_sidebar); ?>" 
                class="nav-link <?= $is_my_profile ? 'active' : ''; ?>" 
            > 
 
                <i class="fa-regular fa-user"></i> 
 
                <span> 
                    <?= lender_nav_text('my_profile', 'My Profile'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
        <!-- LOGOUT --> 
 
        <li class="nav-item lender-logout-item"> 
 
            <a 
                href="logout.php" 
                class="nav-link lender-logout-link" 
            > 
 
                <i class="fa-solid fa-right-from-bracket"></i> 
 
                <span> 
                    <?= lender_nav_text('logout', 'Logout'); ?> 
                </span> 
 
            </a> 
 
        </li> 
 
    </ul> 
 
</aside>