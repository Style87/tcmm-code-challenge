<style>
.btn-default {
  border: 1px solid black;
}
</style>
<script>
import { onDestroy } from 'svelte';
import { notesStore } from '../stores/Notes.js';
import { tagsStore } from '../stores/Tags.js';
import Note from '../Note.svelte';

let notes = [];
let unsubscribe;


let newTitle = '',
    newDescription = '';

onDestroy(() => {
  if(unsubscribe) {
    unsubscribe();
    unsubscribe = null;
  }
});

function updateNotes(data) {
  notes = data;
}

unsubscribe = notesStore.subscribe(updateNotes);

function onAddNewNote() {
  let formData = new FormData();
  formData.append("title", document.getElementById('input-note-title').value);
  formData.append("description", document.getElementById('input-note-description').value);

  var checkboxes = document.getElementsByName('checkbox-note-tag');
  var checkboxesChecked = [];
  for (var i=0; i<checkboxes.length; i++) {
     if (checkboxes[i].checked) {
        checkboxesChecked.push(checkboxes[i].value);
     }
  }

  formData.append('tag_ids', checkboxesChecked);

  var data = {};
  formData.forEach(function(value, key){
      data[key] = value;
  });

  const withQuery = (url, params) => {
    let query = Object.keys(params)
      .filter(k => params[k] !== undefined)
      .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
      .join('&');
    url += (url.indexOf('?') === -1 ? '?' : '&') + query;
    return url;
  };

  var params = {"omi":"Notes().relation(Tags())"};

  fetch(withQuery('/api/notes', params), {
    method: 'post',
    body: JSON.stringify(data),
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    }
  })
  .then(response => {
    if (response.ok) {
      return response.json();
    } else {
      const text = response.text();
      throw new Error(text);
    }
  })
  .then(data => {
    console.log(data);
      newTitle = '';
      newDescription = '';
      for (var i=0; i<checkboxes.length; i++) {
        checkboxes[i].checked = false;
      }
      notesStore.update((store) => {
        store.notes.unshift(data);
        return store;
      });
  })
}
function onFilterNotes() {
  // Update the notes store
  const withQuery = (url, params) => {
    let query = Object.keys(params)
      .filter(k => params[k] !== undefined)
      .map(k => encodeURIComponent(k) + '=' + encodeURIComponent(params[k]))
      .join('&');
    url += (url.indexOf('?') === -1 ? '?' : '&') + query;
    return url;
  };

  let tag_names = document.getElementById('input-note-filter').value;

  var params = {"omi":`Notes().order(created_timestamp,DESC).relation(Tags())`};

  if (tag_names != '') {
    params.omi += `.join(NoteTagLkp(note_id), id).join(Tags(id), NoteTagLkp(tag_id)).in(Tags(tag_name),${tag_names})`;
  }

  fetch(withQuery('/api/notes', params), {
    method: 'GET',
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    },
  }).then(response => {
    if (response.ok) {
      return response.json();
    } else {
      const text = response.text();
      throw new Error(text);
    }
  })
  .then(data => {
    notesStore.update((store) => {
      store.notes = data;
      return store;
    });
  });
}
</script>
<div class="row">
  <div class="col s12">
    <button id="btn-add-note" class="btn btn-default" data-toggle="modal" data-target="#modal-add-note">Add Note</button>
  </div>
</div>

<div class="row">
  <div class="col col-sm-12 col-md-11">
      <input type="text" id="input-note-filter" class="form-control" name="note-filter" placeholder="Type in tag(s) separated by a comma">
  </div>
  <div class="col col-xs-12 col-md-1">
    <button class="btn btn-default mb-2" on:click|preventDefault|stopPropagation={onFilterNotes}>Filter</button>
  </div>
</div>
{#each notes.notes as note}
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
              <input type="text" class="form-control" id="input-note-title" value={newTitle}>
            </div>
          </div>
          <div class="form-group row">
            <label for="input-note-description" class="col-sm-2 col-form-label">Description</label>
            <div class="col-sm-10">
              <textarea rows="5" class="form-control" id="input-note-description">{newDescription}</textarea>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-2">Tags</div>
            <div class="col-sm-10">
              {#each $tagsStore.tags as tag (tag.id)}
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
