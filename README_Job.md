Cách hệ thống đang chấm điểm

JobRecommendationService lấy hồ sơ ứng viên (kỹ năng từ candidates.skills, địa điểm candidates.location, lịch sử ứng tuyển/lưu để suy ra ngành yêu thích).
Hàm getSmartRecommendations() trong Job.php:600-690 trả về các job published chưa hết hạn, chưa từng ứng tuyển/lưu.
Điểm relevance_score được tính theo từng thành phần:

## JobFind – Quy trình tính điểm & hiển thị việc làm gợi ý

Tài liệu này mô tả chi tiết cách hệ thống phân tích hồ sơ ứng viên, tính điểm và đưa ra danh sách việc làm gợi ý trên dashboard.

## 1. Nguồn dữ liệu đầu vào
- `candidates` (location, skills JSON, headline, summary, CV path).
- Lịch sử `applications` (các job đã nộp, status khác withdrawn).
- `saved_jobs` (job ứng viên lưu lại).
- `jobs` và `job_category_map` (tin đăng, ngành nghề, yêu cầu, hạn nộp, trạng thái).

## 2. Luồng xử lý tổng quát
1. **Candidate truy cập dashboard** (`public/dashboard.php`).
2. Controller gọi `JobRecommendationService::getRecommendationsForUser($userId, $limit)`.
3. Service thực hiện:
	 - Lấy hồ sơ ứng viên theo user_id.
	 - Chuẩn hoá địa điểm (lowercase, bỏ dấu) và tạo nhiều pattern so khớp (`%Ha Noi%`, `%ha noi%`...).
	 - Trích tối đa 6 kỹ năng: ưu tiên mảng JSON `skills`, đồng thời phân tích từ khoá trong headline + summary để lấp đầy nếu thiếu.
	 - Tính danh sách category yêu thích dựa trên job đã ứng tuyển hoặc lưu. Nếu ứng viên mới chưa có dữ liệu, hệ thống fallback sang các category đang có nhiều job nhất.
4. Service truyền toàn bộ bối cảnh (location_patterns, skills, preferred_categories, candidate_id) cho `Job::getSmartRecommendations()`.
5. Model `Job` chạy truy vấn SQL duy nhất để tính điểm và trả về danh sách job kèm `relevance_score` + số category khớp.
6. Service hậu xử lý: tạo danh sách highlight (“Địa điểm phù hợp…”, “Kỹ năng: PHP”, “Khớp ngành…”) và đánh dấu `is_fallback` nếu phải lấy job nổi bật chung.
7. Dashboard render card gợi ý với badge điểm số + highlights. Nếu là fallback sẽ hiển thị badge “Gợi ý chung”.

## 3. Công thức chấm điểm chi tiết
Hàm `Job::getSmartRecommendations()` chấm điểm theo công thức:

```
score = 5
			+ 35 * matchLocation
			+ Σ (15 * matchSkill_i)  (tối đa 6 kỹ năng)
			+ 20 * matchedCategories
```

- `matchLocation` = 1 nếu `jobs.location` chứa bất kỳ pattern địa điểm ứng viên (không phân biệt hoa/thường, có bỏ dấu) ⇒ +35 điểm.
- `matchSkill_i` = 1 nếu kỹ năng i xuất hiện trong `title`, `description` hoặc `job_requirements` ⇒ mỗi kỹ năng +15 điểm.
- `matchedCategories` = số category job thuộc nằm trong danh sách ngành yêu thích ⇒ mỗi category +20 điểm.
- Điểm nền tảng 5 đảm bảo job vẫn hiển thị khi thiếu dữ liệu.
- Job bị loại nếu:
	- `status != 'published'` hoặc `deadline < TODAY`.
	- Ứng viên đã ứng tuyển (status khác withdrawn) hoặc đã lưu job đó.

Nếu truy vấn trả về rỗng (ví dụ ứng viên mới, data hồ sơ thiếu), service gọi `Job::getFallbackRecommendations()` để hiển thị danh sách job mới cập nhật nhất và gắn nhãn fallback.

## 4. Highlights hiển thị trên dashboard
Sau khi có danh sách job, service gắn thêm mô tả ngắn:
- `Địa điểm phù hợp: Hà Nội` – khi job.location khớp địa điểm ứng viên.
- `Kỹ năng: PHP` / `Kỹ năng: Marketing` – khi tìm thấy kỹ năng tương ứng trong mô tả job.
- `Khớp ngành nghề bạn quan tâm` – khi job thuộc category trong danh sách ưu tiên.
- `Gợi ý dựa trên hoạt động gần đây` – khi không có lý do cụ thể.
- `Việc làm nổi bật tuần này` + badge “Gợi ý chung” – khi dùng fallback.

## 5. Các điều kiện ảnh hưởng kết quả
- Ứng viên chưa khai báo kỹ năng/địa điểm ⇒ hệ thống tự tách từ headline/summary, nhưng độ chính xác phụ thuộc nội dung mô tả.
- Hồ sơ mới chưa có lịch sử apply/saved ⇒ danh sách category ưu tiên sẽ dùng dữ liệu trending (các ngành có nhiều job nhất hiện tại).
- Job hết hạn (`deadline < TODAY`) hoặc `status = draft/closed` ⇒ không được xét dù có dữ liệu trùng khớp.
- Nếu file `candidates.skills` không phải JSON hợp lệ ⇒ service chỉ dựa trên keywords trong headline/summary.

## 6. Cách giải thích với giảng viên
1. Mở dashboard ứng viên, trình bày luồng từ controller → service → model → giao diện.
2. Cho giảng viên xem bảng `candidates` (location, skills JSON), `applications`, `saved_jobs`, `jobs`, `job_category_map` để thấy dữ liệu nguồn.
3. Mô tả hàm SQL trong `Job::getSmartRecommendations()` với các CASE WHEN cộng điểm.
4. Thay đổi hồ sơ (ví dụ thêm kỹ năng “React”) và refresh dashboard để chứng minh điểm & highlight thay đổi theo.
5. Xoá lịch sử apply/lưu để demo trường hợp fallback, badge “Gợi ý chung” xuất hiện.

---
Tài liệu này giúp bạn trả lời các câu hỏi về cơ chế tính điểm và luồng xử lý gợi ý việc làm trong JobFind khi bảo vệ đồ án.
Luồng xử lý gợi ý

Ứng viên vào dashboard → controller gọi JobRecommendationService::getRecommendationsForUser().
Service lấy Candidate theo user_id, trích tối đa 5 kỹ năng (từ JSON hoặc chuỗi), đọc địa điểm, dựng danh sách ngành ưu tiên.
Truyền các tham số này xuống Job::getSmartRecommendations() để chạy query SQL có các CASE WHEN cộng điểm.
Kết quả trả về gồm relevance_score và highlights (badge “Địa điểm phù hợp…”, “Kỹ năng…”) hiển thị trên dashboard.
Vì sao bạn thấy chưa sát

Nếu candidates.skills rỗng hoặc lưu không đúng dạng JSON (ví dụ chuỗi plain), service sẽ không trích được kỹ năng → điểm chỉ dựa vào địa điểm/ngành.
Địa điểm đang so sánh bằng LIKE đơn giản, nhạy cảm với chính tả (“Hà Nội” khác “Ha Noi”).
Ngành yêu thích được suy ra từ lịch sử ứng tuyển / saved jobs; nếu ứng viên mới chưa có lịch sử, phần này = 0.
Job phải còn hạn (deadline >= CURDATE() hoặc null) và status = published. Nếu job hết hạn, sẽ không được xét dù phù hợp.
