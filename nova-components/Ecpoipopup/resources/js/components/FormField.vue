<template>
  <div>
    <default-field :field="field" :errors="errors" :show-help-text="showHelpText">
      <template slot="field">
        <div ref="selectedImageList" id="selectedImageList">
          <div class="selectedImageRow flex flex-wrap mb-1" v-for="row in loadedPois">
            <!--<div class="w-1/5">
              <img class="selectedThunbnail" :src="row.name.it">
            </div>-->
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
                        <p class="subtitle">Seleziona i Poi Georeferenziati nelle vicinanze della traccia</p>
                        <div class="modal-body flex flex-wrap">

                          <div class="poi-list w-1/2 flex flex-wrap" style="border-right: 1px solid grey">

                            <div :class="selectedPois.includes(poi.properties.id) ? 'selected' : ''" class="box-poi"
                                 v-for="poi in poiList.features" style="margin:5px"
                                 @click="toggleImage(poi.properties)">

                              <img class="image ec-poi-image"
                                   :src="poi.properties.image.url">
                              <!--<img src="/Vector.png"
                                   :class="selectedPois.includes(poi.properties.id) ? 'vector-visible' : 'vector-hidden'">-->
                              <p @click="toggleImage(poi.properties)" @mouseover="showOverlay(poi.properties.id)"
                                 style="padding-left:3px">IT:
                                {{ poi.properties.name.it }}<br>
                                <br>
                                EN: {{ poi.properties.name.en }}</p>
                              <!--<div class="overlay"
                                   :class="selectedPois.includes(poi.properties.id) ? 'selected' : ''"
                                   :src="poi.properties.image.url"
                                   @click="toggleImage(poi.properties)">
                                <div class="text-poi">Seleziona</div>
                              </div>-->
                            </div>
                            <br>
                          </div>
                          <div class="map w-1/2 text-center">
                            <MapComponent id="map-component" :feature="field.geojson" :media="poiList"
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
    var that = this;
    axios.get('/api/ec/track/' + this.resourceId + '/neighbour_pois')
        .then(response => {
          that.poiList = response.data;
        });
    axios.get('/api/ec/track/' + this.resourceId + '/associated_ec_poi')
        .then(response => {
          that.selectedPois.splice(0);
          that.associatedPoiList = response.data;
          that.associatedPoiList.forEach(element => {
                that.loadedPois.push(element)
                that.selectedPois.push(element.id)

              }
          );

        });
  },

  methods: {
    showOverlay(id) {
      var that = this;
      axios.get('/api/ec/poi/' + id)
          .then(response => {
            var coordinate = response.data.geometry.coordinates;

            if (coordinate) {
              var overlayPopup = new Overlay({
                element: document.getElementById('overlayPopup')
              });
              var popupImage = overlayPopup.setPosition(coordinate);

              this.$refs.mapComponent.map.addOverlay(overlayPopup);

              document.getElementById("popupImageLabel").innerHTML = response.data.properties.name.it;
              document.getElementById("popupImage").src = response.data.properties.feature_image;
            }
          });
    },
    toggleImage(item) {
      if (this.selectedPois.includes(item.id)) {
        this.loadedPois.splice(this.loadedPois.indexOf(item.id), 1);
        this.selectedPois.splice(this.selectedPois.indexOf(item.id), 1);
      } else {
        this.loadedPois.push(item);
        this.selectedPois.push(item.id);
      }

    },
    loadImages() {
      document.getElementById('selectedImageList').style.display = "block";
      this.modalOpen = false;
    },
    cancelUpload() {
      this.modalOpen = false;
    },
    removeImage(id) {
      this.selectedPois.splice(this.selectedPois.indexOf(id), 1);
      this.loadedPois.splice(this.loadedPois.indexOf(id), 1)
    },
    openImagePopup(id, poiCoordinate) {
      let coordinate;
      if (poi)
        coordinate = poi.getGeometry().getClosestPoint(event.coordinate);
      else if (track)
        coordinate = track.getGeometry().getClosestPoint(event.coordinate);
      if (coordinate) {
        var overlayPopup = new Overlay({
          element: document.getElementById('overlayPopup')
        });
        var popupImage = overlayPopup.setPosition(coordinate);
        this.map.addOverlay(overlayPopup);
        document.getElementById("popupImageLabel").innerHTML = poi['values_']['name']['it'];
        document.getElementById("popupImage").src = "/storage" + poi['values_']['url'];
      }
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

.modal-footer {
  padding: 1rem;
  text-align: right
}

.poi-list .box-image:hover {
  border: 3px solid #55af60 !important;
  box-sizing: border-box;
  opacity: 0.8;
}

.poi-list .box-image.selected:hover {
  border: 0px;
}

.modal-default-button {
  float: right;
}

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

.ec-poi-image {
  width: 108px;
  height: 101px !important;
  border-radius: 8px;
}

.ec-media-image:hover {
  border: 3px solid #55af60 !important;
  box-sizing: border-box;
  opacity: 0.8;
}

.selected {
  border: 3px solid #55af60 !important;
  box-sizing: border-box;
  opacity: 0.8;
}


.vector-visible {
  display: block;
  position: absolute;
  right: 0px;
  top: 5px;
}

.vector-hidden {
  display: none;
  position: absolute;
  right: 15px;
  top: 5px;
}


.image {
  display: block;
  height: auto;
}

.overlay {
  position: absolute;
  top: 5px !important;
  bottom: 0;
  left: 5px !important;
  right: 0;
  height: 101px;
  width: 101px;
  opacity: 0;
  -webkit-transition: .5s ease;
  transition: .5s ease;
  background-color: black;
  border-radius: 8px;
}

.box-image:hover .overlay {
  opacity: 0.5;
}


.box-image {
  padding: 5px;
}

.text-poi {
  color: white;
  font-size: 15px;
  position: absolute;
  top: 50%;
  left: 50%;
  -webkit-transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  text-align: left;
}

.box-poi {
  position: relative;
  display: flex;
  justify-content: left;
  height: 108px;
  width: 100%;
  padding: 3px;
}

.box-poi:hover {
  border: 1px solid lightblue;
}

.overlay.selected {
  display: none;
}

.selectedThunbnail {
  width: 40px;
  height: 40px;
  border-radius: 8px;
}

.close-button {
  font-size: 20px;
  margin-right: 15px;
  margin-top: 15px;
}

.poi-list {
  background-color: #f1f3f5;
  max-height: 50vh;
  overflow: scroll;
  padding-top: 27px;
  padding-left: 34px;
  padding-right: 62px;
}

.subtitle {
  padding-left: 20px;
  padding-top: 10px;
  padding-bottom: 10px;
  font-family: Nunito, sans-serif;
}

</style>