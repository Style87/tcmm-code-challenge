<?php
namespace Phalcon\OMA\Actions;

use \App\Exceptions\AppException;

use Phalcon\OMA\ActionResponse;
use Phalcon\OMA\ObjectMappingApi;

class ActionSelect extends Action
{
  public function execute($key, $actionName, $actionString = '') {
    $actionParts = $this->parseActionString($actionString);
    if (count($actionParts) == 0) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    $select = [];
    foreach ($actionParts as $attribute) {
      if (!in_array($attribute, $this->attributes)) {
        try {
          $objectMappingApi = new ObjectMappingApi($attribute);
          list($_, $attribute) = $objectMappingApi->parseObjectAttribute();
        }
        catch(AppException $e) {
          // TODO: This exception is misleading as there could be a proper exception from the InputParser
          throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $this->$className, $attribute);
        }
      }
      else {
        // Remove any blacklisted columns from the output
        if (isset($GLOBALS['OMA_COLUMN_BLACKLIST'][$this->classFullName]) && in_array($attribute, $GLOBALS['OMA_COLUMN_BLACKLIST'][$this->classFullName])) {
          continue;
        }
        $attribute = "{$this->classFullName}.$attribute";
      }

      $select[] = $attribute;
    }

    return new ActionResponse(['select' => $select]);
  }
}
