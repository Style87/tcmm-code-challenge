<?php
namespace App\Exceptions;

class Http404Exception extends AbstractHttpException {
  protected $message = 'Not found.';
  protected $code = 404;
}