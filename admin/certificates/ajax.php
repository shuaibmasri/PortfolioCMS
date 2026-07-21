<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function certificateAjaxResponse(array $payload, int $status = 200): never
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

function certificateImageUrl(?string $path): string
{
    $path = trim((string) $path);

    return $path !== '' ? url($path) : '';
}

function certificateDateLabel(?string $value, string $fallback = '-'): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('M j, Y', $timestamp);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    certificateAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
}

$action = (string) ($_GET['action'] ?? 'list');
$profileId = certificateProfileId($pdo);

try {
    if ($action === 'show') {
        $certificationId = trim((string) ($_GET['id'] ?? ''));
        if ($certificationId === '' || !ctype_digit($certificationId) || (int) $certificationId < 1) {
            certificateAjaxResponse(['success' => false, 'message' => 'Invalid certificate.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
        }

        $statement = $pdo->prepare(
            'SELECT certification_id, profile_id, name, issuing_organization, credential_id, credential_url, issued_date, expiry_date, certificate_image_path, certificate_file_path, display_order, is_public, created_at, updated_at
             FROM certifications
             WHERE certification_id = :certification_id AND profile_id = :profile_id
             LIMIT 1'
        );
        $statement->execute([':certification_id' => (int) $certificationId, ':profile_id' => $profileId]);
        $certificate = $statement->fetch(PDO::FETCH_ASSOC);

        if ($certificate === false) {
            certificateAjaxResponse(['success' => false, 'message' => 'Certificate not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $certificate['image_url'] = certificateImageUrl((string) ($certificate['certificate_image_path'] ?? ''));
        $certificate['pdf_url'] = certificateImageUrl((string) ($certificate['certificate_file_path'] ?? ''));
        $certificate['issue_date_label'] = certificateDateLabel($certificate['issued_date'] ?? null);
        $certificate['expiry_date_label'] = certificateDateLabel($certificate['expiry_date'] ?? null);
        $certificate['certificate_pdf_name'] = trim((string) ($certificate['certificate_file_path'] ?? '')) !== '' ? basename((string) $certificate['certificate_file_path']) : '';

        certificateAjaxResponse(['success' => true, 'message' => 'Certificate loaded successfully.', 'data' => ['certificate' => $certificate], 'errors' => new stdClass()]);
    }

    $statement = $pdo->prepare(
        'SELECT certification_id, profile_id, name, issuing_organization, credential_id, credential_url, issued_date, expiry_date, certificate_image_path, certificate_file_path, display_order, is_public, created_at, updated_at
         FROM certifications
         WHERE profile_id = :profile_id
         ORDER BY display_order ASC, issued_date DESC, certification_id DESC'
    );
    $statement->execute([':profile_id' => $profileId]);
    $certificates = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($certificates as &$certificate) {
        $certificate['image_url'] = certificateImageUrl((string) ($certificate['certificate_image_path'] ?? ''));
        $certificate['pdf_url'] = certificateImageUrl((string) ($certificate['certificate_file_path'] ?? ''));
        $certificate['issue_date_label'] = certificateDateLabel($certificate['issued_date'] ?? null);
        $certificate['expiry_date_label'] = certificateDateLabel($certificate['expiry_date'] ?? null);
        $certificate['certificate_pdf_name'] = trim((string) ($certificate['certificate_file_path'] ?? '')) !== '' ? basename((string) $certificate['certificate_file_path']) : '';
    }
    unset($certificate);

    certificateAjaxResponse(['success' => true, 'message' => 'Certificates loaded successfully.', 'data' => ['certificates' => $certificates, 'profile_id' => $profileId], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    certificateAjaxResponse(['success' => false, 'message' => 'Unable to load certificates.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
