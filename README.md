# Kanban Todo MVP

## Локальный запуск из `public_html`

1. Установить зависимости и собрать фронтенд:
   - `cd frontend`
   - `npm install`
   - `npm run build`
2. Вернуться в корень и запустить PHP с document root:
   - `cd ..`
   - `php -S localhost:8001 -t public_html`
3. Открыть `http://localhost:8001`

## API и БД вне web-root
- Приватные скрипты: `api/` (вне `public_html`)
- База: `data/app.db` (вне `public_html`)
- Публичные точки входа API: `public_html/api/*` (тонкие прокси-обертки)

## Stack
- Vue 3 + TypeScript + Pinia + Vue Router
- PHP 8.2 + SQLite (PDO)
