<?php
// Open PHP tag to start server-side logic

// Require the configuration file to access the database connection and helpers
require_once __DIR__ . '/config.php';

// Define a function to run a SELECT query and return all rows
function db_all(string $sql, array $params = []): array {
	// Prepare the SQL statement using the shared PDO connection
	$stmt = db()->prepare($sql);
	// Execute the prepared statement with the provided parameters
	$stmt->execute($params);
	// Fetch all resulting rows as an array of associative arrays and return
	return $stmt->fetchAll();
}

// Define a function to run a SELECT query that returns a single row
function db_one(string $sql, array $params = []): ?array {
	// Prepare the SQL statement for execution
	$stmt = db()->prepare($sql);
	// Execute the statement with the bound parameters
	$stmt->execute($params);
	// Fetch one row from the result set
	$row = $stmt->fetch();
	// Return null if no row was found; otherwise, return the row
	return $row === false ? null : $row;
}

// Define a function to run an INSERT/UPDATE/DELETE statement
function db_exec(string $sql, array $params = []): int {
	// Prepare the SQL statement safely
	$stmt = db()->prepare($sql);
	// Execute the statement with the given parameters
	$stmt->execute($params);
	// Return the number of affected rows to the caller
	return $stmt->rowCount();
}

// Define a function to compute the total cost between two dates for a room
function calculate_total(float $pricePerNight, string $checkIn, string $checkOut): float {
	// Create DateTime objects for the check-in and check-out dates
	$in = new DateTime($checkIn);
	$out = new DateTime($checkOut);
	// Calculate the difference between dates in days
	$days = (int)$in->diff($out)->format('%a');
	// Ensure at least one day is counted to avoid zero totals
	$days = max(1, $days);
	// Multiply days by price per night to compute the total amount
	return round($days * $pricePerNight, 2);
}

// Define a function to check if a room is available for a given date range
function is_room_available(int $roomId, string $checkIn, string $checkOut, ?int $ignoreBookingId = null): bool {
	// Build the base SQL query to find overlapping bookings for the same room
	$sql = "SELECT COUNT(*) AS cnt FROM bookings WHERE room_id = :room AND status IN ('pending','confirmed','checked_in') AND NOT (check_out <= :in OR check_in >= :out)";
	// If a booking ID is provided to ignore (for updates), append a condition to exclude it
	$params = [':room' => $roomId, ':in' => $checkIn, ':out' => $checkOut];
	// Conditionally adjust the query to exclude a specific booking ID
	if ($ignoreBookingId !== null) {
		$sql .= " AND id <> :ignore";
		$params[':ignore'] = $ignoreBookingId;
	}
	// Execute the query and fetch the single count value
	$row = db_one($sql, $params);
	// If the count is zero, the room is available; otherwise, it is not
	return isset($row['cnt']) ? ((int)$row['cnt'] === 0) : true;
}
?>