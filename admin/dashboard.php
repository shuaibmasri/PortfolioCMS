<?php

declare(strict_types=1);

// This guard must remain at the top of every administrator page.
require_once dirname(__DIR__) . '/includes/auth.php';
requireAdmin();

$dashboardStats = [];
$visitorLabels = [];
$visitorValues = [];
$recentActivity = [];
try {
    $queries = [
        'visitors' => 'SELECT COUNT(*) FROM visitor_sessions',
        'today_visitors' => 'SELECT COUNT(*) FROM visitor_sessions WHERE started_at >= CURDATE()',
        'monthly_visitors' => 'SELECT COUNT(*) FROM visitor_sessions WHERE started_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")',
        'projects' => 'SELECT COUNT(*) FROM projects WHERE is_public = 1',
        'certificates' => 'SELECT COUNT(*) FROM certifications WHERE is_public = 1',
        'skills' => 'SELECT COUNT(*) FROM skills WHERE is_public = 1',
        'experience' => 'SELECT COUNT(*) FROM work_experiences WHERE is_public = 1',
        'education' => 'SELECT COUNT(*) FROM educations WHERE is_public = 1',
        'messages' => 'SELECT COUNT(*) FROM contact_messages WHERE status = "new"',
        'downloads' => 'SELECT COUNT(*) FROM download_events',
    ];
    foreach ($queries as $key => $sql) { $dashboardStats[$key] = (int) $pdo->query($sql)->fetchColumn(); }

    $visitorStatement = $pdo->query('SELECT DATE(started_at) AS visit_date, COUNT(*) AS visitor_count FROM visitor_sessions WHERE started_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(started_at)');
    $visitorCounts = [];
    foreach ($visitorStatement->fetchAll(PDO::FETCH_ASSOC) as $row) { $visitorCounts[(string) $row['visit_date']] = (int) $row['visitor_count']; }
    for ($dayOffset = 6; $dayOffset >= 0; $dayOffset--) {
        $date = new DateTimeImmutable('-' . $dayOffset . ' days');
        $visitorLabels[] = $date->format('M j');
        $visitorValues[] = $visitorCounts[$date->format('Y-m-d')] ?? 0;
    }

    $activityStatement = $pdo->query('SELECT activity_type, activity_label, occurred_at FROM (SELECT "message" AS activity_type, CONCAT("New message from ", sender_name) AS activity_label, created_at AS occurred_at FROM contact_messages WHERE status = "new" UNION ALL SELECT "download" AS activity_type, "CV downloaded" AS activity_label, created_at AS occurred_at FROM download_events UNION ALL SELECT "visitor" AS activity_type, "New portfolio visitor" AS activity_label, started_at AS occurred_at FROM visitor_sessions) AS activity_feed ORDER BY occurred_at DESC LIMIT 5');
    $recentActivity = $activityStatement->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $exception) { error_log($exception->getMessage()); }

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$breadcrumbs = [['label' => 'Dashboard']];

ob_start();
?>
<section class="row g-3 g-xl-4 mb-4" aria-label="Portfolio overview">
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-primary-subtle text-primary"><i class="fa fa-eye"></i></span><div><p class="stat-card__label">Total visitors</p><p class="stat-card__value"><?= number_format($dashboardStats['visitors'] ?? 0) ?></p><span class="text-muted small"><?= number_format($dashboardStats['today_visitors'] ?? 0) ?> today</span></div></article></div>
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-success-subtle text-success"><i class="fa fa-folder-open"></i></span><div><p class="stat-card__label">Published projects</p><p class="stat-card__value"><?= number_format($dashboardStats['projects'] ?? 0) ?></p><span class="text-muted small">Portfolio showcase</span></div></article></div>
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-warning-subtle text-warning"><i class="fa fa-envelope"></i></span><div><p class="stat-card__label">New messages</p><p class="stat-card__value"><?= number_format($dashboardStats['messages'] ?? 0) ?></p><span class="text-muted small">Awaiting response</span></div></article></div>
    <div class="col-12 col-sm-6 col-xl-3"><article class="stat-card h-100 d-flex align-items-center gap-3"><span class="stat-card__icon bg-info-subtle text-info"><i class="fa fa-download"></i></span><div><p class="stat-card__label">CV downloads</p><p class="stat-card__value"><?= number_format($dashboardStats['downloads'] ?? 0) ?></p><span class="text-muted small"><?= number_format($dashboardStats['monthly_visitors'] ?? 0) ?> monthly visitors</span></div></article></div>
</section>
<section class="row g-3 g-xl-4">
    <div class="col-lg-8"><article class="dashboard-panel h-100"><div class="dashboard-panel__header"><div><h2>Visitor overview</h2><p>Unique visitors for the last seven days</p></div><span class="badge rounded-pill text-bg-light">This week</span></div><div class="chart-wrap"><canvas id="visitorChart" aria-label="Visitor activity chart" role="img" data-labels='<?= escape(json_encode($visitorLabels) ?: '[]') ?>' data-values='<?= escape(json_encode($visitorValues) ?: '[]') ?>'></canvas></div></article></div>
    <div class="col-lg-4"><article class="dashboard-panel h-100"><div class="dashboard-panel__header"><div><h2>Content totals</h2><p>Published portfolio records</p></div></div><div class="p-4 small"><div class="d-flex justify-content-between mb-2"><span>Certificates</span><strong><?= number_format($dashboardStats['certificates'] ?? 0) ?></strong></div><div class="d-flex justify-content-between mb-2"><span>Skills</span><strong><?= number_format($dashboardStats['skills'] ?? 0) ?></strong></div><div class="d-flex justify-content-between mb-2"><span>Experience</span><strong><?= number_format($dashboardStats['experience'] ?? 0) ?></strong></div><div class="d-flex justify-content-between"><span>Education</span><strong><?= number_format($dashboardStats['education'] ?? 0) ?></strong></div></div></article></div>
    <div class="col-lg-7"><article class="dashboard-panel"><div class="dashboard-panel__header"><div><h2>Recent activity</h2><p>Latest recorded portfolio events</p></div><a href="<?= escape(url('admin/messages/index.php')) ?>" class="small text-decoration-none">View messages</a></div><div class="pb-2"><?php if ($recentActivity === []): ?><div class="activity-item"><div><strong>No activity recorded yet</strong><small>Visits, downloads, and messages will appear here.</small></div></div><?php else: foreach ($recentActivity as $activity): ?><div class="activity-item"><span class="activity-dot<?= ($activity['activity_type'] ?? '') === 'download' ? ' bg-success' : (($activity['activity_type'] ?? '') === 'message' ? ' bg-warning' : '') ?>"></span><div><strong><?= escape((string) ($activity['activity_label'] ?? 'Portfolio activity')) ?></strong><small><?= escape(date('M j, Y g:i A', strtotime((string) ($activity['occurred_at'] ?? 'now')))) ?></small></div></div><?php endforeach; endif; ?></div></article></div>
    <div class="col-lg-5"><article class="dashboard-panel"><div class="dashboard-panel__header"><div><h2>Portfolio status</h2><p>Published content summary</p></div></div><div class="px-4 pb-4"><div class="d-flex justify-content-between small mb-2"><span>Public content</span><strong><?= number_format($dashboardStats['projects'] ?? 0) ?> projects, <?= number_format($dashboardStats['certificates'] ?? 0) ?> certificates</strong></div><div class="d-flex justify-content-between border-top pt-3 small"><span class="text-muted">Today&apos;s visitors</span><strong><?= number_format($dashboardStats['today_visitors'] ?? 0) ?></strong></div></div></article></div>
</section>
<?php
$pageContent = (string) ob_get_clean();

require dirname(__DIR__) . '/includes/admin-layout.php';
