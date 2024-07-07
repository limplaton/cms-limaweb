<template>
  <ITab v-memo="[countIncomplete]">
    <Icon icon="Calendar" />

    {{ $t('activities::activity.activities') }}

    <IBadge
      v-show="countIncomplete"
      variant="danger"
      :text="countIncomplete"
      pill
    />
  </ITab>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})

/**
 * Record incomplete activities count
 *
 * We will check if the actual resource record incomplete_activities is 0 but the actual
 * loaded activities have incomplete, in this case, we will return the value from the loaded activities
 *
 * This may happen e.q. if there is a workflows e.q. company created => create activity
 * But because the workflow is executed on app terminating, the resource record data
 * is already retrieved before termination and the incomplete_activities_for_user_count will be 0
 */
const countIncomplete = computed(() => {
  const incomplete = (props.resource.activities || []).filter(
    activity => !activity.is_completed
  )

  let count = props.resource.incomplete_activities_for_user_count

  if (count === 0 && incomplete.length > 0) {
    return incomplete.length
  }

  return count
})
</script>
