# 📋 Техническое задание: Kanban Todo List Application

> **Версия:** 1.0  
> **Дата:** 07.05.2026  
> **Статус:** Утверждено для разработки MVP  

---

## 1. Обзор проекта
Веб-приложение для управления командными задачами в формате канбан-доски. Доступно через мобильный и десктопный браузеры. Данные синхронизируются между устройствами через централизованное хранилище (SQLite). Обновление состояния происходит при перезагрузке страницы или ручном обновлении списка. Внешние сервисы, очереди и WebSockets не используются.

---

## 2. Цели и ограничения
| Цель                           | Описание                                                     |
| ------------------------------ | ------------------------------------------------------------ |
| 🎯 **MVP за 3–4 спринта**       | Полный цикл: регистрация → доска → фильтры → логи → деплой   |
| 📱 **Mobile-first**             | Touch-оптимизированный drag & drop, адаптивная верстка, fallback-меню перемещения |
| 🔒 **Без внешних зависимостей** | Только LAMP-хостинг, vanilla PHP, SQLite, клиентский JS      |
| ⚡ **Простота поддержки**       | Минимум абстракций, понятная структура файлов, прямые PDO-запросы |

---

## 3. Роли и права доступа
| Роль    | Права                                                        |
| ------- | ------------------------------------------------------------ |
| `user`  | Регистрация, вход, создание/редактирование/удаление задач, перенос между колонками, назначение/смена ответственного, фильтрация, просмотр задач |
| `admin` | Все права `user` + доступ к странице `/admin/logs` (полный лог изменений задач), управление статусом пользователей (опционально) |

---

## 4. Технологический стек
| Слой                | Технология                                   | Примечание                                             |
| ------------------- | -------------------------------------------- | ------------------------------------------------------ |
| **Frontend**        | Vue 3.5 (`<script setup>`), Vite, TypeScript | Composition API, строгая типизация                     |
| **State & Routing** | Pinia, Vue Router 4                          | Клиентский кэш, реактивные фильтры, навигация          |
| **UI**              | `shadcn-vue` + Tailwind CSS                  | Переиспользуемые компоненты, CSS-переменные для цветов |
| **Drag & Drop**     | `vue-draggable-plus`                         | Поддержка touch, стабильна на iOS/Android              |
| **Backend**         | PHP 8.2+ (vanilla), PDO                      | Без фреймворков, прямая работа с SQLite                |
| **БД**              | SQLite 3 (`pdo_sqlite`)                      | Файл `data/app.db`, WAL-режим для конкурентных записей |
| **Сервер**          | Apache/Nginx + LAMP                          | `.htaccess` для роутинга, `session` для аутентификации |

---

## 5. Архитектура базы данных (SQLite)

### 5.1. Схема таблиц
```sql
PRAGMA journal_mode=WAL;
PRAGMA busy_timeout=5000;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    full_name TEXT NOT NULL,
    role TEXT DEFAULT 'user' CHECK(role IN ('user', 'admin')),
    color_hex TEXT DEFAULT '#3B82F6',
    created_at DATETIME DEFAULT (datetime('now')),
    updated_at DATETIME DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT DEFAULT '',
    status TEXT DEFAULT 'todo' CHECK(status IN ('todo', 'in_progress', 'done')),
    assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_by INTEGER REFERENCES users(id),
    deadline DATETIME,
    created_at DATETIME DEFAULT (datetime('now')),
    updated_at DATETIME DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS task_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER REFERENCES tasks(id) ON DELETE CASCADE,
    changed_by INTEGER REFERENCES users(id),
    action TEXT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    created_at DATETIME DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned ON tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_logs_task ON task_logs(task_id);
```

### 5.2. Конкурентность
- `PRAGMA journal_mode=WAL` снижает блокировки при параллельных записях
- `busy_timeout=5000` даёт PDO время на повторную попытку при `database is locked`
- Все мутации оборачиваются в транзакции `BEGIN IMMEDIATE; ... COMMIT;`

---

## 6. API Specification (JSON REST-like)

> Все запросы идут на `/api/*.php`. Формат ответа: `{ success: true, data: ... }` или `{ error: { code: 400, message: "..." } }`

| Метод    | Endpoint             | Описание             | Тело запроса                           | Доступ         |
| -------- | -------------------- | -------------------- | -------------------------------------- | -------------- |
| `POST`   | `/api/auth/register` | Регистрация          | `{ email, password, full_name }`       | Публичный      |
| `POST`   | `/api/auth/login`    | Вход                 | `{ email, password }`                  | Публичный      |
| `GET`    | `/api/auth/me`       | Текущий пользователь | —                                      | Авторизованный |
| `POST`   | `/api/auth/logout`   | Выход                | —                                      | Авторизованный |
| `GET`    | `/api/users`         | Список пользователей | `?search=...`                          | Авторизованный |
| `GET`    | `/api/tasks`         | Все задачи           | `?status=&assignee=&q=`                | Авторизованный |
| `POST`   | `/api/tasks`         | Создание задачи      | `{ title, description, deadline }`     | Авторизованный |
| `PATCH`  | `/api/tasks/:id`     | Обновление задачи    | `{ status?, assigned_to?, deadline? }` | Авторизованный |
| `DELETE` | `/api/tasks/:id`     | Удаление задачи      | —                                      | Авторизованный |
| `GET`    | `/api/logs`          | Лог изменений        | `?task_id=&page=&limit=`               | Только `admin` |

### 6.1. Логирование изменений (автоматическое на бэке)
При `PATCH /api/tasks/:id` PHP сравнивает старое и новое значение. При изменении `status`, `assigned_to` или `deadline` создаётся запись в `task_logs` с полями `action`, `old_value`, `new_value`.

---

## 7. Фронтенд-архитектура

### 7.1. Структура проекта
```
src/
├── api/             # Фетчи к /api/*.php
├── assets/          # Стили, иконки
├── components/      # UI: KanbanBoard, TaskCard, FilterBar, TaskModal, LogTable
├── composables/     # useAuth, useDragSync, useFilters
├── layouts/         # AuthLayout, MainLayout, AdminLayout
├── router/          # vue-router маршруты
├── stores/          # pinia: auth, tasks, ui
├── types/           # TypeScript интерфейсы
├── views/           # Login, Register, Board, AdminLogs, Profile
└── App.vue
```

### 7.2. Pinia Stores
| Store   | Назначение                                                   |
| ------- | ------------------------------------------------------------ |
| `auth`  | Сессия, текущий пользователь, проверка `role === 'admin'`    |
| `tasks` | Кэш задач, `fetchTasks()`, `syncTask(id, patch)`, фильтрация через `computed` |
| `ui`    | Состояние модалок, фильтров, тема, состояние drag            |

### 7.3. Фильтрация (клиентская)
```ts
// stores/tasks.ts
export const filteredTasks = computed(() => {
  return tasks.value.filter(t => {
    const matchStatus = !statusFilter.value || t.status === statusFilter.value;
    const matchAssignee = !assigneeFilter.value || t.assigned_to === assigneeFilter.value;
    const matchSearch = !searchQuery.value || t.title.toLowerCase().includes(searchQuery.value.toLowerCase());
    return matchStatus && matchAssignee && matchSearch;
  });
});
```

### 7.4. Drag & Drop Sync
```vue
<Draggable
  :list="columnTasks"
  group="tasks"
  @end="onDragEnd"
  item-key="id"
>
  <template #item="{ element }">
    <TaskCard :task="element" />
  </template>
</Draggable>

<script setup>
function onDragEnd(event) {
  const { from, to, oldIndex, newIndex } = event;
  if (from !== to) {
    const taskId = tasks.value[oldIndex]?.id;
    const newStatus = to.dataset.status; // 'todo' | 'in_progress' | 'done'
    tasksStore.syncTask(taskId, { status: newStatus });
  }
}
</script>
```

---

## 8. UI/UX и дизайн-система

### 8.1. Компоненты (shadcn-vue + кастомизация)
| Компонент               | Источник                      | Кастомизация                                         |
| ----------------------- | ----------------------------- | ---------------------------------------------------- |
| Кнопки, инпуты, модалки | `shadcn-vue`                  | Tailwind-классы, `data-*` атрибуты                   |
| Колонки канбана         | Custom `flex-col md:flex-row` | Горизонтальный скролл на десктопе, стек на мобильном |
| Карточка задачи         | Custom                        | `border-l-4` с `var(--user-color)`, бейдж дедлайна   |
| Селект ответственного   | `shadcn-vue Select`           | Автокомплит по имени/email                           |
| Таблица логов           | `shadcn-vue Table`            | Пагинация, сортировка по дате                        |

### 8.2. Цветовая дифференциация
- В `users.color_hex` хранится HEX (например, `#10B981`)
- При рендере задачи: `:style="{ '--task-accent': task.assigned_user?.color_hex || '#94A3B8' }"`
- CSS: `.task-card { border-left: 4px solid var(--task-accent); }`

### 8.3. Дедлайны
- `< 24h` → красный бейдж `bg-red-100 text-red-700`
- `< 72h` → жёлтый `bg-yellow-100 text-yellow-700`
- `> 72h` или `null` → серый `bg-gray-100 text-gray-600`

### 8.4. Мобильный fallback
Если drag неудобен на маленьком экране, в карточке появляется кнопка `⋮ → Переместить`, открывающая `BottomSheet` с выбором колонки.

---

## 9. Безопасность

| Вектор             | Мера защиты                                                  |
| ------------------ | ------------------------------------------------------------ |
| **Пароли**         | `password_hash($pwd, PASSWORD_ARGON2ID)`                     |
| **Сессии**         | `session.cookie_httponly = 1`, `secure = 1`, `same_site = 'Lax'` |
| **SQL**            | Только PDO prepared statements, строгая валидация типов      |
| **CSRF**           | Токен в `session` → скрытый input в формах / заголовок `X-CSRF-Token` |
| **Авторизация**    | Проверка `$_SESSION['user_id']` на каждом запросе            |
| **Админ-доступ**   | `if ($_SESSION['role'] !== 'admin') { http_response_code(403); exit; }` |
| **Входные данные** | `filter_var($email, FILTER_VALIDATE_EMAIL)`, `htmlspecialchars()` для вывода |
| **Файлы**          | `data/` и `api/` защищены `.htaccess: Deny from all` (кроме index.php) |

---

## 10. Деплой и инфраструктура

### 10.1. Требования хостинга
- PHP 8.2+ с расширениями: `pdo_sqlite`, `json`, `session`, `mbstring`
- Apache или Nginx + PHP-FPM
- Доступ к `.htaccess` или конфиг сервера
- Возможность задать `DocumentRoot` и исключить `data/` из публичного доступа

### 10.2. Структура на сервере
```
/public_html/
  ├── index.html          # Собранный Vue (dist/)
  ├── assets/             # CSS/JS бандлы
  └── .htaccess           # SPA fallback + API proxy
/api/                     # PHP эндпоинты
  ├── auth.php
  ├── tasks.php
  ├── logs.php
  └── helpers.php
/data/                    # Защищённая папка
  └── app.db              # SQLite файл
```

### 10.3. `.htaccess` (минимальный)
```apache
# SPA fallback
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.html [L]

# Защита папок
<Directory "/var/www/html/data">
    Require all denied
</Directory>
```

### 10.4. Права и инициализация
```bash
touch data/app.db
chmod 664 data/app.db
chown www-www-data data/app.db
# PHP создаст таблицы при первом запросе (миграция в api/init.php)
```

---

## 11. Этапы разработки (Roadmap)

| Спринт              | Задачи                                                       | Результат                            |
| ------------------- | ------------------------------------------------------------ | ------------------------------------ |
| **1. Фундамент**    | Vue + Vite + shadcn, PHP PDO init, SQLite схема, auth (register/login/session), маршрутизация | Рабочая регистрация и сессия         |
| **2. Ядро канбана** | `GET/POST/PATCH tasks`, колонки, `vue-draggable-plus`, синхронизация статуса, Pinia store | Перетаскивание задач между колонками |
| **3. Фильтры и UX** | Поиск по названию, фильтр по статусу/ответственному, дедлайны, цветовая маркировка, мобильный fallback | Полная интерактивная доска           |
| **4. Админ и логи** | Таблица `task_logs`, автологирование в PHP, страница `/admin/logs`, защита ролей | Контроль изменений                   |
| **5. Полировка**    | Валидация, обработка ошибок, pull-to-refresh, деплой, документация | Готовый к продакшену MVP             |

---

## 12. Критерии приёмки (Acceptance Criteria)
- [ ] Регистрация и вход работают без перезагрузки страницы
- [ ] Задачи сохраняются в SQLite и отображаются на любом устройстве после логина
- [ ] Drag & drop меняет `status` в БД, карточки окрашены по цвету ответственного
- [ ] Фильтры работают реактивно на клиенте, не отправляют лишних запросов
- [ ] Смена ответственного/дедлайна/статуса фиксируется в `task_logs`
- [ ] Страница `/admin/logs` доступна только пользователям с `role='admin'`
- [ ] Приложение корректно отображается на iPhone/Android (touch, viewport, scroll)
- [ ] Деплой на LAMP занимает ≤ 15 минут по инструкции из п.10

---

📥 **Готово к использованию.** Сохраните как `KANBAN_TODO_SPEC.md`.  
При необходимости могу сгенерировать:
- Полный код `api/tasks.php` с транзакциями и логированием
- `stores/tasks.ts` с `fetch`, `sync`, фильтрами и типами
- `components/KanbanBoard.vue` с `vue-draggable-plus` и shadcn-стилями
- `vite.config.ts` + `tsconfig.json` под Vue 3.5

Укажите, с какого файла начать генерацию кода.