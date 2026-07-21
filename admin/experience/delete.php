<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function experienceDeleteResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function experienceProfileId(PDO $pdo): int
{
    $profile = $pdo->query('SELECT profile_id FROM profiles ORDER BY profile_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    return $profile ? (int) $profile['profile_id'] : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    experienceDeleteResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$experienceId = trim((string) ($_POST['experience_id'] ?? ''));
if ($experienceId === '' || !ctype_digit($experienceId) || (int) $experienceId < 1) {
    experienceDeleteResponse(['success' => false, 'message' => 'Invalid experience.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

$profileId = experienceProfileId($pdo);

try {
    $statement = $pdo->prepare('DELETE FROM work_experiences WHERE work_experience_id = :work_experience_id AND profile_id = :profile_id');
    $statement->execute([':work_experience_id' => (int) $experienceId, ':profile_id' => $profileId]);

    if ($statement->rowCount() === 0) {
        experienceDeleteResponse(['success' => false, 'message' => 'The selected experience could not be found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
    }

    experienceDeleteResponse(['success' => true, 'message' => 'Experience deleted successfully.', 'data' => ['experience_id' => (int) $experienceId], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    experienceDeleteResponse(['success' => false, 'message' => 'Unable to delete experience.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
