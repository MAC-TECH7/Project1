<?php
require __DIR__ . '/src/config.php';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
	$task = trim($_POST['task']);
	if ($task !== '') {
		$stmt = $pdo->prepare('INSERT INTO tasks (title) VALUES (:title)');
		$stmt->execute([':title' => $task]);
		header('Location: /');
		exit;
	}
}

// Handle delete
if (isset($_GET['delete'])) {
	$id = (int)$_GET['delete'];
	$stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
	$stmt->execute([':id' => $id]);
	header('Location: /');
	exit;
}

$tasks = $pdo->query('SELECT id, title, created_at FROM tasks ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>PHP + MySQL + Bootstrap</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
		<div class="container">
			<a class="navbar-brand" href="/">Task Demo</a>
		</div>
	</nav>
	<div class="container py-4">
		<div class="row">
			<div class="col-lg-6 mx-auto">
				<div class="card shadow-sm">
					<div class="card-body">
						<h5 class="card-title mb-3">Add Task</h5>
						<form method="post" class="d-flex gap-2">
							<input name="task" class="form-control" placeholder="Enter task title" required>
							<button class="btn btn-primary" type="submit">Add</button>
						</form>
					</div>
				</div>
				<div class="card shadow-sm mt-3">
					<div class="card-body">
						<h5 class="card-title mb-3">Tasks</h5>
						<?php if (count($tasks) === 0): ?>
							<p class="text-muted mb-0">No tasks yet.</p>
						<?php else: ?>
							<ul class="list-group">
								<?php foreach ($tasks as $t): ?>
									<li class="list-group-item d-flex justify-content-between align-items-center">
										<span>
											<strong><?= htmlspecialchars($t['title']) ?></strong>
											<small class="text-muted ms-2">#<?= (int)$t['id'] ?> · <?= htmlspecialchars($t['created_at']) ?></small>
										</span>
										<a class="btn btn-sm btn-outline-danger" href="?delete=<?= (int)$t['id'] ?>" onclick="return confirm('Delete this task?')">Delete</a>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>