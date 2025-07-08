<?php
// public/profile.php
require_once __DIR__ . '/../src/php/db_connect.php';
requireLogin();

require_once __DIR__ . '/../src/php/templates/header.php';

// Fetch user data (for pre-filling form)
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        try {
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?";
            $params = [$first_name, $last_name, $email];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password_hash = ?"; // Corrected column name
                $params[] = $hashed_password;
            }

            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = "Profile updated successfully!";
            // Refresh user data after update
            $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
        }
    }
}

?>

<main class="profile-main">
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <?php if (!empty($message)): ?>
            <p class="message" style="color: <?= strpos($message, 'Error') !== false ? 'red' : 'green' ?>;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form action="profile.php" method="POST">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">New Password (leave blank to keep current):</label>
                <input type="password" id="password" name="password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit" class="btn-update">Update Profile</button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../src/php/templates/footer.php'; ?>

<style>
.profile-main {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 4rem 2rem;
    background: #4e2e1b;
    min-height: calc(100vh - 120px); /* Adjust based on header/footer height */
    color: #f8f1e5;
}

.profile-container {
    background-color: rgba(26, 26, 26, 0.8);
    padding: 40px;
    border-radius: 18px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
    width: 100%;
    max-width: 600px;
    color: #f8f1e5;
    border: 1px solid rgba(212, 175, 55, 0.3);
    backdrop-filter: blur(10px);
    text-align: center; /* Center content */
}

.profile-container h2 {
    color: #d4af37;
    margin-bottom: 30px;
    font-size: 2.5em;
    font-family: 'Playfair Display', serif;
}

.form-group {
    margin-bottom: 20px;
    text-align: left;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 1.1em;
    color: #d4af37;
    font-family: 'Cormorant Garamond', serif;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group textarea {
    width: calc(100% - 20px); /* Account for padding */
    padding: 10px;
    border: 1px solid #d4af37;
    border-radius: 5px;
    background-color: #333;
    color: #f8f1e5;
    font-size: 1em;
    font-family: 'Cormorant Garamond', serif;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #e0b84c;
    box-shadow: 0 0 5px rgba(212, 175, 55, 0.5);
}

.btn-update {
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

.btn-update:hover {
    background-color: #e0b84c;
    transform: translateY(-2px);
}

.btn-update:active {
    transform: translateY(0);
}

.message {
    margin-bottom: 20px;
    font-weight: bold;
}
</style>
