Nova.booting((Vue, router, store) => {
  Vue.component('index-featureimagepoipopup', require('./components/IndexField'))
  Vue.component('detail-featureimagepoipopup', require('./components/DetailField'))
  Vue.component('form-featureimagepoipopup', require('./components/FormField'))
})
