<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

// -- 1. AUTH CHECK ----------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

// -- 2. GET BOOKING ID (from POST) ------------------------------------------------
$booking_id = $_POST['booking_id'] ?? null; // Changed from $_GET['id'] to $_POST['booking_id']

if (!$booking_id) {
    // Redirect back to bookings if no ID is provided
    header('Location: bookings.php');
    exit;
}

try {
    // -- 3. UPDATE BOOKING STATUS -----------------------------------------------------
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE booking_id = :booking_id");
    $stmt->execute([':booking_id' => $booking_id]);

    // -- 4. REDIRECT ------------------------------------------------------------------
    header('Location: bookings.php?status=rejected'); // Optional: show only rejected bookings after action
    exit;

} catch (PDOException $e) {
    // Handle database error
    error_log("Database error rejecting booking: " . $e->getMessage());
    header('Location: bookings.php?error=db_error');
    exit;
} 