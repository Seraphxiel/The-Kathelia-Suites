<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

// -- 1. AUTH CHECK ----------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ../public/login.php?redirect=' . $redirect_url);
    exit;
}

$user_id = $_GET['id'] ?? null;
$user = null;
$message = '';

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, username, email, role FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = '<p class="error-message">User not found.</p>';
            $user_id = null; // Invalidate user_id if not found
        }
    } catch (PDOException $e) {
        $message = '<p class="error-message">Error fetching user details: ' . $e->getMessage() . '</p>';
        $user_id = null;
    }
} else {
    $message = '<p class="error-message">No user ID provided.</p>';
}

// Handle form submission for updating user
if (isset($_POST['update_user']) && $user_id) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($role)) {
        $message = '<p class="error-message">All fields are required.</p>';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<p class="error-message">Invalid email format.</p>';
    } else {
        try {
            // Check for duplicate username or email (excluding current user)
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id");
            $checkStmt->execute([':username' => $username, ':email' => $email, ':user_id' => $user_id]);
            if ($checkStmt->fetchColumn() > 0) {
                $message = '<p class="error-message">Username or Email already exists for another user.</p>';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, username = :username, email = :email, role = :role WHERE user_id = :user_id");
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':username' => $username,
                    ':email' => $email,
                    ':role' => $role,
                    ':user_id' => $user_id
                ]);
                $message = '<p class="success-message">User updated successfully!</p>';
                // Re-fetch user data to display updated values
                $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, username, email, role FROM users WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $message = '<p class="error-message">Error updating user: ' . $e->getMessage() . '</p>';
        }
    }
}

include 'includes/header.php';
?>

<div class="main-content">
    <h1>Edit User</h1>

    <?php echo $message; // Display messages here ?>

    <?php if ($user): ?>
    <div class="form-container">
        <form action="edit_user.php?id=<?php echo htmlspecialchars($user['user_id']); ?>" method="POST">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="update_user" value="Update User" class="action-button">
                <a href="users.php" class="action-button cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 