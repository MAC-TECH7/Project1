<?php
// Include authentication and helpers
require_once __DIR__ . '/includes/auth.php';
require_login();

// Message for UI feedback
$message = '';

// Load list of bookings for selection
$bookings = db_all("SELECT id, total_amount, status FROM bookings WHERE status <> 'cancelled' ORDER BY id DESC");

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$booking_id = (int)($_POST['booking_id'] ?? 0);
	$amount = (float)($_POST['amount'] ?? 0);
	$method = $_POST['method'] ?? 'cash';
	$reference = trim($_POST['reference'] ?? '');
	$notes = trim($_POST['notes'] ?? '');
	db_exec('INSERT INTO payments (booking_id, amount, method, reference, notes) VALUES (:b,:a,:m,:r,:n)', [
		':b' => $booking_id,
		':a' => $amount,
		':m' => $method,
		':r' => $reference ?: null,
		':n' => $notes ?: null,
	]);
	$message = 'Payment recorded';
}

// Fetch list for table
$list = db_all('SELECT * FROM payments ORDER BY id DESC');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<h3 class="mb-3">Payments</h3>
<?php if($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
<div class="card mb-4"><div class="card-body">
	<form method="post">
		<div class="row g-3">
			<div class="col-md-3">
				<label class="form-label">Booking</label>
				<select name="booking_id" class="form-select" required>
					<option value="">Select booking</option>
					<?php foreach($bookings as $b): ?>
						<option value="<?php echo (int)$b['id']; ?>">#<?php echo (int)$b['id']; ?> (Total $<?php echo number_format((float)$b['total_amount'],2); ?>, <?php echo e($b['status']); ?>)</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-md-2"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
			<div class="col-md-2">
				<label class="form-label">Method</label>
				<select name="method" class="form-select">
					<option>cash</option>
					<option>card</option>
					<option>transfer</option>
				</select>
			</div>
			<div class="col-md-2"><label class="form-label">Reference</label><input name="reference" class="form-control"></div>
			<div class="col-md-3"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
		</div>
		<div class="mt-3"><button class="btn btn-success">Add Payment</button></div>
	</form>
</div></div>
<div class="card"><div class="card-body">
	<table class="table table-striped">
		<thead><tr><th>ID</th><th>Booking</th><th>Amount</th><th>Method</th><th>Reference</th><th>Paid At</th></tr></thead>
		<tbody>
		<?php foreach($list as $p): ?>
			<tr>
				<td><?php echo (int)$p['id']; ?></td>
				<td>#<?php echo (int)$p['booking_id']; ?></td>
				<td>$<?php echo number_format((float)$p['amount'], 2); ?></td>
				<td><?php echo e($p['method']); ?></td>
				<td><?php echo e($p['reference']); ?></td>
				<td><?php echo e($p['paid_at']); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>