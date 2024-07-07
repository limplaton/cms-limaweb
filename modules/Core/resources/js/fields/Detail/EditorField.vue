<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <TextCollapse :text="value || ''" />

    <span v-if="!hasValue">&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>
