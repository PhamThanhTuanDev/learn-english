---
name: tiep-tuc
description: Tiếp tục dự án learn-english từ phiên trước. Dùng khi người dùng nói "tiếp tục", "làm tiếp", "task tiếp theo", hoặc mở phiên mới cho dự án này.
---

# Tiếp tục dự án

1. Đọc mục cuối của `docs/PROGRESS.md` và các task chưa tick của phase hiện tại trong `docs/ROADMAP.md` (không đọc cả file nếu không cần).
2. Chạy `git status --short` và `git log --oneline -5`. Có commit `wip(...)` hoặc file chưa commit thì hoàn thành việc đó trước.
3. Báo người dùng task sẽ làm (mã + tên). Task có ghi *(cần chốt)* thì hỏi người dùng trước khi làm.
4. Bật môi trường nếu cần: `docker compose up -d`.
5. Làm task. Khi đang làm chỉ chạy test liên quan; trước khi commit chạy `docker compose exec -T app composer check`.
6. Commit theo dạng `feat(Px-yy): ...` (hoặc `fix`, `chore`, `docs`, `test`).
7. Tick task trong ROADMAP, thêm mục mới vào PROGRESS (đã làm / tiếp theo / lưu ý), commit `docs: update progress`.
8. Còn nhiều ngữ cảnh thì có thể làm thêm 1 task; nếu không, dừng ở đây để phiên sau bắt đầu sạch.
