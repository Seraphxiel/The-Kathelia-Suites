<?php
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kathelia Suites</title>
    <link rel="stylesheet" href="/kathelia-suites/admin/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <h2>Kathelia Admin</h2>
            <ul>
                <li><a href="/kathelia-suites/admin/dashboard.php" <?= ($current_page === 'dashboard.php') ? 'class="active"' : '' ?>>Dashboard</a></li>
                <li><a href="/kathelia-suites/admin/bookings.php" <?= ($current_page === 'bookings.php') ? 'class="active"' : '' ?>>Bookings</a></li>
                <li><a href="/kathelia-suites/admin/payments.php" <?= ($current_page === 'payments.php') ? 'class="active"' : '' ?>>Payments</a></li>
                <li><a href="/kathelia-suites/admin/room_types.php" <?= ($current_page === 'room_types.php') ? 'class="active"' : '' ?>>Rooms & Prices</a></li>
                <li><a href="/kathelia-suites/admin/extras.php" <?= ($current_page === 'extras.php') ? 'class="active"' : '' ?>>Extras</a></li>
                <li><a href="/kathelia-suites/admin/users.php" <?= ($current_page === 'users.php') ? 'class="active"' : '' ?>>Users</a></li>
                <li><a href="/kathelia-suites/admin/backup_restore.php" <?= ($current_page === 'backup_restore.php') ? 'class="active"' : '' ?>>Backup &amp; Restore</a></li>
                <li><a href="/kathelia-suites/admin/logout.php" <?= ($current_page === 'logout.php') ? 'class="active"' : '' ?>>Logout</a></li>
            </ul>
        </div>
        <div class="main-content">
