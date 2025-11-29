<?php

define('DEBUG', false);
define('MAINTENANCE_MODE', false);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

//------------------------------------------------------------------------------
// Set app constants and initialize globals
//

define('PROJECT_ROOT', dirname(__FILE__));
define('BASE_PATH', str_replace('/' . basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']));
define('VIEWS', PROJECT_ROOT . '/views');
define('VIEW_PARTIALS', PROJECT_ROOT . '/views/partials');

$router;
$payload;
$params;
$dbUpdateTimestamp;

//------------------------------------------------------------------------------
// Import app base classes
//
$files = glob(PROJECT_ROOT . '/base/*.php');
foreach ($files as $file) {
	require_once $file;
}
unset($file, $files);

//------------------------------------------------------------------------------
// Import app models
//
$files = glob(PROJECT_ROOT . '/models/*.php');
foreach ($files as $file) {
	require_once $file;
}
unset($file, $files);

//------------------------------------------------------------------------------
// Import app controllers
//

$files = glob(PROJECT_ROOT . '/controllers/*.php');
foreach ($files as $file) {
	require_once $file;
}
unset($file, $files);

//------------------------------------------------------------------------------
// Instantiate app modules
//

$router = new AltoRouter();

//------------------------------------------------------------------------------
// Run migrations
//

Topic::migrate();

//------------------------------------------------------------------------------
// Configure modules
//

require_once __DIR__ . '/routes.php';
