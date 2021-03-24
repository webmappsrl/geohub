Nova.booting((Vue, router, store) => {
    Vue.component('map-wm-embedmaps-field', require('./components/MapComponent'))
    Vue.component('index-wm-embedmaps-field', require('./components/IndexField'))
    Vue.component('detail-wm-embedmaps-field', require('./components/DetailField'))
    Vue.component('form-wm-embedmaps-field', require('./components/FormField'))
})
