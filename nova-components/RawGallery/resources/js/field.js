Nova.booting((Vue, router, store) => {
  Vue.component('index-raw-gallery', require('./components/IndexField'))
  Vue.component('detail-raw-gallery', require('./components/DetailField'))
  Vue.component('form-raw-gallery', require('./components/FormField'))
})
