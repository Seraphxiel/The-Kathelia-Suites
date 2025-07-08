<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
require_once __DIR__ . '/../src/php/templates/header.php';

// Get today's date and format it
$today = date('Y-m-d');
$min_checkin = date('Y-m-d', strtotime('+2 days')); // Minimum check-in is 2 days after today
?>

<section class="home-hero" id="home">
  <div class="home-hero-bg"></div>
  <div class="home-hero-content">
    <h1 class="home-hero-title">KATHELIA SUITES</h1>
    <p class="home-hero-subtitle">
      ENJOY THE ONE OF THE MOST HIGHEST<br>
      RATE SUITES IN THE PHILIPPINES
    </p>
  </div>

  <!-- booking-bar form: added action & method, renamed inputs -->
  <?php // if (isLoggedIn()): ?> // Reverted: Removed isLoggedIn() check
  <form 
    class="booking-bar" 
    id="bookingForm"
    action="/kathelia-suites/public/rooms.php" 
    method="get"
  >
    <div class="booking-field">
      <label for="check_in">Check In</label>
      <input 
        type="date" 
        id="check_in" 
        name="check_in" 
        min="<?= $min_checkin ?>"
        required
      >
    </div>
    <div class="booking-field">
      <label for="check_out">Check Out</label>
      <input 
        type="date" 
        id="check_out" 
        name="check_out" 
        required
      >
    </div>
    <div class="booking-field">
      <label for="guests">Guest</label>
      <input 
        type="number" 
        id="guests" 
        name="guests" 
        min="1" 
        max="10" 
        value="1" 
        required
      >
    </div>
    <button type="submit" class="booking-btn">
      Check Availability
    </button>
  </form>
  <?php // endif; ?> // Reverted: Removed isLoggedIn() check
</section>

<!-- Rooms Section -->
<section class="rooms-section" id="rooms">
  <h2 class="section-title">Our Rooms</h2>
  <div class="rooms-cards">
    <div class="room-card" data-room="twin">
      <div class="room-card-img">
        <img src="/kathelia-suites/assets/images/twin-room.png" alt="Twin Room">
      </div>
      <div class="room-card-info">
        <h3 class="room-card-title">Twin Room</h3>
      </div>
    </div>
    <div class="room-card" data-room="family">
      <div class="room-card-img">
        <img src="/kathelia-suites/assets/images/family-room.png" alt="Family Room">
      </div>
      <div class="room-card-info">
        <h3 class="room-card-title">Family Room</h3>
      </div>
    </div>
    <div class="room-card" data-room="harmony">
      <div class="room-card-img">
        <img src="/kathelia-suites/assets/images/harmony-room.png" alt="Harmony Room">
      </div>
      <div class="room-card-info">
        <h3 class="room-card-title">Harmony Room</h3>
      </div>
    </div>
  </div>
</section>

<!-- Room Details Section (hidden by default, shown when card is clicked) -->
<section class="room-details-section" id="room-details" style="display:none;"></section>

<!-- Amenities Section -->
<section class="amenities-section" id="amenities">
  <h2 class="section-title">Amenities</h2>
  <div class="amenities-cards">
    <div class="amenity-card">
      <div class="amenity-card-img">
        <img src="/kathelia-suites/assets/images/swimming-pool.png" alt="Swimming Pool">
      </div>
      <div class="amenity-card-info">
        <h3>Swimming Pool</h3>
        <div class="amenity-desc">
          Relax and refresh in our luxurious pool.
        </div>
      </div>
    </div>
    <div class="amenity-card">
      <div class="amenity-card-img">
        <img src="/kathelia-suites/assets/images/basketball.png" alt="Basketball">
      </div>
      <div class="amenity-card-info">
        <h3>Basketball</h3>
        <div class="amenity-desc">
          Enjoy a game on our basketball court.
        </div>
      </div>
    </div>
    <div class="amenity-card">
      <div class="amenity-card-img">
        <img src="/kathelia-suites/assets/images/gym.png" alt="Gym">
      </div>
      <div class="amenity-card-info">
        <h3>Gym</h3>
        <div class="amenity-desc">
          Stay fit with our modern gym facilities.
        </div>
      </div>
    </div>
    <div class="amenity-card">
      <div class="amenity-card-img">
        <img src="/kathelia-suites/assets/images/billiards.png" alt="Billiards">
      </div>
      <div class="amenity-card-info">
        <h3>Billiards</h3>
        <div class="amenity-desc">
          Challenge friends in our billiards area.
        </div>
      </div>
    </div>
  </div>
</section>

<!-- About Us Section -->
<section class="about-section" id="about-us">
  <h2 class="section-title">Welcome to Kathelia Suites</h2>
  <div class="about-block">
    <p>
      Your home away from home. Nestled in a relaxing environment, Kathelia Suites offers thoughtfully designed accommodations ideal for couples, families, and large groups. Whether you're visiting for a quick weekend stay or an extended retreat, our goal is to provide a smooth, memorable, and comfortable experience from check-in to check-out.
    </p>
    <p>
      Each room is equipped with hotel-style comforts: air-conditioning, high-speed WiFi, smart TVs with Netflix, heated showers, and a complimentary breakfast served daily from 6–10 AM. Guests can also enjoy our shared outdoor amenities including a swimming pool, children's playground, basketball court, billiards area, and a chill bar for drinks and downtime.
    </p>
    <p>
      With attentive service, spacious suites, and thoughtful extras, Kathelia Suites is a place to rest, reconnect, and recharge—whether you're here to relax, celebrate, or simply escape the city.
    </p>
  </div>
  <div class="about-block">
    <h3>Our Story</h3>
    <p>
      Kathelia Suites was built on friendship, dreams, and a shared passion for hospitality. Founded by four schoolmates and best friends, what started as casual conversations soon turned into a real place—carefully planned, personally designed, and filled with heart.
    </p>
    <p>
      We wanted to create more than just rooms. We wanted a space where memories are made—where guests feel both comfortable and cared for, just like we would with family. Every detail, from the room layout to the breakfast menu, was chosen with that in mind.
    </p>
    <p>
      Kathelia Suites is our shared dream made real—and now, it's yours to enjoy too.
    </p>
  </div>
  <div class="about-block">
    <h3>Meet the Team</h3>
    <p>
      Behind Kathelia Suites are four passionate women who turned their friendship and shared vision into something special. We're schoolmates, best friends, and now co-founders—each bringing something unique to the table, from creativity and hospitality to design and operations. What unites us is our love for creating comfortable spaces, our attention to detail, and our desire to make every guest feel truly welcome.
    </p>
    <p>
      We believe that a great stay starts with genuine care—and that's exactly what we aim to provide, every single day.
    </p>
    <p><b>Kathelia Suites Team</b><br>
    Together, we are Kathelia—a blend of names, a bond of friendship, and a shared dream to give you a place to rest, reconnect, and recharge.</p>
    <img src="/kathelia-suites/assets/images/team_kathelia.jpg" alt="Kathelia Suites Team" style="display: block; margin: 20px auto; max-width: 500px; height: auto; border-radius: 8px;">
  </div>
  <div class="about-block">
    <h3>Our Mission</h3>
    <p>
      To provide every guest with a warm, welcoming stay through clean, comfortable spaces, reliable service, and thoughtful amenities—creating an experience that feels both effortless and heartfelt.
    </p>
  </div>
  <div class="about-block">
    <h3>Our Vision</h3>
    <p>
      To be known as one of the most trusted local destinations for families and groups, where comfort, connection, and care are always part of the stay.
    </p>
  </div>
  <div class="about-block">
    <h3>Core Values</h3>
    <ul>
      <li><b>Hospitality First</b> – Every guest is treated like family</li>
      <li><b>Cleanliness & Comfort</b> – We prioritize a tidy, well-maintained space for every stay</li>
      <li><b>Reliability</b> – Consistent service, clear communication, and availability when needed</li>
      <li><b>Local Warmth</b> – Embracing genuine Filipino hospitality in everything we do</li>
    </ul>
  </div>
  <div class="about-block">
    <h3>Why Choose Us</h3>
    <ul>
      <li>Cozy, well-equipped rooms for couples, families, and large groups</li>
      <li>Free breakfast and daily essentials included</li>
      <li>Access to fun outdoor amenities (pool, playground, billiards, basketball, bar)</li>
      <li>Quiet and secure environment, perfect for relaxing or celebrating</li>
      <li>Flexible stay options with hourly or daily extensions available</li>
    </ul>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    
    // Set initial check-out min date (tomorrow)
    function updateCheckOutMin() {
        const checkInDate = new Date(checkInInput.value);
        const minCheckOut = new Date(checkInDate);
        minCheckOut.setDate(checkInDate.getDate() + 1); // Minimum 1 night stay
        
        // Format date to YYYY-MM-DD
        const minCheckOutStr = minCheckOut.toISOString().split('T')[0];
        checkOutInput.min = minCheckOutStr;
        
        // If current check-out date is before new minimum, update it
        if (checkOutInput.value && new Date(checkOutInput.value) < minCheckOut) {
            checkOutInput.value = minCheckOutStr;
        }
    }

    // Update check-out minimum when check-in date changes
    checkInInput.addEventListener('change', updateCheckOutMin);
    
    // Initial setup
    updateCheckOutMin();
});
</script>

<?php
require_once __DIR__ . '/../src/php/templates/footer.php';
?>
