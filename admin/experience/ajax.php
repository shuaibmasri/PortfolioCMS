<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function experienceResponse(array $payload, int $status = 200): never
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

function experiencePeriodLabel(?string $startDate, ?string $endDate, bool $isCurrent): string
{
    $start = $startDate ? date('M Y', strtotime($startDate)) : '';
    $end = $isCurrent ? 'Present' : ($endDate ? date('M Y', strtotime($endDate)) : 'Present');

    return $start !== '' ? $start . ' - ' . $end : $end;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    experienceResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
}

$action = (string) ($_GET['action'] ?? 'list');
$profileId = experienceProfileId($pdo);

try {
    if ($action === 'show') {
        $experienceId = trim((string) ($_GET['id'] ?? ''));
        if ($experienceId === '' || !ctype_digit($experienceId) || (int) $experienceId < 1) {
            experienceResponse(['success' => false, 'message' => 'Invalid experience.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
        }

        $statement = $pdo->prepare(
            'SELECT work_experience_id, profile_id, employer_name, job_title, employment_type, location, start_date, end_date, is_current, description, display_order, is_public
             FROM work_experiences
             WHERE work_experience_id = :work_experience_id AND profile_id = :profile_id
             LIMIT 1'
        );
        $statement->execute([':work_experience_id' => (int) $experienceId, ':profile_id' => $profileId]);
        $experience = $statement->fetch(PDO::FETCH_ASSOC);

        if ($experience === false) {
            experienceResponse(['success' => false, 'message' => 'Experience not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $experience['employment_period'] = experiencePeriodLabel((string) $experience['start_date'], $experience['end_date'] !== null ? (string) $experience['end_date'] : null, (bool) $experience['is_current']);
        experienceResponse(['success' => true, 'message' => 'Experience loaded successfully.', 'data' => ['experience' => $experience], 'errors' => new stdClass()]);
    }

    $statement = $pdo->prepare(
        'SELECT work_experience_id, profile_id, employer_name, job_title, employment_type, location, start_date, end_date, is_current, description, display_order, is_public
         FROM work_experiences
         WHERE profile_id = :profile_id
         ORDER BY display_order ASC, start_date DESC, work_experience_id DESC'
    );
    $statement->execute([':profile_id' => $profileId]);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['employment_period'] = experiencePeriodLabel((string) $row['start_date'], $row['end_date'] !== null ? (string) $row['end_date'] : null, (bool) $row['is_current']);
    }

    experienceResponse(['success' => true, 'message' => 'Experiences loaded successfully.', 'data' => ['experiences' => $rows, 'profile_id' => $profileId], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    experienceResponse(['success' => false, 'message' => 'Unable to load experiences.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
