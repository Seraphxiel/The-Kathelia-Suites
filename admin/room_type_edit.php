<?php
// admin/room_type_edit.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM room_types WHERE room_type_id=?");
$stmt->execute([$id]);
$rt = $stmt->fetch() or exit('Not found');

include 'includes/header.php';
?>
<div class="main-content">
  <h1>Edit “<?= htmlspecialchars($rt['name']) ?>”</h1>
  <form method="post" action="room_type_handle.php">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="room_type_id" value="<?= $id ?>">
    <div class="form-group">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($rt['name']) ?>" required>
    </div>
    <div class="form-group">
      <label for="min_pax">Min Pax</label>
      <input type="number" id="min_pax" name="min_pax" value="<?= $rt['min_pax'] ?>" min="1" required>
    </div>
    <div class="form-group">
      <label for="max_pax">Max Pax</label>
      <input type="number" id="max_pax" name="max_pax" value="<?= $rt['max_pax'] ?>" min="1" required>
    </div>
    <div class="form-group">
      <label for="rate_per_night">Rate per Night</label>
      <input type="number" id="rate_per_night" name="rate_per_night" value="<?= $rt['rate_per_night'] ?>" step="0.01" required>
    </div>
    <div class="form-group">
      <label for="total_rooms">Total Rooms</label>
      <input type="number" id="total_rooms" name="total_rooms" value="<?= $rt['total_rooms'] ?? 0 ?>" min="0" required>
    </div>
    <button type="submit">Save</button>
    <a href="room_types.php">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?> 