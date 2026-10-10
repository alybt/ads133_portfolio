<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/config/config.php';

use App\Router\Router;
use App\Controllers\AuthController;
use App\Controllers\EducationController;
use App\Controllers\CertificateController;

$router = new Router();

// Homepage
$router->onGet('/', function () {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Online Resume</title>
        <link rel="stylesheet" href="<?= css('index') ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <?php view('components/Navbar'); ?>
        <?php view('features/user/homepage'); ?>
    </body>
    </html>
    <?php
});

// Authentication routes
$router->onGet('auth/login',   [AuthController::class, 'showLogin']);
$router->onPost('auth/login',  [AuthController::class, 'login']);
$router->onGet('login',        [AuthController::class, 'showLogin']);
$router->onPost('login',       [AuthController::class, 'login']);

$router->onPost('auth/logout', [AuthController::class, 'logout']);
$router->onGet('auth/logout',  [AuthController::class, 'logout']);
$router->onPost('logout',      [AuthController::class, 'logout']);
$router->onGet('logout',       [AuthController::class, 'logout']);

// Education routes (protected by Auth)
$router->onGet('educations',                     [EducationController::class, 'create']);
$router->onPost('educations',                    [EducationController::class, 'store']);
$router->onGet('educations/create',              [EducationController::class, 'create']);
$router->onPost('educations/delete',             [EducationController::class, 'delete']);
$router->onGet('views/features/educations/eduform.php', [EducationController::class, 'create']);
$router->onPost('views/features/educations/eduform.php', [EducationController::class, 'store']);
$router->onGet('features/educations/eduform',    [EducationController::class, 'create']);
$router->onPost('features/educations/eduform',   [EducationController::class, 'store']);

// Certificate routes (protected by Auth)
$router->onGet('certificates',                      [CertificateController::class, 'create']);
$router->onPost('certificates',                     [CertificateController::class, 'store']);
$router->onGet('certificates/create',               [CertificateController::class, 'create']);
$router->onPost('certificates/delete',              [CertificateController::class, 'delete']);
$router->onGet('views/features/certificates/certform.php',  [CertificateController::class, 'create']);
$router->onPost('views/features/certificates/certform.php', [CertificateController::class, 'store']);
$router->onGet('features/certificates/certform',     [CertificateController::class, 'create']);
$router->onPost('features/certificates/certform',    [CertificateController::class, 'store']);

$router->dispatch();