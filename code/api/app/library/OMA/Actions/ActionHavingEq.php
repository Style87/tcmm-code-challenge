<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionHavingEq extends ActionEq
{
  public function execute($key, $actionName, $actionString = '') {
    $actionResponse = parent::execute($key, $actionName, $actionString);
    $having = [
      'condition' => $actionResponse->getCondition(),
      'bind'      => $actionResponse->getBind(),
      'or'        => substr($actionName, 0, strlen('or')) === 'or',
    ];
    $actionResponse->setHaving($having);
    $actionResponse->setCondition(false);
    $actionResponse->setBind(false);
    return $actionResponse;
  }
}
