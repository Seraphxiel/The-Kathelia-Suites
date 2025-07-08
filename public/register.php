<?php
// public/register.php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

$error = '';
$first = '';
$last  = '';
$user  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect & trim
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name']  ?? '');
    $user  = trim($_POST['username']   ?? '');
    $email = trim($_POST['email']      ?? '');
    $pw    = $_POST['password']             ?? '';
    $pw2   = $_POST['confirm_password']     ?? '';

    // basic validation
    if (!$first || !$last || !$user || !$email || !$pw || !$pw2) {
        $error = 'All fields are required.';
    } elseif ($pw !== $pw2) {
        $error = 'Passwords do not match.';
    } else {
        // check uniqueness
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
              FROM users 
             WHERE username = ? 
                OR email    = ?
        ");
        $stmt->execute([$user, $email]);

        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already taken.';
        } else {
            // insert new user
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("
                INSERT INTO users 
                  (first_name, last_name, username, email, password_hash, role) 
                VALUES 
                  (?, ?, ?, ?, ?, 'client')
            ");
            $ins->execute([$first, $last, $user, $email, $hash]);

            // log them in
            $newId = $pdo->lastInsertId();
            $_SESSION['user_id']  = $newId;
            $_SESSION['username'] = $user;
            $_SESSION['role']     = 'client';

            header('Location: profile.php');
            exit;
        }
    }
}
?>

<?php include __DIR__ . '/../src/php/templates/header.php'; ?>

<main>
  <div class="modal-overlay">
    <div class="modal-box">
      <!-- Close back to Home -->
      <button class="modal-close" onclick="window.location='home.php'">&times;</button>

      <h2>Create an Account</h2>

      <?php if ($error): ?>
        <p class="error" style="color:#dc3545; text-align:center;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <form method="post" action="">
        <label for="first_name">First Name</label>
        <input
          type="text"
          name="first_name"
          id="first_name"
          value="<?= htmlspecialchars($first) ?>"
          required
        >

        <label for="last_name">Last Name</label>
        <input
          type="text"
          name="last_name"
          id="last_name"
          value="<?= htmlspecialchars($last) ?>"
          required
        >

        <label for="username">Username</label>
        <input
          type="text"
          name="username"
          id="username"
          value="<?= htmlspecialchars($user) ?>"
          required
        >

        <label for="email">Email</label>
        <input
          type="email"
          name="email"
          id="email"
          value="<?= htmlspecialchars($email) ?>"
          required
        >

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input
          type="password"
          name="confirm_password"
          id="confirm_password"
          required
        >

        <button type="submit">Register</button>
      </form>

      <p class="register-prompt">
        Already have an account?
        <a href="login.php">Log in here</a>
      </p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../src/php/templates/footer.php'; ?>
