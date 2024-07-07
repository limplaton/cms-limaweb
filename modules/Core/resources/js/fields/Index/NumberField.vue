<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    {{ hasValue ? formatNumber(value) : '' }}
    <span v-if="!hasValue">&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
import { useAccounting } from '../../composables/useAccounting'

defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])

const { formatNumber } = useAccounting()
</script>
