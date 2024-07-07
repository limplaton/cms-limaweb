
import { translate } from '@/Core/i18n'

import CreateDealModal from './components/CreateDealModal.vue'
import DealFloatingModal from './components/DealFloatingModal.vue'
import DealPresentationCard from './components/DealPresentationCard.vue'
import SettingsDeals from './components/SettingsDeals.vue'
import FormLostReasonField from './fields/Form/LostReasonField.vue'
import FormPipelineStageField from './fields/Form/PipelineStageField.vue'
import IndexLostReasonField from './fields/Index/LostReasonField.vue'
import IndexPipelineStageField from './fields/Index/PipelineStageField.vue'
import DealsPipelinesCreate from './views/DealsPipelinesCreate.vue'
import DealsPipelinesEdit from './views/DealsPipelinesEdit.vue'
import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting((app, router) => {
    app.component('DealPresentationCard', DealPresentationCard)
    app.component('DealFloatingModal', DealFloatingModal)
    app.component('CreateDealModal', CreateDealModal)

    // Fields
    app.component('FormLostReasonField', FormLostReasonField)
    app.component('FormPipelineStageField', FormPipelineStageField)
    app.component('IndexPipelineStageField', IndexPipelineStageField)
    app.component('IndexLostReasonField', IndexLostReasonField)

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'deals',
      name: 'deals-settings-index',
      component: SettingsDeals,
      meta: {
        title: translate('deals::deal.deals'),
      },
      children: [
        {
          path: 'pipelines/create',
          name: 'create-pipeline',
          component: DealsPipelinesCreate,
          meta: { title: translate('deals::deal.pipeline.create') },
        },
      ],
    })

    router.addRoute('settings', {
      path: 'deals/pipelines/:id/edit',
      name: 'edit-pipeline',
      component: DealsPipelinesEdit,
      meta: { title: translate('deals::deal.pipeline.edit') },
    })
  })
}
