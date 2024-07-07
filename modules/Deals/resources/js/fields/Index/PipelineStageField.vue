<template>
  <BaseIndexField
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <span class="font-medium" v-text="field.value?.name" />

    <span
      :class="[
        'text-sm text-neutral-500 dark:text-neutral-400 sm:text-xs',
        column.wrap ? 'block' : 'ml-1',
      ]"
      v-text="row.pipeline?.name"
    />
  </BaseIndexField>
</template>

<script setup>
defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>
