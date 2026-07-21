<?php

declare(strict_types=1);

$ownerName = trim((string) (($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')));
$footerText = $ownerName !== '' ? $ownerName : 'Professional Portfolio';
$copyrightText = '© ' . date('Y') . ' ' . $footerText . '. All Rights Reserved.';
$socialLinks = isset($socialLinks) && is_array($socialLinks) ? $socialLinks : [];
?>
<footer class="public-footer">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-4 text-center text-md-start">
                <div class="public-footer__brand"><?= escape($footerText) ?></div>
                <small><?= escape($copyrightText) ?></small>
            </div>

            <div class="col-md-4 text-center">
                <div class="public-socials">
                    <?php foreach ($socialLinks as $link): ?>
                        <?php if (empty($link['url'])) { continue; } ?>
                        <a href="<?= escape((string) $link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= escape((string) $link['label']) ?>">
                            <i class="fa <?= escape((string) $link['icon']) ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-4 text-center text-md-end">
                <a class="btn btn-outline-light btn-sm" href="#home">
                    <i class="fa fa-arrow-up me-1"></i>Back to top
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= escape(asset('lib/bootstrap/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<?php if (isset($pageScripts) && is_string($pageScripts) && trim($pageScripts) !== ''): ?>
<?= $pageScripts ?>
<?php endif; ?>
</div>
</body>
</html>
