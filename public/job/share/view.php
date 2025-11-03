<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/app/models/Job.php';
require_once dirname(__DIR__, 3) . '/app/models/Employer.php';
require_once dirname(__DIR__, 3) . '/app/models/SavedJob.php';

$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($jobId <= 0) {
    header('Location: /JobFind/public/job/share/index.php');
    exit;
}

$jobModel = new Job();
$job = $jobModel->getById($jobId);
if (!$job || ($job['status'] ?? '') !== 'published') {
    header('Location: /JobFind/public/job/share/index.php');
    exit;
}

$employerModel = new Employer();
$employer = $employerModel->getById((int)$job['employer_id']);

function jf_job_format_description(?string $text): string {
    if (!$text) {
        return '<p class="text-muted">Nhà tuyển dụng chưa cập nhật mô tả chi tiết cho vị trí này.</p>';
    }
    $text = trim($text);
    if ($text === '') {
        return '<p class="text-muted">Nhà tuyển dụng chưa cập nhật mô tả chi tiết cho vị trí này.</p>';
    }

    $paragraphs = preg_split("/(\r?\n){2,}/", $text);
    $htmlParts = [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        $lines = preg_split("/\r?\n/", $paragraph);
        $listItems = [];
        $isList = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^[-*•]/', $line)) {
                $line = preg_replace('/^[-*•]\s*/', '', $line);
                $listItems[] = htmlspecialchars($line);
            } else {
                $isList = false;
                break;
            }
        }
        if ($isList && !empty($listItems)) {
            $htmlParts[] = '<ul class="mb-3">' . implode('', array_map(static fn($item) => '<li>' . $item . '</li>', $listItems)) . '</ul>';
        } else {
            $htmlParts[] = '<p>' . nl2br(htmlspecialchars($paragraph)) . '</p>';
        }
    }

    return implode('', $htmlParts);
}

function jf_job_time_ago(?string $date): string {
    if (!$date) {
        return 'Không xác định';
    }
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return $date;
    }
    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Vừa xong';
    }
    $minutes = floor($diff / 60);
    if ($minutes < 60) {
        return $minutes . ' phút trước';
    }
    $hours = floor($minutes / 60);
    if ($hours < 24) {
        return $hours . ' giờ trước';
    }
    $days = floor($hours / 24);
    if ($days === 1) {
        return '1 ngày trước';
    }
    if ($days < 7) {
        return $days . ' ngày trước';
    }
    $weeks = floor($days / 7);
    if ($weeks === 1) {
        return '1 tuần trước';
    }
    if ($weeks < 5) {
        return $weeks . ' tuần trước';
    }
    return date('d/m/Y', $timestamp);
}

$clientIp = null;
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $clientIp = trim($parts[0]);
} elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $clientIp = trim($_SERVER['HTTP_CLIENT_IP']);
} elseif (!empty($_SERVER['REMOTE_ADDR'])) {
    $clientIp = trim($_SERVER['REMOTE_ADDR']);
}
$clientIp = $clientIp !== '' ? $clientIp : null;
$jobModel->recordView($jobId, $clientIp);
$viewStats = $jobModel->getViewStats($jobId);

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = (int)($_SESSION['role_id'] ?? 0);
$canSaveJob = $userId > 0 && $userRole === 3;
$savedJobModel = $canSaveJob ? new SavedJob() : null;
$isSavedJob = $canSaveJob ? $savedJobModel->isSavedByUser($userId, $jobId) : false;

$jobShareFlash = $_SESSION['job_share_flash'] ?? null;
if ($jobShareFlash) {
    unset($_SESSION['job_share_flash']);
}

$currentUri = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/job/share/view.php?id=' . $jobId;
$locationText = trim((string)($job['location'] ?? ''));
if ($locationText === '') {
    $locationText = trim((string)($employer['address'] ?? ''));
}
$mapLocation = $locationText !== '' ? $locationText : 'Việt Nam';
$mapEmbedUrl = 'https://www.google.com/maps?q=' . urlencode($mapLocation) . '&output=embed';

$logoPath = trim((string)($employer['logo_path'] ?? ''));
$logoUrl = $logoPath !== '' ? BASE_URL . '/' . ltrim($logoPath, '/') : '';
$companyName = $employer['company_name'] ?? 'Nhà tuyển dụng JobFind';
$jobTitle = $job['title'] ?? 'Tin tuyển dụng';
$employmentType = $job['employment_type'] ?: 'Chưa cập nhật';
$salaryRange = $job['salary'] ?: 'Thỏa thuận';
$jobLocation = $job['location'] ?: ($employer['address'] ?? 'Toàn quốc');
$jobPostedAt = $job['created_at'] ?? null;
$jobUpdatedAt = $job['updated_at'] ?? $jobPostedAt;
$viewCount = isset($viewStats['total_views']) ? (int)$viewStats['total_views'] : 0;
$lastViewedAt = $viewStats['last_viewed_at'] ?? null;

$pageTitle = htmlspecialchars($jobTitle . ' | ' . $companyName);
$bodyClass = 'job-detail-page';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<main class="job-detail-page py-5">
  <div class="container">
    <?php if ($jobShareFlash): ?>
      <div class="alert alert-<?= htmlspecialchars($jobShareFlash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($jobShareFlash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
          <div class="d-flex align-items-center gap-3">
            <div class="job-logo-lg flex-shrink-0">
              <?php if ($logoUrl !== ''): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($companyName) ?>">
              <?php else: ?>
                <span class="job-logo-fallback"><?= htmlspecialchars(strtoupper(substr($companyName, 0, 2))) ?></span>
              <?php endif; ?>
            </div>
            <div>
              <h1 class="h3 mb-1 text-capitalize"><?= htmlspecialchars($jobTitle) ?></h1>
              <div class="text-muted fw-semibold mb-2"><?= htmlspecialchars($companyName) ?></div>
              <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-light text-success border border-success"><i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($jobLocation) ?></span>
                <span class="badge bg-light text-success border border-success"><i class="fa-solid fa-suitcase me-1"></i><?= htmlspecialchars($employmentType) ?></span>
                <span class="badge bg-light text-success border border-success"><i class="fa-solid fa-coins me-1"></i><?= htmlspecialchars($salaryRange) ?></span>
              </div>
            </div>
          </div>
          <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-3 w-100 w-lg-auto">
            <a class="btn btn-success btn-lg px-4 flex-grow-1" href="<?= BASE_URL ?>/account/login.php">
              <i class="fa-solid fa-paper-plane me-2"></i>Ứng tuyển ngay
            </a>
            <?php if ($canSaveJob): ?>
              <form action="<?= BASE_URL ?>/job/share/save.php" method="post" class="flex-grow-1">
                <input type="hidden" name="job_id" value="<?= $jobId ?>">
                <input type="hidden" name="return" value="<?= htmlspecialchars($currentUri) ?>">
                <input type="hidden" name="action" value="<?= $isSavedJob ? 'remove' : 'save' ?>">
                <button type="submit" class="btn <?= $isSavedJob ? 'btn-danger' : 'btn-outline-danger' ?> btn-lg w-100 d-flex align-items-center justify-content-center gap-2" title="<?= $isSavedJob ? 'Bỏ lưu việc làm' : 'Lưu việc làm' ?>">
                  <i class="fa-<?= $isSavedJob ? 'solid' : 'regular' ?> fa-heart"></i>
                  <?= $isSavedJob ? 'Đã lưu' : 'Lưu tin' ?>
                </button>
              </form>
            <?php else: ?>
              <a class="btn btn-outline-danger btn-lg flex-grow-1 d-flex align-items-center justify-content-center gap-2" href="<?= BASE_URL ?>/account/login.php" title="Đăng nhập để lưu việc">
                <i class="fa-regular fa-heart"></i>
                Lưu tin
              </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mt-4 text-muted small">
          <div><i class="fa-solid fa-calendar-days me-1 text-success"></i>Đăng ngày: <?= htmlspecialchars($jobPostedAt ? date('d/m/Y', strtotime($jobPostedAt)) : 'Chưa cập nhật') ?></div>
          <?php if ($jobUpdatedAt && $jobUpdatedAt !== $jobPostedAt): ?>
            <div><i class="fa-solid fa-rotate me-1 text-success"></i>Cập nhật <?= htmlspecialchars(jf_job_time_ago($jobUpdatedAt)) ?></div>
          <?php endif; ?>
          <div><i class="fa-solid fa-eye me-1 text-success"></i><?= number_format($viewCount) ?> lượt xem</div>
          <?php if ($lastViewedAt): ?>
            <div><i class="fa-solid fa-clock me-1 text-success"></i>Lượt xem gần nhất <?= htmlspecialchars(jf_job_time_ago($lastViewedAt)) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <section class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4 p-lg-5">
            <h2 class="h5 mb-4">Chi tiết công việc</h2>
            <div class="job-description rich-text">
              <?= jf_job_format_description($job['description'] ?? '') ?>
            </div>
          </div>
        </section>

        <section class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4 p-lg-5">
            <h2 class="h5 mb-3">Thông tin bổ sung</h2>
            <div class="row row-cols-1 row-cols-md-2 gy-3">
              <div class="d-flex align-items-start gap-3">
                <div class="text-success"><i class="fa-solid fa-graduation-cap"></i></div>
                <div>
                  <div class="small text-muted">Kinh nghiệm</div>
                  <div class="fw-semibold"><?= htmlspecialchars($job['experience_level'] ?? 'Không yêu cầu / Chưa cập nhật') ?></div>
                </div>
              </div>
              <div class="d-flex align-items-start gap-3">
                <div class="text-success"><i class="fa-solid fa-users"></i></div>
                <div>
                  <div class="small text-muted">Số lượng tuyển</div>
                  <div class="fw-semibold"><?= htmlspecialchars($job['headcount'] ?? 'Không giới hạn / Chưa cập nhật') ?></div>
                </div>
              </div>
              <div class="d-flex align-items-start gap-3">
                <div class="text-success"><i class="fa-solid fa-language"></i></div>
                <div>
                  <div class="small text-muted">Ngôn ngữ</div>
                  <div class="fw-semibold"><?= htmlspecialchars($job['language_requirement'] ?? 'Chưa cập nhật') ?></div>
                </div>
              </div>
              <div class="d-flex align-items-start gap-3">
                <div class="text-success"><i class="fa-solid fa-calendar-check"></i></div>
                <div>
                  <div class="small text-muted">Hạn nộp hồ sơ</div>
                  <div class="fw-semibold"><?= htmlspecialchars(isset($job['deadline']) ? date('d/m/Y', strtotime($job['deadline'])) : 'Chưa cập nhật') ?></div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="card border-0 shadow-sm">
          <div class="card-body p-4 p-lg-5">
            <h2 class="h5 mb-3">Cách ứng tuyển</h2>
            <ul class="mb-3 text-muted">
              <li>Nhấn nút <strong>Ứng tuyển ngay</strong> để đăng nhập hoặc tạo tài khoản JobFind.</li>
              <li>Cập nhật CV mới nhất và gửi kèm thư giới thiệu (nếu có).</li>
              <li>Theo dõi email/điện thoại để nhận phản hồi từ nhà tuyển dụng.</li>
            </ul>
            <p class="mb-0 small text-muted">Nếu bạn cần hỗ trợ, vui lòng liên hệ hotline hoặc email hỗ trợ của JobFind để được giải đáp nhanh chóng.</p>
          </div>
        </section>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <h5 class="card-title">Thông tin chung</h5>
            <ul class="list-unstyled mb-0 small text-muted">
              <li class="d-flex justify-content-between mb-2"><span>Loại công việc</span><strong><?= htmlspecialchars($employmentType) ?></strong></li>
              <li class="d-flex justify-content-between mb-2"><span>Địa điểm</span><strong><?= htmlspecialchars($jobLocation) ?></strong></li>
              <li class="d-flex justify-content-between mb-2"><span>Mức lương</span><strong><?= htmlspecialchars($salaryRange) ?></strong></li>
              <li class="d-flex justify-content-between mb-2"><span>Ngày đăng</span><strong><?= htmlspecialchars($jobPostedAt ? date('d/m/Y', strtotime($jobPostedAt)) : 'Chưa cập nhật') ?></strong></li>
              <li class="d-flex justify-content-between"><span>Lượt xem</span><strong><?= number_format($viewCount) ?></strong></li>
            </ul>
          </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <h5 class="card-title mb-3">Văn phòng &amp; địa chỉ</h5>
            <p class="small text-muted mb-3">
              <i class="fa-solid fa-location-dot text-success me-2"></i>
              <?= $locationText !== '' ? htmlspecialchars($locationText) : 'Nhà tuyển dụng chưa cập nhật địa chỉ cụ thể.' ?>
            </p>
            <div class="ratio ratio-4x3 rounded overflow-hidden shadow-sm">
              <iframe src="<?= htmlspecialchars($mapEmbedUrl) ?>" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body p-4">
            <h5 class="card-title mb-3">Về nhà tuyển dụng</h5>
            <p class="mb-2 fw-semibold text-capitalize"><?= htmlspecialchars($companyName) ?></p>
            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($employer['about'] ?? 'Doanh nghiệp đang cập nhật thông tin giới thiệu.')) ?></p>
            <?php if (!empty($employer['website'])): ?>
              <a class="small d-inline-flex align-items-center gap-2 mt-3" href="<?= htmlspecialchars($employer['website']) ?>" target="_blank" rel="noopener">
                <i class="fa-solid fa-globe text-success"></i>Website chính thức
              </a>
            <?php endif; ?>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body p-4 d-grid gap-2">
            <a class="btn btn-success" href="<?= BASE_URL ?>/account/login.php">Ứng tuyển ngay</a>
            <a class="btn btn-outline-success" href="<?= BASE_URL ?>/job/share/hot.php">Xem thêm việc làm hot</a>
            <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/job/share/index.php">Quay lại danh sách</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
