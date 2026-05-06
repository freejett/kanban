<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  require_auth();
  $q = trim((string)($_GET['search'] ?? ''));
  $stmt = db()->prepare('SELECT id,email,full_name,role,color_hex,is_active FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY full_name LIMIT 100');
  $like = '%' . $q . '%';
  $stmt->execute([$like, $like]);
  json_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
}

$auth = require_admin();
verify_csrf();
$pdo = db();

if ($method === 'POST') {
  $in = body_json();
  $email = filter_var((string)($in['email'] ?? ''), FILTER_VALIDATE_EMAIL);
  $password = (string)($in['password'] ?? '');
  $fullName = trim((string)($in['full_name'] ?? ''));
  $role = (string)($in['role'] ?? 'user');
  $colorHex = strtoupper(trim((string)($in['color_hex'] ?? '#3B82F6')));
  if (!$email || strlen($password) < 6 || $fullName === '') json_error('Invalid input', 422);
  if (!in_array($role, ['user', 'admin'], true)) json_error('Invalid role', 422);
  if (!preg_match('/^#[0-9A-F]{6}$/', $colorHex)) json_error('Invalid color', 422);

  $isActive = isset($in['is_active']) ? ((int)$in['is_active'] === 1 ? 1 : 0) : 0;
  $stmt = $pdo->prepare('INSERT INTO users (email,password_hash,full_name,role,color_hex,is_active) VALUES (?,?,?,?,?,?)');
  try {
    $stmt->execute([$email, password_hash($password, PASSWORD_ARGON2ID), $fullName, $role, $colorHex, $isActive]);
  } catch (Throwable $e) {
    json_error('Email already exists', 409);
  }
  $id = (int)$pdo->lastInsertId();
  $out = $pdo->prepare('SELECT id,email,full_name,role,color_hex,is_active FROM users WHERE id = ?');
  $out->execute([$id]);
  json_ok($out->fetch(PDO::FETCH_ASSOC));
}

if ($method === 'PATCH') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) json_error('Invalid id', 422);
  $in = body_json();
  $allowed = ['full_name', 'role', 'color_hex', 'password', 'is_active'];
  $patch = [];
  foreach ($allowed as $field) {
    if (array_key_exists($field, $in)) $patch[$field] = $in[$field];
  }
  if (!$patch) json_error('Nothing to update', 422);
  if (isset($patch['role']) && !in_array((string)$patch['role'], ['user', 'admin'], true)) json_error('Invalid role', 422);
  if (isset($patch['color_hex']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', (string)$patch['color_hex'])) json_error('Invalid color', 422);
  if (isset($patch['password']) && strlen((string)$patch['password']) < 6) json_error('Password too short', 422);
  if (isset($patch['is_active']) && !in_array((int)$patch['is_active'], [0, 1], true)) json_error('Invalid is_active', 422);

  $sets = [];
  $params = [];
  if (isset($patch['full_name'])) {
    $sets[] = 'full_name = ?';
    $params[] = trim((string)$patch['full_name']);
  }
  if (isset($patch['role'])) {
    $sets[] = 'role = ?';
    $params[] = (string)$patch['role'];
  }
  if (isset($patch['color_hex'])) {
    $sets[] = 'color_hex = ?';
    $params[] = strtoupper((string)$patch['color_hex']);
  }
  if (isset($patch['password'])) {
    $sets[] = 'password_hash = ?';
    $params[] = password_hash((string)$patch['password'], PASSWORD_ARGON2ID);
  }
  if (isset($patch['is_active'])) {
    $sets[] = 'is_active = ?';
    $params[] = (int)$patch['is_active'] === 1 ? 1 : 0;
  }
  $sets[] = 'updated_at = datetime(\'now\')';
  $params[] = $id;
  $stmt = $pdo->prepare('UPDATE users SET ' . implode(',', $sets) . ' WHERE id = ?');
  $stmt->execute($params);

  $out = $pdo->prepare('SELECT id,email,full_name,role,color_hex,is_active FROM users WHERE id = ?');
  $out->execute([$id]);
  json_ok($out->fetch(PDO::FETCH_ASSOC));
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) json_error('Invalid id', 422);
  if ($id === (int)$auth['id']) json_error('You cannot delete yourself', 422);
  $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
  $stmt->execute([$id]);
  json_ok(['ok' => true]);
}

json_error('Method not allowed', 405);
