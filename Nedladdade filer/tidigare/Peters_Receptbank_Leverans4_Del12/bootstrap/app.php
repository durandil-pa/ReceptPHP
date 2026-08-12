<?php
declare(strict_types=1);

/*
 |-------------------------------------------------------
 | Peters Receptbank Bootstrap
 |-------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function($class){
    $paths = [
        APP_PATH.'/Core/',
        APP_PATH.'/Controllers/',
        APP_PATH.'/Models/',
        APP_PATH.'/Helpers/',
        APP_PATH.'/Middleware/'
    ];

    foreach($paths as $path){
        $file = $path.$class.'.php';
        if(file_exists($file)){
            require_once $file;
            return;
        }
    }
});

VerifyCsrf::handle();
