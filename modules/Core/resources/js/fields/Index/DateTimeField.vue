<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    {{ hasValue ? localizedDateTime(value) : '' }}
    <span v-if="!hasValue">&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
import { useDates } from '@/Core/composables/useDates'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])

const { localizedDateTime } = useDates()
</script>
