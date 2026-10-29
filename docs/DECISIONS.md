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

## D15 · 2026-10-29 · Dữ liệu lộ trình nằm trong DB, nạp lại từ `roadmap.json` mỗi lần deploy
5 bảng: `stages`, `months`, `weeks`, `roadmap_days` (728 ngày) và `roadmap_resources` (R0–R29). Các phần chỉ dùng để hiển thị là cột JSON: khối cố định mỗi ngày và ghi chú của giai đoạn (`stages.blocks`, `stages.notes`), các dòng thông tin của tuần (`weeks.details`). Những phần này luôn đọc cùng bảng cha và không cần truy vấn riêng.
`RoadmapSeeder` đọc `database/data/roadmap.json`, rồi `upsert` theo `code`/`number` trong một transaction. Dòng có sẵn được cập nhật tại chỗ và id không đổi, nên tiến độ học (P3-03) trỏ vào `roadmap_days.id` không bị ảnh hưởng. Deploy chạy seeder ngay sau `migrate`.
Lý do: sửa `tools/roadmap/data.js` → `npm run json` → push là DB production tự cập nhật, không phải làm tay. Seeder chạy khoảng 0,5 giây.
Giới hạn: seeder không xóa dòng nào. Nếu sau này bớt tuần hoặc ngày khỏi lộ trình thì phải viết migration dọn riêng.

## D16 · 2026-10-29 · Bảng tiến độ học: có dòng là đã xong, cổng tháng chỉ ghi đạt/không đạt
- `day_completions`: có dòng nghĩa là ngày đó đã học xong; bỏ tick thì xóa dòng. Cột `minutes` (mặc định 0) và `completed_at` (UTC, bắt buộc). So `completed_at` với ngày theo lịch để biết học bù hay đúng ngày, và dùng nó để tính chuỗi ngày học. Không có cột ghi chú (lỗi sai ghi vào Sổ lỗi ở Phase 5).
- `gate_results`: mỗi người một kết quả cho mỗi tháng, làm lại thì ghi đè. Cột `passed`, `note` (được để trống), `checked_at` (UTC). Điểm chi tiết từng kỹ năng để P6-01 làm bảng riêng.
- Cả hai bảng unique theo `user_id` + ngày/tháng; xóa người dùng thì xóa luôn tiến độ của họ.
- Cột thời điểm dùng kiểu `datetime`, không dùng `timestamp`. Trên MariaDB 10.5 (`explicit_defaults_for_timestamp` = 0), cột `timestamp NOT NULL` đầu tiên của bảng tự nhận `ON UPDATE CURRENT_TIMESTAMP`, nên sửa số phút sẽ làm `completed_at` đổi theo.

## D17 · 2026-10-29 · Trang lộ trình 3 cấp, cổng tháng mở từ tuần cuối và xóa được
- Ba trang: `/roadmap` (24 tháng theo giai đoạn) → `/roadmap/months/{n}` (các tuần + cổng cuối tháng) → `/roadmap/weeks/{n}` (7 ngày, tick và học bù).
- Kết quả cổng của một tháng nhập được từ ngày 1 của tuần cuối tháng (`Month::gateOpensOnDay()`), để làm cổng sớm vài ngày vẫn ghi được. Sau đó lúc nào cũng nhập hoặc sửa được (khi học bù).
- Có nút xóa kết quả cổng: xóa dòng `gate_results`, tháng quay về "chưa kiểm tra". Làm lại cổng thì vẫn ghi đè và `checked_at` đổi thành lúc lưu mới.

## D18 · 2026-10-29 · Thống kê nằm trên trang Hôm nay, chuỗi ngày tính theo ngày lịch có tick
- Không làm trang riêng: 4 ô số ở đầu trang Hôm nay (% hoàn thành = ngày đã xong / 728, chuỗi ngày học, tổng thời gian, số cổng đạt / 24), khối "Thời gian học theo tháng" ở cuối trang. Chỉ hiện khi lộ trình đã bắt đầu. Tính trong `App\Support\StudyStats`.
- Chuỗi ngày học đếm các ngày lịch (giờ của người học) có ít nhất một tick theo `completed_at`. Học bù tính cho ngày tick, không nối lại chuỗi cũ. Ngày nghỉ của lộ trình (N7) không tick thì không làm đứt chuỗi và cũng không cộng; có tick thì cộng 1. Hôm nay chưa tick thì chuỗi vẫn giữ tới hết ngày. Có thêm chuỗi dài nhất.
- Thời gian theo tháng tính theo tháng lộ trình (1–24): cộng số phút của các ngày thuộc tháng đó, kể cả ngày học bù về sau. Chỉ hiện các tháng đã tới. Vẽ bằng thanh ngang CSS (độ dài so với tháng nhiều phút nhất), không thêm thư viện biểu đồ.

## D19 · 2026-10-29 · CSV từ vựng nằm trong repo, deploy tự import
- `../vocabulary-set-33.csv` được chép vào `database/data/vocabulary.csv` và commit. Repo đang public và phần định nghĩa/ví dụ tiếng Anh giống từ điển Oxford; bạn đã biết điều này và vẫn chọn cách này cho tiện deploy (ngoại lệ của quy tắc không đưa nội dung có bản quyền vào repo).
- `php artisan app:import-vocabulary [path]` upsert theo `word` + `part_of_speech`, chạy sau RoadmapSeeder ở mỗi lần deploy. Từ bị bỏ khỏi CSV vẫn giữ trong bảng (thẻ ôn ở P4-02 có thể trỏ tới). Trùng từ + loại từ trong file thì dòng sau thắng; dòng thiếu từ hoặc nghĩa bị bỏ qua.
- `level` chỉ nhận A1–C2 (không phân biệt hoa thường); rỗng, `undefined`, `intermediate`, `advanced`, `Symbol` thành null (2.720 từ). Loại từ rỗng hoặc `undefined` lưu là chuỗi rỗng, không phải null, để khóa unique vẫn khớp khi import lại.
- `words` là từ điển dùng chung nên không có `user_id`. Bỏ 3 cột luôn trống trong CSV (`fixed_phrase`, `related_words`, `notes`). Đổi tên cột: `type` → `part_of_speech`, `description` → `definition`, `example_sentence` → `example`.

## D20 · 2026-10-29 · Flashcard: SM-2 gốc, hạn ôn theo ngày, lưu lịch sử ôn
- Thuật toán SM-2 gốc (`App\Support\Sm2`). 4 nút: Again = quên (q < 3), Hard/Good/Easy = q 3/4/5. Trả lời đúng: khoảng cách 1 ngày → 6 ngày → khoảng cách trước × hệ số dễ, làm tròn lên. Hệ số dễ bắt đầu 2,5, đổi theo q sau khi đã dùng để tính khoảng cách, thấp nhất 1,3. Again: đếm lại từ đầu, hạn = hôm nay (ôn lại cuối buổi), giữ nguyên hệ số dễ; chỉ tính là một lần quên (`lapses`) nếu thẻ đã thuộc trước đó. FSRS để sau, khi đã có lịch sử ôn.
- `cards`: một thẻ cho mỗi người học và mỗi từ. Hạn ôn `due_on` là ngày theo lịch của người học (kiểu date), tính từ "hôm nay" theo múi giờ người học; thẻ đến hạn khi `due_on` ≤ hôm nay.
- `card_reviews`: mỗi lần bấm nút ghi một dòng (nút, hệ số dễ, khoảng cách và hạn mới, `reviewed_at` UTC), để tính tỉ lệ nhớ ở P4-05 và chuyển sang FSRS sau này. Ôn thẻ qua `App\Actions\ReviewCard` (cập nhật thẻ + ghi lịch sử trong một transaction).

## D21 · 2026-10-29 · Từ mới mỗi ngày: theo giai đoạn, hết mức thì lấy mức khác, không dồn ngày bỏ lỡ
- Số từ mới mỗi ngày học: giai đoạn 1–5 lần lượt 8/10/12/15/10; tuần thi thử của giai đoạn 5 (73, 78, 82, 86, 91, 95) còn 5; tuần ôn trước cổng (13, 26, 39, 47, 65, 69), ngày nghỉ và giai đoạn 6 không có. Các con số nằm trong `App\Support\NewWordPlan`; test `NewWordPlanTest` đối chiếu với ghi chú "Từ vựng" của từng tuần trong `roadmap.json`.
- Mức của từng giai đoạn: 1 = A1; 2 = A1–A2; 3 = B1; 4 = B2–C1; 5 = C1–C2, vì CSV không đánh dấu từ nào thuộc AWL. Hết từ đúng mức thì lấy theo thứ tự: mức thấp hơn còn sót → mức cao hơn → từ chưa có mức. Trong cùng mức, lấy theo thứ tự trong CSV (id). Nếu học đủ mọi ngày thì B1 hết ở ngày học thứ 50 của giai đoạn 3, C1 hết trong giai đoạn 4, và giai đoạn 5 nhận 231 từ C2 còn lại rồi khoảng 1.150 từ chưa có mức. Tổng cộng dùng khoảng 5.900 trên 7.463 từ. Có danh sách AWL thì bổ sung sau.
- `App\Actions\AddNewCards::handle($user)` tạo thẻ hạn hôm nay cho phần còn thiếu của hôm nay. Thẻ đã tạo từ 0 giờ theo giờ người học được tính vào hạn mức, nên gọi nhiều lần trong ngày vẫn chỉ đủ một lần. Ngày bỏ lỡ không dồn sang hôm sau.
