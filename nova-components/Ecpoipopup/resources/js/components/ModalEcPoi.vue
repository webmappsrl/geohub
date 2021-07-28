<template>
  <div id="EcPoiModal" class="modal" tabindex="-1" role="dialog">
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
              <a class="nav-link active" id="associatedPoi-tab" data-toggle="tab" href="#associatedPoi" role="tab"
                 aria-controls="associatedPoi"
                 aria-selected="true">Poi associati alla Track</a>
            </li>
          </ul>
          <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="associatedPoi" role="tabpanel"
                 aria-labelledby="associatedPoi-tab">
              associatedPoi
            </div>
            <div class="tab-pane fade" id="uploadPoi" role="tabpanel" aria-labelledby="uploadPoi-tab">
              uploadPoi
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