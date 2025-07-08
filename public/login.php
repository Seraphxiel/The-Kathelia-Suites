<?php
// public/login.php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

// After login, return here or to a default page
$redirect = $_GET['redirect'] ?? 'home.php';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // Use the loginUser function from db_connect.php
        if (loginUser($username, $password)) {
            // Debugging output
            error_log("Login successful. User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", Username: " . ($_SESSION['username'] ?? 'N/A') . ", Role: " . ($_SESSION['role'] ?? 'N/A'));
            error_log("Initial Redirect target: " . $redirect);

            // Check for pending booking details after successful login
            error_log("Checking for pending_booking_details. Isset: " . (isset($_SESSION['pending_booking_details']) ? 'true' : 'false'));
            if (isset($_SESSION['pending_booking_details'])) {
                error_log("pending_booking_details found! Redirecting to booking.php");
                $redirect = '/kathelia-suites/public/booking.php';
                unset($_SESSION['pending_booking_details']); // Clear the session variable
                header('Location: ' . $redirect);
                exit;
            }

            // Check for pending availability parameters after successful login
            error_log("Checking for pending_availability_params. Isset: " . (isset($_SESSION['pending_availability_params']) ? 'true' : 'false'));
            if (isset($_SESSION['pending_availability_params'])) {
                error_log("pending_availability_params found! Redirecting to rooms.php with params.");
                $params = $_SESSION['pending_availability_params'];
                $redirect_url = '/kathelia-suites/public/rooms.php?check_in=' . urlencode($params['check_in']) . '&check_out=' . urlencode($params['check_out']) . '&guests=' . urlencode($params['guests']);
                unset($_SESSION['pending_availability_params']); // Clear the session variable
                header('Location: ' . $redirect_url);
                exit;
            }

            error_log("Final Redirect value before header: " . $redirect);

            // Check the role from the session after successful login
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                if (strpos($redirect, 'admin/') !== false) {
                    header("Location: $redirect");
                } else {
                    header("Location: /kathelia-suites/admin/dashboard.php");
                }
            } else {
                header("Location: $redirect");
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>

<?php include __DIR__ . '/../src/php/templates/header.php'; ?>

<main>
  <div class="modal-overlay">
    <div class="modal-box">
      <!-- Close button -->
      <button class="modal-close" onclick="window.location='home.php'">&times;</button>

      <h2>Login to Your Account</h2>

      <?php if ($error): ?>
        <p class="error" style="color:#dc3545; text-align:center;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <form method="post" action="">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Log In</button>
      </form>

      <p class="register-prompt">
        Don't have an account?
        <a href="register.php">Register now</a>
      </p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../src/php/templates/footer.php'; ?>
