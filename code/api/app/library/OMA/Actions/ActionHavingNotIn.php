<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionHavingNotIn extends ActionNotIn
{
  public function execute($key, $actionName, $actionString = '') {
    $actionResponse = parent::execute($key, $actionName, $actionString);
    $actionResponse->setHaving([
      'condition' => $actionResponse->getCondition(),
      'bind'      => $actionResponse->getBind(),
      'or'        => substr($actionName, 0, strlen('or')) === 'or',
    ]);
    $actionResponse->setCondition(false);
    $actionResponse->setBind(false);
    return $actionResponse;
  }
}
