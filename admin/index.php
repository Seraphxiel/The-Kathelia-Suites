<?php
require_once __DIR__ . '/../src/php/db_connect.php';

// Include the admin header
include __DIR__ . '/includes/header.php';
?>

<header class="admin-header">
  <h1>Admin Panel</h1>
  <div class="user-info">
    <?php if (isAdmin()): ?>
      <span>Welcome, <?= htmlspecialchars($_SESSION['first_name'] ?? '') ?></span>
      <a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i>Logout</a>
    <?php else: ?>
      <!-- Login button will be displayed in the main content area -->
    <?php endif; ?>
  </div>
</header>

<div class="admin-wrapper">
    <div class="sidebar">
        <!-- Sidebar content from admin/includes/header.php -->
    </div>
    <div class="main-content">
        <?php if (isAdmin()): ?>
            <h2>Welcome to the Admin Dashboard</h2>
            <p>This is the main content area for the admin panel.</p>
            <!-- Other dashboard content will go here -->
        <?php else: ?>
            <div class="unauthenticated-content">
                <h2>Welcome to the Kathelia Suites Admin Panel</h2>
                <p>Please log in to manage your hotel operations.</p>
                <a href="login.php" class="login-button">Admin Login</a>
            </div>
        <?php endif; ?>

<?php
// Include the admin footer
include __DIR__ . '/includes/footer.php';
?>
