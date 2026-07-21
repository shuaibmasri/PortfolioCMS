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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    respond(['success' => false, 'message' => 'Invalid request.'], 419);
}

$skillId = trim((string) ($_POST['skill_id'] ?? ''));
$skillName = trim((string) ($_POST['skill_name'] ?? ''));
$skillCategory = trim((string) ($_POST['skill_category'] ?? ''));
$proficiency = trim((string) ($_POST['proficiency'] ?? ''));
$displayOrder = trim((string) ($_POST['display_order'] ?? ''));
$isPublic = isset($_POST['is_public']) ? 1 : 0;

$errors = [];
$length = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

if ($skillId !== '' && (!ctype_digit($skillId) || (int) $skillId < 1)) {
    $errors['skill_id'] = 'Invalid skill.';
}

if ($skillName === '') {
    $errors['skill_name'] = 'Skill name is required.';
} elseif ($length($skillName) > 150) {
    $errors['skill_name'] = 'Skill name is too long.';
}

if ($skillCategory === '') {
    $errors['skill_category'] = 'Category is required.';
} elseif ($length($skillCategory) > 100) {
    $errors['skill_category'] = 'Category is too long.';
}

$proficiencyValue = filter_var($proficiency, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
if ($proficiency === '' || $proficiencyValue === false) {
    $errors['proficiency'] = 'Proficiency must be between 0 and 100.';
}

if ($displayOrder === '' || !ctype_digit($displayOrder)) {
    $errors['display_order'] = 'Display order must be a whole number.';
}

if ($errors !== []) {
    respond(['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors], 422);
}

try {
    $pdo->beginTransaction();

    $categoryStmt = $pdo->prepare(
        'INSERT INTO skill_categories (name, display_order)
         VALUES (:name, 0)
         ON DUPLICATE KEY UPDATE skill_category_id = LAST_INSERT_ID(skill_category_id)'
    );
    $categoryStmt->execute([':name' => $skillCategory]);
    $skillCategoryId = (int) $pdo->lastInsertId();

    if ($skillId !== '') {
        $existing = $pdo->prepare('SELECT skill_id FROM skills WHERE skill_id = :skill_id');
        $existing->execute([':skill_id' => (int) $skillId]);
        if ($existing->fetch() === false) {
            $pdo->rollBack();
            respond(['success' => false, 'message' => 'The selected skill could not be found.'], 404);
        }

        $update = $pdo->prepare(
            'UPDATE skills
             SET skill_category_id = :skill_category_id,
                 name = :name,
                 proficiency_level = :proficiency_level,
                 display_order = :display_order,
                 is_public = :is_public
             WHERE skill_id = :skill_id'
        );
        $update->execute([
            ':skill_category_id' => $skillCategoryId,
            ':name' => $skillName,
            ':proficiency_level' => (int) $proficiencyValue,
            ':display_order' => (int) $displayOrder,
            ':is_public' => $isPublic,
            ':skill_id' => (int) $skillId,
        ]);

        $pdo->commit();
        respond(['success' => true, 'message' => 'Skill updated successfully.']);
    }

    $insert = $pdo->prepare(
        'INSERT INTO skills (skill_category_id, name, proficiency_level, display_order, is_public)
         VALUES (:skill_category_id, :name, :proficiency_level, :display_order, :is_public)'
    );
    $insert->execute([
        ':skill_category_id' => $skillCategoryId,
        ':name' => $skillName,
        ':proficiency_level' => (int) $proficiencyValue,
        ':display_order' => (int) $displayOrder,
        ':is_public' => $isPublic,
    ]);

    $pdo->commit();
    respond(['success' => true, 'message' => 'Skill added successfully.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($exception instanceof PDOException && (int) $exception->getCode() === 23000) {
        respond(['success' => false, 'message' => 'That skill already exists in this category.'], 422);
    }

    error_log($exception->getMessage());
    respond(['success' => false, 'message' => 'Unable to save skill.'], 500);
}
