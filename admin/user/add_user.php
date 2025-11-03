<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/models/User.php';
require_once dirname(__DIR__, 2) . '/app/helpers/avatar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
  header('Location: /JobFind/public/403.php');
  exit;
}

$userModel = new User();
$message = '';
$uploadError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $name = trim($_POST['name']);
  $password = $_POST['password'];
  $role_id = (int)$_POST['role_id'];

  $newId = $userModel->create($email, $password, $role_id, $name);

  if ($newId) {
    if (!empty($_FILES['avatar']) && isset($_FILES['avatar']['error']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
      if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $err = null;
        $relative = handle_avatar_upload($_FILES['avatar'], $err);
        if ($relative) {
          $userModel->setAvatar($newId, $relative);
        } else {
          $uploadError = $err;
        }
      } else {
        $uploadError = avatar_upload_error_message($_FILES['avatar']['error']);
      }
    }
    if ($uploadError) {
      $message = 'Thêm người dùng thành công, nhưng ảnh đại diện chưa được lưu.';
    } else {
      $message = 'Thêm người dùng thành công!';
    }
  } else {
    $message = 'Không thể thêm người dùng (email có thể đã tồn tại).';
  }
}

// biến này dùng để layout.php hiển thị phần nội dung
ob_start();
?>

<div class="pagetitle">
  <h1>Thêm người dùng</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/JobFind/admin/dashboard.php">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="/JobFind/admin/user/users.php">Người dùng</a></li>
      <li class="breadcrumb-item active">Thêm mới</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="card p-4 shadow-sm">
    <?php if (!empty($message)): ?>
      <div class="alert alert-<?= strpos($message, 'thành công') ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php if (!$uploadError && strpos($message, 'thành công') !== false): ?>
        <script>
          setTimeout(() => window.location.href = '/JobFind/admin/user/users.php', 2000);
        </script>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($uploadError): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($uploadError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Tên người dùng</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email đăng nhập</label>
          <input type="email" name="email" class="form-control" required>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Mật khẩu</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Vai trò</label>
          <select name="role_id" class="form-select" required>
            <option value="">-- Chọn vai trò --</option>
            <option value="1">Admin</option>
            <option value="2">Nhà tuyển dụng</option>
            <option value="3">Ứng viên</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Ảnh đại diện (tuỳ chọn)</label>
        <input type="file" name="avatar" accept="image/*" class="form-control">
      </div>

      <div class="text-end">
        <a href="/JobFind/admin/user/users.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Quay lại
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-plus-circle"></i> Thêm người dùng
        </button>
      </div>
    </form>
  </div>
</section>

<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layout.php';
