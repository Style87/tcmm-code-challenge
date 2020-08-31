<?php
namespace Phalcon\OMA\Actions;

Interface Actionable
{
  public function execute($key, $actionName, $actionString = '');
}
