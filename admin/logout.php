<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect(url('admin/login.php'));
}

$csrfToken = isset($_POST[CSRF_TOKEN_NAME]) && is_string($_POST[CSRF_TOKEN_NAME])
    ? $_POST[CSRF_TOKEN_NAME]
    : null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($csrfToken)) {
    flashMessage('Your logout request could not be verified. Please try again.', 'warning');
    redirect(url('admin/dashboard.php'));
}

destroySession();

startSecureSession();
flashMessage('You have been signed out.', 'success');
redirect(url('admin/login.php'));
