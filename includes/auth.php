<?php

declare(strict_types=1);

/**
 * Authentication middleware and helpers for the administrator area.
 *
 * Pages that use this file receive the configured PDO instance, secure session,
 * CSRF helpers, and shared redirect behaviour from config/config.php.
 */
require_once dirname(__DIR__) . '/config/config.php';

/**
 * Redirects authenticated administrators away from guest-only pages.
 */
function redirectIfAuthenticated(): void
{
    if (isLoggedIn()) {
        redirect(url('admin/dashboard.php'));
    }
}

/**
 * Protects an administrator page and redirects guests to the login form.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        flashMessage('Please sign in to continue.', 'warning');
        redirect(url('admin/login.php'));
    }
}

/**
 * Returns the authenticated administrator ID, or throws when middleware was missed.
 */
function authenticatedUserId(): int
{
    requireAdmin();

    return (int) $_SESSION['user_id'];
}

/**
 * Establishes an authenticated session after credentials have been verified.
 * The session ID and CSRF token are both rotated to prevent fixation/reuse.
 */
function loginAdministrator(array $user): void
{
    regenerateSession();

    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_name'] = (string) $user['full_name'];
    $_SESSION['authenticated_at'] = time();
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

/**
 * Removes transient form input so it cannot be displayed after a redirect.
 */
function clearOldInput(): void
{
    unset($_SESSION['_old_input']);
}

/**
 * Validates the password policy used for administrator passwords.
 *
 * @return list<string>
 */
function passwordValidationErrors(string $password, string $confirmation = ''): array
{
    $errors = [];
    $length = strlen($password);

    if ($length < 12) {
        $errors[] = 'Your new password must be at least 12 characters long.';
    }

    if ($length > 4096) {
        $errors[] = 'Your new password is too long.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Your new password must include a lowercase letter.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Your new password must include an uppercase letter.';
    }

    if (!preg_match('/\d/', $password)) {
        $errors[] = 'Your new password must include a number.';
    }

    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
        $errors[] = 'Your new password must include a special character.';
    }

    if ($confirmation !== '' && !hash_equals($password, $confirmation)) {
        $errors[] = 'The new password confirmation does not match.';
    }

    return $errors;
}
