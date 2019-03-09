<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


require 'vendor/autoload.php';

try {   
    require 'config/settings.php';    
    require 'config/database.php';
    require 'lib/Helper.php';   

} catch (Exception $e) {
    header("Access-Control-Allow-Origin: *");
    echo '{"error":{"text":'. 'Unable to start up the web service. ' . $e->getMessage() .'}}';
    die();    
}

$app = new \Slim\App([ 
    'displayErrorDetails' => true,  
    'debug' => true,
    'settings' => [
        'displayErrorDetails' => true,
        'debug'               => true,
        'whoops.editor'       => 'sublime',
        'cache'               => false
    ]
]);


include 'controllers/UserController.php';
include 'config/middleware.php';
include 'config/dependencies.php';


$app->run();

?>