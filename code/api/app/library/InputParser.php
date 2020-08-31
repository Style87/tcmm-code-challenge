<?php
namespace Phalcon;

use \DomainException;

use \App\Exceptions\AppException;

use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Paginator\Factory;

// Object(attribute, RelatedObject(attribute).action(key, value, value), attribute).action(key, value, value):Object().join(Object(attribute), attribute)

class InputParser
{
  protected $inputString;

  private $workingString = '';
  private $parts = [];
  private $leftParenthesisCount = 0;
  private $isAction = false;

  private $attributes;

  public $className;
  public $classFullName;
  public $select = [];
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
    $this->reset($inputString);
  }

  public function reset($inputString) {
    $this->inputString = $inputString;
    $this->workingString = $inputString;
    $this->leftParenthesisCount = 0;
    $this->className = null;
    $this->classFullName = null;
    $this->select = [];
    $this->columns = [];
    $this->conditions = null;
    $this->bind = [];
    $this->join = [];
    $this->order = null;
    $this->groupBy = null;
    $this->having = null;
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
    $this->parseActions();

    if (count($this->columns) == 0) {
      $this->columns = null;
    }

    $return = [
      $this->className,
      [
        'from'       => $this->classFullName,
        'select'    => $this->select,
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

    return [$this->classFullName, "{$this->classFullName}.{$this->columns[0]}"];
  }

  private function parseClass() {
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

  // Users()
  // Users(isActive, Fans())
  // Users(isActive, Fans(id,username))
  // Users(isActive, Fans(id,name)).where(followerUserId=86)
  // Users(isActive, Fans()).where(followerUserId=86):Idols().where(followedUserId=86)
  // Users():Fans(id, Users()):Idols(id, Users(id)):Pings(id, Users())
  // Users():Fans(id, Users()):Idols(id, Users(id)):Pings(id, Users(id, Fans(followedUserId, followerUserId).whereEq(followerUserId,5)), lat, lng)

  private function parseColumns() {
    // trigger_error('parseColumns');
    // trigger_error("WORKING: {$this->workingString}");
    $relations = [];

    // Find columns closing parenthesis
    $closingParenthesisPosition = $this->findMatchingParenthesisPosition($this->workingString, 1);
    // trigger_error($closingParenthesisPosition . ' - ' . strlen($this->workingString));
    if (($closingParenthesisPosition + 1) == 1) {
      // trigger_error('No columns.');
      $this->workingString = substr_replace($this->workingString, '', 0, $closingParenthesisPosition+1);
      return false;
    }

    // Separate the columns string
    $columnsString = trim(substr($this->workingString, 0, $closingParenthesisPosition+1));
    // Remove the columns string from the working string
    $this->workingString = substr_replace($this->workingString, '', 0, $closingParenthesisPosition+2);

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

          $inputParser = new InputParser(substr($columnsString, 0, $closingParenthesisPosition+1));
          list($key, $relation) = $inputParser->parse();
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
  private function parseActions() {
    // trigger_error("Parse Actions: '{$this->workingString}' " . strlen($this->workingString));
    if (strlen($this->workingString) == 0) {
      return false;
    }

    // Build a regex to split the actions out
    $regexString = '/\.(';
    $regexString .= 'select';
    $regexString .= '|eq|orEq|where|whereEq|orWhereEq';
    $regexString .= '|ne|orNe|whereNe|orWhereNe';
    $regexString .= '|gt|orGt|whereGt|orWhereGt';
    $regexString .= '|lt|orLt|whereLt|orWhereLt';
    $regexString .= '|gtEq|orGtEq|whereGtEq|orWhereGtEq';
    $regexString .= '|ltEq|orLtEq|whereLtEq|orWhereLtEq';
    $regexString .= '|like|orLike|whereLike|orWhereLike';
    $regexString .= '|between|orBetween|whereBetween|orWhereBetween';
    $regexString .= '|in|orIn|whereIn|orWhereIn';
    $regexString .= '|noIn|orNotIn|whereNotIn|orWhereNotIn';
    $regexString .= '|withinBox|orWithinBox';
    $regexString .= '|isNull|orIsNull';
    $regexString .= '|isNotNull|orIsNotNull';
    $regexString .= '|order';
    $regexString .= '|page|offset';
    $regexString .= '|perPage|limit';
    $regexString .= '|whereGroup|wsg';
    $regexString .= '|whereGroupEnd|wge';
    $regexString .= '|havingEq|orHavingEq';
    $regexString .= '|havingNe|orHavingNe';
    $regexString .= '|havingGt|orHavingGt';
    $regexString .= '|havingLt|orHavingLt';
    $regexString .= '|havingGtEq|orHavingGtEq';
    $regexString .= '|havingLtEq|orHavingLtEq';
    $regexString .= '|havingBetween|orHavingBetween';
    $regexString .= '|havingIn|orHavingIn';
    $regexString .= '|havingNotIn|orHavingNotIn';
    $regexString .= '|groupBy';
    $regexString .= '|join';
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
    $metaData = new Mvc\Model\MetaData\Memory();
    $this->attributes = $metaData->getAttributes($object);

    foreach ($this->parts as $key => $value) {
      $this->parseAction($key, $value);
    }
  }

  private function parseAction($key, $value) {
    $part = $value;

    $actionNamePosEnd = strpos($part, '(');

    $actionName = substr($part, 0, $actionNamePosEnd);

    $part = trim(substr_replace($part, '', 0, $actionNamePosEnd+1));
    // Remove the last right parentheses with substr because rtrim will remove multiples
    $part = substr($part, 0, -1);
    $actionParts = array_map('trim', array_filter(explode(',', $part), function($value) { return $value !== ''; }));

    $select = false;
    $condition = false;
    $glue = 'AND';
    $order = false;
    $having = false;
    $groupBy = false;
    $perPage = false;
    $page = false;
    $bind = [];
    $join = [];
    switch($actionName) {
      case 'select':
        $select = $this->getSelect($key, $actionName, $actionParts);
      break;
      case 'where':
      case 'whereEq':
      case 'orWhere':
      case 'orWhereEq':
      case 'eq':
      case 'orEq':
        list($condition, $rBind) = $this->getWhereEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereNe':
      case 'orWhereNe':
      case 'ne':
      case 'orNe':
        list($condition, $rBind) = $this->getWhereEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereGt':
      case 'orWhereGt':
      case 'gt':
      case 'orGt':
        list($condition, $rBind) = $this->getWhereGt($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereLt':
      case 'orWhereLt':
      case 'lt':
      case 'orLt':
        list($condition, $rBind) = $this->getWhereLt($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereGtEq':
      case 'orWhereGtEq':
      case 'gtEq':
      case 'orGtEq':
        list($condition, $rBind) = $this->getWhereGtEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereLtEq':
      case 'orWhereLtEq':
      case 'ltEq':
      case 'orLtEq':
        list($condition, $rBind) = $this->getWhereLtEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereLike':
      case 'orWhereLike':
      case 'like':
      case 'orLike':
        list($condition, $rBind) = $this->getWhereLike($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereBetween':
      case 'orWhereBetween':
      case 'between':
      case 'orBetween':
        list($condition, $rBind) = $this->getWhereBetween($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereIn':
      case 'orWhereIn':
      case 'in':
      case 'orIn':
        list($condition, $rBind) = $this->getWhereIn($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereNotIn':
      case 'orWhereNotIn':
      case 'notIn':
      case 'orNotIn':
        list($condition, $rBind) = $this->getWhereNotIn($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'withinBox':
      case 'orWithinBox':
        list($condition, $rBind) = $this->getWithinBox($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'isNull':
      case 'orIsNull':
        list($condition, $rBind) = $this->getIsNull($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'isNotNull':
      case 'orIsNotNull':
        list($condition, $rBind) = $this->getIsNotNull($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'whereGroup':
      case 'wgs':
        $condition = '(';
      break;
      case 'whereGroupEnd':
      case 'wge':
        $condition = ')';
      break;
      case 'order':
        $order = $this->getOrder($key, $actionName, $actionParts);
      break;
      case 'limit':
      case 'perPage':
        $perPage = $this->getPerPage($key, $actionName, $actionParts);
      break;
      case 'page':
        $page = $this->getPage($key, $actionName, $actionParts);
      break;
      case 'offset':
        $page = $this->getPage($key, $actionName, $actionParts);
      break;
      case 'havingEq':
      case 'orHavingEq':
        list($having, $rBind) = $this->getHavingEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingNe':
      case 'orHavingNe':
        list($having, $rBind) = $this->getHavingNe($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingGt':
      case 'orHavingGt':
        list($having, $rBind) = $this->getHavingGt($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingLt':
      case 'orHavingLt':
        list($having, $rBind) = $this->getHavingLt($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingGtEq':
      case 'orHavingGtEq':
        list($having, $rBind) = $this->getHavingGtEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingLtEq':
      case 'orHavingLtEq':
        list($having, $rBind) = $this->getHavingLtEq($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingBetween':
      case 'orHavingBetween':
        list($having, $rBind) = $this->getHavingBetween($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingIn':
      case 'orHavingIn':
        list($having, $rBind) = $this->getHavingIn($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'havingNotIn':
      case 'orHavingNotIn':
        list($having, $rBind) = $this->getHavingNotIn($key, $actionName, $actionParts);
        $bind = array_merge($bind, $rBind);
      break;
      case 'groupBy':
        $groupBy = $this->getGroupBy($key, $actionName, $actionParts);
      break;
      case 'join':
        $join = $this->getjoin($key, $actionName, $actionParts);
      break;
      default:
        trigger_error("Unknown action: $actionName");
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ACTION, $actionName);
    }

    if (substr($actionName, 0, strlen('or')) === 'or') {
      $glue = 'OR';
    }

    if ($condition) {
      if ($this->conditions != null && !in_array($actionName, ['whereGroup', 'wgs', 'whereGroupEnd', 'wge']) && substr($this->conditions, -2) !== '( ') {
        $condition = "$glue $condition";
      }
      $this->conditions .= " $condition ";
      $this->bind = array_merge($this->bind, $bind);
    }
    else if ($order) {
      if ($this->order != null) {
        $order = ", $order";
      }
      $this->order .= $order;
    }
    else if (is_numeric($perPage)) {
      $this->perPage = $perPage;
    }
    else if (is_numeric($page)) {
      $this->page= $page;
    }
    else if ($having) {
      if ($this->having != null) {
        $having = "$glue $order";
      }
      $this->having .= $having;
    }
    else if ($groupBy) {
      if (is_null($this->groupBy)) {
        $this->groupBy = [];
      }
      $this->groupBy = array_merge($this->groupBy, $groupBy);
    }
    else if (count($join) > 0) {
      $this->join = array_merge($this->join, $join);
    }
    else if ($select !== false) {
      $this->select = array_merge($this->select, $select);
    }
  }

  private function findMatchingParenthesisPosition($string, $count = 0, $offset = 0) {
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

  private function findNextKey($string) {
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

  private function getSelect($key, $actionName, $actionParts = []) {
    if (count($actionParts) == 0) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    $select = [];
    foreach ($actionParts as $attribute) {
      if (!in_array($attribute, $this->attributes)) {
        try {
          $inputParser = new InputParser($attribute);
          list($_, $attribute) = $inputParser->parseObjectAttribute();
        }
        catch(AppException $e) {
          // TODO: This exception is misleading as there could be a proper exception from the InputParser
          throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $attribute);
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

    return $select;
  }

  private function getWhereEq($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} = :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereNe($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} != :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereGt($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} > :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereLt($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} < :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereGtEq($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} >= :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereLtEq($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} <= :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereLike($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} LIKE :{$actionName}_part_$key:", ["{$actionName}_part_$key" => $actionParts[1]]];
  }

  private function getWhereBetween($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 3) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 3);
    }

    $bind = [];

    if (in_array($actionParts[0], $this->attributes)) {
      $sql1 = "{$this->classFullName}.{$actionParts[0]}";
    }
    else {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $sql1) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        $sql1 = ":between_part1_$key:";
        $bind["between_part1_$key"] = $actionParts[0];
      }
    }

    if (in_array($actionParts[1], $this->attributes)) {
      $sql2 = "{$this->classFullName}.{$actionParts[1]}";
    }
    else {
      try {
        $inputParser = new InputParser($actionParts[1]);
        list($_, $sql2) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        $sql2 = ":between_part2_$key:";
        $bind["between_part2_$key"] = $actionParts[1];
      }
    }

    if (in_array($actionParts[2], $this->attributes)) {
      $sql3 = "{$this->classFullName}.{$actionParts[2]}";
    }
    else {
      try {
        $inputParser = new InputParser($actionParts[2]);
        list($_, $sql3) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        $sql3 = ":between_part3_$key:";
        $bind["between_part3_$key"] = $actionParts[2];
      }
    }

    return ["$sql1 BETWEEN $sql2 AND $sql3", $bind];
  }

  private function getWhereIn($key, $actionName, $actionParts = []) {
    if (count($actionParts) <= 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }

    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    $bind = [];
    $condition = "{$actionParts[0]} IN (";
    for ($index =1; $index < count($actionParts); $index++) {
      $condition .= ":in_{$key}_{$index}:,";
      $bind["in_{$key}_{$index}"] = $actionParts[$index];
    }
    $condition = rtrim($condition,',');
    $condition .= ")";

    return [$condition, $bind];
  }

  private function getWhereNotIn($key, $actionName, $actionParts = []) {
    if (count($actionParts) <= 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    $bind = [];
    $condition = "{$actionParts[0]} NOT IN (";
    for ($index =1; $index < count($actionParts); $index++) {
      $condition .= ":in_{$key}_{$index}:,";
      $bind["in_{$key}_{$index}"] = $actionParts[$index];
    }
    $condition = rtrim($condition,',');
    $condition .= ")";

    return [$condition, $bind];
  }

  private function getWithinBox($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 5) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 5);
    }

    // Ensure all points are numeric because they have to be directly concantinated with the query
    if (!(is_numeric($actionParts[1]) && is_numeric($actionParts[2]) && is_numeric($actionParts[3]) && is_numeric($actionParts[4]))) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 5);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    $bind = [];

    $box = "ST_GEOMFROMTEXT('POLYGON(({$actionParts[1]} {$actionParts[2]}, {$actionParts[3]} {$actionParts[2]}, {$actionParts[3]} {$actionParts[4]}, {$actionParts[1]} {$actionParts[4]}, {$actionParts[1]} {$actionParts[2]}))')";
    $condition = "ST_WITHIN({$actionParts[0]}, $box)";

    return [$condition, $bind];
  }

  private function getIsNull($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} IS NULL", []];
  }

  private function getIsNotNull($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    return ["{$actionParts[0]} IS NOT NULL", []];
  }

  private function getOrder($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 2) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[0], $this->attributes)) {
      try {
        $inputParser = new InputParser($actionParts[0]);
        list($_, $actionParts[0]) = $inputParser->parseObjectAttribute();
      }
      catch(AppException $e) {
        // TODO: This exception is misleading as there could be a proper exception from the InputParser
        throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[0]);
      }
    }
    else {
      $actionParts[0] = "{$this->classFullName}.{$actionParts[0]}";
    }

    $value = strtoupper($actionParts[1]);
    if ($value != 'ASC' && $value != 'DESC') {
      trigger_error("Invalid order direction ($value) for action: " . $part);
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ORDER, $value);
    }
    return "{$actionParts[0]} $value";
  }

  private function getPerPage($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    if (!is_numeric($actionParts[0])) {
      // throw exception
    }

    if ($actionParts[0] <= 0) {
      // throw exception
    }

    return $actionParts[0];
  }

  private function getPage($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    if (!is_numeric($actionParts[0])) {
      // throw exception
    }

    if ($actionParts[0] <= 0) {
      // throw exception
    }

    return $actionParts[0];
  }

  private function getOffset($key, $actionName, $actionParts = []) {
    if (count($actionParts) != 1) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    if (!is_numeric($actionParts[0])) {
      // throw exception
    }

    if ($actionParts[0] < 0) {
      // throw exception
    }

    if (is_null($this->perPage)) {
      // throw exception
    }

    if ($actionParts[0] % $this->perPage != 0) {
      // throw exception
    }

    return ($actionParts[0] % $this->perPage) + 1;
  }

  private function getHavingEq($key, $actionName, $actionParts = []) {
    return $this->getWhereEq($key, $actionName, $actionParts);
  }

  private function getHavingNe($key, $actionName, $actionParts = []) {
    return $this->getWhereNe($key, $actionName, $actionParts);
  }

  private function getHavingGt($key, $actionName, $actionParts = []) {
    return $this->getWhereGt($key, $actionName, $actionParts);
  }

  private function getHavingLt($key, $actionName, $actionParts = []) {
    return $this->getWhereLt($key, $actionName, $actionParts);
  }

  private function getHavingGtEq($key, $actionName, $actionParts = []) {
    return $this->getWhereGtEq($key, $actionName, $actionParts);
  }

  private function getHavingLtEq($key, $actionName, $actionParts = []) {
    return $this->getWhereLtEq($key, $actionName, $actionParts);
  }

  private function getHavingBetween($key, $actionName, $actionParts = []) {
    return $this->getWhereBetween($key, $actionName, $actionParts);
  }

  private function getHavingIn($key, $actionName, $actionParts = []) {
    return $this->getWhereIn($key, $actionName, $actionParts);
  }

  private function getHavingNotIn($key, $actionName, $actionParts = []) {
    return $this->getWhereNotIn($key, $actionName, $actionParts);
  }

  private function getGroupBy($key, $actionName, $actionParts = []) {
    if (count($actionParts) == 0) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 1);
    }

    foreach ($actionParts as $key => $value) {
      if (!in_array($actionParts[$key], $this->attributes)) {
        try {
          $inputParser = new InputParser($actionParts[$key]);
          list($_, $actionParts[$key]) = $inputParser->parseObjectAttribute();
        }
        catch(AppException $e) {
          // TODO: This exception is misleading as there could be a proper exception from the InputParser
          throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[$key]);
        }
      }
      else {
        $actionParts[$key] = "{$this->classFullName}.{$actionParts[$key]}";
      }
    }

    return $actionParts;
  }

  // .join(Object(attribute), attribute)
  private function getJoin($key, $actionName, $actionParts = []) {
    if (count($actionParts) < 2 || count($actionParts) > 3) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_INCORRECT_ACTION_PARTS, count($actionParts), $actionName, 2);
    }

    if (!in_array($actionParts[1], $this->attributes)) {
      throw AppException::sprintf(AppException::EMSG_INPUT_PARSER_UNKNOWN_ATTRIBUTE, $actionParts[1]);
    }

    $ip = new InputParser($actionParts[0]);
    list($classFullName, $joinCondition) = $ip->parseObjectAttribute();

    $condition = [
      'condition' => "{$joinCondition} = {$this->classFullName}.{$actionParts[1]}",
      'left' => false,
    ];

    if (count($actionParts) == 3 && $actionParts[2] == 'left') {
      $condition['left'] = true;
    }

    return [$classFullName => $condition];
  }
}
