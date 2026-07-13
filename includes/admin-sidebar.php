<?php

declare(strict_types=1);

$activeMenu = $activeMenu ?? 'dashboard';
$menuItems = [
    'dashboard' => ['Dashboard', 'fa-dashboard', url('admin/dashboard.php')],
    'profile' => ['Profile', 'fa-user', '#'],
    'skills' => ['Skills', 'fa-code', '#'],
    'languages' => ['Languages', 'fa-language', '#'],
    'experience' => ['Experience', 'fa-briefcase', '#'],
    'education' => ['Education', 'fa-graduation-cap', '#'],
    'projects' => ['Projects', 'fa-folder-open', '#'],
    'certificates' => ['Certificates', 'fa-certificate', '#'],
    'achievements' => ['Achievements', 'fa-trophy', '#'],
    'services' => ['Services', 'fa-cubes', '#'],
    'testimonials' => ['Testimonials', 'fa-quote-left', '#'],
    'social-links' => ['Social Links', 'fa-share-alt', '#'],
    'cv-manager' => ['CV Manager', 'fa-file-pdf-o', '#'],
    'messages' => ['Messages', 'fa-envelope', '#'],
    'visitor-statistics' => ['Visitor Statistics', 'fa-line-chart', '#'],
    'theme-settings' => ['Theme Settings', 'fa-paint-brush', '#'],
    'website-settings' => ['Website Settings', 'fa-cog', '#'],
];
?>
<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">
        <a href="<?= escape(url('admin/dashboard.php')) ?>" class="sidebar-brand__link">
            <span class="sidebar-brand__mark"><i class="fa fa-briefcase" aria-hidden="true"></i></span>
            <span class="sidebar-brand__name">Portfolio<span>CMS</span></span>
        </a>
        <button class="sidebar-close d-lg-none" type="button" data-sidebar-close aria-label="Close navigation"><i class="fa fa-times"></i></button>
    </div>
    <div class="sidebar-user">
        <span class="sidebar-user__avatar" aria-hidden="true"><?= escape(strtoupper(substr((string) ($_SESSION['user_name'] ?? 'A'), 0, 1))) ?></span>
        <div class="sidebar-user__details"><strong><?= escape((string) ($_SESSION['user_name'] ?? 'Administrator')) ?></strong><small>Administrator</small></div>
    </div>
    <nav class="sidebar-nav">
        <p class="sidebar-label">Workspace</p>
        <ul class="sidebar-menu list-unstyled mb-0">
            <?php foreach ($menuItems as $key => [$label, $icon, $href]): ?>
                <li><a class="<?= $activeMenu === $key ? 'is-active' : '' ?>" href="<?= escape($href) ?>"<?= $href === '#' ? ' aria-disabled="true" title="Available when this module is added"' : '' ?>><i class="fa <?= escape($icon) ?>" aria-hidden="true"></i><span><?= escape($label) ?></span></a></li>
            <?php endforeach; ?>
        </ul>
        <p class="sidebar-label mt-4">Account</p>
        <ul class="sidebar-menu list-unstyled mb-0">
            <li><a class="<?= $activeMenu === 'change-password' ? 'is-active' : '' ?>" href="<?= escape(url('admin/reset-password.php')) ?>"><i class="fa fa-lock" aria-hidden="true"></i><span>Change Password</span></a></li>
            <li>
                <form method="post" action="<?= escape(url('admin/logout.php')) ?>" class="sidebar-logout-form">
                    <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
                    <button type="submit"><i class="fa fa-sign-out" aria-hidden="true"></i><span>Logout</span></button>
                </form>
            </li>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay" data-sidebar-close></div>
