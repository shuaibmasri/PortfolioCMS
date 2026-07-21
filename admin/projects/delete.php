<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function projectDeleteResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function projectDeleteFile(?string $path): void
{
    $path = trim((string) $path);
    if ($path === '') {
        return;
    }

    $fullPath = uploadFilePath($path);
    if ($fullPath !== null && is_file($fullPath)) {
        @unlink($fullPath);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    projectDeleteResponse([
        'success' => false,
        'message' => 'Invalid request.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 419);
}

$projectId = trim((string) ($_POST['project_id'] ?? ''));
if ($projectId === '' || !ctype_digit($projectId) || (int) $projectId < 1) {
    projectDeleteResponse([
        'success' => false,
        'message' => 'Invalid project.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 422);
}

$projectIdValue = (int) $projectId;

try {
    $statement = $pdo->prepare(
        'SELECT image_path
         FROM project_images
         WHERE project_id = :project_id'
    );
    $statement->execute([':project_id' => $projectIdValue]);
    $imagePaths = $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $exists = $pdo->prepare('SELECT project_id FROM projects WHERE project_id = :project_id LIMIT 1');
    $exists->execute([':project_id' => $projectIdValue]);
    if ($exists->fetchColumn() === false) {
        projectDeleteResponse([
            'success' => false,
            'message' => 'Project not found.',
            'data' => new stdClass(),
            'errors' => new stdClass(),
        ], 404);
    }

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM projects WHERE project_id = :project_id')->execute([':project_id' => $projectIdValue]);
    $pdo->commit();

    foreach (array_unique(array_filter(array_map('strval', $imagePaths))) as $path) {
        projectDeleteFile($path);
    }

    projectDeleteResponse([
        'success' => true,
        'message' => 'Project deleted successfully.',
        'data' => ['project_id' => $projectIdValue],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());
    projectDeleteResponse([
        'success' => false,
        'message' => 'Unable to delete project.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 500);
}
