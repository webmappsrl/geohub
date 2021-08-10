Nova.booting((Vue, router, store) => {
  Vue.component('index-feature-image-popup', require('./components/IndexField'))
  Vue.component('detail-feature-image-popup', require('./components/DetailField'))
  Vue.component('form-feature-image-popup', require('./components/FormField'))
})
