
import { translate } from '@/Core/i18n'

import SettingsTranslator from './components/SettingsTranslator.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    router.addRoute('settings', {
      path: '/settings/translator',
      component: SettingsTranslator,
      meta: { title: translate('translator::translator.translator') },
    })
  })
}
