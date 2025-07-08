<?php
// File: /kathelia-suites/admin/login.php
require_once __DIR__ . '/../src/php/db_connect.php';

// Debugging: Log session state at login page start
error_log("Login page: SESSION_ID=" . session_id() . ", user_id=" . ($_SESSION['user_id'] ?? 'N/A') . ", role=" . ($_SESSION['role'] ?? 'N/A') . ", Redirect: " . ($redirect ?? 'N/A'));

// decide where to go after login
$redirect = $_GET['redirect'] ?? 'dashboard.php';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']  ?? '');
    $password =          $_POST['password'] ?? '';

    if ($username && $password && loginUser($username, $password)) {
        // Debugging: Log successful login
        error_log("Login successful. User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", Role: " . ($_SESSION['role'] ?? 'N/A') . ", Redirecting to: " . $redirect);

        if ($_SESSION['role'] === 'admin') {
            header("Location: $redirect");
            exit;
        } else {
            // not an admin; bail back to client-side
            error_log("Login successful, but not admin. Redirecting to public login.");
            header("Location: /kathelia-suites/public/login.php");
            exit;
        }
    }
    error_log("Login failed for username: " . $username . ". Error: " . $error);
    $error = 'Invalid username or password.';
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="login-container">
  <form method="post" class="login-form">
    <h2>Admin Login</h2>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <label>Username<br><input name="username" type="text" required></label><br>
    <label>Password<br><input type="password" name="password" required></label><br>
    <button type="submit">Log In</button>
  </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?> 