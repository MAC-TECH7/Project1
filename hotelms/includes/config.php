<?php
// Start a new PHP tag to begin server-side code execution

// Enable strict types to encourage safer code (optional but good practice)
declare(strict_types=1);

// Start the session to handle user login state across requests
session_start();

// Turn on error reporting during development to make issues visible
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Define constants for database connection parameters to avoid magic strings
define('DB_HOST', '127.0.0.1'); // The hostname or IP address of the MySQL server
define('DB_NAME', 'hotelms');   // The name of the MySQL database to use
define('DB_USER', 'root');      // The MySQL username with access to the database
define('DB_PASS', '');          // The MySQL password for the above user (empty by default)

// Define a constant for the base URL of the application for redirects and links
// Adjust this value if you deploy to a different path or domain
define('BASE_URL', '/hotelms');

// Create a variable to hold a single shared PDO database connection instance
$pdo = null;

// Create a function to obtain a PDO connection using the configuration above
function db(): PDO {
	// Use the global $pdo variable to implement a simple singleton pattern
	global $pdo;
	// If the $pdo connection is not yet created, we will construct it now
	if ($pdo === null) {
		// Build the Data Source Name (DSN) string with host and database name
		$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
		// Configure PDO options for error mode, fetch mode, and prepared statements
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on database errors
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Return results as associative arrays
			PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements if available
		];
		// Instantiate the PDO object with DSN, username, password, and options
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
	}
	// Return the established PDO connection
	return $pdo;
}

// Define a helper function to perform safe redirects within the application
function redirect(string $path): void {
	// Build the full URL by combining the base URL with the provided path
	$url = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
	// Send a Location header to instruct the browser to navigate to the new URL
	header('Location: ' . $url);
	// Terminate script execution after sending the redirect to prevent further output
	exit();
}

// Define a helper to escape HTML output to mitigate XSS attacks
function e(string $value): string {
	// Convert special characters to HTML entities (e.g., < becomes &lt;)
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>