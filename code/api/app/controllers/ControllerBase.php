<?php
namespace App\Controllers;

use \DateTime;
use \DateInterval;
use \Exception;

use App\Exceptions\AppException;

use Phalcon\FormatLibrary;
use Phalcon\Mvc\Controller;
use Phalcon\Session\Adapter\Files as Session;

class ControllerBase extends \Phalcon\DI\Injectable
{

  /**
   * This is executed before EVERY action
   *
   * @dispatcher
   */
  public function __construct()
  {
  }

  function returnResponse($actionName, $response = [])
  {
    if (is_string($response)) {
      $response = ['message' => $response];
    }

    return FormatLibrary::format($response);
  }

  function returnErrorMsg($constInt)
  {
    throw new AppException($constInt);
  }

  function returnError($actionName, $constInt, $exception = NULL, $additionalMessage = '')
  {
    if ($exception != NULL && is_a($exception, 'Exception') )
    {
      trigger_error("Exception: " . $exception->getMessage());
    }

    $errorMessage = (isset(AppException::$ERROR_MESSAGE[$constInt]) ? AppException::$ERROR_MESSAGE[$constInt]['message'] : '') . (empty($additionalMessage) ? '' : ' '.$additionalMessage);

    throw new AppException($constInt, $errorMessage);
  }

  static public function parseBooleanInput($input, $default)
  {
    if ($input === '1' || $input === 1 || strtolower($input) === 'true')
    {
      $input = true;
    }
    else if ($input === '0' || $input === 0 || strtolower($input) === 'false')
    {
      $input = false;
    }
    else
    {
      $input = $default;
    }

    return $input;
  }

  public function getInput($input, $default = null, $notEmpty = false)
  {
    if ($this->request->isGet() && $this->request->hasQuery($input))
    {
      $r = $this->request->get($input);
    }
    else if ($this->request->isPost() && $this->request->hasPost($input))
    {
      $r = $this->request->getPost($input);
    }
    else if (($this->request->isPut() || $this->request->isDelete()) && $this->request->hasPut($input))
    {
      $r = $this->request->getPut($input);
    }
    else
    {
      $requestJSON = (array) $this->request->getJsonRawBody();
      if (array_key_exists($input, $requestJSON))
      {
        $r = $requestJSON[$input];
      }
      else
      {
        $r = $default;
      }
    }

    if ($notEmpty && empty($r)) {
      $r = $default;
    }

    return $r;
  }

  public function getInputAsInt($input, $default = null)
  {
    $r = $this->getInput($input, $default);
    if (!is_null($r)) {
      $r = (int) $r;
    }
    return $r;
  }

  public function getInputAsFloat($input, $default = null)
  {
    $r = $this->getInput($input, $default);
    if (!is_null($r)) {
      $r = (float) $r;
    }
    return $r;
  }

  public function getInputAsBoolean($input, $default = null)
  {
    $r = $this->getInput($input, $default);
    if ($r === '1' || $r === 1 || strtolower($r) === 'true')
    {
      $r = true;
    }
    else if ($r === '0' || $r === 0 || strtolower($r) === 'false')
    {
      $r = false;
    }
    return $r;
  }

  protected function checkRequiredParameters($requiredParameters = []) {
    $return = [];
    $missingParameters = [];
    foreach ($requiredParameters as $requiredParameter) {
      $parameter = $this->getInput($requiredParameter);
      if ($parameter == NULL) {
        $missingParameters[] = "Missing $requiredParameter.";
      }
      $return[] = $parameter;
    }

    if (count($missingParameters) > 0)
    {
      throw new AppException(AppException::EMSG_INCORRECT_INPUT, implode(' ', $missingParameters));
    }

    return $return;
  }

  protected function logQuery() {
    // Get the generated profiles from the profiler
    $profile = $this->getDI()->get('profiler')->getLastProfile();
    trigger_error("SQL Statement: ". $profile->getSQLStatement());
    trigger_error("SQL Variables: " . print_r($profile->getSqlVariables(),true));
    trigger_error("Start Time: ". $profile->getInitialTime());
    trigger_error("Final Time: ". $profile->getFinalTime());
    trigger_error("Total Elapsed Time: ". $profile->getTotalElapsedSeconds());
  }
}
