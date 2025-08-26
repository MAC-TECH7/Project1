<?php
// Include authentication and helpers to control access and run queries
require_once __DIR__ . '/includes/auth.php';
require_login();

// Initialize a message string to show results of operations
$message = '';

// Handle create/update submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Determine the action requested by the form
	$action = $_POST['action'] ?? '';
	// Extract form fields with default values
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$phone = trim($_POST['phone'] ?? '');
	$address = trim($_POST['address'] ?? '');
	// Create a new guest when the action is 'create'
	if ($action === 'create') {
		db_exec('INSERT INTO guests (first_name,last_name,email,phone,address) VALUES (:f,:l,:e,:p,:a)', [
			':f' => $first_name,
			':l' => $last_name,
			':e' => $email ?: null,
			':p' => $phone ?: null,
			':a' => $address ?: null,
		]);
		$message = 'Guest added successfully';
	}
	// Update an existing guest when action is 'update'
	if ($action === 'update') {
		$id = (int)($_POST['id'] ?? 0);
		db_exec('UPDATE guests SET first_name=:f,last_name=:l,email=:e,phone=:p,address=:a WHERE id=:id', [
			':f' => $first_name,
			':l' => $last_name,
			':e' => $email ?: null,
			':p' => $phone ?: null,
			':a' => $address ?: null,
			':id' => $id,
		]);
		$message = 'Guest updated successfully';
	}
}

// Handle delete via GET parameter
if (isset($_GET['delete'])) {
	$id = (int)$_GET['delete'];
	db_exec('DELETE FROM guests WHERE id=:id', [':id' => $id]);
	$message = 'Guest deleted';
}

// Fetch all guests for display
$guests = db_all('SELECT * FROM guests ORDER BY id DESC');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h3>Guests</h3>
	<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#guestForm">Add Guest</button>
</div>
<?php if($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
<div id="guestForm" class="collapse mb-4">
	<div class="card"><div class="card-body">
		<form method="post">
			<input type="hidden" name="action" value="create">
			<div class="row g-3">
				<div class="col-md-3"><label class="form-label">First Name</label><input name="first_name" class="form-control" required></div>
				<div class="col-md-3"><label class="form-label">Last Name</label><input name="last_name" class="form-control" required></div>
				<div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
				<div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
				<div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control"></div>
			</div>
			<div class="mt-3"><button class="btn btn-success">Save</button></div>
		</form>
	</div></div>
</div>
<div class="card"><div class="card-body">
	<table class="table table-striped">
		<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead>
		<tbody>
		<?php foreach($guests as $g): ?>
			<tr>
				<td><?php echo (int)$g['id']; ?></td>
				<td><?php echo e($g['first_name'] . ' ' . $g['last_name']); ?></td>
				<td><?php echo e($g['email']); ?></td>
				<td><?php echo e($g['phone']); ?></td>
				<td><?php echo e($g['address']); ?></td>
				<td>
					<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?php echo (int)$g['id']; ?>">Edit</button>
					<a class="btn btn-sm btn-outline-danger" href="guests.php?delete=<?php echo (int)$g['id']; ?>" onclick="return confirm('Delete this guest?');">Delete</a>
				</td>
			</tr>
			<tr class="collapse" id="edit-<?php echo (int)$g['id']; ?>">
				<td colspan="6">
					<form method="post" class="border p-3 rounded bg-light">
						<input type="hidden" name="action" value="update">
						<input type="hidden" name="id" value="<?php echo (int)$g['id']; ?>">
						<div class="row g-3">
							<div class="col-md-3"><label class="form-label">First Name</label><input name="first_name" class="form-control" value="<?php echo e($g['first_name']); ?>" required></div>
							<div class="col-md-3"><label class="form-label">Last Name</label><input name="last_name" class="form-control" value="<?php echo e($g['last_name']); ?>" required></div>
							<div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo e($g['email']); ?>"></div>
							<div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?php echo e($g['phone']); ?>"></div>
							<div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?php echo e($g['address']); ?>"></div>
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