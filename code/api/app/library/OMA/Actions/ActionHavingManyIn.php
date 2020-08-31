<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;
use \App\Library\StringHelper;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionHavingManyIn extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) < 4) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 4);
    }

    if (!in_array($actionParts[1], $this->attributes)) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->$className, $actionParts[1]);
    }

    $objectMappingApi = new ObjectMappingApi($actionParts[0]);
    list($classFullName, $joinCondition, $joinAttribute) = $objectMappingApi->parseObjectAttribute();
    $joinAlias = "{$actionName}_{$key}";
    $joinCondition = "{$joinAlias}.{$joinAttribute}";

    $actionParts[1] = $this->parseAttribute($actionParts[1]);

    $condition = [
      'condition' => "{$joinCondition} = {$actionParts[1]}",
      'left' => false,
      'alias' => $joinAlias,
    ];

    $objectMappingApi = new ObjectMappingApi($actionParts[2]);
    list($classFullName, $joinCondition, $joinAttribute) = $objectMappingApi->parseObjectAttribute();

    $condition['condition'] .= " AND {$joinAlias}.{$joinAttribute} IN (";

    $bind = [];
    $inConditionString = '';
    $inConditionElements = array_slice($actionParts, 3);
    sort($inConditionElements);

    for ($index = 0; $index < count($inConditionElements); $index++) {
      if (!is_numeric($inConditionElements[$index])) {
        // TODO: Make a specific error code for this.
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->className, $inConditionElements[$index]);
      }
      $inConditionElements[$index] = (int) $inConditionElements[$index];
      $inConditionString .=  "{$inConditionElements[$index]},";
    }
    $inConditionString = rtrim($inConditionString,',');
    $condition['condition'] .= rtrim($inConditionString,',');
    $condition['condition'] .= ")";

    $groupBy = [
      "{$actionParts[1]}"
    ];

    // Using a custom phalcon dialect function GROUP_CONCAT. That's why the value inside is wraped in quotes.
    $havingBindKey = "HAVING_{$actionName}_{$key}";
    $having = [
      'condition' => "GROUP_CONCAT('{$joinAlias}.{$joinAttribute} ORDER BY {$joinAlias}.{$joinAttribute}') = :{$havingBindKey}:",
      'bind' => [$havingBindKey => $inConditionString],
      'or' => false,
    ];

    return new ActionResponse([
      'join' => [$classFullName => $condition],
      'groupBy' => $groupBy,
      'having' => $having,
      'bind' => $bind,
    ]);
  }
}
