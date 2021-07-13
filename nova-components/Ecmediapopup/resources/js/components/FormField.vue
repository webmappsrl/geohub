<template>
  <div>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
      <template slot="field">
        <div ref="selectedImageList" id="selectedImageList"
             style="display:none">
          <div class="selectedImageRow flex flex-wrap mb-1" v-for="row in loadedImages">
            <div class="w-1/5">
              <img class="selectedThunbnail" :src="row.url">
            </div>
            <div class="w-3/5">
              <p>IT: {{ row.name.it }}</p>
              <br>
              <p>EN: {{ row.name.en }}</p>
            </div>
            <div class="w-1/5">
              <button type="button" class="btn btn-primary btn-default" @click="removeImage(row.id)">Cancel</button>
            </div>
            <hr>
          </div>

        </div>
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

                        <div class="modal-body flex flex-wrap">
                          <div class="media-list w-1/2 flex flex-wrap" style="border-right: 1px solid grey">
                            <div class="w-1/4 box-image" v-for="media in mediaList.features">
                              <img class="image ec-media-image"
                                   :class="selectedImages.includes(media.properties.id) ? 'selected' : ''"
                                   :src="media.properties.url"
                                   @click="toggleImage(media.properties)">
                              <div class="overlay"
                                   :class="selectedImages.includes(media.properties.id) ? 'selected' : ''"
                                   :src="media.properties.url"
                                   @click="toggleImage(media.properties)">
                                <div class="text">Seleziona</div>
                              </div>
                            </div>
                          </div>
                          <div class="map w-1/2 text-center">
                            Mappa
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
                    <p class="text-right">
                      <button type="button" class="btn btn-primary btn-default" @click="cancelUpload()">X</button>
                    </p>
                  </div>

                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary btn-default" @click="loadImages()">
                    Carica Selezionati
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
      selectedImages: [],
      loadedImages: [],
    }
  },
  mounted() {
    axios.get('/api/ec/track/' + this.resourceId + '/near_points')
        .then(response => {
          this.mediaList = response.data;
        });
  },

  methods: {
    toggleImage(item) {
      if (this.selectedImages.includes(item.id)) {
        this.loadedImages.splice(this.loadedImages.indexOf(item.id), 1)
        this.selectedImages.splice(this.selectedImages.indexOf(item.id), 1)
      } else {
        this.loadedImages.push(item);
        this.selectedImages.push(item.id);
      }
    },
    loadImages() {
      document.getElementById('selectedImageList').style.display = "block";
      this.modalOpen = false;
    },
    cancelUpload() {
      this.selectedImages.splice(0);
      this.loadedImages.splice(0);
      this.modalOpen = false;
    },
    removeImage(id) {
      this.selectedImages.splice(this.selectedImages.indexOf(id), 1);
      this.loadedImages.splice(this.loadedImages.indexOf(id), 1)
    },
    associateImages() {

    },

    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      //formData.append('ec-medias', this.selectedImages);
      //formData.append('viaRelationship', 'ecmedia');
      formData.append(this.field.attribute, JSON.stringify(this.selectedImages))
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
  padding: 1rem 0.5rem;
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

.ec-media-image {
  border-radius: 5px;
}

.ec-media-image:hover {
  border: 4px solid lightgreen;
}


.selected {
  border: 4px solid lightgreen;
  opacity: 0.8;
}


.image {
  display: block;
  height: auto;
}

.overlay {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100%;
  width: 100%;
  opacity: 0;
  transition: .5s ease;
  background-color: black;
}

.box-image:hover .overlay {
  opacity: 0.5;
}

.text {
  color: white;
  font-size: 15px;
  position: absolute;
  top: 50%;
  left: 50%;
  -webkit-transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  text-align: center;
}

.box-image {
  position: relative;
}

.overlay.selected {
  display: none;
}

.box-image {
  margin: 5px;
}

.selectedThunbnail {
  max-width: 50%;
  border-radius: 10px;
}

</style>