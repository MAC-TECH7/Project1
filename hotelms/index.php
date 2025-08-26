<?php
// Include authentication and helper code to guard the page and query data
require_once __DIR__ . '/includes/auth.php';
require_login();
// Query counts for small dashboard cards
$kpi_rooms = db_one('SELECT COUNT(*) AS c FROM rooms');
$kpi_bookings = db_one("SELECT COUNT(*) AS c FROM bookings WHERE status <> 'cancelled'");
$kpi_guests = db_one('SELECT COUNT(*) AS c FROM guests');
$kpi_revenue = db_one('SELECT COALESCE(SUM(amount),0) AS s FROM payments');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<h3 class="mb-3">Dashboard</h3>
<div class="row g-3 mb-4">
	<div class="col-md-3">
		<div class="card text-bg-primary"><div class="card-body"><div class="fw-bold">Rooms</div><div class="display-6"><?php echo (int)$kpi_rooms['c']; ?></div></div></div>
	</div>
	<div class="col-md-3">
		<div class="card text-bg-success"><div class="card-body"><div class="fw-bold">Bookings</div><div class="display-6"><?php echo (int)$kpi_bookings['c']; ?></div></div></div>
	</div>
	<div class="col-md-3">
		<div class="card text-bg-info"><div class="card-body"><div class="fw-bold">Guests</div><div class="display-6"><?php echo (int)$kpi_guests['c']; ?></div></div></div>
	</div>
	<div class="col-md-3">
		<div class="card text-bg-warning"><div class="card-body"><div class="fw-bold">Revenue</div><div class="display-6">$<?php echo number_format((float)$kpi_revenue['s'], 2); ?></div></div></div>
	</div>
</div>
<div class="row g-3">
	<div class="col-md-6">
		<div class="card"><div class="card-header">Recent Bookings</div><div class="card-body">
			<table class="table table-sm">
				<thead><tr><th>ID</th><th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
				<tbody>
				<?php foreach(db_all('SELECT b.id, r.room_number, CONCAT(g.first_name, " ", g.last_name) AS guest, b.check_in, b.check_out, b.status FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN guests g ON b.guest_id=g.id ORDER BY b.id DESC LIMIT 5') as $row): ?>
					<tr>
						<td><?php echo (int)$row['id']; ?></td>
						<td><?php echo e($row['room_number']); ?></td>
						<td><?php echo e($row['guest']); ?></td>
						<td><?php echo e($row['check_in']); ?></td>
						<td><?php echo e($row['check_out']); ?></td>
						<td><span class="badge text-bg-secondary"><?php echo e($row['status']); ?></span></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div></div>
	</div>
	<div class="col-md-6">
		<div class="card"><div class="card-header">Recent Payments</div><div class="card-body">
			<table class="table table-sm">
				<thead><tr><th>ID</th><th>Booking</th><th>Amount</th><th>Method</th><th>Paid At</th></tr></thead>
				<tbody>
				<?php foreach(db_all('SELECT p.id, p.booking_id, p.amount, p.method, p.paid_at FROM payments p ORDER BY p.id DESC LIMIT 5') as $row): ?>
					<tr>
						<td><?php echo (int)$row['id']; ?></td>
						<td>#<?php echo (int)$row['booking_id']; ?></td>
						<td>$<?php echo number_format((float)$row['amount'], 2); ?></td>
						<td><?php echo e($row['method']); ?></td>
						<td><?php echo e($row['paid_at']); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div></div>
	</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>