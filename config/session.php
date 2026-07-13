<?php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';

/**
 * Starts a securely configured PHP session when one is not already active.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    ini_set('session.gc_maxlifetime', '7200');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // Rotate the ID periodically to reduce session fixation risk.
    if (
        !isset($_SESSION['_last_regeneration'])
        || (time() - (int) $_SESSION['_last_regeneration']) >= 1800
    ) {
        regenerateSession();
    }
}

/**
 * Safely regenerates the active session ID.
 */
function regenerateSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        startSecureSession();
    }

    session_regenerate_id(true);
    $_SESSION['_last_regeneration'] = time();
}

/**
 * Clears all session data, removes the session cookie, and destroys the session.
 */
function destroySession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    $cookieParams = session_get_cookie_params();

    if (ini_get('session.use_cookies')) {
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $cookieParams['path'] ?? '/',
                'domain' => $cookieParams['domain'] ?? '',
                'secure' => (bool) ($cookieParams['secure'] ?? false),
                'httponly' => (bool) ($cookieParams['httponly'] ?? true),
                'samesite' => $cookieParams['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Returns whether the current session belongs to an authenticated user.
 */
function isLoggedIn(): bool
{
    startSecureSession();

    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}