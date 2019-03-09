<?php

define("ENVIRONMENT", "DEV");

if(ENVIRONMENT == 'DEV') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

date_default_timezone_set('Europe/Dublin');
define('ROOTDIR',dirname(dirname(__FILE__)));
define('HKEY','28934adhifbaskaasady411azcb3421ax6');
define("SITE_URL", "http://localhost:8080");
define("DEFAULT_EMAIL", "test@gmail.com");
define("SITE_DOMAIN", "testsite.com");
define("SITE_NAME", "MyWebsite");
define("DATABASE_NAME", "");
define("DATABASE_USER", "");
define("DATABASE_PASSWORD", "");
define("ONLY_USERS", [ 'restricted' ]);
define("ONLY_ADMIN", [ 'admin' ]);

?>