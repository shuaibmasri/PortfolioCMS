<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function experienceSaveResponse(array $payload, int $status = 200): never
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
    experienceSaveResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$experienceId = trim((string) ($_POST['experience_id'] ?? ''));
$profileId = (int) ($_POST['profile_id'] ?? 0);
$employerName = trim((string) ($_POST['employer_name'] ?? ''));
$jobTitle = trim((string) ($_POST['job_title'] ?? ''));
$employmentType = trim((string) ($_POST['employment_type'] ?? ''));
$location = trim((string) ($_POST['location'] ?? ''));
$startDate = trim((string) ($_POST['start_date'] ?? ''));
$endDate = trim((string) ($_POST['end_date'] ?? ''));
$isCurrent = isset($_POST['is_current']) ? 1 : 0;
$description = trim((string) ($_POST['description'] ?? ''));
$displayOrder = trim((string) ($_POST['display_order'] ?? ''));
$isPublic = isset($_POST['is_public']) ? 1 : 0;

$errors = [];
$max = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

if ($experienceId !== '' && (!ctype_digit($experienceId) || (int) $experienceId < 1)) {
    $errors['experience_id'] = 'Invalid experience.';
}

if ($employerName === '') {
    $errors['employer_name'] = 'Company is required.';
} elseif ($max($employerName) > 200) {
    $errors['employer_name'] = 'Company is too long.';
}

if ($jobTitle === '') {
    $errors['job_title'] = 'Position is required.';
} elseif ($max($jobTitle) > 200) {
    $errors['job_title'] = 'Position is too long.';
}

if ($employmentType !== '' && $max($employmentType) > 50) {
    $errors['employment_type'] = 'Employment type is too long.';
}

if ($location !== '' && $max($location) > 200) {
    $errors['location'] = 'Location is too long.';
}

if ($startDate === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) !== 1 || $startDate > date('Y-m-d')) {
    $errors['start_date'] = 'Please provide a valid start date.';
}

if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) !== 1) {
    $errors['end_date'] = 'Please provide a valid end date.';
}

if ($displayOrder === '' || !ctype_digit($displayOrder)) {
    $errors['display_order'] = 'Display order must be a whole number.';
}

if ($description !== '' && $max($description) > 4000) {
    $errors['description'] = 'Description is too long.';
}

if ($isCurrent === 1) {
    $endDate = '';
}

if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
    $errors['end_date'] = 'End date must be after the start date.';
}

if ($errors !== []) {
    experienceSaveResponse(['success' => false, 'message' => 'Please correct the highlighted fields.', 'data' => new stdClass(), 'errors' => $errors], 422);
}

if ($profileId < 1) {
    $profileId = experienceProfileId($pdo);
}

if ($profileId < 1) {
    experienceSaveResponse(['success' => false, 'message' => 'Create a profile before adding experiences.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

try {
    $pdo->beginTransaction();

    if ($experienceId !== '') {
        $existing = $pdo->prepare('SELECT work_experience_id FROM work_experiences WHERE work_experience_id = :work_experience_id AND profile_id = :profile_id');
        $existing->execute([':work_experience_id' => (int) $experienceId, ':profile_id' => $profileId]);
        if ($existing->fetch() === false) {
            $pdo->rollBack();
            experienceSaveResponse(['success' => false, 'message' => 'The selected experience could not be found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $statement = $pdo->prepare(
            'UPDATE work_experiences
             SET employer_name = :employer_name,
                 job_title = :job_title,
                 employment_type = :employment_type,
                 location = :location,
                 start_date = :start_date,
                 end_date = :end_date,
                 is_current = :is_current,
                 description = :description,
                 display_order = :display_order,
                 is_public = :is_public
             WHERE work_experience_id = :work_experience_id AND profile_id = :profile_id'
        );
        $statement->execute([
            ':employer_name' => $employerName,
            ':job_title' => $jobTitle,
            ':employment_type' => $employmentType !== '' ? $employmentType : null,
            ':location' => $location !== '' ? $location : null,
            ':start_date' => $startDate,
            ':end_date' => $endDate !== '' ? $endDate : null,
            ':is_current' => $isCurrent,
            ':description' => $description !== '' ? $description : null,
            ':display_order' => (int) $displayOrder,
            ':is_public' => $isPublic,
            ':work_experience_id' => (int) $experienceId,
            ':profile_id' => $profileId,
        ]);

        $pdo->commit();
        experienceSaveResponse(['success' => true, 'message' => 'Experience updated successfully.', 'data' => ['experience_id' => (int) $experienceId], 'errors' => new stdClass()]);
    }

    $statement = $pdo->prepare(
        'INSERT INTO work_experiences
            (profile_id, employer_name, job_title, employment_type, location, start_date, end_date, is_current, description, display_order, is_public)
         VALUES
            (:profile_id, :employer_name, :job_title, :employment_type, :location, :start_date, :end_date, :is_current, :description, :display_order, :is_public)'
    );
    $statement->execute([
        ':profile_id' => $profileId,
        ':employer_name' => $employerName,
        ':job_title' => $jobTitle,
        ':employment_type' => $employmentType !== '' ? $employmentType : null,
        ':location' => $location !== '' ? $location : null,
        ':start_date' => $startDate,
        ':end_date' => $endDate !== '' ? $endDate : null,
        ':is_current' => $isCurrent,
        ':description' => $description !== '' ? $description : null,
        ':display_order' => (int) $displayOrder,
        ':is_public' => $isPublic,
    ]);

    $pdo->commit();
    experienceSaveResponse(['success' => true, 'message' => 'Experience added successfully.', 'data' => ['experience_id' => (int) $pdo->lastInsertId()], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());
    experienceSaveResponse(['success' => false, 'message' => 'Unable to save experience.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
