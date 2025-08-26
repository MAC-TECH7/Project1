<?php
// Include authentication and helpers so only logged-in staff can access bookings
require_once __DIR__ . '/includes/auth.php';
require_login();

// Initialize a message variable to notify the user of results
$message = '';

// Load lists of rooms and guests for select controls
$rooms = db_all("SELECT id, room_number, price_per_night FROM rooms WHERE status='available' ORDER BY room_number");
$guests = db_all('SELECT id, first_name, last_name FROM guests ORDER BY id DESC');

// Handle form submissions for create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Determine action
	$action = $_POST['action'] ?? '';
	// Read fields
	$room_id = (int)($_POST['room_id'] ?? 0);
	$guest_id = (int)($_POST['guest_id'] ?? 0);
	$check_in = $_POST['check_in'] ?? '';
	$check_out = $_POST['check_out'] ?? '';
	$status = $_POST['status'] ?? 'pending';
	$notes = trim($_POST['notes'] ?? '');
	// Find price for selected room to compute total
	$room = db_one('SELECT price_per_night FROM rooms WHERE id=:id', [':id' => $room_id]);
	$price = $room ? (float)$room['price_per_night'] : 0.0;
	$total = calculate_total($price, $check_in, $check_out);

	// Create a new booking
	if ($action === 'create') {
		// Ensure room is available for the requested dates
		if (!is_room_available($room_id, $check_in, $check_out, null)) {
			$message = 'Selected room is not available for these dates';
		} else {
			db_exec('INSERT INTO bookings (room_id, guest_id, check_in, check_out, status, total_amount, notes, created_by) VALUES (:r,:g,:in,:out,:st,:tot,:notes,:by)', [
				':r' => $room_id,
				':g' => $guest_id,
				':in' => $check_in,
				':out' => $check_out,
				':st' => $status,
				':tot' => $total,
				':notes' => $notes ?: null,
				':by' => current_user()['id'],
			]);
			$message = 'Booking created';
		}
	}

	// Update existing booking
	if ($action === 'update') {
		$id = (int)($_POST['id'] ?? 0);
		if (!is_room_available($room_id, $check_in, $check_out, $id)) {
			$message = 'Selected room is not available for these dates';
		} else {
			db_exec('UPDATE bookings SET room_id=:r, guest_id=:g, check_in=:in, check_out=:out, status=:st, total_amount=:tot, notes=:notes WHERE id=:id', [
				':r' => $room_id,
				':g' => $guest_id,
				':in' => $check_in,
				':out' => $check_out,
				':st' => $status,
				':tot' => $total,
				':notes' => $notes ?: null,
				':id' => $id,
			]);
			$message = 'Booking updated';
		}
	}
}

// Cancel via GET parameter
if (isset($_GET['cancel'])) {
	$id = (int)$_GET['cancel'];
	db_exec("UPDATE bookings SET status='cancelled' WHERE id=:id", [':id' => $id]);
	$message = 'Booking cancelled';
}

// Fetch bookings list for the grid
$list = db_all('SELECT b.*, r.room_number, CONCAT(g.first_name," ",g.last_name) AS guest_name FROM bookings b JOIN rooms r ON b.room_id=r.id JOIN guests g ON b.guest_id=g.id ORDER BY b.id DESC');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h3>Bookings</h3>
	<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#bookingForm">Create Booking</button>
</div>
<?php if($message): ?><div class="alert alert-info"><?php echo e($message); ?></div><?php endif; ?>
<div id="bookingForm" class="collapse mb-4">
	<div class="card"><div class="card-body">
		<form method="post">
			<input type="hidden" name="action" value="create">
			<div class="row g-3">
				<div class="col-md-3">
					<label class="form-label">Room</label>
					<select name="room_id" class="form-select" required>
						<option value="">Select a room</option>
						<?php foreach($rooms as $r): ?>
							<option value="<?php echo (int)$r['id']; ?>"><?php echo e($r['room_number']); ?> ($<?php echo number_format((float)$r['price_per_night'],2); ?>)</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label">Guest</label>
					<select name="guest_id" class="form-select" required>
						<option value="">Select a guest</option>
						<?php foreach($guests as $g): ?>
							<option value="<?php echo (int)$g['id']; ?>"><?php echo e($g['first_name'].' '.$g['last_name']); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-2"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" required></div>
				<div class="col-md-2"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" required></div>
				<div class="col-md-2">
					<label class="form-label">Status</label>
					<select name="status" class="form-select">
						<option value="pending">pending</option>
						<option value="confirmed">confirmed</option>
						<option value="checked_in">checked_in</option>
						<option value="checked_out">checked_out</option>
					</select>
				</div>
				<div class="col-12">
					<label class="form-label">Notes</label>
					<textarea name="notes" class="form-control" rows="2"></textarea>
				</div>
			</div>
			<div class="mt-3"><button class="btn btn-success">Save</button></div>
		</form>
	</div></div>
</div>

<div class="card"><div class="card-body">
	<table class="table table-striped">
		<thead><tr><th>ID</th><th>Room</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead>
		<tbody>
		<?php foreach($list as $b): ?>
			<tr>
				<td><?php echo (int)$b['id']; ?></td>
				<td><?php echo e($b['room_number']); ?></td>
				<td><?php echo e($b['guest_name']); ?></td>
				<td><?php echo e($b['check_in']); ?></td>
				<td><?php echo e($b['check_out']); ?></td>
				<td><span class="badge text-bg-secondary"><?php echo e($b['status']); ?></span></td>
				<td>$<?php echo number_format((float)$b['total_amount'], 2); ?></td>
				<td>
					<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?php echo (int)$b['id']; ?>">Edit</button>
					<a class="btn btn-sm btn-outline-warning" href="bookings.php?cancel=<?php echo (int)$b['id']; ?>" onclick="return confirm('Cancel this booking?');">Cancel</a>
				</td>
			</tr>
			<tr class="collapse" id="edit-<?php echo (int)$b['id']; ?>">
				<td colspan="8">
					<form method="post" class="border p-3 rounded bg-light">
						<input type="hidden" name="action" value="update">
						<input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
						<div class="row g-3">
							<div class="col-md-3">
								<label class="form-label">Room</label>
								<select name="room_id" class="form-select" required>
									<?php foreach($rooms as $r): ?>
										<option value="<?php echo (int)$r['id']; ?>" <?php echo $r['id']==$b['room_id']?'selected':''; ?>><?php echo e($r['room_number']); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-md-3">
								<label class="form-label">Guest</label>
								<select name="guest_id" class="form-select" required>
									<?php foreach($guests as $g): ?>
										<option value="<?php echo (int)$g['id']; ?>" <?php echo $g['id']==$b['guest_id']?'selected':''; ?>><?php echo e($g['first_name'].' '.$g['last_name']); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-md-2"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" value="<?php echo e($b['check_in']); ?>" required></div>
							<div class="col-md-2"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" value="<?php echo e($b['check_out']); ?>" required></div>
							<div class="col-md-2">
								<label class="form-label">Status</label>
								<select name="status" class="form-select">
									<?php foreach(['pending','confirmed','checked_in','checked_out','cancelled'] as $st): ?>
										<option value="<?php echo $st; ?>" <?php echo $st===$b['status']?'selected':''; ?>><?php echo $st; ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"><?php echo e($b['notes']); ?></textarea></div>
						</div>
						<div class="mt-3"><button class="btn btn-success">Update</button></div>
					</form>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>