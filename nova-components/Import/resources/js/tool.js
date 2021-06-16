Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'import',
      path: '/import',
      component: require('./components/Tool'),
    },
  ])
})
