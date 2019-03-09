<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Europe/Dublin');

define('ROOTDIR',dirname(__FILE__));
define('HKEY','2877Z934adhifbaskaasady411a26udxA1');

require 'vendor/autoload.php';

try {   
    require 'config/database.php';    
    require 'lib/Helper.php';   

} catch (Exception $e) {
    header("Access-Control-Allow-Origin: *");
    echo '{"error":{"text":'. 'Unable to start up the web service. ' . $e->getMessage() .'}}';
    die();    
}

$app = new \Slim\App([ 'displayErrorDetails' => true,  'debug' => true ]);


include 'controllers/UserController.php';
include 'controllers/CategoryController.php';
include 'config/middleware.php';
include 'config/dependencies.php';


$app->run();

?>