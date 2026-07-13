<?php

declare(strict_types=1);

$pageTitle = isset($pageTitle) && trim((string) $pageTitle) !== ''
    ? trim((string) $pageTitle) . ' | ' . APP_NAME
    : APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?></title>

    <link rel="stylesheet" href="<?= escape(asset('lib/bootstrap/dist/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('fonts/font-awesome.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(assets('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= escape(assets('css/responsive.css')) ?>">
</head>
<body>
