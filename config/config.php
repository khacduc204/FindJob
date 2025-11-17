<?php
// config/config.php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "jobfinder";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập bộ ký tự UTF-8
$conn->set_charset("utf8");

// Base URL configuration
define('BASE_URL', '/JobFind/public');
define('ASSETS_URL', BASE_URL . '/assets');
 
// Avatar / upload configuration
define('AVATAR_UPLOAD_DIR', 'uploads/avatars'); // relative to public/
define('AVATAR_UPLOAD_PATH', __DIR__ . '/../public/' . AVATAR_UPLOAD_DIR);
define('AVATAR_MAX_SIZE', 2 * 1024 * 1024); // 2 MB
// allowed MIME types for avatars
define('AVATAR_ALLOWED_MIME', serialize(['image/png','image/jpeg','image/gif','image/webp','image/pjpeg','image/jpg']));
define('AVATAR_MAX_WIDTH', 800);
define('AVATAR_MAX_HEIGHT', 800);
define('AVATAR_THUMB_SIZE', 96);

// Company logo upload configuration
define('COMPANY_LOGO_UPLOAD_DIR', 'uploads/company-logos');
define('COMPANY_LOGO_UPLOAD_PATH', __DIR__ . '/../public/' . COMPANY_LOGO_UPLOAD_DIR);
define('COMPANY_LOGO_MAX_SIZE', 3 * 1024 * 1024); // 3 MB
define('COMPANY_LOGO_ALLOWED_MIME', serialize(['image/png','image/jpeg','image/gif','image/webp','image/pjpeg','image/jpg']));
define('COMPANY_LOGO_MAX_WIDTH', 600);
define('COMPANY_LOGO_MAX_HEIGHT', 600);
?>
