<template>
  <div style="width: 100%; height: 100%" class="feature-image-popup-container">
    <div
      :ref="`feature-ec-media-map-root`"
      style="width: 100%; height: 100%"
    ></div>
    <div id="overlayPopupContainer">
      <div id="overlayPopup">
        <div id="popupImage" style="height: 85%; width: 70%"></div>

        <p id="popupImageLabel"></p>
      </div>
      <div class="bottom-popup"></div>
    </div>
  </div>
</template>

<script>
import View from "ol/View";
import Map from "ol/Map";
import TileLayer from "ol/layer/Tile";
import XYZ from "ol/source/XYZ";
import { transform, transformExtent } from "ol/proj";
import Attribution from "ol/control/Attribution";
import Zoom from "ol/control/Zoom";
import VectorSource from "ol/source/Vector";
import VectorLayer from "ol/layer/Vector";
import GeoJSON from "ol/format/GeoJSON";
import Style from "ol/style/Style";
import CircleStyle from "ol/style/Circle";
import Stroke from "ol/style/Stroke";
import Fill from "ol/style/Fill";
import { defaults as defaultInteractions, Select } from "ol/interaction";
import { getDistance } from "ol/sphere";
import Overlay from "ol/Overlay";

export default {
  name: "MapComponent",
  props: {
    feature: {},
    media: {},
    selectedMedia: [],
    loadedImages: [],
  },
  data: () => ({
    map: null,
    view: null,
    featureLayer: null,
    featureSource: null,
    mediaLayer: null,
    mediaSource: null,
    overlayPopup: null,
  }),
  mounted() {
    this.featureSource = new VectorSource({
      features: [],
    });
    this.featureLayer = new VectorLayer({
      source: this.featureSource,
      visible: true,
      style: (feature) => {
        return this._style(feature);
      },
      updateWhileAnimating: true,
      updateWhileInteracting: true,
      zIndex: 50,
    });
    this.mediaSource = new VectorSource({
      features: [],
    });
    this.mediaLayer = new VectorLayer({
      source: this.mediaSource,
      visible: true,
      style: (feature) => {
        return this._style(feature, true);
      },
      updateWhileAnimating: true,
      updateWhileInteracting: true,
      zIndex: 60,
    });

    this.view = new View({
      center: this._fromLonLat([10.4, 43]),
      maxZoom: 17,
      minZoom: 3,
      projection: "EPSG:3857",
      constrainOnlyCenter: true,
      zoom: 6,
    });
    this.map = new Map({
      target: Object.values(this.$refs)[0],
      layers: [
        new TileLayer({
          source: new XYZ({
            maxZoom: 21,
            minZoom: 1,
            tileUrlFunction: (c) => {
              return (
                "https://api.webmapp.it/tiles/" +
                c[0] +
                "/" +
                c[1] +
                "/" +
                c[2] +
                ".png"
              );
            },
            projection: "EPSG:3857",
            tileSize: [256, 256],
            attributions: [
              "© <a href='https://webmapp.it' target='_blank'>Webmapp</a>",
              "© <a href='https://www.openstreetmap.org/' target='_blank'>OpenStreetMap</a>",
            ],
          }),
        }),
        this.featureLayer,
        this.mediaLayer,
      ],
      view: this.view,
      controls: [
        new Zoom(),
        new Attribution({
          collapsed: false,
          collapsible: false,
        }),
      ],
      interactions: defaultInteractions({
        mouseWheelZoom: false,
        doubleClickZoom: true,
        shiftDragZoom: true,
        dragPan: true,
        altShiftDragRotate: true,
        pinchRotate: true,
        pinchZoom: true,
      }).getArray(),
    });

    this.map.on("pointermove", (event) => {
      let minPoiDistance = 15,
        minTrackDistance = 15,
        minPolygonArea,
        poi,
        track,
        polygon,
        foreachFeature = (feature) => {
          if (feature.getGeometry().getType() === "Point") {
            let coord = feature.getGeometry().getCoordinates(),
              dist = Math.round(this.getFixedDistance(coord, event.coordinate));
            if (dist < minPoiDistance) {
              minPoiDistance = dist;
              poi = feature;
            }
          } else if (
            !poi &&
            (feature.getGeometry().getType() === "LineString" ||
              feature.getGeometry().getType() === "MultiLineString")
          ) {
            let coord = feature.getGeometry().getClosestPoint(event.coordinate),
              dist = Math.round(this.getFixedDistance(coord, event.coordinate));
            if (dist < minTrackDistance) {
              minTrackDistance = dist;
              track = feature;
            }
          } else if (
            !poi &&
            !track &&
            (feature.getGeometry().getType() === "Polygon" ||
              feature.getGeometry().getType() === "MultiPolygon")
          ) {
            let area,
              poly = feature.getGeometry();

            if (poly.containsXY(event.coordinate[0], event.coordinate[1])) {
              area = poly.getArea();
              if (area < minPolygonArea || !minPolygonArea) {
                minPolygonArea = area;
                polygon = feature;
              }
            }
          }
        };

      this.mediaSource.forEachFeatureInExtent(
        this.view.calculateExtent(this.map.getSize()),
        foreachFeature
      );
      this._toggleOverlay(poi || track || undefined);
    });

    this.map.on("click", (event) => {
      let minPoiDistance = 15,
        minTrackDistance = 15,
        minPolygonArea,
        poi,
        track,
        polygon,
        foreachFeature = (feature) => {
          if (feature.getGeometry().getType() === "Point") {
            let coord = feature.getGeometry().getCoordinates(),
              dist = Math.round(this.getFixedDistance(coord, event.coordinate));
            if (dist < minPoiDistance) {
              minPoiDistance = dist;
              poi = feature;
            }
          } else if (
            !poi &&
            (feature.getGeometry().getType() === "LineString" ||
              feature.getGeometry().getType() === "MultiLineString")
          ) {
            let coord = feature.getGeometry().getClosestPoint(event.coordinate),
              dist = Math.round(this.getFixedDistance(coord, event.coordinate));
            if (dist < minTrackDistance) {
              minTrackDistance = dist;
              track = feature;
            }
          } else if (
            !poi &&
            !track &&
            (feature.getGeometry().getType() === "Polygon" ||
              feature.getGeometry().getType() === "MultiPolygon")
          ) {
            let area,
              poly = feature.getGeometry();

            if (poly.containsXY(event.coordinate[0], event.coordinate[1])) {
              area = poly.getArea();
              if (area < minPolygonArea || !minPolygonArea) {
                minPolygonArea = area;
                polygon = feature;
              }
            }
          }
        };

      this.mediaSource.forEachFeatureInExtent(
        this.view.calculateExtent(this.map.getSize()),
        foreachFeature
      );
      let id;
      if (poi) id = poi.getId();
      else if (track) id = track.getId();
      if (id) {
        this.selectedMedia = [id];
        this.loadedImages = [poi["values_"]];

        this.mediaLayer.changed();
        this.map.render();
      }
    });

    setTimeout(() => {
      this.map.updateSize();
      this.drawFeature();
      this.drawMedia();
    }, 500);
  },
  watch: {
    selectedMedia() {
      this.mediaLayer.changed();
      this.map.render();
    },
  },
  methods: {
    getFixedDistance(point1, point2) {
      return (
        getDistance(this._toLonLat(point1), this._toLonLat(point2)) /
        this.view.getResolution()
      );
    },
    _toLonLat(coordinates) {
      return transform(coordinates, "EPSG:3857", "EPSG:4326");
    },
    _fromLonLat(coordinates) {
      return transform(coordinates, "EPSG:4326", "EPSG:3857");
    },
    _extentToLonLat(extent) {
      return transformExtent(extent, "EPSG:3857", "EPSG:4326");
    },
    _extentFromLonLat(extent) {
      return transformExtent(extent, "EPSG:4326", "EPSG:3857");
    },
    _style(feature, isMedia) {
      const isSelected =
        isMedia && this.selectedMedia.indexOf(feature.getId()) >= 0;
      if (feature.getGeometry().getType() === "Point")
        return this._getPoiStyle(feature, isMedia, isSelected);
      else if (
        feature.getGeometry().getType() === "LineString" ||
        feature.getGeometry().getType() === "MultiLineString"
      )
        return this._getLineStyle(feature, isMedia, isSelected);
      else if (
        feature.getGeometry().getType() === "Polygon" ||
        feature.getGeometry().getType() === "MultiPolygon"
      )
        return this._getPolygonStyle(feature, isMedia, isSelected);
      else return [];
    },
    _getPoiStyle(feature, isMedia, isSelected) {
      let style,
        color = !isMedia ? "#ff0000" : isSelected ? "#63a2de" : "#ffb100",
        colorRgb = !isMedia
          ? "255,0,0"
          : isSelected
          ? "99,162,222"
          : "255,177,0",
        zoomFactor = 1.2,
        borderSize = 2;

      style = [
        new Style({
          image: new CircleStyle({
            radius: (isSelected ? 1.5 : zoomFactor) * 7 + borderSize / 2 + 8,
            fill: new Fill({ color: "rgba(" + colorRgb + ",0.2)" }),
          }),
          zIndex: isSelected ? 200 : 100,
        }),
        new Style({
          image: new CircleStyle({
            radius: (isSelected ? 1.5 : zoomFactor) * 7 + borderSize / 2,
            fill: new Fill({ color: "#fff" }),
          }),
          zIndex: isSelected ? 201 : 101,
        }),
        new Style({
          image: new CircleStyle({
            radius: (isSelected ? 1.5 : zoomFactor) * 7 - borderSize / 2,
            fill: new Fill({ color: color }),
          }),
          zIndex: isSelected ? 202 : 102,
        }),
      ];

      return style;
    },
    _getLineStyle(feature, isMedia, isSelected) {
      const isRelated =
        feature.getId() + "" !== "wm-main-feature" &&
        !!this.feature &&
        !this.features &&
        !!this.related;
      let style = [];

      let color = isRelated ? "#66b3ff" : "#ff0000",
        strokeWidth = isRelated ? 2 : 4,
        lineDash = [],
        lineCap = "round",
        zIndex = isRelated ? 100 : 200;

      style.push(
        new Style({
          stroke: new Stroke({
            color: color,
            width: strokeWidth,
            lineDash: lineDash,
            lineCap: lineCap,
          }),
          zIndex: zIndex + 2,
        })
      );

      return style;
    },
    _getPolygonStyle(feature, isMedia, isSelected) {
      const isRelated =
        feature.getId() + "" !== "wm-main-feature" &&
        !!this.feature &&
        !this.features &&
        !!this.related;

      let style = [],
        color = isRelated ? "#66b3ff" : "#ff0000",
        colorRgb = isRelated ? "102, 179, 255" : "255, 0, 0",
        fillOpacity = 0.3,
        strokeWidth = 2,
        strokeOpacity = 1,
        lineDash = [],
        fillColor = "";

      fillColor = "rgba(" + colorRgb + "," + fillOpacity + ")";
      color = "rgba(" + colorRgb + "," + strokeOpacity + ")";

      style.push(
        new Style({
          fill: new Fill({
            color: fillColor,
          }),
          stroke: new Stroke({
            color: color,
            width: strokeWidth,
            lineDash: lineDash,
          }),
          zIndex: isRelated ? 100 : 200,
        })
      );

      return style;
    },
    drawFeature() {
      if (!!this.feature && !!this.feature.geometry) {
        const features = new GeoJSON({
          featureProjection: "EPSG:3857",
        }).readFeatures(this.feature);
        this.featureSource.addFeatures([features[0]]);

        const extent = this.featureSource.getExtent();

        this.view.fit(extent, {
          padding: [20, 20, 40, 20],
        });
      }
    },
    drawMedia() {
      if (!!this.media) {
        const features = new GeoJSON({
          featureProjection: "EPSG:3857",
        }).readFeatures(this.media);
        for (let i in features) {
          features[i].setId(features[i].get("id"));
        }
        this.mediaSource.addFeatures(features);
      }
    },
    showOverlay(id) {
      for (let media of this.mediaSource.getFeatures()) {
        if (media.getId() + "" === id + "") {
          this._toggleOverlay(media);
          break;
        }
      }
    },
    hideOverlay() {
      this._toggleOverlay();
    },
    _toggleOverlay(feature) {
      const coordinate =
        feature &&
        feature.getGeometry() &&
        feature.getGeometry().getCoordinates();
      if (coordinate) {
        if (!this.overlayPopup) {
          this.overlayPopup = new Overlay({
            element: document.getElementById("overlayPopupContainer"),
          });
        }
        this.overlayPopup.setPosition(coordinate);

        this.map.addOverlay(this.overlayPopup);
        let properties = feature.getProperties(),
          url =
            properties.sizes && properties.sizes["150x150"]
              ? properties.sizes["150x150"]
              : properties.url,
          nameObj = properties.name ? properties.name : {},
          name = "";

        if (Object.keys(nameObj).length > 0)
          name = nameObj[Object.keys(nameObj)[0]];

        if (document.getElementById("popupImageLabel").innerHTML !== name)
          document.getElementById("popupImageLabel").innerHTML = name;

        let popupImage = document.getElementById("popupImage");
        if (popupImage.style.backgroundImage !== "url(" + url + ")")
          popupImage.style.backgroundImage = "url(" + url + ")";
      } else if (this.overlayPopup) {
        this.map.removeOverlay(this.overlayPopup);
      }
    },
  },
};
</script>

<style scoped>
@import "../../../node_modules/ol/ol.css";

#overlayPopupContainer {
  padding: 5px;
  width: 240px;
  height: 130px;
  background-color: white;
  position: absolute;
  top: -150px;
  left: -125px;
}

#overlayPopupContainer .bottom-popup {
  background-color: white;
  height: 25px;
  -webkit-clip-path: polygon(50% 0%, 30% 0, 52% 100%);
  clip-path: polygon(50% 0%, 30% 0, 52% 100%);
  margin-top: 0px;
}

#overlayPopupContainer #overlayPopup {
  display: flex;
  justify-content: space-evenly;
  align-items: center;
  flex-direction: column;
  height: 100%;
}

#overlayPopupContainer #overlayPopup #popupImage {
  background-position: center;
  background-size: contain;
  background-repeat: no-repeat;
}
</style>
