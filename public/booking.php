<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
require_once __DIR__ . '/../src/php/templates/header.php';

// Check if booking ID is in session
$booking_id = $_SESSION['current_booking_id'] ?? null;

if (!$booking_id) {
    // If no booking ID, redirect to rooms page or display an error
    header('Location: rooms.php');
    exit;
}

// Fetch booking details from the database
$stmt = $pdo->prepare("
    SELECT
        b.booking_id,
        b.check_in_date,
        b.check_out_date,
        b.number_of_guests,
        b.quantity,
        b.total_amount,
        rt.name AS room_type
    FROM bookings b
    JOIN room_types rt ON b.room_type_id = rt.room_type_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$bookingDbDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bookingDbDetails) {
    // Booking not found in DB, redirect or error
    header('Location: rooms.php');
    exit;
}

// Set totalAmount directly from database value (it now includes extras due to triggers)
$totalAmount = (float)($bookingDbDetails['total_amount'] ?? 0);

// Fetch room type details including rate_per_night
$stmt = $pdo->prepare("SELECT room_type_id, name, rate_per_night FROM room_types WHERE name = ?");
$stmt->execute([ucfirst($bookingDbDetails['room_type'])]);
$roomTypeDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$roomTypeDetails) {
    // Room type not found, redirect or error
    header('Location: rooms.php');
    exit;
}

// Fetch amenities
$stmt = $pdo->prepare("
    SELECT a.name
    FROM booking_amenities ba
    JOIN amenities a ON ba.amenity_id = a.amenity_id
    WHERE ba.booking_id = ?
");
$stmt->execute([$booking_id]);
$rawAmenities = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rawAmenities[$row['name']] = 1; // Store original name
}

// Fetch extras
$stmt = $pdo->prepare("
    SELECT ei.name, be.quantity
    FROM booking_extras be
    JOIN extras ei ON be.extra_id = ei.extra_id
    WHERE be.booking_id = ?
");
$stmt->execute([$booking_id]);
$extras = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $extras[strtolower($row['name'])] = (int)$row['quantity'];
}

// Get extra item prices from the database (still needed for display of individual extra prices)
$extraPrices = getExtraPrices();
// Convert keys to lowercase for consistent lookup
$extraPrices = array_change_key_case($extraPrices, CASE_LOWER);

// Construct bookingDetails array similar to pending_reservation
$bookingDetails = [
    'room_type' => $bookingDbDetails['room_type'],
    'check_in'  => $bookingDbDetails['check_in_date'],
    'check_out' => $bookingDbDetails['check_out_date'],
    'guests'    => $bookingDbDetails['number_of_guests'],
    'quantity'  => $bookingDbDetails['quantity']
];

// Calculate number of nights
$check_in_obj = new DateTime($bookingDetails['check_in']);
$check_out_obj = new DateTime($bookingDetails['check_out']);
$interval = $check_in_obj->diff($check_out_obj);
$numberOfNights = $interval->days > 0 ? $interval->days : 1; // Ensure at least 1 night

$roomPrice = (float)($roomTypeDetails['rate_per_night'] ?? 0); // Use dynamically fetched price
$roomTitle = $roomTypeDetails['name'] ?? '';
$roomQuantity = $bookingDetails['quantity'] ?? 1; // Default to 1 if not set

// Amenities are free: filter only those checked
$amenities = array_filter($rawAmenities ?? [], fn($qty) => $qty > 0);

// The totalAmount is already correct from the database due to triggers
$fiftyPercent = $totalAmount * 0.5;

?>

<style>
body {
    font-family: 'Cormorant Garamond', serif; /* Apply elegant font to body */
    background-color: #4e2e1b; /* Dark brown background for the page */
    margin: 0;
    padding: 0;
    color: #f8f1e5; /* Light text for contrast */
}

.receipt-container {
    max-width: 800px;
    margin: 50px auto;
    background-color: #1a1a1a; /* Black background for the container */
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.5); /* Stronger shadow */
}

.receipt-header {
    text-align: left;
    margin-bottom: 30px;
    border-bottom: 2px solid #d4af37; /* Gold border */
    padding-bottom: 20px;
}

.receipt-header h1 {
    color: #d4af37; /* Gold for main heading */
    font-family: 'Playfair Display', serif; /* Elegant font for heading */
    margin-bottom: 0px;
    font-size: 2.5em;
}

.receipt-header p {
    display: none;
}

.receipt-section {
    margin-bottom: 20px;
}

.receipt-section h2 {
    color: #d4af37; /* Gold for section headings */
    font-family: 'Playfair Display', serif; /* Elegant font for headings */
    border-bottom: 1px solid rgba(212, 175, 55, 0.3); /* Lighter gold border */
    padding-bottom: 10px;
    margin-bottom: 15px;
    font-size: 1.5em;
}

.receipt-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 0;
}

.receipt-details p {
    margin: 0;
    color: #f8f1e5; /* Light text for details */
    display: contents;
}

.receipt-details strong {
    display: inline-block;
    color: #f8f1e5; /* Light text for strong elements */
    text-align: left;
    padding-right: 10px;
}

.receipt-details span {
    text-align: right;
}

.amenities-list, .extras-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.amenities-list li, .extras-list li {
    margin-bottom: 5px;
    color: #f8f1e5;
}

.extras-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.extras-table th,
.extras-table td {
    padding: 10px;
    /* border-bottom: 1px solid rgba(248, 241, 229, 0.1); */ /* Removed general border */
    text-align: left;
    color: #f8f1e5;
}

.extras-table th {
    color: #d4af37;
    font-family: 'Playfair Display', serif;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.9em;
    border-bottom: 1px solid rgba(248, 241, 229, 0.1); /* Added border to header cells */
}

.extras-table tbody td {
    border-bottom: 1px solid rgba(248, 241, 229, 0.1); /* Add border to body cells */
}

.extras-table tbody tr:last-child td {
    border-bottom: none;
}

.extras-table tfoot tr {
    border-top: 2px solid #d4af37; /* Thick gold line above each footer row */
}

.extras-table tfoot tr:last-child {
    border-bottom: 2px solid #d4af37; /* Thick gold line below the last footer row */
}

.extras-table tfoot td {
    font-weight: bold;
}

.extras-table .text-right {
    text-align: right;
}

.total-amount {
    font-size: 1.8em;
    color: #d4af37;
    font-weight: bold;
    text-align: right;
    margin-top: 30px;
}

.account-details {
    background-color: #222;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
    text-align: left;
}

.account-details p {
    margin: 5px 0;
    font-size: 1em;
}

.confirm-payment-btn {
    display: inline-block;
    background-color: #d4af37;
    color: #1a1a1a;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2em;
    font-weight: bold;
    text-transform: uppercase;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 20px;
}

.confirm-payment-btn:hover {
    background-color: #e0b84c;
    transform: translateY(-2px);
}

.confirm-payment-btn:active {
    transform: translateY(0);
}

.redirect-message {
    text-align: center;
    margin-top: 20px;
    color: #d4af37;
    font-size: 1.2em;
}

.loading-spinner {
    border: 4px solid rgba(248, 241, 229, 0.3);
    border-top: 4px solid #d4af37;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.book-now-btn {
    display: inline-block;
    padding: 15px 30px;
    background-color: #d4af37; /* Yellow background */
    color: #1a1a1a; /* Black text */
    text-decoration: none;
    border-radius: 8px;
    font-family: 'Playfair Display', serif;
    font-size: 1.5em;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    border: 1px solid #d4af37;
    cursor: pointer;
}

.book-now-btn:hover {
    background-color: transparent;
    color: #d4af37;
}

/* Modal Styles */
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0,0,0,0.7); /* Black w/ opacity */
    display: flex; /* Use flexbox for centering */
    align-items: center; /* Center vertically */
    justify-content: center; /* Center horizontally */
}

.modal-content {
    background-color: #1a1a1a;
    margin: auto;
    padding: 30px;
    border: 1px solid #d4af37;
    border-radius: 10px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    position: relative;
    color: #f8f1e5;
}

.modal-content h2 {
    text-align: center;
    margin-bottom: 20px;
    font-family: 'Playfair Display', serif;
}

.modal-content .form-group {
    margin-bottom: 15px;
}

.modal-content label {
    display: block;
    margin-bottom: 5px;
    font-family: 'Cormorant Garamond', serif;
}

.modal-content input[type="text"],
.modal-content input[type="file"] {
    width: calc(100% - 22px); /* Adjust for padding and border */
    padding: 10px;
    border: 1px solid #d4af37;
    border-radius: 5px;
    background-color: #333;
    color: #f8f1e5;
    font-size: 1em;
    font-family: 'Cormorant Garamond', serif;
}

.modal-content input[type="file"] {
    background-color: transparent;
    border: none;
}

.modal-content .confirm-payment-btn {
    display: block;
    width: 100%;
    padding: 15px;
    background-color: #d4af37;
    color: #1a1a1a;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2em;
    font-weight: bold;
    text-transform: uppercase;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 20px;
}

.modal-content .confirm-payment-btn:hover {
    background-color: #e0b84c;
    transform: translateY(-2px);
}

.modal-content .close-button {
    position: absolute;
    top: 10px;
    right: 20px;
    color: #f8f1e5;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.modal-content .close-button:hover,
.modal-content .close-button:focus {
    color: #d4af37;
    text-decoration: none;
}
</style>

<main>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>Booking Confirmation</h1>
            <p>Thank you for your booking!</p>
        </div>

        <div class="receipt-section">
            <h2>Room Information</h2>
            <div class="receipt-details">
                <p><strong>Room Type:</strong> <span><?= htmlspecialchars($bookingDetails['room_type']) ?></span></p>
                <p><strong>Guests:</strong> <span><?= htmlspecialchars($bookingDetails['guests']) ?> Pax</span></p>
                <p><strong>Quantity:</strong> <span><?= htmlspecialchars($bookingDetails['quantity']) ?></span></p>
                <p><strong>Rate Per Night:</strong> <span>₱<?= htmlspecialchars(number_format($roomPrice, 2)) ?></span></p>
                <p><strong>Check-in Date:</strong> <span><?= htmlspecialchars($bookingDetails['check_in']) ?></span></p>
                <p><strong>Check-out Date:</strong> <span><?= htmlspecialchars($bookingDetails['check_out']) ?></span></p>
                <p><strong>Number of Nights:</strong> <span><?= htmlspecialchars($numberOfNights) ?></span></p>
            </div>
        </div>

        <?php if (!empty($amenities)): ?>
        <div class="receipt-section">
            <h2>Amenities</h2>
            <ul class="amenities-list">
                <?php foreach ($amenities as $amenityName => $_): ?>
                    <li><?= htmlspecialchars($amenityName) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($extras)): ?>
        <div class="receipt-section">
            <h2>Extras</h2>
            <table class="extras-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($extras as $extraName => $qty):
                        $price = $extraPrices[$extraName] ?? 0;
                        $lineTotal = $price * $qty;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars(ucfirst($extraName)) ?></td>
                        <td class="text-right">₱<?= number_format($price,2) ?></td>
                        <td class="text-right"><?= $qty ?></td>
                        <td class="text-right">₱<?= number_format($lineTotal,2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total:</strong></td>
                        <td class="text-right">₱<?= number_format($totalAmount,2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right"><strong>50%:</strong></td>
                        <td class="text-right">₱<?= number_format($fiftyPercent,2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <div style="text-align: center;">
        <button type="button" class="confirm-payment-btn" id="proceedToPaymentBtn">Proceed to Payment</button>
    </div>
</main>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <h2 style="color: #d4af37;">Payment Information</h2>
    <div class="qr-code" style="margin: 20px auto; text-align: center;">
        <img src="/kathelia-suites/assets/images/kathelia-qr.png" alt="GCash QR Code" style="width: 150px; height: 150px; border: 5px solid #d4af37; border-radius: 8px;">
        <p style="font-size:0.9em; color:rgba(248, 241, 229, 0.8); margin-top:10px;">Scan the QR code to pay.</p>
    </div>
    <form action="process_payment.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking_id) ?>">
      <div class="form-group">
        <label for="gcash_ref" style="color: #d4af37;">Reference number:</label>
        <input type="text" id="gcash_ref" name="gcash_ref" required style="width: calc(100% - 20px); padding: 10px; border: 1px solid #d4af37; border-radius: 5px; background-color: #333; color: #f8f1e5; font-size: 1em;">
      </div>
      <div class="form-group">
        <label for="payer_name" style="color: #d4af37;">ENTER NAME:</label>
        <input type="text" id="payer_name" name="payer_name" required style="width: calc(100% - 20px); padding: 10px; border: 1px solid #d4af37; border-radius: 5px; background-color: #333; color: #f8f1e5; font-size: 1em;">
      </div>
      <div class="form-group">
        <label for="amount" style="color: #d4af37;">AMOUNT:</label>
        <p style="font-size: 1.2em; color: #f8f1e5;">₱<?= htmlspecialchars(number_format($fiftyPercent, 2)) ?></p>
        <input type="hidden" name="amount_paid" value="<?= htmlspecialchars($fiftyPercent) ?>">
      </div>
      <div class="form-group">
        <label for="proof_of_payment" style="color: #d4af37;">PROOF OF PAYMENT:</label>
        <input type="file" id="proof_of_payment" name="proof_of_payment" accept="image/*" required style="width: 100%; padding: 10px 0; color: #f8f1e5;">
      </div>
      <button type="submit" class="confirm-payment-btn">Confirm Payment</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../src/php/templates/footer.php'; ?>

<script>
// Get the modal
const paymentModal = document.getElementById('paymentModal');

// Get the button that opens the modal
const proceedToPaymentBtn = document.getElementById('proceedToPaymentBtn');

// Get the <span> element that closes the modal
const closePaymentModal = document.getElementById('closePaymentModal');

// When the user clicks the button, open the modal
if (proceedToPaymentBtn) {
    proceedToPaymentBtn.onclick = function() {
        paymentModal.style.display = "flex";
    }
}

// When the user clicks on <span> (x), close the modal
if (closePaymentModal) {
    closePaymentModal.onclick = function() {
        paymentModal.style.display = "none";
    }
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == paymentModal) {
        paymentModal.style.display = "none";
    }
}

// Ensure the modal is hidden by default on page load.
// This might be needed if the modal is set to display:flex by default in CSS or previous scripts.
if (paymentModal) {
    paymentModal.style.display = 'none';
}

</script>