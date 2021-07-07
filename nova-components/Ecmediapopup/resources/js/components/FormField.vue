<template>
  <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
    <template slot="field">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#EcMediaModal">
        Select EcMedia
      </button>


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
                  <a class="nav-link active" id="associatedMedia-tab" data-toggle="tab" href="#associatedMedia"
                     role="tab"
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
                  <div class="row pt-2">
                    <div class="col-md-6">
                      Media images
                    </div>
                    <div class="col-md-6">
                      Medei maps
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="uploadMedia" role="tabpanel" aria-labelledby="uploadMedia-tab">
                  <div class="row pt-2">
                    <div class="col-md-12"></div>
                    uploadMedia
                  </div>
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
  </default-field>
</template>

<script>
import {FormField, HandlesValidationErrors} from 'laravel-nova'

import EcMediaModal from './ModalEcMedia';

export default {
  mixins: [FormField, HandlesValidationErrors],

  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      modalOpen: false
    }
  },
  mounted() {
  },
  components: {
    EcMediaModal
  },
  methods: {
    openModal() {
      this.modalOpen = true;
    },
    confirmModal() {
      this.modalOpen = false;
    },
    closeModal() {
      this.modalOpen = false;
    },
    /*
     * Set the initial, internal value for the field.
     */
    setInitialValue() {
      this.value = this.field.value || ''
    },

    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      formData.append(this.field.attribute, this.value || '')
    },
  },
}
</script>
