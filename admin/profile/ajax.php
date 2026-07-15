<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

try {
    $profile = $pdo->query('SELECT * FROM profiles ORDER BY profile_id LIMIT 1')->fetch() ?: [];
    echo json_encode(['success' => true, 'profile' => $profile]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false]);
}
