<?php
session_start();

require_once 'includes/lang.php';
require_once 'includes/config.php';

// Translate common equipment values stored in the database.
// Unknown/user-entered values are displayed unchanged.
function translate_equipment_value($value, $type = 'general') {
    $value = trim((string)$value);
    if ($value === '') return '';
    $normalized = strtolower($value);
    $maps = [
        'status' => ['available' => 'equipment_available', 'rented' => 'equipment_rented'],
        'condition' => ['good' => 'condition_good', 'excellent' => 'condition_excellent', 'fair' => 'condition_fair', 'poor' => 'condition_poor'],
        'location' => [
            'bagalkot' => 'district_bagalkot',
            'bagalkote' => 'district_bagalkot',
            'ballari' => 'district_ballari',
            'bellary' => 'district_ballari',
            'belagavi' => 'district_belagavi',
            'belgaum' => 'district_belagavi',
            'bengaluru rural' => 'district_bengaluru_rural',
            'bangalore rural' => 'district_bengaluru_rural',
            'bengaluru urban' => 'district_bengaluru_urban',
            'bangalore urban' => 'district_bengaluru_urban',
            'bengaluru' => 'district_bengaluru_urban',
            'bangalore' => 'district_bengaluru_urban',
            'bidar' => 'district_bidar',
            'chamarajanagar' => 'district_chamarajanagar',
            'chamrajnagar' => 'district_chamarajanagar',
            'chikkaballapur' => 'district_chikkaballapur',
            'chikballapur' => 'district_chikkaballapur',
            'chikkamagaluru' => 'district_chikkamagaluru',
            'chikmagalur' => 'district_chikkamagaluru',
            'chitradurga' => 'district_chitradurga',
            'dakshina kannada' => 'district_dakshina_kannada',
            'south kanara' => 'district_dakshina_kannada',
            'davanagere' => 'district_davanagere',
            'davangere' => 'district_davanagere',
            'dharwad' => 'district_dharwad',
            'gadag' => 'district_gadag',
            'hassan' => 'district_hassan',
            'haveri' => 'district_haveri',
            'kalaburagi' => 'district_kalaburagi',
            'gulbarga' => 'district_kalaburagi',
            'kodagu' => 'district_kodagu',
            'coorg' => 'district_kodagu',
            'kolar' => 'district_kolar',
            'koppal' => 'district_koppal',
            'mandya' => 'district_mandya',
            'mysuru' => 'district_mysuru',
            'mysore' => 'district_mysuru',
            'raichur' => 'district_raichur',
            'ramanagara' => 'district_ramanagara',
            'ramnagaram' => 'district_ramanagara',
            'shivamogga' => 'district_shivamogga',
            'shimoga' => 'district_shivamogga',
            'tumakuru' => 'district_tumakuru',
            'tumkur' => 'district_tumakuru',
            'udupi' => 'district_udupi',
            'uttara kannada' => 'district_uttara_kannada',
            'north kanara' => 'district_uttara_kannada',
            'vijayapura' => 'district_vijayapura',
            'bijapur' => 'district_vijayapura',
            'yadgir' => 'district_yadgir',
            'yadagiri' => 'district_yadgir',
            'vijayanagara' => 'district_vijayanagara',
            'vijayanagaram' => 'district_vijayanagara',
        ]
    ];
    if (isset($maps[$type][$normalized])) return __($maps[$type][$normalized]);
    if ($type === 'category') {
        $key = trim(preg_replace('/[^a-z0-9]+/i', '_', $normalized), '_');
        if (in_array($key, ['tractor','harvesting','irrigation','tillage','seeding','spraying'], true)) return __($key);
    }
    return $value;
}


// Protect Page: Ensure user is logged in as Lender
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'lender') {
    header("Location: login.php");
    exit();
}

$lender_id = $_SESSION['user_id'];
$lender_name = $_SESSION['full_name'] ?? 'Lender';

// Handle Deletion via GET request securely with Prepared Statements
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $equipment_id_to_delete = intval($_GET['id']);

    $del_stmt = $conn->prepare("DELETE FROM equipment WHERE equipment_id = ? AND lender_id = ?");
    $del_stmt->bind_param("ii", $equipment_id_to_delete, $lender_id);

    if ($del_stmt->execute()) {
        header("Location: my_equipment.php?msg=deleted");
        exit();
    }

    $del_stmt->close();
}

// Fetch Real Equipment Belonging ONLY to Logged-in Lender
$equip_stmt = $conn->prepare("SELECT * FROM equipment WHERE lender_id = ? ORDER BY equipment_id DESC");
$equip_stmt->bind_param("i", $lender_id);
$equip_stmt->execute();
$equip_result = $equip_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang ?? 'en'; ?>">

<head>

    <meta charset="UTF-8">

    <title>
        <?php echo __('my_equipment_title'); ?> - Agriculture Equipment Rental System
    </title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --navy-primary: #0B3478;
            --navy-dark: #082B65;
            --blue-hover: #164C9A;
            --sidebar-bg: #C7DDF7;
            --sidebar-active: #A6C8F5;
            --text-dark: #10194A;
            --text-muted: #425D80;
            --bg-body: #EAF3FF;
            --card-bg: #F8FBFF;
            --border-color: #C8DDF5;
            --blue-accent: #8DB8F0;
        }


        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #EAF3FF;
            color: #10194A;
            margin: 0;
        }

        /* Shared lender_sidebar.php is fixed at 250px */
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 20px 30px;
        }

        .top-banner {
            background: #0B3478;
            color: white;
            padding: 12px 20px;
            font-weight: bold;
            border-radius: 8px;
            margin-bottom: 25px;
            letter-spacing: 1px;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .equipment-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #C8DDF5;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }

        .equipment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .card-img-container {
            height: 180px;
            width: 100%;
            background: #EAF3FF;
            position: relative;
        }

        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-body-content {
            padding: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .equipment-title {
            font-size: 16px;
            font-weight: bold;
            color: #10194A;
            margin-bottom: 0;
        }

        .meta-text {
            font-size: 13px;
            color: #425D80;
        }

        .price-tag {
            font-size: 16px;
            font-weight: bold;
            color: #0B3478;
            margin-top: auto;
        }

        .card-footer-actions {
            padding: 12px 15px;
            background: #F8FBFF;
            border-top: 1px solid #C8DDF5;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }

        .btn-action {
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            flex: 1;
        }

        .btn-view {
            background: #DCEBFF;
            color: #0B3478;
        }

        .btn-view:hover {
            background: #C7DDF7;
            color: #082B65;
        }

        .btn-edit {
            background: #A6C8F5;
            color: #082B65;
        }

        .btn-edit:hover {
            background: #8DB8F0;
            color: #082B65;
        }

        .btn-remove {
            background: #DCEBFF;
            color: #0B3478;
        }

        .btn-remove:hover {
            background: #C7DDF7;
            color: #082B65;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #C8DDF5;
            margin-top: 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: #C8DDF5;
            margin-bottom: 15px;
        }

        .badge-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }

        .status-available {
            background: #DCEBFF;
            color: #0B3478;
        }

        .status-rented {
            background: #C7DDF7;
            color: #082B65;
        }

        .lang-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #C8DDF5;
            font-size: 13px;
            outline: none;
            background: #fff;
            cursor: pointer;
        }

        @media (max-width: 991.98px) {

            .main-content {
                margin-left: 250px;
                padding: 20px;
            }
        }

    </style>

</head>

<body>

    <!-- SHARED LENDER SIDEBAR -->
    <?php include 'lender_sidebar.php'; ?>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <div class="main-content">

        <!-- Top banner with Language Selector dropdown included -->

        <div class="top-banner">

            <span>
                <i class="fa-solid fa-list me-2"></i>
                <?php echo __('my_equipment_inventory'); ?>
            </span>

            <select
                class="lang-select text-dark"
                onchange="location = this.value;"
            >

                <option
                    value="my_equipment.php?lang=en"
                    <?php echo ($current_lang === 'en') ? 'selected' : ''; ?>
                >
                    🌐 English
                </option>

                <option
                    value="my_equipment.php?lang=hi"
                    <?php echo ($current_lang === 'hi') ? 'selected' : ''; ?>
                >
                    🌐 हिन्दी (Hindi)
                </option>

                <option
                    value="my_equipment.php?lang=kn"
                    <?php echo ($current_lang === 'kn') ? 'selected' : ''; ?>
                >
                    🌐 ಕನ್ನಡ (Kannada)
                </option>

            </select>

        </div>


        <!-- =====================================================
             EQUIPMENT LIST
             ===================================================== -->

        <?php if ($equip_result && $equip_result->num_rows > 0): ?>

            <div class="equipment-grid">

                <?php while ($row = $equip_result->fetch_assoc()): ?>

                    <?php

                    $img_path = !empty($row['image'])
                        ? 'uploads/' . htmlspecialchars($row['image'])
                        : '';

                    $has_valid_img =
                        !empty($row['image']) &&
                        file_exists($img_path);

                    ?>

                    <div class="equipment-card">


                        <!-- EQUIPMENT IMAGE -->

                        <div class="card-img-container">

                            <?php if ($has_valid_img): ?>

                                <img
                                    src="<?php echo $img_path; ?>"
                                    alt="Equipment Image"
                                >

                            <?php else: ?>

                                <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">

                                    <i class="fa-solid fa-tractor fa-2x"></i>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- EQUIPMENT DETAILS -->

                        <div class="card-body-content">

                            <div class="d-flex justify-content-between align-items-start">

                                <h4 class="equipment-title">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['title'] ?? __('unnamed_equipment')
                                    );
                                    ?>

                                </h4>


                                <span
                                    class="badge-status
                                    <?php
                                    echo (
                                        isset($row['status']) &&
                                        strtolower($row['status']) === 'rented'
                                    )
                                        ? 'status-rented'
                                        : 'status-available';
                                    ?>"
                                >

                                    <?php
                                    $status_value = $row['status'] ?? 'Available';
                                    echo htmlspecialchars(
                                        translate_equipment_value($status_value, 'status'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </span>

                            </div>


                            <!-- CATEGORY / BRAND -->

                            <div class="meta-text">

                                <i class="fa-solid fa-tag me-1"></i>

                                <?php
                                echo htmlspecialchars(
                                    translate_equipment_value(
                                        $row['category'] ?? __('uncategorized'),
                                        'category'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                                <?php
                                echo !empty($row['brand_model'])
                                    ? ' | ' . htmlspecialchars($row['brand_model'])
                                    : '';
                                ?>

                            </div>


                            <!-- LOCATION -->

                            <div class="meta-text">

                                <i class="fa-solid fa-location-dot me-1"></i>

                                <?php
                                $location_value = $row['service_location']
                                    ?? __('location_not_specified');
                                $location_parts = preg_split('/\s*,\s*/u', (string)$location_value);
                                $translated_locations = array_map(
                                    function ($place) {
                                        return translate_equipment_value($place, 'location');
                                    },
                                    $location_parts
                                );
                                echo htmlspecialchars(
                                    implode(', ', $translated_locations),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </div>


                            <!-- CONDITION -->

                            <div class="meta-text">

                                <i class="fa-solid fa-circle-info me-1"></i>

                                <?php echo __('condition'); ?>:

                                <?php
                                echo htmlspecialchars(
                                    translate_equipment_value(
                                        $row['equipment_condition'] ?? 'N/A',
                                        'condition'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </div>


                            <!-- PRICE -->

                            <div class="price-tag">

                                ₹<?php
                                echo number_format(
                                    $row['price_per_day'] ?? 0,
                                    2
                                );
                                ?>

                                <small class="text-muted fw-normal">

                                    <?php echo __('per_day'); ?>

                                </small>

                            </div>


                            <!-- QUANTITY -->

                            <div class="meta-text">

                                <?php echo __('quantity_label'); ?>:

                                <strong>
                                    <?php
                                    $quantity = (int)($row['total_quantity'] ?? 1);
                                    echo $quantity;
                                    ?>
                                </strong>

                                <?php echo ($quantity === 1) ? __('unit_label') : __('units_label'); ?>

                            </div>

                        </div>


                        <!-- ACTION BUTTONS -->

                        <div class="card-footer-actions">

                            <a
                                href="equipment_details.php?id=<?php echo $row['equipment_id']; ?>"
                                class="btn-action btn-view"
                            >

                                <i class="fa-solid fa-eye"></i>

                                <?php echo __('view'); ?>

                            </a>


                            <a
                                href="edit_equipment.php?id=<?php echo $row['equipment_id']; ?>"
                                class="btn-action btn-edit"
                            >

                                <i class="fa-solid fa-pen-to-square"></i>

                                <?php echo __('edit'); ?>

                            </a>


                            <a
                                href="my_equipment.php?action=delete&id=<?php echo $row['equipment_id']; ?>"
                                class="btn-action btn-remove"
                                onclick="return confirm('<?php echo __('confirm_remove_equipment'); ?>');"
                            >

                                <i class="fa-solid fa-trash"></i>

                                <?php echo __('remove'); ?>

                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>


        <?php else: ?>


            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <div class="empty-state">

                <i class="fa-solid fa-box-open"></i>

                <h3>
                    <?php echo __('no_equipment_added'); ?>
                </h3>


                <a
                    href="add_item.php"
                    class="btn px-4 py-2 text-white mt-2"
                    style="background-color: #0e7490;"
                >

                    <i class="fa-solid fa-circle-plus me-2"></i>

                    <?php echo __('add_equipment'); ?>

                </a>

            </div>


        <?php endif; ?>

    </div>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
    ></script>

</body>

</html>