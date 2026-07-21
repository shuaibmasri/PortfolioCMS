<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once __DIR__ . '/validation.php';
requireAdmin();
header('Content-Type: application/json');

function settingsAjaxResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    settingsAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
}

try {
    settingsAjaxResponse([
        'success' => true,
        'message' => 'Settings loaded successfully.',
        'data' => ['settings' => websiteSettingsLoad($pdo)],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    settingsAjaxResponse([
        'success' => false,
        'message' => 'Unable to load website settings.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 500);
}
