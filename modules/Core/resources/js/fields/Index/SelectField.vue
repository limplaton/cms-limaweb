<template>
  <BaseIndexField
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <OptionsList
      :value="field.value"
      :display-as-badges="field.displayAsBadges"
      :on-option-click="field.onOptionClick"
      :label-key="field.labelKey"
      :value-key="field.valueKey"
      :list="field.options"
    />
  </BaseIndexField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>
