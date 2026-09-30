<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/src/config/config.php';

use App\Router\Router;
use App\Controllers\AuthController;

$router = new Router();
$router->onGet('/login',  [AuthController::class, 'showLogin']);
$router->onPost('/login', [AuthController::class, 'login']);
$router->onPost('/logout', [AuthController::class, 'logout']);
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
    <?php view('components/Navbar');?>
    <?php view('features/user/homepage');?>
</body>
</html>