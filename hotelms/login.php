<?php
// Include authentication helpers to perform login
require_once __DIR__ . '/includes/auth.php';

// If the user is already logged in, redirect them to the dashboard
if (current_user()) {
	redirect('index.php');
}

// Initialize a variable to hold any error message to show on the page
$error = '';

// Check whether the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Read the submitted email from the POST array, defaulting to an empty string
	$email = isset($_POST['email']) ? trim($_POST['email']) : '';
	// Read the submitted password from the POST array, defaulting to an empty string
	$password = isset($_POST['password']) ? $_POST['password'] : '';
	// Attempt to log the user in using the provided credentials
	if (login($email, $password)) {
		// On success, redirect to the dashboard page
		redirect('index.php');
	} else {
		// On failure, set an error message to display to the user
		$error = 'Invalid credentials. Please try again.';
	}
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="row justify-content-center">
	<div class="col-md-5">
		<h3 class="mb-3">Staff Login</h3>
		<?php if($error): ?>
			<div class="alert alert-danger"><?php echo e($error); ?></div>
		<?php endif; ?>
		<div class="card">
			<div class="card-body">
				<form method="post">
					<div class="mb-3">
						<label class="form-label">Email</label>
						<input type="email" name="email" class="form-control" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Password</label>
						<input type="password" name="password" class="form-control" required>
					</div>
					<button type="submit" class="btn btn-primary">Login</button>
				</form>
			</div>
		</div>
	</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>