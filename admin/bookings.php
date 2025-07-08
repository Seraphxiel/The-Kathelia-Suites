<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
include('includes/header.php');

// Auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

// Filtering and Sorting
$filter_status = $_GET['status'] ?? '';
$search_name = $_GET['name'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$where_clauses = [];
$params = [];

if (!empty($filter_status)) {
    $where_clauses[] = 'b.status = :status';
    $params[':status'] = $filter_status;
}

if (!empty($search_name)) {
    $where_clauses[] = '(u.first_name LIKE :search_name_first OR u.last_name LIKE :search_name_last)';
    $params[':search_name_first'] = '%' . $search_name . '%';
    $params[':search_name_last'] = '%' . $search_name . '%';
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$order_sql = " ORDER BY ";
switch ($sort_by) {
    case 'booking_id':
        $order_sql .= "b.booking_id";
        break;
    case 'guest_name':
        $order_sql .= "guest_name";
        break;
    case 'check_in_date':
        $order_sql .= "b.check_in_date";
        break;
    case 'booking_status':
        $order_sql .= "b.status";
        break;
    default:
        $order_sql .= "b.created_at";
        break;
}
$order_sql .= " " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');

// Fetch bookings along with payment status, amenities, extras, 50% total, and booking status
$sql = "
SELECT
  b.booking_id,
  CONCAT(u.first_name,' ',u.last_name)             AS guest_name,
  rt.name                                         AS room_type,
  b.number_of_guests                              AS guests,
  b.check_in_date                                 AS `in`,
  b.check_out_date                                AS `out`,
  GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ')    AS amenities,
  GROUP_CONCAT(DISTINCT CONCAT(e.name,' (x',be.quantity,') (₱',FORMAT(e.rate, 2),')') SEPARATOR ', ') AS extras,
  (
    DATEDIFF(b.check_out_date, b.check_in_date) * rt.rate_per_night * b.quantity
  ) AS room_total_debug,
  (
    SELECT SUM(be_inner.quantity * e_inner.rate)
    FROM booking_extras be_inner
    JOIN extras e_inner ON be_inner.extra_id = e_inner.extra_id
    WHERE be_inner.booking_id = b.booking_id
  ) AS extras_sum_debug,
  (
    DATEDIFF(b.check_out_date, b.check_in_date) * rt.rate_per_night * b.quantity
    + COALESCE((
        SELECT SUM(be_inner.quantity * e_inner.rate)
        FROM booking_extras be_inner
        JOIN extras e_inner ON be_inner.extra_id = e_inner.extra_id
        WHERE be_inner.booking_id = b.booking_id
    ), 0)
  ) AS full_total_amount,
  b.status                                        AS booking_status,
  lp.status                                        AS payment_status
FROM bookings b
JOIN users u           ON b.user_id      = u.user_id
JOIN room_types rt     ON b.room_type_id = rt.room_type_id
LEFT JOIN booking_amenities ba ON b.booking_id = ba.booking_id
LEFT JOIN amenities a         ON ba.amenity_id = a.amenity_id
LEFT JOIN booking_extras be   ON b.booking_id = be.booking_id
LEFT JOIN extras e            ON be.extra_id   = e.extra_id
LEFT JOIN (
    SELECT
        p_inner.booking_id,
        p_inner.status
    FROM payments p_inner
    INNER JOIN (
        SELECT
            booking_id,
            MAX(payment_date) AS latest_date,
            MAX(payment_id) AS latest_id
        FROM payments
        GROUP BY booking_id
    ) AS latest_payments
    ON p_inner.booking_id = latest_payments.booking_id
    AND p_inner.payment_date = latest_payments.latest_date
    AND p_inner.payment_id = latest_payments.latest_id
) AS lp ON b.booking_id = lp.booking_id
" . $where_sql . " GROUP BY b.booking_id" . $order_sql;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
  <h1>Booking Approvals</h1>

    <div class="filter-form">
        <form action="bookings.php" method="GET">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">All</option>
                <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>

            <label for="name">Guest Name:</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($search_name) ?>" placeholder="Search by guest name">

            <label for="sort_by">Sort By:</label>
            <select name="sort_by" id="sort_by">
                <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                <option value="booking_id" <?= $sort_by === 'booking_id' ? 'selected' : '' ?>>Booking ID</option>
                <option value="guest_name" <?= $sort_by === 'guest_name' ? 'selected' : '' ?>>Guest Name</option>
                <option value="check_in_date" <?= $sort_by === 'check_in_date' ? 'selected' : '' ?>>Check-in Date</option>
                <option value="booking_status" <?= $sort_by === 'booking_status' ? 'selected' : '' ?>>Booking Status</option>
            </select>

            <label for="sort_order">Order:</label>
            <select name="sort_order" id="sort_order">
                <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Descending</option>
                <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
            </select>
            
            <button type="submit" class="action-button">Apply Filters</button>
            <a href="bookings.php" class="action-button btn-reset">Reset</a>
        </form>
    </div>

  <table class="admin-table" style="font-size: 0.85em; table-layout: fixed; width: 100%;">
    <thead>
      <tr>
        <th style="width: 3%;">#</th>
        <th style="width: 10%;">NAME</th>
        <th style="width: 7%;">ROOM</th>
        <th style="width: 3%;">GUEST</th>
        <th style="width: 8%;">IN</th>
        <th style="width: 8%;">OUT</th>
        <th style="width: 18%;">AMENITIES</th>
        <th style="width: 18%;">EXTRAS</th>
        <th style="width: 8%;">TOTAL AMOUNT</th>
        <th style="width: 8%;">50% DUE</th>
        <th style="width: 10%;">BOOKING STATUS</th>
        <th style="width: 10%;">PAYMENT</th>
        <th style="width: 15%;">ACTION</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $b): 
        // normalize payment display
        switch($b['payment_status']) {
          case 'submitted': $pay = 'Submitted'; break;
          case 'verified':  $pay = 'Verified';  break;
          case 'rejected':  $pay = 'Rejected';  break;
          default:          $pay = 'None';      break;
        }
      ?>
      <tr>
        <td><?= htmlspecialchars($b['booking_id']) ?></td>
        <td><?= htmlspecialchars($b['guest_name']) ?></td>
        <td><?= htmlspecialchars($b['room_type']) ?></td>
        <td><?= $b['guests'] ?></td>
        <td><?= htmlspecialchars($b['in']) ?></td>
        <td><?= htmlspecialchars($b['out']) ?></td>
        <td><?= htmlspecialchars($b['amenities'] ?: '—') ?></td>
        <td><?= htmlspecialchars($b['extras'] ?: '—') ?></td>
        <td>₱<?= number_format($b['full_total_amount'], 2) ?></td>
        <td>₱<?= number_format($b['full_total_amount'] * 0.5, 2) ?></td>
        <td><?= ucfirst(htmlspecialchars($b['booking_status'])) ?></td>
        <td><?= $pay ?></td>
        <td>
          <?php if ($b['booking_status'] === 'pending'): ?>
            <form method="post" action="approve_booking.php" style="display:inline">
              <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
              <button name="action" value="approve">Approve</button>
            </form>
            <form method="post" action="reject_booking.php" style="display:inline">
              <input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>">
              <button name="action" value="reject">Reject</button>
            </form>
          <?php else: ?>
            <span style="font-size:.85em; white-space: nowrap;"><?= ucfirst(htmlspecialchars($b['booking_status'])) ?></span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</main>

<?php include('includes/footer.php'); ?>

<!-- 6. OPTIONAL: add JS for bulk-approve/reject and check-all behavior -->
<script>
  // ... your JS here to post selected IDs to approve_bulk.php / reject_bulk.php ...
</script>
