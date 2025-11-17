
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/User.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . '/account/login.php'); exit;
}
$userModel = new User();
$user = $userModel->getById($_SESSION['user_id']);
$role = $_SESSION['role_id'];

if ((int)$role === 2) {
  header('Location: ' . BASE_URL . '/employer/admin/dashboard.php');
  exit;
}

// Mock stats data (replace with real database queries)
$stats = [
  'applications' => 12,
  'saved_jobs' => 8,
  'profile_views' => 45,
  'messages' => 3
];
?>

<?php 
$pageTitle = 'Dashboard - JobFind';
$additionalCSS = ['<link href="' . ASSETS_URL . '/css/dashboard.css" rel="stylesheet">'];
require_once __DIR__ . '/includes/header.php'; 
?>

<div class="dashboard-container">
  <div class="container">
    
    <!-- Welcome Banner -->
    <div class="dashboard-welcome-banner">
      <div class="welcome-content">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div>
            <h1 class="welcome-title">Xin chào, <?php echo htmlspecialchars($user['name'] ?: $user['email']); ?>! 👋</h1>
            <div class="welcome-meta">
              <div class="welcome-meta-item">
                <i class="fa-solid fa-user-shield"></i>
                <span><?php if ($role == 1) echo 'Quản trị viên'; elseif ($role == 2) echo 'Nhà tuyển dụng'; else echo 'Ứng viên'; ?></span>
              </div>
              <div class="welcome-meta-item">
                <i class="fa-solid fa-calendar-days"></i>
                <span><?php echo date('d/m/Y'); ?></span>
              </div>
            </div>
          </div>
          <a href="<?= BASE_URL ?>/account/logout.php" class="btn-logout">
            <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>
            Đăng xuất
          </a>
        </div>
      </div>
    </div>
    
    <!-- Stats Cards -->
    <?php if ($role == 3): ?>
    <div class="row dashboard-stats-row g-3">
      <div class="col-lg-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon bg-success-custom">
            <i class="fa-solid fa-file-circle-check"></i>
          </div>
          <div class="stat-value" data-target="<?= $stats['applications'] ?>">0</div>
          <div class="stat-label">Đơn ứng tuyển</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon bg-info-custom">
            <i class="fa-solid fa-bookmark"></i>
          </div>
          <div class="stat-value" data-target="<?= $stats['saved_jobs'] ?>">0</div>
          <div class="stat-label">Việc làm đã lưu</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card">
          <div class="stat-icon bg-warning-custom">
            <i class="fa-solid fa-eye"></i>
          </div>
          <div class="stat-value" data-target="<?= $stats['profile_views'] ?>">0</div>
          <div class="stat-label">Lượt xem hồ sơ</div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="stat-card position-relative">
          <div class="stat-icon bg-danger-custom">
            <i class="fa-solid fa-envelope"></i>
          </div>
          <div class="stat-value" data-target="<?= $stats['messages'] ?>">0</div>
          <div class="stat-label">Tin nhắn mới</div>
          <?php if ($stats['messages'] > 0): ?>
          <span class="notification-badge"><?= $stats['messages'] ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="row g-4 mb-4">
      <?php if ($role == 1): ?>
        <!-- Admin Actions -->
        <div class="col-lg-4 col-md-6">
          <a href="/JobFind/admin/index.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(0,177,79,0.1), rgba(0,177,79,0.2));">
              <i class="fa-solid fa-gauge-high" style="color: #00b14f;"></i>
            </div>
            <h5 class="action-title">Quản trị hệ thống</h5>
            <p class="action-description">Truy cập Admin Panel</p>
          </a>
        </div>
        <div class="col-lg-4 col-md-6">
          <a href="/JobFind/admin/user/users.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(13,110,253,0.1), rgba(13,110,253,0.2));">
              <i class="fa-solid fa-users" style="color: #0d6efd;"></i>
            </div>
            <h5 class="action-title">Quản lý Users</h5>
            <p class="action-description">Xem và chỉnh sửa người dùng</p>
          </a>
        </div>
        <div class="col-lg-4 col-md-6">
          <a href="/JobFind/admin/candidates.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,193,7,0.2));">
              <i class="fa-solid fa-briefcase" style="color: #ffc107;"></i>
            </div>
            <h5 class="action-title">Quản lý Ứng viên</h5>
            <p class="action-description">Xem danh sách ứng viên</p>
          </a>
        </div>
      <?php elseif ($role == 2): ?>
        <!-- Employer Actions -->
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/job/index.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(0,177,79,0.1), rgba(0,177,79,0.2));">
              <i class="fa-solid fa-bullhorn" style="color: #00b14f;"></i>
            </div>
            <h5 class="action-title">Đăng tin tuyển dụng</h5>
            <p class="action-description">Tạo tin tuyển dụng mới</p>
          </a>
        </div>
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/employer_jobs.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(13,110,253,0.1), rgba(13,110,253,0.2));">
              <i class="fa-solid fa-list-check" style="color: #0d6efd;"></i>
            </div>
            <h5 class="action-title">Quản lý tuyển dụng</h5>
            <p class="action-description">Xem và chỉnh sửa tin đăng</p>
          </a>
        </div>
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/employer_candidates.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,193,7,0.2));">
              <i class="fa-solid fa-users" style="color: #ffc107;"></i>
            </div>
            <h5 class="action-title">Ứng viên tiềm năng</h5>
            <p class="action-description">Xem hồ sơ ứng tuyển</p>
          </a>
        </div>
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/employer/edit.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(108,117,125,0.1), rgba(108,117,125,0.2));">
              <i class="fa-solid fa-building" style="color: #6c757d;"></i>
            </div>
            <h5 class="action-title">Thông tin công ty</h5>
            <p class="action-description">Cập nhật hồ sơ doanh nghiệp</p>
          </a>
        </div>
      <?php else: ?>
        <!-- Candidate Actions -->
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/candidate/profile.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(0,177,79,0.1), rgba(0,177,79,0.2));">
              <i class="fa-solid fa-user" style="color: #00b14f;"></i>
            </div>
            <h5 class="action-title">Hồ sơ của tôi</h5>
            <p class="action-description">Cập nhật thông tin cá nhân</p>
          </a>
        </div>
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/job/share/index.php" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(13,110,253,0.1), rgba(13,110,253,0.2));">
              <i class="fa-solid fa-magnifying-glass" style="color: #0d6efd;"></i>
            </div>
            <h5 class="action-title">Tìm việc làm</h5>
            <p class="action-description">Khám phá cơ hội mới</p>
          </a>
        </div>
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/job/share/index.php?saved=1" class="quick-action-card">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(255,193,7,0.1), rgba(255,193,7,0.2));">
              <i class="fa-solid fa-bookmark" style="color: #ffc107;"></i>
            </div>
            <h5 class="action-title">Việc đã lưu</h5>
            <p class="action-description">Xem các việc làm đã lưu</p>
          </a>
        </div>
        <div class="col-lg-3 col-md-6">
          <a href="<?= BASE_URL ?>/job/applications.php" class="quick-action-card position-relative">
            <div class="action-icon-box" style="background: linear-gradient(135deg, rgba(220,53,69,0.1), rgba(220,53,69,0.2));">
              <i class="fa-solid fa-file-circle-check" style="color: #dc3545;"></i>
            </div>
            <h5 class="action-title">Ứng tuyển của tôi</h5>
            <p class="action-description">Xem và quản lý các đơn đã nộp</p>
            <?php if ($stats['applications'] > 0): ?>
            <span class="notification-badge"><?= $stats['applications'] ?></span>
            <?php endif; ?>
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Content Sections -->
    <div class="row g-4">
      <!-- Left Column -->
      <div class="col-lg-8">
        
        <!-- Recent Activity -->
        <div class="dashboard-section">
          <div class="section-header">
            <h5 class="section-title">
              <i class="fa-solid fa-clock-rotate-left"></i>
              Hoạt động gần đây
            </h5>
          </div>
          
          <?php if ($role == 3): ?>
          <div class="activity-timeline">
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Bạn đã ứng tuyển vị trí <strong>Senior PHP Developer</strong></div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>2 giờ trước
                </div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Đã lưu công việc <strong>Frontend Developer tại FPT Software</strong></div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>1 ngày trước
                </div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Cập nhật CV mới <strong>"CV-Nguyen-Van-A-2025.pdf"</strong></div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>3 ngày trước
                </div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Đã xem công ty <strong>VNG Corporation</strong></div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>5 ngày trước
                </div>
              </div>
            </div>
          </div>
          <?php elseif ($role == 2): ?>
          <div class="activity-timeline">
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Nhận được <strong>5 hồ sơ ứng tuyển</strong> mới</div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>1 giờ trước
                </div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Đăng tin tuyển dụng <strong>Marketing Manager</strong></div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>2 ngày trước
                </div>
              </div>
            </div>
            <div class="activity-item">
              <div class="activity-dot"></div>
              <div class="activity-content">
                <div class="activity-title">Cập nhật thông tin công ty</div>
                <div class="activity-time">
                  <i class="fa-regular fa-clock me-1"></i>1 tuần trước
                </div>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <div class="empty-state-icon">
              <i class="fa-solid fa-circle-info"></i>
            </div>
            <h6 class="empty-state-title">Chưa có hoạt động nào</h6>
            <p class="empty-state-description">Hoạt động của bạn sẽ hiển thị ở đây</p>
          </div>
          <?php endif; ?>
        </div>
        
        <!-- Profile Completion (for candidates) -->
        <?php if ($role == 3): ?>
        <div class="dashboard-section">
          <div class="section-header">
            <h5 class="section-title">
              <i class="fa-solid fa-chart-pie"></i>
              Hoàn thiện hồ sơ
            </h5>
          </div>
          <div class="progress-card">
            <div class="progress-header">
              <span class="progress-label">
                <i class="fa-solid fa-user-check me-2"></i>
                Thông tin cá nhân
              </span>
              <span class="progress-percentage">100%</span>
            </div>
            <div class="progress-bar-custom">
              <div class="progress-bar-fill" style="width: 100%;"></div>
            </div>
          </div>
          
          <div class="progress-card">
            <div class="progress-header">
              <span class="progress-label">
                <i class="fa-solid fa-file-pdf me-2"></i>
                CV & Thư giới thiệu
              </span>
              <span class="progress-percentage">75%</span>
            </div>
            <div class="progress-bar-custom">
              <div class="progress-bar-fill" style="width: 75%;"></div>
            </div>
          </div>
          
          <div class="progress-card">
            <div class="progress-header">
              <span class="progress-label">
                <i class="fa-solid fa-graduation-cap me-2"></i>
                Học vấn & Kinh nghiệm
              </span>
              <span class="progress-percentage">60%</span>
            </div>
            <div class="progress-bar-custom">
              <div class="progress-bar-fill" style="width: 60%;"></div>
            </div>
          </div>
          
          <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>/candidate/profile.php" class="btn btn-success">
              <i class="fa-solid fa-pen-to-square me-2"></i>
              Hoàn thiện hồ sơ ngay
            </a>
          </div>
        </div>
        <?php endif; ?>
        
      </div>
      
      <!-- Right Column -->
      <div class="col-lg-4">
        
        <!-- Quick Links -->
        <div class="dashboard-section">
          <div class="section-header">
            <h5 class="section-title">
              <i class="fa-solid fa-link"></i>
              Liên kết nhanh
            </h5>
          </div>
          <div class="list-group list-group-flush">
            <?php if ($role == 3): ?>
            <a href="<?= BASE_URL ?>/jobs.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-magnifying-glass me-2 text-primary"></i>
              Tìm việc làm phù hợp
            </a>
            <a href="<?= BASE_URL ?>/job/applications.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-file-circle-check me-2 text-danger"></i>
              Ứng tuyển của tôi
            </a>
            <a href="<?= BASE_URL ?>/companies.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-building me-2 text-info"></i>
              Khám phá công ty
            </a>
            <a href="<?= BASE_URL ?>/cv_builder.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-file-lines me-2 text-success"></i>
              Tạo CV online
            </a>
            <a href="<?= BASE_URL ?>/career_blog.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-newspaper me-2 text-warning"></i>
              Cẩm nang nghề nghiệp
            </a>
            <?php elseif ($role == 2): ?>
            <a href="<?= BASE_URL ?>/job/index.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-plus me-2 text-success"></i>
              Đăng tin tuyển dụng
            </a>
            <a href="<?= BASE_URL ?>/employer_candidates.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-users me-2 text-primary"></i>
              Tìm ứng viên
            </a>
            <a href="<?= BASE_URL ?>/employer_pricing.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-crown me-2 text-warning"></i>
              Nâng cấp gói dịch vụ
            </a>
            <?php else: ?>
            <a href="/JobFind/admin/index.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-gauge-high me-2 text-primary"></i>
              Admin Dashboard
            </a>
            <a href="/JobFind/admin/user/users.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-users me-2 text-info"></i>
              Quản lý Users
            </a>
            <a href="/JobFind/admin/candidates.php" class="list-group-item list-group-item-action">
              <i class="fa-solid fa-briefcase me-2 text-success"></i>
              Quản lý Ứng viên
            </a>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Tips & Tricks -->
        <div class="dashboard-section">
          <div class="section-header">
            <h5 class="section-title">
              <i class="fa-solid fa-lightbulb"></i>
              Mẹo hữu ích
            </h5>
          </div>
          <div class="alert alert-success border-0 mb-3">
            <h6 class="alert-heading mb-2">
              <i class="fa-solid fa-circle-check me-2"></i>
              <?php if ($role == 3): ?>
              Tăng cơ hội được tuyển dụng
              <?php elseif ($role == 2): ?>
              Thu hút ứng viên chất lượng
              <?php else: ?>
              Quản lý hiệu quả
              <?php endif; ?>
            </h6>
            <p class="mb-0 small">
              <?php if ($role == 3): ?>
              Hãy cập nhật đầy đủ thông tin hồ sơ, kỹ năng và kinh nghiệm để nhà tuyển dụng dễ dàng tìm thấy bạn!
              <?php elseif ($role == 2): ?>
              Viết mô tả công việc chi tiết, rõ ràng với mức lương cạnh tranh để thu hút nhiều ứng viên hơn!
              <?php else: ?>
              Sử dụng bộ lọc và công cụ tìm kiếm để quản lý dữ liệu một cách nhanh chóng và hiệu quả!
              <?php endif; ?>
            </p>
          </div>
        </div>
        
      </div>
    </div>
    
  </div>
</div>

<script src="<?= ASSETS_URL ?>/js/dashboard.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
