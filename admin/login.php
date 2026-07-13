<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

redirectIfAuthenticated();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = isset($_POST[CSRF_TOKEN_NAME]) ? (string) $_POST[CSRF_TOKEN_NAME] : null;

    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Your form session has expired. Please try again.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($password === '' || strlen($password) > 4096) {
        $errors[] = 'Enter your password.';
    }

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'SELECT user_id, email, password_hash, full_name, is_active
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            // A fallback hash keeps the verification path consistent for unknown emails.
            $hash = is_array($user) ? (string) $user['password_hash'] : '$2y$10$zY7Yv1VIKpI9cE4hUE9HHuYB6S7SrNG.r66R7C9eAqslGfEnP2TJe';
            $passwordMatches = password_verify($password, $hash);

            if (!is_array($user) || (int) $user['is_active'] !== 1 || !$passwordMatches) {
                $errors[] = 'Invalid email or password.';
            } else {
                loginAdministrator($user);

                $update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE user_id = :user_id');
                $update->execute(['user_id' => (int) $user['user_id']]);

                redirect(url('admin/dashboard.php'));
            }
        } catch (Throwable $exception) {
            error_log('Administrator login failed: ' . $exception->getMessage());
            $errors[] = 'Unable to sign in at this time. Please try again later.';
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
    <title>Administrator sign in | <?= escape(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= escape(asset('lib/bootstrap/dist/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('fonts/font-awesome.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('css/auth.css')) ?>">
</head>
<body class="auth-page d-flex align-items-center py-4">
    <main class="container">
        <div class="auth-shell mx-auto">
        <div class="card auth-card">
            <div class="auth-card__body">
                <div class="text-center mb-4"><div class="auth-mark"><i class="fa fa-lock" aria-hidden="true"></i></div><h1 class="h3 fw-bold mb-1">Administrator sign in</h1><p class="text-secondary mb-0">Access the <?= escape(APP_NAME) ?> dashboard.</p></div>
                <?php if ($flash !== null): ?><div class="alert alert-<?= escape(in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info') ?>" role="alert"><?= escape($flash['message']) ?></div><?php endif; ?>
                <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><ul class="mb-0 ps-3"><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <form method="post" action="<?= escape(url('admin/login.php')) ?>" novalidate data-submit-lock>
                    <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                    <div class="mb-3"><label for="email" class="form-label">Email address</label><div class="input-group"><span class="input-group-text"><i class="fa fa-envelope" aria-hidden="true"></i></span><input class="form-control<?= $errors !== [] ? ' is-invalid' : '' ?>" type="email" id="email" name="email" value="<?= escape($email) ?>" autocomplete="username" maxlength="255" required autofocus></div></div>
                    <div class="mb-4"><label for="password" class="form-label">Password</label><div class="input-group"><span class="input-group-text"><i class="fa fa-key" aria-hidden="true"></i></span><input class="form-control<?= $errors !== [] ? ' is-invalid' : '' ?>" type="password" id="password" name="password" autocomplete="current-password" maxlength="4096" required><button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password"><i class="fa fa-eye" aria-hidden="true"></i></button></div></div>
                    <button class="btn btn-primary w-100 py-2" type="submit"><i class="fa fa-sign-in me-2" aria-hidden="true"></i>Sign in</button>
                </form>
            </div>
        </div>
        <p class="auth-footer text-center mt-4 mb-0"><i class="fa fa-shield me-1" aria-hidden="true"></i>Secure administrator access</p>
        </div>
    </main>
    <script src="<?= escape(asset('lib/bootstrap/dist/js/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= escape(asset('js/auth.js')) ?>"></script>
</body>
</html>
