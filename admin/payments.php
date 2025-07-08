<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
requireAdmin();  // from db_connect.php: redirects non-admins away

// Filtering and Sorting
$filter_status = $_GET['status'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'payment_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$where_clauses = [];
$params = [];

if (!empty($filter_status)) {
    $where_clauses[] = 'p.status = :status';
    $params[':status'] = $filter_status;
}

$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

$order_sql = " ORDER BY ";
switch ($sort_by) {
    case 'payment_id':
        $order_sql .= "p.payment_id";
        break;
    case 'guest_name':
        $order_sql .= "guest_name";
        break;
    case 'date':
        $order_sql .= "p.payment_date";
        break;
    case 'status':
        $order_sql .= "p.status";
        break;
    default:
        $order_sql .= "p.payment_date";
        break;
}
$order_sql .= " " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');

$payments = [];

try {
    // join payments → bookings → users → room_types
    $stmt = $pdo->prepare("
        SELECT
            p.payment_id,
            p.booking_id,
            CONCAT(u.first_name, ' ', u.last_name) AS guest_name,
            rt.name                                      AS room_type,
            (
              DATEDIFF(b.check_out_date, b.check_in_date) * rt.rate_per_night * b.quantity
              + COALESCE((
                  SELECT SUM(be_inner.quantity * e_inner.rate)
                  FROM booking_extras be_inner
                  JOIN extras e_inner ON be_inner.extra_id = e_inner.extra_id
                  WHERE be_inner.booking_id = b.booking_id
              ), 0)
            ) AS booking_total,
            (
              DATEDIFF(b.check_out_date, b.check_in_date) * rt.rate_per_night * b.quantity
              + COALESCE((
                  SELECT SUM(be_inner.quantity * e_inner.rate)
                  FROM booking_extras be_inner
                  JOIN extras e_inner ON be_inner.extra_id = e_inner.extra_id
                  WHERE be_inner.booking_id = b.booking_id
              ), 0)
            ) * 0.50 AS amount_paid,
            p.reference_number                            AS reference_number,
            p.proof_url                                   AS proof_url,
            p.payment_date                 AS payment_date,
            p.status                       AS status,
            GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS amenities,
            GROUP_CONCAT(DISTINCT CONCAT(e.name,' (x',be.quantity,') (₱',FORMAT(e.rate, 2),')') SEPARATOR ', ') AS extras
        FROM payments p
        JOIN bookings    b ON p.booking_id    = b.booking_id
        JOIN users       u ON b.user_id       = u.user_id
        JOIN room_types  rt ON b.room_type_id = rt.room_type_id
        LEFT JOIN booking_amenities ba ON b.booking_id = ba.booking_id
        LEFT JOIN amenities a         ON ba.amenity_id = a.amenity_id
        LEFT JOIN booking_extras be   ON b.booking_id = be.booking_id
        LEFT JOIN extras e            ON be.extra_id   = e.extra_id
        " . $where_sql . " GROUP BY p.payment_id " . $order_sql . "
    ");
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p>Error fetching payments: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="main-content">
    <h1>Payments Management</h1>

    <div class="filter-form">
        <form action="payments.php" method="GET">
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">All</option>
                <option value="submitted" <?= $filter_status === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                <option value="verified" <?= $filter_status === 'verified' ? 'selected' : '' ?>>Verified</option>
                <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>

            <label for="sort_by">Sort By:</label>
            <select name="sort_by" id="sort_by">
                <option value="payment_date" <?= $sort_by === 'payment_date' ? 'selected' : '' ?>>Date</option>
                <option value="payment_id" <?= $sort_by === 'payment_id' ? 'selected' : '' ?>>Payment ID</option>
                <option value="guest_name" <?= $sort_by === 'guest_name' ? 'selected' : '' ?>>Guest Name</option>
                <option value="status" <?= $sort_by === 'status' ? 'selected' : '' ?>>Status</option>
            </select>

            <label for="sort_order">Order:</label>
            <select name="sort_order" id="sort_order">
                <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Descending</option>
                <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
            </select>
            
            <button type="submit" class="action-button">Apply Filters</button>
            <a href="payments.php" class="action-button btn-reset">Reset</a>
        </form>
    </div>

    <?php if (empty($payments)): ?>
        <p>No payments found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Booking ID</th>
                    <th>Guest</th>
                    <th>Room Type</th>
                    <th>Amenities</th>
                    <th>Extras</th>
                    <th>Booking Total</th>
                    <th>Amount Paid</th>
                    <th>Reference No.</th>
                    <th>Proof</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= $p['payment_id'] ?></td>
                    <td><a href="bookings.php?booking_id=<?= $p['booking_id'] ?>">#<?= $p['booking_id'] ?></a></td>
                    <td><?= htmlspecialchars($p['guest_name']) ?></td>
                    <td><?= htmlspecialchars($p['room_type']) ?></td>
                    <td><?= htmlspecialchars($p['amenities'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($p['extras'] ?: '—') ?></td>
                    <td>₱<?= number_format($p['booking_total'], 2) ?></td>
                    <td>₱<?= number_format($p['amount_paid'], 2) ?></td>
                    <td><?= htmlspecialchars($p['reference_number']) ?></td>
                    <td>
                        <?php if (!empty($p['proof_url'])): ?>
                            <a href="<?= htmlspecialchars($p['proof_url']) ?>" target="_blank">View Proof</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d H:i', strtotime($p['payment_date'])) ?></td>
                    <td><?= ucfirst($p['status']) ?></td>
                    <td>
                        <?php if ($p['status'] === 'submitted'): ?>
                            <a href="verify_payment.php?payment_id=<?= $p['payment_id'] ?>" class="action-button approve-btn">Verify</a>
                            <a href="reject_payment.php?payment_id=<?= $p['payment_id'] ?>" class="action-button reject-btn">Reject</a>
                        <?php elseif ($p['status'] === 'verified'): ?>
                            <!-- Action column is blank if already verified -->
                        <?php elseif ($p['status'] === 'rejected'): ?>
                            <span class="badge badge-danger">Rejected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?> 