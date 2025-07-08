<?php
// src/php/db_connect.php

// Database credentials
$db_host = '127.0.0.1';
$db_port = '3307';
$db_name = 'kathelia_suites';
$db_user = 'root';
$db_pass = ''; // no password

// DSN (including charset)
$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create the PDO instance
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    echo 'Database Connection Failed: ' . $e->getMessage();
    exit;
}

// choose session name based on URI
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/admin/') !== false) {
    session_name('ADMIN_SESSION');
} else {
    session_name('CLIENT_SESSION');
}

// Only start session if one hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// User authentication functions
function registerUser($first_name, $last_name, $username, $email, $password) {
    global $pdo;
    
    // Check if email or username already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    $result = $stmt->rowCount();
    
    if ($result > 0) {
        return false; // Email or username already exists
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password_hash) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$first_name, $last_name, $username, $email, $password_hash]);
    
    return $pdo->lastInsertId();
}

function loginUser($identifier, $password) {
    global $pdo;

    error_log("Attempting login for identifier: " . $identifier);

    // Check if the identifier is an email or username
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, username, email, password_hash, role FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$identifier, $identifier]);
    $result = $stmt->rowCount();

    error_log("Query row count: " . $result);

    if ($result === 1) {
        $user = $stmt->fetch();
        error_log("User found. Stored password hash: " . $user['password_hash'] . ", Role: " . $user['role']);
        if (password_verify($password, $user['password_hash'])) {
            error_log("Password verified successfully!");
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email']; // Use the email from the fetched user data
            $_SESSION['role'] = $user['role'];

            return true;
        } else {
            error_log("Password verification failed for identifier: " . $identifier);
        }
    } else {
        error_log("User not found or multiple users found for identifier: " . $identifier);
    }

    return false;
}

function logout() {
    session_destroy();
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, username, email, role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->rowCount();
    
    if ($result === 1) {
        return $stmt->fetch();
    }
    
    return null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /kathelia-suites/public/login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: /kathelia-suites/public/home.php");
        exit();
    }
}

function updateUserProfile($user_id, $first_name, $last_name, $username, $email) {
    global $pdo;
    
    // Check if email or username already exists for other users
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE (email = ? OR username = ?) AND user_id != ?");
    $stmt->execute([$email, $username, $user_id]);
    $result = $stmt->rowCount();
    
    if ($result > 0) {
        return false; // Email or username already exists
    }
    
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE user_id = ?");
    $stmt->execute([$first_name, $last_name, $username, $email, $user_id]);
    
    return true;
}

function updateUserPassword($user_id, $current_password, $new_password) {
    global $pdo;
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->rowCount();
    
    if ($result === 1) {
        $user = $stmt->fetch();
        if (password_verify($current_password, $user['password_hash'])) {
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$password_hash, $user_id]);
            
            return true;
        }
    }
    
    return false;
}

function getExtraPrices() {
    global $pdo;
    $stmt = $pdo->query("SELECT name, rate FROM extras");
    $prices = [];
    while ($row = $stmt->fetch()) {
        $prices[$row['name']] = $row['rate'];
    }
    return $prices;
}