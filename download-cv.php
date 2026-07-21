<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$profile = $pdo->query('SELECT profile_id FROM profiles WHERE is_public = 1 ORDER BY profile_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$profileId = (int) ($profile['profile_id'] ?? 0);
$file = null;
if ($profileId > 0) {
    $statement = $pdo->prepare(
        'SELECT cv_file_id, storage_path, mime_type
         FROM cv_files WHERE profile_id = :profile_id AND is_active = 1
         ORDER BY updated_at DESC, cv_file_id DESC LIMIT 1'
    );
    $statement->execute([':profile_id' => $profileId]);
    $file = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
}

$path = $file !== null ? str_replace('\\', '/', trim((string) $file['storage_path'])) : '';
$root = realpath(__DIR__);
$candidate = $path !== '' ? realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim($path, '/')) : false;
if ($candidate !== false && $root !== false && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    try {
        $sessionId = trackPublicVisit($pdo, 'download-cv.php');
        $event = $pdo->prepare('INSERT INTO download_events (cv_file_id, visitor_session_id, download_type, source_path, ip_hash, user_agent) VALUES (:cv_file_id, :session_id, :type, :path, :ip_hash, :user_agent)');
        $event->execute([
            ':cv_file_id' => (int) $file['cv_file_id'], ':session_id' => $sessionId, ':type' => 'uploaded_pdf', ':path' => $path,
            ':ip_hash' => !empty($_SERVER['REMOTE_ADDR']) ? hash('sha256', (string) $_SERVER['REMOTE_ADDR']) : null,
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000) ?: null,
        ]);
    } catch (Throwable $exception) { error_log($exception->getMessage()); }
    header('Content-Type: application/pdf');
    header('Content-Length: ' . (string) filesize($candidate));
    header('Content-Disposition: attachment; filename="Shuaib-Al-Masri-CV.pdf"');
    header('X-Content-Type-Options: nosniff');
    readfile($candidate);
    exit;
}

redirect(url('resume.php?download=1'));
