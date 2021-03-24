<template>
    <div ref="wm-map-root"
         style="width: 100%; height: 100%">
    </div>
</template>

<script>
import View from 'ol/View'
import Map from 'ol/Map'
import TileLayer from 'ol/layer/Tile'
import XYZ from 'ol/source/XYZ';
import {transform, transformExtent} from "ol/proj";
import Attribution from 'ol/control/Attribution';
import Zoom from 'ol/control/Zoom';
import ScaleLine from 'ol/control/ScaleLine';
import FullScreen from 'ol/control/FullScreen';
import VectorSource from 'ol/source/Vector';
import VectorLayer from 'ol/layer/Vector';
import GeoJSON from 'ol/format/GeoJSON';
import Style from 'ol/style/Style';
import CircleStyle from 'ol/style/Circle';
import Stroke from 'ol/style/Stroke';
import Fill from 'ol/style/Fill';
import {defaults as defaultInteractions} from "ol/interaction";

export default {
    name: 'WmMapContainer',
    components: {},
    props: {
        geojson: String
    },
    data: () => ({
        map: null,
        view: null,
        vectorLayer: null,
        vectorSource: null
    }),
    mounted() {
        this.vectorSource = new VectorSource({
            features: [],
        });
        this.vectorLayer = new VectorLayer({
            source: this.vectorSource,
            visible: true,
            style: (feature) => {
                return this._style(feature);
            },
            updateWhileAnimating: true,
            updateWhileInteracting: true,
            zIndex: 50,
        });

        this.view = new View({
            center: this._fromLonLat([10.4, 43, 71]),
            maxZoom: 17,
            minZoom: 3,
            projection: "EPSG:3857",
            constrainOnlyCenter: true,
            zoom: 6,
        });
        this.map = new Map({
            target: this.$refs['wm-map-root'],
            layers: [
                new TileLayer({
                    source: new XYZ({
                        maxZoom: 21,
                        minZoom: 1,
                        tileUrlFunction: (c) => {
                            return 'https://api.webmapp.it/tiles/' + c[0] + "/" + c[1] + "/" + c[2] + ".png";
                        },
                        projection: "EPSG:3857",
                        tileSize: [256, 256],
                        attributions: [
                            "© <a href='https://webmapp.it' target='_blank'>Webmapp</a>",
                            "© <a href='http://www.openstreetmap.org/' target='_blank'>OpenStreetMap</a>"
                        ]
                    })
                }),
                this.vectorLayer
            ],
            view: this.view,
            controls: [
                new Zoom(),
                new ScaleLine(),
                new FullScreen(),
                new Attribution({
                    collapsed: false,
                    collapsible: false
                })
            ],
            interactions:
                defaultInteractions({
                    mouseWheelZoom: false,
                    doubleClickZoom: true,
                    shiftDragZoom: true,
                    dragPan: true,
                    altShiftDragRotate: true,
                    pinchRotate: true,
                    pinchZoom: true,
                }).getArray()

        });

        this.updateSource(this.geojson)
    },
    watch: {
        geojson(value) {
            this.updateSource(value);
        }
    },
    methods: {
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
        _style(feature) {
            if (feature.getGeometry().getType() === "Point")
                return this._getPoiStyle();
                // else if (
                //     feature.getGeometry().getType() === "LineString" ||
                //     feature.getGeometry().getType() === "MultiLineString"
                // )
                //     return this._getLineStyle();
                // else if (
                //     feature.getGeometry().getType() === "Polygon" ||
                //     feature.getGeometry().getType() === "MultiPolygon"
                // )
            //     return this._getPolygonStyle();
            else return [];
        },
        _getPoiStyle() {
            let style,
                color = "#ff0000";

            let maxRadius = 1.7,
                minRadius = 1,
                minZoom = 8,
                currentZoom = this.view.getZoom(),
                zoomFactor =
                    currentZoom < minZoom
                        ? minRadius
                        : ((maxRadius - minRadius) / (16 - minZoom)) *
                        (currentZoom - minZoom) +
                        minRadius,
                borderSize = 3;

            style = [
                new Style({
                    image: new CircleStyle({
                        radius:
                            7 * zoomFactor +
                            borderSize / 2,
                        fill: new Fill({color: "#fff"}),
                        stroke: new Stroke({
                            color:
                                "rgba(125, 125, 125, 0.75)",
                            width: 1,
                        }),
                    }),
                    zIndex: 100,
                }),
                new Style({
                    image: new CircleStyle({
                        radius:
                            7 * zoomFactor -
                            borderSize / 2,
                        fill: new Fill({color: color}),
                    }),
                    zIndex: 101,
                })
            ];

            return style;
        },
        updateSource(geojson) {
            const features = new GeoJSON({
                featureProjection: 'EPSG:3857',
            }).readFeatures(geojson);

            this.vectorSource.clear();
            this.vectorSource.addFeatures(features);

            this.view.fit(this.vectorSource.getExtent(), {
                // padding: [20, 20, 20, 20]
            });
        }
    }
}
</script>

<style scoped>
@import "../../../node_modules/ol/ol.css";
</style>
