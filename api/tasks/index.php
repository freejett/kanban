<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
$auth = require_auth();

function normalize_deadline_date($raw): ?string {
  if ($raw === null || $raw === '') return null;
  $value = trim((string)$raw);
  $datePart = substr($value, 0, 10);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart)) return null;
  [$year, $month, $day] = array_map('intval', explode('-', $datePart));
  if (!checkdate($month, $day, $year)) return null;
  return $datePart . ' ' . date('H:i:s');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $sql = 'SELECT t.*, u.id as au_id, u.email as au_email, u.full_name as au_full_name, u.role as au_role, u.color_hex as au_color_hex
          FROM tasks t LEFT JOIN users u ON u.id = t.assigned_to ORDER BY t.updated_at DESC';
  $rows = db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $data = array_map(function ($r) {
    return [
      'id' => (int)$r['id'],
      'title' => $r['title'],
      'description' => $r['description'],
      'status' => $r['status'],
      'assigned_to' => $r['assigned_to'] !== null ? (int)$r['assigned_to'] : null,
      'created_by' => (int)$r['created_by'],
      'deadline' => $r['deadline'],
      'created_at' => $r['created_at'],
      'updated_at' => $r['updated_at'],
      'assigned_user' => $r['au_id'] ? [
        'id' => (int)$r['au_id'],
        'email' => $r['au_email'],
        'full_name' => $r['au_full_name'],
        'role' => $r['au_role'],
        'color_hex' => $r['au_color_hex'],
      ] : null,
    ];
  }, $rows);
  json_ok($data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $in = body_json();
  $title = trim((string)($in['title'] ?? ''));
  if ($title === '') json_error('Title is required', 422);
  $description = trim((string)($in['description'] ?? ''));
  $deadline = normalize_deadline_date($in['deadline'] ?? null);
  if (($in['deadline'] ?? null) !== null && ($in['deadline'] ?? '') !== '' && $deadline === null) {
    json_error('Invalid deadline date', 422);
  }
  $status = (string)($in['status'] ?? 'todo');
  $allowedStatuses = ['todo', 'in_progress', 'done', 'archived'];
  if (!in_array($status, $allowedStatuses, true)) json_error('Invalid status', 422);
  $assignedTo = array_key_exists('assigned_to', $in) && $in['assigned_to'] !== null ? (int)$in['assigned_to'] : null;

  if ($assignedTo !== null) {
    $userCheck = db()->prepare('SELECT id FROM users WHERE id = ?');
    $userCheck->execute([$assignedTo]);
    if (!$userCheck->fetch(PDO::FETCH_ASSOC)) json_error('Assignee not found', 422);
  }

  $pdo = db();
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('INSERT INTO tasks (title, description, status, assigned_to, created_by, deadline) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $description, $status, $assignedTo, $auth['id'], $deadline]);
    $id = (int)$pdo->lastInsertId();
    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    json_error('Cannot create task', 500);
  }
  $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
  $stmt->execute([$id]);
  json_ok($stmt->fetch(PDO::FETCH_ASSOC));
}

json_error('Method not allowed', 405);
