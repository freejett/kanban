<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function ensure_schema(): void {
  $sql = <<<SQL
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
  status TEXT DEFAULT 'todo' CHECK(status IN ('todo', 'in_progress', 'done', 'archived')),
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
SQL;

  db()->exec($sql);
  ensure_users_is_active_column();
  ensure_tasks_support_archived_status();
}

function ensure_users_is_active_column(): void {
  $columns = db()->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
  $hasColumn = false;
  foreach ($columns as $column) {
    if (($column['name'] ?? '') === 'is_active') {
      $hasColumn = true;
      break;
    }
  }
  if (!$hasColumn) {
    db()->exec("ALTER TABLE users ADD COLUMN is_active INTEGER NOT NULL DEFAULT 1");
  }
}

function ensure_tasks_support_archived_status(): void {
  $sqlRow = db()->query("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'tasks'")->fetch(PDO::FETCH_ASSOC);
  $createSql = strtolower((string)($sqlRow['sql'] ?? ''));
  if (str_contains($createSql, "'archived'")) {
    return;
  }

  $pdo = db();
  $pdo->beginTransaction();
  try {
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->exec('ALTER TABLE tasks RENAME TO tasks_old');
    $pdo->exec(
      "CREATE TABLE tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT DEFAULT '',
        status TEXT DEFAULT 'todo' CHECK(status IN ('todo', 'in_progress', 'done', 'archived')),
        assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_by INTEGER REFERENCES users(id),
        deadline DATETIME,
        created_at DATETIME DEFAULT (datetime('now')),
        updated_at DATETIME DEFAULT (datetime('now'))
      )"
    );
    $pdo->exec('INSERT INTO tasks (id, title, description, status, assigned_to, created_by, deadline, created_at, updated_at)
      SELECT id, title, description, status, assigned_to, created_by, deadline, created_at, updated_at FROM tasks_old');
    $pdo->exec('DROP TABLE tasks_old');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tasks_assigned ON tasks(assigned_to)');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->commit();
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    $pdo->exec('PRAGMA foreign_keys = ON');
    throw $e;
  }
}

if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'init.php') {
  ensure_schema();
  json_ok(['ok' => true]);
}
