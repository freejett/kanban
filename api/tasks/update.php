<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
$auth = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') json_error('Method not allowed', 405);
verify_csrf();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_error('Invalid id', 422);
$in = body_json();

$pdo = db();
$oldStmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
$oldStmt->execute([$id]);
$old = $oldStmt->fetch(PDO::FETCH_ASSOC);
if (!$old) json_error('Task not found', 404);

$allowed = ['status', 'assigned_to', 'deadline'];
$patch = [];
foreach ($allowed as $key) {
  if (array_key_exists($key, $in)) $patch[$key] = $in[$key];
}
if (!$patch) json_error('Nothing to update', 422);

$pdo->beginTransaction();
try {
  $sets = [];
  $params = [];
  foreach ($patch as $k => $v) {
    $sets[] = "$k = ?";
    $params[] = $v;
  }
  $sets[] = 'updated_at = datetime(\'now\')';
  $params[] = $id;
  $sql = 'UPDATE tasks SET ' . implode(',', $sets) . ' WHERE id = ?';
  $upd = $pdo->prepare($sql);
  $upd->execute($params);

  foreach (['status', 'assigned_to', 'deadline'] as $f) {
    if (array_key_exists($f, $patch) && (string)$old[$f] !== (string)$patch[$f]) {
      $log = $pdo->prepare('INSERT INTO task_logs (task_id, changed_by, action, old_value, new_value) VALUES (?, ?, ?, ?, ?)');
      $log->execute([$id, $auth['id'], $f . '_changed', (string)$old[$f], (string)$patch[$f]]);
    }
  }
  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  json_error('Cannot update task', 500);
}

$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$id]);
json_ok($stmt->fetch(PDO::FETCH_ASSOC));
