<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\App;

$app = App::getInstance();
$app->bootstrap(__DIR__ . '/..');

$router = $app->getRouter();

require_once __DIR__ . '/../routes/router.php';
registerRoutes($router);

$app->run();
