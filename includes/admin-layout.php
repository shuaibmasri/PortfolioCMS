<?php

declare(strict_types=1);

if (!isset($pageContent) || !is_string($pageContent)) {
    throw new RuntimeException('Admin pages must provide page content before loading the layout.');
}

require __DIR__ . '/admin-header.php';
require __DIR__ . '/admin-sidebar.php';
?>
<div class="admin-main">
    <?php require __DIR__ . '/admin-topbar.php'; ?>
    <main class="admin-page-content">
        <?= $pageContent ?>
    </main>
    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>
