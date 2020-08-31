<?php
namespace Phalcon;

use Phalcon\Mvc\Model\Manager as ModelManager;

class Manager extends ModelManager {
    public function getRelations($modelName) {
       $relations = parent::getRelations($modelName);
       $entity = strtolower($modelName);
       if(is_array($this->_hasManyToManySingle) && array_key_exists($entity, $this->_hasManyToManySingle)){
         foreach ($this->_hasManyToManySingle[$entity] as $relation){
           $relations[] = $relation;
         }
       }
       return $relations;
    }

    public function getRelationsBetween($first, $second) {
      $relations = parent::getRelationsBetween($first, $second);
      $entity = strtolower($first);
      if(is_array($this->_hasManyToManySingle) && array_key_exists($entity, $this->_hasManyToManySingle)){
        foreach ($this->_hasManyToManySingle[$entity] as $relation){
          $relations[] = $relation;
        }
      }
      return $relations;
    }
}
