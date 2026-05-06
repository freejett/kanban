<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
$auth = require_auth();

$stmt = db()->prepare('SELECT id,email,full_name,role,color_hex,is_active FROM users WHERE id = ?');
$stmt->execute([$auth['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) json_error('User not found', 404);
if ((int)($user['is_active'] ?? 0) !== 1) {
  session_destroy();
  json_error('Аккаунт не активирован администратором', 403);
}
$user['csrf_token'] = csrf_token();
json_ok($user);
