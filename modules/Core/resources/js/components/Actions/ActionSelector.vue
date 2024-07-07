<template>
  <component
    :is="type === 'select' ? ActionSelectorSelect : ActionSelectorDropdown"
    v-if="hasActionsAvailable"
    :actions="filteredActions"
    @run="emitRunEvent"
  />
</template>

<script setup>
import { computed } from 'vue'

import ActionSelectorDropdown from './ActionSelectorDropdown.vue'
import ActionSelectorSelect from './ActionSelectorSelect.vue'

const props = defineProps({
  actions: { type: Array, default: () => [] },
  type: { required: true, type: String },
  view: {
    default: 'detail',
    validator: function (value) {
      return ['index', 'detail'].indexOf(value) !== -1
    },
  },
})

const emit = defineEmits(['run'])

const filteredActions = computed(() =>
  props.actions.filter(
    action =>
      !(props.view === 'detail' && action.hideOnDetail === true) &&
      !(props.view === 'index' && action.hideOnIndex === true)
  )
)

const hasActionsAvailable = computed(() => filteredActions.value.length > 0)

function emitRunEvent(response) {
  emit('run', response)
}
</script>
