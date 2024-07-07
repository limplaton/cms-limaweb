
import { translate } from '@/Core/i18n'

import ActivityFloatingModal from './components/ActivityFloatingModal.vue'
import CreateActivityModal from './components/CreateActivityModal.vue'
import MyActivitiesCard from './components/MyActivitiesCard.vue'
import ActivitiesTab from './components/RecordTabActivity.vue'
import ActivitiesTabPanel from './components/RecordTabActivityPanel.vue'
import RecordTabTimelineActivity from './components/RecordTabTimelineActivity.vue'
import SettingsActivities from './components/SettingsActivities.vue'
import FormActivityDueDateField from './fields/Form/ActivityDueDateField.vue'
import FormActivityEndDateField from './fields/Form/ActivityEndDateField.vue'
import FormActivityTypeField from './fields/Form/ActivityTypeField.vue'
import FormGuestsSelectField from './fields/Form/GuestsSelectField.vue'
import IndexActivityDueDateField from './fields/Index/ActivityDueDateField.vue'
import IndexActivityEndDateField from './fields/Index/ActivityEndDateField.vue'
import IndexActivityTypeField from './fields/Index/ActivityTypeField.vue'
import routes from './routes'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    app.component('MyActivitiesCard', MyActivitiesCard)
    app.component('RecordTabTimelineActivity', RecordTabTimelineActivity)
    app.component('CreateActivityModal', CreateActivityModal)
    app.component('ActivityFloatingModal', ActivityFloatingModal)

    // Fields
    app.component('FormActivityDueDateField', FormActivityDueDateField)
    app.component('FormActivityEndDateField', FormActivityEndDateField)
    app.component('FormGuestsSelectField', FormGuestsSelectField)
    app.component('FormActivityTypeField', FormActivityTypeField)
    app.component('IndexActivityTypeField', IndexActivityTypeField)
    app.component('IndexActivityDueDateField', IndexActivityDueDateField)
    app.component('IndexActivityEndDateField', IndexActivityEndDateField)

    // Tabs
    app.component('ActivitiesTab', ActivitiesTab)
    app.component('ActivitiesTabPanel', ActivitiesTabPanel)

    // Routes
    routes.forEach(route => router.addRoute(route))

    router.addRoute('settings', {
      path: 'activities',
      name: 'activity-settings',
      component: SettingsActivities,
      meta: {
        title: translate('activities::activity.activities'),
      },
    })
  })
}
