<template>
  <BaseDetailField
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <OptionsList
      label-key="name"
      :value="field.value"
      :display-as-badges="true"
    />
  </BaseDetailField>
</template>

<script setup>
import OptionsList from '../OptionsList.vue'

defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>
