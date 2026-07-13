<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application Configuration
|--------------------------------------------------------------------------
*/

define('APP_NAME', 'Portfolio CMS');
define('APP_VERSION', '1.0.0');
// Production-safe by default; set APP_ENV=development only in a local environment.
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', 'http://localhost/PortfolioCMS');
define('TIMEZONE', 'Asia/Aden');

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| Store production credentials in environment variables or a secure secrets
| manager. These defaults are suitable for a local XAMPP development setup.
*/

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'portfolio_cms');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/*
|--------------------------------------------------------------------------
| Security Configuration
|--------------------------------------------------------------------------
*/

define('SESSION_NAME', 'portfolio_cms_session');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_ALGO', PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| Upload Configuration
|--------------------------------------------------------------------------
*/

define('UPLOAD_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

date_default_timezone_set(TIMEZONE);
