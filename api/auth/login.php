<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
verify_csrf();

$in = body_json();
$email = filter_var((string)($in['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = (string)($in['password'] ?? '');
if (!$email || $password === '') json_error('Invalid input', 422);

$stmt = db()->prepare('SELECT id,email,password_hash,full_name,role,color_hex,is_active FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || !password_verify($password, (string)$user['password_hash'])) json_error('Invalid credentials', 401);
if ((int)($user['is_active'] ?? 0) !== 1) json_error('Аккаунт не активирован администратором', 403);

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = (string)$user['role'];
unset($user['password_hash']);
$user['csrf_token'] = csrf_token();
json_ok($user);
