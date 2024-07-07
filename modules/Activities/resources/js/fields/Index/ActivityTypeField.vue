<template>
  <BaseIndexField
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <OptionsList
      :value="field.value"
      :label-key="field.labelKey"
      :on-option-click="field.onOptionClick"
      :display-as-badges="true"
    />
  </BaseIndexField>
</template>

<script setup>
import OptionsList from '@/Core/fields/OptionsList.vue'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>
