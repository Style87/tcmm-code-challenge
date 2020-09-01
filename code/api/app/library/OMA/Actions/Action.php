<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

abstract class Action implements Actionable
{
  protected $attributes = [];
  protected $classFullName = '';
  protected $className = '';
  protected $actionString = '';
  protected $actionParts = [];

  public function __construct($className, $classFullName, $attributes, $actionString) {
    $this->className = $className;
    $this->classFullName = $classFullName;
    $this->attributes = $attributes;
    $this->actionString = $actionString;

    $this->actionParts = $this->parseActionString($actionString);
  }

  protected function parseActionString($string) {
    return array_map('trim', array_filter(explode(',', $string), function($value) { return $value !== ''; }));
  }

  protected function parseAttribute($attribute) {
    if (!in_array($attribute, $this->attributes)) {
      try {
        $objectMappingApi = new ObjectMappingApi($attribute);
        list($_, $attribute) = $objectMappingApi->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->$className, $attribute);
      }
    }
    else {
      $attribute = "{$this->classFullName}.{$attribute}";
    }

    return $attribute;
  }
}
