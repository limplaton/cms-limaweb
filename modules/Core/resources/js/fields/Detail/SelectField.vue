<template>
  <BaseDetailField
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <OptionsList
      :value="selectedOption"
      :display-as-badges="field.displayAsBadges"
      :on-option-click="field.onOptionClick"
      :label-key="field.labelKey"
    />
  </BaseDetailField>
</template>

<script setup>
import { computed } from 'vue'

import OptionsList from '../OptionsList.vue'

const props = defineProps([
  'resource',
  'resourceName',
  'resourceId',
  'field',
  'isFloating',
])

const selectedOption = computed(() =>
  props.field.value
    ? props.field.options.find(
        option => option[props.field.valueKey] == props.field.value
      )
    : null
)
</script>
