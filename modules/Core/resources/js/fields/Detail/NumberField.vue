<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    {{ hasValue ? formatNumber(value) : '' }}
    <span v-if="!hasValue">&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
import { useAccounting } from '../../composables/useAccounting'

defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])

const { formatNumber } = useAccounting()
</script>
