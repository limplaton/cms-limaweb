
import { translate } from '@/Core/i18n'

import CompanyFloatingModal from './components/CompanyFloatingModal.vue'
import ContactFloatingModal from './components/ContactFloatingModal.vue'
import CreateCompanyModal from './components/CreateCompanyModal.vue'
import CreateContactModal from './components/CreateContactModal.vue'
import SettingsCompanies from './components/SettingsCompanies.vue'
import DetailPhoneField from './fields/Detail/PhoneField.vue'
import FormPhoneField from './fields/Form/PhoneField.vue'
import IndexPhoneField from './fields/Index/PhoneField.vue'
import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    app.component('CompanyFloatingModal', CompanyFloatingModal)
    app.component('ContactFloatingModal', ContactFloatingModal)

    app.component('CreateCompanyModal', CreateCompanyModal)
    app.component('CreateContactModal', CreateContactModal)

    app.component('FormPhoneField', FormPhoneField)
    app.component('DetailPhoneField', DetailPhoneField)
    app.component('IndexPhoneField', IndexPhoneField)

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'companies',
      component: SettingsCompanies,
      name: 'settings-companies',
      meta: { title: translate('contacts::company.companies') },
    })
  })
}
