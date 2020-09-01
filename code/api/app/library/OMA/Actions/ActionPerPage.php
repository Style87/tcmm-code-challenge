<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionPerPage extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) != 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    if (!is_numeric($actionParts[0]) || $actionParts[0] <= 0) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INVALID_PER_PAGE, $actionParts[0]);
    }

    return new ActionResponse([
      'perPage' => $actionParts[0],
    ]);
  }
}
