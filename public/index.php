<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
require_once __DIR__ . '/../src/php/templates/header.php';

?>

<!-- Preloader for black flash on load -->
<div id="preloader"></div>

<section class="hero" id="hero">
  <div class="hero-overlay"></div>
  <!-- Hero copy -->
  <div class="hero-content">
    <h1 class="hero-title">Welcome to Kathelia Suites</h1>
    <div class="hero-text">
      <div class="hero-section" style="display: none;">
        <h2 class="hero-subtitle"></h2>
        <p class="hero-desc"></p>
      </div>
    </div>
    <a href="home.php" class="btn hero-btn">Explore</a>
  </div>

  <!-- Polaroid thumbnails (data-image → background target) -->
  <div class="hero-carousel">
    <div id="carousel-images" class="carousel-images"></div>
  </div>
</section>

<?php
require_once __DIR__ . '/../src/php/templates/footer.php';
