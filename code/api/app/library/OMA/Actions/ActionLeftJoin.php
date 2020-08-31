<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

// .leftJoin(Object(attribute), attribute)
class ActionLeftJoin extends ActionJoin
{
  public function execute($key, $actionName, $actionString = '') {
    if (count($this->actionParts) != 2 ) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($this->actionParts), $actionName, 2);
    }

    $this->actionParts[] = 'left';

    return parent::execute($key, $actionName, $actionString);
  }
}
