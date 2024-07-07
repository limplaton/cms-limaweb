
import { translate } from '@/Core/i18n'

import SettingsProducts from './components/SettingsProducts.vue'
import DetailBillableAmountField from './fields/Detail/BillableAmountField.vue'
import IndexBillableAmountField from './fields/Index/BillableAmountField.vue'
import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    app.component('DetailBillableAmountField', DetailBillableAmountField)
    app.component('IndexBillableAmountField', IndexBillableAmountField)

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'products',
      component: SettingsProducts,
      name: 'settings-products',
      meta: { title: translate('billable::product.products') },
    })
  })
}
