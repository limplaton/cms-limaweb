<template>
  <ICustomSelect
    v-model="selectedAction"
    label="name"
    :clearable="false"
    :loading="actionIsRunning"
    :options="actions"
    :placeholder="$t('core::actions.select')"
    @option-selected="runWhenSelected ? run() : $emit('selected', $event)"
  />
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
  runWhenSelected: { type: Boolean, default: true },
})

const emit = defineEmits(['run', 'selected'])

const actionIds = computed(() => castArray(props.ids))

const {
  run,
  action: selectedAction,
  actionIsRunning,
} = useAction(
  actionIds,
  {
    resourceName: toRef(props, 'resourceName'),
    requestParams: toRef(props, 'actionRequestParams'),
  },
  response => emit('run', response)
)

defineExpose({ run })
</script>
