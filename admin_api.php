<?php
require 'db.php';
require 'auth.php';

// Require login and admin permission
requireLogin();
requirePermission('manage_users');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Super Admin role id (top-tier, immutable)
const SUPER_ADMIN_ROLE_ID = 1;

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
            
        case 'create_role':
            createRole($conn);
            break;
            
        case 'update_role':
            updateRole($conn);
            break;
            
        case 'delete_role':
            deleteRole($conn);
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
    $rolesStmt = $conn->prepare("SELECT id, name, description, is_system FROM roles ORDER BY id");
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
    
    // Super Admin permissions are locked (full access by design)
    if ($role_id === SUPER_ADMIN_ROLE_ID) {
        echo json_encode(['success' => false, 'message' => 'Permission Super Admin tidak bisa diubah']);
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

/**
 * Create a new custom role (optionally with initial permissions)
 */
function createRole($conn) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $permission_ids = json_decode($_POST['permission_ids'] ?? '[]', true);
    
    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Nama role wajib diisi']);
        return;
    }
    
    if (mb_strlen($name) > 50) {
        echo json_encode(['success' => false, 'message' => 'Nama role maksimal 50 karakter']);
        return;
    }
    
    if (!is_array($permission_ids)) {
        $permission_ids = [];
    }
    
    // Unique check
    $checkStmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Nama role sudah digunakan']);
        return;
    }
    
    $conn->begin_transaction();
    try {
        $insertRole = $conn->prepare("INSERT INTO roles (name, description, is_system) VALUES (?, ?, 0)");
        $insertRole->bind_param("ss", $name, $description);
        $insertRole->execute();
        $newRoleId = $conn->insert_id;
        
        if (!empty($permission_ids)) {
            $insertPerm = $conn->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permission_ids as $pid) {
                $pid = intval($pid);
                if ($pid <= 0) continue;
                $insertPerm->bind_param("ii", $newRoleId, $pid);
                $insertPerm->execute();
            }
        }
        
        $conn->commit();
        logAudit($conn, $_SESSION['user_id'], 'create_role', 'admin', "Created role: $name (ID $newRoleId)");
        
        echo json_encode(['success' => true, 'message' => 'Role berhasil dibuat', 'role_id' => $newRoleId]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Gagal membuat role: ' . $e->getMessage()]);
    }
}

/**
 * Update an existing role's name/description.
 * Super Admin (system role) can be edited only by another Super Admin
 * AND only its description (name stays "Super Admin").
 */
function updateRole($conn) {
    $role_id = intval($_POST['role_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($role_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
        return;
    }
    
    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Nama role wajib diisi']);
        return;
    }
    
    // Lookup current role to check is_system flag
    $cur = $conn->prepare("SELECT name, is_system FROM roles WHERE id = ?");
    $cur->bind_param("i", $role_id);
    $cur->execute();
    $row = $cur->get_result()->fetch_assoc();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan']);
        return;
    }
    
    // System role: name is locked, only description editable
    if ((int)$row['is_system'] === 1) {
        if ($name !== $row['name']) {
            echo json_encode(['success' => false, 'message' => 'Nama role sistem tidak bisa diubah']);
            return;
        }
    }
    
    // Unique check (excluding self)
    $checkStmt = $conn->prepare("SELECT id FROM roles WHERE name = ? AND id <> ?");
    $checkStmt->bind_param("si", $name, $role_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Nama role sudah digunakan']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $role_id);
    
    if ($stmt->execute()) {
        logAudit($conn, $_SESSION['user_id'], 'update_role', 'admin', "Updated role ID $role_id ($name)");
        echo json_encode(['success' => true, 'message' => 'Role berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update role']);
    }
}

/**
 * Delete a custom role.
 * - System roles (Super Admin) cannot be deleted.
 * - Roles still assigned to one or more users cannot be deleted.
 */
function deleteRole($conn) {
    $role_id = intval($_POST['role_id'] ?? 0);
    
    if ($role_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID']);
        return;
    }
    
    // Check existence + is_system
    $cur = $conn->prepare("SELECT name, is_system FROM roles WHERE id = ?");
    $cur->bind_param("i", $role_id);
    $cur->execute();
    $row = $cur->get_result()->fetch_assoc();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Role tidak ditemukan']);
        return;
    }
    
    if ((int)$row['is_system'] === 1) {
        echo json_encode(['success' => false, 'message' => 'Role sistem tidak bisa dihapus']);
        return;
    }
    
    // Check if any user still uses this role
    $useStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE role_id = ?");
    $useStmt->bind_param("i", $role_id);
    $useStmt->execute();
    $cnt = (int)$useStmt->get_result()->fetch_assoc()['cnt'];
    if ($cnt > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Role masih digunakan oleh $cnt user. Pindahkan user ke role lain dulu."
        ]);
        return;
    }
    
    $conn->begin_transaction();
    try {
        // role_permissions has ON DELETE CASCADE, but be explicit for clarity
        $delPerm = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $delPerm->bind_param("i", $role_id);
        $delPerm->execute();
        
        $delRole = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $delRole->bind_param("i", $role_id);
        $delRole->execute();
        
        $conn->commit();
        logAudit($conn, $_SESSION['user_id'], 'delete_role', 'admin', "Deleted role: {$row['name']} (ID $role_id)");
        echo json_encode(['success' => true, 'message' => 'Role berhasil dihapus']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus role: ' . $e->getMessage()]);
    }
}
