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
  * class NoteTagLkp
  */
class NoteTagLkp extends \App\Models\BaseCoreModel
{
  /**
    * @var int
    */
  protected $note_id;

  /**
    * @var int
    */
  protected $tag_id;

  /**
    * {@inheritDoc}
    */
  public function initialize()
  {
    $this->setSource('note_tag_lkp');
    $this->hasOne('note_id', \App\Models\Notes::class, 'id', [
      'alias' => 'Notes'
    ]);
    $this->hasOne('tag_id', \App\Models\Tags::class, 'id', [
      'alias' => 'Tags'
    ]);

  }
  public function validation()
  {
    $validator = new Validation();
    $validator->add(
      'note_id',
      new PresenceOf([
        'message' => 'The :field attribute is required',
      ])
    );
    $validator->add(
      'note_id',
      new DigitValidator([
        'message' => ':field must be an integer',
      ])
    );
    $validator->add(
      'tag_id',
      new PresenceOf([
        'message' => 'The :field attribute is required',
      ])
    );
    $validator->add(
      'tag_id',
      new DigitValidator([
        'message' => ':field must be an integer',
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
    * Gets note_id
    * @return int
    */
  public function getNoteId()
  {
    return $this->note_id;
  }

  /**
    * Sets note_id
    * @param $value
    * @return NoteTagLkp
    */
  public function setNoteId($value)
  {
    $this->note_id = $value;
    return $this;
  }

  /**
    * Gets tag_id
    * @return int
    */
  public function getTagId()
  {
    return $this->tag_id;
  }

  /**
    * Sets tag_id
    * @param $value
    * @return NoteTagLkp
    */
  public function setTagId($value)
  {
    $this->tag_id = $value;
    return $this;
  }

}
