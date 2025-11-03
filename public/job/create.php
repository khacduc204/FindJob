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

$jobController = new JobController();
$employer = $jobController->ensureEmployer((int)$userId);
if (!$employer) {
    header('Location: ' . BASE_URL . '/403.php');
    exit;
}

$errors = [];
$values = [
  'title' => '',
  'location' => '',
  'salary' => '',
  'employment_type' => 'Full-time',
  'status' => 'draft',
  'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['title'] = trim($_POST['title'] ?? '');
    $values['location'] = trim($_POST['location'] ?? '');
    $values['salary'] = trim($_POST['salary'] ?? '');
    $values['employment_type'] = trim($_POST['employment_type'] ?? 'Full-time');
    $values['description'] = trim($_POST['description'] ?? '');

    if ($values['title'] === '') {
        $errors['title'] = 'Vui lòng nhập tiêu đề tin tuyển dụng.';
    }
    if ($values['description'] === '') {
        $errors['description'] = 'Vui lòng mô tả chi tiết công việc.';
    }

    $values['status'] = 'draft';

    if (empty($errors)) {
        $jobId = $jobController->createJob(
            (int)$userId,
            $values['title'],
            $values['description'],
            $values['location'] !== '' ? $values['location'] : null,
            $values['salary'] !== '' ? $values['salary'] : null,
            $values['employment_type'] !== '' ? $values['employment_type'] : null,
            'draft'
        );

        if ($jobId) {
            $_SESSION['job_flash'] = [
                'type' => 'success',
                'message' => 'Tin tuyển dụng đã được tạo và sẽ hiển thị sau khi quản trị viên phê duyệt.'
            ];
            header('Location: ' . BASE_URL . '/job/index.php');
            exit;
        }

        $errors['general'] = 'Không thể lưu tin tuyển dụng. Vui lòng thử lại.';
    }
}

$pageTitle = 'Đăng tin tuyển dụng mới | JobFind';
$bodyClass = 'job-manage-page';
require_once dirname(__DIR__) . '/includes/header.php';

$employmentOptions = ['Full-time', 'Part-time', 'Internship', 'Contract', 'Freelance'];
?>

<main class="container py-5">
  <div class="row mb-4">
    <div class="col-12 col-lg-8">
      <h1 class="fw-semibold mb-1">Đăng tin tuyển dụng mới</h1>
      <p class="text-muted mb-0">Mô tả chi tiết vị trí để thu hút ứng viên phù hợp.</p>
      <div class="alert alert-warning mt-3">
        Tin mới sẽ ở trạng thái <strong>chờ duyệt</strong>. Quản trị viên sẽ kiểm tra trước khi tin được hiển thị với ứng viên.
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
        <input type="text" id="jobTitle" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($values['title']) ?>" placeholder="Ví dụ: Chuyên viên Marketing Digital">
        <?php if (isset($errors['title'])) : ?><div class="invalid-feedback"><?= htmlspecialchars($errors['title']) ?></div><?php endif; ?>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label for="jobLocation" class="form-label">Địa điểm</label>
          <input type="text" id="jobLocation" name="location" class="form-control" value="<?= htmlspecialchars($values['location']) ?>" placeholder="Ví dụ: Hà Nội hoặc Remote">
        </div>
        <div class="col-md-6">
          <label for="jobSalary" class="form-label">Mức lương</label>
          <input type="text" id="jobSalary" name="salary" class="form-control" value="<?= htmlspecialchars($values['salary']) ?>" placeholder="Ví dụ: 15 - 25 triệu">
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
      </div>

      <div class="mt-3">
        <label for="jobDescription" class="form-label">Mô tả công việc<span class="text-danger">*</span></label>
        <textarea id="jobDescription" name="description" rows="8" class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" placeholder="Nêu rõ trách nhiệm, yêu cầu và quyền lợi của vị trí."><?= htmlspecialchars($values['description']) ?></textarea>
        <?php if (isset($errors['description'])) : ?><div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2 p-3">
      <a href="<?= BASE_URL ?>/job/index.php" class="btn btn-light">Huỷ</a>
      <button type="submit" class="btn btn-success">Lưu tin tuyển dụng</button>
    </div>
  </form>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
