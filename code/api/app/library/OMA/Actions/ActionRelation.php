<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionRelation extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    if (empty($actionString)) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, 0, $actionName, 1);
    }

    $objectMappingApi = new ObjectMappingApi($actionString);
    list($class, $relation) = $objectMappingApi->parse();

    return new ActionResponse([
      'relation' => [$class => $relation],
    ]);
  }
}
