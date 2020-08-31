<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

// .join(Object(attribute), attribute[, left])
class ActionJoin extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    if (count($this->actionParts) < 2 || count($this->actionParts) > 3) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($this->actionParts), $actionName, 2);
    }

    $objectMappingApi = new ObjectMappingApi($this->actionParts[0]);
    list($classFullName, $joinCondition) = $objectMappingApi->parseObjectAttribute();

    $this->actionParts[1] = $this->parseAttribute($this->actionParts[1]);

    $condition = [
      'condition' => "{$joinCondition} = {$this->actionParts[1]}",
      'left' => false,
    ];

    if (count($this->actionParts) == 3 && $this->actionParts[2] == 'left') {
      $condition['left'] = true;
    }

    return new ActionResponse([
      'join' => [$classFullName => $condition],
    ]);
  }
}
