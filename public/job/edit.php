<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/controllers/JobController.php';

$userId = $_SESSION['user_id'] ?? null;
$roleId = $_SESSION['role_id'] ?? null;
if (!$userId) {
  header('Location: ' . BASE_URL . '/account/login.php');
  exit;
}
if ((int)$roleId !== 2) {
  header('Location: ' . BASE_URL . '/403.php');
  exit;
}

$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($jobId <= 0) {
    header('Location: ' . BASE_URL . '/job/index.php');
    exit;
}

$jobController = new JobController();
$employer = $jobController->ensureEmployer((int)$userId);
if (!$employer) {
    header('Location: ' . BASE_URL . '/403.php');
    exit;
}

$job = $jobController->getJobForEmployer((int)$userId, $jobId);
if (!$job) {
    header('Location: ' . BASE_URL . '/403.php');
    exit;
}

$errors = [];
$values = [
  'title' => $job['title'] ?? '',
  'location' => $job['location'] ?? '',
  'salary' => $job['salary'] ?? '',
  'employment_type' => $job['employment_type'] ?? '',
  'status' => $job['status'] ?? 'draft',
  'description' => $job['description'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['title'] = trim($_POST['title'] ?? '');
    $values['location'] = trim($_POST['location'] ?? '');
    $values['salary'] = trim($_POST['salary'] ?? '');
    $values['employment_type'] = trim($_POST['employment_type'] ?? '');
    $values['description'] = trim($_POST['description'] ?? '');
  $values['status'] = $job['status'] ?? 'draft';

    if ($values['title'] === '') {
        $errors['title'] = 'Vui lòng nhập tiêu đề tin tuyển dụng.';
    }
    if ($values['description'] === '') {
        $errors['description'] = 'Vui lòng mô tả chi tiết công việc.';
    }

    $allowedStatuses = ['draft', 'published', 'closed'];
    if (!in_array($values['status'], $allowedStatuses, true)) {
        $values['status'] = 'draft';
    }

    if (empty($errors)) {
        $updated = $jobController->updateJob(
            (int)$userId,
            $jobId,
            $values['title'],
            $values['description'],
            $values['location'] !== '' ? $values['location'] : null,
            $values['salary'] !== '' ? $values['salary'] : null,
            $values['employment_type'] !== '' ? $values['employment_type'] : null,
            $values['status']
        );

        if ($updated) {
            $_SESSION['job_flash'] = [
                'type' => 'success',
                'message' => 'Tin tuyển dụng đã được cập nhật.'
            ];
            header('Location: ' . BASE_URL . '/job/index.php');
            exit;
        }

        $errors['general'] = 'Không thể cập nhật tin tuyển dụng. Vui lòng thử lại.';
    }
}

$pageTitle = 'Chỉnh sửa tin tuyển dụng | JobFind';
$bodyClass = 'job-manage-page';
require_once dirname(__DIR__) . '/includes/header.php';

$employmentOptions = ['Full-time', 'Part-time', 'Internship', 'Contract', 'Freelance'];
?>

<main class="container py-5">
  <div class="row mb-4">
    <div class="col-12 col-lg-8">
      <h1 class="fw-semibold mb-1">Chỉnh sửa tin tuyển dụng</h1>
      <p class="text-muted mb-2">Cập nhật thông tin để đảm bảo ứng viên nắm rõ yêu cầu mới nhất.</p>
      <div class="alert alert-info mb-0">
        Trạng thái hiện tại: <strong><?= htmlspecialchars(($values['status'] === 'draft') ? 'Chờ duyệt' : ($values['status'] === 'published' ? 'Đang hiển thị' : 'Đã đóng')) ?></strong>. Liên hệ quản trị viên nếu cần thay đổi trạng thái.
      </div>
    </div>
    <div class="col-12 col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="<?= BASE_URL ?>/job/index.php" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Quay lại danh sách
      </a>
    </div>
  </div>

  <?php if (!empty($errors['general'])) : ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
  <?php endif; ?>

  <form method="post" class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <div class="mb-3">
        <label for="jobTitle" class="form-label">Tiêu đề<span class="text-danger">*</span></label>
        <input type="text" id="jobTitle" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($values['title']) ?>">
        <?php if (isset($errors['title'])) : ?><div class="invalid-feedback"><?= htmlspecialchars($errors['title']) ?></div><?php endif; ?>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label for="jobLocation" class="form-label">Địa điểm</label>
          <input type="text" id="jobLocation" name="location" class="form-control" value="<?= htmlspecialchars($values['location']) ?>">
        </div>
        <div class="col-md-6">
          <label for="jobSalary" class="form-label">Mức lương</label>
          <input type="text" id="jobSalary" name="salary" class="form-control" value="<?= htmlspecialchars($values['salary']) ?>">
        </div>
      </div>

      <div class="row g-3 mt-0 mt-md-1">
        <div class="col-md-6">
          <label for="employmentType" class="form-label">Hình thức làm việc</label>
          <select id="employmentType" name="employment_type" class="form-select">
            <option value="">-- Chọn hình thức --</option>
            <?php foreach ($employmentOptions as $option) : ?>
              <option value="<?= htmlspecialchars($option) ?>" <?= $values['employment_type'] === $option ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Trạng thái</label>
          <div class="form-control-plaintext">
            <?php if ($values['status'] === 'published') : ?>
              <span class="badge bg-success">Đang hiển thị</span>
            <?php elseif ($values['status'] === 'closed') : ?>
              <span class="badge bg-dark">Đã đóng</span>
            <?php else : ?>
              <span class="badge bg-secondary">Chờ duyệt</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <label for="jobDescription" class="form-label">Mô tả công việc<span class="text-danger">*</span></label>
        <textarea id="jobDescription" name="description" rows="8" class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"><?= htmlspecialchars($values['description']) ?></textarea>
        <?php if (isset($errors['description'])) : ?><div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
      <a href="<?= BASE_URL ?>/job/index.php" class="btn btn-light">Huỷ</a>
      <button type="submit" class="btn btn-success">Lưu thay đổi</button>
    </div>
  </form>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
