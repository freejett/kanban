# Инициализация и запуск проекта

## 1) Требования

- PHP `8.2+` с расширениями: `pdo_sqlite`, `json`, `session`, `mbstring`
- Node.js `18+` и `npm`
- Доступ на запись в `data/app.db`

Проверка:

```bash
php -v
php -m | rg "pdo_sqlite|json|session|mbstring"
node -v
npm -v
```

---

## 2) Первичная инициализация

Из корня проекта:

```bash
cd /Volumes/work/__projects/kanban
```

Создать БД-файл (если отсутствует):

```bash
mkdir -p data
touch data/app.db
```

Установить frontend-зависимости:

```bash
cd frontend
npm install
cd ..
```

---

## 3) Запуск в локальной разработке

Из корня проекта:

```bash
cd frontend
npm run build
cd ..
php -S localhost:8001 -t public_html
```

Приложение будет доступно на `http://localhost:8001`.
API доступен по `http://localhost:8001/api/*`, но сами приватные скрипты лежат вне public root (`api/`).

---

## 4) Инициализация схемы SQLite

Один раз после старта PHP-сервера:

```bash
curl -s http://localhost:8001/api/init.php
```

Ожидаемый ответ:

```json
{"success":true,"data":{"ok":true}}
```

---

## 5) Быстрый smoke-check

1. Открыть `http://localhost:8001`
2. Зарегистрировать пользователя
3. Создать задачу
4. Перетащить задачу между колонками
5. Проверить фильтры (status / assignee / search)

Для проверки админ-логов:
- выставить пользователю роль `admin` в SQLite,
- зайти в `/admin/logs`.

Пример смены роли:

```bash
sqlite3 data/app.db "update users set role='admin' where email='admin@example.com';"
```

---

## 6) Production build frontend

```bash
cd frontend
npm run build
```

Сборка будет в `public_html/`.

---

## 7) Частые проблемы

- **Ошибка `Invalid CSRF token`**
  - Обновите страницу и повторите запрос.
  - Убедитесь, что frontend обращается к backend через текущую сессию (cookie + proxy).

- **Ошибка SQLite lock**
  - Убедитесь, что используется один файл БД `data/app.db`.
  - Проверьте права на запись в файл.

- **`403 Forbidden` на `/api/logs`**
  - Доступ только для пользователя с ролью `admin`.

- **Не стартует frontend**
  - Проверьте `node -v` (нужен 18+) и повторите `npm install`.
