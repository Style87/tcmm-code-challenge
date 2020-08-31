<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionDistinct extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) > 0) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 0);
    }

    return new ActionResponse([
      'distinct' => true,
    ]);
  }
}
