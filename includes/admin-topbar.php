<?php

declare(strict_types=1);

$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [['label' => 'Dashboard']];
$administratorName = (string) ($_SESSION['user_name'] ?? 'Administrator');
?>
<header class="admin-topbar sticky-top">
    <div class="d-flex align-items-center gap-2">
        <button class="topbar-icon-button" type="button" data-sidebar-toggle aria-label="Open navigation" aria-controls="adminSidebar"><i class="fa fa-bars" aria-hidden="true"></i></button>
        <div class="d-none d-sm-block"><p class="topbar-greeting mb-0">Welcome back, <?= escape($administratorName) ?></p><small class="text-muted">Manage your portfolio from one place.</small></div>
    </div>
    <div class="d-flex align-items-center gap-2 gap-sm-3">
        <div class="dropdown">
            <button class="topbar-icon-button position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications"><i class="fa fa-bell-o" aria-hidden="true"></i><span class="notification-dot"></span></button>
            <div class="dropdown-menu dropdown-menu-end notification-menu shadow border-0 p-0">
                <div class="notification-menu__header"><strong>Notifications</strong><span class="badge text-bg-primary rounded-pill">3</span></div>
                <div class="notification-item"><span class="notification-icon bg-primary-subtle text-primary"><i class="fa fa-envelope-o"></i></span><div><strong>New message</strong><small>Review your latest inquiry</small></div></div>
                <div class="notification-item"><span class="notification-icon bg-success-subtle text-success"><i class="fa fa-eye"></i></span><div><strong>Visitor activity</strong><small>Portfolio views are increasing</small></div></div>
                <div class="notification-item"><span class="notification-icon bg-warning-subtle text-warning"><i class="fa fa-clock-o"></i></span><div><strong>Content reminder</strong><small>Keep your projects current</small></div></div>
                <a class="notification-menu__footer" href="#" aria-disabled="true">View all notifications</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="user-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false"><span class="user-menu__avatar"><?= escape(strtoupper(substr($administratorName, 0, 1))) ?></span><span class="d-none d-md-inline text-start"><strong><?= escape($administratorName) ?></strong><small>Administrator</small></span><i class="fa fa-angle-down d-none d-md-inline" aria-hidden="true"></i></button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                <li><a class="dropdown-item" href="#" aria-disabled="true"><i class="fa fa-user me-2 text-muted"></i>My profile</a></li>
                <li><a class="dropdown-item" href="<?= escape(url('admin/reset-password.php')) ?>"><i class="fa fa-lock me-2 text-muted"></i>Change password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><form method="post" action="<?= escape(url('admin/logout.php')) ?>"><input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>"><button class="dropdown-item text-danger" type="submit"><i class="fa fa-sign-out me-2"></i>Logout</button></form></li>
            </ul>
        </div>
    </div>
</header>
<div class="admin-content">
    <div class="admin-page-heading d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div><h1 class="h3 mb-1 fw-bold"><?= escape((string) ($pageTitle ?? 'Dashboard')) ?></h1><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><?php foreach ($breadcrumbs as $index => $breadcrumb): ?><li class="breadcrumb-item<?= $index === array_key_last($breadcrumbs) ? ' active' : '' ?>"<?= $index === array_key_last($breadcrumbs) ? ' aria-current="page"' : '' ?>><?= escape((string) ($breadcrumb['label'] ?? '')) ?></li><?php endforeach; ?></ol></nav></div>
        <?php if (isset($pageAction) && is_string($pageAction)): ?><?= $pageAction ?><?php endif; ?>
    </div>
