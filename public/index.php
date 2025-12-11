<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/Job.php';
require_once __DIR__ . '/../app/models/Employer.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/SavedJob.php';

if (!function_exists('jf_format_metric')) {
  function jf_format_metric($value) {
    $value = (int)$value;
    if ($value <= 0) {
      return '0';
    }
    if ($value >= 1000000) {
      $short = round($value / 1000000, 1);
      $short = rtrim(rtrim(number_format($short, 1, '.', ''), '0'), '.');
      return $short . 'M+';
    }
    if ($value >= 1000) {
      $short = round($value / 1000, 1);
      $short = rtrim(rtrim(number_format($short, 1, '.', ''), '0'), '.');
      return $short . 'K+';
    }
    return number_format($value) . '+';
  }
}

if (!function_exists('jf_category_icon')) {
  function jf_category_icon($name) {
    $normalized = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    $map = [
      'công nghệ' => 'fa-code',
      'developer' => 'fa-code',
      'it' => 'fa-code',
      'data' => 'fa-database',
      'kinh doanh' => 'fa-chart-line',
      'sale' => 'fa-chart-line',
      'marketing' => 'fa-bullhorn',
      'truyền thông' => 'fa-bullhorn',
      'nhân sự' => 'fa-people-group',
      'hành chính' => 'fa-briefcase',
      'thiết kế' => 'fa-palette',
      'sáng tạo' => 'fa-lightbulb',
      'kế toán' => 'fa-calculator',
      'tài chính' => 'fa-coins',
      'y tế' => 'fa-stethoscope',
      'chăm sóc sức khỏe' => 'fa-stethoscope',
      'logistics' => 'fa-truck-fast',
    ];
    foreach ($map as $keyword => $icon) {
      if (strpos($normalized, $keyword) !== false) {
        return $icon;
      }
    }
    return 'fa-briefcase';
  }
}

$jobModel = new Job();
$employerModel = new Employer();
$userModel = new User();
$savedJobModel = null;

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = (int)($_SESSION['role_id'] ?? 0);
$canSaveJobs = $userId > 0 && $userRole === 3;
$savedJobModel = $canSaveJobs ? new SavedJob() : null;
$savedJobIds = $canSaveJobs ? $savedJobModel->getSavedJobIdsForUser($userId) : [];

$stats = [
  'candidates' => $userModel->countByRole(3),
  'employers' => $employerModel->countAll(),
  'jobs' => $jobModel->countPublished()
];

$heroMetrics = [
  ['label' => 'Ứng viên tin dùng JobFind', 'value' => $stats['candidates']],
  ['label' => 'Nhà tuyển dụng đang tuyển', 'value' => $stats['employers']],
  ['label' => 'Việc làm đang mở', 'value' => $stats['jobs']],
];

$topCategories = $jobModel->getTopCategories(6);
$heroHighlightCategories = array_slice(array_filter($topCategories, static function ($cat) {
  return (int)($cat['job_count'] ?? 0) > 0;
}), 0, 3);

$searchKeywords = $jobModel->getPopularKeywords(6);
$hotJobs = $jobModel->getHotJobs(4, ['within_days' => 30]);
if (empty($hotJobs)) {
  $hotJobs = $jobModel->getHotJobs(4, ['within_days' => 90]);
}
if (empty($hotJobs)) {
  $hotJobs = $jobModel->getFeaturedJobs(4);
}
$topEmployers = $employerModel->getTopEmployersByJobs(5);

if (empty($searchKeywords)) {
  $searchKeywords = array_map(static function ($cat) {
    return $cat['name'];
  }, array_slice($topCategories, 0, 4));
}
if (empty($searchKeywords)) {
  $searchKeywords = ['Marketing', 'Sales', 'Designer', 'IT'];
}

$jobShareFlash = $_SESSION['job_share_flash'] ?? null;
if ($jobShareFlash) {
  unset($_SESSION['job_share_flash']);
}

$currentUri = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/index.php';
$prefilledKeyword = trim((string)($_GET['keyword'] ?? ''));
$prefilledLocation = trim((string)($_GET['location'] ?? ''));

$pageTitle = 'JobFind - Nền tảng việc làm chuẩn TopCV';
$bodyClass = 'home-page';
$additionalCSS = $additionalCSS ?? [];
$additionalCSS[] = '<link rel="stylesheet" href="' . ASSETS_URL . '/css/home.css">';
$additionalScripts = $additionalScripts ?? [];
$additionalScripts[] = '<script src="' . ASSETS_URL . '/js/homepage.js" defer></script>';
require_once __DIR__ . '/includes/header.php';
?>

<main class="home-main">
  <?php if ($jobShareFlash): ?>
    <div class="container mt-4">
      <div class="alert alert-<?= htmlspecialchars($jobShareFlash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($jobShareFlash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  <section class="home-hero jf-hero">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-7">
          <div class="hero-content">
            <span class="hero-eyebrow"><i class="fa-solid fa-bolt"></i> TopCV Experience</span>
            <h1 class="hero-title">Tìm việc nhanh 24h &ndash; Chạm gần hơn với công việc mơ ước</h1>
            <p class="hero-subtitle">Hàng nghìn cơ hội mới từ các doanh nghiệp uy tín được cập nhật mỗi ngày. JobFind đồng hành cùng bạn trên hành trình sự nghiệp chuyên nghiệp.</p>
            <div class="home-search-card jf-search-card">
              <form class="row g-3 home-search-form" method="get" action="<?= BASE_URL ?>/job/share/index.php" data-search-url="<?= BASE_URL ?>/job/share/index.php">
                <div class="col-lg-5">
                  <div class="form-floating">
                    <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Vị trí, kỹ năng, công ty" value="<?= htmlspecialchars($prefilledKeyword) ?>">
                    <label for="keyword"><i class="fa-solid fa-magnifying-glass me-2 text-success"></i>Vị trí, kỹ năng, công ty</label>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="form-floating">
                    <select class="form-select" id="location" name="location">
                      <option value="" <?= $prefilledLocation === '' ? 'selected' : '' ?>>Toàn quốc</option>
                      <option value="Hà Nội" <?= $prefilledLocation === 'Hà Nội' ? 'selected' : '' ?>>Hà Nội</option>
                      <option value="TP. Hồ Chí Minh" <?= $prefilledLocation === 'TP. Hồ Chí Minh' ? 'selected' : '' ?>>TP. Hồ Chí Minh</option>
                      <option value="Đà Nẵng" <?= $prefilledLocation === 'Đà Nẵng' ? 'selected' : '' ?>>Đà Nẵng</option>
                      <option value="Remote" <?= $prefilledLocation === 'Remote' ? 'selected' : '' ?>>Remote</option>
                    </select>
                    <label for="location"><i class="fa-solid fa-location-dot me-2 text-success"></i>Địa điểm</label>
                  </div>
                </div>
                <div class="col-lg-3">
                  <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-search"></i>
                    Tìm kiếm
                  </button>
                </div>
              </form>
              <div class="home-search-tags">
                <span class="tag-label">Từ khóa nổi bật:</span>
                <?php foreach (array_slice($searchKeywords, 0, 4) as $keyword): ?>
                  <a href="<?= BASE_URL ?>/job/share/index.php?keyword=<?= urlencode($keyword) ?>" class="badge">#<?= htmlspecialchars($keyword) ?></a>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="hero-metrics">
              <?php foreach ($heroMetrics as $metric): ?>
                <div class="hero-metric-card" data-metric>
                  <div class="hero-metric-value" data-value="<?= (int)$metric['value'] ?>" data-format="<?= htmlspecialchars(jf_format_metric($metric['value'])) ?>">0</div>
                  <p class="hero-metric-label mb-0"><?= htmlspecialchars($metric['label']) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="hero-visual">
            <div class="hero-card">
              <img src="<?= ASSETS_URL ?>/img/slides-2.jpg" alt="Ứng viên JobFind" class="img-fluid">
              <div class="highlight-panel">
                <p class="mb-2 fw-semibold">Top ngành nổi bật</p>
                <ul class="list-unstyled mb-0">
                  <?php if (!empty($heroHighlightCategories)): ?>
                    <?php foreach ($heroHighlightCategories as $cat): ?>
                      <li class="d-flex align-items-center gap-2 mb-1">
                        <span class="text-success"><i class="fa-solid fa-circle"></i></span>
                        <span><?= htmlspecialchars($cat['name']) ?> &ndash; <?= number_format((int)$cat['job_count']) ?> vị trí</span>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li class="text-muted">Chưa có dữ liệu ngành nghề. Hãy đăng tuyển ngay hôm nay!</li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="home-highlight jf-highlight">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="highlight-card jf-highlight-card fade-in-element">
            <div class="highlight-icon" style="background: rgba(0,177,79,0.12); color: var(--home-primary);">
              <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
            <h5>Gợi ý việc làm thông minh</h5>
            <p>Thuật toán AI, chuẩn TopCV phân tích hồ sơ và đề xuất công việc phù hợp nhất với bạn.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="highlight-card jf-highlight-card fade-in-element">
            <div class="highlight-icon" style="background: rgba(13,110,253,0.12); color: #0d6efd;">
              <i class="fa-solid fa-file-signature"></i>
            </div>
            <h5>Mẫu CV chuyên nghiệp</h5>
            <p>Thư viện CV design chuẩn ATS, tối ưu chuyển đổi và được chuyên gia TopCV kiểm chứng.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="highlight-card jf-highlight-card fade-in-element">
            <div class="highlight-icon" style="background: rgba(255,193,7,0.15); color: #f59f11;">
              <i class="fa-solid fa-building"></i>
            </div>
            <h5>Doanh nghiệp uy tín</h5>
            <p>Kết nối với hơn 5.000 thương hiệu lớn như VNPay, Momo, Viettel, FPT cùng nhiều startup.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="home-categories jf-categories py-5">
    <div class="container">
      <div class="section-heading">
        <div>
          <h2>Ngành nghề nổi bật</h2>
          <p>Khám phá cơ hội theo lĩnh vực bạn yêu thích</p>
        </div>
        <a class="btn-link-primary" href="<?= BASE_URL ?>/job/share/index.php?filter=featured">
          Xem tất cả việc làm
          <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      <div class="row g-4">
        <?php if (!empty($topCategories)): ?>
          <?php foreach ($topCategories as $cat): ?>
            <div class="col-xl-4 col-md-6">
              <a class="jf-category-card fade-in-element" href="<?= BASE_URL ?>/job/share/index.php?category=<?= (int)($cat['id'] ?? 0) ?>">
                <div class="icon"><i class="fa-solid <?= jf_category_icon($cat['name']) ?>"></i></div>
                <div>
                  <h5><?= htmlspecialchars($cat['name']) ?></h5>
                  <p><?= number_format((int)$cat['job_count']) ?> việc làm đang tuyển</p>
                </div>
                <i class="fa-solid fa-arrow-right-long"></i>
              </a>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="alert alert-light border text-center mb-0">Chưa có dữ liệu ngành nghề. Hãy thêm danh mục và việc làm để khởi động!</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="jf-featured-jobs">
    <div class="container">
      <div class="section-heading">
        <div>
          <h2>Việc làm hot</h2>
          <p>Được nhiều ứng viên quan tâm nhất trong thời gian gần đây</p>
        </div>
        <a class="btn-link-primary" href="<?= BASE_URL ?>/job/share/index.php">
          Xem thêm việc làm
          <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      <div class="row g-4">
        <?php if (!empty($hotJobs)): ?>
          <?php foreach ($hotJobs as $job): ?>
            <?php
              $jobId = (int)($job['id'] ?? 0);
              $companyName = $job['company_name'] ?? 'Nhà tuyển dụng JobFind';
              $title = $job['title'] ?? 'Tin tuyển dụng';
              $location = $job['location'] ?: 'Toàn quốc';
              $salary = $job['salary'] ?: 'Thỏa thuận';
              $employmentType = $job['employment_type'] ?: 'Toàn thời gian';
              $viewCount = isset($job['view_count']) ? (int)$job['view_count'] : 0;
              $jobDetailUrl = BASE_URL . '/job/share/view.php?id=' . $jobId;
              $isSaved = $canSaveJobs && in_array($jobId, $savedJobIds, true);
            ?>
            <div class="col-xl-3 col-md-6">
              <div class="jf-job-card h-100 fade-in-element">
                <div class="d-flex align-items-start justify-content-between mb-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="logo"><?= htmlspecialchars(strtoupper(substr($companyName, 0, 2))) ?></div>
                    <div>
                      <h5 class="mb-1"><?= htmlspecialchars($title) ?></h5>
                      <span class="text-muted small fw-semibold"><?= htmlspecialchars($companyName) ?></span>
                    </div>
                  </div>
                  <div class="ms-2">
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
                <ul class="list-unstyled mt-3 mb-4 small text-muted">
                  <li><i class="fa-solid fa-location-dot me-2 text-success"></i><?= htmlspecialchars($location) ?></li>
                  <li><i class="fa-solid fa-coins me-2 text-success"></i><?= htmlspecialchars($salary) ?></li>
                </ul>
                <div class="d-flex justify-content-between align-items-center mt-auto">
                  <span class="badge bg-success bg-opacity-10 text-success"><i class="fa-solid fa-eye me-1"></i><?= number_format($viewCount) ?></span>
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-success border border-success"><?= htmlspecialchars($employmentType) ?></span>
                    <a href="<?= htmlspecialchars($jobDetailUrl) ?>" class="btn btn-outline-success btn-sm">
                      Xem chi tiết
                      <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="alert alert-light border text-center mb-0">Chưa có việc làm nào được đăng. Nhà tuyển dụng hãy tạo tin tuyển dụng đầu tiên ngay!</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <div class="home-gradient-divider"></div>

  <section class="jf-trusted">
    <div class="container">
      <div class="text-center mb-5">
        <span class="hero-eyebrow" style="background: rgba(0,177,79,0.12); color: var(--home-primary);">Đối tác chiến lược</span>
        <h2 class="mt-3">Những thương hiệu đồng hành cùng JobFind</h2>
        <p class="text-muted">Từ tập đoàn công nghệ tới startup tiềm năng, hơn 5.000 doanh nghiệp tin tưởng vào JobFind</p>
      </div>
      <?php if (!empty($topEmployers)): ?>
        <div class="trusted-row no-wrap">
          <?php foreach ($topEmployers as $index => $employer): ?>
            <?php
              $companyName = trim($employer['company_name'] ?? '') ?: 'Nhà tuyển dụng JobFind';
              $jobCount = (int)($employer['job_count'] ?? 0);
              $words = explode(' ', $companyName);
              $initial = '';
              if (function_exists('mb_substr')) {
                if (count($words) >= 2) {
                  $initial = mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8'), 'UTF-8') . mb_strtoupper(mb_substr($words[1], 0, 1, 'UTF-8'), 'UTF-8');
                } else {
                  $initial = mb_strtoupper(mb_substr($companyName, 0, 2, 'UTF-8'), 'UTF-8');
                }
              } else {
                $initial = strtoupper(substr($companyName, 0, 2));
              }
              $rankNum = $index + 1;
            ?>
            <div class="jf-brand-card fade-in-element">
              <!-- <div class="brand-header">
                <div class="brand-logo">
                  <span class="brand-initial"><?= htmlspecialchars($initial) ?></span>
                </div>
                <div class="brand-rank">#<?= $rankNum ?></div>
              </div> -->
              <div class="brand-content">
                <h6 class="brand-name" title="<?= htmlspecialchars($companyName) ?>"><?= htmlspecialchars($companyName) ?></h6>
                <div class="brand-stats">
                  <i class="fa-solid fa-briefcase"></i>
                  <span><?= number_format($jobCount) ?> việc làm</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-light border text-center mb-0">Chưa có dữ liệu nhà tuyển dụng nổi bật.</div>
      <?php endif; ?>
    </div>
  </section>

  <section class="jf-blog">
    <div class="container">
      <div class="section-heading">
        <div>
          <h2>Cẩm nang nghề nghiệp</h2>
          <p>Xu hướng mới và lời khuyên từ chuyên gia nhân sự</p>
        </div>
        <a class="btn-link-primary" href="<?= BASE_URL ?>/blog.php">
          Xem tất cả bài viết
          <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      <div class="row g-4">
        <?php
        $articles = [
          ['title' => '5 bí kíp nâng cấp CV khiến nhà tuyển dụng ấn tượng ngay lập tức', 'category' => 'CV & Hồ sơ', 'time' => '5 phút đọc'],
          ['title' => 'Checklist phỏng vấn: Chuẩn bị gì để không bị hỏi khó?', 'category' => 'Phỏng vấn', 'time' => '7 phút đọc'],
          ['title' => 'Kỹ năng phân tích dữ liệu cho marketer thời 4.0', 'category' => 'Kỹ năng', 'time' => '6 phút đọc'],
        ];
        foreach ($articles as $article): ?>
          <div class="col-lg-4">
            <a class="jf-blog-card fade-in-element" href="<?= BASE_URL ?>/blog.php">
              <div class="tag"><i class="fa-solid fa-hashtag"></i> <?= htmlspecialchars($article['category']) ?></div>
              <h5><?= htmlspecialchars($article['title']) ?></h5>
              <span><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($article['time']) ?></span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
