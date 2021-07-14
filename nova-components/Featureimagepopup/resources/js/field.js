Nova.booting((Vue, router, store) => {
  Vue.component('index-featureimagepopup', require('./components/IndexField'))
  Vue.component('detail-featureimagepopup', require('./components/DetailField'))
  Vue.component('form-featureimagepopup', require('./components/FormField'))
})
