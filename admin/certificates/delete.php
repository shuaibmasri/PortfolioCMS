<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function certificateDeleteResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function certificateProfileId(PDO $pdo): int
{
    $profile = $pdo->query('SELECT profile_id FROM profiles ORDER BY profile_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);

    return $profile ? (int) $profile['profile_id'] : 0;
}

function certificateDeleteFile(?string $path): void
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
    certificateDeleteResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$certificationId = trim((string) ($_POST['certification_id'] ?? ''));
if ($certificationId === '' || !ctype_digit($certificationId) || (int) $certificationId < 1) {
    certificateDeleteResponse(['success' => false, 'message' => 'Invalid certificate.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

$profileId = certificateProfileId($pdo);
$certificationIdValue = (int) $certificationId;

try {
    $statement = $pdo->prepare('SELECT certificate_image_path, certificate_file_path FROM certifications WHERE certification_id = :certification_id AND profile_id = :profile_id LIMIT 1');
    $statement->execute([':certification_id' => $certificationIdValue, ':profile_id' => $profileId]);
    $certificate = $statement->fetch(PDO::FETCH_ASSOC);

    if ($certificate === false) {
        certificateDeleteResponse(['success' => false, 'message' => 'Certificate not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
    }

    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM certifications WHERE certification_id = :certification_id AND profile_id = :profile_id')->execute([':certification_id' => $certificationIdValue, ':profile_id' => $profileId]);
    $pdo->commit();

    certificateDeleteFile((string) ($certificate['certificate_image_path'] ?? ''));
    certificateDeleteFile((string) ($certificate['certificate_file_path'] ?? ''));

    certificateDeleteResponse(['success' => true, 'message' => 'Certificate deleted successfully.', 'data' => ['certification_id' => $certificationIdValue], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());
    certificateDeleteResponse(['success' => false, 'message' => 'Unable to delete certificate.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
