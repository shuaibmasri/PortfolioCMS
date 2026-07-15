<?php

declare(strict_types=1);
?>
    <footer class="admin-footer"><span>&copy; <?= date('Y') ?> <?= escape(APP_NAME) ?>.</span><span>Administrator workspace</span></footer>
    </div>
</div>
<script src="<?= escape(asset('lib/jquery/dist/jquery.min.js')) ?>"></script>
<script src="<?= escape(asset('lib/bootstrap/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= escape(asset('js/chart.min.js')) ?>"></script>
<script src="<?= escape(asset('js/admin.js')) ?>"></script>
<?php if (isset($pageScripts) && is_string($pageScripts)): ?><?= $pageScripts ?><?php endif; ?>
</body>
</html>
