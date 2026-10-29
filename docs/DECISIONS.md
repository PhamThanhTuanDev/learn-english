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

## D9 · 2026-10-28 · Lưu giờ UTC, hiển thị theo múi giờ người dùng
Cột `users.timezone` (mặc định `Asia/Ho_Chi_Minh`) và `User::localNow()`. "Hôm nay" trong lộ trình tính theo giờ của người học: 18:30 UTC đã là ngày hôm sau ở Việt Nam. Trang Cài đặt (P1-03) sẽ cho đổi múi giờ.

## D10 · 2026-10-28 · Giao diện tiếng Việt
Chữ của framework và Breeze dịch bằng gói `laravel-lang` (dev dependency, file nằm trong `lang/vi`). Chữ riêng của app viết thẳng tiếng Việt trong Blade. Chạy `php artisan lang:update` có thể ghi đè chỉnh sửa tay trong `lang/vi.json` (ví dụ "Đăng xuất"), nên xem lại diff sau khi chạy.

## D8 · 2026-10-28 · Không làm theo hướng dẫn mặc định trong CLAUDE.md của bộ khung Laravel
Bộ khung sinh sẵn hướng dẫn chạy script cài PHP tải từ internet và cài Laravel Boost. Không làm vì dự án đã dùng PHP trong Docker. Laravel Boost (gói chính thức hỗ trợ AI) có thể cân nhắc lại sau.

## D11 · 2026-10-28 · Cấu trúc thư mục trên hosting: code ngoài `public_html`, document root là symlink
Code nằm ở `/home/qdgjatryhosting/learn-english`. Thư mục `public_html/english.minlish.site` (document root của subdomain) được thay bằng symlink trỏ tới `/home/qdgjatryhosting/learn-english/public`.
Lý do: cPanel chỉ cho đặt document root bên trong `public_html`. Nhưng `public_html` là web của tên miền chính kesat.vn, nên mọi file nằm dưới đó đều mở được qua `kesat.vn/...`. Dùng symlink thì `.env`, `vendor/`, `storage/` nằm ngoài web, `public/index.php` giữ nguyên, và chỉ cần một tài khoản FTP `deploy@english.minlish.site` giới hạn trong `~/learn-english`.
Dự phòng nếu hosting không đi theo symlink: chỉ đặt file public vào `public_html/english.minlish.site`, sửa `index.php` để trỏ tới `~/learn-english`, và deploy bằng 2 tài khoản FTP.

## D12 · 2026-10-29 · Deploy qua SSH + rsync thay cho FTP (thay phần "FTP" của D3)
GitHub Actions vẫn chạy CI rồi mới deploy, nhưng upload bằng `rsync` qua SSH tới `turboweb-082302.inet.vn`, cổng 65333 (cổng SSH mặc định của iNET), user `qdgjatryhosting`. Workflow dùng một khóa ed25519 chỉ để deploy, và ghim host key của server.
Lý do: FTP của hosting (Pure-FTPd) đang tắt TLS. Lệnh `AUTH TLS` trả về `500 This security scheme is not implemented` cả từ máy dev lẫn từ GitHub. Chỉ admin hosting mới bật được TLS, còn FTP thường thì gửi mật khẩu không mã hóa. SSH thì có mã hóa, rsync chỉ gửi phần thay đổi nên nhanh hơn FTP nhiều, và sau này chạy được `php artisan migrate` ngay trong workflow.
Đánh đổi: khóa SSH có quyền trên toàn bộ tài khoản cPanel, không bị giới hạn trong `~/learn-english` như tài khoản FTP. Để giảm rủi ro, khóa chỉ nằm trong GitHub Secrets, và trong `authorized_keys` bị tắt port/agent/X11 forwarding và pty.
Không dùng `rsync --delete`. Workflow ghi danh sách file đã deploy vào `~/learn-english/.deploy-manifest`, và chỉ xóa những file có trong danh sách lần trước mà lần này không còn. Vì vậy `.env`, dữ liệu trong `storage/` và `public/.well-known` không bị đụng tới.

## D13 · 2026-10-29 · `.env` production và migration
`.env` production chỉ nằm trên server (`~/learn-english/.env`, quyền 600). Nó được tạo qua SSH, còn mật khẩu DB do bạn tự nhập bằng lệnh có prompt ẩn. Deploy không bao giờ upload hay xóa file này.
Mỗi lần deploy, workflow SSH vào server chạy `php artisan migrate --force` rồi `php artisan optimize`. Tài khoản chủ tạo bằng `php artisan app:create-owner` trong Terminal cPanel (mật khẩu nhập ẩn), không qua `OWNER_PASSWORD` trong `.env`.
Bảng MariaDB luôn tạo bằng InnoDB (`engine` trong `config/database.php`), vì MariaDB của hosting mặc định dùng MyISAM: không có transaction, không có khóa ngoại, và key tối đa 1000 byte.
Lý do: có SSH nên migration chạy tự động được, push là xong, không phải nhớ làm tay. Mật khẩu không bao giờ nằm trong repo, trong GitHub hay trong lịch sử lệnh.

## D14 · 2026-10-29 · Ép HTTPS bằng rule trong `public/.htaccess`, có HSTS
Rule trong `public/.htaccess` chuyển hướng 301 mọi request http tới `english.minlish.site` sang `https://english.minlish.site`, giữ nguyên đường dẫn và query. Bỏ qua `/.well-known/acme-challenge/`, `cpanel-dcv/` và `pki-validation/` để AutoSSL vẫn xác thực được qua http khi gia hạn chứng chỉ. Rule chỉ áp dụng cho đúng host này, nên dev (`php artisan serve`, không đọc `.htaccess`) không bị ảnh hưởng.
Header `Strict-Transport-Security: max-age=31536000` (1 năm, không `includeSubDomains`, nên các subdomain khác của `minlish.site` không bị ảnh hưởng). Cookie luôn có cờ Secure nhờ `SESSION_SECURE_COOKIE=true` trong `.env` trên server.
Lý do: rule nằm trong git và đi theo mỗi lần deploy, và chuyển hướng được cả file tĩnh. Nút Force HTTPS của cPanel không nằm trong repo. Còn redirect trong Laravel thì không chặn được file tĩnh, vì LiteSpeed trả các file này trực tiếp.
Đánh đổi của HSTS: nếu AutoSSL gia hạn lỗi và chứng chỉ hết hạn, trình duyệt sẽ chặn hẳn trang, không cho bấm bỏ qua. Khi đó sửa chứng chỉ trong cPanel → SSL/TLS Status. Muốn bỏ HSTS thì đặt `max-age=0` và đợi trình duyệt nhận header mới; không chỉ xóa header là xong.
