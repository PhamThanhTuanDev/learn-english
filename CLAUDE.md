# Learn English – hướng dẫn cho Claude

Web học tiếng Anh cá nhân, bám theo lộ trình 24 tháng (file Word ở thư mục cha: `../Lo_trinh_tu_mat_goc_den_IELTS_7_plus_24_thang.docx`, nguồn dữ liệu ở `tools/roadmap/`).
Production: https://english.minlish.site (cPanel shared hosting: PHP 8.4.27, MariaDB 10.5, không có PHP trên máy dev).

## Stack
- Laravel 13, PHP 8.4 (composer `platform.php` = 8.4.27 để khớp hosting), MariaDB 10.5
- Breeze 2.4: Blade + Alpine.js + Tailwind, build bằng Vite
- Chất lượng: PHPUnit (SQLite `:memory:`), Pint, Larastan level 6, CI ở `.github/workflows/ci.yml`
- Dev: Docker compose (`app` = PHP 8.4, `db` = mariadb:10.5). Node chạy trên máy host.

## Lệnh
- Bật dev: `docker compose up -d` → http://localhost:8000 ; frontend: `npm run dev` (host)
- Artisan/Composer: `docker compose exec -T app php artisan ...` / `docker compose exec -T app composer ...`
- Trước mỗi commit: `docker compose exec -T app composer check` (Pint + Larastan + test)
- Sửa format: `docker compose exec -T app vendor/bin/pint`
- Test một phần: `docker compose exec -T app php artisan test --compact --filter=TenTest`

## Quy ước
- Code, tên biến, commit message: tiếng Anh. Giao diện và tài liệu: tiếng Việt.
- Conventional Commits kèm mã task: `feat(P3-02): add roadmap tables`.
- Mọi bảng dữ liệu của người học có `user_id` (sẵn sàng nhiều người dùng). Đăng ký công khai tắt bằng `APP_REGISTRATION_ENABLED=false`.
- Migration, seeder, lệnh import phải chạy lại nhiều lần không hỏng dữ liệu (idempotent).
- Sinh code mẫu bằng `php artisan make:*`, không viết tay boilerplate.
- Không đưa nội dung có bản quyền (đề Cambridge IELTS…) vào repo/DB; chỉ lưu kết quả người học nhập.
- Không commit `.env`, mật khẩu, thông tin FTP. Secrets deploy do người dùng tự nhập vào GitHub.
- Quyết định quan trọng (kiến trúc, dữ liệu, chi phí, deploy): hỏi người dùng trước, ghi vào `docs/DECISIONS.md`.

## Quy tắc tiết kiệm tokens (bắt buộc)
1. Đầu phiên: đọc mục cuối `docs/PROGRESS.md` → task chưa tick đầu tiên trong `docs/ROADMAP.md`. Mỗi phiên làm 1 task (tối đa 2).
2. Commit sau mỗi bước chạy được — git là điểm lưu. Phải dừng giữa chừng thì commit `wip(Px-yy): ...`.
3. Cuối phiên, hoặc khi hội thoại đã dài: cập nhật `docs/PROGRESS.md` (đã làm / tiếp theo / lưu ý), tick ROADMAP, commit.
4. Không in file lớn: CSV từ vựng (7.463 dòng), `composer.lock`, `package-lock.json`, `public/build`. Dùng `head`, `wc -l` hoặc script.
5. Lệnh ồn thêm `-q`, `--no-progress`, `--no-ansi` hoặc `| tail -n 5`. Lệnh trên 2 phút chạy nền.
6. Docker trên ổ D: chậm (bộ test ~35 giây): khi đang làm chỉ chạy test liên quan; `composer check` đầy đủ trước commit.
7. Bị ngắt giữa chừng: `git status --short`, `git diff --stat` và mục cuối PROGRESS cho biết đang ở đâu.

## Tài liệu
- `docs/ROADMAP.md` – các phase và task
- `docs/PROGRESS.md` – nhật ký từng phiên (bàn giao)
- `docs/DECISIONS.md` – các quyết định đã chốt và lý do
