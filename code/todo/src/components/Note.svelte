<script>
  export let note;

  const d = new Date(note.created_timestamp);
  const options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    dayPeriod: 'short'
  };
  const dateTimeFormat  = new Intl.DateTimeFormat('en-US', options);
  const [{ value: month },,{ value: day },,{ value: year },,{ value: hour },,{ value: minute },,{ value: dayPeriod }] = dateTimeFormat .formatToParts(d)

</script>

<div>
  <div class="row">
    <div class="col s12 col-md-auto">
      <h3>{note.title}</h3>
    </div>
    <div class="col s12 m6 text-right text-secondary">
      Created: {hour}:{minute}{dayPeriod.toLowerCase()} {year}/{month}/{day}
    </div>
  </div>
  <div class="row">
    <div class="col s12">
      {note.description}
    </div>
  </div>
  <div class="row">
      {#each note.Tags as tag}
        <div class="col col-sm-auto">
          <span class="badge badge-secondary">{tag.tag_name}</span>
        </div>
      {/each}
  </div>
  <div class="row">
    <div class="col s12">
      <hr>
    </div>
  </div>
</div>
