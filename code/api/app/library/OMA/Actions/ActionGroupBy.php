<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionGroupBy extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) == 0) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    foreach ($actionParts as $key => $value) {
      if (!in_array($actionParts[$key], $this->attributes)) {
        try {
          $objectMappingApi = new ObjectMappingApi($actionParts[$key]);
          list($_, $actionParts[$key]) = $objectMappingApi->parseObjectAttribute();
        }
        catch(AppException $e) {
          // TODO: This exception is misleading as there could be a proper exception from the InputParser
          throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->$className, $actionParts[$key]);
        }
      }
      else {
        $actionParts[$key] = "{$this->classFullName}.{$actionParts[$key]}";
      }
    }

    return new ActionResponse([
      'groupBy' => $actionParts,
    ]);
  }
}
