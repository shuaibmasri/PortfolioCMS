<?php

declare(strict_types=1);

$publicPageTitle = isset($pageTitle) && trim((string) $pageTitle) !== ''
    ? trim((string) $pageTitle)
    : APP_NAME;
$publicPageDescription = isset($pageDescription) ? trim((string) $pageDescription) : '';
$publicPageKeywords = isset($pageKeywords) ? trim((string) $pageKeywords) : '';
$publicCanonicalUrl = isset($canonicalUrl) ? trim((string) $canonicalUrl) : url();
$publicLanguage = isset($htmlLanguage) && preg_match('/^[A-Za-z0-9_-]{2,10}$/', (string) $htmlLanguage) === 1
    ? (string) $htmlLanguage
    : 'en';
?>
<!DOCTYPE html>
<html lang="<?= escape($publicLanguage) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="<?= escape($robots ?? 'index,follow') ?>">
    <?php if ($publicPageDescription !== ''): ?><meta name="description" content="<?= escape($publicPageDescription) ?>"><?php endif; ?>
    <?php if ($publicPageKeywords !== ''): ?><meta name="keywords" content="<?= escape($publicPageKeywords) ?>"><?php endif; ?>
    <link rel="canonical" href="<?= escape($publicCanonicalUrl) ?>">
    <title><?= escape($publicPageTitle) ?></title>

    <link rel="stylesheet" href="<?= escape(asset('lib/bootstrap/dist/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('fonts/font-awesome.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('css/public.css')) ?>">
</head>
<body class="public-body">
<div class="public-page">
