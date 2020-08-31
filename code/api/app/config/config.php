<?php
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config(array(
  'database' => [
      'adapter'     => 'Mysql',
      'host'        => 'mysql',
      'username'    => getenv('MYSQL_USER'),
      'password'    => getenv('MYSQL_PASSWORD'),
      'dbname'      => getenv('MYSQL_DATABASE'),
      'charset'     => 'utf8',
      'options' => [PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  ],
  'db' => [
      'adapter'     => 'Mysql',
      'host'        => 'mysql',
      'username'    => getenv('MYSQL_USER'),
      'password'    => getenv('MYSQL_PASSWORD'),
      'dbname'      => getenv('MYSQL_DATABASE'),
      'charset'     => 'utf8',
      'options' => [PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
  ],
  'application' => [
    'appDir'         => APP_PATH . '/',
    'cacheDir'       => APP_PATH . '/cache/',
    'controllersDir' => APP_PATH . '/controllers/',
    'interfacesDir'  => APP_PATH . '/interfaces/',
    'libraryDir'     => APP_PATH . '/library/',
    'modelsDir'      => APP_PATH . '/models/',
    'migrationsDir'  => APP_PATH . '/migrations/',
    'tasksDir'       => APP_PATH . '/tasks/',

    'baseUri'        => '/',
    'staticUri'      => '/',
  ],
));
