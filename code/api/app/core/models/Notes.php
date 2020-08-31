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
  * class Notes
  */
class Notes extends \App\Models\BaseCoreModel
{
  /**
    * @var int
    */
  protected $id;

  /**
    * @var string
    */
  protected $title;

  /**
    * @var string
    */
  protected $description;

  /**
    * @var DateTime
    */
  protected $created_timestamp;

  protected $dateTimeFields = ['created_timestamp'];

  /**
    * {@inheritDoc}
    */
  public function initialize()
  {
    $this->setSource('notes');
    $this->hasMany(
      'id',
      \App\Models\NoteTagLkp::class,
      'note_id', [
        'foreignKey' => [
          'action' => Relation::ACTION_CASCADE,
        ],
        'alias' => 'NoteTagLkp',
      ]
    );
    $this->hasManyToMany(
      'id',
      \App\Models\NoteTagLkp::class,
      'note_id', 'tag_id',
      \App\Models\Tags::class,
      'id', [
        'alias' => 'Tags'
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
    * @return Notes
    */
  public function setId($value)
  {
    $this->id = $value;
    return $this;
  }

  /**
    * Gets title
    * @return string
    */
  public function getTitle()
  {
    return $this->title;
  }

  /**
    * Sets title
    * @param $value
    * @return Notes
    */
  public function setTitle($value)
  {
    $this->title = $value;
    return $this;
  }

  /**
    * Gets description
    * @return string
    */
  public function getDescription()
  {
    return $this->description;
  }

  /**
    * Sets description
    * @param $value
    * @return Notes
    */
  public function setDescription($value)
  {
    $this->description = $value;
    return $this;
  }

  /**
    * Gets created_timestamp
    * @return DateTime
    */
  public function getCreatedTimestamp()
  {
    return $this->created_timestamp;
  }

  /**
    * Sets created_timestamp
    * @param $value
    * @return Notes
    */
  public function setCreatedTimestamp($value)
  {
    $this->created_timestamp = $value;
    return $this;
  }

}
