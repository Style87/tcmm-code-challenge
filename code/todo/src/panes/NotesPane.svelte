<style>
#btn-add-note {
  border: 1px solid black;
}
</style>
<script>
import { onDestroy } from 'svelte';
import { initialValue, makeNotesStore } from '../stores/Notes.js';
import Note from '../Note.svelte';

let store = makeNotesStore();
let unsubscribe;
let notes = initialValue();

// Empty store exists - but fetches don't happen
// until subscription and we are not using
// auto-subscription here.

onDestroy(() => {
  if(unsubscribe) {
    unsubscribe();
    unsubscribe = null;
  }
});

function updateNotes(data) {
  // trigger component reactivity
  notes = data;
}

function handleClick() {
  if(!unsubscribe) {
    unsubscribe = store.subscribe(updateNotes);
  }
}

if(!unsubscribe) {
  unsubscribe = store.subscribe(updateNotes);
}
</script>
<div class="row">
  <div class="col s12">
    <button id="btn-add-note" class="btn btn-default">Add Note</button>
  </div>
</div>
{#each notes.notes as note}
  <Note note={note} />
{/each}
