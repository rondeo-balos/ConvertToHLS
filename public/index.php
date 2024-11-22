<?php
ini_set('upload_max_filesize', '5000M');
ini_set('post_max_size', '5000M');
ini_set('memory_limit', '5000M');
ini_set('max_input_time', '3000');
ini_set('max_execution_time', '3000');

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
