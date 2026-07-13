<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $csrfToken = isset($_POST[CSRF_TOKEN_NAME]) ? (string) $_POST[CSRF_TOKEN_NAME] : null;

    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Your form session has expired. Please try again.';
    }

    if ($currentPassword === '' || strlen($currentPassword) > 4096) {
        $errors[] = 'Enter your current password.';
    }

    $errors = array_merge($errors, passwordValidationErrors($newPassword, $confirmPassword));

    if ($errors === []) {
        try {
            $statement = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = :user_id AND is_active = 1 LIMIT 1');
            $statement->execute(['user_id' => authenticatedUserId()]);
            $user = $statement->fetch();

            if (!is_array($user) || !password_verify($currentPassword, (string) $user['password_hash'])) {
                $errors[] = 'Your current password is incorrect.';
            } elseif (hash_equals($currentPassword, $newPassword)) {
                $errors[] = 'Your new password must be different from your current password.';
            } else {
                $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id');
                $update->execute([
                    'password_hash' => password_hash($newPassword, PASSWORD_ALGO),
                    'user_id' => authenticatedUserId(),
                ]);

                regenerateSession();
                $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
                flashMessage('Your password has been updated successfully.', 'success');
                redirect(url('admin/reset-password.php'));
            }
        } catch (Throwable $exception) {
            error_log('Administrator password reset failed: ' . $exception->getMessage());
            $errors[] = 'Unable to update your password at this time. Please try again later.';
        }
    }
}

$flash = getFlashMessage();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password | <?= escape(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= escape(asset('lib/bootstrap/dist/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('fonts/font-awesome.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('css/auth.css')) ?>">
</head>
<body class="password-page py-4 py-md-5">
    <main class="container">
        <div class="card password-card mx-auto">
            <div class="password-card__body">
                <div class="d-flex align-items-center mb-4"><div class="password-title-icon me-3"><i class="fa fa-shield fa-lg" aria-hidden="true"></i></div><div><h1 class="h3 fw-bold mb-1">Reset password</h1><p class="text-secondary mb-0">Choose a unique, strong password for your administrator account.</p></div></div>
                <?php if ($flash !== null): ?><div class="alert alert-<?= escape(in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info') ?>" role="alert"><?= escape($flash['message']) ?></div><?php endif; ?>
                <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <form method="post" action="<?= escape(url('admin/reset-password.php')) ?>" novalidate data-submit-lock>
                    <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                    <div class="mb-3"><label class="form-label" for="current_password">Current password</label><div class="input-group"><input class="form-control" type="password" id="current_password" name="current_password" autocomplete="current-password" maxlength="4096" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="current_password" aria-label="Show password"><i class="fa fa-eye" aria-hidden="true"></i></button></div></div>
                    <div class="mb-3"><label class="form-label" for="new_password">New password</label><div class="input-group"><input class="form-control" type="password" id="new_password" name="new_password" autocomplete="new-password" maxlength="4096" required aria-describedby="passwordHelp"><button class="btn btn-outline-secondary" type="button" data-password-toggle="new_password" aria-label="Show password"><i class="fa fa-eye" aria-hidden="true"></i></button></div><div id="passwordHelp" class="form-text">At least 12 characters, including uppercase, lowercase, number, and special character.</div></div>
                    <div class="mb-4"><label class="form-label" for="confirm_password">Confirm new password</label><div class="input-group"><input class="form-control" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" maxlength="4096" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="confirm_password" aria-label="Show password"><i class="fa fa-eye" aria-hidden="true"></i></button></div></div>
                    <div class="d-flex flex-column-reverse flex-sm-row justify-content-between gap-2"><a class="btn btn-outline-secondary" href="<?= escape(url('admin/dashboard.php')) ?>"><i class="fa fa-arrow-left me-1" aria-hidden="true"></i>Back to dashboard</a><button class="btn btn-primary" type="submit"><i class="fa fa-check me-1" aria-hidden="true"></i>Update password</button></div>
                </form>
            </div>
        </div>
    </main>
    <script src="<?= escape(asset('lib/bootstrap/dist/js/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= escape(asset('js/auth.js')) ?>"></script>
</body>
</html>
