Nova.booting((Vue, router, store) => {
  Vue.component(
    'index-map-multi-purpose-nova3',
    require('./components/IndexField').default
  )
  Vue.component(
    'detail-map-multi-purpose-nova3',
    require('./components/DetailField').default
  )
  Vue.component(
    'form-map-multi-purpose-nova3',
    require('./components/FormField').default
  )
  Vue.component(
    'wm-map-multi-purpose-nova3',
    require('./components/MapComponent').default
  )
})
