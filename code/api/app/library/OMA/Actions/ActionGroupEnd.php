<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionGroupEnd extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    return new ActionResponse([
      'condition' => ')',
    ]);
  }
}
