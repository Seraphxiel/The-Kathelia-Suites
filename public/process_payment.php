<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to make a payment']);
    exit;
}

// Get booking details from session (these were previously set in rooms.php)
$bookingDetails = $_SESSION['pending_reservation'] ?? null;

if (!$bookingDetails) {
    echo json_encode(['success' => false, 'message' => 'Booking details not found. Please start a new booking.']);
    exit;
}

// Get form data
$gcashRef = $_POST['gcash_ref'] ?? '';
$payerName = $_POST['payer_name'] ?? '';
$proofOfPayment = $_FILES['proof_of_payment'] ?? null;

// Validate required fields
if (empty($gcashRef) || !$proofOfPayment || $proofOfPayment['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Extract details from session for booking insertion
    $user_id = $_SESSION['user_id'];
    $roomType = $bookingDetails['room_type'];
    $check_in_date = $bookingDetails['check_in'];
    $check_out_date = $bookingDetails['check_out'];
    $number_of_guests = $bookingDetails['guests'];
    $quantity = $bookingDetails['quantity'];
    $rawAmenities = $bookingDetails['amenities'];
    $extras = $bookingDetails['extras'];

    // Calculate number of nights
    $check_in_obj = new DateTime($check_in_date);
    $check_out_obj = new DateTime($check_out_date);
    $interval = $check_in_obj->diff($check_out_obj);
    $numberOfNights = $interval->days > 0 ? $interval->days : 1; // Ensure at least 1 night

    // Fetch room_type_id and rate_per_night
    $stmt = $pdo->prepare("SELECT room_type_id, rate_per_night FROM room_types WHERE name = ?");
    $stmt->execute([ucfirst($roomType)]);
    $roomTypeData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roomTypeData) {
        throw new Exception('Room type not found.');
    }
    $room_type_id = $roomTypeData['room_type_id'];
    $roomPrice = (float)$roomTypeData['rate_per_night'];

    // Get extra item prices from the database for total calculation
    $extraPrices = getExtraPrices();
    $extraPrices = array_change_key_case($extraPrices, CASE_LOWER);

    // Calculate grand total: (room_rate_per_night * quantity_of_rooms * number_of_nights) + extras_total
    $grandTotal = ($roomPrice * $quantity * $numberOfNights);

    // Add extras cost to grand total
    if (!empty($extras)) {
        foreach ($extras as $extraName => $qty) {
            if ($qty > 0) {
                $itemPrice = $extraPrices[strtolower($extraName)] ?? 0; // Default to 0 if price not found
                $grandTotal += ($qty * $itemPrice);
            }
        }
    }

    // No longer inserting new booking here, it's already done in rooms.php
    // We retrieve the existing booking_id from the session
    $booking_id = $_SESSION['current_booking_id'] ?? null;

    if (!$booking_id) {
        throw new Exception('Booking ID not found in session. Please start booking again.');
    }

    // Handle file upload (moved from earlier in the file)
    $uploadDir = __DIR__ . '/../uploads/proofs/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . '_' . basename($proofOfPayment['name']);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($proofOfPayment['tmp_name'], $targetPath)) {
        throw new Exception('Failed to upload proof of payment');
    }

    // Save payment details (link to the existing booking_id)
    $sql_payment = "
        INSERT INTO payments (
            booking_id,
            reference_number,
            proof_url,
            status
        ) VALUES (?, ?, ?, ?)
    ";
    $params_payment = [
        $booking_id,
        $gcashRef,
        '/kathelia-suites/uploads/proofs/' . $fileName,
        'submitted' // Payment is submitted, still pending verification by admin
    ];

    $stmt = $pdo->prepare($sql_payment);
    $stmt->execute($params_payment);

    // Clear pending reservation details from session
    unset($_SESSION['pending_reservation']);
    // Unset current_booking_id after payment is associated
    unset($_SESSION['current_booking_id']);

    // Commit transaction
    $pdo->commit();

    // Redirect to the reservations page on successful payment confirmation
    header('Location: /kathelia-suites/public/reservations.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Payment processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 