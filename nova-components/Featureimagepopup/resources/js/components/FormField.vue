<template>
  <div>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
      <template slot="field">
        <div ref="selectedFeatureImageList" id="selectedFeatureImageList">
          <div class="selectedImageRow flex flex-wrap mb-1" v-for="row in loadedImages">
            <div class="w-1/5">
              <img class="selectedThunbnail" :src="row.url">
            </div>
            <div class="w-3/5">
              <p>IT: {{ row.name.it }}</p>
              <p>EN: {{ row.name.en }}</p>
            </div>
            <div class="w-1/5">
              <button type="button" class="btn btn-primary btn-default" @click="removeImage(row.id)">Cancel
              </button>
            </div>
            <hr>
          </div>
          <br>
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
                                   :class="selectedMedia.includes(media.properties.id) ? 'selected' : ''"
                                   :src="media.properties.url"
                                   @click="toggleImage(media.properties)">
                              <div class="overlay"
                                   :class="selectedMedia.includes(media.properties.id) ? 'selected' : ''"
                                   :src="media.properties.url"
                                   @click="toggleImage(media.properties)">
                                <div class="text">Seleziona</div>
                              </div>
                            </div>
                          </div>
                          <div class="map w-1/2 text-center">
                            <MapComponent :feature="field.geojson" :media="mediaList"
                                          :selectedMedia="selectedMedia" :loadedImages="loadedImages"></MapComponent>
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
                      <button type="button" class="btn btn-danger btn-default" @click="cancelUpload()">X</button>
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
import MapComponent from './MapComponent';

import {FormField, HandlesValidationErrors} from 'laravel-nova'

import EcMediaModal from './ModalEcMedia';

export default {
  mixins: [FormField, HandlesValidationErrors],
  components: {
    EcMediaModal,
    Tabs,
    Tab,
    MapComponent,
  },
  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      modalOpen: false,
      associatedMediaList: {},
      mediaList: {},
      selectedMedia: [],
      loadedImages: [],
    }
  },
  mounted() {
    var that = this;
    axios.get('/api/ec/track/' + this.resourceId + '/near_points')
        .then(response => {
          that.mediaList = response.data;
        });
    axios.get('/api/ec/track/' + this.resourceId + '/feature_image')
        .then(response => {
          that.selectedMedia.splice(0);
          that.associatedMediaList = response.data;
          that.associatedMediaList.forEach(element => {
                that.loadedImages.push(element)
                that.selectedMedia.push(element.id)
              }
          );
        });
  },

  methods: {
    toggleImage(item) {
      this.selectedMedia.splice(0);
      this.loadedImages.splice(0);
      this.loadedImages.push(item);
      this.selectedMedia.push(item.id);
    },
    loadImages() {
      document.getElementById('selectedFeatureImageList').style.display = "block";
      this.modalOpen = false;
    },
    cancelUpload() {
      this.modalOpen = false;
    },
    removeImage(id) {
      this.selectedMedia.splice(this.selectedMedia.indexOf(id), 1);
      this.loadedImages.splice(this.loadedImages.indexOf(id), 1)
    },


    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      formData.append(this.field.attribute, this.selectedMedia[0])
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
  width: 75%;
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  font-family: Helvetica, Arial, sans-serif;
  border: 1px solid #D4DDE4;
  box-sizing: border-box;
  border-radius: 8px;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  padding: 1rem 0.5rem;
  min-height: 50vh;
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
  width: 108px;
  height: 101px !important;
  border-radius: 8px;
}

.ec-media-image:hover {
  border: 5px solid #63A2DE;
  box-sizing: border-box;
  opacity: 0.8;
}


.selected {
  border: 5px solid #63A2DE;
  box-sizing: border-box;
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
  height: 101px;
  width: 108px;
  opacity: 0;
  transition: .5s ease;
  background-color: black;
  border-radius: 8px;
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

}

.selectedThunbnail {
  width: 40px;
  height: 40px;
  border-radius: 8px;
}

</style>