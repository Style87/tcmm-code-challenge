<?php
namespace App\Exceptions;

class Http415Exception extends AbstractHttpException {
  protected $message = 'Unsupported Media Type';
  protected $code = 415;
}
