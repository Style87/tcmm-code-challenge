<?php
namespace App\Exceptions;

class AppException extends \Exception {
  const EMSG_INCORRECT_INPUT = 0;
  // Notes
  const EMSG_GET_NOTES = 1;
  const EMSG_POST_NOTES = 2;
  const EMSG_PUT_NOTES = 3;
  const EMSG_DELETE_NOTES = 4;
  // Tags
  const EMSG_GET_TAGS = 5;
  const EMSG_POST_TAGS = 6;
  const EMSG_PUT_TAGS = 7;
  const EMSG_DELETE_TAGS = 8;

  public static $ERROR_MESSAGE = [
    ["message" => "Incorrect input parameters.", "code"=>502],
    ["message" => "Failed to get Note(s).", "code"=>503],
    ["message" => "Failed to post Note.", "code"=>504],
    ["message" => "Failed to put Note.", "code"=>505],
    ["message" => "Failed to delete Note.", "code"=>506], // 5
    ["message" => "Failed to get Tag(s).", "code"=>507],
    ["message" => "Failed to post Tag.", "code"=>508],
    ["message" => "Failed to put Tag.", "code"=>509],
    ["message" => "Failed to delete Tag.", "code"=>510],
  ];

  const KEY_CODE = 'httpCode';
  const KEY_MESSAGE = 'httpMessage';
  const KEY_ERROR = 'appError';

  protected $code;
  protected $message;
  protected $appError;

  function __construct($emsg, $appError = null) {
    $this->code = self::$ERROR_MESSAGE[$emsg]['code'];
    $this->message = self::$ERROR_MESSAGE[$emsg]['message'];
    if (is_null($appError)) {
      $this->appError = self::$ERROR_MESSAGE[$emsg]['message'];
    } else {
      $this->appError = $appError;
    }
  }

  public function getAppError() {
    return [
      self::KEY_CODE    => $this->code,
      self::KEY_MESSAGE => $this->appError
    ];
  }

  public static function sprintf() {
    $args = func_get_args();
    $emsg = $args[0];
    $args[0] = self::$ERROR_MESSAGE[$emsg]['message'];

    $message = call_user_func_array('sprintf', $args);

    return new AppException($emsg, $message);
  }
}
