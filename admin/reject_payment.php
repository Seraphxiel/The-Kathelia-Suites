<?php
require_once __DIR__ . '/../src/php/db_connect.php';
requireAdmin();

if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    try {
        $pdo->beginTransaction();

        // Get booking_id associated with the payment
        $stmt = $pdo->prepare("SELECT booking_id FROM payments WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        $booking_id = $stmt->fetchColumn();

        if ($booking_id) {
            // Update payment status to 'rejected'
            $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected' WHERE payment_id = ?");
            $stmt->execute([$payment_id]);

            // Update booking status to 'rejected'
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE booking_id = ?");
            $stmt->execute([$booking_id]);

            $pdo->commit();
            $_SESSION['success_message'] = "Payment and associated booking rejected successfully.";
        } else {
            $_SESSION['error_message'] = "Payment not found.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error rejecting payment: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "No payment ID provided.";
}

header('Location: payments.php');
exit;
?> 