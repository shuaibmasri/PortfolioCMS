<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function educationSaveResponse(array $payload, int $status = 200): never
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
    educationSaveResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$educationId = trim((string) ($_POST['education_id'] ?? ''));
$profileId = (int) ($_POST['profile_id'] ?? 0);
$institutionName = trim((string) ($_POST['institution_name'] ?? ''));
$degree = trim((string) ($_POST['degree'] ?? ''));
$fieldOfStudy = trim((string) ($_POST['field_of_study'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$startDate = trim((string) ($_POST['start_date'] ?? ''));
$endDate = trim((string) ($_POST['end_date'] ?? ''));
$grade = trim((string) ($_POST['grade'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$displayOrder = trim((string) ($_POST['display_order'] ?? ''));
$isPublic = isset($_POST['is_public']) ? 1 : 0;

$errors = [];
$length = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

if ($educationId !== '' && (!ctype_digit($educationId) || (int) $educationId < 1)) {
    $errors['education_id'] = 'Invalid education record.';
}

if ($institutionName === '') {
    $errors['institution_name'] = 'Institution is required.';
} elseif ($length($institutionName) > 250) {
    $errors['institution_name'] = 'Institution is too long.';
}

if ($degree !== '' && $length($degree) > 200) {
    $errors['degree'] = 'Degree is too long.';
}

if ($fieldOfStudy !== '' && $length($fieldOfStudy) > 200) {
    $errors['field_of_study'] = 'Field of study is too long.';
}

if ($location !== '' && $length($location) > 200) {
    $errors['location'] = 'Location is too long.';
}

if ($grade !== '' && $length($grade) > 100) {
    $errors['grade'] = 'Grade is too long.';
}

if ($startDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) !== 1) {
    $errors['start_date'] = 'Please provide a valid start date.';
}

if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) !== 1) {
    $errors['end_date'] = 'Please provide a valid graduation date.';
}

if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
    $errors['end_date'] = 'Graduation date must be after the start date.';
}

if ($displayOrder === '' || !ctype_digit($displayOrder)) {
    $errors['display_order'] = 'Display order must be a whole number.';
}

if ($description !== '' && $length($description) > 4000) {
    $errors['description'] = 'Description is too long.';
}

if ($errors !== []) {
    educationSaveResponse(['success' => false, 'message' => 'Please correct the highlighted fields.', 'data' => new stdClass(), 'errors' => $errors], 422);
}

if ($profileId < 1) {
    $profileId = educationProfileId($pdo);
}

if ($profileId < 1) {
    educationSaveResponse(['success' => false, 'message' => 'Create a profile before adding education records.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

try {
    $pdo->beginTransaction();

    if ($educationId !== '') {
        $existing = $pdo->prepare('SELECT education_id FROM educations WHERE education_id = :education_id AND profile_id = :profile_id');
        $existing->execute([':education_id' => (int) $educationId, ':profile_id' => $profileId]);
        if ($existing->fetch() === false) {
            $pdo->rollBack();
            educationSaveResponse(['success' => false, 'message' => 'The selected education record could not be found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $statement = $pdo->prepare(
            'UPDATE educations
             SET institution_name = :institution_name,
                 degree = :degree,
                 field_of_study = :field_of_study,
                 location = :location,
                 start_date = :start_date,
                 end_date = :end_date,
                 grade = :grade,
                 description = :description,
                 display_order = :display_order,
                 is_public = :is_public
             WHERE education_id = :education_id AND profile_id = :profile_id'
        );
        $statement->execute([
            ':institution_name' => $institutionName,
            ':degree' => $degree !== '' ? $degree : null,
            ':field_of_study' => $fieldOfStudy !== '' ? $fieldOfStudy : null,
            ':location' => $location !== '' ? $location : null,
            ':start_date' => $startDate !== '' ? $startDate : null,
            ':end_date' => $endDate !== '' ? $endDate : null,
            ':grade' => $grade !== '' ? $grade : null,
            ':description' => $description !== '' ? $description : null,
            ':display_order' => (int) $displayOrder,
            ':is_public' => $isPublic,
            ':education_id' => (int) $educationId,
            ':profile_id' => $profileId,
        ]);

        $pdo->commit();
        educationSaveResponse(['success' => true, 'message' => 'Education updated successfully.', 'data' => ['education_id' => (int) $educationId], 'errors' => new stdClass()]);
    }

    $statement = $pdo->prepare(
        'INSERT INTO educations
            (profile_id, institution_name, degree, field_of_study, location, start_date, end_date, grade, description, display_order, is_public)
         VALUES
            (:profile_id, :institution_name, :degree, :field_of_study, :location, :start_date, :end_date, :grade, :description, :display_order, :is_public)'
    );
    $statement->execute([
        ':profile_id' => $profileId,
        ':institution_name' => $institutionName,
        ':degree' => $degree !== '' ? $degree : null,
        ':field_of_study' => $fieldOfStudy !== '' ? $fieldOfStudy : null,
        ':location' => $location !== '' ? $location : null,
        ':start_date' => $startDate !== '' ? $startDate : null,
        ':end_date' => $endDate !== '' ? $endDate : null,
        ':grade' => $grade !== '' ? $grade : null,
        ':description' => $description !== '' ? $description : null,
        ':display_order' => (int) $displayOrder,
        ':is_public' => $isPublic,
    ]);

    $pdo->commit();
    educationSaveResponse(['success' => true, 'message' => 'Education added successfully.', 'data' => ['education_id' => (int) $pdo->lastInsertId()], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());
    educationSaveResponse(['success' => false, 'message' => 'Unable to save education record.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
