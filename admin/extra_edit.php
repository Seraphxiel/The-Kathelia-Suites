<?php
// admin/extra_edit.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM extras WHERE extra_id=?");
$stmt->execute([$id]);
$extra = $stmt->fetch() or exit('Extra not found');

include 'includes/header.php';
?>
<div class="main-content">
  <h1>Edit “<?= htmlspecialchars($extra['name']) ?>”</h1>
  <form method="post" action="extra_handle.php">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="extra_id" value="<?= $id ?>">
    <div class="form-group">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($extra['name']) ?>" required>
    </div>
    <div class="form-group">
      <label for="rate">Rate</label>
      <input type="number" id="rate" name="rate" value="<?= htmlspecialchars($extra['rate']) ?>" step="0.01" required>
    </div>
    <div class="form-group">
      <label for="rate_unit">Rate Unit</label>
      <input type="text" id="rate_unit" name="rate_unit" value="<?= htmlspecialchars($extra['rate_unit']) ?>" required>
    </div>
    <button type="submit">Save</button>
    <a href="extras.php">Cancel</a>
  </form>
</div>
<?php include 'includes/footer.php'; ?> 