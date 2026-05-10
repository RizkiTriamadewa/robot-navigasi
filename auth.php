<?php
/**
 * Authentication Helper Functions
 * Handles session management, login, logout, and permission checks
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'] ?? '',
        'role_id' => $_SESSION['role_id'],
        'role_name' => $_SESSION['role_name']
    ];
}

/**
 * Check if user has specific permission
 */
function hasPermission($permission_name) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Super Admin has all permissions
    if ($_SESSION['role_id'] == 1) {
        return true;
    }
    
    // Check if permission exists in session
    return isset($_SESSION['permissions']) && in_array($permission_name, $_SESSION['permissions']);
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific permission
 */
function requirePermission($permission_name) {
    requireLogin();
    
    if (!hasPermission($permission_name)) {
        http_response_code(403);
        die('Access Denied: You do not have permission to access this resource.');
    }
}

/**
 * Login user
 */
function loginUser($conn, $username, $password) {
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.password, u.full_name, u.email, u.role_id, u.is_active, r.name as role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.username = ?
    ");
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Username atau password salah'];
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user is active
    if ($user['is_active'] != 1) {
        return ['success' => false, 'message' => 'Akun Anda telah dinonaktifkan'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Username atau password salah'];
    }
    
    // Get user permissions
    $permissions = getUserPermissions($conn, $user['role_id']);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['permissions'] = $permissions;
    $_SESSION['login_time'] = time();
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();
    
    // Log audit
    logAudit($conn, $user['id'], 'login', 'auth', 'User logged in');
    
    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser($conn = null) {
    if ($conn && isLoggedIn()) {
        logAudit($conn, $_SESSION['user_id'], 'logout', 'auth', 'User logged out');
    }
    
    // Clear session
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Get user permissions by role_id
 */
function getUserPermissions($conn, $role_id) {
    $stmt = $conn->prepare("
        SELECT p.name
        FROM permissions p
        JOIN role_permissions rp ON p.id = rp.permission_id
        WHERE rp.role_id = ?
    ");
    
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['name'];
    }
    
    return $permissions;
}

/**
 * Log audit trail
 */
function logAudit($conn, $user_id, $action, $module, $details = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, module, details, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issss", $user_id, $action, $module, $details, $ip_address);
    $stmt->execute();
}

/**
 * Get user's role name
 */
function getRoleName() {
    return $_SESSION['role_name'] ?? 'Guest';
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['role_id'] == 1;
}

/**
 * Check if user is operator
 */
function isOperator() {
    return isLoggedIn() && $_SESSION['role_id'] == 2;
}

/**
 * Check if user is viewer
 */
function isViewer() {
    return isLoggedIn() && $_SESSION['role_id'] == 3;
}

/**
 * Check if user is technician
 */
function isTechnician() {
    return isLoggedIn() && $_SESSION['role_id'] == 4;
}

/**
 * Get session timeout in seconds (30 minutes)
 */
function getSessionTimeout() {
    return 1800; // 30 minutes
}

/**
 * Check if session has expired
 */
function isSessionExpired() {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $elapsed = time() - $_SESSION['login_time'];
    return $elapsed > getSessionTimeout();
}

/**
 * Refresh session timeout
 */
function refreshSession() {
    $_SESSION['login_time'] = time();
}
