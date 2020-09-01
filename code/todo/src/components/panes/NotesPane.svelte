<style>
  .btn-default {
    border: 1px solid black;
  }
</style>
<script>
  // stores
  import { tagsStore } from '../../stores/Tags.js';
  import { notesStore } from '../../stores/Notes.js';

  // collections
  import {noteCollection} from '../../collections/NoteCollection.js';

  // models
  import {NoteModel} from '../../models/NoteModel.js';
  import {TagModel} from '../../models/TagModel.js';

  // components
  import Note from '../Note.svelte';

  noteCollection.setParams({"omi":"Notes().order(created_timestamp,DESC).relation(Tags())"});
  noteCollection.get();

  let newTitle = '',
      newDescription = '',
      filterTagNames = '';

  function onAddNewNote() {
    let noteModel = new NoteModel();
    noteModel.title = newTitle;
    noteModel.description = newDescription;
    var checkboxes = document.getElementsByName('checkbox-note-tag');
    for (var i=0; i<checkboxes.length; i++) {
       if (checkboxes[i].checked) {
         let tag = new TagModel();
         tag.id = checkboxes[i].value
         noteModel.Tags.push(tag);
       }
    }

    noteModel.post({"omi":"Notes().relation(Tags())"}, (response) => {
      newTitle = '';
      newDescription = '';
      for (var i=0; i<checkboxes.length; i++) {
        checkboxes[i].checked = false;
      }
      noteCollection.prepend(response);
    })
  }

  function onFilterNotes() {
    var params = {"omi":`Notes().order(created_timestamp,DESC).relation(Tags())`};

    if (filterTagNames != '') {
      params.omi += `.join(NoteTagLkp(note_id), id).join(Tags(id), NoteTagLkp(tag_id)).in(Tags(tag_name),${filterTagNames})`;
    }

    noteCollection.setParams(params);
    noteCollection.get();
  }
</script>
<div class="row">
  <div class="col s12">
    <button id="btn-add-note" class="btn btn-default" data-toggle="modal" data-target="#modal-add-note">Add Note</button>
  </div>
</div>

<div class="row">
  <div class="col col-sm-12 col-md-11">
      <input type="text" id="input-note-filter" class="form-control" name="note-filter" placeholder="Type in tag(s) separated by a comma" bind:value={filterTagNames}>
  </div>
  <div class="col col-xs-12 col-md-1">
    <button class="btn btn-default mb-2" on:click|preventDefault|stopPropagation={onFilterNotes}>Filter</button>
  </div>
</div>
{#each $notesStore as note}
  <Note note={note} />
{/each}


<!-- Modal -->
<div class="modal fade" id="modal-add-note" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">ADD NOTE</h5>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group row">
          <label for="input-note-title" class="col-sm-2 col-form-label">Title</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="input-note-title" bind:value={newTitle}>
            </div>
          </div>
          <div class="form-group row">
            <label for="input-note-description" class="col-sm-2 col-form-label">Description</label>
            <div class="col-sm-10">
              <textarea rows="5" class="form-control" id="input-note-description" bind:value={newDescription}></textarea>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-2">Tags</div>
            <div class="col-sm-10">
              {#each $tagsStore as tag (tag.id)}
                <div class="form-check">
                  <input name="checkbox-note-tag" class="form-check-input" type="checkbox" id="checkbox-note-tag-{tag.id}" value="{tag.id}">
                  <label class="form-check-label" for="checkbox-note-tag-{tag.id}">
                    {tag.tag_name}
                  </label>
                </div>
              {/each}
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal" on:click={onAddNewNote}>Save changes</button>
      </div>
    </div>
  </div>
</div>
