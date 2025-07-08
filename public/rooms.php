<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
requireLogin(); // Reverted: Restore original requireLogin()
// public/rooms.php
require_once __DIR__ . '/../src/php/templates/header.php';

// Determine if the user came from the home page search
$from_home_search = isset($_GET['check_in']) && isset($_GET['check_out']) && isset($_GET['guests']);

// Get extra item prices from the database
$extraPrices = getExtraPrices();

// Get booking parameters from GET request (from home.php) or set defaults
$check_in  = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';
$guests    = (int)($_GET['guests'] ?? 0);

// Set minimum check-in date for the modal (consistent with home.php)
$min_checkin_date = date('Y-m-d', strtotime('+2 days'));

// Room configurations with multiple images
$rooms = [];
$stmt = $pdo->query("SELECT name, rate_per_night, min_pax, max_pax, total_rooms FROM room_types");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roomName = strtolower($row['name']);
    $rooms[$roomName] = [
        'title' => ucfirst($roomName) . ' Room (' . $row['min_pax'] . '–' . $row['max_pax'] . ' Pax)',
        'images' => [], // Images will still be hardcoded or fetched separately if needed
        'desc' => '', // Description will need to be fetched or hardcoded for now
        'guest_range' => [(int)$row['min_pax'], (int)$row['max_pax']],
        'amount' => '₱' . number_format($row['rate_per_night'], 2) . '/night',
        'rate_per_night_raw' => (float)$row['rate_per_night'], // Store raw for calculations
        'total_rooms' => (int)$row['total_rooms'] // Add total_rooms to the room data
    ];

    // Populate descriptions and images (can be fetched dynamically or kept here)
    switch ($roomName) {
        case 'twin':
            $rooms[$roomName]['desc'] = 'Good for 2 Pax. Spacious comfort for friends or colleagues.';
            $rooms[$roomName]['images'] = ['twin-room.png', 'twin-room-2.png', 'twin-room-3.png'];
            break;
        case 'family':
            $rooms[$roomName]['desc'] = 'Good for 3 to 5 Pax. Perfect for family getaways and bonding.';
            $rooms[$roomName]['images'] = ['family-room.png', 'family-room-2.png', 'family-room-3.png'];
            break;
        case 'harmony':
            $rooms[$roomName]['desc'] = 'Good for 6 to 10 Pax. Ultimate relaxation for large groups.';
            $rooms[$roomName]['images'] = ['harmony-room.png', 'harmony-room-2.png', 'harmony-room-3.png'];
            break;
    }
}

// Handle reservation details if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("rooms.php: POST request received.");

    $roomType = $_POST['room_type'] ?? '';
    $rawAmenities = [];
    $amenityMap = [
        'pool' => 'Swimming Pool',
        'billiards' => 'Billiards',
        'basketball' => 'Basketball',
        'gym' => 'Gym'
    ];

    foreach ($amenityMap as $shortName => $fullName) {
        $postKey = $shortName . '_' . $roomType;
        if (isset($_POST[$postKey]) && $_POST[$postKey] == '1') {
            $rawAmenities[$fullName] = 1; // Store full name in rawAmenities
        }
    }

    // Capture user-selected dates and guests
    $check_in_date_post = $_POST['check_in'] ?? date('Y-m-d'); // Default to today
    $check_out_date_post = $_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day')); // Default to tomorrow
    $number_of_guests_post = (int)($_POST['guests'] ?? 0);

    // Prepare pending reservation details with user-selected dates/guests
    $_SESSION['pending_reservation'] = [
        'room_type' => $roomType,
        'check_in'  => $check_in_date_post,
        'check_out' => $check_out_date_post,
        'guests'    => $number_of_guests_post,
        'amenities' => $rawAmenities,
        'extras'    => [
            'pillow'  => (int)($_POST['pillow']  ?? 0),
            'blanket' => (int)($_POST['blanket'] ?? 0),
            'slipper' => (int)($_POST['slipper'] ?? 0),
            'towel'   => (int)($_POST['towel']   ?? 0)
        ],
        'quantity' => max(1, (int)($_POST['quantity'] ?? 0))
    ];
    error_log("rooms.php: pending_reservation set. Content: " . json_encode($_SESSION['pending_reservation']));

    // Retrieve booking details from session for database insertion
    $bookingDetails = $_SESSION['pending_reservation'];
    error_log("rooms.php: Booking details for DB insertion: " . json_encode($bookingDetails)); // Log booking details

    if (!isset($_SESSION['user_id'])) {
        error_log("rooms.php: User not logged in. Setting pending_booking_details and redirecting.");
        // Store booking details in session before redirecting to login
        $_SESSION['pending_booking_details'] = $bookingDetails;
        header('Location: /kathelia-suites/public/login.php?redirect=booking.php');
        exit;
    } else {
        // User is logged in, save booking to database and proceed to booking details page
        $user_id = $_SESSION['user_id'];
        $roomType = $bookingDetails['room_type'];
        
        // Use dates and guests from bookingDetails (user input or defaults)
        $check_in_date = $bookingDetails['check_in'];
        $check_out_date = $bookingDetails['check_out'];
        $number_of_guests = $bookingDetails['guests'];
        
        $quantity = $bookingDetails['quantity'];

        // Calculate number of nights
        $check_in_obj = new DateTime($check_in_date);
        $check_out_obj = new DateTime($check_out_date);
        $interval = $check_in_obj->diff($check_out_obj);
        $numberOfNights = $interval->days > 0 ? $interval->days : 1; // Ensure at least 1 night

        // Fetch room_type_id
        $stmt = $pdo->prepare("SELECT room_type_id FROM room_types WHERE name = ?");
        $stmt->execute([ucfirst($roomType)]); // Assuming room_type names in DB are capitalized (e.g., 'Twin', 'Family', 'Harmony')
        $room_type_id = $stmt->fetchColumn();

        if ($room_type_id) {
            // Calculate grand total: (room_rate_per_night * quantity_of_rooms * number_of_nights) + extras_total
            $roomPrice = (float)$rooms[$roomType]['rate_per_night_raw'];
            $roomQuantity = $quantity; // Quantity of rooms booked
            $grandTotal = ($roomPrice * $roomQuantity * $numberOfNights);

            // Add extras cost to grand total
            if ($bookingDetails && isset($bookingDetails['extras'])) {
                foreach ($bookingDetails['extras'] as $extra => $qty) {
                    // Use the dynamically fetched price for each extra item
                    $itemPrice = $extraPrices[strtolower($extra)] ?? 0; // Default to 0 if price not found
                    $grandTotal += ($qty * $itemPrice);
                }
            }

            // Amenities are free: filter only those checked
            $amenities = array_filter($bookingDetails['amenities'] ?? [], fn($qty) => $qty > 0);
            error_log("rooms.php: Filtered amenities to save: " . json_encode($amenities)); // Log filtered amenities

            // Save booking to database
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_type_id, check_in_date, check_out_date, number_of_guests, quantity, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $room_type_id, $check_in_date, $check_out_date, $number_of_guests, $quantity, $grandTotal]);
            $booking_id = $pdo->lastInsertId();
            $_SESSION['current_booking_id'] = $booking_id; // Store booking ID in session
            error_log("rooms.php: Booking saved with ID: " . $booking_id); // Log booking ID

            // Save amenities
            foreach ($amenities as $amenityName => $value) {
                error_log("rooms.php: Processing amenity: " . $amenityName);
                $stmt = $pdo->prepare("SELECT amenity_id FROM amenities WHERE name = ?");
                $stmt->execute([ucfirst($amenityName)]);
                $amenity_id = $stmt->fetchColumn();
                error_log("rooms.php: Amenity " . $amenityName . " - amenity_id found: " . ($amenity_id ?? "null"));

                if ($amenity_id) {
                    $stmt = $pdo->prepare("INSERT INTO booking_amenities (booking_id, amenity_id) VALUES (?, ?)");
                    $stmt->execute([$booking_id, $amenity_id]);
                    error_log("rooms.php: Inserted booking_amenity for " . $amenityName);
                } else {
                    error_log("rooms.php: Failed to find amenity_id for " . $amenityName);
                }
            }

            // Save extras
            foreach ($bookingDetails['extras'] as $extraName => $quantity) {
                if ($quantity > 0) {
                    error_log("rooms.php: Processing extra: " . $extraName . " with quantity " . $quantity);
                    $stmt = $pdo->prepare("SELECT extra_id, rate FROM extras WHERE name = ?");
                    $stmt->execute([ucfirst($extraName)]);
                    $extraItem = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("rooms.php: Extra " . $extraName . " - extraItem found: " . json_encode($extraItem));

                    if ($extraItem) {
                        $extra_id = $extraItem['extra_id'];
                        $rate_at_booking = (float)($extraItem['rate'] ?? 0.00); // Ensure it's a float and default to 0.00 if null
                        $stmt = $pdo->prepare("INSERT INTO booking_extras (booking_id, extra_id, quantity, rate_at_booking) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$booking_id, $extra_id, $quantity, $rate_at_booking]);
                        error_log("rooms.php: Inserted booking_extra for " . $extraName);
                    } else {
                        error_log("rooms.php: Failed to find extra_id for " . $extraName);
                    }
                }
            }
        }

        header('Location: /kathelia-suites/public/booking.php');
        exit;
    }
}
?>
<style>
.rooms-main {
    padding: 4rem 2rem;
    background: #4e2e1b;
    color: #f8f1e5;
}
.room-section {
    margin: 6rem 0;
    scroll-margin-top: 90px; /* Adjust for fixed header */
}
.room-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2.5rem 2rem;
    gap: 3rem;
}
.room-container.reverse {
    flex-direction: row-reverse;
}
.room-image {
    flex: 1;
    position: relative;
    overflow: hidden;
    border-radius: 18px;
}
.slideshow {
    position: relative;
    width: 100%;
    height: 420px;
}
.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out;
}
.slide.active {
    opacity: 1;
}
.slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 18px;
}
.room-content {
    flex: 1.2;
    padding: 2rem 1.5rem;
    color: #f8f1e5;
    font-size: 1.35rem;
}
.room-content h2 {
    font-size: 2.8rem;
    margin-bottom: 1.2rem;
    font-family: 'Playfair Display', serif;
}
.room-content p {
    margin-bottom: 1.7rem;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
}
.room-content b {
    font-size: 1.25rem;
}
.text-right {
    text-align: right;
}
.text-left {
    text-align: left;
}
#family {
    margin-top: 10rem;
}
.pay-btn {
    display: inline-block;
    padding: 1.2rem 3.5rem;
    background-color: #d4af37;
    color: #1a1a1a;
    text-decoration: none;
    border-radius: 8px;
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    border: 1px solid #d4af37;
    margin-top: 2.2rem;
    cursor: pointer;
}
.pay-btn:hover {
    background-color: transparent;
    color: #d4af37;
}
.amenities-extras {
    margin-top: 2.2rem;
    color: #f8f1e5;
    font-size: 1.25rem;
    line-height: 2.2rem;
}
.amenities-grid {
    display: grid;
    grid-template-columns: auto 1fr; /* Label, Checkbox */
    gap: 0.8rem 1.2rem; /* Adjusted gap for better spacing */
    align-items: center;
    margin-bottom: 1.2rem;
}
.extras-grid {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr; /* Label, Input, Label, Input */
    gap: 0.5rem 1.2rem;
    margin-top: 0.8rem;
}
.amenities-extras label {
    font-size: 1.15rem;
    margin: 0;
    display: block; /* Ensure labels take their own row in the grid */
}
.amenities-extras label span {
    display: none; /* Hide the descriptive spans for amenities */
}
.amenities-extras input[type='number'] {
    width: 70px;
    font-size: 1.15rem;
    padding: 0.3rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #d4af37;
    background: #fff8e1;
    color: #603813;
    margin-left: 0.5rem;
}
.amenities-extras input[type='checkbox'] {
    transform: scale(1.5); /* Make checkbox larger */
    margin-left: 10px; /* Add some spacing */
    accent-color: #d4af37; /* Gold accent color */
}

/* New Modal Styles for Room Details */
.room-details-modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6); /* Semi-transparent black overlay */
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px); /* Subtle blur for the background */
}

.room-details-modal-content {
    background-color: rgba(26, 26, 26, 0.8); /* Slightly transparent dark background */
    padding: 30px;
    border-radius: 18px; /* Softer rounded corners */
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); /* Enhanced shadow for depth */
    text-align: center; /* Keep text centered unless specified otherwise */
    width: 90%;
    max-width: 500px;
    position: relative;
    color: #f8f1e5;
    border: 1px solid rgba(212, 175, 55, 0.3); /* Subtle gold border */
    backdrop-filter: blur(10px); /* Frosted glass effect for content */
}

.room-details-modal-content h2 {
    color: #d4af37;
    margin-bottom: 25px;
    font-size: 2.2em; /* Slightly larger title */
    font-family: 'Playfair Display', serif;
}

.room-details-form-group {
    margin-bottom: 20px;
    text-align: left; /* Align labels to the left */
}

.room-details-form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 1.1em;
    color: #d4af37; /* Gold for labels */
    font-family: 'Cormorant Garamond', serif;
}

.room-details-input {
    width: calc(100% - 20px); /* Account for padding and border */
    padding: 10px;
    border: 1px solid #d4af37;
    border-radius: 5px;
    background-color: #333; /* Darker input background */
    color: #f8f1e5;
    font-size: 1em;
    font-family: 'Cormorant Garamond', serif;
}

.room-details-input:focus {
    outline: none;
    border-color: #e0b84c;
    box-shadow: 0 0 5px rgba(212, 175, 55, 0.5);
}

.room-details-book-btn {
    background-color: #d4af37;
    color: #1a1a1a;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2em;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 20px;
}

.room-details-book-btn:hover {
    background-color: #e0b84c;
    transform: translateY(-2px);
}

.room-details-book-btn:active {
    transform: translateY(0);
}

/* Close button for modal */
.close-button {
    color: #f8f1e5;
    position: absolute;
    top: 15px;
    right: 25px;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-button:hover,
.close-button:focus {
    color: #d4af37;
    text-decoration: none;
}
</style>
<main class="rooms-main">
<?php
$is_reverse = false;
foreach ($rooms as $id => $room): ?>
  <section class="room-section" id="<?= $id ?>">
    <div class="room-container <?= $is_reverse ? 'reverse' : '' ?>">
      <div class="room-image">
        <div class="slideshow">
          <?php foreach ($room['images'] as $index => $image): ?>
            <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                      <img src="/kathelia-suites/assets/images/<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($room['title']) ?> Image <?= $index + 1 ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="room-content">
        <h2><?= htmlspecialchars($room['title']) ?></h2>
        <p><?= htmlspecialchars($room['desc']) ?></p>
        <p class="room-price">Price: <?= htmlspecialchars($room['amount']) ?></p>
        <p class="room-pax">Guests: <?= htmlspecialchars($room['guest_range'][0]) ?>–<?= htmlspecialchars($room['guest_range'][1]) ?> Pax</p>
        <form method="POST" action="">
            <input type="hidden" name="room_type" value="<?= $id ?>">
            
            <?php if (isset($_SESSION['user_id'])): // Display amenities, extras, and quantity only if logged in ?>
            <div class="amenities-extras">
                <div><b>Amenities:</b></div>
                <div class="amenities-grid">
                    <label for="pool-<?= $id ?>">Pool:</label>
                    <input id="pool-<?= $id ?>" name="pool_<?= $id ?>" type="checkbox" value="1">
                    <label for="billiards-<?= $id ?>">Billiards:</label>
                    <input id="billiards-<?= $id ?>" name="billiards_<?= $id ?>" type="checkbox" value="1">
                    <label for="basketball-<?= $id ?>">Basketball:</label>
                    <input id="basketball-<?= $id ?>" name="basketball_<?= $id ?>" type="checkbox" value="1">
                    <label for="gym-<?= $id ?>">Gym:</label>
                    <input id="gym-<?= $id ?>" name="gym_<?= $id ?>" type="checkbox" value="1">
                </div>
                <div style="margin-top:1.2rem;"><b>Extras:</b> <span style="font-size: 0.8em; color: rgba(248, 241, 229, 0.85); display: inline;"> (How many would you like to avail?)</span></div>
                <div class="extras-grid">
                    <label for="pillow-<?= $id ?>">
                        Pillow <small style="font-size: 0.8em; color: rgba(248, 241, 229, 0.7);">(₱<?= htmlspecialchars($extraPrices['Pillow'] ?? '0.00') ?>)</small>:
                    </label>
                    <input id="pillow-<?= $id ?>" name="pillow" type="number" min="0" value="0">

                    <label for="blanket-<?= $id ?>">
                        Blanket <small style="font-size: 0.8em; color: rgba(248, 241, 229, 0.7);">(₱<?= htmlspecialchars($extraPrices['Blanket'] ?? '0.00') ?>)</small>:
                    </label>
                    <input id="blanket-<?= $id ?>" name="blanket" type="number" min="0" value="0">

                    <label for="slipper-<?= $id ?>">
                        Slipper <small style="font-size: 0.8em; color: rgba(248, 241, 229, 0.7);">(₱<?= htmlspecialchars($extraPrices['Slipper'] ?? '0.00') ?>)</small>:
                    </label>
                    <input id="slipper-<?= $id ?>" name="slipper" type="number" min="0" value="0">

                    <label for="towel-<?= $id ?>">
                        Towel <small style="font-size: 0.8em; color: rgba(248, 241, 229, 0.7);">(₱<?= htmlspecialchars($extraPrices['Towel'] ?? '0.00') ?>)</small>:
                    </label>
                    <input id="towel-<?= $id ?>" name="towel" type="number" min="0" value="0">
                </div>
            </div>
            <div style="margin-top:2.2rem;">
                <label for="quantity-<?= $id ?>" style="color:#f8f1e5; font-size:1.25rem;">How many rooms would you like to avail?</label>
                <input id="quantity-<?= $id ?>" name="quantity" type="number" min="1" value="1" style="width: 70px; font-size: 1.15rem; padding: 0.3rem 0.5rem; border-radius: 4px; border: 1px solid #d4af37; background: #fff8e1; color: #603813; margin-left: 0.5rem;">
            </div>
            <?php else: // If not logged in, we only need to pass room type and a default quantity for redirect to login ?>
                <input type="hidden" name="quantity" value="1">
            <?php endif; ?>
            <button type="button" class="pay-btn book-now-trigger" data-total-rooms="<?= $room['total_rooms'] ?>" data-room-id="<?= $id ?>">Book Now</button>
        </form>
      </div>
    </div>
    <!-- Room Details Modal for this room type -->
    <div id="roomDetailsModal_<?= $id ?>" class="room-details-modal">
        <div class="room-details-modal-content">
            <span class="close-button" id="closeModal_<?= $id ?>">&times;</span>
            <h2>Proceed to Booking</h2>
            <form id="bookingForm_<?= $id ?>" method="POST" action="">
                <input type="hidden" name="room_type" value="<?= $id ?>">
                <div class="room-details-form-group">
                    <label for="modal_check_in_<?= $id ?>">Check-in Date:</label>
                    <input type="date" id="modal_check_in_<?= $id ?>" name="check_in" class="room-details-input" value="<?= htmlspecialchars($check_in) ?>" min="<?= $min_checkin_date ?>" required>
                </div>
                <div class="room-details-form-group">
                    <label for="modal_check_out_<?= $id ?>">Check-out Date:</label>
                    <input type="date" id="modal_check_out_<?= $id ?>" name="check_out" class="room-details-input" value="<?= htmlspecialchars($check_out) ?>" min="<?= $min_checkin_date ?>" required>
                </div>
                <div class="room-details-form-group">
                    <label for="modal_guests_<?= $id ?>">Number of Guests:</label>
                    <input type="number" id="modal_guests_<?= $id ?>" name="guests" class="room-details-input" value="<?= htmlspecialchars($guests > 0 ? $guests : $room['guest_range'][0]) ?>" min="<?= $room['guest_range'][0] ?>" max="<?= $room['guest_range'][1] ?>" required>
                </div>
                <button type="submit" class="room-details-book-btn">Proceed to Booking</button>
            </form>
        </div>
    </div>
  </section>
  <?php $is_reverse = !$is_reverse; // Toggle for next room ?>
<?php endforeach; ?>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
        const roomCards = document.querySelectorAll('.room-card');
        const roomsSection = document.querySelector('.rooms-section');
        const roomDetailsSection = document.getElementById('room-details');

        // Modal functionality - REMOVED, now handled by room_availability.js
        /*
        document.querySelectorAll('.pay-btn').forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.dataset.roomId;
                const modal = document.getElementById(`roomDetailsModal_${roomId}`);
                if (modal) {
                    modal.style.display = 'flex'; // Show modal
                }
            });
        });
        */

        document.querySelectorAll('.close-button').forEach(button => {
      button.addEventListener('click', function() {
                const modalId = this.id.replace('closeModal_', 'roomDetailsModal_');
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none'; // Hide modal
              }
          });
        });

        // Close modal if clicked outside
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.room-details-modal').forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Update check-out min date in modal when check-in date changes
        document.querySelectorAll('[id^="modal_check_in_"]').forEach(checkInInput => {
            checkInInput.addEventListener('change', function() {
                const roomId = this.id.replace('modal_check_in_', '');
                const checkOutInput = document.getElementById(`modal_check_out_${roomId}`);
                const checkInDate = new Date(this.value);
                const minCheckOut = new Date(checkInDate);
                minCheckOut.setDate(checkInDate.getDate() + 1); // Minimum 1 night stay
                const minCheckOutStr = minCheckOut.toISOString().split('T')[0];
                checkOutInput.min = minCheckOutStr;
                if (checkOutInput.value < minCheckOutStr) {
                    checkOutInput.value = minCheckOutStr;
                }
            });
        });

        // Room card click logic (this might be vestigial, but kept for now)
        const roomDetails = {
            twin: {
                title: 'Twin Room',
                desc: 'Good for 2 Pax. Spacious comfort for friends or colleagues. Includes 2 single beds, air conditioning, WiFi, and ensuite bathroom.',
                img: '',
            },
            family: {
                title: 'Family Room',
                desc: 'Good for 3 to 5 Pax. Perfect for family getaways and bonding. Multiple beds, extra space, and family-friendly amenities.',
                img: '',
            },
            harmony: {
                title: 'Harmony Room',
                desc: 'Good for 6 to 10 Pax. Ultimate relaxation for large groups. Elegant design, premium amenities, and lots of space.',
                img: '',
            }
        };

        roomCards.forEach(card => {
            card.addEventListener('click', function() {
                const room = this.dataset.room;
                if (roomDetails[room]) {
                    // This is the old "room details" view, which is no longer explicitly used
                    // Instead, the form directly submits to booking.
                    // This block might be vestigial if the "Book Now" buttons are handled by the forms.
                    // For now, keep it as is, but it does not interfere with the booking form.
                    roomsSection.style.display = 'none';
                    roomDetailsSection.style.display = 'block';
                    roomDetailsSection.innerHTML = `
                      <div class="room-details-card">
                        <div class="room-details-img" style="background:#75491b;height:260px;border-radius:10px 10px 0 0;"></div>
                        <h2 style="margin:24px 0 12px 0;">${roomDetails[room].title}</h2>
                        <p style="font-size:18px;color:#444;">${roomDetails[room].desc}</p>
                        <button class="back-to-rooms-btn" style="margin-top:30px;padding:10px 30px;">Back to Rooms</button>
                      </div>
                    `;
                }
      });
  });
        // Back to rooms
        roomDetailsSection.addEventListener('click', function(e) {
            if (e.target.classList.contains('back-to-rooms-btn')) {
                roomDetailsSection.style.display = 'none';
                roomsSection.style.display = 'block';
        }
    });
});
</script>
<script src="/kathelia-suites/src/js/room_availability.js"></script>
<?php require_once __DIR__ . '/../src/php/templates/footer.php'; ?>  
