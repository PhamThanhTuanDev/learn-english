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
- Đã push 9 commit (đến `1d527e0`) ngày 29/10. Job Deploy xanh: bước `migrate` → `db:seed --class=RoadmapSeeder` → `optimize` chạy nối bằng `&&` và thành công, nên production đã có các bảng P3-02, P3-03 và dữ liệu lộ trình. Chưa đếm trực tiếp số dòng `roadmap_days` trên production.
- Nút bấm chính của Breeze tự viết hoa toàn bộ chữ ("ĐÁNH DẤU ĐÃ XONG").

## 2026-10-29 · P3-05 (trang tuần/tháng, học bù, cổng kiểm tra)
**Đã làm**
- Chốt với bạn (D17): trang 3 cấp; cổng mở từ ngày đầu tuần cuối tháng; có nút xóa kết quả cổng.
- `/roadmap` (`RoadmapController`): 24 tháng xếp theo giai đoạn, mỗi tháng có số ngày đã xong / tổng số ngày, trạng thái cổng ("Cổng: ✓ Đạt", "✗ Chưa đạt", "chưa kiểm tra"). Tháng đang học có nền tím nhạt, có link tới tuần hiện tại.
- `/roadmap/months/{n}` (`MonthController@show`): trọng tâm tháng, các tuần (link + dải N1–N7 + x/7 ngày), khối "Cổng kiểm tra cuối tháng" (`#gate`): nội dung cổng, kết quả đã lưu (giờ theo múi giờ người dùng), form Đạt/Chưa đạt + ghi chú (≤ 2000 ký tự), nút "Xóa kết quả" (có hộp xác nhận). Chưa tới tuần cuối thì báo ngày mở cổng.
- `/roadmap/weeks/{n}` (`WeekController@show`): 7 ngày kèm ngày theo lịch, việc cần làm, trạng thái. Ngày đã tới có form số phút (nút "Đánh dấu học bù" cho ngày đã qua, "Đánh dấu đã xong" cho hôm nay), ngày tương lai ghi "Chưa tới ngày này.". Tick muộn hơn ngày theo lịch thì hiện "· học bù". Tuần cuối tháng có link sang cổng.
- `PUT/DELETE /months/{n}/gate` (`GateResultController`, `UpdateGateResultRequest`): lưu qua `$user->gateResults()->updateOrCreate(...)`, `checked_at = now()`. Trước khi mở cổng trả 403.
- Dải N1–N7 tách thành component `<x-week-days>` (dùng ở Hôm nay, trang tháng, trang tuần). Trang Hôm nay có link "Xem cả tuần" và "Tháng N và cổng kiểm tra". Menu "Lộ trình" sáng cả ở trang con.
- Thêm `Month::days()` (hasManyThrough), `Month::gateOpensOnDay()`, `User::roadmapDayDate()`. `DayCompletionController@update` flash thêm `saved_day` để trang tuần biết form nào vừa lưu.
- Test: `RoadmapPagesTest` (10), `GateResultTest` (8). Cả bộ 103 test xanh.
- Đã xem ở dev bằng tài khoản thử tạm thời (đã xóa): lưu cổng "Chưa đạt" kèm ghi chú, đánh dấu học bù N3, trang danh sách tháng hiện "3/28 ngày · Cổng: ✗ Chưa đạt".

**Tiếp theo**
- P3-06: Dashboard (% hoàn thành, chuỗi ngày học liên tiếp, tổng giờ theo tháng).

**Lưu ý**
- Chưa push: `main` có `de7ef28` và 2 commit của P3-05. Không có migration mới.
- Trang tuần có nhiều form cùng tên `minutes`: mỗi form gửi kèm `day` (input ẩn) để chỉ form vừa gửi hiện lại giá trị cũ và lỗi.
- Test tạo user bằng factory phải `->refresh()`, nếu không `timezone` còn null và "hôm nay" bị tính theo UTC.

## 2026-10-29 · P3-06 (thống kê trên trang Hôm nay)
**Đã làm**
- Chốt với bạn (D18): thống kê đặt trên trang Hôm nay; chuỗi ngày tính theo ngày lịch có tick, ngày nghỉ không tick không làm đứt chuỗi; thời gian tính theo tháng lộ trình; vẽ bằng thanh ngang CSS.
- `App\Support\StudyStats::for($user)`: số ngày đã xong / 728 và %, tổng phút, trung bình phút mỗi ngày đã xong, chuỗi hiện tại và dài nhất, số cổng đạt / 24, phút và số ngày theo từng tháng đã tới (kèm % so với tháng nhiều nhất). `StudyStats::duration()` đổi phút thành "2 giờ 30 phút".
- Trang Hôm nay (`TodayController`, `today.blade.php`): 4 ô số ngay dưới khối chào (2 cột trên điện thoại, 4 cột trên màn rộng), khối "Thời gian học theo tháng" ở cuối trang (link tới trang tháng, nhãn "đang học"). Chưa bắt đầu lộ trình thì không hiện. Sau ngày 728 vẫn hiện.
- Test `StudyStatsTest` (6): %, tổng giờ, cổng; chuỗi theo giờ Việt Nam (giờ UTC rơi vào ngày khác); ngày nghỉ và hôm nay không làm đứt chuỗi, ngày thiếu thì đứt, học bù tính cho ngày tick; phút tính vào tháng của ngày, tháng chưa tới bị ẩn; số 0 ở ngày đầu; chưa bắt đầu thì không hiện. Cả bộ 109 test xanh.
- Đã xem ở dev bằng tài khoản thử tạm thời (đã xóa): 30/728 ngày (4,1%), chuỗi 15 ngày, 51 giờ 20 phút, cổng 1/24, thanh tháng 1 và 2. Khổ 375px không tràn ngang.

**Tiếp theo**
- Phase 3 xong. Phase 4: P4-01 bảng `words` + lệnh `app:import-vocabulary` đọc CSV.

**Lưu ý**
- Chưa push: `main` có `de7ef28`, 2 commit của P3-05 và 2 commit của P3-06. Không có migration mới.
- Chuỗi ngày dựa vào ngày bắt đầu hiện tại để biết ngày nào là ngày nghỉ; đổi ngày bắt đầu thì ngày nghỉ dịch theo.
- Cửa sổ app bị ẩn thì trình duyệt dựng sẵn không chụp màn hình hay click được; đăng nhập bằng `requestSubmit()` và kiểm tra bố cục bằng số đo DOM.

## 2026-10-29 · P4-01 (bảng từ vựng và lệnh import)
**Đã làm**
- Chốt với bạn (D19): commit CSV vào repo dù repo public; level không phải CEFR thì để trống.
- Bảng `words` (`word`, `part_of_speech`, `pronunciation`, `meaning`, `definition`, `definition_vi`, `example`, `example_vi`, `level`), unique theo `word` + `part_of_speech`, index `level`. Model `Word`.
- `database/data/vocabulary.csv` (bản chép của `../vocabulary-set-33.csv`, có BOM).
- Lệnh `app:import-vocabulary [path]`: đọc theo tên cột ở dòng đầu (bỏ BOM), cần cột `word` và `meaning`; upsert 500 dòng một lần trong transaction; in "Đã nhập N từ, bỏ qua M dòng."
- Deploy chạy thêm `php artisan app:import-vocabulary` sau RoadmapSeeder.
- Test `ImportVocabularyCommandTest` (5): chuẩn hóa level và loại từ, chạy lại cập nhật tại chỗ và giữ từ đã bỏ, bỏ qua dòng thiếu và giữ dòng trùng cuối, báo lỗi khi thiếu file/cột, import file thật (7.463 từ). Cả bộ 114 test xanh.
- Đã chạy ở dev (MariaDB) hai lần: vẫn 7.463 từ; level A1 808, A2 629, B1 593, B2 1.347, C1 1.130, C2 236, null 2.720; 348 từ không có loại từ.

**Tiếp theo**
- P4-02: bảng `cards` + thuật toán lập lịch ôn *(cần chốt: SM-2 hay FSRS)*.

**Lưu ý**
- Chưa push: `main` có `de7ef28`, 2 commit của P3-05, 2 commit của P3-06 và 2 commit của P4-01. Có migration mới (`words`); lần deploy tới sẽ tạo bảng và import khoảng 7.500 từ.
- Ở dev, import mất khoảng 8 giây (Docker trên ổ D). Test import file thật mất vài giây trong bộ test.
- Sửa CSV thì sửa `database/data/vocabulary.csv` trong repo rồi deploy; file ở thư mục cha không còn được dùng.

## 2026-10-29 · P4-02 (thẻ ôn và SM-2)
**Đã làm**
- Chốt với bạn (D20): SM-2 gốc, lưu lịch sử ôn, hạn ôn theo ngày.
- Bảng `cards` (`user_id`, `word_id`, `ease_factor`, `interval_days`, `repetitions`, `lapses`, `due_on`, `last_reviewed_at`; unique `user_id` + `word_id`, index `user_id` + `due_on`) và `card_reviews` (`user_id`, `card_id`, `rating`, `ease_factor`, `interval_days`, `due_on`, `reviewed_at`). Xóa người dùng thì xóa thẻ và lịch sử ôn; từ vẫn giữ.
- Enum `App\Enums\Rating` (Again/Hard/Good/Easy = 1–4, `quality()` ra q của SM-2), `App\Support\Sm2::next()` (hàm thuần), `App\Actions\ReviewCard::handle($card, $rating)`. Thêm `User::cards()`, `User::cardReviews()`.
- Test: `tests/Unit/Sm2Test.php` (5: chuỗi 1 → 6 → 15 → 38 ngày, Hard/Easy đổi hệ số, làm tròn lên, sàn 1,3, Again), `ReviewCardTest` (4: hạn tính theo hôm nay giờ Việt Nam, quên thẻ đã thuộc, Again thẻ mới không tính quên, xóa người dùng). Cả bộ 123 test xanh.
- Đã chạy thử trên MariaDB dev (dữ liệu thử đã xóa): Good, Good, Hard, Again → hạn 30/10, 04/11, 13/11, rồi về 29/10.

**Tiếp theo**
- P4-03: chọn từ mới theo giai đoạn (8/10/12/15/10 từ mỗi ngày, đúng mức CEFR) → tạo thẻ với `due_on` = hôm nay.

**Lưu ý**
- Chưa push: `main` hơn origin 9 commit (gồm P3-05, P3-06, P4-01, P4-02). Có 3 migration mới (`words`, `cards`, `card_reviews`).
- Tạo thẻ qua `$user->cards()->create([...])`; ghi lịch sử ôn chỉ qua `ReviewCard`, không ghi `card_reviews` trực tiếp.
- Cột là `interval_days` vì `interval` là từ khóa của MariaDB.

## 2026-10-29 · P4-03 (chọn từ mới theo giai đoạn)
**Đã làm**
- Chốt với bạn (D21): hết từ đúng mức thì lấy mức thấp hơn còn sót, rồi mức cao hơn, cuối cùng là từ chưa có mức; giai đoạn 5 lấy C1–C2 thay cho AWL; tuần thi thử 5 từ/ngày; ngày nghỉ và ngày bỏ lỡ không dồn. Tuần ôn trước cổng (13, 26, 39, 47, 65, 69) không thêm từ, đúng như ghi chú của tuần.
- `App\Support\NewWordPlan` (hàm thuần): `quota($stage, $week)`, `stageLevels()`, `levelOrder()`. `App\Actions\AddNewCards::handle($user)`: lấy ngày lộ trình hôm nay, trừ số thẻ đã tạo từ 0 giờ (giờ người học), chọn từ chưa có thẻ theo thứ tự mức rồi id, tạo thẻ với `due_on` = hôm nay bằng `insertOrIgnore`. Trả về số thẻ đã thêm.
- `WordFactory` (state `level()`), `Word` dùng `HasFactory`.
- Test: `NewWordPlanTest` (2: thứ tự mức, hạn mức khớp ghi chú "Từ vựng" của cả 104 tuần), `AddNewCardsTest` (5 + 9 trường hợp hạn mức: 8 từ A1 đúng một lần, ngày theo giờ Việt Nam và không dồn ngày bỏ lỡ, thứ tự lấy khi hết mức, chưa bắt đầu thì không thêm). Cả bộ 138 test xanh.
- Đã chạy thử trên MariaDB dev trong transaction rồi rollback: ngày 57 → 10 từ A1, 183 → 12 B1, 330 → 15 B2, 505 → 5 C1, 85 và 666 → 0; gọi lần hai đều ra 0.

**Tiếp theo**
- P4-04: màn hình ôn (Alpine). Khi mở màn hình thì gọi `AddNewCards` trước, rồi lấy thẻ có `due_on` ≤ hôm nay; trả lời qua `ReviewCard`.

**Lưu ý**
- Chưa push: `main` hơn origin 12 commit (P3-05, P3-06, P4-01, P4-02, P4-03). P4-03 không có migration mới.
- `AddNewCards` chưa được gọi ở đâu; P4-04 sẽ gọi.
- Thẻ mới được tạo bằng `insertOrIgnore` (nhanh, và hai request cùng lúc không làm vượt hạn mức), nên không đi qua `$user->cards()->create()`.

## 2026-10-29 · P4-04 (màn hình ôn flashcard)
**Đã làm**
- `/flashcards` (thay trang "sắp có"): `FlashcardController@index` gọi `AddNewCards` trước, rồi lấy mọi thẻ có hạn đến hôm nay. Thẻ đã ôn xếp trước (theo hạn rồi id), thẻ mới xếp sau. `POST /flashcards/{card}/review` (`flashcards.review`) nhận `rating` 1–4, chấm qua `ReviewCard`, trả JSON `{due_on, due_today}`. Thẻ của người khác trả 404.
- Component Alpine `flashcardReview` (`resources/js/flashcard-review.js`, đăng ký trong `app.js`) và view `flashcards/index.blade.php`:
  - Mặt trước: từ, loại từ, phiên âm, mức, nhãn "Từ mới", nút Nghe. Mặt sau: nghĩa tiếng Việt, định nghĩa Anh–Việt, câu ví dụ kèm nút nghe.
  - 4 nút Quên/Khó/Nhớ/Dễ (`Rating::label()`). Thẻ bấm Quên quay lại cuối hàng.
  - Đếm số thẻ còn lại, số từ mới và số lần đã trả lời; có màn hình "Xong!". Chưa chọn ngày bắt đầu thì có hướng dẫn và link tới Cài đặt học.
- Phát âm dùng `speechSynthesis` của trình duyệt, ưu tiên giọng en-GB (khớp phiên âm Oxford), tốc độ 0,9. Tự đọc từ khi lật thẻ, vì trình duyệt chặn đọc khi người dùng chưa bấm gì.
- Phím tắt: Space/Enter lật thẻ, 1–4 chấm, P nghe lại; bỏ qua khi đang ở ô nhập hoặc link, hoặc khi có giữ Ctrl/Alt/Cmd. Đang lưu thì bỏ qua lần bấm tiếp.
- Test `FlashcardReviewTest` (4): thứ tự hàng thẻ và thêm từ mới, chưa có ngày bắt đầu, chấm Nhớ rồi Quên, bắt buộc rating hợp lệ và đúng chủ thẻ (khách bị chuyển sang đăng nhập). Cả bộ 142 test xanh.
- Đã thử ở dev bằng tài khoản tạm (đã xóa): lật bằng phím và bằng click, Quên đưa thẻ về cuối hàng, tải lại trang thì thẻ đó lên đầu và không thêm từ mới lần nữa, ôn hết thì hiện "Xong!". DB khớp: 8 thẻ, 9 lần ôn. Khổ 375px không tràn ngang, 4 nút 80×62px.

**Tiếp theo**
- P4-05: thống kê flashcard (thẻ đến hạn, đã học, tỉ lệ nhớ) từ `cards` và `card_reviews`.

**Lưu ý**
- Chưa push: `main` hơn origin 14 commit (P3-05 → P4-04). Không có migration mới.
- Lọc thẻ đến hạn bằng `due_on < ngày mai`, không dùng `<= hôm nay`: SQLite (test) lưu cột date qua cast thành `Y-m-d 00:00:00`, so chuỗi với `Y-m-d` bị sai. MariaDB thì không bị.
- Tài khoản owner trên dev đã có 8 thẻ, tạo khi trang `/flashcards` được mở lúc 16:21 ngày 29/10. Đó không phải dữ liệu thử nên mình giữ nguyên.
- Khung trình duyệt dựng sẵn bị ẩn thì Alpine vẽ lại trễ (x-show dùng requestAnimationFrame); kiểm tra bằng `Alpine.$data()` và chờ một lúc rồi đo DOM.
- `public/build` không commit; CI tự build. Ở dev chạy `npm run build` (hoặc `npm run dev`) sau khi sửa JS.

## 2026-10-29 · Push + P4-05 (thống kê flashcard)
**Đã làm**
- Push 14 commit (P3-05 → P4-04). Deploy tự chạy thành công (test, migrate 3 bảng mới, import từ vựng); production trả 200.
- Chốt với bạn (D22): thống kê ở trang Flashcard và trang Hôm nay; tỉ lệ nhớ 30 ngày chỉ tính thẻ đã nhớ ở lần trước; có số đã học và đã thuộc; có dự báo 7 ngày tới.
- `App\Support\FlashcardStats::for($user)`: `due`, `dueNew`, `pendingNew`, `answeredToday`, `learned`, `known`, `retentionReviews`/`retentionPassed` (`retentionPercent()`), `forecast` (7 ngày, kèm % so với ngày nhiều thẻ nhất). `AddNewCards::remaining()` đếm từ mới còn thiếu của hôm nay mà không tạo thẻ (tách `studyDay()`/`missing()` dùng chung với `handle()`).
- Trang Flashcard: 3 ô số và khối "Thẻ đến hạn 7 ngày tới" dưới phần ôn, chỉ hiện khi đã học ít nhất 1 từ. Trang Hôm nay: khối "Flashcard" ngay dưới 4 ô thống kê, có nút "Ôn ngay" (hết thẻ thì là "Xem thống kê").
- Test `FlashcardStatsTest` (4): các số đếm và ranh giới ngày theo giờ Việt Nam, tỉ lệ nhớ (bỏ lần đầu, lần ôn lại sau Quên, lần ngoài 30 ngày, thẻ người khác), trang Hôm nay đếm từ mới mà không tạo thẻ, ẩn khi chưa bắt đầu hoặc chưa học. Cả bộ 146 test xanh.
- Đã chạy truy vấn `lag()` và nhóm theo ngày trên MariaDB dev (rollback): tỉ lệ 2/3 đúng, dự báo đúng ngày. Xem trên trình duyệt bằng tài khoản tạm (đã xóa): sau 6 lần trả lời (1 Quên), trang Hôm nay báo "còn 3 thẻ (2 từ mới)", trang Flashcard báo đã học 6 và ngày mai 5 thẻ; khổ 375px không tràn ngang.

**Tiếp theo**
- Phase 4 xong. Phase 5: P5-01 Sổ lỗi (thêm/sửa/xóa lỗi: câu sai, câu đúng, loại lỗi, tuần; tăng số lần lặp lại).

**Lưu ý**
- Chưa push: `main` hơn origin 2 commit (P4-05). Không có migration mới.
- Các số trên trang Flashcard chụp lúc mở trang, không tự cập nhật khi đang ôn (bộ đếm "Còn N thẻ" và "Đã trả lời" thì có).
- `pendingNew` giả định danh sách từ chưa hết (chỉ hết khi đã dùng gần 7.463 từ).

## 2026-10-30 · P5-01 (Sổ lỗi: thêm/sửa/xóa, +1 lần)
**Đã làm**
- Chốt với bạn (D23): 8 loại lỗi cố định theo Phụ lục A, thêm ô ghi chú không bắt buộc.
- Bảng `mistakes`, model `Mistake` (+ factory), enum `MistakeCategory` (`label()` tiếng Việt), `MistakeRequest`, `MistakeController` (index/store/edit/update/destroy/repeat; lỗi của người khác trả 404). `User::mistakes()`, `User::roadmapWeekNumber()`.
- Trang `/mistakes` thay trang "sắp có": form "Ghi lỗi mới" (mặc định tuần hiện tại và loại vừa dùng), danh sách mới nhất ở trên (câu sai gạch đỏ, câu đúng màu xanh, ghi chú, ngày theo giờ người học, "Đã gặp N lần", nút +1 lần / Sửa / Xóa), phân trang 30. Sau "+1 lần" trang quay về đúng lỗi đó (`#mistake-{id}`).
- Test `MistakeLogTest` (7): giá trị mặc định của form, danh sách và thứ tự, thêm (bỏ qua số lần gửi lên), kiểm tra dữ liệu, +1 lần, sửa/xóa, không đụng được lỗi người khác và xóa theo người dùng. Cả bộ 153 test xanh.
- Đã thử ở dev bằng tài khoản tạm (đã xóa): thêm 2 lỗi, +1, sửa (đổi câu, số lần, ghi chú), xóa; DB khớp. Khổ 375px không tràn ngang.

**Tiếp theo**
- P5-02: lọc theo loại lỗi; danh sách "3 lỗi ưu tiên" cho Ngày 6 (Phụ lục A: lỗi lặp từ 3 lần là lỗi ưu tiên).

**Lưu ý**
- Chưa push: `main` hơn origin 2 commit. Có migration mới (`mistakes`); deploy sẽ tạo bảng.
- Hôm nay Docker Desktop chưa chạy khi mở phiên (máy khởi động lại); đã bật bằng tay rồi `docker compose up -d`.
- `php artisan serve` trong container chỉ có 1 luồng: thỉnh thoảng một request bị kẹt 1–3 phút sau khi trình duyệt dựng sẵn tải trang (có thể do kết nối giữ sẵn của trình duyệt). Nếu phiền có thể đặt `PHP_CLI_SERVER_WORKERS` cho service `app` (cần chốt trước khi đổi cấu hình Docker).
- Sau khi thêm view mới phải `npm run build` thì class Tailwind mới có trong CSS ở dev (CI tự build cho production).

## 2026-10-30/31 · Push P5-01 + P5-02 (lọc và 3 lỗi ưu tiên)
**Đã làm**
- Push 2 commit của P5-01; deploy thành công (migrate bảng `mistakes`), production trả 200.
- Chốt với bạn (D24): app gợi ý lỗi gặp từ 3 lần, người học tự ghim tối đa 3; danh sách hiện ở Sổ lỗi và trang Hôm nay mỗi ngày, Ngày 6 có lời nhắc.
- Migration thêm `mistakes.pinned_at`. Scope `Mistake::pinned()` / `Mistake::suggested()` (attribute `#[Scope]`), hằng `MAX_PINNED`, `PRIORITY_OCCURRENCES`. Route `mistakes.pin` (POST) / `mistakes.unpin` (DELETE); quay về `#priorities`.
- Trang Sổ lỗi: khối "3 lỗi ưu tiên" (đã ghim + gợi ý), lọc theo loại `?category=` với số lượng mỗi loại (giữ bộ lọc khi phân trang), nhãn "Đang ghim"/"Ưu tiên", nút Ghim/Bỏ ghim ở từng lỗi. Component `x-mistake-summary` dùng chung với trang Hôm nay.
- Trang Hôm nay: khối "Lỗi ưu tiên tuần này" ngay dưới khối Flashcard.
- Test `MistakePrioritiesTest` (4): lọc và số lượng (loại lạ thì hiện tất cả), thứ tự gợi ý, giới hạn 3 và thứ tự ghim, bỏ ghim, không ghim được lỗi người khác, trang Hôm nay ngày thường/Ngày 6. Cả bộ 157 test xanh.
- Đã thử ở dev bằng tài khoản tạm ở Ngày 6 (đã xóa): gợi ý đúng thứ tự, bấm Ghim chuyển lỗi lên danh sách ưu tiên, trang Hôm nay có lời nhắc và lỗi đã ghim, lọc "Ngữ pháp (2)" đúng; khổ 375px không tràn ngang.

**Tiếp theo**
- Phase 5 xong. Phase 6: P6-01 kết quả cổng kiểm tra và thi thử IELTS theo 4 kỹ năng, Overall làm tròn theo quy tắc IELTS *(nên chốt cấu trúc bảng điểm trước)*.

**Lưu ý**
- Chưa push: `main` hơn origin 2 commit. Có migration mới (`pinned_at`).
- Phiên này bị ngắt qua đêm: Docker còn chạy nhưng container đã dừng, phải `docker compose up -d` lại.
- Tài khoản owner trên dev hiện có 16 thẻ (bạn mở trang Flashcard thêm một ngày); giữ nguyên.
