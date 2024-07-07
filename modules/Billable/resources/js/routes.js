
import { translate } from '@/Core/i18n'

import ProductsCreate from './views/ProductsCreate.vue'
import ProductsEdit from './views/ProductsEdit.vue'
import ProductsIndex from './views/ProductsIndex.vue'

export default [
  {
    path: '/products',
    name: 'product-index',
    component: ProductsIndex,
    meta: {
      title: translate('billable::product.products'),
    },
    children: [
      {
        path: 'create',
        name: 'create-product',
        component: ProductsCreate,
        meta: { title: translate('billable::product.create') },
      },
      {
        path: ':id',
        name: 'view-product',
        component: ProductsEdit,
      },
      {
        path: ':id/edit',
        name: 'edit-product',
        component: ProductsEdit,
      },
    ],
  },
]
