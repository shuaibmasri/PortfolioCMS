<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once __DIR__ . '/validation.php';
requireAdmin();
header('Content-Type: application/json');

function settingsResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

$csrfToken = is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($csrfToken)) {
    settingsResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$validation = websiteSettingsValidate($_POST);
$data = $validation['data'];
$errors = $validation['errors'];

if ($errors !== []) {
    settingsResponse([
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'data' => new stdClass(),
        'errors' => $errors,
    ], 422);
}

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO website_settings (setting_key, setting_value, value_type, setting_group, description, is_public)
         VALUES (:setting_key, :setting_value, :value_type, :setting_group, :description, :is_public)
         ON DUPLICATE KEY UPDATE
             setting_value = VALUES(setting_value),
             value_type = VALUES(value_type),
             setting_group = VALUES(setting_group),
             description = VALUES(description),
             is_public = VALUES(is_public)'
    );

    foreach (websiteSettingsDefinitions() as $key => $definition) {
        $value = $data[$key] ?? '';
        $statement->execute([
            ':setting_key' => $key,
            ':setting_value' => (string) $value,
            ':value_type' => (string) ($definition['type'] ?? 'text'),
            ':setting_group' => (string) ($definition['group'] ?? 'general'),
            ':description' => (string) ($definition['label'] ?? $key),
            ':is_public' => 0,
        ]);
    }

    $pdo->commit();

    settingsResponse([
        'success' => true,
        'message' => 'Website settings saved successfully.',
        'data' => ['settings' => $data],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());
    settingsResponse([
        'success' => false,
        'message' => 'Unable to save website settings.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 500);
}
