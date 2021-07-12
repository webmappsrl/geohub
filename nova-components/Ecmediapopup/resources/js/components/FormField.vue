<template>
  <div>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
      <template slot="field">
        <button type="button" class="btn btn-primary btn-default" @click="modalOpen = true">
          Select EcMedia
        </button>


      </template>
    </default-field>
    <template v-if="modalOpen">
      <div id="root" class="container">
        <transition name="modal">
          <div class="modal-mask">
            <div class="modal-wrapper">
              <div class="modal-container">

                <div class="modal-header">
                  <div style="display:flex">
                    <tabs>
                      <tab name="Media associati alla track" :selected="true">

                        <div class="modal-body" style="display:flex">
                          <div>
                            <div class="media-list col-50">
                              <div v-for="media in mediaList.features">
                                <img :src="media.properties.url" style="max-width:30px">
                                <img :src="media.properties.url" style="max-width:30px">
                                <img :src="media.properties.url" style="max-width:30px">
                                <img :src="media.properties.url" style="max-width:30px">
                                <img :src="media.properties.url" style="max-width:30px">

                              </div>

                            </div>
                            <div class="map col-50 text-center">
                              Mappa
                            </div>
                          </div>
                        </div>
                      </tab>
                      <tab name="Carica Media">

                        <div class="modal-body text-center">
                          <p class="py-1"><b>Trascina </b>i file da caricare <br>
                            oppure</p>
                          <input dusk="ecmedia" type="file" id="file-ec-tracks-ecmedia" name="name"
                                 class="form-file-input select-none">
                          <label for="file-ec-tracks-ecmedia"
                                 class="form-file-btn btn btn-default btn-primary select-none"><span>Scegli File</span></label>
                        </div>
                      </tab>
                    </tabs>
                  </div>

                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary btn-default" @click="modalOpen = false">
                    OK
                  </button>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </template>
  </div>
</template>

<script>
import Tab from './tab';
import Tabs from './tabs';

import {FormField, HandlesValidationErrors} from 'laravel-nova'

import EcMediaModal from './ModalEcMedia';

export default {
  mixins: [FormField, HandlesValidationErrors],
  components: {
    EcMediaModal,
    Tabs,
    Tab,
  },
  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      modalOpen: false,
      mediaList: {},
    }
  },
  mounted() {
    axios.get('/api/ec/track/' + this.resourceId + '/near_points')
        .then(response => {
          this.mediaList = response.data;
          console.log(this.mediaList);
        });
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

<style scoped>
.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  width: 50%;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  font-family: Helvetica, Arial, sans-serif;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  padding: 1rem;
  text-align: right
}

.modal-default-button {
  float: right;
}

/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */

.modal-enter {
  opacity: 0;
}

.modal-leave-active {
  opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.modal-header {
  border-bottom: 1px solid lightgray;
}

.modal-footer {
  border-top: 1px solid lightgray;
}

.col-50 {
  flex: 0 0 49%;
  max-width: 49%;
}

.col-20 {
  flex: 0 0 20%;
  max-width: 20%;
}
</style>