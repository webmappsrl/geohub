Nova.booting((Vue, router, store) => {
    Vue.component('modal-ecmediapoipopup', require('./components/ModalEcMedia'))
    Vue.component('index-ecmediapoipopup', require('./components/IndexField'))
    Vue.component('detail-ecmediapoipopup', require('./components/DetailField'))
    Vue.component('form-ecmediapoipopup', require('./components/FormField'))
})
