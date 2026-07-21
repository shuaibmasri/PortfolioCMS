<?php

declare(strict_types=1);

/**
 * Escapes a value for safe HTML output.
 */
function escape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
        'UTF-8'
    );
}

/**
 * Redirects to a local or absolute URL and stops script execution.
 */
function redirect(string $location, int $statusCode = 302): void
{
    if (strpos($location, "\r") !== false || strpos($location, "\n") !== false) {
        throw new InvalidArgumentException('Invalid redirect location.');
    }

    if ($statusCode < 300 || $statusCode > 399) {
        throw new InvalidArgumentException('Redirect status code must be between 300 and 399.');
    }

    header('Location: ' . $location, true, $statusCode);
    exit;
}

/**
 * Returns the public URL for an asset.
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Returns consistent, safe attributes for responsive raster images. */
function responsiveImageAttributes(string $alt, string $loading = 'lazy'): string
{
    $loading = $loading === 'eager' ? 'eager' : 'lazy';

    return 'alt="' . escape($alt) . '" loading="' . $loading . '" decoding="async"';
}

/**
 * Resolves an application upload URL path to its filesystem location.
 *
 * Only files in the managed upload directories are accepted so database values
 * can never traverse outside the upload root when old files are replaced or deleted.
 */
function uploadFilePath(string $path): ?string
{
    $path = str_replace('\\', '/', trim($path));
    if (
        preg_match('#\Auploads/(profile|projects|certificates)/[a-f0-9]{32}\.(?:jpg|png|webp|ico|pdf)\z#i', $path) !== 1
    ) {
        return null;
    }

    return UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, substr($path, strlen('uploads/')));
}

/** Records one privacy-conscious public visitor session and page view. */
function trackPublicVisit(PDO $pdo, string $path): ?int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    $token = $_SESSION['_visitor_token'] ?? '';
    if (!is_string($token) || preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_visitor_token'] = $token;
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
    $statement = $pdo->prepare(
        'INSERT INTO visitor_sessions (session_token, last_activity_at, ip_hash, referrer_url, landing_path)
         VALUES (:token, NOW(), :ip_hash, :referrer, :path)
         ON DUPLICATE KEY UPDATE last_activity_at = NOW()'
    );
    $statement->execute([
        ':token' => $token,
        ':ip_hash' => $ipHash,
        ':referrer' => substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 1000) ?: null,
        ':path' => substr($path, 0, 500),
    ]);

    $lookup = $pdo->prepare('SELECT visitor_session_id FROM visitor_sessions WHERE session_token = :token LIMIT 1');
    $lookup->execute([':token' => $token]);
    $sessionId = (int) $lookup->fetchColumn();
    if ($sessionId > 0) {
        $view = $pdo->prepare('INSERT INTO page_views (visitor_session_id, page_path, referrer_url) VALUES (:session_id, :path, :referrer)');
        $view->execute([':session_id' => $sessionId, ':path' => substr($path, 0, 500), ':referrer' => substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 1000) ?: null]);
    }

    return $sessionId > 0 ? $sessionId : null;
}

/**
 * Builds an application URL from a relative path.
 */
function url(string $path = ''): string
{
    $configuredUrl = defined('APP_URL') ? APP_URL : '';
    $configuredParts = parse_url($configuredUrl);
    $basePath = isset($configuredParts['path']) ? rtrim($configuredParts['path'], '/') : '';

    /*
     * Keep the configured application path while using the active host and port.
     * This supports local servers such as localhost:8080 without requiring a
     * separate APP_URL value for every developer machine.
     */
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (
        is_string($host)
        && preg_match('/\A(?:[a-z0-9.-]+|\[[0-9a-f:]+\])(?::\d{1,5})?\z/i', $host) === 1
    ) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);
        $baseUrl = ($isHttps ? 'https' : 'http') . '://' . $host . $basePath;
    } else {
        $baseUrl = rtrim($configuredUrl, '/');
    }

    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

/**
 * Returns a previously submitted form value, safely escaped for HTML output.
 */
function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old_input'][$key] ?? $default;

    return escape(is_scalar($value) ? $value : $default);
}

/**
 * Creates or returns the current session CSRF token.
 */
function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (function_exists('startSecureSession')) {
            startSecureSession();
        } else {
            throw new RuntimeException('A secure session must be started before creating a CSRF token.');
        }
    }

    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verifies a submitted CSRF token against the token stored in the session.
 */
function verifyCsrfToken(?string $token): bool
{
    if (
        session_status() !== PHP_SESSION_ACTIVE
        || $token === null
        || empty($_SESSION[CSRF_TOKEN_NAME])
    ) {
        return false;
    }

    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Stores a one-time flash message in the session.
 */
function flashMessage(string $message, string $type = 'success'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (function_exists('startSecureSession')) {
            startSecureSession();
        } else {
            throw new RuntimeException('A secure session must be started before setting a flash message.');
        }
    }

    $_SESSION['_flash_message'] = [
        'message' => $message,
        'type' => $type,
    ];
}

/**
 * Retrieves and removes the current one-time flash message.
 *
 * @return array{message: string, type: string}|null
 */
function getFlashMessage(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['_flash_message'])) {
        return null;
    }

    $flashMessage = $_SESSION['_flash_message'];
    unset($_SESSION['_flash_message']);

    return $flashMessage;
}
