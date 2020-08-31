<?php
namespace App\Controllers;


use App\Controllers\ControllerBase;

use App\Exceptions\AppException;

use App\Models\Tags;
use App\Models\NoteTagLkp;

use \Phalcon\Mvc\Model\Query\Builder;

class TagsController extends ControllerBase
{
  /**
   * Gets a tag or tags.
   * @param  integer $id The id of the tag to get.
   * @throws AppException::EMSG_GET_TAGS
   * @method GET
   * @return Tags|Array<Tags>
   */
  public function get($id = null)
  {
    $builder = $this->modelsManager->createBuilder()
      ->from('App\Models\Tags');
      $builder = Tags::getBuilder($builder);
      if (!empty($id))
      {
        $builder
          ->where('App\Models\Tags.id = :id:', ['id' => $id])
          ->limit(1)
          ->offset(0);
      }

      return $builder;
  }

  /**
   * Creates a tag.
   * @throws AppException::EMSG_INCORRECT_INPUT
   * @throws AppException::EMSG_POST_TAGS
   * @method POST
   * @return Tags
   */
  public function post()
  {
    list($tag_name) = $this->checkRequiredParameters(['tag_name']);

    $tag = Tags::findFirst([
      'tag_name = :tag_name:',
      'bind' => ['tag_name' => $tag_name]
    ]);

    if ($tag === FALSE) {
      $tag = new Tags();
      $tag->setTagName($tag_name);

      if($tag->save() == false) {
        $error = '';
        foreach ($tag->getMessages() as $message) {
          $error .=$message."\n";
        }

        throw new AppException( AppException::EMSG_POST_TAGS, $error );
      }
    }

    return $tag;
  }

  /**
   * Updates a tag.
   * @param  integer $id The id of the tag to update.
   * @throws AppException::EMSG_PUT_TAGS
   * @method PUT
   * @return Tags
   */
  public function put($id)
  {
    $tag_name = $this->getInput('tag_name');

    $tag = Tags::findFirst($id);

    if ($tag === false) {
      throw new AppException(AppException::EMSG_PUT_TAGS);
    }

    if ($tag_name !== NULL)
      $note->setTagName($tag_name);

    if($tag->update() == false) {
      $error = '';
      foreach ($tag->getMessages() as $message) {
        $error .=$message."\n";
      }
      throw new AppException( AppException::EMSG_PUT_TAGS, $error);
    }

    return $tag;
  }

  /**
   * Deletes a tag.
   *
   * Deleting a tag will delete all NoteTagLkp references from the tag.
   *
   * @param  integer $id The id of the tag to delete.
   * @throws AppException::EMSG_DELETE_TAGS
   * @method DELETE
   * @return null
   */
  public function delete($id)
  {
    $tag = Tags::findFirst([
      'id = :id:',
      'bind' => ['id' => $id]
    ]);

    if ($tag === false) {
      throw new AppException(AppException::EMSG_DELETE_TAGS);
    }

    $noteTagLkps = NoteTagLkp::find([
      'tag_id = :tag_id:',
      'bind' => ['tag_id' => $id]
    ]);

    foreach($noteTagLkps as $noteTagLkp) {
      $noteTagLkp->delete();
    }

    $tag->delete();

    return null;
  }
}
