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
        feature: Object,
        features: Object,
        related: Object,
        editable: Boolean
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
            center: this._fromLonLat([10.4, 43]),
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
                            "© <a href='https://www.openstreetmap.org/' target='_blank'>OpenStreetMap</a>"
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

        if (this.editable) {
            this.map.on('click', (event) => {
                if (!this.feature) {
                    this.feature = {};
                }
                if (!this.feature.type) this.feature.type = 'Feature';
                if (!this.feature.properties) this.feature.properties = {};
                if (!this.feature.geometry) {
                    this.feature.geometry = {
                        type: "Point",
                    };
                }
                this.feature.geometry.coordinates = this._toLonLat(event.coordinate);
                this.updateSource(true);
            });
        }
        this.updateSource();
    },
    watch: {
        feature() {
        },
        related() {
            this.updateSource();
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
                return this._getPoiStyle(feature);
            else if (
                feature.getGeometry().getType() === "LineString" ||
                feature.getGeometry().getType() === "MultiLineString"
            )
                return this._getLineStyle(feature);
            else if (
                feature.getGeometry().getType() === "Polygon" ||
                feature.getGeometry().getType() === "MultiPolygon"
            )
                return this._getPolygonStyle(feature);
            else return [];
        },
        _getPoiStyle(feature) {
            const isRelated = feature.getId() + "" !== "wm-main-feature" && !!this.feature && !this.features && !!this.related;
            let style,
                color = isRelated ? "#66b3ff" : "#ff0000";

            let maxRadius = isRelated ? 1.2 : 1.7,
                minRadius = isRelated ? 0.7 : 1,
                minZoom = 8,
                currentZoom = this.view.getZoom(),
                zoomFactor =
                    currentZoom < minZoom
                        ? minRadius
                        : ((maxRadius - minRadius) / (16 - minZoom)) *
                        (currentZoom - minZoom) +
                        minRadius,
                borderSize = isRelated ? 2 : 3;

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
                    zIndex: isRelated ? 100 : 200,
                }),
                new Style({
                    image: new CircleStyle({
                        radius:
                            7 * zoomFactor -
                            borderSize / 2,
                        fill: new Fill({color: color}),
                    }),
                    zIndex: isRelated ? 101 : 201,
                })
            ];

            return style;
        },
        _getLineStyle(feature) {
            const isRelated = feature.getId() + "" !== "wm-main-feature" && !!this.feature && !this.features && !!this.related;
            let style = [];

            let color = isRelated ? "#66b3ff" : "#ff0000",
                strokeWidth = isRelated ? 2 : 4,
                lineDash = [],
                lineCap = 'round',
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
        _getPolygonStyle(feature) {
            const isRelated = feature.getId() + "" !== "wm-main-feature" && !!this.feature && !this.features && !!this.related;

            let style = [],
                color = isRelated ? "#66b3ff" : "#ff0000",
                colorRgb = isRelated ? "102, 179, 255" : "255, 0, 0",
                fillOpacity = 0.3,
                strokeWidth = 2,
                strokeOpacity = 1,
                lineDash = [],
                fillColor = '';

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
        updateSource(skip_fit) {
            this.vectorSource.clear();
            if (!!this.feature && !!this.feature.geometry) {
                const features = new GeoJSON({
                    featureProjection: 'EPSG:3857',
                }).readFeatures(this.feature);
                features[0].setId('wm-main-feature');
                this.vectorSource.addFeatures(features);

                const extent = this.vectorSource.getExtent();

                if (typeof this.related !== 'undefined'
                    && this.related.type === 'FeatureCollection'
                    && typeof this.related.features !== 'undefined'
                    && typeof this.related.features.length === 'number'
                    && this.related.features.length > 0) {
                    const related = new GeoJSON({
                        featureProjection: 'EPSG:3857',
                    }).readFeatures(this.related);
                    this.vectorSource.addFeatures(related);
                }

                if (!skip_fit) {
                    this.view.fit(extent, {
                        padding: [20, 20, 20, 20]
                    });
                }
            }

            if (!!this.features && this.features['type'] === 'FeatureCollection') {
                let featureCollection = {
                    type: "FeatureCollection",
                    features: []
                };
                for (let i in this.features['features']) {
                    if (!!this.features['features'][i] && !!this.features['features'][i].geometry)
                        featureCollection.features.push(this.features['features'][i]);
                }
                this.features = featureCollection;
                const features = new GeoJSON({
                    featureProjection: 'EPSG:3857',
                }).readFeatures(this.features);
                this.vectorSource.addFeatures(features);

                const extent = this.vectorSource.getExtent();

                if (!skip_fit) {
                    this.view.fit(extent, {
                        padding: [20, 20, 20, 20]
                    });
                }
            }
        }
    }
}
</script>

<style scoped>
@import "../../../node_modules/ol/ol.css";
</style>
