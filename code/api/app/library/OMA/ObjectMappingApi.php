<?php
namespace Phalcon\OMA;

use \DomainException;

use \App\Exceptions\AppException;

use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Paginator\Factory;

// Object(attribute, RelatedObject(attribute).action(key, value, value), attribute).action(key, value, value):Object().join(Object(attribute), attribute)

class ObjectMappingApi
{
  private $actionList = [];

  protected $inputString;

  protected $workingString = '';
  protected $parts = [];
  protected $leftParenthesisCount = 0;
  protected $isAction = false;

  protected $attributes;

  public $className;
  public $classFullName;
  public $select = [];
  public $distinct = null;
  public $columns = null;
  public $conditions = null;
  public $bind = [];
  public $join = [];
  public $order = null;
  public $groupBy = null;
  public $having = null;
  public $page = null;
  public $perPage = null;
  public $relations = [];
  /**
   * Constructor
   * @param array $parameters The parameters passed during construction
  */
  public function __construct($inputString = '')
  {
    // trigger_error("inputString: {$inputString}");
    $this->reset($inputString);

    foreach (ObjectActions::ACTIONS as $action => $actionAliasList) {
      $this->actionList = array_merge($this->actionList, $actionAliasList);
    }
  }

  public function reset($inputString) {
    $this->inputString = $inputString;
    $this->workingString = $inputString;
    $this->leftParenthesisCount = 0;
    $this->className = null;
    $this->classFullName = null;
    $this->select = [];
    $this->distinct = null;
    $this->columns = [];
    $this->conditions = null;
    $this->bind = [];
    $this->join = [];
    $this->order = null;
    $this->groupBy = null;
    $this->having = [];
    $this->page = null;
    $this->perPage = null;
    $this->relations = [];
  }

  public function parse() {
    // trigger_error("PARSE: {$this->inputString}");

    $this->parseClass();
    // If the related class has been blacklisted return false
    if (array_key_exists($this->classFullName, $GLOBALS['OMA_RELATION_BLACKLIST'])) {
      return [false, []];
    }
    $this->parseColumns();
    $this->parseActionsString();

    if (count($this->columns) == 0) {
      $this->columns = null;
    }

    $return = [
      $this->className,
      [
        'from'       => $this->classFullName,
        'select'     => $this->select,
        'distinct'   => $this->distinct,
        'columns'    => $this->columns,
        'conditions' => $this->conditions,
        'bind'       => $this->bind,
        'join'       => $this->join,
        'order'      => $this->order,
        'groupBy'    => $this->groupBy,
        'having'     => $this->having,
        'page'       => $this->page,
        'perPage'    => $this->perPage,
        'relations'  => $this->relations,
      ]
    ];

    if (is_null($this->conditions)) {
      unset($return[1]['conditions']);
    }

    // trigger_error(print_r($return,true));

    return $return;
  }

  public function parseObjectAttribute() {
    // trigger_error("PARSE: {$this->inputString}");

    $this->parseClass();
    // If the related class has been blacklisted return false
    if (array_key_exists($this->classFullName, $GLOBALS['OMA_RELATION_BLACKLIST'])) {
      // TODO: throw exception
    }
    $this->parseColumns();


    if (count($this->columns) != 1) {
      // TODO: throw exception
    }

    return [$this->classFullName, "{$this->classFullName}.{$this->columns[0]}", $this->columns[0]];
  }

  protected function parseClass() {
    $class = $this->workingString;
    $this->leftParenthesisCount++;
    $classNamePosEnd = strpos($class, '(');

    $this->className = substr($class, 0, $classNamePosEnd);
    $this->classFullName = "App\Models\\{$this->className}";

    if (!class_exists($this->classFullName)) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_MODEL, $this->className);
    }

    $this->workingString = substr_replace($this->workingString, '', 0, $classNamePosEnd+1);
    //trigger_error($this->workingString);
  }

  // GET
  // Users()
  // Users(isActive, Fans())
  // Users(isActive, Fans(id,username))
  // Users(isActive, Fans(id,name)).where(followerUserId=86)
  // Users(isActive, Fans()).where(followerUserId=86):Idols().where(followedUserId=86)
  // Users():Fans(id, Users()):Idols(id, Users(id)):Pings(id, Users())
  // Users():Fans(id, Users()):Idols(id, Users(id)):Pings(id, Users(id, Fans(followedUserId, followerUserId).whereEq(followerUserId,5)), lat, lng)

  // POST/PUT/PATCH
  // - User access to edit.
  // - Save/Edit multiple objects of the same type
  // Users().set(attribute, value).set(attribute, value)
  // Collections().set(attribute, value).set(attribute, value):Regions().set()
  // Collections().where(id,1).set(attribute, value).set(attribute, value)
  // Regions().groupStart().set().groupEnd().groupStart().set():Regions().set().groupEnd()

  protected function parseColumns() {
    // trigger_error('parseColumns');
    // trigger_error("WORKING: {$this->workingString}");
    $relations = [];

    // Find columns closing parenthesis
    $closingParenthesisPosition = $this->findMatchingParenthesisPosition($this->workingString, 1);
    // trigger_error($closingParenthesisPosition . ' - ' . strlen($this->workingString));
    if (($closingParenthesisPosition + 1) == 1) {
      // trigger_error('No columns.');
      $this->workingString = substr_replace($this->workingString, '', 0, $closingParenthesisPosition+1);
      return [];
    }

    // Separate the columns string
    $columnsString = trim(substr($this->workingString, 0, $closingParenthesisPosition));
    // Remove the columns string from the working string
    $this->workingString = substr_replace($this->workingString, '', 0, $closingParenthesisPosition+1);

    while(strlen($columnsString) > 0) {
      // trigger_error("COLUMNS: $columnsString");
      list($key, $pos) = $this->findNextKey($columnsString);
      // trigger_error("KEY, POS: '$key', $pos");
      switch($key) {
        case ',':
        case '':
        case ')':
          // trigger_error('Add column: ' . substr($columnsString, 0, $pos));
          $column = substr($columnsString, 0, $pos);
          // Remove any blacklisted columns from the output
          if (isset($GLOBALS['OMA_COLUMN_BLACKLIST'][$this->classFullName]) && in_array($column, $GLOBALS['OMA_COLUMN_BLACKLIST'][$this->classFullName])) {
            break;
          }
          $this->columns[] = $column;
        break;
        case '(':
          $closingParenthesisPosition = $this->findMatchingParenthesisPosition($columnsString, 1, $pos+1);
          $c = 0;
          // trigger_error(($closingParenthesisPosition+1)." < " . strlen($columnsString)." && ".substr($columnsString, $closingParenthesisPosition+1, 1)." == ".'.'." && $c<5");
          while ($closingParenthesisPosition+1 < strlen($columnsString) && substr($columnsString, $closingParenthesisPosition+1, 1) == '.' && $c<5) {
            $c++;
            $closingParenthesisPosition = $this->findMatchingParenthesisPosition($columnsString, 1, (strpos($columnsString, '(', $closingParenthesisPosition+1) + 1));
          }
          // trigger_error("NEXT CHARACTER: '" . substr($columnsString, $closingParenthesisPosition+1, 1) . "'");

          $objectMappingApi = new ObjectMappingApi(substr($columnsString, 0, $closingParenthesisPosition+1));
          list($key, $relation) = $objectMappingApi->parse();
          $this->relations[$key] = $relation;
          $pos = $closingParenthesisPosition +1;
        break;
      }
      $columnsString = trim(substr_replace($columnsString, '', 0, min($pos+1, strlen($columnsString))));
    }
  }

  /**
    * Parse input strings from relations and filter for clauses.
    *
    * @param array &$parameters A reference to the parameter array.
    *
    * @return void
    */
  protected function parseActions() {
    // trigger_error("Parse Actions: '{$this->workingString}' " . strlen($this->workingString));
    if (strlen($this->workingString) == 0) {
      return false;
    }

    // Build a regex to split the actions out
    $regexString = '/\.(';
    $regexString .= implode('|', $this->actionList);
    $regexString .= ')/';
    $splitParts = preg_split($regexString, $this->workingString, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    if ($this->parts === false) {
      return false;
    }
    $this->parts = [];
    // Recombine the action with its parts
    for($index=1;$index<count($splitParts);$index+=2) {
      $this->parts[] = $splitParts[$index-1].$splitParts[$index];
    }
    if (count($this->parts) == 0) {
      return false;
    }
    // Get the model attributes as we'll be needing them for action verification
    if (!class_exists($this->classFullName)) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_MODEL, $this->className);
    }
    $classFullName = $this->classFullName;
    $object = new $classFullName();
    $relatedModel = get_class($object);
    if ($relatedModel === false) {
      // TODO: I don't think it's possible to get here. Should test.
      return false;
    }
    $metaData = new Memory();
    $this->attributes = $metaData->getAttributes($object);

    foreach ($this->parts as $key => $value) {
      $this->parseAction($key, $value);
    }
  }

  /**
    * Parse input strings from relations and filter for clauses.
    *
    * @param array &$parameters A reference to the parameter array.
    *
    * @return void
    */
  protected function parseActionsString() {
    // trigger_error("Parse Actions: '{$this->workingString}' " . strlen($this->workingString));
    if (strlen($this->workingString) == 0) {
      return false;
    }

    $str = $this->workingString;

    // Build a regex to split the actions out
    $regexString = '/^\.(';
    $regexString .= implode('|', $this->actionList);
    $regexString .= ')\(/';
    $this->parts = [];

    while (preg_match($regexString, $str, $matches)) {
      $match = $matches[0]; // With period
      $pos = strlen($match)-1; // -1 for the matched parenthetical
      $posStart = $pos;
      $char = substr($str, $pos, 1);
      $count = 0;
      $inString = false;
      $stringIdentifier = '';
      do {
        if ($char == "'" && ($char == $stringIdentifier || empty($stringIdentifier))) {
          $stringIdentifier = "'";
          if ($inString) {
            $stringIdentifier = '';
          }
          $inString = !$inString;
        } else if ($char == '"' && ($char == $stringIdentifier || empty($stringIdentifier))) {
          $stringIdentifier = '"';
          if ($inString) {
            $stringIdentifier = '';
          }
          $inString = !$inString;

        } else if ($char == '(' && !$inString) {
          $count ++;
        } else if ($char == ')' && !$inString) {
          $count--;
        }
        $pos++;
        $char = substr($str, $pos, 1);
        if ($pos > strlen($str)) {
          error_log('Error processing action string: ' . $str);
          return false;
        }
      } while ($count > 0 && $pos <= strlen($str));
      $posEnd = $pos--;
      $part = $matches[1].substr($str, $posStart, $posEnd - $posStart);
      $this->parts[] = $matches[1].substr($str, $posStart, $posEnd - $posStart);
      $str = substr($str, strlen($part)+1, strlen($str) - strlen($part)+1);
    }

    if (count($this->parts) == 0) {
      return false;
    }
    // Get the model attributes as we'll be needing them for action verification
    if (!class_exists($this->classFullName)) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_MODEL, $this->className);
    }
    $classFullName = $this->classFullName;
    $object = new $classFullName();
    $relatedModel = get_class($object);
    if ($relatedModel === false) {
      // TODO: I don't think it's possible to get here. Should test.
      return false;
    }
    $metaData = new Memory();
    $this->attributes = $metaData->getAttributes($object);

    foreach ($this->parts as $key => $value) {
      $this->parseAction($key, $value);
    }
  }

  protected function parseAction($key, $value) {
    $part = $value;

    $actionNamePosEnd = strpos($part, '(');

    $actionName = substr($part, 0, $actionNamePosEnd);

    $part = trim(substr_replace($part, '', 0, $actionNamePosEnd+1));
    // Remove the last right parentheses with substr because rtrim will remove multiples
    $part = substr($part, 0, -1);
    // $actionParts = array_map('trim', array_filter(explode(',', $part), function($value) { return $value !== ''; }));

    $glue = 'AND';

    $actionResponse = null;
    foreach (ObjectActions::ACTIONS as $actionClass => $actionAliasList) {
      if (in_array($actionName, $actionAliasList)) {
        $actionClassFullName = __NAMESPACE__ . "\\Actions\\{$actionClass}";
        $action = new $actionClassFullName($this->className, $this->classFullName, $this->attributes, $part);
        $actionResponse = $action->execute($key, $actionName, $part);
        break;
      }
    }

    if (is_null($actionResponse)) {
      trigger_error("Unknown action: $actionName");
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ACTION, $actionName);
    }

    if (substr($actionName, 0, strlen('or')) === 'or') {
      $glue = 'OR';
    }

    if ($actionResponse->getCondition()) {
      if (
          (
            $this->conditions != null
            && !in_array($actionName, array_merge(ObjectActions::ACTIONS[ObjectActions::ACTION_WHERE_GROUP], ObjectActions::ACTIONS[ObjectActions::ACTION_WHERE_GROUP_END]))
            && substr($this->conditions, -2) !== '( '
          ) ||
          (
            $this->conditions != null
            && in_array($actionName, ObjectActions::ACTIONS[ObjectActions::ACTION_WHERE_GROUP])
          )
      ) {
        $actionResponse->setCondition("$glue {$actionResponse->getCondition()}");
      }
      $this->conditions .= " {$actionResponse->getCondition()} ";
    }
    if ($actionResponse->getBind()) {
      $this->bind = array_merge($this->bind, $actionResponse->getBind());
    }
    if ($actionResponse->getOrder()) {
      if ($this->order != null) {
        $actionResponse->setOrder(", {$actionResponse->getOrder()}");
      }
      $this->order .= $actionResponse->getOrder();
    }
    if ($actionResponse->getPerPage()) {
      $this->perPage = $actionResponse->getPerPage();
    }
    if ($actionResponse->getPage()) {
      $this->page = $actionResponse->getPage();
    }
    if ($actionResponse->getHaving()) {
      $this->having[] = $actionResponse->getHaving();
    }
    if ($actionResponse->getGroupBy()) {
      if (is_null($this->groupBy)) {
        $this->groupBy = [];
      }
      $this->groupBy = array_merge($this->groupBy, $actionResponse->getGroupBy());
    }
    if (count($actionResponse->getJoin()) > 0) {
      $this->join = array_merge($this->join, $actionResponse->getJoin());
    }
    if ($actionResponse->getSelect() !== false) {
      $this->select = array_merge($this->select, $actionResponse->getSelect());
    }
    if (count($actionResponse->getRelation()) > 0) {
      $this->relations = array_merge($this->relations, $actionResponse->getRelation());
    }
    if ($actionResponse->getDistinct() !== false) {
      $this->distinct = $actionResponse->getDistinct();
    }
  }

  protected function findMatchingParenthesisPosition($string, $count = 0, $offset = 0) {
    //trigger_error('findMatchingParenthesisPosition');
    $c = 0;
    $strlen = strlen($string);
    $rightParenthesisPosition = $strlen;
    while ($count > 0 && $c<20) {
      $c++;
      $leftParenthesisPosition = strpos($string, '(', $offset) ?: $strlen;
      $rightParenthesisPosition = strpos($string, ')', $offset);
      //trigger_error("$leftParenthesisPosition < $rightParenthesisPosition");
      if ($leftParenthesisPosition < $rightParenthesisPosition) {
        $count++;
        $offset = $leftParenthesisPosition + 1;
      } else {
        $count--;
        $offset = $rightParenthesisPosition + 1;
      }
      //trigger_error($count);
    }

    return $rightParenthesisPosition;
  }

  protected function findNextKey($string) {
    $outOfBoundsPosition = strlen($string)+1;
    $pos = min(
      strpos($string, '(') ?: $outOfBoundsPosition,
      strpos($string, ')') ?: $outOfBoundsPosition,
      strpos($string, '.') ?: $outOfBoundsPosition,
      strpos($string, ',') ?: $outOfBoundsPosition
    );
    if ($pos === false) {
      trigger_error("ERROR: Cannot find key in string. '$string'");
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_PARSE_STRING, $string);
    }
    return [substr($string, $pos, 1), $pos];
  }
}
