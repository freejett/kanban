<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../init.php';
ensure_schema();
require_auth();

$q = trim((string)($_GET['search'] ?? ''));
$stmt = db()->prepare('SELECT id,email,full_name,role,color_hex FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY full_name LIMIT 30');
$like = '%' . $q . '%';
$stmt->execute([$like, $like]);
json_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
