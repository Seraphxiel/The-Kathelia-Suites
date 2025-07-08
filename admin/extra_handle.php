<?php
// admin/extra_handle.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

$action = $_POST['action'] ?? '';

switch($action) {
  case 'create':
    $stmt = $pdo->prepare(
      "INSERT INTO extras (name, rate, rate_unit) VALUES (?, ?, ?)"
    );
    $stmt->execute([
      $_POST['name'],
      $_POST['rate'],
      $_POST['rate_unit']
    ]);
    break;

  case 'update':
    $stmt = $pdo->prepare(
      "UPDATE extras SET name=?, rate=?, rate_unit=?, updated_at=NOW() WHERE extra_id=?"
    );
    $stmt->execute([
      $_POST['name'],
      $_POST['rate'],
      $_POST['rate_unit'],
      $_POST['extra_id']
    ]);
    break;

  case 'delete':
    $stmt = $pdo->prepare(
      "DELETE FROM extras WHERE extra_id=?"
    );
    $stmt->execute([$_POST['extra_id']]);
    break;
}

header('Location: extras.php');
exit; 