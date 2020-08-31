<?php

if (php_sapi_name() !== 'cli') {
  exit;
}

use App\Library\StringHelper;

use \Phalcon\Di\FactoryDefault\Cli as CliDI;
use \Phalcon\Cli\Console as ConsoleApp;
use \Phalcon\Loader;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Profiler as ProfilerDb;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlPdo;

$isSecure = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $isSecure = true;
}
elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
    $isSecure = true;
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', realpath('/var/www/html/app'));

/**
 * Include composer autoloader
 */
if (is_file(APP_PATH . "/../vendor/autoload.php")) {
    require APP_PATH . "/../vendor/autoload.php";
}

/**
  * Read the configuration
  */
include APP_PATH . "/config/consts.php";

/**
  * Set error handling
  */
include APP_PATH . "/config/errors.php";

// Using the CLI factory default services container
$di = new CliDI();

/**
 * Include Services
 */
include APP_PATH . '/config/services.php';

/**
 * Get config service for use in inline setup below
 */
$config = $di->getConfig();

/**
 * Register the autoloader and tell it to register the tasks directory
 */
$loader = include APP_PATH . '/config/loader.php';

$loader->registerDirs([
    $config->application->tasksDir,
], true);
$loader->register();

// Create a console application
$console = new ConsoleApp();

$console->setDI($di);

/**
 * Process the console arguments
 */
$arguments = [];

foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments['task'] = $arg;
    } elseif ($k === 2) {
        $arguments['action'] = StringHelper::toCamelCase($arg);
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}

try {
    // Handle incoming arguments
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    // Do Phalcon related stuff here
    // ..
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} catch (\Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    fwrite(STDERR, $throwable->getLine() . PHP_EOL);
    exit(1);
}
