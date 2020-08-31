<style>
.btn {
  border: 1px solid black;
}
</style>
<script>
import { onDestroy } from 'svelte';
import { store } from '../stores/Tags.js';
import Tag from '../Tag.svelte';

let tags = [];
let unsubscribe;

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

unsubscribe = store.subscribe(updateTags);

function onAddTag(event) {
  let data = new FormData();
  data.append("tag_name", document.getElementById('tag_name').value);
  fetch('/api/tags', {
    method: 'post',
    body: JSON.stringify({"tag_name": document.getElementById('tag_name').value}),
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
      store.update((store) => {
        store.tags.push(data);
        return store;
      });

      document.getElementById('tag_name').value = '';
  })
}

function deleteTag(event) {
  fetch('/api/tags/'+event.detail.id, {
    method: 'delete',
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json"
    }
  })
  .then(response => {
    if (response.ok) {
      return;
    } else {
      const text = response.text();
      throw new Error(text);
    }
  })
  .then(data => {
    store.update((store) => {
      store.tags = store.tags.filter(( tag ) => tag.id !== event.detail.id);
      return store;
    });
  })
}
</script>
<div class="row">
  <div class="col s12">
  <form class="form-inline">
    <div class="form-group mx-sm-3 mb-2">
      <input type="text" id="tag_name" class="form-control" name="tag_name" placeholder="Type in tag name">
    </div>
    <button class="btn btn-default mb-2" on:click|preventDefault|stopPropagation={onAddTag}>Add Tag</button>
    </form>
  </div>
</div>
{#each tags.tags as tag}
  <Tag tag={tag} on:onDeleteTag={deleteTag}/>
{/each}
