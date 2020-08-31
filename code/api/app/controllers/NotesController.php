<?php
namespace App\Controllers;

use App\Controllers\ControllerBase;

use App\Exceptions\AppException;

use App\Models\Notes;
use App\Models\NoteTagLkp;

use \Phalcon\Mvc\Model\Query\Builder;

class NotesController extends ControllerBase
{
  /**
   * Gets a note or notes.
   * @param  integer $id The id of the note to get.
   * @throws AppException::EMSG_GET_NOTES
   * @method GET
   * @return Notes|Array<Notes>
   */
  public function get($id = null)
  {
    $builder = $this->modelsManager->createBuilder()
      ->from('App\Models\Notes');
      $builder = Notes::getBuilder($builder);
      if (!empty($id))
      {
        $builder
          ->where('App\Models\Notes.id = :id:', ['id' => $id])
          ->limit(1)
          ->offset(0);
      }

      return $builder;
  }

  /**
   * Creates a note.
   * @throws AppException::EMSG_INCORRECT_INPUT
   * @throws AppException::EMSG_POST_NOTES
   * @method POST
   * @return Notes
   */
  public function post()
  {
    list($title, $description, $tag_ids) = $this->checkRequiredParameters(['title', 'description', 'tag_ids']);

    $note = new Notes();
    $note->title = $title;
    $note->description = $description;
    $note->created_timestamp = date("Y-m-d H:i:s", time());

    if($note->save() == false) {
      $error = '';
      foreach ($note->getMessages() as $message) {
        $error .=$message."\n";
      }

      throw new AppException( AppException::EMSG_POST_NOTES, $error );
    }

    $tag_id_array = explode(',', $tag_ids);
    foreach ($tag_id_array as $tag_id) {
      $note_tag_lkp = new NoteTagLkp();
      $note_tag_lkp->note_id = $note->getId();
      $note_tag_lkp->tag_id = $tag_id;

      if($note_tag_lkp->save() == false) {
        $error = '';
        foreach ($note_tag_lkp->getMessages() as $message) {
          $error .=$message."\n";
        }

        throw new AppException( AppException::EMSG_POST_NOTES, $error );
      }
    }

    $builder = $this->modelsManager->createBuilder()
      ->from('App\Models\Notes');
      $builder = Notes::getBuilder($builder);
      $builder
        ->where('App\Models\Notes.id = :id:', ['id' => $note->getId()])
        ->limit(1)
        ->offset(0);

      return $builder;
  }

  /**
   * Updates a note.
   * @param  integer $id The id of the note to update.
   * @throws AppException::EMSG_PUT_NOTES
   * @method PUT
   * @return Notes
   */
  public function put($id)
  {
    $title = $this->getInput('title');
    $description = $this->getInput('description');
    $tag_ids = $this->getInput('tag_ids');

    $note = Notes::findFirst($id);

    if ($note === false) {
      throw new AppException(AppException::EMSG_PUT_NOTES);
    }

    if ($title !== NULL)
      $note->title = $title;
    if ($description !== NULL)
      $note->description = $description;

    if($note->update() == false) {
      $error = '';
      foreach ($note->getMessages() as $message) {
        $error .=$message."\n";
      }
      throw new AppException( AppException::EMSG_PUT_NOTES, $error);
    }

    if ($tag_ids !== NULL)
    {
      $noteTags = NoteTagLkp::find([
        'note_id = :note_id:',
        'bind' => array('note_id' => $note->getId())
      ]);

      // Delete current note tags
      foreach($noteTags as $noteTag)
      {
        $noteTag->delete();
      }

      // Update the new Tags
      foreach($tag_ids as $tag_id)
      {
        $noteTagLkp = new NoteTagLkp();
        $noteTagLkp->note_id = $note->getId();
        $noteTagLkp->tag_id = $tag_ids;
        $noteTagLkp->save();
      }
    }

    return $note;
  }

  /**
   * Deletes a note.
   * @param  integer $id The id of the note to delete.
   * @throws AppException::EMSG_DELETE_NOTES
   * @method DELETE
   * @return null
   */
  public function delete($id)
  {
    $note = Notes::findFirst([
      'id = :id:',
      'bind' => ['id' => $id]
    ]);

    if ($note === false) {
      throw new AppException(AppException::EMSG_DELETE_NOTES);
    }

    $note->delete();

    return null;
  }
}
