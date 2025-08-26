<?php
// Protect the page behind login
require_once __DIR__ . '/includes/auth.php';
require_login();

// Compute simple KPIs and time-based aggregates
$today = date('Y-m-d');
$kpi_today_bookings = db_one('SELECT COUNT(*) AS c FROM bookings WHERE DATE(created_at)=:d', [':d' => $today]);
$kpi_total_bookings = db_one("SELECT COUNT(*) AS c FROM bookings WHERE status <> 'cancelled'");
$kpi_pending = db_one("SELECT COUNT(*) AS c FROM bookings WHERE status = 'pending'");
$revenue_month = db_one("SELECT COALESCE(SUM(amount),0) AS s FROM payments WHERE DATE_FORMAT(paid_at,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')");

// Top rooms by bookings
$top_rooms = db_all('SELECT r.room_number, COUNT(*) AS cnt FROM bookings b JOIN rooms r ON b.room_id=r.id GROUP BY r.id ORDER BY cnt DESC LIMIT 5');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<h3 class="mb-3">Reports & Analytics</h3>
<div class="row g-3 mb-4">
	<div class="col-md-3"><div class="card text-bg-primary"><div class="card-body"><div class="fw-bold">Today Bookings</div><div class="display-6"><?php echo (int)$kpi_today_bookings['c']; ?></div></div></div></div>
	<div class="col-md-3"><div class="card text-bg-success"><div class="card-body"><div class="fw-bold">Total Bookings</div><div class="display-6"><?php echo (int)$kpi_total_bookings['c']; ?></div></div></div></div>
	<div class="col-md-3"><div class="card text-bg-warning"><div class="card-body"><div class="fw-bold">Pending</div><div class="display-6"><?php echo (int)$kpi_pending['c']; ?></div></div></div></div>
	<div class="col-md-3"><div class="card text-bg-info"><div class="card-body"><div class="fw-bold">Revenue (month)</div><div class="display-6">$<?php echo number_format((float)$revenue_month['s'],2); ?></div></div></div></div>
</div>
<div class="card"><div class="card-header">Top Rooms by Bookings</div><div class="card-body">
	<table class="table table-sm">
		<thead><tr><th>Room</th><th>Bookings</th></tr></thead>
		<tbody>
			<?php foreach($top_rooms as $tr): ?>
				<tr><td><?php echo e($tr['room_number']); ?></td><td><?php echo (int)$tr['cnt']; ?></td></tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>