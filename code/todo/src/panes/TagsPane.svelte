<style>
#btn-add-note {
  border: 1px solid black;
}
</style>
<script>
import { onDestroy } from 'svelte';
import { initialValue, makeTagsStore } from '../stores/Tags.js';
import Tag from '../Tag.svelte';

let store = makeTagsStore();
let unsubscribe;
let tags = initialValue();

// Empty store exists - but fetches don't happen
// until subscription and we are not using
// auto-subscription here.

onDestroy(() => {
  if(unsubscribe) {
    unsubscribe();
    unsubscribe = null;
  }
});

function updateTags(data) {
  // trigger component reactivity
  tags = data;
}

if(!unsubscribe) {
  unsubscribe = store.subscribe(updateTags);
}
</script>
<div class="row">
  <div class="col s12">
    <button id="btn-add-note" class="btn btn-default">Add Tag</button>
  </div>
</div>
{#each tags.tags as tag}
  <Tag tag={tag} />
{/each}
