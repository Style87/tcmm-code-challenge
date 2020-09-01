<style>
.btn {
  border: 1px solid black;
}
</style>
<script>
  // stores
  import { tagsStore } from '../../stores/Tags.js';

  // collections
  import {noteCollection} from '../../collections/NoteCollection.js';
  import {TagCollection} from '../../collections/TagCollection.js';

  // models
  import {TagModel} from '../../models/TagModel.js';

  // components
  import Tag from '../Tag.svelte';

  let newTagName = '';

let tagCollection = new TagCollection(tagsStore);
tagCollection.get();

function onAddTag(event) {
  let tagModel = new TagModel();
  tagModel.tag_name = newTagName;
  tagModel.post({}, (response) => {
    newTagName = '';
    tagCollection.append(response);
  });
}

function deleteTag(event) {
  let tagModel = new TagModel();
  tagModel.id = event.detail.id;
  tagModel.delete({}, (response) => {
    tagsStore.update((store) => {
      store = store.filter(( tag ) => tag.id !== event.detail.id);
      return store;
    });

    noteCollection.get();
  });
}
</script>
<div class="row">
  <div class="col s12">
  <form class="form-inline">
    <div class="form-group mx-sm-3 mb-2">
      <input type="text" id="tag_name" class="form-control" name="tag_name" placeholder="Type in tag name" bind:value={newTagName}>
    </div>
    <button class="btn btn-default mb-2" on:click|preventDefault|stopPropagation={onAddTag}>Add Tag</button>
    </form>
  </div>
</div>
{#each $tagsStore as tag}
  <Tag tag={tag} on:onDeleteTag={deleteTag}/>
{/each}
