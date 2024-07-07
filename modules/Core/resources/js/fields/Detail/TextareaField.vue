<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <TextCollapse v-if="hasValue" :text="value" :length="250" />

    <span v-else>&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>
