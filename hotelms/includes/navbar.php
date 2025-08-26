<?php
// Bring auth helpers to show user state and role
require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-teal" style="background-color:#0f6b6b">
	<div class="container-fluid">
		<a class="navbar-brand" href="index.php">Hotel</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="nav">
			<ul class="navbar-nav me-auto mb-2 mb-lg-0">
				<li class="nav-item"><a class="nav-link" href="rooms.php">Rooms</a></li>
				<li class="nav-item"><a class="nav-link" href="guests.php">Guests</a></li>
				<li class="nav-item"><a class="nav-link" href="bookings.php">Bookings</a></li>
				<li class="nav-item"><a class="nav-link" href="payments.php">Payments</a></li>
				<li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
			</ul>
			<div class="d-flex">
				<?php if($user): ?>
					<span class="navbar-text me-3">Hello, <?php echo e($user['name']); ?> (<?php echo e($user['role']); ?>)</span>
					<a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
				<?php else: ?>
					<a class="btn btn-outline-light btn-sm" href="login.php">Login</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</nav>
<div class="container my-4">