<?php
// admin/includes/auth.php

// Ensure session is started
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../../src/php/db_connect.php';

// Debugging: Log session state before authentication check
error_log("Auth check: SESSION_ID=" . session_id() . ", user_id=" . ($_SESSION['user_id'] ?? 'N/A') . ", role=" . ($_SESSION['role'] ?? 'N/A'));

// Check for logged-in user with admin role
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    // Debugging: Log redirection reason
    error_log("Auth check failed. Redirecting to login. User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ", Role: " . ($_SESSION['role'] ?? 'N/A'));

    // Not an admin → send to admin login with current page as redirect
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: /kathelia-suites/admin/login.php?redirect=' . $redirect_url);
    exit;
}
