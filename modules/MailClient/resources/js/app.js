
import { usePrivateChannel } from '@/Core/composables/useBroadcast'

import RecordTabTimelineEmail from './components/RecordTabTimelineEmail.vue'
import DetailEmailSendableField from './fields/Detail/EmailSendableField.vue'
import FormMailEditorField from './fields/Form/MailEditorField.vue'
import IndexEmailSendableField from './fields/Index/EmailSendableField.vue'
import EmailAccountsStore from './store/EmailAccounts'
import EmailsTab from './views/Emails/RecordTabEmails.vue'
import EmailsTabPanel from './views/Emails/RecordTabEmailsPanel.vue'
import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router, store) {
    app.component('RecordTabTimelineEmail', RecordTabTimelineEmail)

    // Fields
    app.component('FormMailEditorField', FormMailEditorField)
    app.component('DetailEmailSendableField', DetailEmailSendableField)
    app.component('IndexEmailSendableField', IndexEmailSendableField)

    // Tabs
    app.component('EmailsTab', EmailsTab)
    app.component('EmailsTabPanel', EmailsTabPanel)

    store.registerModule('emailAccounts', EmailAccountsStore)

    routes.forEach(route => router.addRoute(route))

    usePrivateChannel(
      'inbox',
      '.Modules\\MailClient\\App\\Events\\EmailAccountsSynchronized',
      e => {
        // eslint-disable-next-line vue/custom-event-name-casing
        this.$emit('email-accounts-sync-finished', e)

        this.request('mail/accounts/unread').then(({ data }) =>
          store.dispatch('emailAccounts/updateUnreadCountUI', data)
        )
      }
    )
  })
}
