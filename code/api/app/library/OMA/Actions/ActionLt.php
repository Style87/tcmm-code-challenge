<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;
use \App\Library\StringHelper;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionLt extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $objectMappingApi = new ObjectMappingApi($actionParts[0]);
        list($_, $actionParts[0]) = $objectMappingApi->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->$className, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return new ActionResponse([
      'condition' => "{$actionParts[0]} < :{$actionName}_part_$key:",
      'bind' => ["{$actionName}_part_$key" => StringHelper::removeQuotes($actionParts[1])],
    ]);
  }
}
