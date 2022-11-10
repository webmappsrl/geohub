<template>
    <div id="container">
        <div :id="mapRef" class="wm-map"></div>
    </div>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import "leaflet/dist/leaflet.css";
import L from "leaflet";
const DEFAULT_TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const DEFAULT_ATTRIBUTION = '<a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery (c) <a href="https://www.mapbox.com/">Mapbox</a>';
const DEFAULT_CENTER = [0, 0];
const DEFAULT_MINZOOM = 8;
const DEFAULT_MAXZOOM = 17;
const DEFAULT_DEFAULTZOOM = 8;
const linestringOption = {
    fillColor: '#f03',
    fillOpacity: 0.5,
};
let mapDiv = null;
let pois = null;
export default {
    name: "MapMultiPurpose",
    mixins: [FormField, HandlesValidationErrors],
    props: ['field', 'geojson'],
    data() {
        return {
            mapRef: `mapContainer-${Math.floor(Math.random() * 10000 + 10)}`,
            uploadFileContainer: 'uploadFileContainer',
        }
    },
    methods: {
        initMap() {
            
            setTimeout(() => {
                const center = this.field.center ?? this.center ?? DEFAULT_CENTER;
                const defaultZoom = this.field.defaultZoom ?? DEFAULT_DEFAULTZOOM;
                const poigeojson = this.field.poigeojson;
                mapDiv = L.map(this.mapRef).setView(center, defaultZoom);
                L.tileLayer(
                    this.field.tiles ?? DEFAULT_TILES,
                    {
                        attribution: this.field.attribution ?? DEFAULT_ATTRIBUTION,
                        maxZoom: this.field.maxZoom ?? DEFAULT_MAXZOOM,
                        minZoom: this.field.minZoom ?? DEFAULT_MINZOOM,
                        id: "mapbox/streets-v11",
                    }
                ).addTo(mapDiv);
                function creategreenIcon (feature, latlng) {
                let greenIcon = L.icon({
                    iconUrl: 'https://ecmedia.s3.eu-central-1.amazonaws.com/miscellaneous/circle-green.png',
                    shadowSize:   [0,0], // size of the shadow
                    iconSize:     [25, 25], // width and height of the image in pixels
                    popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
                })
                    return L.marker(latlng, { icon: greenIcon })
                }
                function createorangeIcon (feature, latlng) {
                let orangeIcon = L.icon({
                    iconUrl: 'https://ecmedia.s3.eu-central-1.amazonaws.com/miscellaneous/circle-orange.png',
                    shadowSize:   [0,0], // size of the shadow
                    iconSize:     [25, 25], // width and height of the image in pixels
                    popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
                })
                    return L.marker(latlng, { icon: orangeIcon })
                }
                if (poigeojson != null) {
                    pois = L.geoJson(JSON.parse(poigeojson), {
                        pointToLayer: createorangeIcon,
                        onEachFeature: function (feature, pois) {
                            pois.bindPopup('<h1>'+feature.properties.name+'</h1><p>visits: '+feature.properties.visits+'</p>');
                        }
                    }).addTo(mapDiv);
                    mapDiv.fitBounds(pois.getBounds());
                }
            }, 300);
        }
    },
    watch: {
        
    },
    mounted() {
        this.initMap();
    },
};
</script>