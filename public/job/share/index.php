<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/app/models/Job.php';
require_once dirname(__DIR__, 3) . '/app/models/SavedJob.php';

$jobModel = new Job();
$categoryOptions = $jobModel->getAllCategories();
$categoryLookup = [];
foreach ($categoryOptions as $categoryOption) {
  $categoryId = (int)($categoryOption['id'] ?? 0);
  if ($categoryId > 0) {
    $categoryLookup[$categoryId] = $categoryOption;
  }
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = (int)($_SESSION['role_id'] ?? 0);
$canSaveJobs = $userId > 0 && $userRole === 3;
$savedJobModel = $canSaveJobs ? new SavedJob() : null;
$savedJobIds = $canSaveJobs ? $savedJobModel->getSavedJobIdsForUser($userId) : [];

$filters = [
    'keyword' => trim($_GET['keyword'] ?? ''),
    'location' => trim($_GET['location'] ?? ''),
  'employment_type' => trim($_GET['type'] ?? ''),
  'category' => (int)($_GET['category'] ?? 0)
];

if ($filters['category'] > 0 && !isset($categoryLookup[$filters['category']])) {
  $filters['category'] = 0;
}

$allowedTypes = ['Full-time', 'Part-time', 'Internship', 'Contract', 'Freelance'];
if ($filters['employment_type'] !== '' && !in_array($filters['employment_type'], $allowedTypes, true)) {
    $filters['employment_type'] = '';
}

$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));

$savedParam = isset($_GET['saved']) ? trim((string)$_GET['saved']) : '';
if ($savedParam !== '' && !$canSaveJobs) {
  $_SESSION['job_share_flash'] = [
    'type' => 'warning',
    'message' => 'Vui lòng đăng nhập bằng tài khoản ứng viên để xem việc làm đã lưu.'
  ];
  header('Location: ' . BASE_URL . '/account/login.php');
  exit;
}

$showSaved = $canSaveJobs && $savedParam !== '' && $savedParam !== '0';

$jobs = [];
$queryError = null;
$totalJobs = 0;

$conditions = ["j.status = 'published'", "(j.deadline IS NULL OR j.deadline >= CURDATE())"];
$params = [];
$types = '';

if ($filters['keyword'] !== '') {
  $conditions[] = '(j.title LIKE ? OR e.company_name LIKE ? OR j.description LIKE ?)';
  $like = '%' . $filters['keyword'] . '%';
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= 'sss';
}

if ($filters['location'] !== '') {
  $conditions[] = '(j.location LIKE ? OR e.address LIKE ?)';
  $like = '%' . $filters['location'] . '%';
  $params[] = $like;
  $params[] = $like;
  $types .= 'ss';
}

if ($filters['employment_type'] !== '') {
  $conditions[] = 'j.employment_type = ?';
  $params[] = $filters['employment_type'];
  $types .= 's';
}

if ($filters['category'] > 0) {
  $conditions[] = 'EXISTS (SELECT 1 FROM job_category_map m WHERE m.job_id = j.id AND m.category_id = ?)';
  $params[] = $filters['category'];
  $types .= 'i';
}

if ($showSaved && !empty($savedJobIds)) {
  $placeholders = implode(',', array_fill(0, count($savedJobIds), '?'));
  $conditions[] = "j.id IN ($placeholders)";
  foreach ($savedJobIds as $savedId) {
    $params[] = $savedId;
    $types .= 'i';
  }
}

$totalPages = 1;

if ($showSaved && empty($savedJobIds)) {
  $page = 1;
} else {
  $whereSql = 'WHERE ' . implode(' AND ', $conditions);
  $countSql = "SELECT COUNT(*) AS total FROM jobs j INNER JOIN employers e ON e.id = j.employer_id $whereSql";

  if ($types === '') {
    $countResult = $jobModel->conn->query($countSql);
    if ($countResult instanceof mysqli_result) {
      $row = $countResult->fetch_assoc();
      $totalJobs = (int)($row['total'] ?? 0);
      $countResult->free();
    }
  } else {
    $countStmt = $jobModel->conn->prepare($countSql);
    if ($countStmt !== false) {
      $countStmt->bind_param($types, ...$params);
      if ($countStmt->execute()) {
        $countResult = $countStmt->get_result();
        if ($countResult) {
          $row = $countResult->fetch_assoc();
          $totalJobs = (int)($row['total'] ?? 0);
          $countResult->free();
        }
      }
      $countStmt->close();
    }
  }

  $totalPages = $totalJobs > 0 ? (int)ceil($totalJobs / $perPage) : 1;
  if ($page > $totalPages) {
    $page = $totalPages;
  }
  $offset = ($page - 1) * $perPage;

  $dataSql = "SELECT j.id, j.title, j.location, j.salary, j.employment_type, j.quantity, j.deadline, j.created_at,
             e.company_name, e.logo_path
        FROM jobs j
        INNER JOIN employers e ON e.id = j.employer_id
        $whereSql
        ORDER BY j.created_at DESC
        LIMIT ? OFFSET ?";

  $dataTypes = $types . 'ii';
  $dataParams = $params;
  $dataParams[] = $perPage;
  $dataParams[] = $offset;

  $stmt = $jobModel->conn->prepare($dataSql);
  if ($stmt === false) {
    $queryError = $jobModel->conn->error;
  } else {
    $stmt->bind_param($dataTypes, ...$dataParams);
    if ($stmt->execute()) {
      $result = $stmt->get_result();
      if ($result) {
        while ($row = $result->fetch_assoc()) {
          $jobs[] = $row;
        }
        $result->free();
      }
    } else {
      $queryError = $stmt->error;
    }
    $stmt->close();
  }
}

$displayedJobs = count($jobs);
$jobCategoryMap = [];
if (!empty($jobs)) {
  $jobIds = [];
  foreach ($jobs as $jobRow) {
    $jobIds[] = (int)($jobRow['id'] ?? 0);
  }
  $jobIds = array_values(array_filter(array_unique($jobIds)));
  if (!empty($jobIds)) {
    $jobCategoryMap = $jobModel->getCategoriesForJobs($jobIds);
  }
}
$locationSuggestion = '';
if ($filters['location'] === '' && $displayedJobs > 0) {
    $cityCounts = [];
    foreach ($jobs as $job) {
        $city = trim((string)($job['location'] ?? ''));
        if ($city !== '') {
            $cityCounts[$city] = ($cityCounts[$city] ?? 0) + 1;
        }
    }
    if (!empty($cityCounts)) {
        arsort($cityCounts);
        $locationSuggestion = array_key_first($cityCounts);
    }
}

$fullTimeCount = count(array_filter($jobs, static fn($job) => ($job['employment_type'] ?? '') === 'Full-time'));
$remoteCount = count(array_filter($jobs, static fn($job) => stripos((string)($job['location'] ?? ''), 'remote') !== false));
$hasFilters = $filters['keyword'] !== '' || $filters['location'] !== '' || $filters['employment_type'] !== '' || $filters['category'] > 0;

$jobShareFlash = $_SESSION['job_share_flash'] ?? null;
if ($jobShareFlash) {
    unset($_SESSION['job_share_flash']);
}

$currentUri = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/job/share/index.php';
$paginationBase = BASE_URL . '/job/share/index.php';
$paginationQuery = $_GET;
unset($paginationQuery['page']);
$buildPageUrl = static function (int $pageNumber, string $base, array $query): string {
    $query['page'] = $pageNumber;
    $queryString = http_build_query($query);
    if ($queryString === '') {
        return $base . '?page=' . $pageNumber;
    }
    return $base . '?' . $queryString;
};

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
    return 'Vừa đăng';
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

$pageTitle = $showSaved ? 'Việc làm đã lưu | JobFind' : 'Danh sách việc làm mới nhất | JobFind';
$headingTitle = $showSaved ? 'Việc làm đã lưu' : 'Việc làm mới nhất';
$summaryUnit = $showSaved ? 'việc đã lưu' : 'vị trí';
$totalLabel = $showSaved ? 'Tổng việc đã lưu' : 'Tổng tin phù hợp';
$resetFiltersUrl = $showSaved ? BASE_URL . '/job/share/index.php?saved=1' : BASE_URL . '/job/share/index.php';
$ctaUrl = $showSaved ? BASE_URL . '/job/share/index.php' : BASE_URL . '/employer/index.php';
$ctaLabel = $showSaved ? 'Khám phá việc làm mới nhất' : 'Khám phá nhà tuyển dụng';

$bodyClass = 'job-listing-page';
$additionalCSS = $additionalCSS ?? [];
$additionalCSS[] = '<link rel="stylesheet" href="' . ASSETS_URL . '/css/home.css">';
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>

<main class="job-listing-page py-5">
  <div class="container">
    <?php if ($jobShareFlash): ?>
      <div class="alert alert-<?= htmlspecialchars($jobShareFlash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($jobShareFlash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <div class="row g-4">
      <div class="col-lg-3">
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-body">
            <h5 class="card-title">Bộ lọc</h5>
            <form class="job-filter-form" method="get">
              <?php if ($showSaved): ?>
                <input type="hidden" name="saved" value="1">
              <?php endif; ?>
              <div class="mb-3">
                <label class="form-label" for="keyword">Từ khóa</label>
                <input type="text" id="keyword" name="keyword" value="<?= htmlspecialchars($filters['keyword']) ?>" class="form-control" placeholder="Chức danh, công ty, kỹ năng">
              </div>
              <div class="mb-3">
                <label class="form-label" for="location">Địa điểm</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($filters['location']) ?>" class="form-control" placeholder="Ví dụ: Hà Nội">
                <?php if ($locationSuggestion !== ''): ?>
                  <div class="form-text">Gợi ý: <?= htmlspecialchars($locationSuggestion) ?></div>
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <label class="form-label" for="category">Ngành nghề</label>
                <select id="category" name="category" class="form-select">
                  <option value="0" <?= $filters['category'] === 0 ? 'selected' : '' ?>>Tất cả</option>
                  <?php foreach ($categoryOptions as $category): ?>
                    <?php $categoryId = (int)($category['id'] ?? 0); ?>
                    <?php if ($categoryId <= 0) { continue; } ?>
                    <option value="<?= $categoryId ?>" <?= $filters['category'] === $categoryId ? 'selected' : '' ?>><?= htmlspecialchars($category['name'] ?? ('Ngành nghề #' . $categoryId)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label" for="type">Hình thức làm việc</label>
                <select id="type" name="type" class="form-select">
                  <option value="">Tất cả</option>
                  <?php foreach ($allowedTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $filters['employment_type'] === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success">Áp dụng</button>
                <a href="<?= htmlspecialchars($resetFiltersUrl) ?>" class="btn btn-light">Xóa bộ lọc</a>
              </div>
            </form>
          </div>
        </div>
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h6 class="fw-semibold mb-3">Số liệu nhanh</h6>
            <ul class="list-unstyled mb-0 text-muted small">
              <li class="d-flex justify-content-between mb-2"><span><?= htmlspecialchars($totalLabel) ?></span><strong><?= number_format($totalJobs) ?></strong></li>
              <li class="d-flex justify-content-between mb-2"><span>Hiển thị trên trang</span><strong><?= $displayedJobs ?></strong></li>
              <li class="d-flex justify-content-between mb-2"><span>Làm toàn thời gian</span><strong><?= $fullTimeCount ?></strong></li>
              <li class="d-flex justify-content-between mb-2"><span>Làm từ xa</span><strong><?= $remoteCount ?></strong></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-lg-9">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
          <div>
            <h1 class="h3 mb-1"><?= htmlspecialchars($headingTitle) ?></h1>
            <p class="text-muted mb-0">
              Đang hiển thị <strong><?= $displayedJobs ?></strong> / <strong><?= number_format($totalJobs) ?></strong> <?= htmlspecialchars($summaryUnit) ?><?= $filters['keyword'] !== '' ? ' cho từ khóa "' . htmlspecialchars($filters['keyword']) . '"' : '' ?>.
            </p>
          </div>
          <a class="btn btn-outline-success" href="<?= htmlspecialchars($ctaUrl) ?>"><?= htmlspecialchars($ctaLabel) ?></a>
        </div>

        <?php if ($queryError): ?>
          <div class="alert alert-danger">Không thể tải dữ liệu việc làm. Vui lòng thử lại sau. (<?= htmlspecialchars($queryError) ?>)</div>
        <?php elseif (empty($jobs)): ?>
          <div class="alert alert-light border text-center py-5">
            <?php if ($showSaved): ?>
              <?php if ($hasFilters): ?>
                <h5 class="fw-semibold mb-2">Không có việc làm đã lưu phù hợp</h5>
                <p class="text-muted">Không có việc làm đã lưu nào khớp với bộ lọc hiện tại. Hãy thử tinh chỉnh lại.</p>
                <a class="btn btn-success" href="<?= htmlspecialchars($resetFiltersUrl) ?>">Xóa bộ lọc</a>
              <?php else: ?>
                <h5 class="fw-semibold mb-2">Bạn chưa lưu việc làm nào</h5>
                <p class="text-muted">Nhấn biểu tượng trái tim trên mỗi tin tuyển dụng để lưu và quản lý trong danh sách này.</p>
                <a class="btn btn-success" href="<?= BASE_URL ?>/job/share/index.php">Khám phá việc làm</a>
              <?php endif; ?>
            <?php else: ?>
              <h5 class="fw-semibold mb-2">Hiện chưa có việc làm phù hợp</h5>
              <p class="text-muted">Hãy điều chỉnh bộ lọc hoặc quay lại sau để xem thêm cơ hội mới.</p>
              <a class="btn btn-success" href="<?= htmlspecialchars($resetFiltersUrl) ?>">Xóa bộ lọc</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="row row-cols-1 gy-4">
            <?php foreach ($jobs as $job): ?>
              <?php
                $logoUrl = '';
                $logoPath = trim((string)($job['logo_path'] ?? ''));
                if ($logoPath !== '') {
                    $logoUrl = BASE_URL . '/' . ltrim($logoPath, '/');
                }
                $companyName = $job['company_name'] ?? 'Nhà tuyển dụng JobFind';
                $employmentType = $job['employment_type'] ?: 'Full-time';
                $location = $job['location'] ?: 'Toàn quốc';
                $salary = $job['salary'] ?: 'Thỏa thuận';
                $postedAgo = jf_job_time_ago($job['created_at'] ?? null);
                $jobId = (int)$job['id'];
                $isSaved = $canSaveJobs && in_array($jobId, $savedJobIds, true);
                $deadlineDate = $job['deadline'] ? date('d/m/Y', strtotime($job['deadline'])) : null;
                $jobCategories = $jobCategoryMap[$jobId] ?? [];
              ?>
              <div class="col">
                <article class="card shadow-sm border-0 h-100">
                  <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                      <div class="d-flex align-items-center gap-3">
                        
                        <div>
                          <h3 class="h5 mb-1">
                            <a href="<?= BASE_URL ?>/job/share/view.php?id=<?= $jobId ?>" class="text-decoration-none"><?= htmlspecialchars($job['title']) ?></a>
                          </h3>
                          <div class="text-muted small fw-semibold"><?= htmlspecialchars($companyName) ?></div>
                        </div>
                      </div>
                      <div class="ms-3">
                        <?php if ($canSaveJobs): ?>
                          <form action="<?= BASE_URL ?>/job/share/save.php" method="post" class="job-save-form">
                            <input type="hidden" name="job_id" value="<?= $jobId ?>">
                            <input type="hidden" name="return" value="<?= htmlspecialchars($currentUri) ?>">
                            <input type="hidden" name="action" value="<?= $isSaved ? 'remove' : 'save' ?>">
                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none <?= $isSaved ? 'text-danger' : 'text-muted' ?>" title="<?= $isSaved ? 'Bỏ lưu việc làm' : 'Lưu việc làm' ?>" aria-label="<?= $isSaved ? 'Bỏ lưu việc làm' : 'Lưu việc làm' ?>">
                              <i class="fa-<?= $isSaved ? 'solid' : 'regular' ?> fa-heart fa-lg"></i>
                            </button>
                          </form>
                        <?php else: ?>
                          <a href="<?= BASE_URL ?>/account/login.php" class="btn btn-sm btn-link p-0 text-muted" title="Đăng nhập để lưu việc" aria-label="Đăng nhập để lưu việc">
                            <i class="fa-regular fa-heart fa-lg"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="row row-cols-1 row-cols-md-3 g-3 small text-muted mb-3">
                      <div><i class="fa-solid fa-location-dot me-2 text-success"></i><?= htmlspecialchars($location) ?></div>
                      <div><i class="fa-solid fa-coins me-2 text-success"></i><?= htmlspecialchars($salary) ?></div>
                      <div><i class="fa-solid fa-suitcase me-2 text-success"></i><?= htmlspecialchars($employmentType) ?></div>
                    </div>
                    <?php if (!empty($jobCategories)): ?>
                      <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($jobCategories as $category): ?>
                          <span class="badge bg-success bg-opacity-10 text-success border border-success"><?= htmlspecialchars($category['name']) ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
                      <span><i class="fa-solid fa-users me-2 text-success"></i><?= $job['quantity'] ? (int)$job['quantity'] . ' vị trí' : 'Không giới hạn' ?></span>
                      <span><i class="fa-solid fa-calendar-day me-2 text-success"></i><?= $deadlineDate ? 'Hạn ' . htmlspecialchars($deadlineDate) : 'Hạn nộp linh hoạt' ?></span>
                    </div>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">
                      <span class="badge bg-light text-dark"><?= htmlspecialchars($postedAgo) ?></span>
                      <a href="<?= BASE_URL ?>/job/share/view.php?id=<?= $jobId ?>" class="btn btn-outline-success mt-3 mt-sm-0">
                        Xem chi tiết
                        <i class="fa-solid fa-arrow-right ms-2"></i>
                      </a>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
            <?php
              $startPage = max(1, $page - 2);
              $endPage = min($totalPages, $startPage + 4);
              $startPage = max(1, $endPage - 4);
            ?>
            <nav class="mt-4" aria-label="Phân trang việc làm">
              <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= $page <= 1 ? '#' : $buildPageUrl($page - 1, $paginationBase, $paginationQuery) ?>" tabindex="<?= $page <= 1 ? '-1' : '0' ?>" aria-label="Trang trước">
                    &laquo;
                  </a>
                </li>
                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                  <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $buildPageUrl($p, $paginationBase, $paginationQuery) ?>"><?= $p ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="<?= $page >= $totalPages ? '#' : $buildPageUrl($page + 1, $paginationBase, $paginationQuery) ?>" tabindex="<?= $page >= $totalPages ? '-1' : '0' ?>" aria-label="Trang sau">
                    &raquo;
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>
