<?php
// admin/room_types.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

$roomTypes = $pdo
  ->query("SELECT room_type_id, name, min_pax, max_pax, rate_per_night, total_rooms FROM room_types ORDER BY name")
  ->fetchAll();

include 'includes/header.php';
?>
<div class="main-content">
  <h1>Rooms & Prices</h1>
  <a href="room_type_create.php" class="button">+ Add New Room Type</a>
  <table class="admin-table">
    <thead>
      <tr><th>ID</th><th>Name</th><th>Min</th><th>Max</th><th>Rate/Night</th><th>Total Rooms</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach($roomTypes as $rt): ?>
      <tr>
        <td><?= $rt['room_type_id'] ?></td>
        <td><?= htmlspecialchars($rt['name']) ?></td>
        <td><?= $rt['min_pax'] ?></td>
        <td><?= $rt['max_pax'] ?></td>
        <td>₱<?= number_format($rt['rate_per_night'],2) ?></td>
        <td><?= $rt['total_rooms'] ?></td>
        <td>
          <a href="room_type_edit.php?id=<?= $rt['room_type_id'] ?>">Edit</a> |
          <form method="post" action="room_type_handle.php" style="display:inline" 
                onsubmit="return confirm('Delete <?= addslashes($rt['name']) ?>?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="room_type_id" value="<?= $rt['room_type_id'] ?>">
            <button>Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
