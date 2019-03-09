<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;
use Carbon\Carbon;


$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $response, $next) {
    $parts = explode("/api/",$request->getUri()); 
    $uri = end($parts);
    $r = $next($request, $response);
    return $r
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->get('/', function (Request $request, Response $response) {
    $name = json_encode(array( "message" => "Welcome to the rest api"));
    $response->getBody()->write($name);
    return $response;
});


?>