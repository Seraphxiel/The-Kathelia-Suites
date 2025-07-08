<?php
// admin/room_type_create.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();
include 'includes/header.php';
?>
<div class="main-content">
  <h1>Add Room Type</h1>
  <form method="post" action="room_type_handle.php">
    <input type="hidden" name="action" value="create">
    <div class="form-group">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
      <label for="min_pax">Min Pax</label>
      <input type="number" id="min_pax" name="min_pax" min="1" required>
    </div>
    <div class="form-group">
      <label for="max_pax">Max Pax</label>
      <input type="number" id="max_pax" name="max_pax" min="1" required>
    </div>
    <div class="form-group">
      <label for="rate_per_night">Rate per Night</label>
      <input type="number" id="rate_per_night" name="rate_per_night" step="0.01" required>
    </div>
    <button type="submit">Create</button>
    <a href="room_types.php">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?> 