<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') json_error('Method not allowed', 405);
verify_csrf();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) json_error('Invalid id', 422);
$pdo = db();
$pdo->exec('BEGIN IMMEDIATE');
try {
  $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
  $stmt->execute([$id]);
  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  json_error('Cannot delete task', 500);
}
json_ok(['ok' => true]);
