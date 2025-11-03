<?php
// app/controllers/AuthMiddleware.php
require_once __DIR__ . '/../models/Permission.php';

function checkPermission($required_permission) {
    if (!isset($_SESSION['role_id'])) {
        header("Location: /JobFind/public/403.php");
        exit;
    }

    $role_id = $_SESSION['role_id'];
    $permission = new Permission();
    if (!$permission->hasPermission($role_id, $required_permission)) {
        header("Location: /JobFind/public/403.php");
        exit;
    }
}
// app/controllers/AuthMiddleware.php

class AuthMiddleware {
    public function checkLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /JobFind/public/login.php');
            exit;
        }
    }

    public function checkAdmin() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /JobFind/public/403.php');
            exit;
        }
    }

    public function checkCandidate() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'candidate') {
            header('Location: /JobFind/public/403.php');
            exit;
        }
    }

    public function checkEmployer() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employer') {
            header('Location: /JobFind/public/403.php');
            exit;
        }
    }
}

?>
