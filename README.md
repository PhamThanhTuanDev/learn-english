# Learn English

Web học tiếng Anh cá nhân, bám theo lộ trình 24 tháng từ số 0 đến IELTS 7.0+:
lịch học 104 tuần, flashcard lặp lại ngắt quãng, sổ lỗi, điểm thi thử và biểu đồ tiến bộ.

Production: https://english.minlish.site

## Công nghệ
Laravel 13 · PHP 8.4 · MariaDB 10.5 · Blade + Alpine.js + Tailwind (Breeze) · Vite · PHPUnit · Pint · Larastan · GitHub Actions

## Chạy trên máy
Cần Docker Desktop và Node.js 24. Không cần cài PHP.

```bash
cp .env.example .env
docker compose up -d
docker compose exec -T app composer install
docker compose exec -T app php artisan key:generate
docker compose exec -T app php artisan migrate
npm install
npm run dev
```

Mở http://localhost:8000.

## Kiểm tra chất lượng
```bash
docker compose exec -T app composer check
```
Chạy Pint (định dạng code), Larastan (phân tích tĩnh) và toàn bộ test. CI trên GitHub chạy đúng các bước này.

## Tài liệu
- [docs/ROADMAP.md](docs/ROADMAP.md) – lộ trình dựng web theo phase/task
- [docs/PROGRESS.md](docs/PROGRESS.md) – nhật ký tiến độ từng phiên
- [docs/DECISIONS.md](docs/DECISIONS.md) – các quyết định đã chốt
- [CLAUDE.md](CLAUDE.md) – hướng dẫn cho AI khi làm việc với repo
