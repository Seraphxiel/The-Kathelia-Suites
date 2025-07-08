<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
requireAdmin();

// Placeholder for success/error messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Define the backup directory relative to the project root
$backup_dir = __DIR__ . '/../database/backups/';

// Ensure the backup directory exists
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Database credentials from db_connect.php (assuming they are global or accessible)
global $db_host, $db_port, $db_name, $db_user, $db_pass;

$db_pass_arg = $db_pass ? '-p' . escapeshellarg($db_pass) : '';
$db_port_arg = $db_port ? '-P' . escapeshellarg($db_port) : '';

// point directly to your WampServer MySQL bin folder
$mysqldumpPath = '"C:\\wamp64\\bin\\mysql\\mysql9.1.0\\bin\\mysqldump.exe"';
$mysqlPath     = '"C:\\wamp64\\bin\\mysql\\mysql9.1.0\\bin\\mysql.exe"';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['backup'])) {
        // Handle database backup
        $timestamp = date('Ymd_His');
        $backup_file = $backup_dir . $db_name . '_' . $timestamp . '.sql';

        // Construct the mysqldump command
        $command = sprintf(
            '%s -h %s %s -u %s %s %s > %s',
            $mysqldumpPath,
            escapeshellarg($db_host),
            $db_port_arg,
            escapeshellarg($db_user),
            $db_pass_arg,
            escapeshellarg($db_name),
            escapeshellarg($backup_file)
        );

        $command .= ' 2>&1'; // Redirect stderr to stdout to capture errors

        $output = shell_exec($command);

        if (file_exists($backup_file) && filesize($backup_file) > 0) {
            $message = 'Database backup created successfully: ' . basename($backup_file);
            $message_type = 'success';
        } else {
            $message = 'Database backup failed: ' . ($output ? $output : 'Unknown error.');
            $message_type = 'error';
            if (file_exists($backup_file)) {
                unlink($backup_file); // Clean up empty file if backup failed
            }
        }
    } elseif (isset($_POST['restore'])) {
        // Handle database restore
        if (isset($_FILES['restore_file']) && $_FILES['restore_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['restore_file']['tmp_name'];
            $file_name = $_FILES['restore_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($file_ext === 'sql') {
                $restore_file_path = $backup_dir . 'restore_' . uniqid() . '.sql';
                if (move_uploaded_file($file_tmp_path, $restore_file_path)) {
                    // Construct the mysql command
                    $command = sprintf(
                        '%s -h %s %s -u %s %s %s < %s',
                        $mysqlPath,
                        escapeshellarg($db_host),
                        $db_port_arg,
                        escapeshellarg($db_user),
                        $db_pass_arg,
                        escapeshellarg($db_name),
                        escapeshellarg($restore_file_path)
                    );

                    $output = shell_exec($command);

                    if ($output === null || $output === '') {
                        $message = 'Database restored successfully from: ' . basename($file_name);
                        $message_type = 'success';
                    } else {
                        $message = 'Database restore failed: ' . $output;
                        $message_type = 'error';
                    }
                    unlink($restore_file_path); // Clean up the uploaded file
                } else {
                    $message = 'Failed to move uploaded file.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Invalid file type. Please upload a .sql file.';
                $message_type = 'error';
            }
        } else {
            $message = 'No file uploaded or an upload error occurred.';
            $message_type = 'error';
        }
    }
}

?>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="admin-content-area">
    <h1>Back Up & Restore Database</h1>

    <?php if ($message): ?>
        <p class="<?= $message_type === 'error' ? 'error' : 'success' ?>" style="color: <?= $message_type === 'error' ? '#dc3545' : '#28a745' ?>; text-align: center; margin-bottom: 20px;">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <div class="card">
        <h2>Backup Database</h2>
        <p>Create a backup of your current database.</p>
        <form method="post">
            <button type="submit" name="backup">Create Backup</button>
        </form>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Restore Database</h2>
        <p>Upload a .sql file to restore your database.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="restore_file" accept=".sql" required>
            <button type="submit" name="restore" style="margin-top: 10px;">Restore Database</button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
