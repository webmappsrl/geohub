<template>
  <div>
    <default-field
      :field="field"
      :errors="errors"
      :show-help-text="showHelpText"
      :full-width-content="true"
    >
      <template slot="field">
        <div ref="selectedImageList" id="selectedImageList">
          <div
            class="selectedImageRow flex flex-wrap mb-1"
            v-for="row in loadedImages"
            v-bind:key="row"
          >
            <div class="w-1/5">
              <img class="selectedThumbnail" :src="getBackgroundImage(row)" />
            </div>
            <div class="w-3/5">
              <p>IT: {{ row.name.it }}</p>
              <p>EN: {{ row.name.en }}</p>
            </div>
            <div class="w-1/5">
              <button
                type="button"
                class="btn btn-primary btn-default"
                @click="removeImage(row.id)"
              >
                Cancel
              </button>
            </div>
            <hr />
          </div>
          <br />
        </div>
        <button
          type="button"
          class="btn btn-primary btn-default"
          @click="openModal()"
        >
          Select images
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
                  <div style="display: flex">
                    <tabs @currentTab="updateTab">
                      <tab name="Associated images" :selected="true">
                        <div class="modal-body flex flex-wrap">
                          <div class="modal-image-list w-1/2">
                            <p class="subtitle">
                              Select the geolocated media nearby
                            </p>
                            <div
                              class="media-list flex flex-wrap"
                              @mouseover="hideOverlay()"
                            >
                              <div
                                class="ec-media-image"
                                :class="
                                  selectedMedia.includes(media.properties.id)
                                    ? 'selected'
                                    : ''
                                "
                                :style="{
                                  'background-image':
                                    'url(' + getBackgroundImage(media) + ')',
                                }"
                                v-for="media in mediaList.features"
                                v-bind:key="media"
                                @click="toggleImage(media.properties)"
                                @mouseover="
                                  showOverlay(media.properties.id, $event)
                                "
                              >
                                <div class="select-overlay">Select</div>
                                <img
                                  src="/Vector.png"
                                  class="vector-visible"
                                  v-if="
                                    selectedMedia.includes(media.properties.id)
                                  "
                                  alt="selected"
                                />
                              </div>
                            </div>
                          </div>
                          <div class="map w-1/2 text-center">
                            <MapComponent
                              :feature="field.geojson"
                              :media="mediaList"
                              :selectedMedia="selectedMedia"
                              :loadedImages="loadedImages"
                              ref="mapComponent"
                            ></MapComponent>
                          </div>
                        </div>
                      </tab>
                      <tab name="Upload media">
                        <div class="modal-body flex flex-wrap">
                          <div class="modal-image-list w-1/2">
                            <div class="upload">
                              <div class="upload-button">
                                <input
                                  dusk="ecmedia"
                                  accept="image/png, image/gif, image/jpeg"
                                  type="file"
                                  id="file-ec-tracks-ecmedia"
                                  name="name"
                                  class="form-file-input select-none"
                                  @change="onFileChange"
                                  multiple
                                />
                                <label
                                  for="file-ec-tracks-ecmedia"
                                  class="
                                    form-file-btn
                                    btn btn-default btn-primary
                                    select-none
                                  "
                                  ><span>Select Images</span></label
                                >
                              </div>
                            </div>
                            <div>
                              <div
                                class="upload-text"
                                v-if="selectedFeatureIdx != -1"
                              >
                                <label>name: </label>
                                <input
                                  v-model="
                                    featureCollection.features[
                                      selectedFeatureIdx
                                    ].properties.name
                                  "
                                  placeholder="edit me"
                                />
                              </div>
                            </div>
                            <div class="media-list flex flex-wrap">
                              <div
                                class="ec-media-image"
                                :style="{
                                  'background-image':
                                    'url(' + getBackgroundImage(media) + ')',
                                }"
                                :class="{
                                  selected: index == selectedFeatureIdx,
                                }"
                                v-for="(
                                  media, index
                                ) in featureCollection.features"
                                v-bind:key="media"
                                @click="selectCurrentMedia(index)"
                                @mouseover="
                                  showOverlay(media.properties.id, $event)
                                "
                              >
                                <div
                                  class="select-overlay"
                                  v-if="index == selectedFeatureIdx"
                                >
                                  Select
                                </div>
                                <div
                                  class="delete-visible"
                                  alt="selected"
                                  @click="removeMedia(index)"
                                >
                                  X
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="map w-1/2 text-center">
                            <UploadMapComponent
                              :feature="field.geojson"
                              :media="featureCollection"
                              ref="uMapComponent"
                            ></UploadMapComponent>
                          </div>
                        </div>
                      </tab>
                    </tabs>
                  </div>
                </div>
                <div class="modal-footer">
                  <button
                    class="btn btn-primary btn-default"
                    @click="loadImages()"
                  >
                    {{ buttonSaveText }}
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
<script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
<script>
require("exif")();
export default {
  name: "Index",
  data() {
    return {};
  },
};
</script>
<script>
import Tab from "./tab";
import Tabs from "./tabs";
import MapComponent from "./MapComponent";
import UploadMapComponent from "./UploadMapComponent";
import { EXIF } from "exif-js";

import { FormField, HandlesValidationErrors } from "laravel-nova";

import EcMediaModal from "./ModalEcMedia";

export default {
  mixins: [FormField, HandlesValidationErrors],
  components: {
    EcMediaModal,
    Tabs,
    Tab,
    MapComponent,
    UploadMapComponent,
  },
  props: ["resourceName", "resourceId", "field"],
  data() {
    return {
      modalOpen: false,
      buttonSaveText: "Attach selected",
      associatedMediaList: {},
      mediaList: {},
      selectedMedia: [],
      loadedImages: [],
      initUploadedMediaFeature: {
        type: "Feature",
        properties: {
          id: "uploaded-media",
          url: null,
          name: null,
        },
        geometry: this.field.geojson.geometry,
      },
      features: [],
      selectedFeatureIdx: -1,
      featureCollection: {
        type: "FeatureCollection",
        features: [],
      },
    };
  },
  mounted() {
    if (!this.field.apiBaseUrl) this.field.apiBaseUrl = "/api/ec/track/";
    axios
      .get(this.field.apiBaseUrl + this.resourceId + "/associated_ec_media")
      .then((response) => {
        this.selectedMedia = [];
        this.associatedMediaList =
          response.data && response.data.features ? response.data.features : [];
        if (!!this.associatedMediaList.length) {
          this.associatedMediaList.forEach((element) => {
            if (!!element && !!element.properties && !!element.properties.id) {
              this.loadedImages.push(element.properties);
              this.selectedMedia.push(element.properties.id);
            } else if (!!element && !!element.id) {
              this.loadedImages.push(element);
              this.selectedMedia.push(element.id);
            }
          });
        }
      });
  },
  methods: {
    removeMedia(idx) {
      this.featureCollection.features = this.featureCollection.features.filter(
        (el) => {
          const currentIdx = this.featureCollection.features.indexOf(el);
          return currentIdx != idx;
        }
      );
      this.refreshUploadedMediaList(this.featureCollection.features);
    },
    selectCurrentMedia(idx) {
      this.selectedFeatureIdx = idx;
    },
    updateTab(tabName) {
      if (tabName === "Upload media") {
        this.buttonSaveText = "Upload and attach media";
        this.refreshUploadedMediaList([]);
      } else {
        this.buttonSaveText = "Attach selected";
      }
    },
    refreshUploadedMediaList(features) {
      if (features != null) {
        this.featureCollection.features = features;
      }

      this.featureCollection = JSON.parse(
        JSON.stringify(this.featureCollection)
      );
    },
    mount() {
      axios
        .get(this.field.apiBaseUrl + this.resourceId + "/neighbour_media")
        .then((response) => {
          this.mediaList = response.data;
        });
    },
    openModal() {
      this.modalOpen = true;
      this.mount();
    },
    showOverlay(id, event) {
      event.stopPropagation();
      if (this.$refs.mapComponent) {
        this.$refs.mapComponent.showOverlay(id);
      }
      if (this.$refs.uMapComponent) {
        this.$refs.uMapComponent.showOverlay(id);
      }
    },
    hideOverlay() {
      this.$refs.mapComponent.hideOverlay();
    },
    toggleImage(item) {
      if (this.selectedMedia.includes(item.id))
        this.selectedMedia.splice(this.selectedMedia.indexOf(item.id), 1);
      else this.selectedMedia.push(item.id);
    },
    loadImages() {
      document.getElementById("selectedImageList").style.display = "block";
      this.modalOpen = false;
      let loadedImages = [];

      if (this.features.length > 0) {
        this.loadedImages = this.features.map((f) => f.properties);
      } else {
        for (let i in this.mediaList.features) {
          if (
            this.selectedMedia.includes(
              this.mediaList.features[i].properties.id
            )
          )
            loadedImages.push(this.mediaList.features[i].properties);
        }
      }

      this.loadedImages = loadedImages;
    },
    cancelUpload() {
      this.modalOpen = false;
    },
    removeImage(id) {
      this.selectedMedia.splice(this.selectedMedia.indexOf(id), 1);
      this.loadedImages.splice(this.loadedImages.indexOf(id), 1);
    },
    dismiss() {
      this.modalOpen = false;
      let selectedMedia = [];
      for (let i in this.loadedImages) {
        selectedMedia.push(this.loadedImages[i].id);
      }
      this.selectedMedia = selectedMedia;
    },
    getBackgroundImage(media) {
      let url = undefined,
        properties = !!media.properties
          ? media.properties
          : !!media.url
          ? media
          : undefined;
      if (!!properties) {
        if (!!properties.sizes) {
          try {
            let thumbnails = properties.sizes;
            if (thumbnails["150x150"]) url = thumbnails["150x150"];
          } catch (e) {}
        }

        if (!url) url = properties.url;
      }
      return url;
    },
    /**
     * Fill the given FormData object with the field's internal value.
     */
    fill(formData) {
      if (this.featureCollection.features.length > 0) {
        formData.append(
          "uploadFeatures",
          JSON.stringify(this.featureCollection)
        );
      } else {
        formData.append(
          this.field.attribute,
          JSON.stringify(this.selectedMedia)
        );
      }
    },
    async onFileChange(e) {
      const files = e.target.files;
      var coordinates$ = [];
      var files$ = [];
      const features = [];
      for (const file of files) {
        var lon = null;
        var lat = null;
        coordinates$.push(
          new Promise((resolve, reject) => {
            EXIF.getData(file, function () {
              try {
                const lon1 = this.exifdata.GPSLongitude[0].valueOf();
                const lon2 = this.exifdata.GPSLongitude[1].valueOf();
                const lon3 = this.exifdata.GPSLongitude[2].valueOf();
                const lat1 = this.exifdata.GPSLatitude[0].valueOf();
                const lat2 = this.exifdata.GPSLatitude[1].valueOf();
                const lat3 = this.exifdata.GPSLatitude[2].valueOf();
                lon = lon1 + lon2 / 60 + lon3 / 3600;
                lat = lat1 + lat2 / 60 + lat3 / 3600;
                if (lat != null && lon != null) {
                  resolve([lon, lat]);
                } else {
                  resolve(this.field.geojson.geometry.coordinates);
                }
              } catch (_) {
                reject;
              }
            });
          })
        );
        files$.push(
          new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
              resolve(reader.result);
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
          })
        );
      }
      let coordinates = [];
      let base64 = [];
      await Promise.all(coordinates$).then((val) => (coordinates = val));
      await Promise.all(files$).then((val) => (base64 = val));

      Array.from(files).forEach((f, index) => {
        const geometry = this.field.geojson.geometry;
        let c = coordinates[index];
        let b64 = base64[index];
        if (c != null) {
          geometry.coordinates = c;
        }
        features.push({
          type: "Feature",
          properties: {
            id: `uploaded-media-${features.length}`,
            name: f.name,
            ext: f.type,
            url: URL.createObjectURL(f),
            base64: b64,
          },
          geometry: {
            type: "Point",
            coordinates: c,
          },
        });
      });
      this.refreshUploadedMediaList(features);
    },
  },
};
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
  border: 1px solid #d4dde4;
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
  text-align: right;
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

.ec-media-image:hover,
.ec-media-image.selected {
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
.delete-visible {
  cursor: pointer;
  display: block;
  background-color: white;
  position: absolute;
  padding: 4px;
  right: 0px;
  top: 0px;
  border-radius: 5px;
  margin: 2px;
}
.select-overlay {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  -webkit-transition: 0.5s ease;
  transition: 0.5s ease;
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
