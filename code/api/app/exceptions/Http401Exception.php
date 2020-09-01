<?php
namespace App\Exceptions;

class Http401Exception extends AbstractHttpException {
  protected $message = 'Unauthorized';
  protected $code = 401;
}