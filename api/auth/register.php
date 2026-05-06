<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
verify_csrf();

$in = body_json();
$email = filter_var((string)($in['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = (string)($in['password'] ?? '');
$fullName = trim((string)($in['full_name'] ?? ''));
if (!$email || strlen($password) < 6 || $fullName === '') json_error('Invalid input', 422);

$stmt = db()->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (?, ?, ?)');
try {
  $stmt->execute([$email, password_hash($password, PASSWORD_ARGON2ID), $fullName]);
} catch (Throwable $e) {
  json_error('Email already exists', 409);
}

$id = (int)db()->lastInsertId();
$_SESSION['user_id'] = $id;
$_SESSION['role'] = 'user';

$me = db()->prepare('SELECT id,email,full_name,role,color_hex FROM users WHERE id = ?');
$me->execute([$id]);
$user = $me->fetch(PDO::FETCH_ASSOC);
$user['csrf_token'] = csrf_token();
json_ok($user);
