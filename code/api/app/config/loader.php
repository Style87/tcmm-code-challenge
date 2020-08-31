<?php

$loader = new \Phalcon\Loader();

$loader->registerNamespaces(
  [
    'Phalcon'            => APP_PATH . '/library/',
    'App\Controllers'    => APP_PATH . '/controllers/',
    'App\Exceptions'     => APP_PATH . '/exceptions/',
    'App\Interfaces'     => APP_PATH . '/interfaces/',
    'App\Library'        => APP_PATH . '/library/',
    'App\Models'         => APP_PATH . '/models/',
    'App\Core\Models'    => APP_PATH . '/core/models/',
    'App\Services'       => APP_PATH . '/services/',
  ]
);
/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
  [
    $config->application->controllersDir,
    $config->application->interfacesDir,
    $config->application->libraryDir,
    $config->application->modelsDir,
  ]
);

$loader->register();

return $loader;
