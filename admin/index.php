<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(url('admin/dashboard.php'));
}

redirect(url('admin/login.php'));
