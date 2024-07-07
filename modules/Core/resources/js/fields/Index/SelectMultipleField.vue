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
      :each-on-new-line="column.wrap"
      :on-option-click="field.onOptionClick"
      :label-key="field.labelKey"
    />
  </BaseIndexField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>
