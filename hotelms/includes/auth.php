<?php
// Begin PHP code for authentication helpers

// Include the configuration to use sessions and database access
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Define a function to attempt a user login with email and password
function login(string $email, string $password): bool {
	// Fetch the user record by email using a parameterized query
	$user = db_one('SELECT * FROM users WHERE email = :email', [':email' => $email]);
	// If no user found, return false to indicate failure
	if (!$user) {
		return false;
	}
	// Verify the provided password against the stored password hash
	if (!password_verify($password, $user['password_hash'])) {
		return false;
	}
	// On success, store minimal user data in the session for later checks
	$_SESSION['user'] = [
		'id' => (int)$user['id'],
		'name' => $user['name'],
		'email' => $user['email'],
		'role' => $user['role'],
	];
	// Return true to indicate login success
	return true;
}

// Define a function to remove the session and log the user out
function logout(): void {
	// Unset the session user variable if it exists
	unset($_SESSION['user']);
	// Destroy the entire session to clear all data
	session_destroy();
}

// Define a function to fetch the currently authenticated user or null
function current_user(): ?array {
	// Check if the session has a 'user' entry and return it
	return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

// Define a function to ensure a user is logged in, otherwise redirect to login
function require_login(): void {
	// If there is no current user, redirect to the login page
	if (!current_user()) {
		redirect('login.php');
	}
}

// Define a function to ensure the current user has the admin role
function require_admin(): void {
	// Ensure the user is logged in first
	require_login();
	// Fetch the user record from the session
	$user = current_user();
	// If the role is not admin, deny access with a 403 response code
	if (!$user || $user['role'] !== 'admin') {
		http_response_code(403);
		echo 'Forbidden: Admins only';
		exit();
	}
}
?>