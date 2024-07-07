
import { translate } from '@/Core/i18n'

import DocumentsCreate from './views/DocumentsCreate.vue'
import DocumentsEdit from './views/DocumentsEdit.vue'
import DocumentsIndex from './views/DocumentsIndex.vue'
import DocumentsTemplatesCreate from './views/DocumentsTemplatesCreate.vue'
import DocumentsTemplatesEdit from './views/DocumentsTemplatesEdit.vue'
import DocumentsTemplatesIndex from './views/DocumentsTemplatesIndex.vue'

export default [
  {
    path: '/documents',
    name: 'document-index',
    component: DocumentsIndex,
    meta: {
      title: translate('documents::document.documents'),
    },
    // eslint-disable-next-line no-unused-vars
    beforeEnter: (to, from) => {
      to.meta.initialize = to.name === 'document-index'
    },
    children: [
      {
        path: 'create',
        name: 'create-document',
        components: {
          create: DocumentsCreate,
        },
        meta: { title: translate('documents::document.create') },
      },
      {
        path: ':id',
        name: 'view-document',
        components: {
          edit: DocumentsEdit,
        },
      },
      {
        path: ':id/edit',
        name: 'edit-document',
        components: {
          edit: DocumentsEdit,
        },
      },
    ],
  },
  {
    path: '/document-templates',
    name: 'document-templates-index',
    component: DocumentsTemplatesIndex,
    meta: {
      title: translate('documents::document.template.templates'),
    },
    children: [
      {
        path: 'create',
        name: 'create-document-template',
        components: {
          create: DocumentsTemplatesCreate,
        },
        meta: {
          title: translate('documents::document.template.create'),
        },
      },
      {
        path: ':id/edit',
        name: 'edit-document-template',
        components: {
          edit: DocumentsTemplatesEdit,
        },
      },
    ],
  },
]
