# Nhật ký tiến độ

Mỗi phiên thêm một mục ở cuối: đã làm, tiếp theo, lưu ý. Phiên sau đọc mục cuối cùng để biết bắt đầu từ đâu.

## 2026-10-28 · Phase 0
**Đã làm**
- Khung Laravel 13.39 (PHP 8.4.27 khóa trong composer), Breeze Blade, tắt đăng ký công khai.
- Docker dev: `app` (PHP 8.4.27) + `db` (MariaDB 10.5), DB `learn_english`.
- Pint, Larastan level 6 (0 lỗi), 25 test pass; script `composer check`; CI GitHub Actions.
- Tài liệu: CLAUDE.md, ROADMAP, DECISIONS, PROGRESS; skill `/tiep-tuc`.
- Lưu mã nguồn tạo file Word lộ trình vào `tools/roadmap/` (dùng lại cho P3-01).

- Đã push `main` lên GitHub; CI run đầu tiên xanh (lint, Larastan, test).

**Tiếp theo**
- P1-01: lệnh tạo tài khoản chủ.

## 2026-10-28 · P1-01
**Đã làm**
- `app:create-owner`: hỏi tên/email/mật khẩu (hoặc nhận qua option), kiểm tra hợp lệ, tạo tài khoản đã xác minh email.
- `OwnerSeeder`: tạo tài khoản chủ từ `OWNER_NAME/OWNER_EMAIL/OWNER_PASSWORD` (dùng khi hosting không có Terminal); chạy lại không đổi mật khẩu.
- `DatabaseSeeder` chỉ gọi `OwnerSeeder` (bỏ user test). 32 test pass, Larastan 0 lỗi.

**Tiếp theo**
- P1-02: giao diện tiếng Việt, giờ Việt Nam, menu khung.

## 2026-10-28 · P1-02
**Đã làm**
- Locale `vi` (laravel-lang dịch chữ framework/Breeze; sửa tay "Đăng xuất").
- Cột `users.timezone` + `User::localNow()` (D9).
- Menu: Hôm nay · Lộ trình · Flashcard · Sổ lỗi · Điểm số; 4 mục sau là trang "sắp có". `/` chuyển tới `/dashboard`; bỏ trang welcome mặc định.
- Trang Hôm nay chào tên và hiện thứ/ngày theo giờ người dùng. 45 test pass; đã xem thử trên trình duyệt (điện thoại và máy tính).

**Tiếp theo**
- P1-03: trang Cài đặt học (ngày bắt đầu lộ trình, mục tiêu phút/ngày, múi giờ).

**Lưu ý**
- Tài khoản dev nằm trong `.env` (`OWNER_*`), tạo bằng `php artisan db:seed`. Không commit `.env`.
- Thêm class Tailwind mới thì chạy `npm run build` (hoặc `npm run dev`) mới thấy trên trang.

**Lưu ý**
- Tạo tài khoản dev: `docker compose exec app php artisan app:create-owner` (không có `-T` để nhập mật khẩu ẩn).
- Không truyền `--password` trên dòng lệnh ở production vì mật khẩu sẽ nằm trong lịch sử shell.
- Máy dev không có PHP: mọi lệnh PHP chạy qua `docker compose exec -T app ...`.
- Dữ liệu từ vựng: `../vocabulary-set-33.csv` (7.463 từ; khoảng 2.600 từ thiếu hoặc sai level).
- Trên hosting, DB `qdgjatryhosting_learn-english` đang dùng chung user `qdgjatryhosting_asm-web-ute` với 2 DB khác → nên tạo user riêng (P2-01).
