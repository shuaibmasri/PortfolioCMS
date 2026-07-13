<?php

declare(strict_types=1);

// This guard must remain at the top of every administrator page.
require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];

ob_start();
?>
<section class="row g-3 g-xl-4 mb-4" aria-label="Portfolio overview">
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-primary-subtle text-primary"><i class="fa fa-eye"></i></span><div><p class="stat-card__label">Total visitors</p><p class="stat-card__value">12,480</p><span class="stat-card__trend"><i class="fa fa-arrow-up"></i> 12.5% this month</span></div></article></div>
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-success-subtle text-success"><i class="fa fa-folder-open"></i></span><div><p class="stat-card__label">Published projects</p><p class="stat-card__value">24</p><span class="text-muted small">Portfolio showcase</span></div></article></div>
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-warning-subtle text-warning"><i class="fa fa-envelope"></i></span><div><p class="stat-card__label">New messages</p><p class="stat-card__value">8</p><span class="text-muted small">Awaiting response</span></div></article></div>
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-info-subtle text-info"><i class="fa fa-download"></i></span><div><p class="stat-card__label">CV downloads</p><p class="stat-card__value">326</p><span class="stat-card__trend"><i class="fa fa-arrow-up"></i> 8.2% this month</span></div></article></div>
</section>
<section class="row g-3 g-xl-4">
    <div class="col-lg-8"><article class="dashboard-panel h-100"><div class="dashboard-panel__header"><div><h2>Visitor overview</h2><p>Placeholder activity for the last seven days</p></div><span class="badge rounded-pill text-bg-light">This week</span></div><div class="chart-wrap"><canvas id="visitorChart" aria-label="Visitor activity chart" role="img"></canvas></div></article></div>
    <div class="col-lg-4"><article class="dashboard-panel h-100"><div class="dashboard-panel__header"><div><h2>Quick access</h2><p>Common administrator actions</p></div></div><div class="py-2"><a class="quick-action" href="#" aria-disabled="true"><i class="fa fa-user"></i><span>Update profile</span><i class="fa fa-angle-right ms-auto text-muted"></i></a><a class="quick-action" href="#" aria-disabled="true"><i class="fa fa-folder-open"></i><span>Manage projects</span><i class="fa fa-angle-right ms-auto text-muted"></i></a><a class="quick-action" href="#" aria-disabled="true"><i class="fa fa-envelope"></i><span>Review messages</span><i class="fa fa-angle-right ms-auto text-muted"></i></a><a class="quick-action" href="<?= escape(url('admin/reset-password.php')) ?>"><i class="fa fa-lock"></i><span>Change password</span><i class="fa fa-angle-right ms-auto text-muted"></i></a></div></article></div>
    <div class="col-lg-7"><article class="dashboard-panel"><div class="dashboard-panel__header"><div><h2>Recent activity</h2><p>Placeholder events from your workspace</p></div><a href="#" class="small text-decoration-none" aria-disabled="true">View all</a></div><div class="pb-2"><div class="activity-item"><span class="activity-dot"></span><div><strong>New portfolio message received</strong><small>10 minutes ago</small></div></div><div class="activity-item"><span class="activity-dot bg-success"></span><div><strong>Project “Brand Identity” was viewed</strong><small>2 hours ago</small></div></div><div class="activity-item"><span class="activity-dot bg-warning"></span><div><strong>Time to review your profile content</strong><small>Yesterday</small></div></div></div></article></div>
    <div class="col-lg-5"><article class="dashboard-panel"><div class="dashboard-panel__header"><div><h2>Portfolio status</h2><p>Placeholder publication summary</p></div></div><div class="px-4 pb-4"><div class="d-flex justify-content-between small mb-2"><span>Profile completion</span><strong>85%</strong></div><div class="progress mb-4" style="height:.55rem"><div class="progress-bar" style="width:85%"></div></div><div class="d-flex justify-content-between border-top pt-3 small"><span class="text-muted">Public sections</span><strong>9 of 12 enabled</strong></div></div></article></div>
</section>
<?php
$pageContent = (string) ob_get_clean();

require dirname(__DIR__) . '/includes/admin-layout.php';
