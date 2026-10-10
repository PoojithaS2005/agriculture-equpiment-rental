
<?php
// includes/process_overdue_rentals.php
// Run this file daily to process rental reminders and overdue charges.

require_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Kolkata');

// Fixed penalty per overdue day (change this amount if needed).
$penaltyPerDay = 100.00;

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

function addRentalNotification($conn, $userId, $title, $message)
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO notifications (user_id, title, message)
         VALUES (?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $userId,
        $title,
        $message
    );

    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$success) {
        throw new Exception("Could not save notification.");
    }
}

try {
    /*
     * Process only rentals that have been delivered or
     * are already overdue. Returned rentals are excluded.
     */
    $sql = "
        SELECT
            b.booking_id,
            b.renter_id,
            b.equipment_id,
            b.end_date,
            b.status,
            b.late_charge,
            b.reminder_notification_sent_at,
            b.overdue_notification_sent_at,
            e.title AS equipment_title,
            e.lender_id,
            r.full_name AS renter_name,
            r.phone AS renter_phone,
            l.full_name AS lender_name,
            l.phone AS lender_phone
        FROM bookings b
        INNER JOIN equipment e
            ON e.equipment_id = b.equipment_id
        INNER JOIN users r
            ON r.user_id = b.renter_id
        INNER JOIN users l
            ON l.user_id = e.lender_id
        
WHERE b.status IN ('Delivered','Overdue')
  AND b.end_date IS NOT NULL
  AND b.return_confirmed = 0

    ";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    while ($booking = mysqli_fetch_assoc($result)) {
        $bookingId = (int) $booking['booking_id'];
        $renterId = (int) $booking['renter_id'];
        $lenderId = (int) $booking['lender_id'];
        $endDate = $booking['end_date'];
        $equipmentTitle = $booking['equipment_title'];
        $lenderPhone = $booking['lender_phone'];
        $renterPhone = $booking['renter_phone'];

        // Reminder one day before the rental end date.
        if (
            $endDate === $tomorrow &&
            empty($booking['reminder_notification_sent_at'])
        ) {
            mysqli_begin_transaction($conn);

            try {
                addRentalNotification(
                    $conn,
                    $renterId,
                    'Rental Ends Tomorrow',
                    "Your rental for {$equipmentTitle} ends tomorrow. "
                    . "Please arrange to return the equipment and contact "
                    . "{$booking['lender_name']} at {$lenderPhone}."
                );

                addRentalNotification(
                    $conn,
                    $lenderId,
                    'Rental Ends Tomorrow',
                    "The rental for {$equipmentTitle} ends tomorrow. "
                    . "Please contact renter {$booking['renter_name']} "
                    . "at {$renterPhone} to arrange the return."
                );

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE bookings
                     SET reminder_notification_sent_at = NOW()
                     WHERE booking_id = ?
                       AND reminder_notification_sent_at IS NULL"
                );

                mysqli_stmt_bind_param($stmt, "i", $bookingId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conn);
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                throw $e;
            }
        }

        // A rental becomes overdue the day after end_date.
        if ($endDate < $today) {
            $overdueDays = (int) floor(
                (strtotime($today) - strtotime($endDate)) / 86400
            );

            $charge = round($overdueDays * $penaltyPerDay, 2);

            mysqli_begin_transaction($conn);

            try {
                // Recalculate from overdue days; do not add repeatedly.
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE bookings
                     SET status = 'Overdue',
                         late_charge = ?
                     WHERE booking_id = ?
                       AND status IN ('Delivered', 'Overdue')"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "di",
                    $charge,
                    $bookingId
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Send the initial overdue notification only once.
                if (empty($booking['overdue_notification_sent_at'])) {
                    addRentalNotification(
                        $conn,
                        $renterId,
                        'Rental Period Expired',
                        "Your rental period for {$equipmentTitle} has ended. "
                        . "Please return the equipment and contact your "
                        . "lender at {$lenderPhone}. Current overdue charge: "
                        . "₹{$charge} for {$overdueDays} day(s)."
                    );

                    addRentalNotification(
                        $conn,
                        $lenderId,
                        'Equipment Return Overdue',
                        "The rental period for {$equipmentTitle} has ended, "
                        . "but the equipment is still marked as unreturned. "
                        . "Please contact the renter at {$renterPhone}. "
                        . "Current overdue charge: ₹{$charge}."
                    );

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE bookings
                         SET overdue_notification_sent_at = NOW()
                         WHERE booking_id = ?
                           AND overdue_notification_sent_at IS NULL"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $bookingId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                mysqli_commit($conn);
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                throw $e;
            }
        }
    }

    mysqli_free_result($result);

    echo "Rental reminder and overdue processing completed.";

} catch (Throwable $e) {
    error_log("Rental overdue processing error: " . $e->getMessage());
    http_response_code(500);
    echo "Processing failed. Check the PHP error log.";
}
?>
