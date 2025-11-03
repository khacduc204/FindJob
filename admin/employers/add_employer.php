<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/models/Employer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
  header('Location: /JobFind/public/403.php');
  exit;
}

$employerModel = new Employer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = (int)$_POST['user_id'];
  $company_name = $employerModel->conn->real_escape_string($_POST['company_name']);
  $website = $employerModel->conn->real_escape_string($_POST['website'] ?? '');
  $address = $employerModel->conn->real_escape_string($_POST['address'] ?? '');
  $about = $employerModel->conn->real_escape_string($_POST['about'] ?? '');
  $employerModel->createForUser($user_id, $company_name, $website, $address, $about);
  header('Location: /JobFind/admin/employer/employers.php');
  exit;
}

$users = $employerModel->conn->query("SELECT id, email FROM users WHERE role_id = 2 ORDER BY id");

ob_start();
?>
<div class="pagetitle">
  <h1>Thêm nhà tuyển dụng</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/JobFind/admin/dashboard.php">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="/JobFind/admin/employer/employers.php">Nhà tuyển dụng</a></li>
      <li class="breadcrumb-item active">Thêm</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="card p-4 shadow-sm">
    <form method="POST">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Người dùng</label>
          <select name="user_id" class="form-select" required>
            <option value="">-- Chọn người dùng --</option>
            <?php while ($u = $users->fetch_assoc()): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['email']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tên công ty</label>
          <input type="text" name="company_name" class="form-control" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Địa chỉ</label>
        <input type="text" name="address" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Giới thiệu</label>
        <textarea name="about" class="form-control" rows="3"></textarea>
      </div>

      <div class="text-end">
        <a href="/JobFind/admin/employers/employers.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Huỷ</a>
        <button type="submit" class="btn btn-success" name="add"><i class="bi bi-save"></i> Lưu</button>
      </div>
    </form>
  </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layout.php';
