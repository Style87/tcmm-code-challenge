<?php
namespace Phalcon\OMA;

class ActionResponse
{
  private $select = false;
  private $distinct = false;
  private $condition = false;
  private $order = false;
  private $having = false;
  private $groupBy = false;
  private $perPage = false;
  private $page = false;
  private $bind = [];
  private $join = [];
  private $relation = [];

  public function __construct($hash = []) {
    foreach($hash as $key => $value) {
      $this->$key = $value;
    }
  }

  public function getSelect() {
    return $this->select;
  }

  public function setSelect($select) {
    $this->select = $select;
  }

  public function getDistinct() {
    return $this->distinct;
  }

  public function setDistinct($distinct) {
    $this->distinct = $distinct;
  }

  public function getCondition() {
    return $this->condition;
  }

  public function setCondition($condition) {
    $this->condition = $condition;
  }

  public function getOrder() {
    return $this->order;
  }

  public function setOrder($order) {
    $this->order = $order;
  }

  public function getHaving() {
    return $this->having;
  }

  public function setHaving($having) {
    $this->having = $having;
  }

  public function getGroupBy() {
    return $this->groupBy;
  }

  public function setGroupBy($groupBy) {
    $this->groupBy = $groupBy;
  }

  public function getPerPage() {
    return $this->perPage;
  }

  public function setPerPage($perPage) {
    $this->perPage = $perPage;
  }

  public function getPage() {
    return $this->page;
  }

  public function setPage($page) {
    $this->page = $page;
  }

  public function getBind() {
    return $this->bind;
  }

  public function setBind($bind) {
    $this->bind = $bind;
  }

  public function getJoin() {
    return $this->join;
  }

  public function setJoin($join) {
    $this->join = $join;
  }

  public function getRelation() {
    return $this->relation;
  }

  public function setRelation($relation) {
    $this->relation = $relation;
  }
}
