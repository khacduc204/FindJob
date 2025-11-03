<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/app/models/Job.php';
require_once dirname(__DIR__, 3) . '/app/models/SavedJob.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/job/share/index.php');
    exit;
}

$jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$action = isset($_POST['action']) ? strtolower(trim((string)$_POST['action'])) : 'save';
$returnTarget = isset($_POST['return']) ? trim((string)$_POST['return']) : '';

if ($jobId <= 0) {
    $_SESSION['job_share_flash'] = [
        'type' => 'danger',
        'message' => 'Yêu cầu không hợp lệ.'
    ];
    header('Location: ' . BASE_URL . '/job/share/index.php');
    exit;
}

if (!str_starts_with($returnTarget, BASE_URL)) {
    $returnTarget = BASE_URL . '/job/share/view.php?id=' . $jobId;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = (int)($_SESSION['role_id'] ?? 0);

if ($userId <= 0 || $userRole !== 3) {
    $_SESSION['job_share_flash'] = [
        'type' => 'warning',
        'message' => 'Vui lòng đăng nhập bằng tài khoản ứng viên để lưu việc làm.'
    ];
    header('Location: ' . BASE_URL . '/account/login.php');
    exit;
}

$jobModel = new Job();
$job = $jobModel->getById($jobId);
if (!$job || ($job['status'] ?? '') !== 'published') {
    $_SESSION['job_share_flash'] = [
        'type' => 'danger',
        'message' => 'Không tìm thấy tin tuyển dụng.'
    ];
    header('Location: ' . BASE_URL . '/job/share/index.php');
    exit;
}

$savedJobModel = new SavedJob();
$success = false;

if ($action === 'remove') {
    $success = $savedJobModel->removeForUser($userId, $jobId);
    $_SESSION['job_share_flash'] = [
        'type' => $success ? 'success' : 'danger',
        'message' => $success ? 'Đã bỏ lưu việc làm khỏi danh sách yêu thích.' : 'Không thể bỏ lưu việc làm, vui lòng thử lại.'
    ];
} else {
    $success = $savedJobModel->saveForUser($userId, $jobId);
    $_SESSION['job_share_flash'] = [
        'type' => $success ? 'success' : 'danger',
        'message' => $success ? 'Đã lưu việc làm vào danh sách yêu thích.' : 'Không thể lưu việc làm, vui lòng thử lại.'
    ];
}

header('Location: ' . $returnTarget);
exit;
