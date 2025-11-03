<?php
// app/models/Role.php
require_once __DIR__ . '/Database.php';

class Role extends Database {
    public function getAllRoles() {
        $sql = "SELECT * FROM roles";
        return $this->conn->query($sql);
    }

    public function getRoleById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
