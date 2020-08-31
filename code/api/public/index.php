<?php
// noop: 8
// Report all errors
error_reporting(E_ALL);

// Set the default timezone to UTC
date_default_timezone_set('UTC');

use App\Exceptions\AbstractHttpException;
use App\Exceptions\AppException;
use App\Exceptions\Http415Exception;
use App\Exceptions\Http401Exception;

use Phalcon\Di\FactoryDefault;
use Phalcon\FormatLibrary;
use Phalcon\InputParser;
use Phalcon\OMA\ObjectMappingApi;
use \Phalcon\Http\Request\Exception as PhalconRequestException;
use Dmkit\Phalcon\Auth\JWTAdapter;

try {

  $isSecure = false;
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
      $isSecure = true;
  }
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
      $isSecure = true;
  }

  define('APP_PATH', realpath('../app'));

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

  /**
    * Read the configuration
    */
  $config = include APP_PATH . "/config/config.php";

  /**
    * Read auto-loader
    */
  include APP_PATH . "/config/loader.php";

  /**
    * Set ACL
    */
  $acl = include APP_PATH . "/config/acl.php";

  /**
   * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
   */
  $di = new FactoryDefault();

  /**
    * Read services
    */
  include APP_PATH . "/config/services.php";

  $di->setShared(
    'acl',
    function () use($acl) {
        return $acl;
    }
  );

  /**
    * Handle the request
    */
  $app = new \Phalcon\Mvc\Micro();

  /**
   * Overriding Response-object to set the Content-type header globally
   */
  $di->setShared(
    'response',
    function () use($app) {
      // Get the accept header
      $acceptHeader = $app->request->getHeader('Accept');

      $response = new \Phalcon\Http\Response();
      if ($acceptHeader == 'application/json') {
        $response->setContentType('application/json', 'utf-8');
      }

      return $response;
    }
  );

  // Set the request id response header if we're in dev mode
  if (getenv('IS_DEV')) {
    $app->response->setHeader("x-request-id", REQUEST_ID);
  }

  $app->setDI($di);

  // Check the Accept type
  $app->before(function() use($app) {
    // Get the accept header
    $acceptHeader = $app->request->getHeader('Accept');
    $acceptHeaders = explode(',', $acceptHeader);
    $goodAcceptHeaders = [
      'application/json',
    ];
    $acceptHeaders = array_intersect($acceptHeaders, $goodAcceptHeaders);
    if (count($acceptHeaders) !== 0) {
      return true;
    }
    throw new Http415Exception();
  });

  // Set shared debug and columns
  $app->before(
    function () use ($app) {
      // debug
      $app->di->setShared(
        'debug',
        function () use($app) {
          if ($app->request->isGet() && $app->request->hasQuery('debug'))
          {
            return $app->request->get('debug');
          }
          else if ($app->request->isPost() && $app->request->hasPost('debug'))
          {
            return $app->request->getPost('debug');
          }
          else if (($app->request->isPut() || $app->request->isDelete()) && $app->request->hasPut('debug'))
          {
            return $app->request->getPut('debug');
          }
          else
          {
            $requestJSON = (array) $app->request->getJsonRawBody();
            if (array_key_exists('debug', $requestJSON))
            {
              return $requestJSON[$input];
            }
            return false;
          }
        }
      );
      // omi=Vote().whereEq(vote, 1).whereEq(userId,86)
      $app->di->setShared(
        'omi',
        function () use($app) {
          if (!$app->request->hasQuery('omi') || empty($app->request->hasQuery('omi'))) {
            return false;
          }

          $filter = $app->request->get('omi');

          $inputParser = new ObjectMappingApi($filter);
          list($className, $return) = $inputParser->parse();
          return $return;
        }
      );
    }
  );

  /**
    * Include Routes
    */
  include APP_PATH . "/config/routes.php";

  // Making the correct answer after executing
  $app->after(
    function () use ($app) {
      // Get formatting parameters
      $omi       = $app->di->getShared('omi');
      $filter = null;
      $relations = [];

      if ($omi) {
        $relations = $omi['relations'];
        unset($omi['relations']);
        $filter = $omi;
      }

      $page      = is_array($filter) && array_key_exists('page', $filter) && is_numeric($filter['page']) ? $filter['page'] : null;
      $perPage   = is_array($filter) && array_key_exists('perPage', $filter)  && is_numeric($filter['perPage']) ? $filter['perPage'] : null;
      $order     = is_array($filter) && array_key_exists('order', $filter) ? $filter['order'] : null;

      if (is_array($filter) && sizeof($filter) == 0) {
        throw new AppException(AppException::EMSG_INPUT_PARSER_INVALID_MODEL);
      }

      if ($order === false) {
        throw new AppException(AppException::EMSG_INVALID_ORDER);
      }

      // Get the accept header
      $acceptHeader = $app->request->getHeader('Accept');

      // Get the returned value of method
      $return = $app->getReturnedValue();

      if ($acceptHeader == 'application/json') {
        // Set a header with the request id for better debugging
        $app->response->setHeader("x-request-id", REQUEST_ID);

        if (!is_a($return,'\Phalcon\Mvc\Model\Query\Builder',false)) {
        }
        // Format the return value of method
        $formattedReturn = FormatLibrary::format($return, null, $relations, $filter, $page, $perPage, $order);

        // Set pagination headers and items
        if (is_numeric($page) && is_numeric($perPage) && $perPage != 1) {
          if ($page != $formattedReturn->current) {
            $formattedReturn->current = -1;
            $formattedReturn->items = [];
          }
          $app->response->setHeader("x-pagination-page", $formattedReturn->current);
          $app->response->setHeader("x-pagination-perPage", $formattedReturn->limit);
          $app->response->setHeader("x-pagination-totalCount", $formattedReturn->total_items);
          $app->response->setHeader("x-pagination-pages", $formattedReturn->total_pages);

          $formattedReturn = $formattedReturn->items;
        }

        if (is_array($formattedReturn) || is_object($formattedReturn)) {
          // Transforming arrays to JSON
          $json = json_encode($formattedReturn);
          if (function_exists('json_last_error') && $errno = json_last_error()) {
              if ($errno == JSON_ERROR_UTF8) {
                $formattedReturn = FormatLibrary::convert_from_latin1_to_utf8_recursively($formattedReturn);
                $json = json_encode($formattedReturn);
                if ($errno = json_last_error()) {
                    FormatLibrary::handleJsonError($errno);
                }
              }
          }
          $app->response->setContent($json);
        } elseif (!strlen($formattedReturn)) {
          // Successful response without any content
          $app->response->setStatusCode('204', 'No Content');
        } else {
          // Unexpected response
          throw new Exception('Bad Response');
        }
      }
    }
  );

  // Processing request
  $app->handle();

} catch (AbstractHttpException $e) {
  trigger_error($e->getMessage());

  $app->response->setStatusCode($e->getCode(), $e->getMessage())
                ->setJsonContent($e->getAppError());
} catch (AppException $e) {
  trigger_error("ERROR ({$e->getCode()}) {$e->getMessage()}");
  trigger_error(print_r($e->getAppError(),true));

  $app->response->setStatusCode($e->getCode(), $e->getMessage())
                ->setJsonContent($e->getAppError());
} catch (PhalconRequestException $e) {
  trigger_error($e->getMessage());
  trigger_error($e->getTraceAsString());
  $app->response->setStatusCode(400, 'Bad request')
                ->setJsonContent([
                  AbstractHttpException::KEY_CODE    => 400,
                  AbstractHttpException::KEY_MESSAGE => 'Bad request'
                ]);
} catch (\Exception $e) {
  trigger_error($e->getMessage());
  trigger_error($e->getTraceAsString());
  // Standard error format
  $result = [
    AbstractHttpException::KEY_CODE    => 500,
    AbstractHttpException::KEY_MESSAGE => 'Some error occurred on the server.'
  ];

  // Sending error response
  $app->response->setStatusCode(500, 'Internal Server Error')
                ->setJsonContent($result);
} finally {
  // Sending response to the client
  $app->response->send();
}
