<?php
namespace App\Exceptions;

class AbstractHttpException extends \Exception {
  const KEY_CODE = 'httpCode';
  const KEY_MESSAGE = 'httpMessage';
  const KEY_ERROR = 'appError';
  
  protected $code = '500';
  protected $message = 'Internal message.';
  protected $appError = 'Message from app.';

  function __construct($appError = null) {
    if (!is_null($appError)) {
      $this->appError = $appError;
    }
  }

  public function getAppError() {
    return [
      self::KEY_CODE    => $this->code,
      self::KEY_MESSAGE => $this->message,
      self::KEY_ERROR => $this->appError
    ];
  }
}