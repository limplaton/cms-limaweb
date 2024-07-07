<template>
  <BaseIndexField
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <OptionsList
      :value="field.value"
      :field="field"
      :label-key="field.labelKey"
      :display-as-badges="field.displayAsBadges"
      :on-option-click="field.onOptionClick"
    />
  </BaseIndexField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>
