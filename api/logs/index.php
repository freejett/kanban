<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
require_admin();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$total = (int)db()->query('SELECT COUNT(*) FROM task_logs')->fetchColumn();
$stmt = db()->prepare('
  SELECT l.*, u.full_name as user_name, t.title as task_title
  FROM task_logs l
  LEFT JOIN users u ON u.id = l.changed_by
  LEFT JOIN tasks t ON t.id = l.task_id
  ORDER BY l.created_at DESC
  LIMIT ? OFFSET ?
');
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
json_ok(['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
