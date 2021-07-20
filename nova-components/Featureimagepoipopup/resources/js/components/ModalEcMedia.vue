<template>
  <div id="EcMediaModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Modal title</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="associatedMedia-tab" data-toggle="tab" href="#associatedMedia" role="tab"
                 aria-controls="associatedMedia"
                 aria-selected="true">Media associati alla Track</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="uploadMedia-tab" data-toggle="tab" href="#uploadMedia" role="tab"
                 aria-controls="uploadMedia"
                 aria-selected="false">Carica Media</a>
            </li>
          </ul>
          <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="associatedMedia" role="tabpanel"
                 aria-labelledby="associatedMedia-tab">
              associatedMedia
            </div>
            <div class="tab-pane fade" id="uploadMedia" role="tabpanel" aria-labelledby="uploadMedia-tab">
              uploadMedia
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

</script>

<style scoped>
</style>