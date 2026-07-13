<?php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once dirname(__DIR__) . '/includes/functions.php';

/*
|--------------------------------------------------------------------------
| Runtime Configuration
|--------------------------------------------------------------------------
*/

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

/*
|--------------------------------------------------------------------------
| Application Services
|--------------------------------------------------------------------------
| Load the database before the session so the PDO instance is available
| application-wide through $GLOBALS['pdo'].
*/

$pdo = require __DIR__ . '/database.php';
$GLOBALS['pdo'] = $pdo;

require_once __DIR__ . '/session.php';

startSecureSession();
