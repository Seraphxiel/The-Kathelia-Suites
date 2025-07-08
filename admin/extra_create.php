<?php
// admin/extra_create.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

include 'includes/header.php';
?>
<div class="main-content">
  <h1>Add New Extra</h1>
  <form method="post" action="extra_handle.php">
    <input type="hidden" name="action" value="create">
    <div class="form-group">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
      <label for="rate">Rate</label>
      <input type="number" id="rate" name="rate" step="0.01" required>
    </div>
    <div class="form-group">
      <label for="rate_unit">Rate Unit</label>
      <input type="text" id="rate_unit" name="rate_unit" required>
    </div>
    <button type="submit">Add Extra</button>
    <a href="extras.php">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?> 