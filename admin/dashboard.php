<?php
// session_start(); // Handled by src/php/db_connect.php
require_once __DIR__ . '/../src/php/db_connect.php';
include('includes/header.php');

$approved_bookings = 0;
$all_bookings_count = 0;
$pending_approvals = 0;
$total_revenue = 0;

// Fetch approved bookings
$stmt = $pdo->query("SELECT COUNT(b.booking_id) FROM bookings b JOIN payments p ON b.booking_id = p.booking_id WHERE b.status='approved' AND p.status='verified'");
$approved_bookings = $stmt->fetchColumn();

// Fetch total bookings count
$stmt = $pdo->prepare("SELECT COUNT(*) AS total_count FROM bookings");
$stmt->execute();
$all_bookings_count = $stmt->fetchColumn();
$stmt->closeCursor(); // Release the connection for the next query

// Fetch pending approvals
$stmt = $pdo->prepare("SELECT COUNT(*) AS pending_count FROM bookings WHERE status = 'pending'");
$stmt->execute();
$pending_approvals = $stmt->fetchColumn();
$stmt->closeCursor(); // Release the connection for the next query

// Calculate total revenue from approved bookings (based on 50% of total_amount, which now includes extras)
$sql_revenue = "
    SELECT SUM(
        b.total_amount
        * 0.50
    ) AS grand_total_revenue
    FROM bookings b
    JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.status = 'approved' AND p.status = 'verified';
";

$stmt_revenue = $pdo->prepare($sql_revenue);
$stmt_revenue->execute();
$result_revenue = $stmt_revenue->fetch(PDO::FETCH_ASSOC);
$total_revenue = $result_revenue['grand_total_revenue'] ?? 0;
$stmt_revenue->closeCursor();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    header('Location: ../public/login.php?redirect=' . $redirect_url);
    exit;
}

// 1. Pull only VERIFIED payments and compute total
// Removed the SQL query and table display for reservations as requested.
?>

<div class="main-content">
    <h1>Admin Dashboard</h1>

    <div class="dashboard-grid">
        <div class="card total-bookings">
            <h3>Total Bookings</h3>
            <p><?php echo $approved_bookings; ?> / <?php echo $all_bookings_count; ?></p>
        </div>
        <div class="card pending-approvals">
            <h3>Pending Approvals</h3>
            <p><?php echo $pending_approvals; ?></p>
            
        </div>
        <div class="card total-revenue">
            <h3>Total Revenue</h3>
            <p>₱<?= number_format($total_revenue, 2) ?></p>
        </div>
    </div>

    <!-- Quick Actions card removed as requested -->

</div>

<?php
include('includes/footer.php');
?>
