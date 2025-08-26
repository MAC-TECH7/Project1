<?php
// Include the auth helpers to call the logout function
require_once __DIR__ . '/includes/auth.php';
// Log the user out by clearing the session
logout();
// Redirect to the login page after logging out
redirect('login.php');