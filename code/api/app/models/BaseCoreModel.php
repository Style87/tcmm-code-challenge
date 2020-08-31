<?php
namespace App\Models;

use Phalcon\FormatLibrary;
use Phalcon\Mvc\Model\Relation;
use App\Interfaces\History;
use App\Library\StringHelper;

class BaseCoreModel extends \Phalcon\Mvc\Model
{
  protected $dateTimeFields = [];

  public static function getBuilder(\Phalcon\Mvc\Model\Query\Builder $builder) {
    return $builder;
  }

  /**
   * Allows to query a set of records that match the specified conditions
   *
   * @param mixed $parameters
   * @return CollectionPings[]
   */
  public static function find($parameters = null)
  {
    $class = get_called_class();
    $parameters = $class::setAccessControlParameters($parameters);
    return parent::find($parameters);
  }

  /**
   * Allows to query the first record that match the specified conditions
   *
   * @param mixed $parameters
   * @return CollectionPings
   */
  public static function findFirst($parameters = null)
  {
    $class = get_called_class();
    $parameters = $class::setAccessControlParameters($parameters);
    return parent::findFirst($parameters);
  }

  public static function setAccessControlParameters($parameters = null) {
    return $parameters;
  }

  public function isEditableBy($userId = null) {
    if (!isset($this->userId) || is_null($userId)) {
      return false;
    }

    return $this->userId == $userId;
  }

  /**
   * Find the object by id using a builder.
   * @param  integer $id The id of the object.
   * @method GET
   * @return mixed Either boolean false or the object selected.
   */
  public static function findById($id) {
    $modelsManager = \Phalcon\DI::getDefault()->getShared('modelsManager');
    $class = get_called_class();
    return $class::getBuilder($modelsManager->createBuilder())
      ->from($class)
      ->andWhere('id = :id:', ['id' => $id])
      ->getQuery()
      ->getSingleResult();
  }

  public function delete() {
    $db = \Phalcon\DI::getDefault()->getShared('db');
    $db->query("SET FOREIGN_KEY_CHECKS=0");
    $_r = parent::delete();
    $db->query("SET FOREIGN_KEY_CHECKS=1");
    return $_r;
  }

  public function convertDateTimeFields($format = 'c') {
    foreach ($this->dateTimeFields as $dateTimeField) {
      if (isset($this->$dateTimeField)) {
        $this->$dateTimeField = date($format, strtotime($this->$dateTimeField));
      }
    }
  }

  public function afterFetch()
  {
    $this->convertDateTimeFields('c');
  }

  public function save($data = null, $whiteList = null)
  {
    $this->convertDateTimeFields('Y-m-d H:i:s');
    $return = parent::save($data, $whiteList);
    $this->afterFetch();

    if ($return === false) {
      $error = '';
      foreach ($this->getMessages() as $message) {
        $error .=$message."\n";
      }

      error_log($error);
    }

    return $return;
  }

  public function afterSave() {
    if ($this instanceof History && array_key_exists($this->getOperationMade(), HistoryRecords::ACTIONS)) {
      $historyRecord = new HistoryRecords();
      $historyRecord->setHistorySource($this->getSource());
      $historyRecord->record = json_encode($this->toArray());
      $historyRecord->action = HistoryRecords::ACTIONS[$this->getOperationMade()];
      if ($this->getDI()->offsetExists('user')) {
        $historyRecord->userId = $this->getDI()->getShared('user')->id;
      }
      $historyRecord->save();
    }
  }

  public function toArray($columns = NULL, $relations = [])
  {
    $class = get_called_class();

    // Remove any blacklisted columns from the output
    if (isset($GLOBALS['OMA_COLUMN_BLACKLIST'][$class]) && !is_null($columns)) {
      $columnIntersection = array_intersect($columns, $GLOBALS['OMA_COLUMN_BLACKLIST'][$class]);
      $columns = array_diff($columns, $columnIntersection);
    }

    $return = parent::toArray($columns);

    // Remove blacklisted columns when all columns were selected
    if (isset($GLOBALS['OMA_COLUMN_BLACKLIST'][$class]) && is_null($columns)) {
      $blacklist = array_flip($GLOBALS['OMA_COLUMN_BLACKLIST'][$class]);
      $return = array_filter($return, function ($key) use ($blacklist) {
        return !array_key_exists($key, $blacklist);
      }, ARRAY_FILTER_USE_KEY);
    }

    $modelsManager = $this->modelsManager;

    $relationObject = false;
    foreach($relations as $relation => $relationData) {
      foreach ($this->getModelsManager()->getRelations($class) as $_relation) {
        if ($_relation->getOptions()['alias']  ==  $relation) {
          $relationObject = $_relation;
          break;
        }
      }

      if ($relationObject === false) {
        trigger_error("Couldn't find relation $relation on $class.");
        continue;
      }

      $joinField = $relationObject->getFields();

      if (is_null($this->$joinField)) {
        $return[$relation] = null;
        continue;
      }

      $relatedObjects = [];
      if (
        in_array($relationObject->getType(), [Relation::BELONGS_TO, Relation::HAS_ONE])
      ) {
        $whereString =
          $relationObject->getReferencedModel() . '.' .$relationObject->getReferencedFields() . ' = :joinField:';

        $from = $relationObject->getReferencedModel();
        $data = $from::getBuilder($this->modelsManager->createBuilder());

        $data
          ->from($relationObject->getReferencedModel())
          ->andWhere($whereString, ['joinField' => $this->$joinField])
          ->limit(1)
          ->offset(0);
      }
      else if (
        in_array($relationObject->getType(), [Relation::HAS_MANY])
      ) {
        $whereString =
          $relationObject->getReferencedModel() . '.' .$relationObject->getReferencedFields() . ' = :joinField:';

        $from = $relationObject->getReferencedModel();
        $data = $from::getBuilder($this->modelsManager->createBuilder());

        $data
          ->from($relationObject->getReferencedModel())
          ->andWhere($whereString, ['joinField' => $this->$joinField]);
      }
      else if (
        in_array($relationObject->getType(), [Relation::HAS_MANY_THROUGH])
        && $relation == $relationObject->getOptions()['alias']
      ) {
        $whereString = $relationObject->getIntermediateModel() . '.' . $relationObject->getIntermediateFields() . ' = ' . $this->$joinField;

        $joinStringReferenced =
          $relationObject->getReferencedModel() . '.' .$relationObject->getReferencedFields() . ' = ' .
          $relationObject->getIntermediateModel() . '.' .$relationObject->getIntermediateReferencedFields();

        $from = $relationObject->getReferencedModel();
        $data = $from::getBuilder($this->modelsManager->createBuilder());
        $data
          ->from($relationObject->getReferencedModel())
          ->join($relationObject->getIntermediateModel(), $joinStringReferenced)
          ->where($whereString);
      }

      if (isset($relationObject->getOptions()['conditions'])) {
        $data->andWhere($relationObject->getOptions()['conditions']);
      }

      $getRelatedMethod = 'getRelated'.StringHelper::getShortName($from);
      if (method_exists($this, $getRelatedMethod)) {
        $data = $this->$getRelatedMethod($data);
      }

      $formattedData = FormatLibrary::format($data, $relationData['columns'], $relationData['relations'], $relationData, $relationData['page'], $relationData['perPage']);
      if (
        (
          $modelsManager->existsBelongsTo($class, $relationData['from'])
          || $modelsManager->existsHasOne($class, $relationData['from'])
        )
        && is_array($formattedData)
        && count($formattedData) == 1
      ) {
        $formattedData = $formattedData[0];
      }

      $return[$relation] = $formattedData;
    }

    return $return;
  }
}
