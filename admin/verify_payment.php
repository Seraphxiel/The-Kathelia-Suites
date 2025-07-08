<?php
require_once __DIR__ . '/../src/php/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

// Get payment ID from URL
$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id) {
    header('Location: payments.php'); // Redirect back if no ID
    exit;
}

try {
    $pdo->beginTransaction();

    // Update payment status to 'verified'
    $stmt = $pdo->prepare("UPDATE payments SET status = 'verified' WHERE payment_id = ?");
    $stmt->execute([$payment_id]);

    // Get the associated booking_id from the payment
    $stmt = $pdo->prepare("SELECT booking_id FROM payments WHERE payment_id = ?");
    $stmt->execute([$payment_id]);
    $booking_id = $stmt->fetchColumn();

    $pdo->commit();

    $_SESSION['message'] = 'Payment verified and booking status remains pending for admin review.';
    header('Location: payments.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Error verifying payment: ' . $e->getMessage();
    header('Location: payments.php');
    exit;
}
?> 