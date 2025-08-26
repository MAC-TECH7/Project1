<?php

declare(strict_types=1);

// Load .env if present (KEY=VALUE lines, # comments)
$envFilePath = __DIR__ . '/../.env';
if (is_file($envFilePath) && is_readable($envFilePath)) {
	$lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		$trimmed = trim($line);
		if ($trimmed === '' || str_starts_with($trimmed, '#')) {
			continue;
		}
		[$key, $value] = array_pad(explode('=', $line, 2), 2, '');
		$key = trim($key);
		$value = trim($value);
		if ($key !== '') {
			putenv($key . '=' . $value);
		}
	}
}

$databaseHost = getenv('DB_HOST') ?: 'db';
$databasePort = (int)(getenv('DB_PORT') ?: '3306');
$databaseName = getenv('DB_NAME') ?: 'appdb';
$databaseUser = getenv('DB_USER') ?: 'appuser';
$databasePassword = getenv('DB_PASSWORD') ?: 'apppassword';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $databaseHost, $databasePort, $databaseName);

$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
];

try {
	$pdo = new PDO($dsn, $databaseUser, $databasePassword, $options);
} catch (PDOException $e) {
	http_response_code(500);
	echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
	exit;
}