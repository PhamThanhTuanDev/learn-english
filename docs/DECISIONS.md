# Các quyết định đã chốt

Mỗi quyết định: ngày, nội dung, lý do. Muốn đổi thì thêm mục mới, không xóa mục cũ.

## D1 · 2026-10-28 · Laravel 13
Ban đầu chọn "Laravel 12", nhưng bản hiện hành là Laravel 13 (ra 17/03/2026, sửa lỗi tới Q3/2027, bảo mật tới 17/03/2028). Laravel 12 đã hết sửa lỗi từ 13/08/2026. Laravel 13 cần PHP 8.3+ và MariaDB 10.3+, chạy được trên hosting (PHP 8.4.27, MariaDB 10.5).

## D2 · 2026-10-28 · Giao diện Blade + Alpine.js + Tailwind (Breeze 2.4)
Ít code tay, render phía server, dễ deploy lên shared hosting. Các starter kit mới của Laravel chỉ có React/Vue/Livewire nên dùng Breeze (vẫn hỗ trợ Laravel 13) cho bản Blade thuần.

## D3 · 2026-10-28 · Deploy bằng GitHub Actions → FTP
Push lên `main` → CI chạy test → build → đẩy file lên hosting. Không phải upload tay qua File Manager. Thông tin FTP lưu trong GitHub Secrets, người dùng tự nhập.

## D4 · 2026-10-28 · Một người dùng, sẵn sàng mở sau
Có đăng nhập, mọi bảng dữ liệu có `user_id`, nhưng tắt đăng ký công khai (`APP_REGISTRATION_ENABLED=false`).

## D5 · 2026-10-28 · Phạm vi MVP
Lộ trình 104 tuần, flashcard lặp lại ngắt quãng, sổ lỗi, điểm thi thử và biểu đồ. AI và PWA để sau MVP.

## D6 · 2026-10-28 · Môi trường dev bằng Docker
PHP 8.4.27 + MariaDB 10.5 trong Docker, khớp hosting; máy dev không cần cài PHP. Nhược điểm: chạy trên ổ D: (Windows) chậm, bộ test mất khoảng 35 giây. Nếu quá chậm có thể chuyển sang PHP cài trực tiếp (ví dụ Laravel Herd) và chỉ giữ MariaDB trong Docker.

## D7 · 2026-10-28 · Test chạy trên SQLite in-memory
Nhanh và đơn giản cho cả máy dev lẫn CI. Khi có truy vấn đặc thù MariaDB, thêm một job CI chạy với MariaDB 10.5.

## D8 · 2026-10-28 · Không làm theo hướng dẫn mặc định trong CLAUDE.md của bộ khung Laravel
Bộ khung sinh sẵn hướng dẫn chạy script cài PHP tải từ internet và cài Laravel Boost. Không làm vì dự án đã dùng PHP trong Docker. Laravel Boost (gói chính thức hỗ trợ AI) có thể cân nhắc lại sau.
