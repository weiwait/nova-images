Nova.booting((Vue, router, store) => {
  Vue.component('index-nova-images', require('./components/IndexField'))
  Vue.component('detail-nova-images', require('./components/DetailField'))
  Vue.component('form-nova-images', require('./components/FormField'))
})
