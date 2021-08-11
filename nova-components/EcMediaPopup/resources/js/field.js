Nova.booting((Vue, router, store) => {
  Vue.component('modal-ec-media-popup', require('./components/ModalEcMedia'))
  Vue.component('index-ec-media-popup', require('./components/IndexField'))
  Vue.component('detail-ec-media-popup', require('./components/DetailField'))
  Vue.component('form-ec-media-popup', require('./components/FormField'))
})
