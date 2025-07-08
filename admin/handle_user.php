<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Debugging: Log session variables
error_log("SESSION DUMP (handle_user.php): " . print_r($_SESSION, true));

// Ensure user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_user':
            $user_id = $_POST['user_id'] ?? null;

            if (empty($user_id)) {
                $response['message'] = 'User ID is required.';
                echo json_encode($response);
                exit;
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
                $stmt->execute([':user_id' => $user_id]);

                if ($stmt->rowCount() > 0) {
                    $response['success'] = true;
                    $response['message'] = 'User deleted successfully.';
                } else {
                    $response['message'] = 'User not found or could not be deleted.';
                }
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
            break;
        default:
            $response['message'] = 'Invalid action.';
            break;
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response); 