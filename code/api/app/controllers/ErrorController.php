<?php
namespace App\Controllers;


class ErrorController extends ControllerBase
{

  public function indexAction()
  {
    $this->view->disable();
  }

  public function requiresLoginAction()
  {
    return $this->returnError(__FUNCTION__, ControllerBase::EMSG_INVALID_SESSION);
  }

  public function insufficientPrivilegesAction()
  {
    return $this->returnError(__FUNCTION__, ControllerBase::EMSG_INSUFFICIENT_PRIVILEGES);
  }

  public function invalidVersionAction()
  {
    return $this->returnError(__FUNCTION__, ControllerBase::EMSG_APP_VERSION);
  }
  
  public function generalAction($exception)
  {
    return $this->returnError(__FUNCTION__, ControllerBase::EMSG_UNKNOWN, $exception);
  }
}
