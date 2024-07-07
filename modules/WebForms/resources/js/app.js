
import { translate } from '@/Core/i18n'

import RecordTabTimelineWebFormSubmission from './components/RecordTabTimelineWebFormSubmission.vue'
import SettingsWebForms from './components/SettingsWebForms.vue'
import WebFormPublicView from './views/WebFormPublicView.vue'
import CreateWebForm from './views/WebFormsCreate.vue'
import EditWebForm from './views/WebFormsEdit.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    app.component('WebFormPublicView', WebFormPublicView)

    app.component(
      'WebFormSubmissionChangelog',
      RecordTabTimelineWebFormSubmission
    )

    router.addRoute('settings', {
      path: 'forms/:id/edit',
      name: 'web-form-edit',
      component: EditWebForm,
    })

    router.addRoute('settings', {
      path: 'forms',
      name: 'web-forms-index',
      component: SettingsWebForms,
      meta: {
        title: translate('webforms::form.forms'),
      },
      children: [
        {
          path: 'create',
          name: 'web-form-create',
          component: CreateWebForm,
        },
      ],
    })
  })
}
