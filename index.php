<?php

include_once __DIR__ . '/vendor/autoload.php';

use \Core\Application\Application;

$app = Application::instance(__DIR__);

$app->run();
