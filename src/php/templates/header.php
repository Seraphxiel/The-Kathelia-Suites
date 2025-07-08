<?php
// src/php/templates/header.php

// db_connect.php does session_start() and defines $pdo and helper functions
require_once __DIR__ . '/../db_connect.php';

$current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Kathelia Suites</title>

  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?
      family=Playfair+Display:wght@400;500;600&
      family=Cormorant+Garamond:wght@300;400;500&
      display=swap"
    rel="stylesheet"
  >

  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <!-- Global styles -->
  <link rel="stylesheet" href="/kathelia-suites/src/php/css/style.css">

  <!-- Home-only overrides -->
  <?php if ($current_page === 'home.php'): ?>
    <link rel="stylesheet" href="/kathelia-suites/public/assets/css/style.css">
  <?php endif; ?>

  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  >

  <!-- Main JS -->
  <script src="/kathelia-suites/src/js/app.js" defer></script>

  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <style>
    .nav-list li a.active { color: #d4af37; }
    .nav-list li a.active::after { width: 100%; }

    /* login/logout icon only */
    .icon-only a,
    .logout-only a {
      padding: 0.5rem;
      display: flex;
      align-items: center;
      color: #f8f8f8;
    }
    .logout-only a:hover {
      color: #d4af37;
    }
  </style>
</head>
<body>
  <header class="main-header">
    <div class="header-container">
      <div class="logo">
        <a href="/kathelia-suites/public/index.php">Kathelia Suites</a>
      </div>
      <nav class="main-nav">
        <ul class="nav-list">
          <li>
            <a href="/kathelia-suites/public/home.php"
               <?= in_array($current_page, ['index.php','home.php']) ? 'class="active"' : '' ?>>
              Home
            </a>
          </li>
          <li>
            <a href="/kathelia-suites/public/rooms.php"
               <?= $current_page === 'rooms.php' ? 'class="active"' : '' ?>>
              Our Rooms
            </a>
          </li>

          <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- not logged in: login icon -->
            <li class="icon-only">
              <a href="/kathelia-suites/public/login.php" title="Log in">
                <i class="fa-regular fa-user"></i>
              </a>
            </li>
          <?php else: ?>
            <!-- logged in: Booking and Reservations -->
            <li>
              <a href="/kathelia-suites/public/booking.php"
                 <?= $current_page === 'booking.php' ? 'class="active"' : '' ?>>
                Bookings
              </a>
            </li>
            <li>
              <a href="/kathelia-suites/public/reservations.php"
                 <?= $current_page === 'reservations.php' ? 'class="active"' : '' ?>>
                My Reservations
              </a>
            </li>
            <!-- Profile icon -->
            <li class="icon-only">
              <a href="/kathelia-suites/public/profile.php" title="My Profile"
                 <?= $current_page === 'profile.php' ? 'class="active"' : '' ?>>
                <i class="fa-regular fa-user-circle"></i>
              </a>
            </li>
            <!-- Message icon for notifications/receipts -->
            <li class="icon-only">
              <a href="/kathelia-suites/public/messages.php" title="Notifications/Receipts">
                <i class="fa-regular fa-bell"></i>
              </a>
            </li>
            <!-- then only logout icon -->
            <li class="icon-only logout-only">
              <a href="/kathelia-suites/public/logout.php" title="Log out">
                <i class="fa-solid fa-sign-out-alt"></i>
              </a>
            </li>
          <?php endif; ?>

        </ul>
      </nav>
    </div>
  </header>

  <main>
