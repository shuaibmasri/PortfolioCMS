<?php

declare(strict_types=1);

$adminPageTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' | ' . APP_NAME
    : APP_NAME . ' Administrator';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= escape($adminPageTitle) ?></title>
    <link rel="stylesheet" href="<?= escape(asset('lib/bootstrap/dist/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('fonts/font-awesome.min.css')) ?>">
    <link rel="stylesheet" href="<?= escape(asset('css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
