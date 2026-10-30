# Lộ trình dựng web

Mỗi task vừa sức một phiên làm việc. Mã task dùng trong commit: `feat(P3-02): ...`.
Thứ tự có chủ đích: deploy sớm (Phase 2) để mỗi tính năng làm xong là lên production ngay.

## Phase 0 — Nền tảng
- [x] P0-01 Khung Laravel 13, khóa thư viện theo PHP 8.4.27
- [x] P0-02 Docker dev: PHP 8.4 + MariaDB 10.5 (khớp hosting)
- [x] P0-03 Breeze (Blade + Alpine + Tailwind), tắt đăng ký công khai, test
- [x] P0-04 Pint, Larastan level 6, `composer check`
- [x] P0-05 CI GitHub Actions (lint + phân tích tĩnh + test)
- [x] P0-06 CLAUDE.md, ROADMAP, PROGRESS, DECISIONS, skill `tiep-tuc`
- [x] P0-07 Push lần đầu lên GitHub và CI xanh

**Xong khi:** CI trên GitHub chạy xanh với nhánh `main`.

## Phase 1 — Tài khoản và giao diện chung
- [x] P1-01 Lệnh `php artisan app:create-owner` + seeder tạo tài khoản chủ từ biến môi trường; test
- [x] P1-02 Giao diện tiếng Việt (`lang/vi`), giờ Việt Nam khi hiển thị; menu: Hôm nay · Lộ trình · Flashcard · Sổ lỗi · Điểm số
- [x] P1-03 Trang Cài đặt học: ngày bắt đầu lộ trình, mục tiêu phút/ngày, múi giờ

**Xong khi:** đăng nhập được bằng tài khoản chủ, giao diện tiếng Việt, có menu khung.

## Phase 2 — Deploy sớm lên english.minlish.site
- [x] P2-01 *(bạn làm trên cPanel, mình hướng dẫn)* tạo DB user riêng cho `learn-english`, đổi collation DB sang `utf8mb4_unicode_ci`, tạo tài khoản FTP giới hạn thư mục, kiểm tra PHP extensions và xem có Terminal/SSH không
- [x] P2-02 Dựng cấu trúc thư mục trên hosting theo D11: code ở `~/learn-english`, `public_html/english.minlish.site` là symlink tới `~/learn-english/public`; thử trang tĩnh chạy được qua symlink
- [x] P2-03 Workflow `deploy.yml`: build (`composer install --no-dev`, `npm run build`) → deploy qua SSH + rsync (FTPS bị hosting tắt, xem D12); secrets do bạn tự nhập
- [x] P2-04 `.env` production + cách chạy migration trên hosting *(cần chốt, tùy có Terminal hay không)*
- [x] P2-05 SSL (AutoSSL), bật Force HTTPS, kiểm tra đăng nhập trên production

**Xong khi:** push lên `main` → web tự cập nhật trên https://english.minlish.site.

## Phase 3 — Lộ trình 104 tuần
- [x] P3-01 Xuất dữ liệu lộ trình từ `tools/roadmap/data.js` thành `database/data/roadmap.json` (6 giai đoạn, 24 tháng, 104 tuần, 728 ngày)
- [x] P3-02 Bảng `stages`, `months`, `weeks`, `roadmap_days` + seeder đọc JSON (idempotent)
- [x] P3-03 Bảng tiến độ: `day_completions` (đã xong, số phút), `gate_results` (kết quả cổng kiểm tra)
- [x] P3-04 Trang **Hôm nay**: tuần/ngày hiện tại theo ngày bắt đầu, các khối cố định của giai đoạn, tick hoàn thành, nhập phút học
- [x] P3-05 Trang tuần/tháng: xem lại, đánh dấu bù, nhập kết quả cổng kiểm tra
- [x] P3-06 Dashboard: % hoàn thành, chuỗi ngày học liên tiếp, tổng giờ theo tháng

**Xong khi:** dùng web thay cho việc tick ô trong file Word.

## Phase 4 — Flashcard lặp lại ngắt quãng
- [x] P4-01 Bảng `words` + lệnh `app:import-vocabulary` đọc CSV (upsert theo từ + loại từ; chuẩn hóa level rỗng/`undefined`/`intermediate`); test
- [x] P4-02 Bảng `cards` + thuật toán lập lịch ôn (SM-2 trước, có unit test; cân nhắc FSRS sau) *(cần chốt thuật toán)*
- [x] P4-03 Chọn từ mới theo giai đoạn: 8/10/12/15/10 từ mỗi ngày, đúng mức CEFR
- [x] P4-04 Màn hình ôn (Alpine): lật thẻ, phát âm bằng giọng đọc của trình duyệt, 4 nút Again/Hard/Good/Easy, phím tắt
- [x] P4-05 Thống kê: thẻ đến hạn, đã học, tỉ lệ nhớ

**Xong khi:** ôn từ hằng ngày trên web thay cho Anki.

## Phase 5 — Sổ lỗi
- [x] P5-01 Thêm/sửa/xóa lỗi (câu sai, câu đúng, loại lỗi, tuần), tăng số lần lặp lại
- [ ] P5-02 Lọc theo loại lỗi; danh sách "3 lỗi ưu tiên" cho Ngày 6

## Phase 6 — Điểm thi và biểu đồ
- [ ] P6-01 Kết quả cổng kiểm tra (R0/R21, A2 Key, B1 Preliminary, B2 First) và thi thử IELTS theo 4 kỹ năng; Overall làm tròn theo quy tắc IELTS (có test)
- [ ] P6-02 Biểu đồ tiến bộ theo kỹ năng, so với mốc của từng tháng

**Xong MVP** khi Phase 3–6 chạy trên production.

## Phase 7 — Sau MVP *(chốt lại khi tới)*
- AI nhận xét bài Writing (Claude API — cần API key, có chi phí)
- PWA: cài lên điện thoại, ôn flashcard khi offline
- Mở đăng ký cho người khác, sao lưu tự động
