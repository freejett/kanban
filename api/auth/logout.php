<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
verify_csrf();
session_destroy();
json_ok(['ok' => true]);
