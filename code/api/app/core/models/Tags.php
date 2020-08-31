<?php
namespace App\Core\Models;

use Phalcon\Mvc\Model\Relation;
use Phalcon\Validation;
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Digit as DigitValidator;
use Phalcon\Validation\Validator\Numericality;


/**
  * class Tags
  */
class Tags extends \App\Models\BaseCoreModel
{
  /**
    * @var int
    */
  protected $id;

  /**
    * @var string
    */
  protected $tag_name;

  /**
    * {@inheritDoc}
    */
  public function initialize()
  {
    $this->setSource('tags');
    $this->hasMany(
      'id',
      \App\Models\NoteTagLkp::class,
      'tag_id', [
        'foreignKey' => [
          'action' => Relation::ACTION_CASCADE,
        ],
        'alias' => 'NoteTagLkp',
      ]
    );
    $this->hasManyToMany(
      'id',
      \App\Models\NoteTagLkp::class,
      'tag_id', 'note_id',
      \App\Models\Notes::class,
      'id', [
        'alias' => 'Notes'
      ]
    );

  }
  public function validation()
  {
    $validator = new Validation();
    $validator->add(
      'id',
      new DigitValidator([
        'message' => ':field must be an integer',
        'allowEmpty' => true,
      ])
    );
    return $this->validate($validator);
  }

  /**
    * Get model attributes
    * @return array
    */
  public function getAttributes()
  {
    $metaData = $this->getModelsMetaData();
    return $metaData->getNonPrimaryKeyAttributes($this);
  }

  /**
    * Gets id
    * @return int
    */
  public function getId()
  {
    return $this->id;
  }

  /**
    * Sets id
    * @param $value
    * @return Tags
    */
  public function setId($value)
  {
    $this->id = $value;
    return $this;
  }

  /**
    * Gets tag_name
    * @return string
    */
  public function getTagName()
  {
    return $this->tag_name;
  }

  /**
    * Sets tag_name
    * @param $value
    * @return Tags
    */
  public function setTagName($value)
  {
    $this->tag_name = $value;
    return $this;
  }

}
