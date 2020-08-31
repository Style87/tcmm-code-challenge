<?php
namespace Phalcon\OMA;

use \DomainException;

use \App\Exceptions\AppException;

use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Paginator\Factory;

// Object(attribute, RelatedObject(attribute).action(key, value, value), attribute).action(key, value, value):Object().join(Object(attribute), attribute)

class ObjectMappingInterface
{
  public $className = '';
  public $selectString = '';
  public $actionString = '';

  /**
   * Constructor
   * @param array $parameters The parameters passed during construction
  */
  public function __construct($class)
  {
    $this->className = (substr($class, strrpos($class, '\\') + 1));
    $this->selectString = '';
    $this->actionString = '';
  }

  public function reset() {
    $this->className = '';
    $this->selectString = '';
    $this->actionString = '';
  }

  public function __toString() {
    return "{$this->className}({$this->selectString}){$this->actionString}";
  }

  public function setClass($class) {
    $this->className = (substr($class, strrpos($class, '\\') + 1));
    return this;
  }

  public function select($attribute) {
    if (!empty($this->selectString)) {
      $this->selectString .= ',';
    }
    $this->selectString .= $attribute;
    return $this;
  }

  public function __call($name, $arguments) {
    foreach (ObjectActions::ACTIONS as $action => $actionAliasList) {
      foreach ($actionAliasList as $action) {
        if ($action == $name) {
          $arguments = array_map(function ($entry) {
            return (is_array($entry)) ? implode(',',$entry):$entry;
          }, $arguments);
          $this->actionString .= ".{$action}(" . implode(',', $arguments) . ')';
          return $this;
        }
      }
    }

    die("COULDN'T FIND ACTION {$name}");
  }

  public function join($object, $objectAttribute, $attribute){
    $this->actionString .= ".".__FUNCTION__."({$object}({$objectAttribute}),{$attribute})";
    return $this;
  }

  public function leftJoin($object, $objectAttribute, $attribute){
    $this->actionString .= ".".__FUNCTION__."({$object}({$objectAttribute}),{$attribute})";
    return $this;
  }

  public function relation(ObjectMappingInterface $omi){
    $this->actionString .= ".".__FUNCTION__."({$omi})";
    return $this;
  }

  public function havingManyIn($object, $objectAttribute, $attribute, $objectManyAttribute, array $list){
    $this->actionString .= ".".__FUNCTION__."({$object}({$objectAttribute}),{$attribute},{$object}({$objectManyAttribute}),".implode(',', $list).")";
    return $this;
  }
}
