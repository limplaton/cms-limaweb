
import { translate } from '@/Core/i18n'

import EmailAccountsCreate from './views/Accounts/EmailAccountsCreate.vue'
import EmailAccountsEdit from './views/Accounts/EmailAccountsEdit.vue'
import EmailAccountsIndex from './views/Accounts/EmailAccountsIndex.vue'
import InboxMessage from './views/Inbox/InboxMessage.vue'
import Inbox from './views/Inbox/InboxMessages.vue'
import InboxMessages from './views/Inbox/InboxMessagesTable.vue'
import ScheduledEmailsIndex from './views/ScheduledEmailsIndex.vue'

export default [
  {
    path: '/inbox',
    name: 'inbox',
    component: Inbox,
    meta: {
      title: translate('mailclient::inbox.inbox'),
    },
    children: [
      {
        path: ':account_id/folder/:folder_id/messages',
        components: {
          messages: InboxMessages,
        },
        name: 'inbox-messages',
        meta: {
          title: translate('mailclient::inbox.inbox'),
        },
      },
      {
        path: ':account_id/folder/:folder_id/messages/:id',
        components: {
          message: InboxMessage,
        },
        name: 'inbox-message',
        meta: {
          scrollToTop: false,
        },
      },
    ],
  },
  {
    path: '/mail/accounts',
    name: 'email-accounts-index',
    component: EmailAccountsIndex,
    meta: {
      title: translate('mailclient::mail.account.accounts'),
    },
    children: [
      {
        path: 'create',
        name: 'create-email-account',
        component: EmailAccountsCreate,
        meta: { title: translate('mailclient::mail.account.create') },
      },
      {
        path: ':id/edit',
        name: 'edit-email-account',
        component: EmailAccountsEdit,
      },
    ],
  },
  {
    path: '/mail/scheduled',
    name: 'scheduled-emails-index',
    component: ScheduledEmailsIndex,
    meta: { title: translate('mailclient::schedule.scheduled_emails') },
  },
]
