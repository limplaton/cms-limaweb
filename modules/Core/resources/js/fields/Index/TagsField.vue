<template>
  <BaseIndexField
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <OptionsList
      label-key="name"
      :value="field.value"
      :display-as-badges="true"
    />
  </BaseIndexField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>
