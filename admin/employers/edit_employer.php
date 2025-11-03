<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/models/Employer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
  header('Location: /JobFind/public/403.php');
  exit;
}

$employerModel = new Employer();
$id = (int)($_GET['id'] ?? 0);
$res = $employerModel->conn->query("SELECT * FROM employers WHERE id = $id");
$employer = $res->fetch_assoc();
if (!$employer) {
  header('Location: /JobFind/admin/employer/employers.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $company_name = $employerModel->conn->real_escape_string($_POST['company_name']);
  $website = $employerModel->conn->real_escape_string($_POST['website'] ?? '');
  $address = $employerModel->conn->real_escape_string($_POST['address'] ?? '');
  $about = $employerModel->conn->real_escape_string($_POST['about'] ?? '');
  $stmt = $employerModel->conn->prepare("UPDATE employers SET company_name=?, website=?, address=?, about=? WHERE id=?");
  $stmt->bind_param("ssssi", $company_name, $website, $address, $about, $id);
  $stmt->execute();
  header('Location: /JobFind/admin/employer/employers.php');
  exit;
}

ob_start();
?>
<div class="pagetitle">
  <h1>Sửa thông tin nhà tuyển dụng</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="/JobFind/admin/dashboard.php">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="/JobFind/admin/employer/employers.php">Nhà tuyển dụng</a></li>
      <li class="breadcrumb-item active">Sửa</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="card p-4 shadow-sm">
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Tên công ty</label>
        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($employer['company_name']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-control" value="<?= htmlspecialchars($employer['website']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Địa chỉ</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($employer['address']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Giới thiệu</label>
        <textarea name="about" class="form-control" rows="3"><?= htmlspecialchars($employer['about']) ?></textarea>
      </div>

      <div class="text-end">
        <a href="/JobFind/admin/employers/employers.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Lưu thay đổi</button>
      </div>
    </form>
  </div>
</section>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/layout.php';
