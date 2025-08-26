<?php
// Include auth to restrict access and helper functions for database queries
require_once __DIR__ . '/includes/auth.php';
require_login();

// Initialize a message variable to display success or error notifications
$message = '';

// Handle Create and Update operations when the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Read the action to determine whether we're creating or updating
	$action = isset($_POST['action']) ? $_POST['action'] : '';
	// Read and sanitize inputs from the form fields
	$room_number = trim($_POST['room_number'] ?? '');
	$room_type = trim($_POST['room_type'] ?? '');
	$description = trim($_POST['description'] ?? '');
	$price_per_night = (float)($_POST['price_per_night'] ?? 0);
	$status = $_POST['status'] ?? 'available';
	// Initialize a variable to store an uploaded image path if present
	$image_path = null;
	// Check whether a file was uploaded in the 'image' field
	if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
		// Generate a unique filename to avoid collisions in the uploads folder
		$filename = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $_FILES['image']['name']);
		// Build the destination path under the uploads directory
		$dest = __DIR__ . '/uploads/' . $filename;
		// Move the uploaded file from the temporary directory to our destination
		if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
			// Store the relative path to save in the database
			$image_path = 'uploads/' . $filename;
		}
	}
	// If the action is create, insert a new row into the rooms table
	if ($action === 'create') {
		// Insert using parameterized SQL to avoid SQL injection
		db_exec('INSERT INTO rooms (room_number, room_type, description, price_per_night, status, image_path) VALUES (:num,:type,:desc,:price,:status,:img)', [
			':num' => $room_number,
			':type' => $room_type,
			':desc' => $description,
			':price' => $price_per_night,
			':status' => $status,
			':img' => $image_path,
		]);
		// Set a success message for the UI
		$message = 'Room created successfully';
	}
	// If the action is update, modify the existing row
	if ($action === 'update') {
		// Read the primary key from the form to identify which row to update
		$id = (int)($_POST['id'] ?? 0);
		// Build the base SQL and parameters for the update
		$sql = 'UPDATE rooms SET room_number=:num, room_type=:type, description=:desc, price_per_night=:price, status=:status';
		$params = [
			':num' => $room_number,
			':type' => $room_type,
			':desc' => $description,
			':price' => $price_per_night,
			':status' => $status,
			':id' => $id,
		];
		// If a new image was uploaded, include it in the update statement
		if ($image_path !== null) {
			$sql .= ', image_path=:img';
			$params[':img'] = $image_path;
		}
		// Finish the statement with the WHERE clause to target the correct row
		$sql .= ' WHERE id=:id';
		// Execute the update with the assembled SQL and parameters
		db_exec($sql, $params);
		// Set a message indicating success
		$message = 'Room updated successfully';
	}
}

// Handle Delete operations triggered by a GET request with an id parameter
if (isset($_GET['delete'])) {
	// Convert the id parameter to an integer for safety
	$id = (int)$_GET['delete'];
	// Execute a delete statement to remove the row from the database
	db_exec('DELETE FROM rooms WHERE id = :id', [':id' => $id]);
	// Set a message to confirm deletion
	$message = 'Room deleted successfully';
}

// Fetch all rooms to display in the table
$rooms = db_all('SELECT * FROM rooms ORDER BY id DESC');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
	<h3>Rooms</h3>
	<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#roomForm">Add Room</button>
</div>
<?php if($message): ?>
	<div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>
<div id="roomForm" class="collapse mb-4">
	<div class="card"><div class="card-body">
		<form method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="create">
			<div class="row g-3">
				<div class="col-md-3">
					<label class="form-label">Room Number</label>
					<input type="text" name="room_number" class="form-control" required>
				</div>
				<div class="col-md-3">
					<label class="form-label">Type</label>
					<input type="text" name="room_type" class="form-control" required>
				</div>
				<div class="col-md-3">
					<label class="form-label">Price / Night</label>
					<input type="number" step="0.01" name="price_per_night" class="form-control" required>
				</div>
				<div class="col-md-3">
					<label class="form-label">Status</label>
					<select name="status" class="form-select">
						<option value="available">available</option>
						<option value="maintenance">maintenance</option>
					</select>
				</div>
				<div class="col-12">
					<label class="form-label">Description</label>
					<textarea name="description" class="form-control" rows="2"></textarea>
				</div>
				<div class="col-md-6">
					<label class="form-label">Image</label>
					<input type="file" name="image" class="form-control">
				</div>
			</div>
			<div class="mt-3">
				<button class="btn btn-success" type="submit">Save</button>
			</div>
		</form>
	</div></div>
</div>

<div class="card">
	<div class="card-body">
		<table class="table table-striped">
			<thead><tr><th>ID</th><th>Number</th><th>Type</th><th>Price</th><th>Status</th><th>Image</th><th>Actions</th></tr></thead>
			<tbody>
			<?php foreach($rooms as $r): ?>
				<tr>
					<td><?php echo (int)$r['id']; ?></td>
					<td><?php echo e($r['room_number']); ?></td>
					<td><?php echo e($r['room_type']); ?></td>
					<td>$<?php echo number_format((float)$r['price_per_night'], 2); ?></td>
					<td><span class="badge text-bg-secondary"><?php echo e($r['status']); ?></span></td>
					<td><?php if($r['image_path']): ?><img src="<?php echo e($r['image_path']); ?>" width="60"><?php endif; ?></td>
					<td>
						<button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-<?php echo (int)$r['id']; ?>">Edit</button>
						<a class="btn btn-sm btn-outline-danger" href="rooms.php?delete=<?php echo (int)$r['id']; ?>" onclick="return confirm('Delete this room?');">Delete</a>
					</td>
				</tr>
				<tr class="collapse" id="edit-<?php echo (int)$r['id']; ?>">
					<td colspan="7">
						<form method="post" enctype="multipart/form-data" class="border p-3 rounded bg-light">
							<input type="hidden" name="action" value="update">
							<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
							<div class="row g-3">
								<div class="col-md-2">
									<label class="form-label">Number</label>
									<input type="text" name="room_number" class="form-control" value="<?php echo e($r['room_number']); ?>" required>
								</div>
								<div class="col-md-2">
									<label class="form-label">Type</label>
									<input type="text" name="room_type" class="form-control" value="<?php echo e($r['room_type']); ?>" required>
								</div>
								<div class="col-md-2">
									<label class="form-label">Price</label>
									<input type="number" step="0.01" name="price_per_night" class="form-control" value="<?php echo e($r['price_per_night']); ?>" required>
								</div>
								<div class="col-md-3">
									<label class="form-label">Status</label>
									<select name="status" class="form-select">
										<option value="available" <?php echo $r['status']==='available'?'selected':''; ?>>available</option>
										<option value="maintenance" <?php echo $r['status']==='maintenance'?'selected':''; ?>>maintenance</option>
									</select>
								</div>
								<div class="col-md-3">
									<label class="form-label">Image</label>
									<input type="file" name="image" class="form-control">
								</div>
								<div class="col-12">
									<label class="form-label">Description</label>
									<textarea name="description" class="form-control" rows="2"><?php echo e($r['description']); ?></textarea>
								</div>
							</div>
							<div class="mt-3">
								<button class="btn btn-success" type="submit">Update</button>
							</div>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>