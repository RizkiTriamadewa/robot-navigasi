<?php
/**
 * Logout Handler
 * Logs out the current user and redirects to login page
 */

require __DIR__ . '/../src/Config/db.php';
require __DIR__ . '/../src/Auth/auth.php';

// Logout user
logoutUser($conn);

// Redirect to login page
header('Location: login.php');
exit;
