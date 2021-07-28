Nova.booting((Vue, router, store) => {
  Vue.component('index-ecpoipopup', require('./components/IndexField'))
  Vue.component('detail-ecpoipopup', require('./components/DetailField'))
  Vue.component('form-ecpoipopup', require('./components/FormField'))
})
