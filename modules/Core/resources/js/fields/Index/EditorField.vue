<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <TextCollapse
      v-if="hasValue"
      v-model:collapsed="collapsed"
      :text="value || ''"
      :strip-tags="!column.wrap && !collapsed"
    />

    <span v-else>&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
import { ref } from 'vue'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
const collapsed = ref(true)
</script>
