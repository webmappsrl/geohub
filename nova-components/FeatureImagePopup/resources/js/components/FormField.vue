<template>
  <div>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText" :full-width-content="true">
      <template slot="field">
        <div ref="selectedFeatureImageList" id="selectedFeatureImageList">
          <div class="selectedImageRow flex flex-wrap mb-1" v-for="row in loadedImages">
            <div class="w-1/5">
              <img class="selectedThumbnail" :src="getBackgroundImage(row)">
            </div>
            <div class="w-3/5">
              <p>IT: {{ row.name.it }}</p>
              <p>EN: {{ row.name.en }}</p>
            </div>
            <div class="w-1/5">
              <button type="button" class="btn btn-primary btn-default" @click="removeImage(row.id)">
                Cancel
              </button>
            </div>
            <hr>
          </div>
          <br>
        </div>
        <button type="button" class="btn btn-primary btn-default" @click="openModal()">
          Select image
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
                      <tab name="Associated images" :selected="true">
                        <div class="modal-body flex flex-wrap">
                          <div class="modal-image-list w-1/2">
                            <p class="subtitle">
                              Select the geolocated media nearby
                            </p>
                            <div class="media-list flex flex-wrap"
                                 @mouseover="hideOverlay()">
                              <div class="image ec-media-image"
                                   :class="selectedMedia.includes(media.properties.id) ? 'selected' : ''"
                                   :style="{'background-image': 'url(' + getBackgroundImage(media) + ')' }"
                                   v-for="media in mediaList.features"
                                   @click="toggleImage(media.properties)"
                                   @mouseover="showOverlay(media.properties.id, $event)">
                                <div class="select-overlay">
                                  Select
                                </div>
                                <img src="/Vector.png"
                                     class="vector-visible"
                                     v-if="selectedMedia.includes(media.properties.id)"
                                     alt="selected">
                              </div>
                            </div>
                          </div>
                          <div class="map w-1/2 text-center">
                            <MapComponent :feature="field.geojson"
                                          :media="mediaList"
                                          :selectedMedia="selectedMedia"
                                          :loadedImages="loadedImages"
                                          ref="mapComponent"></MapComponent>
                          </div>
                        </div>
                      </tab>
                      <tab name="Upload media">
                        <div class="modal-body text-center">
                          <p class="py-1"><b>Drag </b>the files to upload <br>
                            or</p>
                          <input dusk="ecmedia" type="file" id="file-ec-tracks-ecmedia"
                                 name="name"
                                 class="form-file-input select-none">
                          <label for="file-ec-tracks-ecmedia"
                                 class="form-file-btn btn btn-default btn-primary select-none"><span>Select File</span></label>
                        </div>
                      </tab>
                    </tabs>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary btn-default" @click="loadImages()">
                    Upload selected
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
    if (!this.field.apiBaseUrl) this.field.apiBaseUrl = "/api/ec/track/";
    axios.get(this.field.apiBaseUrl + this.resourceId + '/feature_image')
      .then(response => {
        this.selectedMedia = [];
        this.associatedMediaList = response.data;
        if (!!this.associatedMediaList.length && !!this.associatedMediaList.forEach) {
          this.associatedMediaList.forEach(element => {
            if (!!element && !!element.id) {
              this.loadedImages.push(element)
              this.selectedMedia.push(element.id)
            }
          });
        }
      });
  },
  methods: {
    mount() {
      axios.get(this.field.apiBaseUrl + this.resourceId + '/near_points')
        .then(response => {
          this.mediaList = response.data;
        });
    },
    openModal() {
      this.modalOpen = true;
      this.mount();
    },
    showOverlay(id, event) {
      event.stopPropagation();
      this.$refs.mapComponent.showOverlay(id);
    },
    hideOverlay() {
      this.$refs.mapComponent.hideOverlay();
    },
    toggleImage(item) {
      if (this.selectedMedia.includes(item.id))
        this.selectedMedia = [];
      else
        this.selectedMedia = [item.id];
    },
    loadImages() {
      document.getElementById('selectedFeatureImageList').style.display = "block";
      this.modalOpen = false;
      for (let i in this.mediaList.features) {
        if (this.mediaList.features[i].properties.id === this.selectedMedia[0]) {
          this.loadedImages = [this.mediaList.features[i].properties];
          break;
        }
      }
    },
    cancelUpload() {
      this.modalOpen = false;
    },
    removeImage(id) {
      this.selectedMedia.splice(this.selectedMedia.indexOf(id), 1);
      this.loadedImages.splice(this.loadedImages.indexOf(id), 1)
    },
    dismiss() {
      this.modalOpen = false;
      if (this.loadedImages.length > 0)
        this.selectedMedia = [this.loadedImages[0].id];
      else this.selectedMedia = [];
    },
    getBackgroundImage(media) {
      let url = undefined,
        properties = !!media.properties
          ? media.properties
          : !!media.url ? media : undefined;
      if (!!properties) {
        if (!!properties.sizes) {
          try {
            let thumbnails = JSON.parse(properties.sizes);
            if (thumbnails['150x150']) url = thumbnails['150x150'];
          } catch (e) {
          }
        }

        if (!url)
          url = properties.url;
      }
      return url;
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
  width: 60% !important;
  margin: 0px auto;
  padding: 10px 0px !important;
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
  padding: 0px 0px !important;
  min-height: 50vh;
}

.modal-image-list {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
}

.modal-footer {
  padding: 1rem;
  text-align: right
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.modal-header {
  border-bottom: 1px solid lightgray;
}

.ec-media-image {
  display: block;
  width: 108px;
  height: 108px;
  border-radius: 8px;
  overflow: hidden;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  margin-bottom: 15px;
  margin-right: 10px;
  position: relative;
}

.ec-media-image:hover {
  border: 3px solid #55af60 !important;
  box-sizing: border-box;
  opacity: 0.8;
}

.ec-media-image:hover .select-overlay {
  display: flex;
}

.vector-visible {
  display: block;
  position: absolute;
  right: 0px;
  top: 5px;
}

.select-overlay {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  -webkit-transition: .5s ease;
  transition: .5s ease;
  background-color: rgba(0, 0, 0, 0.4);
  color: white;
  font-size: 15px;
  display: none;
  justify-content: center;
  align-items: center;
}

.selectedThumbnail {
  width: 40px;
  height: 40px;
  border-radius: 8px;
}

.media-list {
  width: 100%;
  background-color: #f1f3f5;
  max-height: 50vh;
  overflow: scroll;
  padding-top: 27px;
  padding-left: 34px;
  padding-right: 34px;
  display: flex;
  justify-content: space-around;
  align-items: flex-start;
  flex: 1 1 auto;
}

.subtitle {
  padding: 10px 20px;
  font-family: Nunito, sans-serif;
  background: #fff;
}

</style>
