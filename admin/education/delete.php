<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function educationDeleteResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function educationProfileId(PDO $pdo): int
{
    $profile = $pdo->query('SELECT profile_id FROM profiles ORDER BY profile_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    return $profile ? (int) $profile['profile_id'] : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    educationDeleteResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$educationId = trim((string) ($_POST['education_id'] ?? ''));
if ($educationId === '' || !ctype_digit($educationId) || (int) $educationId < 1) {
    educationDeleteResponse(['success' => false, 'message' => 'Invalid education record.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

$profileId = educationProfileId($pdo);

try {
    $statement = $pdo->prepare('DELETE FROM educations WHERE education_id = :education_id AND profile_id = :profile_id');
    $statement->execute([':education_id' => (int) $educationId, ':profile_id' => $profileId]);

    if ($statement->rowCount() === 0) {
        educationDeleteResponse(['success' => false, 'message' => 'The selected education record could not be found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
    }

    educationDeleteResponse(['success' => true, 'message' => 'Education deleted successfully.', 'data' => ['education_id' => (int) $educationId], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    educationDeleteResponse(['success' => false, 'message' => 'Unable to delete education record.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
