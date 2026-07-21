<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function projectAjaxResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function projectImageUrl(?string $path): string
{
    $path = trim((string) $path);
    return $path !== '' ? url($path) : '';
}

function projectStatusLabel(string $status): string
{
    switch ($status) {
        case 'in_progress':
            return 'In Progress';
        case 'completed':
            return 'Completed';
        case 'archived':
            return 'Archived';
        default:
            return 'Planned';
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    projectAjaxResponse([
        'success' => false,
        'message' => 'Invalid request.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 405);
}

$action = (string) ($_GET['action'] ?? 'list');

try {
    if ($action === 'show') {
        $projectId = trim((string) ($_GET['id'] ?? ''));
        if ($projectId === '' || !ctype_digit($projectId) || (int) $projectId < 1) {
            projectAjaxResponse([
                'success' => false,
                'message' => 'Invalid project.',
                'data' => new stdClass(),
                'errors' => new stdClass(),
            ], 422);
        }

        $statement = $pdo->prepare(
            'SELECT p.project_id,
                    p.project_category_id,
                    c.name AS category_name,
                    p.title,
                    p.slug,
                    p.short_description,
                    p.description,
                    p.repository_url,
                    p.project_url,
                    p.status,
                    p.display_order,
                    p.is_public,
                    p.created_at,
                    p.updated_at,
                    (
                        SELECT pi.image_path
                        FROM project_images pi
                        WHERE pi.project_id = p.project_id
                        ORDER BY pi.is_cover_image DESC, pi.display_order ASC, pi.project_image_id ASC
                        LIMIT 1
                    ) AS image_path,
                    (
                        SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR "||")
                        FROM project_technologies pt
                        INNER JOIN technologies t ON t.technology_id = pt.technology_id
                        WHERE pt.project_id = p.project_id
                    ) AS technology_names
             FROM projects p
             LEFT JOIN project_categories c ON c.project_category_id = p.project_category_id
             WHERE p.project_id = :project_id
             LIMIT 1'
        );
        $statement->execute([':project_id' => (int) $projectId]);
        $project = $statement->fetch(PDO::FETCH_ASSOC);

        if ($project === false) {
            projectAjaxResponse([
                'success' => false,
                'message' => 'Project not found.',
                'data' => new stdClass(),
                'errors' => new stdClass(),
            ], 404);
        }

        $project['image_url'] = projectImageUrl((string) ($project['image_path'] ?? ''));
        $project['status_label'] = projectStatusLabel((string) ($project['status'] ?? 'planned'));
        $project['technologies'] = [];
        $rawTechnologies = trim((string) ($project['technology_names'] ?? ''));
        if ($rawTechnologies !== '') {
            $project['technologies'] = array_values(array_filter(array_map('trim', explode('||', $rawTechnologies))));
        }

        projectAjaxResponse([
            'success' => true,
            'message' => 'Project loaded successfully.',
            'data' => ['project' => $project],
            'errors' => new stdClass(),
        ]);
    }

    $statement = $pdo->query(
        'SELECT p.project_id,
                p.project_category_id,
                c.name AS category_name,
                p.title,
                p.slug,
                p.short_description,
                p.description,
                p.repository_url,
                p.project_url,
                p.status,
                p.display_order,
                p.is_public,
                p.created_at,
                p.updated_at,
                (
                    SELECT pi.image_path
                    FROM project_images pi
                    WHERE pi.project_id = p.project_id
                    ORDER BY pi.is_cover_image DESC, pi.display_order ASC, pi.project_image_id ASC
                    LIMIT 1
                ) AS image_path,
                (
                    SELECT GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR "||")
                    FROM project_technologies pt
                    INNER JOIN technologies t ON t.technology_id = pt.technology_id
                    WHERE pt.project_id = p.project_id
                ) AS technology_names
         FROM projects p
         LEFT JOIN project_categories c ON c.project_category_id = p.project_category_id
         ORDER BY p.display_order ASC, p.title ASC, p.project_id DESC'
    );
    $projects = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($projects as &$project) {
        $project['image_url'] = projectImageUrl((string) ($project['image_path'] ?? ''));
        $project['status_label'] = projectStatusLabel((string) ($project['status'] ?? 'planned'));
        $rawTechnologies = trim((string) ($project['technology_names'] ?? ''));
        $project['technologies'] = $rawTechnologies !== '' ? array_values(array_filter(array_map('trim', explode('||', $rawTechnologies)))) : [];
    }

    $categories = $pdo->query('SELECT name FROM project_categories ORDER BY display_order ASC, name ASC')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $technologies = $pdo->query('SELECT name FROM technologies ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN) ?: [];

    projectAjaxResponse([
        'success' => true,
        'message' => 'Projects loaded successfully.',
        'data' => [
            'projects' => $projects,
            'categories' => $categories,
            'technologies' => $technologies,
        ],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    projectAjaxResponse([
        'success' => false,
        'message' => 'Unable to load projects.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 500);
}
