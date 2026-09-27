<?php

use Dotenv\Dotenv;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

$dotenv = Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

if (!defined('APP_URL')) {
    define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/'));
}
if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
}
if (!defined('VIEW_PATH')) {
    define('VIEW_PATH', BASE_PATH . '/views');
}
if (!defined('ASSET_URL')) {
    define('ASSET_URL', APP_URL . '/assets');
}