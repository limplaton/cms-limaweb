
import { translate } from '@/Core/i18n'

import SettingsBrands from './components/SettingsBrands.vue'
import BrandsCreate from './views/BrandsCreate.vue'
import BrandsEdit from './views/BrandsEdit.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    router.addRoute('settings', {
      path: '/settings/brands',
      component: SettingsBrands,
      meta: {
        title: translate('brands::brand.brands'),
        superAdmin: true,
      },
    })

    router.addRoute('settings', {
      path: '/settings/brands/create',
      component: BrandsCreate,
      name: 'create-brand',
      meta: { superAdmin: true },
    })

    router.addRoute('settings', {
      path: '/settings/brands/:id/edit',
      component: BrandsEdit,
      name: 'edit-brand',
      meta: { superAdmin: true },
    })
  })
}
