<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionBetween extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) != 3) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 3);
    }

    $bind = [];

    if (in_array($actionParts[0], $this->attributes)) {
      $sql1 = "{$this->classFullName}.{$actionParts[0]}";
    }
    else {
      try {
        $objectMappingApi = new ObjectMappingApi($actionParts[0]);
        list($_, $sql1) = $objectMappingApi->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        $sql1 = ":between_part1_$key:";
        $bind["between_part1_$key"] = $actionParts[0];
      }
    }

    if (in_array($actionParts[1], $this->attributes)) {
      $sql2 = "{$this->classFullName}.{$actionParts[1]}";
    }
    else {
      try {
        $objectMappingApi = new ObjectMappingApi($actionParts[1]);
        list($_, $sql2) = $objectMappingApi->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        $sql2 = ":between_part2_$key:";
        $bind["between_part2_$key"] = $actionParts[1];
      }
    }

    if (in_array($actionParts[2], $this->attributes)) {
      $sql3 = "{$this->classFullName}.{$actionParts[2]}";
    }
    else {
      try {
        $objectMappingApi = new ObjectMappingApi($actionParts[2]);
        list($_, $sql3) = $objectMappingApi->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        $sql3 = ":between_part3_$key:";
        $bind["between_part3_$key"] = $actionParts[2];
      }
    }

    return new ActionResponse([
      'condition' => "$sql1 BETWEEN $sql2 AND $sql3",
      'bind' => $bind,
    ]);
  }
}
