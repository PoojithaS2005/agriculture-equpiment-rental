<?php
/* lender_rental_history.php */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role'] ?? '') !== 'lender') {
    header("Location: login.php");
    exit();
}

$lender_id = (int)$_SESSION['user_id'];
$lender_name = $_SESSION['full_name'] ?? 'Lender';
$current_lang = $_SESSION['lang'] ?? 'en';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['en','hi','kn'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    $current_lang = $_GET['lang'];
}

function tr($key, $fallback) {
    global $translations, $current_lang;

    return $translations[$current_lang][$key]
        ?? $translations['en'][$key]
        ?? $fallback;
}

$rows = [];

$sql = "
    SELECT
        b.booking_id,
        b.request_code,
        b.start_date,
        b.end_date,
        b.total_days,
        b.quantity,
        b.total_amount,
        b.status,
        b.created_at,
        e.title AS equipment_title,
        e.category AS equipment_category,
        e.image AS equipment_image,
        u.full_name AS renter_name
    FROM bookings b
    INNER JOIN equipment e
        ON b.equipment_id = e.equipment_id
    INNER JOIN users u
        ON b.renter_id = u.user_id
    WHERE e.lender_id = ?
      AND b.status IN ('Returned', 'Completed', 'Overdue', 'Rejected')
    ORDER BY b.booking_id DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $lender_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
}

$unread = 0;

$nstmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

if ($nstmt) {
    $nstmt->bind_param("i", $lender_id);
    $nstmt->execute();

    $nr = $nstmt->get_result()->fetch_assoc();

    $unread = (int)($nr['total'] ?? 0);

    $nstmt->close();
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang) ?>">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    <?= htmlspecialchars(tr('rental_history', 'Rental History')) ?> - Lender
</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: #f4f6f9;
    color: #333;
    display: block;
}

.main {
    margin-left: 250px;
    padding: 20px 30px;
}

.top-banner {
    background: #0f4c5c;
    color: #fff;
    text-align: center;
    padding: 9px;
    font-weight: bold;
    border-radius: 6px;
    margin-bottom: 20px;
    letter-spacing: 1px;
}

.topbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.lang {
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #fff;
}

.bell {
    position: relative;
    color: #0f4c5c;
    text-decoration: none;
    font-size: 19px;
}

.badge {
    position: absolute;
    top: -9px;
    right: -10px;
    background: #ef4444;
    color: #fff;
    border-radius: 20px;
    font-size: 10px;
    padding: 2px 6px;
}

.profile {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #0f4c5c;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

h1 {
    font-size: 24px;
    color: #0f172a;
}

.subtitle {
    color: #64748b;
    font-size: 13px;
    margin-top: 5px;
}

.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
}

.filter {
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

th {
    text-align: left;
    color: #64748b;
    padding: 11px;
    border-bottom: 1px solid #e2e8f0;
}

td {
    padding: 13px 11px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.equipment {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
}

.equipment img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 7px;
    background: #f1f5f9;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
}

.completed,
.returned {
    background: #dcfce7;
    color: #166534;
}

.overdue {
    background: #fee2e2;
    color: #991b1b;
}

.rejected {
    background: #f1f5f9;
    color: #475569;
}

.view {
    color: #0284c7;
    text-decoration: none;
    font-weight: 700;
}

.empty {
    text-align: center;
    color: #94a3b8;
    padding: 45px 15px;
}

@media (max-width: 800px) {

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .header-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}

</style>

</head>

<body>

<?php include __DIR__ . '/lender_sidebar.php'; ?>

<main class="main">

    <div class="top-banner">
        <?= htmlspecialchars(tr('rental_history', 'RENTAL HISTORY')) ?>
    </div>

    <div class="topbar">

        <select class="lang" onchange="location=this.value">

            <option value="?lang=en"
                <?= $current_lang === 'en' ? 'selected' : '' ?>>
                🌐 English
            </option>

            <option value="?lang=hi"
                <?= $current_lang === 'hi' ? 'selected' : '' ?>>
                🌐 हिन्दी
            </option>

            <option value="?lang=kn"
                <?= $current_lang === 'kn' ? 'selected' : '' ?>>
                🌐 ಕನ್ನಡ
            </option>

        </select>

        <a class="bell"
           href="lender_notifications.php?lang=<?= urlencode($current_lang) ?>">

            <i class="fa-regular fa-bell"></i>

            <?php if ($unread > 0): ?>

                <span class="badge">
                    <?= $unread > 99 ? '99+' : $unread ?>
                </span>

            <?php endif; ?>

        </a>

        <a href="profile.php" style="text-decoration:none">

            <div class="profile">
                <?= htmlspecialchars(
                    mb_strtoupper(
                        mb_substr($lender_name, 0, 1)
                    )
                ) ?>
            </div>

        </a>

    </div>

    <div class="header-row">

        <div>

            <h1>
                <?= htmlspecialchars(
                    tr('rental_history', 'Rental History')
                ) ?>
            </h1>

            <p class="subtitle">
                View completed, returned and past rental records for your equipment.
            </p>

        </div>

        <select id="statusFilter" class="filter">

            <option value="all">All Status</option>
            <option value="Completed">Completed</option>
            <option value="Returned">Returned</option>
            <option value="Overdue">Overdue</option>
            <option value="Rejected">Rejected</option>

        </select>

    </div>

    <div class="card table-wrap">

        <?php if (count($rows) > 0): ?>

            <table id="historyTable">

                <thead>

                    <tr>
                        <th>Equipment</th>
                        <th>Request ID</th>
                        <th>Renter</th>
                        <th>Rental Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($rows as $row):

                    $img = !empty($row['equipment_image'])
                        ? 'uploads/' . $row['equipment_image']
                        : 'uploads/default.png';

                    $status = $row['status'];

                ?>

                    <tr data-status="<?= htmlspecialchars($status) ?>">

                        <td>

                            <div class="equipment">

                                <img
                                    src="<?= htmlspecialchars($img) ?>"
                                    onerror="this.src='uploads/default.png'"
                                    alt=""
                                >

                                <span>
                                    <?= htmlspecialchars($row['equipment_title']) ?>
                                </span>

                            </div>

                        </td>

                        <td>
                            <?= htmlspecialchars($row['request_code']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['renter_name']) ?>
                        </td>

                        <td>
                            <?= date(
                                'd M Y',
                                strtotime($row['start_date'])
                            ) ?>
                            -
                            <?= date(
                                'd M Y',
                                strtotime($row['end_date'])
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                ₹<?= number_format(
                                    (float)$row['total_amount'],
                                    2
                                ) ?>
                            </strong>
                        </td>

                        <td>

                            <span class="status <?= strtolower($status) ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>

                        </td>

                        <td>

                            <a
                                class="view"
                                href="lender_booking_details.php?booking_id=<?= (int)$row['booking_id'] ?>&lang=<?= urlencode($current_lang) ?>"
                            >
                                View Details
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">

                <i
                    class="fa-solid fa-clock-rotate-left"
                    style="font-size:30px;margin-bottom:12px"
                ></i>

                <br>

                No rental history found.

            </div>

        <?php endif; ?>

    </div>

</main>

<script>

document.getElementById('statusFilter').addEventListener('change', function () {

    const value = this.value;

    document
        .querySelectorAll('#historyTable tbody tr')
        .forEach(row => {

            row.style.display =
                (value === 'all' || row.dataset.status === value)
                ? ''
                : 'none';

        });

});

</script>

</body>
</html>