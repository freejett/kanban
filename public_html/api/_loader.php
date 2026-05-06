<?php
declare(strict_types=1);

/**
 * Proxy public entrypoint to private /api scripts outside document root.
 */
function load_private_api(string $relativePath): void {
  $target = realpath(__DIR__ . '/../../api/' . ltrim($relativePath, '/'));
  $apiRoot = realpath(__DIR__ . '/../../api');
  if (!$target || !$apiRoot || strpos($target, $apiRoot) !== 0 || !is_file($target)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => 404, 'message' => 'Not found']], JSON_UNESCAPED_UNICODE);
    exit;
  }
  require $target;
}
