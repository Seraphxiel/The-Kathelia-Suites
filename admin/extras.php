<?php
// admin/extras.php
require_once __DIR__.'/../src/php/db_connect.php';
requireAdmin();

$stmt = $pdo->query("SELECT * FROM extras ORDER BY name");
$extras = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="main-content">
  <h1>Extras</h1>
  <p><a href="extra_create.php" class="button">Add New Extra</a></p>
  <?php if (count($extras) > 0): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Rate</th>
          <th>Rate Unit</th>
          <th>Created At</th>
          <th>Updated At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($extras as $extra): ?>
          <tr>
            <td><?= htmlspecialchars($extra['name']) ?></td>
            <td><?= htmlspecialchars($extra['rate']) ?></td>
            <td><?= htmlspecialchars($extra['rate_unit']) ?></td>
            <td><?= htmlspecialchars($extra['created_at']) ?></td>
            <td><?= htmlspecialchars($extra['updated_at']) ?></td>
            <td>
              <a href="extra_edit.php?id=<?= $extra['extra_id'] ?>">Edit</a> |
              <form method="post" action="extra_handle.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this extra?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="extra_id" value="<?= $extra['extra_id'] ?>">
                <button type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No extras found.</p>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?> 