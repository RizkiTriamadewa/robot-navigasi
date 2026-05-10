<?php
/**
 * Logout Handler
 * Logs out the current user and redirects to login page
 */

require 'db.php';
require 'auth.php';

// Logout user
logoutUser($conn);

// Redirect to login page
header('Location: login.php');
exit;
