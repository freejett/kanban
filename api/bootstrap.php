<?php
declare(strict_types=1);

session_set_cookie_params([
  'httponly' => true,
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'samesite' => 'Lax',
]);
session_start();
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === 'http://localhost:5173') {
  header('Access-Control-Allow-Origin: http://localhost:5173');
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Expose-Headers: X-CSRF-Token');
  header('Vary: Origin');
}
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-CSRF-Token: ' . $_SESSION['csrf_token']);

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  $pdo = new PDO('sqlite:' . __DIR__ . '/../data/app.db');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->exec('PRAGMA journal_mode=WAL;');
  $pdo->exec('PRAGMA busy_timeout=5000;');
  return $pdo;
}

function json_ok(mixed $data): void {
  echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
  exit;
}

function json_error(string $message, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['error' => ['code' => $code, 'message' => $message]], JSON_UNESCAPED_UNICODE);
  exit;
}

function body_json(): array {
  $raw = file_get_contents('php://input') ?: '{}';
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_auth(): array {
  if (!isset($_SESSION['user_id'])) json_error('Unauthorized', 401);
  return ['id' => (int) $_SESSION['user_id'], 'role' => (string) ($_SESSION['role'] ?? 'user')];
}

function require_admin(): array {
  $auth = require_auth();
  if (($auth['role'] ?? 'user') !== 'admin') json_error('Forbidden', 403);
  return $auth;
}

function csrf_token(): string {
  return (string)($_SESSION['csrf_token'] ?? '');
}

function verify_csrf(): void {
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  if (in_array($method, ['POST', 'PATCH', 'DELETE'], true)) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
      json_error('Invalid CSRF token', 419);
    }
  }
}

