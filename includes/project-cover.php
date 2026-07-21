<?php

declare(strict_types=1);

/**
 * Render a designed project cover when a project does not have an uploaded image.
 * This keeps every project visually complete while allowing media to remain optional.
 *
 * @param list<string> $technologies
 */
function projectCoverIcon(string $title, string $category, array $technologies): string
{
    $context = strtolower($title . ' ' . $category . ' ' . implode(' ', $technologies));
    $icons = [
        'hospital|health|clinic|medical' => 'fa-heartbeat',
        'erp|inventory|accounting|finance' => 'fa-line-chart',
        'human resources|\bhr\b|recruit' => 'fa-users',
        'api|integration|backend' => 'fa-exchange',
        'flutter|mobile|android|ios' => 'fa-mobile',
        'shop|ecommerce|store|market' => 'fa-shopping-cart',
        'education|school|course|learning' => 'fa-graduation-cap',
        'security|auth|cyber' => 'fa-shield',
        'dashboard|analytics|report' => 'fa-bar-chart',
    ];

    foreach ($icons as $pattern => $icon) {
        if (preg_match('/' . $pattern . '/i', $context) === 1) {
            return $icon;
        }
    }

    return 'fa-code';
}

/** @param list<string> $technologies */
function projectCoverTagline(string $title, string $category, array $technologies): string
{
    $taglines = [
        'fa-heartbeat' => 'Care, connected',
        'fa-line-chart' => 'Clarity at scale',
        'fa-users' => 'People, in sync',
        'fa-exchange' => 'Systems in motion',
        'fa-mobile' => 'Built for every screen',
        'fa-shopping-cart' => 'Commerce, refined',
        'fa-graduation-cap' => 'Learning, elevated',
        'fa-shield' => 'Trust by design',
        'fa-bar-chart' => 'Insights in focus',
    ];

    $icon = projectCoverIcon($title, $category, $technologies);
    return $taglines[$icon] ?? 'Crafted with purpose';
}

/**
 * @param list<string> $technologies
 */
function renderProjectCover(string $title, string $category, array $technologies, string $className = ''): string
{
    ob_start();
    ?>
    <div class="project-cover <?= escape($className) ?>" aria-label="<?= escape($title) ?> project cover">
        <div class="project-cover__glow project-cover__glow--one"></div>
        <div class="project-cover__glow project-cover__glow--two"></div>
        <i class="fa <?= escape(projectCoverIcon($title, $category, $technologies)) ?> project-cover__icon" aria-hidden="true"></i>
        <div class="project-cover__content">
            <span class="project-cover__tagline"><?= escape(projectCoverTagline($title, $category, $technologies)) ?></span>
        </div>
    </div>
    <?php
    return trim((string) ob_get_clean());
}
