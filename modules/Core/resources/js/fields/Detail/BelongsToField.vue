<template>
  <BaseDetailField
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <OptionsList
      :value="field.value"
      :label-key="field.labelKey"
      :display-as-badges="field.displayAsBadges"
      :on-option-click="field.onOptionClick"
    />
  </BaseDetailField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>
