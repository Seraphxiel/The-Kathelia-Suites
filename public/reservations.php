<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
require_once __DIR__ . '/../src/php/templates/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Room configurations for price lookup
$roomsConfig = [
    'twin' => [
        'amount' => 3000
    ],
    'family' => [
        'amount' => 5000
    ],
    'harmony' => [
        'amount' => 8000
    ]
];

// pull just the main bookings info
$sql = "
SELECT
  b.booking_id,
  rt.name           AS room,
  b.check_in_date,
  b.check_out_date,
  b.number_of_guests,
  b.quantity AS room_quantity, /* Fetch quantity for total calculation */
  b.status,
  b.total_amount
FROM bookings b
JOIN room_types rt
  ON b.room_type_id = rt.room_type_id
WHERE b.user_id = :user_id
ORDER BY b.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="/kathelia-suites/src/php/css/reservations.css">

<div class="container">
  <h1>My Reservations</h1>

  <?php if (empty($reservations)): ?>
    <p>You have no reservations yet.</p>
  <?php else: ?>
    <div class="reservation-cards-container">
        <?php foreach ($reservations as $r): ?>
          <?php
            // Fetch amenities for this booking
            $amenitiesSql = "
                SELECT a.name
                FROM booking_amenities ba
                JOIN amenities a ON ba.amenity_id = a.amenity_id
                WHERE ba.booking_id = ?
            ";
            $amenitiesStmt = $pdo->prepare($amenitiesSql);
            $amenitiesStmt->execute([$r['booking_id']]);
            $amenities = $amenitiesStmt->fetchAll(PDO::FETCH_COLUMN);
            $amenitiesString = implode(', ', $amenities);

            // Fetch extras for this booking and calculate their total cost
            $extrasSql = "
                SELECT ei.name, be.quantity, be.rate_at_booking
                FROM booking_extras be
                JOIN extras ei ON be.extra_id = ei.extra_id
                WHERE be.booking_id = ?
            ";
            $extrasStmt = $pdo->prepare($extrasSql);
            $extrasStmt->execute([$r['booking_id']]);
            $extras = $extrasStmt->fetchAll(PDO::FETCH_ASSOC);

            $extrasString = '';
            $extrasTotal = 0;
            foreach ($extras as $extraItem) {
                $extrasString .= htmlspecialchars($extraItem['name']) . ' (x' . htmlspecialchars($extraItem['quantity']) . '), ';
                $extrasTotal += ($extraItem['quantity'] * $extraItem['rate_at_booking']);
            }
            $extrasString = rtrim($extrasString, ', '); // Remove trailing comma and space

            // Fetch payment reference number
            $paymentSql = "SELECT reference_number, proof_url FROM payments WHERE booking_id = ? ORDER BY payment_date DESC LIMIT 1";
            $paymentStmt = $pdo->prepare($paymentSql);
            $paymentStmt->execute([$r['booking_id']]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            $referenceNumber = $payment['reference_number'] ?? 'N/A';
            $proofUrl = $payment['proof_url'] ?? '';

            // Use the full total amount from the database
            $fullTotal = $r['total_amount'];
            // Calculate 50% of the full total
            $fiftyPercentDue = $fullTotal * 0.5;
          ?>
            <div class="reservation-card" data-booking-id="<?= htmlspecialchars($r['booking_id']) ?>">
                <div class="envelope-header">
                    <i class="fa-solid fa-envelope envelope-icon closed"></i>
                    <h3>Booking #<?= htmlspecialchars($r['booking_id']) ?></h3>
                </div>
                <div class="booking-details" style="display: none;">
                    <div class="detail-row">
                        <strong>ROOM TYPE:</strong> <span><?= htmlspecialchars($r['room']) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>GUESTS:</strong> <span><?= htmlspecialchars($r['number_of_guests']) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>CHECK-IN DATE:</strong> <span><?= htmlspecialchars($r['check_in_date']) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>CHECK-OUT DATE:</strong> <span><?= htmlspecialchars($r['check_out_date']) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>REFERENCE NUMBER:</strong> <span><?= htmlspecialchars($referenceNumber) ?></span>
                    </div>
                    <?php if (!empty($proofUrl)): ?>
                        <div class="detail-row">
                            <strong>PROOF OF PAYMENT:</strong> <a href="<?= htmlspecialchars($proofUrl) ?>" target="_blank">View Proof</a>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <strong>AMENITIES:</strong> <span><?= !empty($amenitiesString) ? $amenitiesString : 'None' ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>EXTRAS:</strong> <span><?= !empty($extrasString) ? $extrasString : 'None' ?></span>
                    </div>
                    <div class="detail-row total-amount">
                        <strong>Total:</strong> <span>₱<?= number_format($fullTotal, 2) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>50% Due:</strong> <span>₱<?= number_format($fiftyPercentDue, 2) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Status:</strong> <span><?= htmlspecialchars(ucfirst($r['status'])) ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../src/php/templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const envelopeHeaders = document.querySelectorAll('.envelope-header');

    envelopeHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const bookingDetails = this.nextElementSibling; // The div containing booking details
            const envelopeIcon = this.querySelector('.envelope-icon');

            if (bookingDetails.style.display === 'none') {
                bookingDetails.style.display = 'block';
                envelopeIcon.classList.remove('fa-envelope');
                envelopeIcon.classList.add('fa-envelope-open');
            } else {
                bookingDetails.style.display = 'none';
                envelopeIcon.classList.remove('fa-envelope-open');
                envelopeIcon.classList.add('fa-envelope');
            }
        });
    });
});
</script>
