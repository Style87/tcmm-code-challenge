<?php
namespace App\Exceptions;

class Http403Exception extends AbstractHttpException {
  protected $message = 'Forbidden';
  protected $code = 403;
}
