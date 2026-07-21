<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
requireAdmin();
header('Content-Type: application/json');

function messageDeleteResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken(is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null)) {
    messageDeleteResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
}

$messageId = trim((string) ($_POST['contact_message_id'] ?? ''));
if ($messageId === '' || !ctype_digit($messageId) || (int) $messageId < 1) {
    messageDeleteResponse(['success' => false, 'message' => 'Invalid message.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
}

try {
    $statement = $pdo->prepare('DELETE FROM contact_messages WHERE contact_message_id = :contact_message_id');
    $statement->execute([':contact_message_id' => (int) $messageId]);

    if ($statement->rowCount() === 0) {
        messageDeleteResponse(['success' => false, 'message' => 'Message not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
    }

    messageDeleteResponse(['success' => true, 'message' => 'Message deleted successfully.', 'data' => ['contact_message_id' => (int) $messageId], 'errors' => new stdClass()]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    messageDeleteResponse(['success' => false, 'message' => 'Unable to delete message.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}
