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
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE booking_id = :booking_id");
    $stmt->execute([':booking_id' => $booking_id]);

    // -- 4. REDIRECT ------------------------------------------------------------------
    header('Location: bookings.php?status=approved'); // Optional: show only approved bookings after action
    exit;

} catch (PDOException $e) {
    // Handle database error (e.g., log it, show a user-friendly message)
    error_log("Database error approving booking: " . $e->getMessage());
    // Redirect with an error message or to a generic error page
    header('Location: bookings.php?error=db_error');
    exit;
} 