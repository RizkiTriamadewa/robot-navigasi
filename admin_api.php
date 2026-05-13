<?php
require 'db.php';
require 'auth.php';

// Require login and admin permission
requireLogin();
requirePermission('manage_users');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_users':
            getUsersList($conn);
            break;
            
        case 'get_permissions':
            getPermissionsData($conn);
            break;
            
        case 'create_user':
            createUser($conn);
            break;
            
        case 'toggle_user_status':
            toggleUserStatus($conn);
            break;
            
        case 'update_permissions':
            updateRolePermissions($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get all users with their roles
 */
function getUsersList($conn) {
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.full_name, u.email, u.is_active, u.last_login, u.created_at,
               r.name as role_name, r.id as role_id
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ORDER BY u.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
}

/**
 * Get all permissions grouped by module with role mappings
 */
function getPermissionsData($conn) {
    // Get all roles
    $rolesStmt = $conn->prepare("SELECT id, name FROM roles ORDER BY id");
    $rolesStmt->execute();
    $rolesResult = $rolesStmt->get_result();
    $roles = [];
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = $row;
    }
    
    // Get all permissions
    $permStmt = $conn->prepare("
        SELECT id, name, description, module, action 
        FROM permissions 
        ORDER BY module, action
    ");
    $permStmt->execute();
    $permResult = $permStmt->get_result();
    
    $permissions = [];
    while ($row = $permResult->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    // Get role-permission mappings
    $mappingStmt = $conn->prepare("SELECT role_id, permission_id FROM role_permissions");
    $mappingStmt->execute();
    $mappingResult = $mappingStmt->get_result();
    
    $mappings = [];
    while ($row = $mappingResult->fetch_assoc()) {
        $mappings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'roles' => $roles,
        'permissions' => $permissions,
        'mappings' => $mappings
    ]);
}

/**
 * Create new user
 */
function createUser($conn) {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($username) || empty($full_name) || empty($password) || $role_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        return;
    }
    
    // Check if username exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
        return;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, full_name, email, role_id, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $created_by = $_SESSION['user_id'];
    $stmt->bind_param("ssssiis", $username, $hashed_password, $full_name, $email, $role_id, $is_active, $created_by);
    
    if ($stmt->execute()) {
        // Log audit
        logAudit($conn, $_SESSION['user_id'], 'create_user', 'admin', "Created user: $username");
        
        echo json_encode(['success' => true, 'message' => 'User berhasil dibuat']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat user']);
    }
}

/**
 * Toggle user active status
 */
function toggleUserStatus($conn) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }
    
    // Prevent disabling own account
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Tidak dapat menonaktifkan akun sendiri']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_active, $user_id);
    
    if ($stmt->execute()) {
        $status = $is_active ? 'activated' : 'deactivated';
        logAudit($conn, $_SESSION['user_id'], 'toggle_user_status', 'admin', "User ID $user_id $status");
        
        echo json_encode(['success' => true, 'message' => 'Status user berhasil diubah']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengubah status user']);
    }
}

/**
 * Update role permissions
 */
function updateRolePermissions($conn) {
    $role_id = intval($_POST['role_id'] ?? 0);
    $permission_ids = json_decode($_POST['permission_ids'] ?? '[]', true);
    
    if ($role_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
        return;
    }
    
    if (!is_array($permission_ids)) {
        echo json_encode(['success' => false, 'message' => 'Invalid permission data']);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing permissions for this role
        $deleteStmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $deleteStmt->bind_param("i", $role_id);
        $deleteStmt->execute();
        
        // Insert new permissions
        if (!empty($permission_ids)) {
            $insertStmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permission_ids as $perm_id) {
                $perm_id = intval($perm_id);
                $insertStmt->bind_param("ii", $role_id, $perm_id);
                $insertStmt->execute();
            }
        }
        
        $conn->commit();
        
        // Log audit
        $permCount = count($permission_ids);
        logAudit($conn, $_SESSION['user_id'], 'update_permissions', 'admin', "Updated permissions for role ID $role_id ($permCount permissions)");
        
        echo json_encode(['success' => true, 'message' => 'Permission berhasil diupdate']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Gagal update permission: ' . $e->getMessage()]);
    }
}
