Nova.booting((Vue, router, store) => {
    Vue.component('modal-ecmediapopup', require('./components/ModalEcMedia'))
    Vue.component('index-ecmediapopup', require('./components/IndexField'))
    Vue.component('detail-ecmediapopup', require('./components/DetailField'))
    Vue.component('form-ecmediapopup', require('./components/FormField'))
})
