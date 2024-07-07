<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <span
      v-if="hasValue"
      class="block size-6 rounded-lg"
      :style="{ backgroundColor: value }"
    />

    <span v-else>&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>
