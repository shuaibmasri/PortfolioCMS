<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function certificateSaveResponse(array $payload, int $status = 200): never
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

function certificateStoreImage(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        return ['error' => 'Please upload a valid certificate image.'];
    }

    if ((int) ($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        return ['error' => 'Certificate image is too large.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime]) || !getimagesize((string) $file['tmp_name'])) {
        return ['error' => 'Use a valid JPG, PNG, or WebP certificate image.'];
    }

    $directory = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'certificates';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['error' => 'Storage is unavailable.'];
    }

    $path = 'uploads/certificates/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $target = uploadFilePath($path);
    if ($target === null || !move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['error' => 'Unable to store the certificate image.'];
    }

    return ['path' => $path];
}

function certificateStorePdf(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        return ['error' => 'Please upload a valid PDF certificate file.'];
    }

    if ((int) ($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        return ['error' => 'Certificate PDF is too large.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
        return ['error' => 'Use a valid PDF certificate file.'];
    }

    $directory = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'certificates';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['error' => 'Storage is unavailable.'];
    }

    $path = 'uploads/certificates/' . bin2hex(random_bytes(16)) . '.pdf';
    $target = uploadFilePath($path);
    if ($target === null || !move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['error' => 'Unable to store the certificate PDF.'];
    }

    return ['path' => $path];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    certificateSaveResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$certificationId = trim((string) ($_POST['certification_id'] ?? ''));
$profileId = (int) ($_POST['profile_id'] ?? 0);
$name = trim((string) ($_POST['name'] ?? ''));
$issuingOrganization = trim((string) ($_POST['issuing_organization'] ?? ''));
$credentialUrl = trim((string) ($_POST['credential_url'] ?? ''));
$issuedDate = trim((string) ($_POST['issued_date'] ?? ''));
$expiryDate = trim((string) ($_POST['expiry_date'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$displayOrder = trim((string) ($_POST['display_order'] ?? ''));
$isPublic = isset($_POST['is_public']) ? 1 : 0;
$currentImagePath = trim((string) ($_POST['current_image_path'] ?? ''));
$currentPdfPath = trim((string) ($_POST['current_pdf_path'] ?? ''));
$imageFile = $_FILES['certificate_image'] ?? null;
$pdfFile = $_FILES['certificate_pdf'] ?? null;

$errors = [];
$length = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

if ($certificationId !== '' && (!ctype_digit($certificationId) || (int) $certificationId < 1)) {
    $errors['certification_id'] = 'Invalid certificate.';
}

if ($name === '') {
    $errors['name'] = 'Certificate name is required.';
} elseif ($length($name) > 250) {
    $errors['name'] = 'Certificate name is too long.';
}

if ($issuingOrganization === '') {
    $errors['issuing_organization'] = 'Issuing organization is required.';
} elseif ($length($issuingOrganization) > 250) {
    $errors['issuing_organization'] = 'Issuing organization is too long.';
}

if ($issuedDate === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $issuedDate) !== 1) {
    $errors['issued_date'] = 'Please provide a valid issue date.';
}

if ($expiryDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate) !== 1) {
    $errors['expiry_date'] = 'Please provide a valid expiration date.';
}

if ($issuedDate !== '' && $expiryDate !== '' && $expiryDate < $issuedDate) {
    $errors['expiry_date'] = 'Expiration date must be after the issue date.';
}

if ($credentialUrl !== '' && filter_var($credentialUrl, FILTER_VALIDATE_URL) === false) {
    $errors['credential_url'] = 'Please provide a valid credential URL.';
} elseif ($credentialUrl !== '' && $length($credentialUrl) > 500) {
    $errors['credential_url'] = 'Credential URL is too long.';
}

if ($description !== '' && $length($description) > 4000) {
    $errors['description'] = 'Description is too long.';
}

if ($displayOrder === '' || !ctype_digit($displayOrder)) {
    $errors['display_order'] = 'Display order must be a whole number.';
}

$hasImageUpload = is_array($imageFile) && (($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK);
$hasPdfUpload = is_array($pdfFile) && (($pdfFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK);

if ($certificationId === '' && !$hasImageUpload) {
    $errors['certificate_image'] = 'Certificate image is required.';
}

if ($hasImageUpload) {
    $storedImage = certificateStoreImage($imageFile);
    if (isset($storedImage['error'])) {
        $errors['certificate_image'] = (string) $storedImage['error'];
    }
}

if ($hasPdfUpload) {
    $storedPdf = certificateStorePdf($pdfFile);
    if (isset($storedPdf['error'])) {
        $errors['certificate_pdf'] = (string) $storedPdf['error'];
    }
}

if ($errors !== []) {
    if (isset($storedImage['path']) && is_string($storedImage['path'])) {
        certificateDeleteFile($storedImage['path']);
    }
    if (isset($storedPdf['path']) && is_string($storedPdf['path'])) {
        certificateDeleteFile($storedPdf['path']);
    }
    certificateSaveResponse(['success' => false, 'message' => 'Please correct the highlighted fields.', 'data' => new stdClass(), 'errors' => $errors], 422);
}

$uploadedImagePath = isset($storedImage['path']) && is_string($storedImage['path']) ? $storedImage['path'] : '';
$uploadedPdfPath = isset($storedPdf['path']) && is_string($storedPdf['path']) ? $storedPdf['path'] : '';

if ($profileId < 1) {
    $profileId = certificateProfileId($pdo);
}

if ($profileId < 1) {
    certificateDeleteFile($uploadedImagePath);
    certificateDeleteFile($uploadedPdfPath);
    certificateSaveResponse(['success' => false, 'message' => 'Create a profile before adding certificates.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

$certificationIdValue = $certificationId !== '' ? (int) $certificationId : 0;

try {
    $pdo->beginTransaction();

    $currentRecord = ['certificate_image_path' => '', 'certificate_file_path' => ''];
    if ($certificationIdValue > 0) {
        $existing = $pdo->prepare(
            'SELECT certification_id, certificate_image_path, certificate_file_path
             FROM certifications
             WHERE certification_id = :certification_id AND profile_id = :profile_id
             LIMIT 1'
        );
        $existing->execute([':certification_id' => $certificationIdValue, ':profile_id' => $profileId]);
        $currentRecord = $existing->fetch(PDO::FETCH_ASSOC);
        if ($currentRecord === false) {
            $pdo->rollBack();
            certificateSaveResponse(['success' => false, 'message' => 'The selected certificate could not be found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }
        $currentRecord = $currentRecord ?: ['certificate_image_path' => '', 'certificate_file_path' => ''];
    }

    $finalImagePath = $uploadedImagePath !== ''
        ? $uploadedImagePath
        : (string) ($currentRecord['certificate_image_path'] ?? '');
    $finalPdfPath = $uploadedPdfPath !== ''
        ? $uploadedPdfPath
        : (string) ($currentRecord['certificate_file_path'] ?? '');

    if ($finalImagePath === '') {
        $pdo->rollBack();
        certificateSaveResponse(['success' => false, 'message' => 'Certificate image is required.', 'data' => new stdClass(), 'errors' => ['certificate_image' => 'Certificate image is required.']], 422);
    }

    $data = [
        ':profile_id' => $profileId,
        ':name' => $name,
        ':issuing_organization' => $issuingOrganization,
        ':credential_url' => $credentialUrl !== '' ? $credentialUrl : null,
        ':issued_date' => $issuedDate,
        ':expiry_date' => $expiryDate !== '' ? $expiryDate : null,
        ':certificate_image_path' => $finalImagePath,
        ':certificate_file_path' => $finalPdfPath !== '' ? $finalPdfPath : null,
        ':display_order' => (int) $displayOrder,
        ':is_public' => $isPublic,
    ];

    if ($certificationIdValue > 0) {
        $statement = $pdo->prepare(
            'UPDATE certifications
             SET name = :name,
                 issuing_organization = :issuing_organization,
                 credential_url = :credential_url,
                 issued_date = :issued_date,
                 expiry_date = :expiry_date,
                 certificate_image_path = :certificate_image_path,
                 certificate_file_path = :certificate_file_path,
                 display_order = :display_order,
                 is_public = :is_public
             WHERE certification_id = :certification_id AND profile_id = :profile_id'
        );
        $statement->execute($data + [':certification_id' => $certificationIdValue]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO certifications
                (profile_id, name, issuing_organization, credential_url, issued_date, expiry_date, certificate_image_path, certificate_file_path, display_order, is_public)
             VALUES
                (:profile_id, :name, :issuing_organization, :credential_url, :issued_date, :expiry_date, :certificate_image_path, :certificate_file_path, :display_order, :is_public)'
        );
        $statement->execute($data);
        $certificationIdValue = (int) $pdo->lastInsertId();
    }

    $pdo->commit();

    if ($certificationIdValue > 0) {
        $previousImagePath = trim((string) ($currentRecord['certificate_image_path'] ?? ''));
        $previousPdfPath = trim((string) ($currentRecord['certificate_file_path'] ?? ''));
        if ($uploadedImagePath !== '' && $previousImagePath !== '' && $previousImagePath !== $uploadedImagePath) {
            certificateDeleteFile($previousImagePath);
        }
        if ($uploadedPdfPath !== '' && $previousPdfPath !== '' && $previousPdfPath !== $uploadedPdfPath) {
            certificateDeleteFile($previousPdfPath);
        }
    }

    certificateSaveResponse([
        'success' => true,
        'message' => $certificationId !== '' ? 'Certificate updated successfully.' : 'Certificate added successfully.',
        'data' => [
            'certification_id' => $certificationIdValue,
            'image_url' => url($finalImagePath),
            'pdf_url' => $finalPdfPath !== '' ? url($finalPdfPath) : '',
        ],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($uploadedImagePath !== '') {
        certificateDeleteFile($uploadedImagePath);
    }
    if ($uploadedPdfPath !== '') {
        certificateDeleteFile($uploadedPdfPath);
    }

    error_log($exception->getMessage());
    certificateSaveResponse(['success' => false, 'message' => 'Unable to save certificate.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
