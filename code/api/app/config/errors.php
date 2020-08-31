<?php
defined('APP_PATH') || define('APP_PATH', realpath('../../app'));

/**
  * Set app logging
  */
ini_set("error_log", APP_LOG_DIR . DIRECTORY_SEPARATOR . "php-error.log");

// error handler function
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
  $errorLog = ini_get( 'error_log' );

  if (!(error_reporting() & $errno)) {
    // This error code is not included in error_reporting, so let it fall
    // through to the standard PHP error handler
    return false;
  }

  $logOutput = [];
  $logOutput['file'] = $errfile;
  $logOutput['line'] = $errline;
  $logOutput['message'] = $errstr;
  $logOutput['request_id'] = REQUEST_ID;
  $logOutput['datetime'] = date('c', time());

  file_put_contents($errorLog, json_encode($logOutput).PHP_EOL, FILE_APPEND);

  /* Don't execute PHP internal error handler */
  return true;
}

// set to the user defined error handler
$old_error_handler = set_error_handler("myErrorHandler");
