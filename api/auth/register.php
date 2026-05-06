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

$stmt = db()->prepare('INSERT INTO users (email, password_hash, full_name, is_active) VALUES (?, ?, ?, 0)');
try {
  $stmt->execute([$email, password_hash($password, PASSWORD_ARGON2ID), $fullName]);
} catch (Throwable $e) {
  json_error('Email already exists', 409);
}

json_ok(['pending_approval' => true, 'message' => 'Регистрация отправлена. Ожидайте активации администратором.']);
