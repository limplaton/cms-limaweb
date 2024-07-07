<template>
  <IDropdown placement="bottom-end">
    <IDropdownButton :text="$t('core::actions.actions')" basic />

    <IDropdownMenu>
      <IDropdownItem
        v-for="action in actions"
        :key="action.uriKey"
        :text="action.name"
        @click="(selectedAction = action), run(action)"
      />
    </IDropdownMenu>
  </IDropdown>
</template>

<script setup>
import { computed, toRef } from 'vue'
import castArray from 'lodash/castArray'

import { useAction } from '../../composables/useAction'

const props = defineProps({
  resourceName: { type: String, required: true },
  ids: { type: [Number, String, Array], required: true },
  actionRequestParams: { type: Object, default: () => ({}) },
  actions: { type: Array, default: () => [] },
})

const emit = defineEmits(['run'])

const actionIds = computed(() => castArray(props.ids))

const { run, action: selectedAction } = useAction(
  actionIds,
  {
    resourceName: toRef(props, 'resourceName'),
    requestParams: toRef(props, 'actionRequestParams'),
  },
  response => emit('run', response)
)
</script>
