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

## 2026-10-28 · P1-03 (xong Phase 1)
**Đã làm**
- Cột `users.roadmap_start_date` (nullable), `users.daily_goal_minutes` (mặc định 120).
- Trang Cài đặt học `/settings` (menu tài khoản): ngày bắt đầu, mục tiêu phút (15–600), múi giờ; kiểm tra dữ liệu, thông báo lỗi tiếng Việt.
- `User::roadmapDayNumber()`: ngày thứ mấy của lộ trình, tính theo múi giờ người học.
- Trang Hôm nay: "Tuần X · Ngày Y", hoặc nhắc chọn ngày bắt đầu. 55 test pass; đã thử lưu cài đặt trên trình duyệt.

**Tiếp theo**
- Phase 2 – deploy. P2-01 do bạn làm trên cPanel (mình hướng dẫn từng bước): DB user riêng, collation, tài khoản FTP, PHP extensions, kiểm tra Terminal/SSH.

**Lưu ý**
- Cách tính tuần/ngày hiện nằm trong `dashboard.blade.php`; Phase 3 sẽ chuyển sang lớp dịch vụ khi có dữ liệu lộ trình.

**Lưu ý**
- Tài khoản dev nằm trong `.env` (`OWNER_*`), tạo bằng `php artisan db:seed`. Không commit `.env`.
- Thêm class Tailwind mới thì chạy `npm run build` (hoặc `npm run dev`) mới thấy trên trang.

**Lưu ý**
- Tạo tài khoản dev: `docker compose exec app php artisan app:create-owner` (không có `-T` để nhập mật khẩu ẩn).
- Không truyền `--password` trên dòng lệnh ở production vì mật khẩu sẽ nằm trong lịch sử shell.
- Máy dev không có PHP: mọi lệnh PHP chạy qua `docker compose exec -T app ...`.
- Dữ liệu từ vựng: `../vocabulary-set-33.csv` (7.463 từ; khoảng 2.600 từ thiếu hoặc sai level).
- Trên hosting, DB `qdgjatryhosting_learn-english` đang dùng chung user `qdgjatryhosting_asm-web-ute` với 2 DB khác → nên tạo user riêng (P2-01).

## 2026-10-28 · P2-01 (chuẩn bị hosting)
**Đã làm** (bạn làm trên cPanel)
- DB `qdgjatryhosting_learn-english`: đang trống, collation `utf8mb4_unicode_ci`, chỉ user `qdgjatryhosting_learn` có quyền (ALL). Đã gỡ quyền của `asm-web-ute` trên DB này.
- FTP `deploy@english.minlish.site` giới hạn trong `/home/qdgjatryhosting/learn-english`. Server `ftp.kesat.vn`, cổng 21, có explicit FTPS.
- PHP 8.4 (CloudLinux PHP Selector, cài đặt chung cho 8 domain của tài khoản). Có đủ extension Laravel cần, kể cả intl, bcmath.
- Terminal: có, `php` CLI 8.4.27, không có `composer` (`vendor/` sẽ build trên CI).
- Cron Jobs: có. cPanel gợi ý dùng `/usr/local/bin/php`, các cron cũ dùng `/usr/bin/php`.
- Chốt D11: code ở `~/learn-english`, document root là symlink.

**Tiếp theo**
- P2-02: dựng symlink trên hosting và thử bằng trang tĩnh.

**Lưu ý**
- Document root chỉ đặt được bên trong `public_html`. `public_html` là web của kesat.vn (WordPress), nên không để file nhạy cảm nào dưới đó.
- `pdo_mysql` và `nd_pdo_mysql` đều đang bật. Nếu log có "Module pdo_mysql already loaded" thì bỏ tick `pdo_mysql` (phải cẩn thận vì ảnh hưởng cả 8 domain).
- `opcache` đang tắt; bật thì nhanh hơn nhưng cũng ảnh hưởng cả 8 domain.
- phpMyAdmin hiện server là `localhost:3306` → production dùng `DB_HOST=localhost`.

## 2026-10-28 · P2-02 (cấu trúc thư mục trên hosting)
**Đã làm** (bạn chạy trong Terminal của cPanel)
- Tạo `~/learn-english/public`, đổi tên thư mục cũ thành `~/public_html/english.minlish.site.bak`, tạo symlink `~/public_html/english.minlish.site` → `/home/qdgjatryhosting/learn-english/public`.
- Đã thử: http://english.minlish.site/symlink-test.txt trả về `ok` → web server (LiteSpeed) đi theo symlink được.

**Tiếp theo**
- Kiểm tra PHP chạy qua web có phải 8.4 không: file tạm `public/v.php` (`<?php echo PHP_VERSION;`), xem xong thì xóa luôn cùng `symlink-test.txt`. Chưa có kết quả thì hỏi bạn.
- P2-03: workflow `deploy.yml` (build → FTPS tới `ftp.kesat.vn:21`; thư mục gốc của tài khoản FTP chính là `~/learn-english`).

**Lưu ý**
- Web server là LiteSpeed, đọc `.htaccess` giống Apache.
- `.htaccess` cũ của subdomain (vẫn còn trong `.bak`) chỉ có 2 phần do cPanel tự sinh: ghi log lỗi PHP vào `~/logs/php.error.log`, và `AddHandler application/x-httpd-ea-php81`. Thư mục mới không có file này. Nếu web vẫn chạy PHP 8.4 thì không cần đưa sang; nếu ra 8.1 thì phải thêm handler vào `public/.htaccess` trong repo.
- Document root giờ là `~/learn-english/public`, nên những gì MultiPHP Manager hay MultiPHP INI Editor ghi vào `.htaccess` sẽ bị deploy ghi đè. Muốn đổi gì thì sửa `public/.htaccess` trong repo.
- AutoSSL sẽ tạo thư mục `.well-known` trong `~/learn-english/public`, nên deploy không được xóa file không do nó upload.
- Tài khoản FTP `asm-web-ute@english.minlish.site` đang trỏ vào thư mục không còn tồn tại; xóa nếu không dùng.
- Giữ `english.minlish.site.bak` cho đến khi web chạy ổn.

## 2026-10-29 · P2-03 (workflow deploy)
**Đã làm**
- Kiểm tra PHP qua web: `v.php` trả về `8.4.27`, nên giữ nguyên `public/.htaccess` của Laravel, không thêm handler.
- `.github/workflows/deploy.yml`: khi push lên `main` (hoặc bấm *Run workflow*), workflow gọi `ci.yml` trước. CI xanh thì job `deploy` mới chạy: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, rồi upload qua FTPS (explicit, cổng 21) bằng `SamKirkland/FTP-Deploy-Action` v4.4.0, ghim theo SHA.
- `ci.yml` giờ chạy khi có `pull_request` hoặc được gọi qua `workflow_call`. Đã bỏ `push: main` để mỗi lần push CI không chạy 2 lần; push lên `main` thì CI chạy bên trong workflow Deploy.
- File được upload: `app`, `artisan`, `bootstrap`, `composer.json/lock`, `config`, `database`, `lang`, `public` (kèm `build`), `resources`, `routes`, `vendor` và các thư mục rỗng của `storage`. Không upload `.env*`, `.git*`, `node_modules`, `tests`, `docker`, `tools`, `docs`, `.claude` và các file cấu hình dev.

**Tiếp theo**
- Bạn nhập 3 secrets `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` rồi push để deploy lần đầu, sau đó xem log.
- Trên hosting: xóa `public/v.php` và `public/symlink-test.txt` nếu chưa xóa.
- P2-04: `.env` production và migration.

**Lưu ý**
- FTPS: thử từ máy dev thì `ftp.kesat.vn` (Pure-FTPd) có liệt kê `AUTH TLS` trong FEAT, nhưng gửi lệnh `AUTH TLS` thì nhận `500 This security scheme is not implemented`, còn cổng 990 thì đóng. Nếu log trên GitHub cũng ra lỗi này, phải nhờ nhà cung cấp hosting bật TLS cho FTP. Không tự chuyển sang FTP thường vì mật khẩu sẽ gửi đi không mã hóa.
- Đã xác nhận ở lần deploy đầu (run 40816495601, 29/10): CI và build xanh, nhưng bước upload lỗi `FTPError: 500 This security scheme is not implemented`. Lỗi xảy ra ở bước `AUTH TLS`, trước khi gửi tài khoản, nên chưa có file nào được upload và mật khẩu chưa bị gửi đi. Đang chờ bạn chọn: nhờ hosting bật TLS cho FTP (sau đó chỉ cần bấm *Re-run failed jobs*) hoặc chuyển sang SSH.
- Workflow để `security: strict`. Nếu gặp lỗi chứng chỉ (tên miền không khớp), sửa secret `FTP_SERVER` thành hostname ghi trong chứng chỉ của server, đừng hạ xuống `loose`.
- Action chỉ ghi đè hoặc xóa những file có trong `~/learn-english/.ftp-deploy-sync-state.json`, tức là file do chính nó upload. Vì vậy `.env`, dữ liệu trong `storage/` và `public/.well-known` không bị đụng tới. Đừng thêm vào exclude một thư mục đã từng deploy (`storage/`, `public/`…), vì khi đó action sẽ xóa cả thư mục ấy trên server, kể cả file bên trong. Nếu xóa file state thì lần sau action upload lại toàn bộ, không xóa gì.
- Lần đầu phải upload toàn bộ `vendor` (vài nghìn file) qua FTP, có thể mất 1–2 giờ. Các lần sau chỉ upload file thay đổi.
- Sau lần deploy đầu, web sẽ báo lỗi 500 vì chưa có `.env` (thiếu APP_KEY); việc này làm ở P2-04. `APP_DEBUG` mặc định là false nên trang lỗi không lộ thông tin.
- Trong lúc upload, web có thể lỗi một lúc vì file mới và file cũ lẫn nhau. Với web cá nhân thì chấp nhận được.
- `bootstrap/cache/packages.php` và `services.php` được sinh trên CI (không có dev package) rồi upload lên. Nếu sau này chạy `config:cache` hay `route:cache` trên server thì phải chạy lại sau mỗi lần deploy (chốt ở P2-04).
- Exclude bật `matchBase`: mẫu không có `/` (vd. `README.md`) sẽ khớp file cùng tên ở mọi thư mục, kể cả trong `vendor`. Điều này không gây hại.
