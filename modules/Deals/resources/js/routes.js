
import { useStorage } from '@vueuse/core'

import { translate } from '@/Core/i18n'

import CompaniesCreate from '@/Contacts/views/CompaniesCreate.vue'
import ContactsCreate from '@/Contacts/views/ContactsCreate.vue'

import DealsBoard from './views/DealsBoard.vue'
import DealsCreate from './views/DealsCreate.vue'
import DealsImport from './views/DealsImport.vue'
import DealsIndex from './views/DealsIndex.vue'
import DealsView from './views/DealsView.vue'

const isBoardDefaultView = useStorage('deals-board-view-default', false)

export default [
  {
    path: '/deals',
    name: 'deal-index',
    component: DealsIndex,
    meta: {
      title: translate('deals::deal.deals'),
      subRoutes: ['create-deal'],
      boardRoute: 'deal-board',
      initialize: false,
    },
    beforeEnter: async (to, from) => {
      // Check if the deals board is active
      if (
        isBoardDefaultView.value &&
        from.name != to.meta.boardRoute &&
        to.meta.subRoutes.indexOf(to.name) === -1
      ) {
        return { name: to.meta.boardRoute, query: to.query }
      }

      to.meta.initialize = to.name === 'deal-index'

      if (to.meta.subRoutes.indexOf(to.name) === -1) {
        isBoardDefaultView.value = false
      }
    },
    children: [
      {
        path: 'create',
        name: 'create-deal',
        components: {
          create: DealsCreate,
        },
        meta: { title: translate('deals::deal.create') },
      },
    ],
  },
  {
    path: '/import/deals',
    name: 'import-deal',
    component: DealsImport,
    meta: { title: translate('deals::deal.import') },
  },
  {
    path: '/deals/board',
    name: 'deal-board',
    component: DealsBoard,
    meta: {
      title: translate('deals::deal.deals'),
    },
    beforeEnter: () => {
      isBoardDefaultView.value = true
    },
  },
  {
    path: '/deals/:id',
    name: 'view-deal',
    component: DealsView,
    children: [
      {
        path: 'contacts/create',
        component: ContactsCreate,
        name: 'createContactViaDeal',
      },
      {
        path: 'companies/create',
        component: CompaniesCreate,
        name: 'createCompanyViaDeal',
      },
    ].map(route => Object.assign(route, { meta: { scrollToTop: false } })),
  },
]
