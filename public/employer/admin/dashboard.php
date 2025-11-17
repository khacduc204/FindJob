<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/app/models/Employer.php';
require_once dirname(__DIR__, 3) . '/app/models/Job.php';

// Only employers
if (empty($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    header('Location: ' . BASE_URL . '/403.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$employerModel = new Employer();
$jobModel = new Job();
$employer = $employerModel->getByUserId($userId);
if (!$employer) {
    header('Location: ' . BASE_URL . '/employer/edit.php');
    exit;
}
$employerId = (int)$employer['id'];

// Stats
$jobs = $jobModel->getByEmployer($employerId);
$totalJobs = 0;
$published = 0;
$draft = 0;
$closed = 0;
if ($jobs) {
  while ($j = $jobs->fetch_assoc()) {
    $totalJobs++;
    $s = $j['status'] ?? 'draft';
    if ($s === 'published') $published++;
    if ($s === 'draft') $draft++;
    if ($s === 'closed') $closed++;
  }
  $jobs->free();
}

// Recent applicants (latest 5)
$recentApplicants = [];
$stmt = $jobModel->conn->prepare("SELECT a.*, j.title, c.user_id, u.email, u.name AS candidate_name FROM applications a JOIN jobs j ON j.id = a.job_id JOIN candidates c ON c.id = a.candidate_id JOIN users u ON u.id = c.user_id WHERE j.employer_id = ? ORDER BY a.applied_at DESC LIMIT 5");
if ($stmt !== false) {
    $stmt->bind_param('i', $employerId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $recentApplicants[] = $row;
        }
        $res->free();
    }
    $stmt->close();
}

$pageTitle = 'Bảng điều khiển nhà tuyển dụng';
$employerNavActive = 'dashboard';
$employerCompanyName = $employer['company_name'];
$_SESSION['employer_company_name'] = $employerCompanyName;
$_SESSION['employer_profile_url'] = BASE_URL . '/employer/show.php?id=' . $employerId;
$employerProfileUrl = BASE_URL . '/employer/show.php?id=' . $employerId;

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <span class="text-muted text-uppercase small fw-semibold">Xin chào trở lại</span>
    <h2 class="h4 mb-0">Tổng quan tuyển dụng</h2>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/employer/edit.php"><i class="fa-regular fa-pen-to-square me-2"></i>Chỉnh sửa hồ sơ</a>
    <a class="btn btn-success" href="<?= BASE_URL ?>/employer/admin/job_edit.php"><i class="fa-solid fa-circle-plus me-2"></i>Đăng tin mới</a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="ea-stat-card">
      <div class="label">Tổng số tin</div>
      <div class="value"><?= $totalJobs ?></div>
      <div class="text-muted small mt-1">Bao gồm cả tin nháp và đã đóng</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="ea-stat-card">
      <div class="label">Tin đang hiển thị</div>
      <div class="value text-success"><?= $published ?></div>
      <div class="text-muted small mt-1">Đang thu hút ứng viên</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="ea-stat-card">
      <div class="label">Ứng viên gần đây</div>
      <div class="value"><?= count($recentApplicants) ?></div>
      <div class="text-muted small mt-1">Trong 5 hồ sơ gần nhất</div>
    </div>
  </div>
</div>

<div class="ea-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="h5 mb-0">Ứng viên mới nhất</h3>
    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/employer/admin/applications.php">Xem tất cả</a>
  </div>
  <?php if (empty($recentApplicants)): ?>
    <div class="text-muted">Chưa có ứng viên ứng tuyển.</div>
  <?php else: ?>
    <div class="list-group list-group-flush">
      <?php foreach ($recentApplicants as $app): ?>
        <a class="list-group-item list-group-item-action py-3" href="<?= BASE_URL ?>/employer/admin/application_view.php?id=<?= (int)$app['id'] ?>">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
              <div class="fw-semibold"><?= htmlspecialchars($app['candidate_name'] ?? $app['email'] ?? 'Ứng viên') ?></div>
              <div class="small text-muted"><?= htmlspecialchars($app['email']) ?> • <?= htmlspecialchars($app['title']) ?></div>
            </div>
            <span class="small text-muted flex-shrink-0"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($app['applied_at'] ?? ''))) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="ea-card">
  <h3 class="h5 mb-3">Các hành động nhanh</h3>
  <div class="d-flex flex-wrap gap-3">
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/employer/admin/jobs.php"><i class="fa-solid fa-briefcase me-2"></i>Quản lý tin tuyển dụng</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/employer/admin/applications.php"><i class="fa-regular fa-id-card me-2"></i>Xem hồ sơ ứng viên</a>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/employer/show.php?id=<?= $employerId ?>" target="_blank" rel="noopener"><i class="fa-regular fa-eye me-2"></i>Xem hồ sơ công ty</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
