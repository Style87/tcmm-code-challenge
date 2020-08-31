<?php
/**
 * Services are globally registered in this file
 *
 * @var \Phalcon\Config $config
 */

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Session\Adapter\Redis as Session;
use Phalcon\Logger\Adapter\Udplogger as UdpLogger;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Events\Manager;
use Phalcon\Db\Profiler as ProfilerDb;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Db\Adapter\Pdo\Mysql as MysqlPdo;
use Phalcon\Db\Dialect\MySQL as SqlDialect;
use \App\Library\StringHelper;
try {
Phalcon\Mvc\Model::setup(['castOnHydrate' => true]);
} catch (\Exception $e) {
  error_log($e->getMessage());
}

/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * Database connection and profiler is created based in the parameters defined in the configuration file
 */
$di->set('profiler', function () {
    return new ProfilerDb();
}, true);

$di->set('db', function () use ($di) {
    $config = $this->getConfig();

    $eventsManager = new EventsManager();

    // Get a shared instance of the DbProfiler
    $profiler      = $di->getProfiler();

    // Listen all the database events
    $eventsManager->attach('db', function ($event, $connection) use ($profiler) {
        if ($event->getType() == 'beforeQuery') {
            $profiler->startProfile($connection->getSQLStatement());
            $profile = $profiler->getLastProfile();
            $profile->setSqlVariables($connection->getSqlVariables() ?: []);
            // error_log(print_r($connection->getSqlVariables(), true));
            // error_log($connection->getSQLStatement());
        }

        if ($event->getType() == 'afterQuery') {
            $profiler->stopProfile();
        }
    });

    $dialect = new SqlDialect();

    $dialect->registerCustomFunction(
        'GROUP_CONCAT',
        function($dialect, $expression) {
            return sprintf(
                " GROUP_CONCAT(%s)",
                StringHelper::removeQuotes($dialect->getSqlExpression($expression['arguments'][0]))
             );
        }
    );

    $connection = new MysqlPdo(
        [
            "host"     => $config->database->host,
            "username" => $config->database->username,
            "password" => $config->database->password,
            "dbname"   => $config->database->dbname,
            "dialectClass"  => $dialect,
        ]
    );

    // Assign the eventsManager to the db adapter instance
    $connection->setEventsManager($eventsManager);

    return $connection;
});

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () {
    $config = $this->getConfig();

    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->set('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
  $session = new Session([
    "uniqueId"   => "todo",
    "host"       => "localhost",
    "port"       => 6379,
    "auth"       => "9b2a627891c14ba2c6bf6e9eaeb88b16",
    "persistent" => false,
    "lifetime"   => TWO_WEEKS,
    "prefix"     => "my",
    "index"      => 1,
  ]);

  $session->start();

  return $session;
});

/*
 * Logging
 */
$di->set('logger', function() {

  $logger = new UdpLogger('errors', [
    'url'  => $url,
    'port' => $port
  ]);

  return $logger;
});

$di->set(
    "modelsManager",
    function() {
        return new \Phalcon\Manager();
    }
);
