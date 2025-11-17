<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/app/controllers/AuthController.php';

$auth = new AuthController();
$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($email, $password);
    if ($result['success']) {
        $role = $_SESSION['role_id'] ?? 3;
        if ($role == 1) {
            header('Location: /JobFind/admin/index.php');
        } elseif ($role == 2) {
            header('Location: ' . BASE_URL . '/employer/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/dashboard.php');
        }
        exit;
    }
    $error = $result['message'] ?? 'Không thể đăng nhập. Vui lòng thử lại.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập JobFind</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .auth-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            max-width: 1100px;
            width: 100%;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .auth-form-pane {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
        }

        .auth-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: #00b14f;
            text-decoration: none;
            margin-bottom: 30px;
        }

        .auth-brand img {
            height: 35px;
        }

        .auth-form-pane h2 {
            font-size: 26px;
            color: #00b14f;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .auth-form-pane > .mb-4 p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .input-group {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .input-group:focus-within {
            border-color: #00b14f;
            box-shadow: 0 0 0 3px rgba(0, 177, 79, 0.1);
        }

        .input-group-text {
            background: white;
            border: none;
            color: #00b14f;
            padding: 12px 15px;
        }

        .form-control {
            border: none;
            padding: 12px 15px;
            font-size: 15px;
        }

        .form-control:focus {
            box-shadow: none;
            outline: none;
        }

        .toggle-password {
            background: white;
            border: none;
            padding: 12px 15px;
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #00b14f;
        }

        .auth-link {
            color: #00b14f;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .btn-success {
            background: #00b14f;
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-success:hover {
            background: #009943;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 177, 79, 0.3);
        }

        .auth-divider {
            position: relative;
            text-align: center;
            margin: 25px 0;
        }

        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }

        .auth-divider span {
            position: relative;
            background: white;
            padding: 0 15px;
            color: #6c757d;
            font-size: 14px;
        }

        .auth-social-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .auth-social-group .btn {
            padding: 12px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-danger:hover:not(.disabled) {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-outline-primary {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .btn-outline-info {
            border-color: #0dcaf0;
            color: #0dcaf0;
        }

        .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .auth-support {
            font-size: 13px;
            color: #6c757d;
        }

        .auth-support a {
            color: #00b14f;
            text-decoration: none;
        }

        .auth-hero-pane {
            background: linear-gradient(135deg, #1e4d3a 0%, #2a6149 40%, #00664e 100%);
            height: 100%;
            min-height: 700px;
            padding: 60px;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .auth-hero-pane::before {
            content: '';
            position: absolute;
            top: 10%;
            right: 5%;
            width: 300px;
            height: 600px;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 8px, rgba(0, 177, 79, 0.4) 8px, rgba(0, 177, 79, 0.4) 9px),
                repeating-linear-gradient(90deg, transparent, transparent 8px, rgba(0, 177, 79, 0.4) 8px, rgba(0, 177, 79, 0.4) 9px);
            transform: rotate(-15deg);
            opacity: 0.6;
        }

        .auth-hero-pane::after {
            content: '';
            position: absolute;
            bottom: 20%;
            right: 15%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(0, 177, 79, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .auth-hero-content {
            position: relative;
            z-index: 1;
        }

        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .bg-success-subtle {
            background-color: rgba(255, 255, 255, 0.2) !important;
        }

        .text-success {
            color: #fff !important;
        }

        .auth-hero-logo {
            display: flex;
            align-items: center;
        }

        .auth-hero-title {
            font-size: 40px;
            line-height: 1.25;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .auth-hero-subtitle {
            color: rgba(255, 255, 255, 0.85);
            font-size: 15px;
            margin-bottom: 0;
            line-height: 1.6;
        }

        .alert {
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 14px;
            border: none;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        @media (max-width: 991px) {
            .auth-form-pane {
                padding: 30px 20px;
            }

            .auth-social-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="auth-wrapper">
        <div class="container">
            <div class="auth-card">
                <div class="row g-0">
                    <div class="col-lg-6">
                        <div class="auth-form-pane">
                            <a class="auth-brand" href="/JobFind/public/index.php">
                                <i class="fa-solid fa-briefcase"></i>
                                JobFind
                            </a>
                            <div class="mb-4">
                                <h2 class="fw-bold mb-1">Chào mừng bạn đã quay trở lại</h2>
                                <p class="text-muted mb-0">Cùng xây dựng một hồ sơ nổi bật và nhận được các cơ hội sự nghiệp lý tưởng</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" class="auth-form">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control" placeholder="Email" required>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        <span>Mật khẩu</span>
                                        <a href="#" class="auth-link">Quên mật khẩu</a>
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fa-solid fa-shield"></i></span>
                                        <input type="password" name="password" class="form-control js-password" placeholder="Mật khẩu" required>
                                        <button type="button" class="toggle-password" aria-label="Hiển thị mật khẩu">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">Đăng nhập</button>
                                </div>
                            </form>
                            
                            <div class="auth-divider">
                                <span>Hoặc đăng nhập bằng</span>
                            </div>
                            
                            <div class="auth-social-group">
                                <a href="/JobFind/public/google_login.php" class="btn btn-outline-danger btn-lg">
                                    <i class="fa-brands fa-google me-1"></i>
                                    Google
                                </a>
                                <a href="#" class="btn btn-outline-primary btn-lg disabled" aria-disabled="true">
                                    <i class="fa-brands fa-facebook-f me-1"></i>
                                    Facebook
                                </a>
                                <a href="#" class="btn btn-outline-info btn-lg disabled" aria-disabled="true">
                                    <i class="fa-brands fa-linkedin-in me-1"></i>
                                    LinkedIn
                                </a>
                            </div>
                            
                            <p class="mt-4 text-center text-muted">Bạn chưa có tài khoản? <a href="/JobFind/public/account/register.php" class="auth-link">Đăng ký ngay</a></p>
                            
                            <div class="auth-support text-center mt-4">
                                <p class="mb-1">Bạn gặp khó khăn khi tạo tài khoản?</p>
                                <p class="mb-0">Vui lòng liên hệ <a href="tel:02471076480" class="fw-semibold">(024) 7107 6480</a> (giờ hành chính)</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 d-none d-lg-block">
                        <div class="auth-hero-pane">
                            <div class="auth-hero-content">
                                <div class="auth-hero-logo mb-4">
                                    <i class="fa-solid fa-briefcase" style="font-size: 48px; color: white;"></i>
                                    <span style="font-size: 48px; color: white; font-weight: 700; margin-left: 10px;">JobFind</span>
                                </div>
                                <h2 class="auth-hero-title fw-bold text-white">Tiếp lợi thế<br>Nối thành công</h2>
                                <p class="auth-hero-subtitle">JobFind - Hệ sinh thái nhân sự tiên phong ứng dụng công nghệ tại Việt Nam</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer style="text-align: center; padding: 20px; color: #00b14f; font-size: 14px;">
        © 2016. All Rights Reserved. JobFind Vietnam JSC.
    </footer>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('.js-password');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    </script>
</body>
</html>