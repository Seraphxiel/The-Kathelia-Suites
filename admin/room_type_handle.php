<?php
// admin/room_type_handle.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

$action = $_POST['action'] ?? '';

switch($action) {
  case 'create':
    $stmt = $pdo->prepare(
      "INSERT INTO room_types (name,min_pax,max_pax,rate_per_night) VALUES (?,?,?,?)"
    );
    $stmt->execute([
      $_POST['name'],
      $_POST['min_pax'],
      $_POST['max_pax'],
      $_POST['rate_per_night']
    ]);
    break;

  case 'update':
    $stmt = $pdo->prepare(
      "UPDATE room_types SET name=?,min_pax=?,max_pax=?,rate_per_night=?,total_rooms=? WHERE room_type_id=?"
    );
    $stmt->execute([
      $_POST['name'],
      $_POST['min_pax'],
      $_POST['max_pax'],
      $_POST['rate_per_night'],
      $_POST['total_rooms'],
      $_POST['room_type_id']
    ]);
    break;

  case 'delete':
    $stmt = $pdo->prepare(
      "DELETE FROM room_types WHERE room_type_id=?"
    );
    $stmt->execute([$_POST['room_type_id']]);
    break;
}

header('Location: room_types.php');
exit; 