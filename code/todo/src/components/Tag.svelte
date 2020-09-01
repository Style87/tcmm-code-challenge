<script>
  import { createEventDispatcher } from 'svelte';

  const dispatch = createEventDispatcher();

  export let tag;

  function onDelete(event) {
    dispatch('onDeleteTag', {id:tag.id});
  }
</script>

<div>
  <div class="row">
    <div class="col s12 col-md-auto">
      <h4>{tag.tag_name}</h4>
    </div>
    <div class="col s12 m6 text-right text-secondary">
      <a href="#" on:click|stopPropagation|preventDefault={onDelete}>delete</a>
    </div>
  </div>
</div>
