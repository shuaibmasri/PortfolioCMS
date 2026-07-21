<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function projectSaveResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function projectSlugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'project';
    }

    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($slug === false) {
        $slug = $value;
    }

    $slug = strtolower((string) $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'project';
}

function projectImageUrl(?string $path): string
{
    $path = trim((string) $path);
    return $path !== '' ? url($path) : '';
}

function projectGetCurrentImagePath(PDO $pdo, int $projectId): string
{
    $statement = $pdo->prepare(
        'SELECT image_path
         FROM project_images
         WHERE project_id = :project_id
         ORDER BY is_cover_image DESC, display_order ASC, project_image_id ASC
         LIMIT 1'
    );
    $statement->execute([':project_id' => $projectId]);
    $path = $statement->fetchColumn();

    return is_string($path) ? $path : '';
}

function projectEnsureCategory(PDO $pdo, string $name): int
{
    $slug = projectSlugify($name);
    $statement = $pdo->prepare(
        'INSERT INTO project_categories (name, slug, display_order)
         VALUES (:name, :slug, 0)
         ON DUPLICATE KEY UPDATE
            project_category_id = LAST_INSERT_ID(project_category_id),
            name = VALUES(name),
            slug = VALUES(slug)'
    );
    $statement->execute([':name' => $name, ':slug' => $slug]);

    return (int) $pdo->lastInsertId();
}

function projectEnsureTechnology(PDO $pdo, string $name): int
{
    $slug = projectSlugify($name);
    $statement = $pdo->prepare(
        'INSERT INTO technologies (name, slug)
         VALUES (:name, :slug)
         ON DUPLICATE KEY UPDATE
            technology_id = LAST_INSERT_ID(technology_id),
            name = VALUES(name),
            slug = VALUES(slug)'
    );
    $statement->execute([':name' => $name, ':slug' => $slug]);

    return (int) $pdo->lastInsertId();
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

function projectStoreImage(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        return ['error' => 'Please upload a valid project image.'];
    }

    if ((int) ($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        return ['error' => 'Project image is too large.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime]) || !getimagesize((string) $file['tmp_name'])) {
        return ['error' => 'Use a valid JPG, PNG, or WebP image.'];
    }

    $directory = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'projects';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return ['error' => 'Storage is unavailable.'];
    }

    $path = 'uploads/projects/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $target = uploadFilePath($path);
    if ($target === null || !move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['error' => 'Unable to store the project image.'];
    }

    return ['path' => $path];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    projectSaveResponse([
        'success' => false,
        'message' => 'Invalid request.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 419);
}

$projectId = trim((string) ($_POST['project_id'] ?? ''));
$title = trim((string) ($_POST['title'] ?? ''));
$shortDescription = trim((string) ($_POST['short_description'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryName = trim((string) ($_POST['category_name'] ?? ''));
$technologiesInput = trim((string) ($_POST['technologies'] ?? ''));
$displayOrder = trim((string) ($_POST['display_order'] ?? ''));
$isPublic = isset($_POST['is_public']) ? 1 : 0;
$repositoryUrl = trim((string) ($_POST['repository_url'] ?? ''));
$projectUrl = trim((string) ($_POST['project_url'] ?? ''));
$status = trim((string) ($_POST['status'] ?? 'planned'));
$currentImagePath = trim((string) ($_POST['current_image_path'] ?? ''));
$imageFile = $_FILES['project_image'] ?? null;

$errors = [];
$max = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
$allowedStatuses = ['planned', 'in_progress', 'completed', 'archived'];

if ($projectId !== '' && (!ctype_digit($projectId) || (int) $projectId < 1)) {
    $errors['project_id'] = 'Invalid project.';
}

if ($title === '') {
    $errors['title'] = 'Project name is required.';
} elseif ($max($title) > 250) {
    $errors['title'] = 'Project name is too long.';
}

if ($shortDescription === '') {
    $errors['short_description'] = 'Short description is required.';
} elseif ($max($shortDescription) > 500) {
    $errors['short_description'] = 'Short description is too long.';
}

if ($description === '') {
    $errors['description'] = 'Full description is required.';
} elseif ($max($description) > 10000) {
    $errors['description'] = 'Full description is too long.';
}

if ($categoryName === '') {
    $errors['category_name'] = 'Category is required.';
} elseif ($max($categoryName) > 100) {
    $errors['category_name'] = 'Category is too long.';
}

if ($technologiesInput === '') {
    $errors['technologies'] = 'At least one technology is required.';
} elseif ($max($technologiesInput) > 1000) {
    $errors['technologies'] = 'Technologies field is too long.';
}

if ($displayOrder === '' || !ctype_digit($displayOrder)) {
    $errors['display_order'] = 'Display order must be a whole number.';
}

if ($repositoryUrl !== '' && filter_var($repositoryUrl, FILTER_VALIDATE_URL) === false) {
    $errors['repository_url'] = 'Enter a valid GitHub URL.';
}

if ($projectUrl !== '' && filter_var($projectUrl, FILTER_VALIDATE_URL) === false) {
    $errors['project_url'] = 'Enter a valid live demo URL.';
}

if (!in_array($status, $allowedStatuses, true)) {
    $errors['status'] = 'Please choose a valid status.';
}

$technologyNames = array_values(array_filter(array_map(
    static fn(string $item): string => trim($item),
    preg_split('/,/', $technologiesInput) ?: []
)));
$technologyNames = array_values(array_unique(array_filter($technologyNames, static fn(string $item): bool => $item !== '')));

if ($technologyNames === []) {
    $errors['technologies'] = 'At least one technology is required.';
}

if ($errors !== []) {
    projectSaveResponse([
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'data' => new stdClass(),
        'errors' => $errors,
    ], 422);
}

$projectIdValue = $projectId !== '' ? (int) $projectId : 0;

try {
    $pdo->beginTransaction();

    if ($projectIdValue > 0) {
        $existing = $pdo->prepare('SELECT project_id FROM projects WHERE project_id = :project_id LIMIT 1');
        $existing->execute([':project_id' => $projectIdValue]);
        if ($existing->fetchColumn() === false) {
            $pdo->rollBack();
            projectSaveResponse([
                'success' => false,
                'message' => 'The selected project could not be found.',
                'data' => new stdClass(),
                'errors' => new stdClass(),
            ], 404);
        }
    }

    $categoryId = projectEnsureCategory($pdo, $categoryName);
    $baseSlug = projectSlugify($title);
    $slug = $baseSlug;
    $suffix = 2;
    while (true) {
        $slugStatement = $pdo->prepare(
            'SELECT project_id FROM projects WHERE slug = :slug' . ($projectIdValue > 0 ? ' AND project_id <> :project_id' : '') . ' LIMIT 1'
        );
        $params = [':slug' => $slug];
        if ($projectIdValue > 0) {
            $params[':project_id'] = $projectIdValue;
        }
        $slugStatement->execute($params);
        if ($slugStatement->fetchColumn() === false) {
            break;
        }

        $slug = $baseSlug . '-' . $suffix;
        $suffix += 1;
    }

    $statementData = [
        ':project_category_id' => $categoryId,
        ':title' => $title,
        ':slug' => $slug,
        ':short_description' => $shortDescription,
        ':description' => $description,
        ':repository_url' => $repositoryUrl !== '' ? $repositoryUrl : null,
        ':project_url' => $projectUrl !== '' ? $projectUrl : null,
        ':status' => $status,
        ':display_order' => (int) $displayOrder,
        ':is_public' => $isPublic,
    ];

    if ($projectIdValue > 0) {
        $statement = $pdo->prepare(
            'UPDATE projects
             SET project_category_id = :project_category_id,
                 title = :title,
                 slug = :slug,
                 short_description = :short_description,
                 description = :description,
                 repository_url = :repository_url,
                 project_url = :project_url,
                 status = :status,
                 display_order = :display_order,
                 is_public = :is_public
             WHERE project_id = :project_id'
        );
        $statement->execute($statementData + [':project_id' => $projectIdValue]);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO projects
                (project_category_id, title, slug, short_description, description, repository_url, project_url, status, display_order, is_public)
             VALUES
                (:project_category_id, :title, :slug, :short_description, :description, :repository_url, :project_url, :status, :display_order, :is_public)'
        );
        $statement->execute($statementData);
        $projectIdValue = (int) $pdo->lastInsertId();
    }

    $technologyIds = [];
    foreach ($technologyNames as $technologyName) {
        if ($technologyName === '') {
            continue;
        }

        $technologyIds[] = projectEnsureTechnology($pdo, $technologyName);
    }
    $technologyIds = array_values(array_unique(array_map('intval', $technologyIds)));

    $pdo->prepare('DELETE FROM project_technologies WHERE project_id = :project_id')->execute([':project_id' => $projectIdValue]);
    if ($technologyIds !== []) {
        $link = $pdo->prepare('INSERT INTO project_technologies (project_id, technology_id) VALUES (:project_id, :technology_id)');
        foreach ($technologyIds as $technologyId) {
            $link->execute([':project_id' => $projectIdValue, ':technology_id' => $technologyId]);
        }
    }

    $uploadedPath = '';
    $hasImageUpload = is_array($imageFile) && (($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK);
    if ($hasImageUpload) {
        $stored = projectStoreImage($imageFile);
        if (isset($stored['error'])) {
            $pdo->rollBack();
            projectSaveResponse([
                'success' => false,
                'message' => (string) $stored['error'],
                'data' => new stdClass(),
                'errors' => ['project_image' => (string) $stored['error']],
            ], 422);
        }

        $uploadedPath = (string) $stored['path'];

        $imageStatement = $pdo->prepare(
            'SELECT project_image_id
             FROM project_images
             WHERE project_id = :project_id AND is_cover_image = 1
             LIMIT 1'
        );
        $imageStatement->execute([':project_id' => $projectIdValue]);
        $existingCoverId = $imageStatement->fetchColumn();

        if ($existingCoverId !== false) {
            $previousImagePath = projectGetCurrentImagePath($pdo, $projectIdValue);
            $updateImage = $pdo->prepare(
                'UPDATE project_images
                 SET image_path = :image_path,
                     alt_text = :alt_text,
                     caption = :caption,
                     is_cover_image = 1
                 WHERE project_image_id = :project_image_id'
            );
            $updateImage->execute([
                ':image_path' => $uploadedPath,
                ':alt_text' => $title,
                ':caption' => $shortDescription,
                ':project_image_id' => (int) $existingCoverId,
            ]);
            if ($previousImagePath !== '' && $previousImagePath !== $uploadedPath) {
                projectDeleteFile($previousImagePath);
            }
        } else {
            $insertImage = $pdo->prepare(
                'INSERT INTO project_images
                    (project_id, image_path, alt_text, caption, display_order, is_cover_image)
                 VALUES
                    (:project_id, :image_path, :alt_text, :caption, 0, 1)'
            );
            $insertImage->execute([
                ':project_id' => $projectIdValue,
                ':image_path' => $uploadedPath,
                ':alt_text' => $title,
                ':caption' => $shortDescription,
            ]);
        }
    }

    $pdo->commit();

    projectSaveResponse([
        'success' => true,
        'message' => $projectId !== '' ? 'Project updated successfully.' : 'Project added successfully.',
        'data' => [
            'project_id' => $projectIdValue,
            'image_url' => projectImageUrl($uploadedPath !== '' ? $uploadedPath : projectGetCurrentImagePath($pdo, $projectIdValue)),
        ],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (isset($uploadedPath) && is_string($uploadedPath) && $uploadedPath !== '') {
        projectDeleteFile($uploadedPath);
    }

    error_log($exception->getMessage());
    projectSaveResponse([
        'success' => false,
        'message' => 'Unable to save project.',
        'data' => new stdClass(),
        'errors' => new stdClass(),
    ], 500);
}
