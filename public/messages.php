<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch approved bookings for the current user
$sql = "
  SELECT
    b.booking_id,
    rt.name AS room_type_name,
    b.number_of_guests,
    b.check_in_date,
    b.check_out_date,
    p.reference_number,
    p.proof_url,
    b.status AS booking_status,
    b.total_amount AS total_amount,
    GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ', ') AS amenities,
    GROUP_CONCAT(DISTINCT CONCAT(e.name, ' (x', be.quantity, ' @ ', be.rate_at_booking, ')') ORDER BY e.name SEPARATOR ', ') AS extras
  FROM bookings b
  JOIN room_types rt
    ON b.room_type_id = rt.room_type_id
  LEFT JOIN payments p
    ON b.booking_id = p.booking_id
  LEFT JOIN booking_amenities ba
    ON b.booking_id = ba.booking_id
  LEFT JOIN amenities a
    ON ba.amenity_id = a.amenity_id
  LEFT JOIN booking_extras be
    ON b.booking_id = be.booking_id
  LEFT JOIN extras e
    ON be.extra_id = e.extra_id
  WHERE b.user_id = :user_id AND b.status = 'approved'
  GROUP BY b.booking_id
  ORDER BY b.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$approved_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../src/php/templates/header.php';
?>

<style>
  .receipt-container {
    max-width: 900px;
    margin: 50px auto;
    background-color: #1a1a1a;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    color: #f8f1e5;
  }

  .receipt-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px dashed #d4af37;
  }

  .receipt-header h1 {
    color: #d4af37;
    font-family: 'Playfair Display', serif;
    font-size: 2.5em;
    margin-bottom: 10px;
  }

  .receipt-header p {
    font-size: 1.1em;
    color: #b0b0b0;
  }

  .booking-details {
    margin-bottom: 30px;
    background-color: #1a1a1a;
    padding: 20px;
    border-radius: 8px;
  }

  .booking-details h2 {
    color: #d4af37;
    font-family: 'Playfair Display', serif;
    font-size: 1.8em;
    margin-bottom: 20px;
    border-bottom: 1px solid #d4af37;
    padding-bottom: 5px;
  }

  .booking-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px 30px;
  }

  .detail-item strong {
    color: #d4af37;
    display: block;
    margin-bottom: 5px;
    font-size: 0.9em;
    text-transform: uppercase;
  }

  .detail-item span {
    font-size: 1.1em;
    color: #f8f1e5;
  }

  .total-amount {
    text-align: right;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px dashed #d4af37;
  }

  .total-amount p {
    font-size: 1.8em;
    color: #d4af37;
    font-weight: bold;
    margin: 0;
  }

  .total-amount span {
    font-size: 0.8em;
    color: #b0b0b0;
  }

  .no-bookings-message {
    text-align: center;
    padding: 50px 20px;
    font-size: 1.2em;
    color: #b0b0b0;
  }

  .proof-link {
    display: inline-block;
    margin-top: 10px;
    color: #d4af37;
    text-decoration: none;
    border: 1px solid #d4af37;
    padding: 5px 10px;
    border-radius: 4px;
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  .proof-link:hover {
    background-color: #d4af37;
    color: #1a1a1a;
  }
</style>

<div class="receipt-container">
  <div class="receipt-header">
    <h1>Your Approved Bookings</h1>
    <p>Here are the details of your confirmed reservations at Kathelia Suites.</p>
  </div>

  <?php if (empty($approved_bookings)): ?>
    <p class="no-bookings-message">No approved bookings found yet.</p>
  <?php else: ?>
    <?php foreach ($approved_bookings as $booking): ?>
      <div class="booking-details">
        <h2>Booking #<?= htmlspecialchars($booking['booking_id']) ?></h2>
        <div class="booking-details-grid">
          <div class="detail-item">
            <strong>Room Type</strong>
            <span><?= htmlspecialchars($booking['room_type_name']) ?></span>
          </div>
          <div class="detail-item">
            <strong>Guests</strong>
            <span><?= htmlspecialchars($booking['number_of_guests']) ?></span>
          </div>
          <div class="detail-item">
            <strong>Check-in Date</strong>
            <span><?= htmlspecialchars($booking['check_in_date']) ?></span>
          </div>
          <div class="detail-item">
            <strong>Check-out Date</strong>
            <span><?= htmlspecialchars($booking['check_out_date']) ?></span>
          </div>
          <div class="detail-item">
            <strong>Reference Number</strong>
            <span><?= htmlspecialchars($booking['reference_number'] ?? 'N/A') ?></span>
          </div>
          <?php if (!empty($booking['proof_url'])): ?>
            <div class="detail-item">
              <strong>Proof of Payment</strong>
              <span><a href="<?= htmlspecialchars($booking['proof_url']) ?>" target="_blank" class="proof-link">View Proof</a></span>
            </div>
          <?php endif; ?>
          <div class="detail-item">
            <strong>Amenities</strong>
            <span><?= htmlspecialchars($booking['amenities'] ?? 'None') ?></span>
          </div>
          <div class="detail-item">
            <strong>Extras</strong>
            <span><?= htmlspecialchars($booking['extras'] ?? 'None') ?></span>
          </div>
        </div>
        <div class="total-amount">
          <p>Total: ₱<?= number_format($booking['total_amount'] * 0.50, 2) ?></p>
          <span>(Amount Paid)</span>
        </div>
        <?php error_log("DEBUG: Messages Page - Displayed Total for Booking ID " . $booking['booking_id'] . ": " . $booking['total_amount']); // DEBUG_LOG ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include '../src/php/templates/footer.php'; ?> 