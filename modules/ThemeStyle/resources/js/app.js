
import { translate } from '@/Core/i18n'

import SettingsThemeStyle from './components/SettingsThemeStyle.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    router.addRoute('settings', {
      path: '/settings/theme-style',
      component: SettingsThemeStyle,
      meta: { title: translate('themestyle::style.theme_style') },
    })
  })
}
