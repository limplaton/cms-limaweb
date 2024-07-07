
import { translate } from '@/Core/i18n'

import DocumentsTab from './components/RecordTabDocument.vue'
import DocumentsTabPanel from './components/RecordTabDocumentPanel.vue'
import RecordTabTimelineDocument from './components/RecordTabTimelineDocument.vue'
import SettingsDocuments from './components/SettingsDocuments.vue'
import DocumentPublicView from './views/DocumentsPublicView.vue'
import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    app.component('DocumentPublicView', DocumentPublicView)
    app.component('DocumentsTab', DocumentsTab)
    app.component('DocumentsTabPanel', DocumentsTabPanel)
    app.component('RecordTabTimelineDocument', RecordTabTimelineDocument)

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'documents',
      name: 'document-settings',
      component: SettingsDocuments,
      meta: {
        title: translate('documents::document.documents'),
      },
    })
  })
}
