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
      :display-as-badges="field.displayAsBadges"
      :each-on-new-line="field.eachOnNewLine"
      :on-option-click="field.onOptionClick"
      :label-key="field.labelKey"
    />
  </BaseDetailField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>
