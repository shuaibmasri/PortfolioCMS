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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['success' => false, 'message' => 'Invalid request.'], 405);
}

$action = (string) ($_GET['action'] ?? 'list');

try {
    if ($action === 'show') {
        $skillId = trim((string) ($_GET['id'] ?? ''));
        if ($skillId === '' || !ctype_digit($skillId) || (int) $skillId < 1) {
            respond(['success' => false, 'message' => 'Invalid skill.'], 422);
        }

        $statement = $pdo->prepare(
            'SELECT s.skill_id,
                    s.skill_category_id,
                    s.name AS skill_name,
                    c.name AS category_name,
                    s.proficiency_level,
                    s.display_order,
                    s.is_public
             FROM skills s
             INNER JOIN skill_categories c ON c.skill_category_id = s.skill_category_id
             WHERE s.skill_id = :skill_id
             LIMIT 1'
        );
        $statement->execute([':skill_id' => (int) $skillId]);
        $skill = $statement->fetch(PDO::FETCH_ASSOC);

        if ($skill === false) {
            respond(['success' => false, 'message' => 'Skill not found.'], 404);
        }

        respond(['success' => true, 'skill' => $skill]);
    }

    $statement = $pdo->query(
        'SELECT s.skill_id,
                s.skill_category_id,
                s.name AS skill_name,
                c.name AS category_name,
                s.proficiency_level,
                s.display_order,
                s.is_public
         FROM skills s
         INNER JOIN skill_categories c ON c.skill_category_id = s.skill_category_id
         ORDER BY s.display_order ASC, s.name ASC, s.skill_id ASC'
    );

    respond(['success' => true, 'skills' => $statement->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    respond(['success' => false, 'message' => 'Unable to load skills.'], 500);
}
