<template>
  <div>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
      <template slot="field">
        <div ref="selectedImageList" id="selectedImageList">
          <div class="selectedImageRow flex flex-wrap mb-1" v-for="row in loadedPois">
            <div class="w-1/5">
              <div class="selectedThumbnail" :style="{'background-image': 'url(' + getBackgroundImage(row) + ')' }">
              </div>
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
        <button type="button" class="btn btn-primary btn-default" @click="openModal()">
          Select EcPoi
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
                      <tab name="Poi associati alla track" :selected="true">
                        <div class="modal-body flex flex-wrap">
                          <div class="modal-poi-list w-1/2 flex flex-wrap" style="border-right: 1px solid grey">
                            <p class="subtitle">Select the points geolocated nearby</p>

                            <div class="poi-list flex flex-wrap"
                                 @mouseover="hideOverlay()">
                              <div class="ec-poi"
                                   :class="selectedPois.includes(poi.properties.id) ? 'selected' : ''"
                                   v-for="poi in poiList.features"
                                   @click="toggleImage(poi.properties)"
                                   @mouseover="showOverlay(poi.properties.id, $event)">
                                <div class="ec-poi-content">
                                  IT: {{ poi.properties.name.it }}<br>
                                  EN: {{ poi.properties.name.en }}
                                </div>
                                <div class="select-overlay">
                                  Select
                                </div>
                                <img src="/Vector.png"
                                     class="vector-visible"
                                     v-if="selectedPois.includes(poi.properties.id)"
                                     alt="selected">
                              </div>
                            </div>
                          </div>
                          <div class="map w-1/2 text-center">
                            <MapComponent id="map-component"
                                          :feature="field.geojson"
                                          :media="poiList"
                                          :selectedPois="selectedPois" :loadedPois="loadedPois"
                                          ref="mapComponent"></MapComponent>
                          </div>
                        </div>
                      </tab>

                    </tabs>

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

import EcPoiModal from './ModalEcPoi';
import {Overlay} from "ol";

export default {
  mixins: [FormField, HandlesValidationErrors],
  components: {
    EcPoiModal,
    Tabs,
    Tab,
    MapComponent,
  },
  props: ['resourceName', 'resourceId', 'field'],
  data() {
    return {
      modalOpen: false,
      associatedPoiList: {},
      poiList: {},
      selectedPois: [],
      loadedPois: [],
    }
  },
  mounted() {
    axios.get('/api/ec/track/' + this.resourceId + '/associated_ec_pois')
      .then(response => {
        this.selectedPois.splice(0);
        this.associatedPoiList = response.data && response.data.features ? response.data.features : [];
        if (!!this.associatedPoiList.length) {
          this.associatedPoiList.forEach(element => {
            if (!!element && !!element.properties && !!element.properties.id) {
              this.loadedPois.push(element.properties)
              this.selectedPois.push(element.properties.id)
            } else if (!!element && !!element.id) {
              this.loadedPois.push(element)
              this.selectedPois.push(element.id)
            }
          });
        }
      });
  },
  methods: {
    mount() {
      axios.get('/api/ec/track/' + this.resourceId + '/neighbour_pois')
        .then(response => {
          this.poiList = response.data;
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
      if (this.selectedPois.includes(item.id))
        this.selectedPois.splice(this.selectedPois.indexOf(item.id), 1);
      else
        this.selectedPois.push(item.id);
    },
    loadImages() {
      document.getElementById('selectedImageList').style.display = "block";
      this.modalOpen = false;
      let loadedImages = [];
      for (let i in this.poiList.features) {
        if (this.selectedPois.includes(this.poiList.features[i].properties.id))
          loadedImages.push(this.poiList.features[i].properties);
      }
      this.loadedPois = loadedImages;
    },
    cancelUpload() {
      this.modalOpen = false;
    },
    removeImage(id) {
      this.selectedPois.splice(this.selectedPois.indexOf(id), 1);
      this.loadedPois.splice(this.loadedPois.indexOf(id), 1)
    },
    dismiss() {
      this.modalOpen = false;
      let selectedPois = [];
      for (let i in this.loadedPois) {
        selectedPois.push(this.loadedPois[i].id);
      }
      this.selectedPois = selectedPois;
    },
    getBackgroundImage(poi) {
      let url = undefined,
        image = poi && poi.properties && poi.properties.feature_image
          ? poi.properties.feature_image : (poi.feature_image ? poi.feature_image : undefined);
      if (image) {
        if (image.sizes && image.sizes['150x150']) url = image.sizes['150x150'];

        if (!url)
          url = image.url;
      }

      return url;
    },
    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      formData.append(this.field.attribute, JSON.stringify(this.selectedPois))
    },
  },
}
</script>

<style scoped>
.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.modal-header {
  border-bottom: 1px solid lightgray;
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
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  border-radius: 8px;
}

.poi-list {
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

.modal-poi-list {
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

.ec-poi {
  display: block;
  padding: 5px;
  border-radius: 8px;
  overflow: hidden;
  margin-bottom: 15px;
  margin-right: 10px;
  position: relative;
}

.ec-poi:hover {
  border: 3px solid #55af60 !important;
  box-sizing: border-box;
  opacity: 0.8;
}

.ec-poi:hover .select-overlay {
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

.poi-list {
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
