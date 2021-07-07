<template>
  <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
    <template slot="field">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#EcMediaModal">
        Select EcMedia
      </button>
      <div id="EcMediaModal" class="modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Modal title</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <p>Modal body text goes here.</p>
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
