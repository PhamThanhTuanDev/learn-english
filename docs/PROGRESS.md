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
- Kiểm tra PHP qua web: `v.php` trả về `8.4.27`. Lúc thử, docroot đã có sẵn `public/.htaccess` do cPanel tự sinh lúc 05:46 ngày 29/10, chỉ gồm khối handler `ea-php81`. Có handler đó mà CloudLinux PHP Selector vẫn chạy 8.4. Vì vậy đã chép nguyên văn khối handler vào cuối `public/.htaccess` của repo: deploy giữ đúng cấu hình đã thử, và khi cPanel ghi lại khối này thì nội dung vẫn như cũ.
- `.github/workflows/deploy.yml`: khi push lên `main` (hoặc bấm *Run workflow*), workflow gọi `ci.yml` trước. CI xanh thì job `deploy` mới chạy: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, gom file vào một thư mục release, rồi `rsync` qua SSH lên `~/learn-english`.
- `ci.yml` giờ chạy khi có `pull_request` hoặc được gọi qua `workflow_call`. Đã bỏ `push: main` để mỗi lần push CI không chạy 2 lần.
- Bản đầu dùng FTPS. Lần deploy thử (run 40816495601) có CI và build xanh, nhưng bước upload lỗi `500 This security scheme is not implemented`: hosting đã tắt TLS cho FTP, và chỉ admin hosting mới bật được. Lỗi xảy ra trước khi gửi tài khoản, nên chưa có file nào lên server. Theo lựa chọn của bạn, deploy chuyển sang SSH (D12).
- Khóa deploy riêng: `C:\Users\tuan\.ssh\learn-english-deploy` (ed25519, fingerprint `SHA256:fpeLm+P245AS/1JoZpSZ4lw367U6NrX2hWNCTrrcQWs`). Host key của server đã được ghim (ed25519, `SHA256:bnTPAtHgvlZqYlymJRfAHlNASt4CZcGp4q3OtnM/t0I`).
- File được upload: `app`, `artisan`, `bootstrap`, `composer.json/lock`, `config`, `database`, `lang`, `public` (kèm `build`), `resources`, `routes`, `vendor` và các thư mục rỗng của `storage`. Không upload `.env*`, `.git*`, `node_modules`, `tests`, `docker`, `tools`, `docs`, `.claude` và các file cấu hình dev.

**Tiếp theo**
- Deploy SSH lần đầu đã chạy xong (run 40822416790, push `b207473`). CI và deploy đều xanh: upload 6.654 file mất 8 giây, bước dọn file cũ chạy được. Server không có `.env`, còn `.well-known` vẫn nguyên. Web trả về 500 với lỗi `No application encryption key has been specified`, nghĩa là Laravel đã chạy được trên PHP 8.4, chỉ còn thiếu `.env`.
- Có thể xóa 3 secrets `FTP_*` trên GitHub. Tài khoản FTP `deploy@english.minlish.site` thì giữ hay xóa tùy bạn, deploy không dùng nữa.
- `v.php` và `symlink-test.txt` đã được xóa khỏi `public/`.
- P2-04: `.env` production và migration. Giờ đã có SSH, nên migration có thể chạy ngay trong workflow.

**Lưu ý**
- Server SSH: `turboweb-082302.inet.vn`, cổng 65333, user `qdgjatryhosting`. Cổng 22 bị chặn. Giờ server là +07.
- Đừng xóa hay sửa khối `# php -- BEGIN cPanel-generated handler` trong `public/.htaccess`. Muốn đổi phiên bản PHP thì đổi trong cPanel trước, rồi chép khối mới mà cPanel sinh ra vào repo.
- Trên server, `~/learn-english/.ftpquota` là file của Pure-FTPd; deploy không đụng tới nó.
- Không dùng `rsync --delete`. Workflow ghi danh sách file đã deploy vào `~/learn-english/.deploy-manifest`, và chỉ xóa những file có ở lần trước mà lần này không còn. Ngoài ra luôn bỏ qua `.env*`, `storage/*`, `public/.well-known/*`. Nếu xóa file manifest thì lần sau không xóa gì cả.
- `rsync --checksum --delay-updates`: chỉ gửi file thay đổi, và đợi đến cuối mới thay file mới vào, nên thời gian web bị lẫn file cũ/mới rất ngắn. Quyền file được đặt về 644, thư mục 755.
- Khóa SSH có quyền trên cả tài khoản cPanel (8 domain), nên không đưa khóa bí mật cho ai và không commit. Nếu lộ: xóa dòng tương ứng trong `~/.ssh/authorized_keys` và tạo khóa mới.
- Nếu host key của server thay đổi (ví dụ iNET chuyển server), deploy sẽ dừng ở bước SSH. Khi đó lấy lại bằng `ssh-keyscan -p 65333 -t ed25519 turboweb-082302.inet.vn`, so fingerprint rồi cập nhật secret `SSH_KNOWN_HOSTS`.
- Sau lần deploy đầu, web sẽ báo lỗi 500 vì chưa có `.env` (thiếu APP_KEY); việc này làm ở P2-04. `APP_DEBUG` mặc định là false nên trang lỗi không lộ thông tin.
- `bootstrap/cache/packages.php` và `services.php` được sinh trên CI (không có dev package) rồi upload lên. Nếu sau này chạy `config:cache` hay `route:cache` trên server thì phải chạy lại sau mỗi lần deploy (chốt ở P2-04).

## 2026-10-29 · P2-04 (`.env` production + migration)
**Đã làm**
- Chốt với bạn (D13): migration tự chạy khi deploy; tài khoản chủ tạo bằng `app:create-owner`; mật khẩu DB do bạn tự nhập bằng lệnh có prompt ẩn.
- Tạo `~/learn-english/.env` trên server qua SSH (quyền 600) và chạy `key:generate`. Cấu hình: production, `APP_DEBUG=false`, `APP_URL=https://english.minlish.site`, DB `localhost` / `qdgjatryhosting_learn-english` / `qdgjatryhosting_learn`, `QUEUE_CONNECTION=sync`, log `daily` giữ 14 ngày ở mức `warning`, mail `log`. Bạn đã nhập `DB_PASSWORD` và `php artisan db:show` kết nối được (MariaDB 10.5.29).
- `deploy.yml` có thêm bước "Migrate and cache" (`migrate --force` rồi `optimize`).
- Lần migrate đầu lỗi `Specified key was too long; max key length is 1000 bytes`, vì MariaDB của hosting mặc định dùng MyISAM. Đã sửa bằng cách đặt `'engine' => 'InnoDB'` cho kết nối `mariadb` trong `config/database.php`, và đã thử trên MariaDB dev với engine mặc định đổi sang MyISAM. Hai bảng rỗng mà lần lỗi tạo ra (`migrations`, `users`, MyISAM) đã được xóa, sau đó deploy lại.
- Run 40827479448: 5 migration chạy xong, cả 9 bảng là InnoDB. `/` chuyển hướng sang `/login` (200), còn `/register` trả 404.
- Bạn đã tạo tài khoản chủ bằng `php artisan app:create-owner` trong Terminal cPanel. Production hiện có 1 user, đã xác thực email.

**Tiếp theo**
- P2-05: AutoSSL (đã có chứng chỉ Let's Encrypt cho `english.minlish.site`), bật Force HTTPS, `SESSION_SECURE_COOKIE=true`, kiểm tra đăng nhập trên production.

**Lưu ý**
- Sửa `.env` trên server xong phải chạy `php artisan optimize` (hoặc `config:clear`), vì config đang được cache. Deploy cũng tự chạy `optimize` mỗi lần.
- Chạy lệnh artisan trên server bằng `php` (8.4 CLI). Đừng dùng `/usr/bin/php`, đó là php-cgi.
- Lệnh `php artisan tinker` trên server in cảnh báo `Unsuccessful stat on filename containing newline ... ea_php_cli.pm` nếu đoạn `--execute` có xuống dòng. Cảnh báo này vô hại.
- Migration lỗi thì job deploy đỏ, mà code mới đã lên nhưng schema chưa đổi. Khi đó sửa rồi push lại; nếu lỗi để lại bảng dở thì xem `migrate:status` trước khi dọn.
- `QUEUE_CONNECTION=sync`: chưa có cron chạy queue. Khi nào cần queue hay scheduler thì thêm cron `php artisan schedule:run`.

## 2026-10-29 · P2-05 (HTTPS, xong Phase 2)
**Đã làm**
- Chứng chỉ AutoSSL (Let's Encrypt `YR2`) cho `english.minlish.site` còn hạn tới 26/01/2027.
- Chốt với bạn (D14): ép HTTPS bằng rule trong `public/.htaccess`, kèm HSTS 1 năm (không `includeSubDomains`). Commit `9309daf`, deploy run 40828470843 xanh.
- Kiểm tra bằng curl: http trả 301 sang https, giữ nguyên đường dẫn và query, file tĩnh cũng bị chuyển hướng. Mọi response đều có `Strict-Transport-Security: max-age=31536000`. `/.well-known/acme-challenge/…` không bị chuyển hướng, để AutoSSL gia hạn qua http.
- Bạn đã thêm `SESSION_SECURE_COOKIE=true` vào `.env` trên server và chạy `optimize` trong Terminal cPanel (`config:show session.secure` = `true`). Cookie `learn-english-session` và `XSRF-TOKEN` đều có cờ `secure`; cookie phiên thêm `httponly` và `samesite=lax`.
- Bạn đã đăng nhập được trên https://english.minlish.site và vào được `/dashboard`.
- Phase 2 xong: push lên `main` là web tự cập nhật trên HTTPS.

**Tiếp theo**
- Phase 3, P3-01: xuất dữ liệu lộ trình từ `tools/roadmap/data.js` thành `database/data/roadmap.json`.

**Lưu ý**
- Khối `# php -- BEGIN cPanel-generated handler` cuối `public/.htaccess` vẫn giữ nguyên. Rule HTTPS nằm trong khối `mod_rewrite`, header HSTS nằm trong khối `mod_headers` riêng, cả hai đều ở trước khối handler.
- Không bật nút Force HTTPS trong cPanel, vì rule trong repo đã làm việc đó; bật cả hai thì bị trùng.
- HSTS: nếu chứng chỉ hết hạn, trình duyệt chặn hẳn trang, không cho bấm bỏ qua. Khi đó xem cPanel → SSL/TLS Status → Run AutoSSL. Cách bỏ HSTS ghi ở D14.
- Ở phiên này, Claude bị chặn ghi `.env` qua SSH (auto mode chặn ghi trên server từ xa). Những lần sau cần sửa `.env` thì bạn chạy lệnh trong Terminal cPanel, hoặc thêm quyền cho Bash.

## 2026-10-29 · P3-01 (xuất dữ liệu lộ trình ra JSON)
**Đã làm**
- Tách phần sinh lịch tuần/ngày khỏi `tools/roadmap/build.js` sang `tools/roadmap/schedule.js` (hàm `weekPlan(n)`, `REST_DAY`, `stageOf`, `monthOf`, `isMonthEnd`). File Word và file JSON giờ lấy cùng một nguồn. File Word sinh lại giống hệt bản cũ, chỉ khác mã liên kết ngẫu nhiên mà thư viện `docx` tạo mới mỗi lần chạy.
- `tools/roadmap/export-json.js` sinh ra `database/data/roadmap.json` (~340 KB). Chạy bằng `cd tools/roadmap && npm run json`. Chỉ cần Node, không cần `npm install`. Script dừng với lỗi nếu tháng không nối tiếp nhau, tuần không khớp giai đoạn, hoặc tuần nào không đủ 6 ngày học.
- Nội dung JSON:
  - `resources`: 30 nguồn R0–R29 (`code`, `name`, `source`, `usage`). `source` có thể là 1 link, nhiều link nối bằng `  ·  `, hoặc chữ thường.
  - `stages`: 6 giai đoạn (`number`, `title` không kèm "Giai đoạn n ·", `goal`, `blocks` [`title`, `minutes`, `description`], `notes`). Tổng các khối cố định mỗi giai đoạn đều là 120 phút.
  - `months`: 24 tháng (`number`, `stage`, `first_week`, `last_week`, `focus`, `gate`).
  - `weeks`: 104 tuần (`number`, `month`, `stage`, `title`, `details` [`label`, `text`, `highlight`], `days`). Mỗi tuần có 7 ngày (`number` từ 1 đến 728, `day` từ 1 đến 7, `label`, `task`, `rest`); Ngày 7 luôn là ngày nghỉ.
- Test `tests/Unit/RoadmapDataTest.php` kiểm tra số lượng 6/24/104/728, đánh số liên tục, tuần nằm đúng tháng và giai đoạn, mỗi tuần 6 ngày học + 1 ngày nghỉ.

**Tiếp theo**
- P3-02: bảng `stages`, `months`, `weeks`, `roadmap_days` + seeder đọc `database/data/roadmap.json` (idempotent). Seeder cần chọn cách lưu `resources`, `blocks`, `details`, `notes`: bảng riêng hay cột JSON.

**Lưu ý**
- Muốn sửa nội dung lộ trình thì sửa `tools/roadmap/data.js` (hoặc `schedule.js`), chạy lại `npm run json`, rồi commit cả `data.js` lẫn `roadmap.json`. Không sửa tay file JSON.
- Câu chữ vẫn y như trong file Word, nên có chỗ nhắc "Phụ lục B/C", "mục Ghi nhớ ở trên", "xem đầu tháng". Khi làm giao diện (P3-04, P3-05) có thể cần sửa lại cho hợp với web.
- `tools/roadmap/node_modules` (thư viện `docx`) chỉ cần khi sinh lại file Word, và đã nằm trong `.gitignore`.
- Hai commit `docs` (P2-05) và `feat(P3-01)` chưa push. Push sẽ chạy deploy, việc này vô hại (`database/data/roadmap.json` sẽ lên server, P3-02 sẽ cần file này).

## 2026-10-29 · P3-02 (bảng lộ trình + seeder)
**Đã làm**
- Chốt với bạn (D15): các phần chỉ để hiển thị lưu bằng cột JSON, và seeder chạy mỗi lần deploy.
- Một migration `create_roadmap_tables` tạo 5 bảng:
  - `roadmap_resources`: `code`, `name`, `source`, `usage`.
  - `stages`: `number`, `title`, `goal`, `blocks` (JSON), `notes` (JSON).
  - `months`: `number`, `stage_id`, `first_week`, `last_week`, `focus`, `gate`.
  - `weeks`: `number`, `month_id`, `title`, `details` (JSON).
  - `roadmap_days`: `number` từ 1 đến 728, `week_id`, `day` từ 1 đến 7, `label`, `task`, `is_rest`.
  - Cột `number`/`code` là unique; các khóa ngoại dùng `constrained()`.
- 5 model `Stage`, `Month`, `Week`, `RoadmapDay`, `RoadmapResource`, có quan hệ `Stage→months`, `Month→stage/weeks`, `Week→month/days`, `RoadmapDay→week`, và cast cho các cột JSON và `is_rest`.
- `RoadmapSeeder` đọc `database/data/roadmap.json` và `upsert` theo `code`/`number` trong một transaction. Eloquent `upsert` không áp dụng cast, nên seeder tự `json_encode` các cột JSON. `DatabaseSeeder` gọi thêm seeder này.
- `deploy.yml`: bước "Migrate, seed roadmap and cache" chạy `migrate` → `db:seed --class=RoadmapSeeder --force` → `optimize`.
- Đã thử trên MariaDB 10.5 ở dev: migrate xong, seed 2 lần mỗi lần khoảng 0,3–0,6 giây, vẫn 728 ngày và id lớn nhất 728, cả 5 bảng InnoDB.
- Test `tests/Feature/Database/RoadmapSeederTest.php` kiểm tra số lượng, chuỗi quan hệ ngày → tuần → tháng → giai đoạn, cột JSON đọc ra mảng, và chạy lại thì cập nhật tại chỗ.

**Tiếp theo**
- Lần push tới sẽ chạy migration và seeder trên production lần đầu. Cần xem job deploy xanh và production có đủ 728 dòng `roadmap_days`.
- P3-03: bảng tiến độ `day_completions` (đã xong, số phút) và `gate_results`, đều có `user_id` và trỏ tới `roadmap_days.id` / `months.id`.

**Lưu ý**
- `main` có 5 commit chưa push (docs P2-05, feat + docs P3-01, feat + docs P3-02). Bạn chọn để tới khi task sau cần mới push.
- Ngày thứ mấy của lộ trình (`User::roadmapDayNumber()`) khớp với `roadmap_days.number`, không phải `id`.
- Seeder không xóa dòng nào. Nếu sau này bớt tuần hoặc ngày khỏi lộ trình thì cần migration dọn riêng (xem D15).

## 2026-10-29 · P3-03 (bảng tiến độ học)
**Đã làm**
- Chốt với bạn (D16): có dòng `day_completions` là ngày đã xong; cổng tháng chỉ ghi đạt/không đạt + ghi chú + thời điểm.
- Hai migration sinh bằng `make:model -m`:
  - `day_completions`: `user_id`, `roadmap_day_id`, `minutes` (mặc định 0), `completed_at` (UTC). Unique (`user_id`, `roadmap_day_id`).
  - `gate_results`: `user_id`, `month_id`, `passed`, `note` (được để trống), `checked_at` (UTC). Unique (`user_id`, `month_id`).
  - `user_id` xóa theo người dùng (`cascadeOnDelete`). `roadmap_day_id`/`month_id` không cascade, vì seeder không xóa dòng nào (D15).
- Model `DayCompletion`, `GateResult`, có quan hệ `User→dayCompletions/gateResults`, `RoadmapDay→completions`, `Month→gateResults` và chiều ngược lại. `user_id` không nằm trong `Fillable`: tạo bản ghi qua `$user->dayCompletions()->create(...)`.
- Đã migrate trên MariaDB 10.5 ở dev: hai bảng InnoDB, cột thời điểm là `datetime` không có `ON UPDATE`.
- Test `tests/Feature/Database/ProgressTablesTest.php` (6 test): quan hệ hai chiều, unique theo người dùng, hai người cùng tick một ngày được, xóa người dùng thì xóa tiến độ.

**Tiếp theo**
- P3-04: trang **Hôm nay**. Tick hoàn thành tạo hoặc xóa dòng `day_completions` của ngày `number = roadmapDayNumber()`, nhập phút học.

**Lưu ý**
- `main` có 7 commit chưa push (thêm feat + docs P3-03). Push đầu tiên sẽ chạy cả migration P3-02, P3-03 và `RoadmapSeeder` trên production.
- Cột thời điểm của bảng học viên nên dùng `datetime`, không dùng `timestamp` (xem D16).

## 2026-10-29 · P3-04 (trang Hôm nay)
**Đã làm**
- `/dashboard` (tên route vẫn là `dashboard`) do `TodayController` xử lý, view đổi tên thành `today.blade.php`. Trang lấy ngày `number = roadmapDayNumber()` từ DB và hiển thị:
  - Tuần/ngày, tên tuần, tháng, giai đoạn, mục tiêu phút.
  - Dải 7 ô N1–N7 của tuần: ô xanh là ngày đã xong, ô có viền chấm là ngày chưa tới, ô có vòng tím là hôm nay.
  - Việc của hôm nay, form nhập số phút (điền sẵn mục tiêu mỗi ngày), nút "Đánh dấu đã xong", sau khi tick thì có "Lưu số phút" và "Bỏ đánh dấu đã xong". Giờ tick hiển thị theo múi giờ người dùng.
  - Các khối cố định của giai đoạn (kèm tổng phút), các dòng thông tin của tuần (dòng `highlight` có nền vàng nhạt), và các tài liệu R0–R29 được nhắc tới trên trang (lấy mã `R\d+` trong các đoạn văn, có link nếu nguồn là URL).
- `PUT /days/{number}/completion` tick ngày hoặc sửa số phút (0–600). `DELETE` cùng đường dẫn bỏ tick. Chỉ tick được hôm nay và các ngày trước (học bù), ngày tương lai trả về 403 (`User::hasReachedRoadmapDay()`). Lưu lại thì đổi số phút nhưng giữ `completed_at` của lần tick đầu.
- Test: `TodayPageTest` (4), `DayCompletionTest` (12). `RoadmapDayTest` nay phải seed lộ trình vì trang Hôm nay đọc từ DB.
- Đã xem trên trình duyệt ở dev bằng một tài khoản thử tạm thời (đã xóa sau khi xem): tick 95 phút, trang hiện "Đã xong lúc 14:06 29/10/2026 · 95 phút", ô N3 chuyển xanh.

**Tiếp theo**
- P3-05: trang tuần/tháng. Có thể dùng lại route `days.completion.update/destroy` cho việc đánh dấu bù, và thêm form nhập kết quả cổng kiểm tra (`gate_results`).

**Lưu ý**
- `main` có 9 commit chưa push. Push đầu tiên sẽ chạy migration P3-02, P3-03 và `RoadmapSeeder` trên production. Nếu seeder chưa chạy thì trang Hôm nay chỉ còn lời chào (không lỗi).
- Nút bấm chính của Breeze tự viết hoa toàn bộ chữ ("ĐÁNH DẤU ĐÃ XONG").
