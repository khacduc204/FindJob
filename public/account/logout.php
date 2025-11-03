<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AuthController.php';
$auth = new AuthController();
$auth->logout();
header('Location: /JobFind/public/index.php');
exit;
?>
