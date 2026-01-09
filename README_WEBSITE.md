# JobFind - Tài liệu luồng nghiệp vụ & xử lý dữ liệu

Tài liệu này dùng để trình bày trước giảng viên về cách hệ thống hoạt động từ khi người dùng đăng nhập cho tới lúc dữ liệu được ghi nhận trong database. Nội dung bám sát codebase PHP/MySQL hiện tại.

## 1. Kiến trúc tổng quan
- Toàn bộ entry point nằm trong `public/` và `admin/`. Mỗi trang tự require `config/config.php` để khởi tạo session, kết nối DB và các hằng BASE_URL, ASSETS_URL.
- Lớp `Database` thiết lập kết nối mysqli. Tất cả models (User, Candidate, Employer, Job, Application, SavedJob, Notification, Permission, Role, ...) kế thừa lớp này.
- Không dùng router trung tâm. Mỗi trang HTML gọi trực tiếp controller/model cần thiết, rồi render view bằng include.
- Thư mục `app/controllers` chứa luồng nghiệp vụ cao cấp (AuthController, JobController, CandidateController...). `app/services/JobRecommendationService.php` xử lý thuật toán gợi ý.

## 2. Luồng xác thực và phân quyền
1. Người dùng truy cập `/account/login.php` hoặc `/account/register.php`.
2. `AuthController` xử lý đăng ký: kiểm tra trùng email, hash mật khẩu, tạo user mặc định role_id=3 (ứng viên) nếu không chọn.
3. Đăng nhập thành công: lưu `$_SESSION['user_id']`, `role_id`, avatar URL, email, tên... Tất cả trang nội bộ kiểm tra session trước khi cho truy cập.
4. Role 1=admin, 2=employer, 3=candidate. Các trang admin gọi thêm `AuthMiddleware::checkPermission()` đối với từng permission.

## 3. Dòng dữ liệu ứng viên
1. Ứng viên đăng ký tài khoản => `users` có record mới.
2. Khi truy cập trang hồ sơ hoặc nộp đơn lần đầu, `CandidateController`/`Candidate` đảm bảo tồn tại record trong `candidates` (auto create nếu thiếu).
3. Người dùng cập nhật headline, summary, location, kỹ năng (JSON), kinh nghiệm (JSON), CV path thông qua form trong `public/candidate/`.
4. Khi apply job (`public/job/apply.php`):
	- Kiểm tra job active (`Job::isActive`).
	- Kiểm tra ứng viên đã có candidate_id, nếu chưa tạo mới.
	- Lưu cover letter, CV snapshot vào bảng `applications` với status `applied`.
	- Nếu ứng viên đã rút đơn trước đó (`withdrawn`) thì `Application::reactivateApplication` cập nhật lại trạng thái.
5. Ứng viên xem lại các đơn ở `/job/applications.php` (gọi `Application::listForCandidate`).

## 4. Dòng dữ liệu nhà tuyển dụng
1. Nhà tuyển dụng tạo tài khoản employer (role_id=2).
2. `Employer` bảng chứa thông tin công ty. Nếu employer chưa có profile, `JobController::ensureEmployer` sẽ tạo record rỗng khi họ mở trang quản lý job.
3. Đăng tin: `/job/create.php` gọi `JobController::createJob` (sử dụng model `Job`) lưu thông tin job, status mặc định `draft`. Khi employer chọn publish sẽ chuyển sang `published`.
4. Bảng liên kết ngành nghề `job_category_map` được đồng bộ bằng `Job::syncCategories` sau khi submit form.
5. Nhà tuyển dụng xem hồ sơ ứng viên tại `/employer/admin/applications.php` (gọi `Application::listForEmployer`). Khi bấm vào chi tiết, `application_view.php` hiển thị full thông tin, cho phép đổi status kèm ghi chú.

## 5. Luồng ứng tuyển & thông báo
1. Ứng viên submit form `public/job/apply.php`:
	- Upload CV qua helper `handle_cv_upload` (kiểm tra mime, di chuyển file).
	- Ghi record vào `applications` (status `applied`).
	- Flash message lưu trong session để thông báo trên UI.
2. Employer vào chi tiết hồ sơ và đổi status:
	- `Application::updateStatus` cập nhật `status` (applied, viewed, shortlisted, rejected, hired, withdrawn) + decision_note.
	- Hệ thống tạo notification (`Notification::create`) gửi đến user_id của ứng viên.
	- Nếu chuyển sang `shortlisted`, file `public/employer/admin/application_view.php` còn gửi email thực tế qua `mail()` (SMTP đã cấu hình trong PHP).
3. Ứng viên rút đơn tại `/job/withdraw_application.php`:
	- Cập nhật status = `withdrawn`.
	- Gửi email thông báo cho employer (best-effort) và flash message cho ứng viên.

## 6. Gợi ý việc làm thông minh
1. Dashboard ứng viên (`public/dashboard.php`) khởi tạo `JobRecommendationService`.
2. Service lấy profile ứng viên: kỹ năng từ JSON, tự tách thêm keywords từ headline/summary, chuẩn hóa địa điểm (bỏ dấu) và xác định các ngành yêu thích dựa trên job đã ứng tuyển hoặc lưu.
3. `Job::getSmartRecommendations()` chạy một truy vấn duy nhất:
	- Cho điểm địa điểm (35 điểm nếu job.location khớp pattern).
	- Mỗi kỹ năng khớp trong title/description/job_requirements được cộng 15 điểm (tối đa 6).
	- Mỗi ngành trùng với lịch sử được cộng 20 điểm.
	- Loại bỏ các job đã ứng tuyển hoặc đã lưu và chỉ xét job `published` chưa hết hạn.
4. Nếu không đủ dữ liệu, service fallback sang `Job::getFallbackRecommendations()` (job nổi bật mới cập nhật) và đánh dấu `is_fallback` để UI hiển thị badge “Gợi ý chung”.
5. Dashboard render card kèm điểm số và badge lý do (địa điểm, kỹ năng, ngành) giúp giảng viên thấy rõ hệ thống dựa trên dữ liệu thực.

## 7. Lưu đồ dữ liệu (tóm tắt)
```
Ứng viên -> đăng ký -> users
Ứng viên -> cập nhật hồ sơ -> candidates (JSON skills, experience, cv_path)
Ứng viên -> apply job -> applications (status, decision_note, snapshot CV)
Nhà tuyển dụng -> đăng job -> jobs + job_category_map
Nhà tuyển dụng -> duyệt hồ sơ -> applications.status + notifications + email
Dashboard -> JobRecommendationService -> Job::getSmartRecommendations -> hiển thị gợi ý
```

## 8. Cơ sở dữ liệu chính
- `users`: thông tin đăng nhập, role_id.
- `candidates`: hồ sơ ứng viên, kỹ năng JSON, CV path.
- `employers`: thông tin doanh nghiệp.
- `jobs`: tin tuyển dụng (status draft/published/closed, deadline, job_requirements, quantity).
- `job_category_map`: map job <-> ngành.
- `applications`: đơn ứng tuyển, status, decision_note, applied_at.
- `saved_jobs`: danh sách job ứng viên lưu.
- `notifications`: thông báo nội bộ hiển thị trong dashboard.
- `job_views`: ghi nhận lượt xem để tính “việc làm hot”.

## 9. Quy trình demo cho giảng viên
1. Đăng nhập bằng 3 role test đã cung cấp để chứng minh phân quyền.
2. Với tài khoản ứng viên: cập nhật kỹ năng, apply 1 job -> xem dashboard hiển thị gợi ý kèm điểm.
3. Với employer: vào ứng viên vừa nộp -> đổi status sang shortlisted -> mở email ứng viên để chứng minh hệ thống gửi thư.
4. Với admin: truy cập `/admin/` để cho thấy bảng thống kê và quản trị roles.

---

Tài liệu này đảm bảo bạn có thể trình bày mạch lạc về toàn bộ luồng dữ liệu và nghiệp vụ chính của JobFind khi báo cáo đồ án.
