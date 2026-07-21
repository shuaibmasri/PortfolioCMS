<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    respond(['success' => false, 'message' => 'Invalid request.'], 419);
}

$skillId = trim((string) ($_POST['skill_id'] ?? ''));
if ($skillId === '' || !ctype_digit($skillId) || (int) $skillId < 1) {
    respond(['success' => false, 'message' => 'Invalid skill.'], 422);
}

try {
    $statement = $pdo->prepare('DELETE FROM skills WHERE skill_id = :skill_id');
    $statement->execute([':skill_id' => (int) $skillId]);

    if ($statement->rowCount() === 0) {
        respond(['success' => false, 'message' => 'The selected skill could not be found.'], 404);
    }

    respond(['success' => true, 'message' => 'Skill deleted successfully.']);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    respond(['success' => false, 'message' => 'Unable to delete skill.'], 500);
}
