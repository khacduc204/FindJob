<?php
// app/models/Permission.php
require_once __DIR__ . '/Database.php';

class Permission extends Database {
    public function getPermissionsByRole($role_id) {
        $sql = "SELECT p.name 
                FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['name'];
        }
        return $permissions;
    }

    public function hasPermission($role_id, $permission_name) {
    $sql = "SELECT COUNT(*) AS total 
        FROM permissions p
        JOIN role_permissions rp ON rp.permission_id = p.id
        WHERE rp.role_id = ? AND p.name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $role_id, $permission_name);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
}
?>
