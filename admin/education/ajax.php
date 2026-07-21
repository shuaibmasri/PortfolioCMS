<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function educationResponse(array $payload, int $status = 200): never
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

function educationGraduationLabel(?string $startDate, ?string $endDate): string
{
    if ($endDate !== null && $endDate !== '') {
        return date('Y', strtotime($endDate));
    }

    if ($startDate !== null && $startDate !== '') {
        return date('Y', strtotime($startDate));
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    educationResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
}

$action = (string) ($_GET['action'] ?? 'list');
$profileId = educationProfileId($pdo);

try {
    if ($action === 'show') {
        $educationId = trim((string) ($_GET['id'] ?? ''));
        if ($educationId === '' || !ctype_digit($educationId) || (int) $educationId < 1) {
            educationResponse(['success' => false, 'message' => 'Invalid education record.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
        }

        $statement = $pdo->prepare(
            'SELECT education_id, profile_id, institution_name, degree, field_of_study, location, start_date, end_date, grade, description, display_order, is_public
             FROM educations
             WHERE education_id = :education_id AND profile_id = :profile_id
             LIMIT 1'
        );
        $statement->execute([':education_id' => (int) $educationId, ':profile_id' => $profileId]);
        $education = $statement->fetch(PDO::FETCH_ASSOC);

        if ($education === false) {
            educationResponse(['success' => false, 'message' => 'Education record not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $education['graduation_year'] = educationGraduationLabel((string) ($education['start_date'] ?? ''), $education['end_date'] !== null ? (string) $education['end_date'] : null);
        educationResponse(['success' => true, 'message' => 'Education loaded successfully.', 'data' => ['education' => $education], 'errors' => new stdClass()]);
    }

    $statement = $pdo->prepare(
        'SELECT education_id, profile_id, institution_name, degree, field_of_study, location, start_date, end_date, grade, description, display_order, is_public
         FROM educations
         WHERE profile_id = :profile_id
         ORDER BY display_order ASC, end_date DESC, start_date DESC, education_id DESC'
    );
    $statement->execute([':profile_id' => $profileId]);
    $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['graduation_year'] = educationGraduationLabel((string) ($row['start_date'] ?? ''), $row['end_date'] !== null ? (string) $row['end_date'] : null);
    }
    unset($row);

    educationResponse(['success' => true, 'message' => 'Education records loaded successfully.', 'data' => ['educations' => $rows, 'profile_id' => $profileId], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    educationResponse(['success' => false, 'message' => 'Unable to load education records.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
