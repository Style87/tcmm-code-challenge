<script>
  import NotesPane from './components/panes/NotesPane.svelte';
  import TagsPane from './components/panes/TagsPane.svelte';
</script>

<style>
.nav-link {
  text-transform: uppercase;
  color: #CCC;
  font-weight: bold;
}

.nav-link.active {
  color: #000;
}
</style>

<div class="container">
  <nav class="nav nav-fill">
    <a id="nav-link-notes" class="nav-link active" data-toggle="tab" href="#notes">notes</a>
    <a id="nav-link-tags" class="nav-link" data-toggle="tab" href="#tags">tags</a>
  </nav>

  <div class="tab-content">
    <div class="tab-pane container active" id="notes">
      <NotesPane />
    </div>
    <div class="tab-pane container fade" id="tags">
      <TagsPane />
    </div>
  </div>
</div>
