<?php

$container = $app->getContainer();
// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler(ROOTDIR."/logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

?>