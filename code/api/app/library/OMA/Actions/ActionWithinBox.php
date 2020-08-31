<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionWithinBox extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) != 5) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 5);
    }

    // Ensure all points are numeric because they have to be directly concantinated with the query
    if (!(is_numeric($actionParts[1]) && is_numeric($actionParts[2]) && is_numeric($actionParts[3]) && is_numeric($actionParts[4]))) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 5);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->$className, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    $bind = [];

    $box = "ST_GEOMFROMTEXT('POLYGON(({$actionParts[1]} {$actionParts[2]}, {$actionParts[3]} {$actionParts[2]}, {$actionParts[3]} {$actionParts[4]}, {$actionParts[1]} {$actionParts[4]}, {$actionParts[1]} {$actionParts[2]}))')";
    $condition = "ST_WITHIN({$actionParts[0]}, $box)";

    return new ActionResponse([
      'condition' => $condition,
      'bind' => $bind,
    ]);
  }
}
