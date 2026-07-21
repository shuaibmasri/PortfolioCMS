<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
header('Content-Type: application/json');

function messageAjaxResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function messageStatusLabel(string $status): string
{
    switch ($status) {
        case 'read':
            return 'Read';
        case 'replied':
            return 'Replied';
        default:
            return 'New';
    }
}

function messageStatusClass(string $status): string
{
    switch ($status) {
        case 'read':
            return 'text-bg-success';
        case 'replied':
            return 'text-bg-info';
        default:
            return 'text-bg-warning';
    }
}

function messageDateLabel(?string $value, string $fallback = '-'): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $fallback;
    }

    return date('M j, Y g:i A', $timestamp);
}

function messageIpToBinary(?string $ip): ?string
{
    $ip = trim((string) $ip);
    if ($ip === '') {
        return null;
    }

    $binary = @inet_pton($ip);
    return $binary === false ? null : $binary;
}

function messageBodySnippet(string $body, int $length = 120): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($body, 'UTF-8') <= $length) {
            return $body;
        }

        return rtrim(mb_substr($body, 0, $length, 'UTF-8')) . '...';
    }

    if (strlen($body) <= $length) {
        return $body;
    }

    return rtrim(substr($body, 0, $length)) . '...';
}

function messageMetaArray(array $row): array
{
    return [
        'contact_message_id' => (int) ($row['contact_message_id'] ?? 0),
        'sender_name' => (string) ($row['sender_name'] ?? ''),
        'sender_email' => (string) ($row['sender_email'] ?? ''),
        'subject' => (string) ($row['subject'] ?? ''),
        'message_body' => (string) ($row['message_body'] ?? ''),
        'status' => (string) ($row['status'] ?? 'new'),
        'status_label' => messageStatusLabel((string) ($row['status'] ?? 'new')),
        'status_class' => messageStatusClass((string) ($row['status'] ?? 'new')),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'created_label' => messageDateLabel($row['created_at'] ?? null),
        'read_at' => (string) ($row['read_at'] ?? ''),
        'read_label' => messageDateLabel($row['read_at'] ?? null),
        'replied_at' => (string) ($row['replied_at'] ?? ''),
        'replied_label' => messageDateLabel($row['replied_at'] ?? null),
        'ip_address_text' => (string) ($row['ip_address_text'] ?? ''),
        'user_agent' => (string) ($row['user_agent'] ?? ''),
        'subject_snippet' => messageBodySnippet((string) ($row['subject'] ?? ''), 80),
        'body_snippet' => messageBodySnippet((string) ($row['message_body'] ?? ''), 120),
    ];
}

$action = (string) ($_GET['action'] ?? 'list');

if ($action === 'submit') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        messageAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
    }

    $token = is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null;
    if (!verifyCsrfToken($token)) {
        messageAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
    }

    $senderName = trim((string) ($_POST['sender_name'] ?? ''));
    $senderEmail = trim((string) ($_POST['sender_email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $messageBody = trim((string) ($_POST['message_body'] ?? ''));

    $errors = [];
    $length = static fn(string $value): int => function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

    if ($senderName === '') {
        $errors['sender_name'] = 'Full name is required.';
    } elseif ($length($senderName) > 150) {
        $errors['sender_name'] = 'Full name is too long.';
    }

    if ($senderEmail === '' || filter_var($senderEmail, FILTER_VALIDATE_EMAIL) === false) {
        $errors['sender_email'] = 'Please enter a valid email address.';
    } elseif ($length($senderEmail) > 255) {
        $errors['sender_email'] = 'Email address is too long.';
    }

    if ($subject === '') {
        $errors['subject'] = 'Subject is required.';
    } elseif ($length($subject) > 250) {
        $errors['subject'] = 'Subject is too long.';
    }

    if ($messageBody === '') {
        $errors['message_body'] = 'Message is required.';
    } elseif ($length($messageBody) > 4000) {
        $errors['message_body'] = 'Message is too long.';
    }

    if ($errors !== []) {
        messageAjaxResponse(['success' => false, 'message' => 'Please correct the highlighted fields.', 'data' => new stdClass(), 'errors' => $errors], 422);
    }

    try {
        $statement = $pdo->prepare(
            'INSERT INTO contact_messages
                (sender_name, sender_email, subject, message_body, status, read_at, replied_at, ip_address, user_agent)
             VALUES
                (:sender_name, :sender_email, :subject, :message_body, :status, NULL, NULL, :ip_address, :user_agent)'
        );
        $statement->execute([
            ':sender_name' => $senderName,
            ':sender_email' => $senderEmail,
            ':subject' => $subject !== '' ? $subject : null,
            ':message_body' => $messageBody,
            ':status' => 'new',
            ':ip_address' => messageIpToBinary($_SERVER['REMOTE_ADDR'] ?? null),
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000) ?: null,
        ]);

        messageAjaxResponse([
            'success' => true,
            'message' => 'Your message has been sent successfully. We will get back to you soon.',
            'data' => ['contact_message_id' => (int) $pdo->lastInsertId()],
            'errors' => new stdClass(),
        ], 201);
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        messageAjaxResponse(['success' => false, 'message' => 'Unable to send your message right now.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
    }
}

if (!isLoggedIn()) {
    messageAjaxResponse(['success' => false, 'message' => 'Authentication required.', 'data' => new stdClass(), 'errors' => new stdClass()], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    messageAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
}

try {
    if ($action === 'show') {
        $messageId = trim((string) ($_GET['id'] ?? ''));
        if ($messageId === '' || !ctype_digit($messageId) || (int) $messageId < 1) {
            messageAjaxResponse(['success' => false, 'message' => 'Invalid message.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
        }

        $statement = $pdo->prepare(
            'SELECT contact_message_id,
                    sender_name,
                    sender_email,
                    subject,
                    message_body,
                    status,
                    read_at,
                    replied_at,
                    IFNULL(INET6_NTOA(ip_address), "") AS ip_address_text,
                    user_agent,
                    created_at,
                    updated_at
             FROM contact_messages
             WHERE contact_message_id = :contact_message_id
             LIMIT 1'
        );
        $statement->execute([':contact_message_id' => (int) $messageId]);
        $message = $statement->fetch(PDO::FETCH_ASSOC);

        if ($message === false) {
            messageAjaxResponse(['success' => false, 'message' => 'Message not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $payload = messageMetaArray($message);
        messageAjaxResponse(['success' => true, 'message' => 'Message loaded successfully.', 'data' => ['message' => $payload], 'errors' => new stdClass()]);
    }

    if ($action === 'toggle-status') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            messageAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 405);
        }

        $token = is_string($_POST[CSRF_TOKEN_NAME] ?? null) ? $_POST[CSRF_TOKEN_NAME] : null;
        if (!verifyCsrfToken($token)) {
            messageAjaxResponse(['success' => false, 'message' => 'Invalid request.', 'data' => new stdClass(), 'errors' => new stdClass()], 419);
        }

        $messageId = trim((string) ($_POST['contact_message_id'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? ''));
        if ($messageId === '' || !ctype_digit($messageId) || (int) $messageId < 1) {
            messageAjaxResponse(['success' => false, 'message' => 'Invalid message.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
        }

        if (!in_array($status, ['new', 'read'], true)) {
            messageAjaxResponse(['success' => false, 'message' => 'Invalid status.', 'data' => new stdClass(), 'errors' => new stdClass()], 422);
        }

        $current = $pdo->prepare('SELECT contact_message_id, status FROM contact_messages WHERE contact_message_id = :contact_message_id LIMIT 1');
        $current->execute([':contact_message_id' => (int) $messageId]);
        if ($current->fetchColumn() === false) {
            messageAjaxResponse(['success' => false, 'message' => 'Message not found.', 'data' => new stdClass(), 'errors' => new stdClass()], 404);
        }

        $statement = $pdo->prepare(
            'UPDATE contact_messages
             SET status = :status,
                 read_at = :read_at
             WHERE contact_message_id = :contact_message_id'
        );
        $statement->execute([
            ':status' => $status,
            ':read_at' => $status === 'read' ? date('Y-m-d H:i:s') : null,
            ':contact_message_id' => (int) $messageId,
        ]);

        messageAjaxResponse([
            'success' => true,
            'message' => $status === 'read' ? 'Message marked as read.' : 'Message marked as unread.',
            'data' => ['contact_message_id' => (int) $messageId, 'status' => $status],
            'errors' => new stdClass(),
        ]);
    }

    $statement = $pdo->query(
        'SELECT contact_message_id,
                sender_name,
                sender_email,
                subject,
                message_body,
                status,
                read_at,
                replied_at,
                IFNULL(INET6_NTOA(ip_address), "") AS ip_address_text,
                user_agent,
                created_at,
                updated_at
         FROM contact_messages
         ORDER BY created_at DESC, contact_message_id DESC'
    );
    $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

    $stats = ['total' => count($rows), 'unread' => 0, 'read' => 0, 'replied' => 0];
    foreach ($rows as &$row) {
        $row = messageMetaArray($row);
        $stats[$row['status']] = ($stats[$row['status']] ?? 0) + 1;
    }
    unset($row);

    messageAjaxResponse([
        'success' => true,
        'message' => 'Messages loaded successfully.',
        'data' => [
            'messages' => $rows,
            'stats' => $stats,
        ],
        'errors' => new stdClass(),
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    messageAjaxResponse(['success' => false, 'message' => 'Unable to load messages.', 'data' => new stdClass(), 'errors' => new stdClass()], 500);
}


