<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

// Debugging: Log session variables and ID
error_log("SESSION DUMP (users.php): " . print_r($_SESSION, true));
error_log("Session ID (users.php): " . session_id());

// -- 1. AUTH CHECK ----------------------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ../public/login.php?redirect=' . $redirect_url);
    exit;
}

$message = '';

// Handle Add New User submission
if (isset($_POST['add_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $message = '<p class="error-message">All fields are required.</p>';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<p class="error-message">Invalid email format.</p>';
    } else {
        try {
            // Check if username or email already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $checkStmt->execute([':username' => $username, ':email' => $email]);
            if ($checkStmt->fetchColumn() > 0) {
                $message = '<p class="error-message">Username or Email already exists.</p>';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password_hash, role) VALUES (:first_name, :last_name, :username, :email, :password_hash, :role)");
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':username' => $username,
                    ':email' => $email,
                    ':password_hash' => $hashed_password,
                    ':role' => $role
                ]);
                $_SESSION['message'] = '<p class="success-message">User added successfully!</p>'; // Store message in session
                
                // Add JavaScript to clear the form fields immediately
                echo '<script>';
                echo 'document.getElementById(\"add_user_form\").reset();'; // Clear the form
                echo '</script>';
                
                header('Location: users.php'); // Redirect to prevent re-submission
                exit;
            }
        } catch (PDOException $e) {
            $message = '<p class="error-message">Error adding user: ' . $e->getMessage() . '</p>';
        }
    }
}

// Check for and display messages from session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}

$users = [];

try {
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, username, email, role FROM users ORDER BY user_id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p class="error-message">Error fetching users: ' . $e->getMessage() . '</p>';
}

?>

<?php include 'includes/header.php'; ?>

<div class="main-content">
    <h1>Users Management</h1>

    <?php echo $message; // Display messages here ?>

    <div id="js-message-container"></div> <!-- New div for JavaScript messages -->

    <div class="form-container">
        <h2>Add New User</h2>
        <form action="users.php" method="POST" id="add_user_form" autocomplete="off">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="add_user" value="Add User" class="action-button">
            </div>
        </form>
    </div>

    <div class="bookings-table-container"> <!-- Reusing table container style -->
        <h2>Existing Users</h2>
        <table class="bookings-table"> <!-- Reusing table style -->
            <thead>
                <tr>
                    <th>USER ID</th>
                    <th>NAME</th>
                    <th>USERNAME</th>
                    <th>EMAIL</th>
                    <th>ROLE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="action-button edit-btn">Edit</a>
                                    <button class="action-button delete-btn" data-id="<?php echo $user['user_id']; ?>">Delete</button>
                                <?php else: ?>
                                    <!-- Leave blank for client users -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const jsMessageContainer = document.getElementById('js-message-container');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.id;
            const userName = this.closest('tr').querySelector('td:nth-child(2)').textContent; // Get user name for confirmation

            if (confirm(`Are you sure you want to delete user: ${userName}?`)) {
                fetch('handle_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_user&user_id=${userId}`,
                })
                .then(response => {
                    // Log the raw text of the response
                    return response.text().then(text => {
                        console.log('Raw server response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            throw new Error('Invalid JSON response from server.');
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed server response:', data); // Changed from 'Server response:'
                    if (data.success) {
                        // Remove the row from the table
                        this.closest('tr').remove();
                        jsMessageContainer.innerHTML = `<p class="success-message">${data.message}</p>`;
                    } else {
                        jsMessageContainer.innerHTML = `<p class="error-message">${data.message}</p>`;
                    }
                    // Clear message after a few seconds
                    setTimeout(() => {
                        jsMessageContainer.innerHTML = '';
                    }, 5000);
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    jsMessageContainer.innerHTML = `<p class="error-message">An error occurred while deleting the user.</p>`;
                    setTimeout(() => {
                        jsMessageContainer.innerHTML = '';
                    }, 5000);
                });
            }
        });
    });
});
</script>