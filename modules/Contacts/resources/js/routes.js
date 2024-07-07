
import { translate } from '@/Core/i18n'

import DealsCreate from '@/Deals/views/DealsCreate.vue'

import CompaniesCreate from './views/CompaniesCreate.vue'
import CompaniesIndex from './views/CompaniesIndex.vue'
import CompaniesView from './views/CompaniesView.vue'
import ContactsCreate from './views/ContactsCreate.vue'
import ContactsIndex from './views/ContactsIndex.vue'
import ContactsView from './views/ContactsView.vue'

export default [
  {
    path: '/companies',
    name: 'company-index',
    component: CompaniesIndex,
    meta: {
      title: translate('contacts::company.companies'),
      initialize: false,
    },
    // eslint-disable-next-line no-unused-vars
    beforeEnter: (to, from) => {
      to.meta.initialize = to.name === 'company-index'
    },
    children: [
      {
        path: 'create',
        name: 'create-company',
        components: {
          create: CompaniesCreate,
        },
        meta: { title: translate('contacts::company.create') },
      },
    ],
  },
  {
    path: '/companies/:id',
    name: 'view-company',
    component: CompaniesView,
    children: [
      {
        path: 'contacts/create',
        component: ContactsCreate,
        name: 'createContactViaCompany',
      },
      {
        path: 'deals/create',
        component: DealsCreate,
        name: 'createDealViaCompany',
      },
    ].map(route => Object.assign(route, { meta: { scrollToTop: false } })),
  },
  // contact routes
  {
    path: '/contacts',
    name: 'contact-index',
    component: ContactsIndex,
    meta: {
      title: translate('contacts::contact.contacts'),
      initialize: false,
    },
    // eslint-disable-next-line no-unused-vars
    beforeEnter: (to, from) => {
      to.meta.initialize = to.name === 'contact-index'
    },
    children: [
      {
        path: 'create',
        name: 'create-contact',
        components: {
          create: ContactsCreate,
        },
        meta: { title: translate('contacts::contact.create') },
      },
    ],
  },
  {
    path: '/contacts/:id',
    name: 'view-contact',
    component: ContactsView,
    children: [
      {
        path: 'companies/create',
        component: CompaniesCreate,
        name: 'createCompanyViaContact',
      },
      {
        path: 'deals/create',
        component: DealsCreate,
        name: 'createDealViaContact',
      },
    ].map(route => Object.assign(route, { meta: { scrollToTop: false } })),
  },
]
